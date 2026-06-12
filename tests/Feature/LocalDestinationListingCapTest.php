<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Services\BackupDestinations\DestinationStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalDestinationListingCapTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_listing_is_capped_at_1000_entries(): void
    {
        $base = sys_get_temp_dir().'/volumevault-listing-cap-'.uniqid();
        File::ensureDirectoryExists($base);

        for ($i = 0; $i < 1010; $i++) {
            File::put($base.'/backup-'.$i.'.tar.gz', 'x');
        }

        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => $base],
        ]);

        $objects = app(DestinationStorage::class)->listBackupObjects($destination);

        $this->assertCount(1000, $objects);

        File::deleteDirectory($base);
    }
}
