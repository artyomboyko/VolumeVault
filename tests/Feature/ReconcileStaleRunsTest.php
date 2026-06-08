<?php

namespace Tests\Feature;

use App\Console\Commands\ReconcileStaleRuns;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\RestoreRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileStaleRunsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_running_backup_run_is_marked_failed_and_job_reschedulable(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_RUNNING);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_RUNNING,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'started_at' => now()->subDays(2),
        ]);

        $this->artisan('volumevault:reconcile-stale-runs')->assertSuccessful();

        $run->refresh();
        $job->refresh();

        $this->assertSame(BackupRun::STATUS_FAILED, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->error_message);
        $this->assertSame(BackupJob::STATUS_ERROR, $job->status);
        $this->assertNotNull($job->next_run_at);
    }

    public function test_stale_queued_backup_run_is_marked_failed(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_ACTIVE);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_QUEUED,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
        ]);
        // No started_at; age the run from created_at.
        $run->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('volumevault:reconcile-stale-runs')->assertSuccessful();

        $this->assertSame(BackupRun::STATUS_FAILED, $run->refresh()->status);
    }

    public function test_recent_running_backup_run_is_not_swept(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_RUNNING);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_RUNNING,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->artisan('volumevault:reconcile-stale-runs')->assertSuccessful();

        $this->assertSame(BackupRun::STATUS_RUNNING, $run->refresh()->status);
    }

    public function test_succeeded_run_is_left_untouched(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_ACTIVE);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'started_at' => now()->subDays(2),
            'finished_at' => now()->subDays(2),
        ]);

        $this->artisan('volumevault:reconcile-stale-runs')->assertSuccessful();

        $this->assertSame(BackupRun::STATUS_SUCCESS, $run->refresh()->status);
    }

    public function test_custom_threshold_keeps_run_younger_than_threshold(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_RUNNING);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_RUNNING,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'started_at' => now()->subMinutes(30),
        ]);

        $this->artisan('volumevault:reconcile-stale-runs', ['--minutes' => 60])->assertSuccessful();
        $this->assertSame(BackupRun::STATUS_RUNNING, $run->refresh()->status);

        $this->artisan('volumevault:reconcile-stale-runs', ['--minutes' => 10])->assertSuccessful();
        $this->assertSame(BackupRun::STATUS_FAILED, $run->refresh()->status);
    }

    public function test_stale_restore_run_is_marked_failed(): void
    {
        $job = $this->backupJob(BackupJob::STATUS_ACTIVE);
        $run = RestoreRun::create([
            'backup_job_id' => $job->id,
            'backup_destination_id' => $job->backup_destination_id,
            'selected_backup_key' => 'backup.tar.gz',
            'source_volume_name' => 'app_data',
            'target_volume_name' => 'app_data_restored',
            'mode' => RestoreRun::MODE_NEW_VOLUME,
            'status' => RestoreRun::STATUS_RUNNING,
            'started_at' => now()->subDays(2),
        ]);

        $this->artisan('volumevault:reconcile-stale-runs')->assertSuccessful();

        $run->refresh();
        $this->assertSame(RestoreRun::STATUS_FAILED, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->error_message);
    }

    public function test_default_threshold_matches_overlap_lock_expiry(): void
    {
        // 86400s expireAfter on the queue jobs == 1440 minutes.
        $this->assertSame(86400, ReconcileStaleRuns::DEFAULT_THRESHOLD_MINUTES * 60);
    }

    private function backupJob(string $status): BackupJob
    {
        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => '/tmp/vv', 'archive_mount_source' => '/tmp/vv'],
        ]);

        return BackupJob::create([
            'name' => 'Local app backup',
            'volume_name' => 'app_data',
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => $status,
        ]);
    }
}
