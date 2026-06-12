<?php

namespace Tests\Feature;

use App\Actions\Backup\CreateBackupRun;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BackupScheduleAnchoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_scheduled_run_anchors_next_run_on_theoretical_slot(): void
    {
        Carbon::setTestNow('2026-05-01 03:30:00');

        // Worker dispatched the 02:00 hourly slot 90 minutes late.
        $job = $this->hourlyJob('2026-05-01 02:00:00');

        app(CreateBackupRun::class)->handle($job, BackupRun::TRIGGER_SCHEDULED);

        $job->refresh();

        // Next slot is the grid step right after the serviced 02:00 slot, NOT 04:00
        // (which a now()-based computation at 03:30 would have produced).
        $this->assertSame('2026-05-01 03:00:00', $job->next_run_at->format('Y-m-d H:i:s'));
    }

    public function test_manual_run_does_not_advance_a_future_schedule(): void
    {
        Carbon::setTestNow('2026-05-01 12:00:00');

        $job = $this->dailyJob('2026-05-02 02:00:00');

        app(CreateBackupRun::class)->handle($job, BackupRun::TRIGGER_MANUAL);

        $job->refresh();

        // The schedule is not yet due, so a manual run must leave the slot untouched.
        $this->assertSame('2026-05-02 02:00:00', $job->next_run_at->format('Y-m-d H:i:s'));
    }

    public function test_per_job_timezone_persists_the_correct_utc_instant(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        // Daily 02:00 in Europe/Zurich (CEST = UTC+2 in June) == 00:00 UTC.
        $job = $this->job([
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'timezone' => 'Europe/Zurich',
            // Already serviced slot (02:00 Zurich on the 12th).
            'next_run_at' => '2026-06-12 00:00:00',
        ]);

        app(CreateBackupRun::class)->handle($job, BackupRun::TRIGGER_SCHEDULED);

        $job->refresh();

        // The next 02:00 Zurich slot stored as an absolute UTC instant must be
        // 00:00 UTC — not 02:00 UTC, the regression where the local wall-clock
        // was written verbatim and read back as if it were UTC.
        $this->assertSame('2026-06-13 00:00:00', $job->next_run_at->utc()->format('Y-m-d H:i:s'));
    }

    private function hourlyJob(string $nextRunAt): BackupJob
    {
        return $this->job([
            'schedule_type' => BackupJob::SCHEDULE_HOURLY,
            'schedule_config' => ['everyHours' => 1],
            'cron_expression' => '0 * * * *',
            'next_run_at' => $nextRunAt,
        ]);
    }

    private function dailyJob(?string $nextRunAt): BackupJob
    {
        return $this->job([
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'next_run_at' => $nextRunAt,
        ]);
    }

    private function job(array $attributes): BackupJob
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
            'name' => 'Host path backup',
            'source_type' => BackupJob::SOURCE_TYPE_HOST_PATH,
            'host_path' => '/srv/app-data',
            'backup_destination_id' => $destination->id,
            'status' => BackupJob::STATUS_ACTIVE,
            ...$attributes,
        ]);
    }
}
