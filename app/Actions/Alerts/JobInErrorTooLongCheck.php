<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;

class JobInErrorTooLongCheck implements AlertCheckAction
{
    public function __construct(private readonly ResolveEffectiveAlertConfig $resolveEffectiveAlertConfig) {}

    public function handle(AlertRule $rule): array
    {
        $findings = [];

        BackupJob::query()
            ->where('status', BackupJob::STATUS_ERROR)
            ->whereNotNull('last_error_at')
            ->with('alertConfigs')
            ->cursor()
            ->each(function (BackupJob $job) use ($rule, &$findings): void {
                $effective = $this->resolveEffectiveAlertConfig->handle($job, $rule);

                if (! $effective['enabled']) {
                    return;
                }

                $days = max(1, (int) ($effective['config']['job_in_error_days'] ?? 3));

                if ($job->last_error_at->greaterThanOrEqualTo(now()->subDays($days))) {
                    return;
                }

                $findings[] = [
                    'subject' => $job,
                    'severity' => AlertSeverity::Critical,
                    'message' => 'Backup job "'.$job->name.'" has been in error for more than '.$days.' days.',
                    'context' => [
                        'threshold_days' => $days,
                        'last_error_at' => $job->last_error_at?->toISOString(),
                        'last_error' => $job->last_error,
                    ],
                ];
            });

        return $findings;
    }
}
