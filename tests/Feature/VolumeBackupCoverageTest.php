<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\DockerVolume;
use App\Models\User;
use App\Services\Volumes\VolumeBackupSummaries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolumeBackupCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_volume_api_returns_backup_state_stack_and_latest_size(): void
    {
        $user = User::factory()->user()->create();
        $token = $user->createToken('volumevault-read', ['read'])->plainTextToken;
        $destination = $this->destination();

        DockerVolume::create([
            'name' => 'app_data',
            'exists' => true,
            'labels' => ['com.docker.compose.project' => 'app'],
        ]);
        DockerVolume::create([
            'name' => 'cache_data',
            'exists' => true,
            'labels' => ['com.docker.stack.namespace' => 'cache'],
        ]);
        DockerVolume::create(['name' => 'logs_data', 'exists' => true]);

        $backedUpJob = $this->job($destination, 'app_data');
        $this->job($destination, 'cache_data');
        BackupRun::create([
            'backup_job_id' => $backedUpJob->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_MANUAL,
            'finished_at' => now(),
            'backup_key' => 'app/volumevault-app_data-run-1.tar.gz',
            'backup_size_bytes' => 2048,
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/volumes')->assertOk();
        $volumes = collect($response->json('data'))->keyBy('name');

        $this->assertSame('backed_up', $volumes['app_data']['backup_state']);
        $this->assertSame('app', $volumes['app_data']['stack_name']);
        $this->assertSame(2048, $volumes['app_data']['last_backup_size_bytes']);
        $this->assertSame('configured', $volumes['cache_data']['backup_state']);
        $this->assertSame('cache', $volumes['cache_data']['stack_name']);
        $this->assertSame('unprotected', $volumes['logs_data']['backup_state']);
    }

    public function test_dashboard_api_exposes_volume_coverage_and_backup_size_stats(): void
    {
        $user = User::factory()->user()->create();
        $token = $user->createToken('volumevault-read', ['read'])->plainTextToken;
        $destination = $this->destination();

        DockerVolume::create(['name' => 'app_data', 'exists' => true]);
        DockerVolume::create(['name' => 'cache_data', 'exists' => true]);
        DockerVolume::create(['name' => 'logs_data', 'exists' => true]);
        DockerVolume::create(['name' => 'missing_data', 'exists' => false]);

        $backedUpJob = $this->job($destination, 'app_data');
        $this->job($destination, 'cache_data');
        BackupRun::create([
            'backup_job_id' => $backedUpJob->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_MANUAL,
            'finished_at' => now(),
            'backup_size_bytes' => 4096,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.total_volumes', 4)
            ->assertJsonPath('data.stats.existing_volumes', 3)
            ->assertJsonPath('data.stats.missing_volumes', 1)
            ->assertJsonPath('data.stats.backed_up_volumes', 1)
            ->assertJsonPath('data.stats.configured_volumes', 1)
            ->assertJsonPath('data.stats.unprotected_volumes', 1)
            ->assertJsonPath('data.stats.total_jobs', 2)
            ->assertJsonPath('data.stats.last_successful_backup_size', 4096);
    }

    public function test_stack_summaries_group_compose_swarm_and_unlabelled_volumes(): void
    {
        $destination = $this->destination();
        DockerVolume::create([
            'name' => 'app_data',
            'exists' => true,
            'labels' => ['com.docker.compose.project' => 'app'],
        ]);
        DockerVolume::create([
            'name' => 'app_logs',
            'exists' => true,
            'labels' => ['com.docker.compose.project' => 'app'],
        ]);
        DockerVolume::create([
            'name' => 'worker_data',
            'exists' => true,
            'labels' => ['com.docker.stack.namespace' => 'worker'],
        ]);
        DockerVolume::create(['name' => 'loose_data', 'exists' => true]);

        $job = $this->job($destination, 'app_data');
        $this->job($destination, 'worker_data');
        BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_MANUAL,
            'finished_at' => now(),
            'backup_size_bytes' => 1024,
        ]);

        $stacks = app(VolumeBackupSummaries::class)
            ->forStacks(DockerVolume::query()->orderBy('name')->get())
            ->keyBy(fn (array $stack): string => $stack['name'] ?? 'none');

        $this->assertSame('partially_configured', $stacks['app']['configuration_state']);
        $this->assertSame(1, $stacks['app']['configured_job_volumes']);
        $this->assertSame(2, $stacks['app']['existing_volumes']);
        $this->assertSame(1, $stacks['app']['backed_up_volumes']);
        $this->assertSame(1024, $stacks['app']['last_backup_size_bytes']);
        $this->assertSame('configured', $stacks['worker']['configuration_state']);
        $this->assertSame(1, $stacks['worker']['configured_volumes']);
        $this->assertSame(0, $stacks['worker']['unprotected_volumes']);
        $this->assertSame('not_configured', $stacks['none']['configuration_state']);
        $this->assertSame(1, $stacks['none']['unprotected_volumes']);
    }

    private function destination(): BackupDestination
    {
        return BackupDestination::create([
            'name' => 'S3',
            'provider' => BackupDestination::PROVIDER_AWS_S3,
            'bucket' => 'backups',
            'access_key_id' => 'access',
            'secret_access_key' => 'secret',
        ]);
    }

    private function job(BackupDestination $destination, string $volumeName): BackupJob
    {
        return BackupJob::create([
            'name' => 'Backup '.$volumeName,
            'volume_name' => $volumeName,
            'backup_destination_id' => $destination->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => BackupJob::STATUS_ACTIVE,
        ]);
    }
}
