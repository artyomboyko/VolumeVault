<?php

namespace Tests\Feature;

use App\Actions\Alerts\EnsureAlertRules;
use App\Actions\Alerts\RunAllAlertChecks;
use App\Enums\AlertEventType;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\BackupDestination;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\JobAlertConfig;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Services\Docker\DockerProcess;
use App\Services\Docker\DockerProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AlertSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_rules_are_initialized_disabled_by_default(): void
    {
        app(EnsureAlertRules::class)->handle();

        $this->assertSame(4, AlertRule::count());
        $this->assertTrue(AlertRule::where('enabled', false)->count() === 4);
    }

    public function test_backup_too_old_alert_triggers_and_resolves(): void
    {
        $job = $this->backupJob([
            'last_success_at' => now()->subDays(8),
        ]);
        $rule = $this->enabledRule(AlertType::BackupTooOld, ['backup_too_old_days' => 7]);

        app(RunAllAlertChecks::class)->handle($rule);

        $alert = Alert::firstOrFail();
        $this->assertSame(AlertStatus::Active, $alert->status);
        $this->assertSame(AlertSeverity::Warning, $alert->severity);
        $this->assertSame(1, $alert->trigger_count);

        $job->forceFill(['last_success_at' => now()])->save();

        app(RunAllAlertChecks::class)->handle($rule);

        $this->assertSame(AlertStatus::Resolved, $alert->fresh()->status);
        $this->assertDatabaseHas('alert_events', [
            'alert_id' => $alert->id,
            'event_type' => AlertEventType::Resolved->value,
        ]);
    }

    public function test_job_never_succeeded_alert_uses_finished_run_threshold(): void
    {
        $job = $this->backupJob(['last_success_at' => null]);
        $rule = $this->enabledRule(AlertType::JobNeverSucceeded, ['job_never_succeeded_min_runs' => 3]);

        BackupRun::create(['backup_job_id' => $job->id, 'status' => BackupRun::STATUS_FAILED, 'trigger' => BackupRun::TRIGGER_SCHEDULED]);
        BackupRun::create(['backup_job_id' => $job->id, 'status' => BackupRun::STATUS_FAILED, 'trigger' => BackupRun::TRIGGER_SCHEDULED]);

        app(RunAllAlertChecks::class)->handle($rule);
        $this->assertSame(0, Alert::count());

        BackupRun::create(['backup_job_id' => $job->id, 'status' => BackupRun::STATUS_FAILED, 'trigger' => BackupRun::TRIGGER_SCHEDULED]);

        app(RunAllAlertChecks::class)->handle($rule);
        $this->assertSame(AlertStatus::Active, Alert::firstOrFail()->status);
    }

    public function test_job_in_error_too_long_uses_last_error_at(): void
    {
        $job = $this->backupJob([
            'status' => BackupJob::STATUS_ERROR,
            'last_error' => 'Boom',
            'last_error_at' => now(),
            'updated_at' => now()->subDays(10),
        ]);
        $rule = $this->enabledRule(AlertType::JobInErrorTooLong, ['job_in_error_days' => 3]);

        app(RunAllAlertChecks::class)->handle($rule);
        $this->assertSame(0, Alert::count());

        $job->forceFill(['last_error_at' => now()->subDays(4)])->save();

        app(RunAllAlertChecks::class)->handle($rule);
        $this->assertSame(AlertStatus::Active, Alert::firstOrFail()->status);
    }

    public function test_backup_size_alert_skips_successful_runs_without_size(): void
    {
        $job = $this->backupJob();
        $rule = $this->enabledRule(AlertType::BackupSizeOutOfRange);

        BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'backup_size_bytes' => null,
        ]);

        app(RunAllAlertChecks::class)->handle($rule);

        $this->assertSame(0, Alert::count());
    }

    public function test_backup_size_alert_uses_latest_successful_known_size(): void
    {
        $job = $this->backupJob();
        $rule = $this->enabledRule(AlertType::BackupSizeOutOfRange, [
            'backup_size_out_of_range_min_bytes' => 1024,
            'backup_size_out_of_range_max_bytes' => 4096,
        ]);
        $run = BackupRun::create([
            'backup_job_id' => $job->id,
            'status' => BackupRun::STATUS_SUCCESS,
            'trigger' => BackupRun::TRIGGER_SCHEDULED,
            'backup_size_bytes' => 128,
            'finished_at' => now(),
        ]);

        app(RunAllAlertChecks::class)->handle($rule);

        $alert = Alert::firstOrFail();
        $this->assertSame(AlertSeverity::Critical, $alert->severity);
        $this->assertSame($run->id, $alert->context['backup_run_id']);
    }

    public function test_job_custom_alert_settings_can_enable_a_globally_disabled_rule(): void
    {
        $job = $this->backupJob([
            'last_success_at' => now()->subDays(8),
            'use_custom_alert_settings' => true,
        ]);
        $rule = $this->disabledRule(AlertType::BackupTooOld);
        JobAlertConfig::create([
            'backup_job_id' => $job->id,
            'alert_rule_id' => $rule->id,
            'enabled' => true,
            'config' => ['backup_too_old_days' => 7],
        ]);

        app(RunAllAlertChecks::class)->handle($rule);

        $this->assertSame(AlertStatus::Active, Alert::firstOrFail()->status);
    }

    public function test_alert_notifications_ignore_backup_notification_level(): void
    {
        $job = $this->backupJob(['last_success_at' => now()->subDays(8)]);
        $rule = $this->enabledRule(AlertType::BackupTooOld, ['backup_too_old_days' => 7]);
        $channel = NotificationChannel::create([
            'name' => 'Errors only',
            'service' => NotificationChannel::SERVICE_ADVANCED,
            'url' => 'ntfy://ntfy.sh/errors',
            'notification_level' => NotificationChannel::LEVEL_ERROR,
        ]);
        $job->notificationChannels()->attach($channel);

        $dockerProcess = Mockery::mock(DockerProcess::class);
        $dockerProcess->shouldReceive('run')->once()->andReturn(new DockerProcessResult([], 0, 'ok', ''));
        $this->app->instance(DockerProcess::class, $dockerProcess);

        app(RunAllAlertChecks::class)->handle($rule);

        $this->assertDatabaseHas('alert_events', [
            'event_type' => AlertEventType::Notified->value,
        ]);
    }

    public function test_alert_notification_toggle_does_not_block_alert_creation(): void
    {
        $job = $this->backupJob([
            'last_success_at' => now()->subDays(8),
            'alert_notifications_enabled' => false,
        ]);
        $rule = $this->enabledRule(AlertType::BackupTooOld, ['backup_too_old_days' => 7]);
        $channel = NotificationChannel::create([
            'name' => 'Errors only',
            'service' => NotificationChannel::SERVICE_ADVANCED,
            'url' => 'ntfy://ntfy.sh/errors',
            'notification_level' => NotificationChannel::LEVEL_ERROR,
        ]);
        $job->notificationChannels()->attach($channel);

        $dockerProcess = Mockery::mock(DockerProcess::class);
        $dockerProcess->shouldReceive('run')->never();
        $this->app->instance(DockerProcess::class, $dockerProcess);

        app(RunAllAlertChecks::class)->handle($rule);

        $this->assertSame(AlertStatus::Active, Alert::firstOrFail()->status);
    }

    public function test_alert_settings_update_omits_null_optional_config_values(): void
    {
        app(EnsureAlertRules::class)->handle();
        $rule = AlertRule::where('type', AlertType::BackupSizeOutOfRange->value)->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->put('/alerts/settings', [
                'rules' => [[
                    'id' => $rule->id,
                    'enabled' => true,
                    'config' => [
                        'check_interval_minutes' => 60,
                        'cooldown_minutes' => 1440,
                        'reminder_enabled' => false,
                        'backup_size_out_of_range_min_bytes' => null,
                        'backup_size_out_of_range_max_bytes' => null,
                    ],
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('alerts.settings.edit'));

        $config = $rule->fresh()->config;

        $this->assertArrayNotHasKey('backup_size_out_of_range_min_bytes', $config);
        $this->assertArrayNotHasKey('backup_size_out_of_range_max_bytes', $config);
    }

    public function test_job_alert_config_update_omits_null_optional_overrides(): void
    {
        app(EnsureAlertRules::class)->handle();
        $job = $this->backupJob();
        $rule = AlertRule::where('type', AlertType::BackupSizeOutOfRange->value)->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->put('/backup-jobs/'.$job->id, [
                'name' => $job->name,
                'source_type' => BackupJob::SOURCE_TYPE_DOCKER_VOLUME,
                'volume_name' => $job->volume_name,
                'backup_destination_id' => $job->backup_destination_id,
                'schedule_type' => $job->schedule_type,
                'schedule_config' => $job->schedule_config,
                'notifications_enabled' => true,
                'notification_channel_ids' => [],
                'use_custom_alert_settings' => true,
                'alert_notifications_enabled' => true,
                'alert_configs' => [[
                    'alert_rule_id' => $rule->id,
                    'enabled' => true,
                    'config' => [
                        'check_interval_minutes' => 5,
                        'cooldown_minutes' => 60,
                        'backup_size_out_of_range_min_bytes' => null,
                        'backup_size_out_of_range_max_bytes' => null,
                    ],
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backup-jobs.index'));

        $config = JobAlertConfig::where('backup_job_id', $job->id)->where('alert_rule_id', $rule->id)->firstOrFail()->config;

        $this->assertArrayNotHasKey('check_interval_minutes', $config);
        $this->assertSame(60, $config['cooldown_minutes']);
        $this->assertArrayNotHasKey('backup_size_out_of_range_min_bytes', $config);
        $this->assertArrayNotHasKey('backup_size_out_of_range_max_bytes', $config);
    }

    private function enabledRule(AlertType $type, array $config = []): AlertRule
    {
        $rule = $this->disabledRule($type);

        $rule->forceFill([
            'enabled' => true,
            'config' => array_replace($rule->config ?? [], $config),
        ])->save();

        return $rule;
    }

    private function disabledRule(AlertType $type): AlertRule
    {
        app(EnsureAlertRules::class)->handle();

        return AlertRule::where('type', $type->value)->firstOrFail();
    }

    private function backupJob(array $attributes = []): BackupJob
    {
        return BackupJob::create([
            'name' => $attributes['name'] ?? 'Nightly',
            'volume_name' => $attributes['volume_name'] ?? 'app_data',
            'backup_destination_id' => $this->destination()->id,
            'schedule_type' => BackupJob::SCHEDULE_DAILY,
            'schedule_config' => ['time' => '02:00'],
            'cron_expression' => '0 2 * * *',
            'status' => $attributes['status'] ?? BackupJob::STATUS_ACTIVE,
            'last_success_at' => $attributes['last_success_at'] ?? null,
            'last_error' => $attributes['last_error'] ?? null,
            'last_error_at' => $attributes['last_error_at'] ?? null,
            'updated_at' => $attributes['updated_at'] ?? now(),
            'use_custom_alert_settings' => $attributes['use_custom_alert_settings'] ?? false,
            'alert_notifications_enabled' => $attributes['alert_notifications_enabled'] ?? true,
        ]);
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
}
