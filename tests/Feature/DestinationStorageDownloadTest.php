<?php

namespace Tests\Feature;

use App\Models\BackupDestination;
use App\Services\BackupDestinations\DestinationStorage;
use App\Services\S3\S3ClientFactory;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use phpseclib3\Net\SFTP;
use RuntimeException;
use Tests\TestCase;

class DestinationStorageDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_download_copies_the_backup_file(): void
    {
        $base = sys_get_temp_dir().'/volumevault-download-local-'.uniqid();
        File::ensureDirectoryExists($base);
        File::put($base.'/backup.tar.gz', 'archive-bytes');

        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => $base],
        ]);

        $target = $base.'/restored.tar.gz';
        app(DestinationStorage::class)->download($destination, 'backup.tar.gz', $target);

        $this->assertFileExists($target);
        $this->assertSame('archive-bytes', File::get($target));

        File::deleteDirectory($base);
    }

    public function test_local_download_throws_when_the_source_is_missing(): void
    {
        $base = sys_get_temp_dir().'/volumevault-download-missing-'.uniqid();
        File::ensureDirectoryExists($base);

        $destination = BackupDestination::create([
            'name' => 'Local',
            'provider' => BackupDestination::PROVIDER_LOCAL,
            'bucket' => 'local',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['archive_path' => $base],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local backup file does not exist.');

        try {
            app(DestinationStorage::class)->download($destination, 'nope.tar.gz', $base.'/out.tar.gz');
        } finally {
            File::deleteDirectory($base);
        }
    }

    public function test_s3_download_requests_the_key_and_saves_to_the_target_path(): void
    {
        $destination = BackupDestination::create([
            'name' => 'S3',
            'provider' => BackupDestination::PROVIDER_AWS_S3,
            'bucket' => 'backups',
            'access_key_id' => 'access',
            'secret_access_key' => 'secret',
        ]);

        $target = sys_get_temp_dir().'/volumevault-download-s3-'.uniqid().'.tar.gz';

        $client = Mockery::mock(S3Client::class);
        $client->shouldReceive('getObject')
            ->once()
            ->with([
                'Bucket' => 'backups',
                'Key' => 'path/to/backup.tar.gz',
                'SaveAs' => $target,
            ]);

        $factory = Mockery::mock(S3ClientFactory::class);
        $factory->shouldReceive('make')->once()->with($destination)->andReturn($client);

        (new DestinationStorage($factory))->download($destination, 'path/to/backup.tar.gz', $target);
    }

    public function test_sftp_download_joins_the_remote_path_and_pulls_the_file(): void
    {
        $destination = $this->sftpDestination();
        $target = sys_get_temp_dir().'/volumevault-download-sftp-'.uniqid().'.tar.gz';

        $sftp = Mockery::mock(SFTP::class);
        $sftp->shouldReceive('get')
            ->once()
            ->with('/srv/backups/backup.tar.gz', $target)
            ->andReturnTrue();

        $this->storageWithSftp($sftp)->download($destination, 'backup.tar.gz', $target);
    }

    public function test_sftp_download_throws_when_the_transfer_fails(): void
    {
        $destination = $this->sftpDestination();

        $sftp = Mockery::mock(SFTP::class);
        $sftp->shouldReceive('get')->once()->andReturnFalse();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to download file over SFTP.');

        $this->storageWithSftp($sftp)->download($destination, 'backup.tar.gz', '/tmp/whatever.tar.gz');
    }

    public function test_webdav_download_targets_the_remote_path_and_raises_on_failure(): void
    {
        Http::fake([
            '*' => Http::response('not found', 404),
        ]);

        $destination = BackupDestination::create([
            'name' => 'WebDAV',
            'provider' => BackupDestination::PROVIDER_WEBDAV,
            'bucket' => 'unused',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['url' => 'https://dav.example.com/remote.php/dav', 'path' => 'backups'],
            'secrets' => ['username' => 'user', 'password' => 'pass'],
        ]);

        try {
            app(DestinationStorage::class)->download($destination, 'backup.tar.gz', sys_get_temp_dir().'/wd.tar.gz');
            $this->fail('A failed WebDAV response should raise a RuntimeException.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('WebDAV request failed', $exception->getMessage());
        }

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://dav.example.com/remote.php/dav/backups/backup.tar.gz');
    }

    private function sftpDestination(): BackupDestination
    {
        return BackupDestination::create([
            'name' => 'SFTP',
            'provider' => BackupDestination::PROVIDER_SSH,
            'bucket' => 'unused',
            'access_key_id' => '',
            'secret_access_key' => '',
            'settings' => ['host' => 'ssh.example.com', 'port' => 22, 'remote_path' => '/srv/backups'],
            'secrets' => ['user' => 'backup', 'password' => 'secret'],
        ]);
    }

    private function storageWithSftp(SFTP $sftp): DestinationStorage
    {
        return new class(app(S3ClientFactory::class), $sftp) extends DestinationStorage
        {
            public function __construct(S3ClientFactory $factory, private readonly SFTP $sftpMock)
            {
                parent::__construct($factory);
            }

            protected function sftp(BackupDestination $destination): SFTP
            {
                return $this->sftpMock;
            }
        };
    }
}
