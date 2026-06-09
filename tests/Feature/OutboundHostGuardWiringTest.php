<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Services\BackupDestinations\TestBackupDestination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundHostGuardWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_destination_test_is_blocked_for_an_internal_sftp_host(): void
    {
        $destination = BackupDestination::create([
            'name' => 'Internal SFTP',
            'provider' => BackupDestination::PROVIDER_SSH,
            'bucket' => 'unused',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['host' => '169.254.169.254', 'port' => 22, 'remote_path' => '/srv'],
            'secrets' => ['user' => 'backup', 'password' => 'secret'],
        ]);

        $result = app(TestBackupDestination::class)->handle($destination);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('blocked', $result['message']);
    }

    public function test_destination_test_is_blocked_for_an_internal_s3_endpoint(): void
    {
        $destination = BackupDestination::create([
            'name' => 'Internal S3',
            'provider' => BackupDestination::PROVIDER_CUSTOM_S3,
            'bucket' => 'backups',
            'access_key_id' => 'key',
            'secret_access_key' => 'secret',
            'settings' => ['endpoint' => 'http://10.0.0.5:9000', 'region' => 'us-east-1'],
            'secrets' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
        ]);

        $result = app(TestBackupDestination::class)->handle($destination);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('blocked', $result['message']);
    }
}
