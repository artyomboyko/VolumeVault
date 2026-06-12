<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Services\Logging\AppendRunLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppendRunLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_are_appended_in_order(): void
    {
        $run = $this->backupRun();

        $append = app(AppendRunLog::class);
        $append->handle($run, 'first line');
        $append->handle($run, 'second line');

        $this->assertSame("first line\nsecond line", $run->fresh()->logs);
    }

    public function test_blank_messages_are_ignored(): void
    {
        $run = $this->backupRun();

        app(AppendRunLog::class)->handle($run, '   ');
        app(AppendRunLog::class)->handle($run, null);

        $this->assertNull($run->fresh()->logs);
    }

    public function test_append_on_a_stale_instance_does_not_overwrite_a_persisted_append(): void
    {
        $run = $this->backupRun();
        // A second in-memory instance that never saw the first append — exactly
        // the situation that occurs when the catch block and the finally block
        // (or the queue failed() hook) touch the same run from stale handles.
        $stale = BackupRun::findOrFail($run->id);

        $append = app(AppendRunLog::class);
        $append->handle($run, 'error from the catch block');
        $append->handle($stale, 'restarted containers from the finally block');

        $logs = $run->fresh()->logs;
        $this->assertStringContainsString('error from the catch block', $logs);
        $this->assertStringContainsString('restarted containers from the finally block', $logs);
    }

    public function test_passed_instance_is_kept_in_sync_with_the_database(): void
    {
        $run = $this->backupRun();

        app(AppendRunLog::class)->handle($run, 'in-memory sync');

        // No refresh(): the caller's instance reflects the persisted value and
        // is no longer dirty, so a later save() cannot resurrect the old logs.
        $this->assertSame('in-memory sync', $run->logs);
        $this->assertFalse($run->isDirty('logs'));
    }

    public function test_logs_are_capped_keeping_the_most_recent_output(): void
    {
        config(['volumevault.run_logs.max_bytes' => 512]);
        $run = $this->backupRun();

        $append = app(AppendRunLog::class);
        $append->handle($run, str_repeat('old-output ', 200));
        $append->handle($run, 'most recent error message');

        $logs = $run->fresh()->logs;
        $this->assertLessThanOrEqual(512, strlen($logs));
        $this->assertStringContainsString('most recent error message', $logs);
        $this->assertStringContainsString('truncated', $logs);
    }

    public function test_capped_logs_stay_valid_utf8(): void
    {
        // Odd budget so a byte-based cut would land in the middle of a 2-byte
        // 'é' sequence; mb_strcut must keep the result valid UTF-8.
        config(['volumevault.run_logs.max_bytes' => 65]);
        $run = $this->backupRun();

        $append = app(AppendRunLog::class);
        $append->handle($run, str_repeat('é', 200));
        $append->handle($run, 'most recent line');

        $logs = $run->fresh()->logs;
        $this->assertLessThanOrEqual(65, strlen($logs));
        $this->assertTrue(mb_check_encoding($logs, 'UTF-8'));
        // The stored value must round-trip through json_encode (the API path).
        $this->assertNotFalse(json_encode(['logs' => $logs]));
        $this->assertStringContainsString('most recent line', $logs);
    }

    public function test_cap_smaller_than_the_marker_is_still_respected(): void
    {
        config(['volumevault.run_logs.max_bytes' => 10]);
        $run = $this->backupRun();

        app(AppendRunLog::class)->handle($run, str_repeat('é', 50));

        $logs = $run->fresh()->logs;
        $this->assertLessThanOrEqual(10, strlen($logs));
        $this->assertTrue(mb_check_encoding($logs, 'UTF-8'));
    }

    private function backupRun(): BackupRun
    {
        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => '/tmp', 'archive_mount_source' => '/tmp'],
        ]);

        $job = BackupJob::create([
            'name' => 'Local app backup',
            'volume_name' => 'app_data',
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
        ]);

        return BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_QUEUED,
            'trigger' => BackupRun::TRIGGER_MANUAL,
        ]);
    }
}
