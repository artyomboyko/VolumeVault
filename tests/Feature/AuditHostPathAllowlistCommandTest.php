<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditHostPathAllowlistCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_succeeds_when_nothing_uses_host_paths(): void
    {
        config(['volumevault.host_path_allowlist' => []]);

        $this->artisan('volumevault:host-path-allowlist:audit')
            ->expectsOutputToContain('nothing to allowlist')
            ->assertSuccessful();
    }

    public function test_it_fails_and_suggests_a_value_when_paths_are_blocked(): void
    {
        config(['volumevault.host_path_allowlist' => []]);
        $this->hostPathJob('/srv/data');

        $this->artisan('volumevault:host-path-allowlist:audit')
            ->expectsOutputToContain('VOLUMEVAULT_HOST_PATH_ALLOWLIST=/srv/data')
            ->assertFailed();

        $this->assertDatabaseHas('activity_logs', ['event_type' => 'host_path_allowlist_misconfigured']);
    }

    public function test_it_succeeds_when_the_allowlist_already_covers_paths(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv']]);
        $this->hostPathJob('/srv/data');

        $this->artisan('volumevault:host-path-allowlist:audit')
            ->expectsOutputToContain('already covers')
            ->assertSuccessful();

        $this->assertSame(0, ActivityLog::where('event_type', 'host_path_allowlist_misconfigured')->count());
    }

    private function hostPathJob(string $hostPath): void
    {
        $destination = BackupDestination::create([
            'name' => 'S3',
            'provider' => BackupDestination::PROVIDER_AWS_S3,
            'bucket' => 'backups',
            'access_key_id' => 'k',
            'secret_access_key' => 's',
        ]);

        BackupJob::create([
            'name' => 'Host job',
            'source_type' => BackupJob::SOURCE_TYPE_HOST_PATH,
            'host_path' => $hostPath,
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
        ]);
    }
}
