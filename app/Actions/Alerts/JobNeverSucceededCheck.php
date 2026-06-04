<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;
use App\Models\BackupRun;

class JobNeverSucceededCheck implements AlertCheckAction
{
    public function __construct(private readonly ResolveEffectiveAlertConfig $resolveEffectiveAlertConfig) {}

    public function handle(AlertRule $rule): array
    {
        $findings = [];

        BackupJob::query()
            ->whereNull('last_success_at')
            ->with('alertConfigs')
            ->withCount(['runs as completed_runs_count' => function ($query): void {
                $query->whereIn('status', [BackupRun::STATUS_SUCCESS, BackupRun::STATUS_FAILED]);
            }])
            ->get()
            ->each(function (BackupJob $job) use ($rule, &$findings): void {
                $effective = $this->resolveEffectiveAlertConfig->handle($job, $rule);

                if (! $effective['enabled']) {
                    return;
                }

                $minRuns = max(1, (int) ($effective['config']['job_never_succeeded_min_runs'] ?? 3));
                $completedRuns = (int) $job->getAttribute('completed_runs_count');

                if ($completedRuns < $minRuns) {
                    return;
                }

                $findings[] = [
                    'subject' => $job,
                    'severity' => AlertSeverity::Critical,
                    'message' => 'Backup job "'.$job->name.'" has never completed successfully after '.$completedRuns.' finished runs.',
                    'context' => [
                        'min_runs' => $minRuns,
                        'completed_runs' => $completedRuns,
                    ],
                ];
            });

        return $findings;
    }
}
