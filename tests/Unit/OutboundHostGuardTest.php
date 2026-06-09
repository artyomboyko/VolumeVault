<?php

namespace Tests\Unit;

use App\Exceptions\OutboundHostBlockedException;
use App\Services\Security\OutboundHostGuard;
use Tests\TestCase;

class OutboundHostGuardTest extends TestCase
{
    /**
     * A guard whose DNS resolution is stubbed, so the tests never touch the
     * network and can pin a host to arbitrary IP addresses.
     *
     * @param  array<string, array<int, string>>  $map
     */
    private function guardResolving(array $map): OutboundHostGuard
    {
        return new class($map) extends OutboundHostGuard
        {
            /** @param array<string, array<int, string>> $map */
            public function __construct(private readonly array $map) {}

            protected function resolveIps(string $host): array
            {
                return $this->map[$host] ?? [];
            }
        };
    }

    public function test_blocks_the_cloud_metadata_endpoint(): void
    {
        $guard = new OutboundHostGuard;

        $this->assertFalse($guard->isHostAllowed('169.254.169.254'));

        $this->expectException(OutboundHostBlockedException::class);
        $guard->assertHostAllowed('169.254.169.254');
    }

    public function test_blocks_loopback_and_private_ipv4_literals(): void
    {
        $guard = new OutboundHostGuard;

        foreach (['127.0.0.1', '10.0.0.5', '172.16.0.1', '192.168.1.1', '0.0.0.0'] as $ip) {
            $this->assertFalse($guard->isHostAllowed($ip), $ip.' should be blocked');
        }
    }

    public function test_allows_public_ipv4(): void
    {
        $this->assertTrue((new OutboundHostGuard)->isHostAllowed('8.8.8.8'));
    }

    public function test_blocks_ipv6_loopback_link_local_and_mapped_ipv4(): void
    {
        $guard = new OutboundHostGuard;

        $this->assertFalse($guard->isHostAllowed('::1'));
        $this->assertFalse($guard->isHostAllowed('[::1]'));
        $this->assertFalse($guard->isHostAllowed('fe80::1'));
        $this->assertFalse($guard->isHostAllowed('fc00::1'));
        // IPv4-mapped IPv6 must be unwrapped so it is matched against IPv4 ranges.
        $this->assertFalse($guard->isHostAllowed('::ffff:169.254.169.254'));
    }

    public function test_allows_public_ipv6(): void
    {
        $this->assertTrue((new OutboundHostGuard)->isHostAllowed('2606:4700:4700::1111'));
    }

    public function test_allowlist_reauthorises_a_private_range(): void
    {
        config(['volumevault.ssrf.allowed_ips' => ['192.168.0.0/16']]);
        $guard = new OutboundHostGuard;

        $this->assertTrue($guard->isHostAllowed('192.168.1.50'));
        // A private range that is not in the allowlist stays blocked.
        $this->assertFalse($guard->isHostAllowed('10.0.0.1'));
    }

    public function test_hostname_resolving_to_a_private_ip_is_blocked(): void
    {
        $guard = $this->guardResolving(['nas.internal' => ['10.0.0.10']]);

        $this->expectException(OutboundHostBlockedException::class);
        $guard->assertHostAllowed('nas.internal');
    }

    public function test_hostname_is_blocked_when_any_resolved_ip_is_private(): void
    {
        // A public + private split (a DNS-rebinding shape) must be refused.
        $guard = $this->guardResolving(['mixed.test' => ['1.1.1.1', '169.254.169.254']]);

        $this->assertFalse($guard->isHostAllowed('mixed.test'));
    }

    public function test_unresolvable_host_is_left_alone(): void
    {
        $guard = $this->guardResolving(['ghost.test' => []]);

        $guard->assertHostAllowed('ghost.test');
        $this->assertTrue($guard->isHostAllowed('ghost.test'));
    }

    public function test_assert_url_allowed_checks_the_url_host(): void
    {
        $guard = new OutboundHostGuard;

        $guard->assertUrlAllowed('https://8.8.8.8/path'); // does not throw

        $this->expectException(OutboundHostBlockedException::class);
        $guard->assertUrlAllowed('http://127.0.0.1:8080/admin');
    }

    public function test_blank_or_hostless_input_is_ignored(): void
    {
        $guard = new OutboundHostGuard;

        $guard->assertHostAllowed('');
        $guard->assertUrlAllowed('not-a-url');

        $this->assertTrue(true); // reached only when nothing threw
    }
}
