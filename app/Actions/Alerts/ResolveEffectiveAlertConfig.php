<?php

namespace App\Actions\Alerts;

use App\Models\AlertRule;
use App\Models\BackupJob;
use App\Models\JobAlertConfig;

class ResolveEffectiveAlertConfig
{
    public function __construct(private readonly EnsureAlertRules $ensureAlertRules) {}

    /** @return array{enabled: bool, config: array<string, mixed>} */
    public function handle(BackupJob $job, AlertRule $rule): array
    {
        $config = array_replace($this->ensureAlertRules->defaultConfig($rule->type), $rule->config ?? []);
        $enabled = $rule->enabled;

        if (! $job->use_custom_alert_settings) {
            return ['enabled' => $enabled, 'config' => $config];
        }

        $jobConfig = $this->jobConfig($job, $rule);

        if ($jobConfig?->enabled !== null) {
            $enabled = $jobConfig->enabled;
        }

        if ($jobConfig?->config) {
            $config = array_replace($config, $jobConfig->config);
        }

        return ['enabled' => $enabled, 'config' => $config];
    }

    private function jobConfig(BackupJob $job, AlertRule $rule): ?JobAlertConfig
    {
        if ($job->relationLoaded('alertConfigs')) {
            return $job->alertConfigs->firstWhere('alert_rule_id', $rule->id);
        }

        return $job->alertConfigs()->where('alert_rule_id', $rule->id)->first();
    }
}
