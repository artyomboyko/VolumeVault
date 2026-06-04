<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAlertRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.id' => ['required', 'integer', 'distinct', 'exists:alert_rules,id'],
            'rules.*.enabled' => ['required', 'boolean'],
            'rules.*.config' => ['required', 'array'],
            'rules.*.config.check_interval_minutes' => ['required', 'integer', 'min:1'],
            'rules.*.config.cooldown_minutes' => ['required', 'integer', 'min:0'],
            'rules.*.config.reminder_enabled' => ['required', 'boolean'],
            'rules.*.config.backup_too_old_days' => ['nullable', 'integer', 'min:1'],
            'rules.*.config.job_never_succeeded_min_runs' => ['nullable', 'integer', 'min:1'],
            'rules.*.config.job_in_error_days' => ['nullable', 'integer', 'min:1'],
            'rules.*.config.backup_size_out_of_range_min_bytes' => ['nullable', 'integer', 'min:0'],
            'rules.*.config.backup_size_out_of_range_max_bytes' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('rules', []) as $index => $rule) {
                $config = $rule['config'] ?? [];

                if (! array_key_exists('backup_size_out_of_range_min_bytes', $config) || ! array_key_exists('backup_size_out_of_range_max_bytes', $config)) {
                    continue;
                }

                if ((int) $config['backup_size_out_of_range_min_bytes'] > (int) $config['backup_size_out_of_range_max_bytes']) {
                    $validator->errors()->add('rules.'.$index.'.config.backup_size_out_of_range_max_bytes', 'The maximum backup size must be greater than or equal to the minimum backup size.');
                }
            }
        });
    }

    /** @return array<int, array{id: int, enabled: bool, config: array<string, bool|int>}> */
    public function alertRules(): array
    {
        return collect($this->validated('rules'))
            ->map(fn (array $rule): array => [
                'id' => (int) $rule['id'],
                'enabled' => (bool) $rule['enabled'],
                'config' => $this->normalizeConfig($rule['config'] ?? []),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, bool|int> */
    private function normalizeConfig(array $config): array
    {
        return collect([
            'check_interval_minutes',
            'cooldown_minutes',
            'reminder_enabled',
            'backup_too_old_days',
            'job_never_succeeded_min_runs',
            'job_in_error_days',
            'backup_size_out_of_range_min_bytes',
            'backup_size_out_of_range_max_bytes',
        ])->filter(fn (string $key): bool => array_key_exists($key, $config) && $config[$key] !== null)
            ->mapWithKeys(fn (string $key): array => [$key => $key === 'reminder_enabled' ? (bool) $config[$key] : (int) $config[$key]])
            ->all();
    }
}
