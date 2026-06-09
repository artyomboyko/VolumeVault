<?php

namespace App\Services\Security;

use App\Exceptions\OutboundHostBlockedException;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Deny-by-default SSRF guard for the requests VolumeVault makes on the admin's
 * behalf (destination tests/listings, restore downloads, notifications).
 *
 * A host is resolved to its IP addresses and refused when any of them falls in
 * a private, loopback or link-local range — which is where the cloud metadata
 * endpoint (169.254.169.254) and internal services live. Ranges can be
 * re-authorised through VOLUMEVAULT_SSRF_ALLOWED_IPS, which is required in a
 * homelab where destinations (NAS, SFTP) sit on private IPs.
 *
 * Known residual: there is a DNS-rebinding window between this check and the
 * actual request (TOCTOU). It is accepted because every caller is admin-gated.
 */
class OutboundHostGuard
{
    /**
     * IP ranges refused unless re-authorised by the allowlist.
     *
     * @var array<int, string>
     */
    private const BLOCKED_RANGES = [
        '0.0.0.0/8',        // "this host" / unspecified
        '10.0.0.0/8',       // RFC1918 private
        '127.0.0.0/8',      // IPv4 loopback
        '169.254.0.0/16',   // link-local (incl. 169.254.169.254 cloud metadata)
        '172.16.0.0/12',    // RFC1918 private
        '192.168.0.0/16',   // RFC1918 private
        '::/128',           // IPv6 unspecified
        '::1/128',          // IPv6 loopback
        'fc00::/7',         // IPv6 unique local
        'fe80::/10',        // IPv6 link-local
    ];

    /**
     * Assert that the host of a URL does not resolve to a blocked address.
     */
    public function assertUrlAllowed(string $url): void
    {
        $host = (string) parse_url(trim($url), PHP_URL_HOST);

        if ($host !== '') {
            $this->assertHostAllowed($host);
        }
    }

    /**
     * Assert that none of the IPs a host resolves to is blocked.
     *
     * @throws OutboundHostBlockedException
     */
    public function assertHostAllowed(string $host): void
    {
        $host = $this->normalizeHost($host);

        if ($host === '') {
            return;
        }

        foreach ($this->resolveIps($host) as $ip) {
            if (! $this->isIpAllowed($ip)) {
                throw new OutboundHostBlockedException(sprintf(
                    'Outbound request to "%s" (%s) is blocked: it resolves to a private, loopback or link-local '
                    .'address. Add the range to VOLUMEVAULT_SSRF_ALLOWED_IPS to allow it.',
                    $host,
                    $this->normalizeIp($ip),
                ));
            }
        }
    }

    public function isHostAllowed(string $host): bool
    {
        try {
            $this->assertHostAllowed($host);

            return true;
        } catch (OutboundHostBlockedException) {
            return false;
        }
    }

    private function isIpAllowed(string $ip): bool
    {
        $ip = $this->normalizeIp($ip);

        $allowlist = $this->allowedCidrs();

        if ($allowlist !== [] && IpUtils::checkIp($ip, $allowlist)) {
            return true;
        }

        return ! IpUtils::checkIp($ip, self::BLOCKED_RANGES);
    }

    /**
     * Resolve a host to the IPv4/IPv6 addresses it points at. An IP literal
     * resolves to itself; a host that does not resolve yields an empty list
     * (the request then fails on its own — not this guard's concern).
     *
     * @return array<int, string>
     */
    protected function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = [];

        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $ips = $v4;
        }

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ipv6'])) {
                $ips[] = (string) $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    /** Strip the brackets from an IPv6 literal host ("[::1]" => "::1"). */
    private function normalizeHost(string $host): string
    {
        $host = trim($host);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        return $host;
    }

    /** Unwrap an IPv4-mapped IPv6 address ("::ffff:169.254.169.254"). */
    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip);

        if (stripos($ip, '::ffff:') === 0) {
            $tail = substr($ip, strlen('::ffff:'));

            if (filter_var($tail, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return $tail;
            }
        }

        return $ip;
    }

    /** @return array<int, string> */
    private function allowedCidrs(): array
    {
        $configured = config('volumevault.ssrf.allowed_ips', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        if (! is_array($configured)) {
            return [];
        }

        return collect($configured)
            ->map(fn (mixed $cidr): string => is_string($cidr) ? trim($cidr) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
