<?php

namespace App\Http\Requests;

use App\Models\BackupJob;
use App\Models\RestoreRun;
use App\Services\BackupDestinations\ListBackupObjects;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class StoreRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selected_backup_key' => ['required', 'string', 'max:2048'],
            'mode' => ['required', 'string', Rule::in([
                RestoreRun::MODE_NEW_VOLUME,
                RestoreRun::MODE_INPLACE,
                RestoreRun::MODE_SAFE_INPLACE,
            ])],
            'target_volume_name' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'confirmation_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('selected_backup_key')) {
                return;
            }

            $this->validateSelectedBackupKey($validator);
        });
    }

    /**
     * Confirm the requested backup key is one the destination actually exposes.
     *
     * The key is the *source* of a host-side file copy on local destinations, so
     * it must be validated as an allow-list against the real listing (fail-closed)
     * rather than trusted as a free-form string. `..` is rejected up front as
     * defense in depth against path traversal.
     */
    private function validateSelectedBackupKey(Validator $validator): void
    {
        $key = (string) $this->input('selected_backup_key', '');

        if ($key === '') {
            return;
        }

        if (str_contains($key, '..') || str_starts_with($key, '/')) {
            $validator->errors()->add('selected_backup_key', 'The selected backup key is invalid.');

            return;
        }

        $backupJob = $this->route('backupJob');

        if (! $backupJob instanceof BackupJob) {
            $validator->errors()->add('selected_backup_key', 'Unable to resolve the backup job for this restore.');

            return;
        }

        $backupJob->loadMissing('destination');

        try {
            $objects = app(ListBackupObjects::class)->handle($backupJob->destination);
        } catch (Throwable) {
            $validator->errors()->add('selected_backup_key', 'Unable to verify the selected backup against the destination listing.');

            return;
        }

        $allowedKeys = collect($objects)->pluck('key')->filter()->all();

        if (! in_array($key, $allowedKeys, true)) {
            $validator->errors()->add('selected_backup_key', 'The selected backup is not available on the destination.');
        }
    }
}
