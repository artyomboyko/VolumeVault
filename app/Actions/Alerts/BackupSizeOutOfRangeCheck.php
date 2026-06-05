<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Models\BackupJob;
use App\Models\BackupRun;

class BackupSizeOutOfRangeCheck implements AlertCheckAction
{
    public function __construct(private readonly ResolveEffectiveAlertConfig $resolveEffectiveAlertConfig) {}

    public function handle(AlertRule $rule): array
    {
        $latestRuns = BackupRun::query()
            ->where('status', BackupRun::STATUS_SUCCESS)
            ->whereNotNull('backup_size_bytes')
            ->orderByDesc('finished_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('backup_job_id')
            ->keyBy('backup_job_id');

        if ($latestRuns->isEmpty()) {
            return [];
        }

        $findings = [];

        BackupJob::query()
            ->whereIn('id', $latestRuns->keys()->all())
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
                        'backup_size' => $this->formatBytes($size),
                        'min_bytes' => $minBytes,
                        'max_bytes' => $maxBytes,
                    ],
                ];
            });

        return $findings;
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

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $index), 1).' '.$units[$index];
    }
}
