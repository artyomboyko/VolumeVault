<?php

namespace App\Http\Requests\Concerns;

use App\Models\BackupDestination;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesBackupDestination
{
    /**
     * Rules shared by the store and update destination requests.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', Rule::in(BackupDestination::PROVIDERS)],
            'endpoint' => ['nullable', 'url', 'max:2048'],
            'region' => ['nullable', 'string', 'max:255'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'path_prefix' => ['nullable', 'string', 'max:255'],
            'access_key_id' => ['nullable', 'string'],
            'secret_access_key' => ['nullable', 'string'],
            'use_path_style_endpoint' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'secrets' => ['nullable', 'array'],
            'settings.*' => ['nullable'],
            'secrets.*' => ['nullable'],
            'settings.storage_limit_warning_bytes' => ['nullable', 'integer', 'min:0'],
            'settings.storage_limit_critical_bytes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Provider-specific rules. When $secretsRequired is true (store), credentials are
     * mandatory; when false (update), they stay optional so existing secrets are kept.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function providerRules(bool $secretsRequired): array
    {
        return match ($this->input('provider')) {
            BackupDestination::PROVIDER_AWS_S3,
            BackupDestination::PROVIDER_CLOUDFLARE_R2,
            BackupDestination::PROVIDER_CUSTOM_S3 => [
                'endpoint' => ['nullable', 'required_if:provider,cloudflare_r2,custom_s3', 'url', 'max:2048'],
                'bucket' => ['required', 'string', 'max:255'],
                'access_key_id' => [$secretsRequired ? 'required_without:secrets.access_key_id' : 'nullable', 'string'],
                'secret_access_key' => [$secretsRequired ? 'required_without:secrets.secret_access_key' : 'nullable', 'string'],
                'secrets.access_key_id' => ['nullable', 'string'],
                'secrets.secret_access_key' => ['nullable', 'string'],
            ],
            BackupDestination::PROVIDER_WEBDAV => [
                'settings.url' => ['required', 'url', 'max:2048'],
                'settings.path' => ['nullable', 'string', 'max:2048'],
                'settings.insecure' => ['boolean'],
                'secrets.username' => ['nullable', 'string'],
                'secrets.password' => ['nullable', 'string'],
            ],
            BackupDestination::PROVIDER_SSH => [
                'settings.host' => ['required', 'string', 'max:255'],
                'settings.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'settings.remote_path' => ['required', 'string', 'max:2048'],
                'settings.identity_file' => ['nullable', 'string', 'max:2048'],
                'secrets.user' => [$secretsRequired ? 'required' : 'nullable', 'string'],
                'secrets.password' => $secretsRequired
                    ? ['nullable', 'required_without:secrets.private_key', 'string']
                    : ['nullable', 'string'],
                'secrets.private_key' => $secretsRequired
                    ? ['nullable', 'required_without:secrets.password', 'string']
                    : ['nullable', 'string'],
                'secrets.private_key_passphrase' => ['nullable', 'string'],
            ],
            BackupDestination::PROVIDER_AZURE_BLOB => [
                'settings.account_name' => $secretsRequired
                    ? ['nullable', 'required_without:secrets.connection_string', 'string', 'max:255']
                    : ['nullable', 'string', 'max:255'],
                'settings.container' => ['required', 'string', 'max:255'],
                'settings.endpoint' => ['nullable', 'url', 'max:2048'],
                'settings.access_tier' => ['nullable', 'string', 'max:255'],
                'secrets.account_key' => $secretsRequired
                    ? ['nullable', 'required_without:secrets.connection_string', 'string']
                    : ['nullable', 'string'],
                'secrets.connection_string' => ['nullable', 'string'],
            ],
            BackupDestination::PROVIDER_DROPBOX => [
                'settings.remote_path' => ['nullable', 'string', 'max:2048'],
                'settings.concurrency_level' => ['nullable', 'integer', 'min:1', 'max:32'],
                'secrets.app_key' => [$secretsRequired ? 'required' : 'nullable', 'string'],
                'secrets.app_secret' => [$secretsRequired ? 'required' : 'nullable', 'string'],
                'secrets.refresh_token' => [$secretsRequired ? 'required' : 'nullable', 'string'],
            ],
            BackupDestination::PROVIDER_GOOGLE_DRIVE => [
                'settings.folder_id' => ['required', 'string', 'max:255'],
                'settings.impersonate_subject' => ['nullable', 'email', 'max:255'],
                'settings.endpoint' => ['nullable', 'url', 'max:2048'],
                'settings.token_url' => ['nullable', 'url', 'max:2048'],
                'secrets.credentials_json' => [$secretsRequired ? 'required' : 'nullable', 'json'],
            ],
            BackupDestination::PROVIDER_LOCAL => [
                'settings.archive_path' => ['required', 'string', 'max:2048'],
                'settings.archive_mount_source' => ['nullable', 'string', 'max:2048'],
            ],
            default => [],
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateStorageLimits($validator);
        });
    }

    protected function validateStorageLimits(Validator $validator): void
    {
        $warning = $this->input('settings.storage_limit_warning_bytes');
        $critical = $this->input('settings.storage_limit_critical_bytes');

        if ($warning !== null && $warning !== '' && $critical !== null && $critical !== '' && (int) $warning > (int) $critical) {
            $validator->errors()->add('settings.storage_limit_critical_bytes', 'The critical storage limit must be greater than or equal to the warning storage limit.');
        }
    }
}
