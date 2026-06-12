<?php

namespace App\Actions\Docker;

use App\Services\Docker\DockerProcess;

class ContainerIsAlive
{
    public function __construct(private readonly DockerProcess $dockerProcess) {}

    /**
     * Whether a container is still present and running.
     *
     * Backup/restore containers run with `--rm`, so once the process exits
     * (normally or on a crash) the container is removed and `docker inspect`
     * reports "No such object".
     *
     * Returns a tri-state so callers can distinguish a genuinely dead container
     * from a transient inability to ask Docker at all:
     *  - true  = the container exists and is running (leave the run alone).
     *  - false = the container is confirmed gone (or exists but stopped).
     *  - null  = could not determine (Docker daemon unreachable, timeout,
     *            permission, …) — the caller should NOT treat this as dead, or a
     *            healthy long-running backup would be failed on a transient blip.
     */
    public function handle(?string $reference): ?bool
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            return false;
        }

        $result = $this->dockerProcess->run(
            ['docker', 'inspect', '--format', '{{.State.Running}}', $reference],
            60,
        );

        if ($result->successful()) {
            return trim($result->output) === 'true';
        }

        // A timeout means we never got an answer — treat as indeterminate.
        if ($result->timedOut) {
            return null;
        }

        // Docker answered that the object does not exist → confirmed gone.
        // Anything else (daemon unreachable, permission denied, …) is
        // indeterminate and must not be read as "dead".
        return str_contains(strtolower($result->combinedOutput()), 'no such object') ? false : null;
    }
}
