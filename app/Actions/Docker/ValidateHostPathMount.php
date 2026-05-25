<?php

namespace App\Actions\Docker;

use App\Services\Docker\DockerProcess;
use RuntimeException;

class ValidateHostPathMount
{
    public function __construct(private readonly DockerProcess $dockerProcess) {}

    public function handle(string $hostPath): void
    {
        $result = $this->dockerProcess->run([
            'docker',
            'run',
            '--rm',
            '--mount',
            'type=bind,src='.$hostPath.',dst=/volumevault-host-path-check,readonly',
            '--entrypoint',
            '/bin/sh',
            RunBackupContainer::IMAGE,
            '-c',
            'test -d /volumevault-host-path-check',
        ], 60);

        if (! $result->successful()) {
            throw new RuntimeException($result->combinedOutput() ?: 'Host path could not be mounted by Docker.');
        }
    }
}
