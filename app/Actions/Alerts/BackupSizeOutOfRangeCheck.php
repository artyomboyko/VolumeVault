<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Support\FormatBytes;
use Illuminate\Database\Eloquent\Collection;

class BackupSizeOutOfRangeCheck implements AlertCheckAction
{
    public function __construct(private readonly ResolveEffectiveAlertConfig $resolveEffectiveAlertConfig) {}

    public function handle(AlertRule $rule): array
    {
        $latestRuns = $this->latestSuccessfulRuns();

        if ($latestRuns->isEmpty()) {
            return [];
        }

        $findings = [];

        BackupJob::query()
            ->whereIn('id', $latestRuns->keys()->all())
            ->where('status', '!=', BackupJob::STATUS_PAUSED)
            ->with('alertConfigs')
            ->get()
            ->each(function (BackupJob $job) use ($rule, $latestRuns, &$findings): void {
                $effective = $this->resolveEffectiveAlertConfig->handle($job, $rule);

                if (! $effective['enabled']) {
                    return;
                }

                $run = $latestRuns->get($job->id);

                if (! $run || $run->backup_size_bytes === null) {
                    return;
                }

                $minBytes = $this->threshold($effective['config'], 'backup_size_out_of_range_min_bytes', 1024);
                $maxBytes = $this->threshold($effective['config'], 'backup_size_out_of_range_max_bytes', 10737418240);

                if ($minBytes !== null && $maxBytes !== null && $maxBytes < $minBytes) {
                    return;
                }

                $size = (int) $run->backup_size_bytes;

                if (($minBytes === null || $size >= $minBytes) && ($maxBytes === null || $size <= $maxBytes)) {
                    return;
                }

                $critical = ($minBytes !== null && $minBytes > 0 && $size < ($minBytes / 2)) || ($maxBytes !== null && $maxBytes > 0 && $size > ($maxBytes * 2));

                $findings[] = [
                    'subject' => $job,
                    'severity' => $critical ? AlertSeverity::Critical : AlertSeverity::Warning,
                    'message' => 'Latest backup size for job "'.$job->name.'" is outside the configured range.',
                    'context' => [
                        'backup_run_id' => $run->id,
                        'backup_size_bytes' => $size,
                        'backup_size' => FormatBytes::format($size),
                        'min_bytes' => $minBytes,
                        'max_bytes' => $maxBytes,
                    ],
                ];
            });

        return $findings;
    }

    /** @return Collection<int, BackupRun> */
    private function latestSuccessfulRuns(): Collection
    {
        $subquery = BackupRun::query()
            ->select('backup_job_id', \DB::raw('MAX(finished_at) as max_finished_at'))
            ->where('status', BackupRun::STATUS_SUCCESS)
            ->whereNotNull('backup_size_bytes')
            ->groupBy('backup_job_id');

        return BackupRun::query()
            ->joinSub($subquery, 'latest', function ($join): void {
                $join->on('backup_runs.backup_job_id', '=', 'latest.backup_job_id')
                    ->on('backup_runs.finished_at', '=', 'latest.max_finished_at');
            })
            ->where('backup_runs.status', BackupRun::STATUS_SUCCESS)
            ->whereNotNull('backup_runs.backup_size_bytes')
            ->get()
            ->keyBy('backup_job_id');
    }

    /** @param array<string, mixed> $config */
    private function threshold(array $config, string $key, int $default): ?int
    {
        if (! array_key_exists($key, $config)) {
            return $default;
        }

        if ($config[$key] === null) {
            return null;
        }

        return max(0, (int) $config[$key]);
    }

}
