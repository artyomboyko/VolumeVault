<?php

namespace App\Actions\Docker;

use App\Models\RestoreRun;
use App\Services\Docker\DockerProcess;
use App\Services\Docker\DockerProcessResult;
use Illuminate\Support\Str;

class RunRestoreContainer
{
    public function __construct(private readonly DockerProcess $dockerProcess) {}

    public function handle(RestoreRun $run, string $archivePath): DockerProcessResult
    {
        $containerName = 'volumevault-restore-'.$run->id.'-'.Str::lower(Str::random(8));

        $command = [
            'docker',
            'run',
            '--rm',
            '--name',
            $containerName,
            '-v',
            $run->target_volume_name.':/restore',
            '-v',
            $archivePath.':/archive/backup.tar.gz:ro',
            '--entrypoint',
            'tar',
            RunBackupContainer::IMAGE,
            '-xzf',
            '/archive/backup.tar.gz',
            '-C',
            '/restore',
            '--strip-components',
            '2',
            // Confine extraction to /restore even if the archive (from a possibly
            // untrusted destination) contains absolute paths or directory escapes.
            '--no-absolute-names',
            '--no-overwrite-dir',
        ];

        $run->forceFill(['docker_container_id' => $containerName])->save();

        return $this->dockerProcess->run($command, 0);
    }
}
