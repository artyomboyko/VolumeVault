<?php

namespace Tests\Feature;

use App\Actions\Restore\RunRestore;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\RestoreRun;
use App\Services\BackupDestinations\DestinationStorage;
use App\Services\Docker\DockerProcess;
use App\Services\Docker\DockerProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RunRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_new_volume_mode_is_supported(): void
    {
        $storage = Mockery::mock(DestinationStorage::class);
        $storage->shouldNotReceive('download');
        $this->app->instance(DestinationStorage::class, $storage);
        $this->app->instance(DockerProcess::class, $this->docker(volumeExists: false));

        $run = $this->restoreRun(['mode' => RestoreRun::MODE_INPLACE]);

        app(RunRestore::class)->handle($run);
        $run->refresh();

        $this->assertSame(RestoreRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('Only restore-to-new-volume is currently enabled.', $run->error_message);
    }

    public function test_run_fails_when_the_target_volume_already_exists(): void
    {
        $storage = Mockery::mock(DestinationStorage::class);
        // The pre-flight guard must trip before we ever try to download anything.
        $storage->shouldNotReceive('download');
        $this->app->instance(DestinationStorage::class, $storage);
        $this->app->instance(DockerProcess::class, $this->docker(volumeExists: true));

        $run = $this->restoreRun();

        app(RunRestore::class)->handle($run);
        $run->refresh();

        $this->assertSame(RestoreRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('Target Docker volume already exists', $run->error_message);
    }

    public function test_markFailed_is_idempotent_for_terminal_runs(): void
    {
        $run = $this->restoreRun(['status' => RestoreRun::STATUS_SUCCESS, 'finished_at' => now()->subHour()]);

        app(RunRestore::class)->markFailed($run, new \RuntimeException('late failure'));
        $run->refresh();

        // A run that already succeeded must not be flipped to FAILED by a late failed() hook.
        $this->assertSame(RestoreRun::STATUS_SUCCESS, $run->status);
        $this->assertNull($run->error_message);
    }

    private function restoreRun(array $overrides = []): RestoreRun
    {
        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => sys_get_temp_dir()],
        ]);

        $job = BackupJob::create([
            'name' => 'Job',
            'volume_name' => 'app_data',
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
        ]);

        return RestoreRun::create(array_merge([
            'backup_job_id' => $job->id,
            'backup_destination_id' => $destination->id,
            'selected_backup_key' => 'backup.tar.gz',
            'source_volume_name' => 'app_data',
            'target_volume_name' => 'app_data_restored',
            'mode' => RestoreRun::MODE_NEW_VOLUME,
            'status' => RestoreRun::STATUS_QUEUED,
        ], $overrides));
    }

    private function docker(bool $volumeExists): DockerProcess
    {
        return new class($volumeExists) extends DockerProcess
        {
            public function __construct(private readonly bool $volumeExists) {}

            public function run(array $command, int $timeout = 300, array $environment = []): DockerProcessResult
            {
                if (($command[1] ?? null) === 'volume' && ($command[2] ?? null) === 'inspect') {
                    return $this->volumeExists
                        ? new DockerProcessResult($command, 0, '[{"Name":"app_data_restored"}]', '')
                        : new DockerProcessResult($command, 1, '', 'no such volume');
                }

                return new DockerProcessResult($command, 0, '', '');
            }
        };
    }
}
