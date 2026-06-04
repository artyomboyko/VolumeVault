<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;

class BackupTooOldCheck implements AlertCheckAction
{
    public function __construct(private readonly ResolveEffectiveAlertConfig $resolveEffectiveAlertConfig) {}

    public function handle(AlertRule $rule): array
    {
        $findings = [];

        BackupJob::query()
            ->whereNotNull('last_success_at')
            ->with('alertConfigs')
            ->get()
            ->each(function (BackupJob $job) use ($rule, &$findings): void {
                $effective = $this->resolveEffectiveAlertConfig->handle($job, $rule);

                if (! $effective['enabled']) {
                    return;
                }

                $days = max(1, (int) ($effective['config']['backup_too_old_days'] ?? 7));
                $warningThreshold = now()->subDays($days);

                if ($job->last_success_at->greaterThanOrEqualTo($warningThreshold)) {
                    return;
                }

                $criticalThreshold = now()->subDays($days * 2);
                $severity = $job->last_success_at->lessThan($criticalThreshold)
                    ? AlertSeverity::Critical
                    : AlertSeverity::Warning;

                $findings[] = [
                    'subject' => $job,
                    'severity' => $severity,
                    'message' => 'Backup job "'.$job->name.'" has not succeeded within '.$days.' days.',
                    'context' => [
                        'threshold_days' => $days,
                        'critical_after_days' => $days * 2,
                        'last_success_at' => $job->last_success_at?->toISOString(),
                    ],
                ];
            });

        return $findings;
    }
}
