<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Services\BackupSources\HostPathAllowlistAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HostPathAllowlistAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_records_reports_no_misconfiguration(): void
    {
        config(['volumevault.host_path_allowlist' => []]);

        $audit = app(HostPathAllowlistAudit::class);

        $this->assertSame([], $audit->pathsInUse());
        $this->assertFalse($audit->hasMisconfiguration());
        $this->assertFalse($audit->reportMisconfiguration());
    }

    public function test_blocked_paths_are_detected_and_a_suggested_allowlist_is_derived(): void
    {
        config(['volumevault.host_path_allowlist' => []]);
        $this->hostPathJob('/srv/data');
        $this->localDestination('/mnt/backups', '/mnt/backups');

        $audit = app(HostPathAllowlistAudit::class);

        $this->assertEqualsCanonicalizing(['/srv/data', '/mnt/backups'], $audit->pathsInUse());
        $this->assertEqualsCanonicalizing(['/srv/data', '/mnt/backups'], $audit->blockedPaths());
        $this->assertTrue($audit->hasMisconfiguration());
        $this->assertSame(['/mnt/backups', '/srv/data'], $audit->suggestedAllowlist());
        $this->assertSame('VOLUMEVAULT_HOST_PATH_ALLOWLIST=/mnt/backups,/srv/data', $audit->suggestedEnvLine());
    }

    public function test_paths_already_covered_are_not_flagged(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv', '/mnt']]);
        $this->hostPathJob('/srv/data');
        $this->localDestination('/mnt/backups', '/mnt/backups');

        $audit = app(HostPathAllowlistAudit::class);

        $this->assertSame([], $audit->blockedPaths());
        $this->assertFalse($audit->hasMisconfiguration());
    }

    public function test_suggestion_merges_existing_prefixes_with_blocked_paths(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv']]);
        $this->hostPathJob('/srv/data');           // already covered
        $this->localDestination('/mnt/backups');   // not covered

        $audit = app(HostPathAllowlistAudit::class);

        $this->assertSame(['/mnt/backups'], $audit->blockedPaths());
        $this->assertSame(['/mnt/backups', '/srv'], $audit->suggestedAllowlist());
    }

    public function test_report_records_activity_log_once_then_throttles(): void
    {
        config(['volumevault.host_path_allowlist' => []]);
        $this->hostPathJob('/srv/data');

        $audit = app(HostPathAllowlistAudit::class);

        $this->assertTrue($audit->reportMisconfiguration());
        $this->assertSame(1, ActivityLog::where('event_type', 'host_path_allowlist_misconfigured')->count());

        // Within the throttle window, the warning is not recorded again.
        $this->assertTrue($audit->reportMisconfiguration());
        $this->assertSame(1, ActivityLog::where('event_type', 'host_path_allowlist_misconfigured')->count());

        Cache::flush();
        $this->assertTrue($audit->reportMisconfiguration());
        $this->assertSame(2, ActivityLog::where('event_type', 'host_path_allowlist_misconfigured')->count());
    }

    private function hostPathJob(string $hostPath): BackupJob
    {
        $destination = BackupDestination::create([
            'name' => 'S3 '.$hostPath,
            'provider' => BackupDestination::PROVIDER_AWS_S3,
            'bucket' => 'backups',
            'access_key_id' => 'k',
            'secret_access_key' => 's',
        ]);

        return BackupJob::create([
            'name' => 'Host job '.$hostPath,
            'source_type' => BackupJob::SOURCE_TYPE_HOST_PATH,
            'host_path' => $hostPath,
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
        ]);
    }

    private function localDestination(string $archivePath, ?string $mountSource = null): BackupDestination
    {
        return BackupDestination::create([
            'name' => 'Local '.$archivePath,
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => array_filter([
                'archive_path' => $archivePath,
                'archive_mount_source' => $mountSource,
            ]),
        ]);
    }
}
