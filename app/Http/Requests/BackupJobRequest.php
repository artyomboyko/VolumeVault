<?php

namespace App\Http\Requests;

use App\Actions\Docker\ValidateHostPathMount;
use App\Models\BackupJob;
use App\Services\BackupSources\HostPathPolicy;
use App\Services\Scheduling\BackupScheduleCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Throwable;

class BackupJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sourceType = (string) ($this->input('source_type') ?: BackupJob::SOURCE_TYPE_DOCKER_VOLUME);
        $hostPath = app(HostPathPolicy::class)->normalize($this->input('host_path'));

        $this->merge([
            'source_type' => $sourceType,
            'host_path' => $hostPath !== '' ? $hostPath : null,
            'volume_name' => $sourceType === BackupJob::SOURCE_TYPE_HOST_PATH ? null : $this->input('volume_name'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', Rule::in([
                BackupJob::SOURCE_TYPE_DOCKER_VOLUME,
                BackupJob::SOURCE_TYPE_HOST_PATH,
            ])],
            'volume_name' => ['required_if:source_type,'.BackupJob::SOURCE_TYPE_DOCKER_VOLUME, 'nullable', 'string', 'max:255'],
            'host_path' => ['required_if:source_type,'.BackupJob::SOURCE_TYPE_HOST_PATH, 'nullable', 'string', 'max:255'],
            'backup_destination_id' => ['required', 'integer', 'exists:backup_destinations,id'],
            'schedule_type' => ['required', 'string', Rule::in([
                BackupJob::SCHEDULE_HOURLY,
                BackupJob::SCHEDULE_DAILY,
                BackupJob::SCHEDULE_WEEKLY,
                BackupJob::SCHEDULE_CRON,
            ])],
            'schedule_config' => ['nullable', 'array'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
            'retention_count' => ['nullable', 'integer', 'min:1'],
            'backup_exclude_regexp' => ['nullable', 'string', 'max:1000'],
            'notifications_enabled' => ['boolean'],
            'notification_channel_ids' => ['nullable', 'array'],
            'notification_channel_ids.*' => ['integer', 'distinct', 'exists:notification_channels,id'],
            'use_custom_alert_settings' => ['boolean'],
            'alert_notifications_enabled' => ['boolean'],
            'alert_configs' => ['nullable', 'array'],
            'alert_configs.*.alert_rule_id' => ['required_with:alert_configs', 'integer', 'distinct', 'exists:alert_rules,id'],
            'alert_configs.*.enabled' => ['nullable', 'boolean'],
            'alert_configs.*.config' => ['nullable', 'array'],
            'alert_configs.*.config.cooldown_minutes' => ['nullable', 'integer', 'min:0'],
            'alert_configs.*.config.reminder_enabled' => ['nullable', 'boolean'],
            'alert_configs.*.config.backup_too_old_days' => ['nullable', 'integer', 'min:1'],
            'alert_configs.*.config.job_never_succeeded_min_runs' => ['nullable', 'integer', 'min:1'],
            'alert_configs.*.config.job_in_error_days' => ['nullable', 'integer', 'min:1'],
            'alert_configs.*.config.backup_size_out_of_range_min_bytes' => ['nullable', 'integer', 'min:0'],
            'alert_configs.*.config.backup_size_out_of_range_max_bytes' => ['nullable', 'integer', 'min:1'],
            'stop_containers_before_backup' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            try {
                app(BackupScheduleCalculator::class)->normalize($this->input('schedule_type'), $this->input('schedule_config', []));
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add('schedule_config', $exception->getMessage());
            }

            $this->validateHostPathSource($validator);
            $this->validateAlertSizeRanges($validator);
        });
    }

    public function normalizedScheduleConfig(): array
    {
        return app(BackupScheduleCalculator::class)->normalize($this->input('schedule_type'), $this->input('schedule_config', []));
    }

    private function validateHostPathSource(Validator $validator): void
    {
        if ($this->input('source_type') !== BackupJob::SOURCE_TYPE_HOST_PATH) {
            return;
        }

        $hostPath = (string) $this->input('host_path', '');

        if ($hostPath === '') {
            return;
        }

        $policy = app(HostPathPolicy::class);

        if ($message = $policy->validationError($hostPath)) {
            $validator->errors()->add('host_path', $message);
        }

        if ($this->boolean('stop_containers_before_backup')) {
            $validator->errors()->add('stop_containers_before_backup', 'Stopping containers before backup is only available for Docker volume sources.');
        }

        if ($validator->errors()->has('host_path')) {
            return;
        }

        try {
            app(ValidateHostPathMount::class)->handle($hostPath);
        } catch (Throwable $exception) {
            $validator->errors()->add('host_path', str($exception->getMessage() ?: 'Host path could not be mounted by Docker.')->limit(500)->toString());
        }
    }

    private function validateAlertSizeRanges(Validator $validator): void
    {
        foreach ($this->input('alert_configs', []) as $index => $alertConfig) {
            $config = $alertConfig['config'] ?? [];

            if (! array_key_exists('backup_size_out_of_range_min_bytes', $config) || ! array_key_exists('backup_size_out_of_range_max_bytes', $config)) {
                continue;
            }

            if ($config['backup_size_out_of_range_min_bytes'] === null || $config['backup_size_out_of_range_min_bytes'] === '' || $config['backup_size_out_of_range_max_bytes'] === null || $config['backup_size_out_of_range_max_bytes'] === '') {
                continue;
            }

            if ((int) $config['backup_size_out_of_range_min_bytes'] > (int) $config['backup_size_out_of_range_max_bytes']) {
                $validator->errors()->add('alert_configs.'.$index.'.config.backup_size_out_of_range_max_bytes', 'The maximum backup size must be greater than or equal to the minimum backup size.');
            }
        }
    }
}
