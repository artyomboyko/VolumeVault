<?php

namespace App\Services\Docker;

class SelfContainerResolver
{
    /** @var list<string>|null */
    private ?array $cachedIdentifiers = null;

    /**
     * Split the containers found on a volume into those that may be stopped and
     * those that belong to the VolumeVault process itself.
     *
     * A backup job whose volume is also mounted by the VolumeVault container
     * must never stop that container: doing so would kill the running backup
     * (and the worker) mid-flight. Such containers are returned in the second
     * slot so the caller can log and skip them.
     *
     * @param  list<array{id?:?string,names?:?string}>  $containers
     * @return array{0: list<array>, 1: list<array>} [stoppable, self]
     */
    public function partitionSelf(array $containers): array
    {
        $stoppable = [];
        $self = [];

        foreach ($containers as $container) {
            if ($this->matches($container)) {
                $self[] = $container;
            } else {
                $stoppable[] = $container;
            }
        }

        return [$stoppable, $self];
    }

    /**
     * Whether the given `docker ps` row identifies the container this process
     * runs in. Always false when running outside a container (local dev/tests)
     * since no identifier can be resolved.
     *
     * @param  array{id?:?string,names?:?string}  $container
     */
    public function matches(array $container): bool
    {
        $identifiers = $this->identifiers();

        if (! $identifiers) {
            return false;
        }

        foreach ($this->candidatesFor($container) as $candidate) {
            foreach ($identifiers as $identifier) {
                if ($this->idEquivalent($identifier, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Lower-cased identifiers (ids / hostname / configured name) for the current
     * container. Empty outside a container.
     *
     * @return list<string>
     */
    public function identifiers(): array
    {
        if ($this->cachedIdentifiers !== null) {
            return $this->cachedIdentifiers;
        }

        $identifiers = [];

        // Explicit overrides win: a deployer can pin the name/id when hostname
        // autodetection is unreliable (custom --hostname, host networking).
        foreach (['self_container.id', 'self_container.name'] as $key) {
            $value = trim((string) config("volumevault.{$key}", ''));

            if ($value !== '') {
                $identifiers[] = $value;
            }
        }

        // Docker sets the hostname to the short (12-char) container id unless
        // overridden, so it usually matches the `docker ps` ID column directly.
        $hostname = trim((string) gethostname());

        if ($hostname !== '') {
            $identifiers[] = $hostname;
        }

        // The full 64-char id lives in the cgroup/mountinfo of the process.
        foreach ($this->idsFromProc() as $id) {
            $identifiers[] = $id;
        }

        return $this->cachedIdentifiers = array_values(array_unique(array_map('strtolower', $identifiers)));
    }

    /**
     * Candidate strings drawn from a `docker ps` row: the container id plus each
     * of its (possibly comma-separated, slash-prefixed) names.
     *
     * @param  array{id?:?string,names?:?string}  $container
     * @return list<string>
     */
    private function candidatesFor(array $container): array
    {
        $candidates = [];

        $id = strtolower(trim((string) ($container['id'] ?? '')));

        if ($id !== '') {
            $candidates[] = $id;
        }

        foreach (explode(',', (string) ($container['names'] ?? '')) as $name) {
            $name = strtolower(trim($name, " \t\n\r\0\x0B/"));

            if ($name !== '') {
                $candidates[] = $name;
            }
        }

        return $candidates;
    }

    /**
     * Whether two identifiers refer to the same container. Exact match, or the
     * shorter is a hex prefix of the longer (the abbreviated `docker ps` id vs
     * the full id from /proc), requiring >= 12 hex chars to stay unambiguous.
     */
    private function idEquivalent(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $short = strlen($a) <= strlen($b) ? $a : $b;
        $long = $short === $a ? $b : $a;

        return strlen($short) >= 12
            && ctype_xdigit($short)
            && ctype_xdigit($long)
            && str_starts_with($long, $short);
    }

    /**
     * Full 64-char container ids referenced in this process's cgroup/mountinfo.
     *
     * @return list<string>
     */
    private function idsFromProc(): array
    {
        $ids = [];

        foreach (['/proc/self/mountinfo', '/proc/self/cgroup'] as $path) {
            if (! @is_readable($path)) {
                continue;
            }

            $contents = @file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            if (preg_match_all('/\b[0-9a-f]{64}\b/', $contents, $matches)) {
                foreach ($matches[0] as $id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
