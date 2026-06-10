<?php

namespace Tests\Feature;

use App\Actions\Backup\RunBackup;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\DockerVolume;
use App\Services\Docker\DockerProcess;
use App\Services\Docker\DockerProcessResult;
use App\Services\Docker\SelfContainerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SelfContainerExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['volumevault.host_path_allowlist' => array_unique([
            sys_get_temp_dir(),
            realpath(sys_get_temp_dir()) ?: sys_get_temp_dir(),
        ])]);
    }

    public function test_backup_does_not_stop_volumevault_own_container(): void
    {
        $archivePath = sys_get_temp_dir().'/volumevault-self-exclusion';
        File::deleteDirectory($archivePath);
        File::ensureDirectoryExists($archivePath);

        // Two containers mount the volume: the app's own container (self) and a
        // genuine consumer that should be stopped.
        $docker = $this->dockerProcess([
            ['ID' => 'aaaaaaaaaaaa', 'Names' => 'volumevault', 'Image' => 'volumevault:local', 'State' => 'running', 'Status' => 'Up'],
            ['ID' => 'bbbbbbbbbbbb', 'Names' => 'app', 'Image' => 'app:latest', 'State' => 'running', 'Status' => 'Up'],
        ], $archivePath);
        $this->app->instance(DockerProcess::class, $docker);

        // Pretend this process runs inside the "volumevault" container.
        $this->app->instance(SelfContainerResolver::class, new class extends SelfContainerResolver
        {
            public function identifiers(): array
            {
                return ['aaaaaaaaaaaa'];
            }
        });

        $run = $this->backupRun($archivePath);
        app(RunBackup::class)->handle($run);
        $run->refresh();

        $this->assertSame(BackupRun::STATUS_SUCCESS, $run->status);
        $this->assertSame([['stop', 'bbbbbbbbbbbb'], ['start', 'bbbbbbbbbbbb']], $docker->lifecycleCalls);
        $this->assertStringContainsString("Skipping VolumeVault's own container (aaaaaaaaaaaa)", $run->logs);
    }

    private function backupRun(string $archivePath): BackupRun
    {
        DockerVolume::create(['name' => 'app_data', 'exists' => true]);
        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => $archivePath, 'archive_mount_source' => $archivePath],
        ]);
        $job = BackupJob::create([
            'name' => 'Local app backup',
            'volume_name' => 'app_data',
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
            'stop_containers_before_backup' => true,
        ]);

        return BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_QUEUED,
            'trigger' => BackupRun::TRIGGER_MANUAL,
        ]);
    }

    /**
     * @param  list<array<string,string>>  $containersOnVolume
     */
    private function dockerProcess(array $containersOnVolume, string $archivePath): DockerProcess
    {
        return new class($containersOnVolume) extends DockerProcess
        {
            /** @var list<array{0:string,1:string}> */
            public array $lifecycleCalls = [];

            /** @param list<array<string,string>> $containersOnVolume */
            public function __construct(private readonly array $containersOnVolume) {}

            public function run(array $command, int $timeout = 300, array $environment = []): DockerProcessResult
            {
                [$bin, $verb] = [$command[0] ?? null, $command[1] ?? null];

                if ($bin === 'docker' && $verb === 'volume' && ($command[2] ?? null) === 'inspect') {
                    return new DockerProcessResult($command, 0, json_encode([[
                        'Name' => 'app_data',
                        'Driver' => 'local',
                        'Mountpoint' => '/var/lib/docker/volumes/app_data/_data',
                        'Labels' => [],
                        'Options' => [],
                    ]], JSON_THROW_ON_ERROR), '');
                }

                if ($bin === 'docker' && $verb === 'ps') {
                    $lines = array_map(fn (array $c): string => json_encode($c, JSON_THROW_ON_ERROR), $this->containersOnVolume);

                    return new DockerProcessResult($command, 0, implode("\n", $lines), '');
                }

                if ($bin === 'docker' && in_array($verb, ['stop', 'start'], true)) {
                    $this->lifecycleCalls[] = [$verb, $command[2] ?? ''];

                    return new DockerProcessResult($command, 0, '', '');
                }

                if ($bin === 'docker' && $verb === 'run') {
                    if (isset($environment['BACKUP_ARCHIVE'], $environment['BACKUP_FILENAME'])) {
                        File::put($environment['BACKUP_ARCHIVE'].'/'.$environment['BACKUP_FILENAME'], str_repeat('x', 1536));
                    }

                    return new DockerProcessResult($command, 0, 'backup complete', '');
                }

                return new DockerProcessResult($command, 0, '', '');
            }
        };
    }
}
