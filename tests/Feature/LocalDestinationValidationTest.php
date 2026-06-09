<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalDestinationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_destination_with_path_outside_the_allowlist_is_rejected(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv/backups']]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/destinations/create')
            ->post('/destinations', $this->payload(['archive_path' => '/root/.ssh']))
            ->assertSessionHasErrors('settings.archive_path');

        $this->assertSame(0, BackupDestination::count());
    }

    public function test_local_destination_with_empty_allowlist_is_rejected_fail_closed(): void
    {
        config(['volumevault.host_path_allowlist' => []]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/destinations/create')
            ->post('/destinations', $this->payload(['archive_path' => '/srv/backups']))
            ->assertSessionHasErrors('settings.archive_path');

        $this->assertSame(0, BackupDestination::count());
    }

    public function test_colon_in_a_local_path_is_rejected(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv/backups']]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/destinations/create')
            ->post('/destinations', $this->payload([
                'archive_path' => '/srv/backups',
                'archive_mount_source' => '/srv/backups:/etc/cron.d',
            ]))
            ->assertSessionHasErrors('settings.archive_mount_source');

        $this->assertSame(0, BackupDestination::count());
    }

    public function test_local_destination_inside_the_allowlist_is_accepted(): void
    {
        config(['volumevault.host_path_allowlist' => ['/srv/backups']]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/destinations/create')
            ->post('/destinations', $this->payload([
                'archive_path' => '/srv/backups/volumevault',
                'archive_mount_source' => '/srv/backups/volumevault',
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, BackupDestination::count());
    }

    /**
     * @param  array<string, string>  $settings
     * @return array<string, mixed>
     */
    private function payload(array $settings): array
    {
        return [
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'is_active' => true,
            'settings' => $settings,
        ];
    }
}
