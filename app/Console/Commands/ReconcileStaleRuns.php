<?php

namespace App\Console\Commands;

use App\Actions\Backup\RunBackup;
use App\Actions\Restore\RunRestore;
use App\Models\BackupRun;
use App\Models\RestoreRun;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Throwable;

class ReconcileStaleRuns extends Command
{
    protected $signature = 'volumevault:reconcile-stale-runs
        {--minutes= : Age threshold in minutes before a queued/running run is considered stale}';

    protected $description = 'Mark backup/restore runs stuck in queued/running as failed after a worker crash, timeout or restart, and restart application containers left stopped by an interrupted backup.';

    /**
     * Default staleness threshold. Kept in step with the queue jobs'
     * WithoutOverlapping->expireAfter(86400) so a legitimate long run is
     * never reconciled before its overlap lock would have expired.
     */
    public const DEFAULT_THRESHOLD_MINUTES = 1440;

    public function handle(RunBackup $runBackup, RunRestore $runRestore): int
    {
        $minutes = (int) ($this->option('minutes') ?: self::DEFAULT_THRESHOLD_MINUTES);

        if ($minutes < 1) {
            $minutes = self::DEFAULT_THRESHOLD_MINUTES;
        }

        $cutoff = now()->subMinutes($minutes);
        $reason = "Run reconciled as failed: stuck in queued/running for more than {$minutes} minute(s) (possible worker crash, timeout or restart).";

        $backupCount = 0;
        $this->staleBackupRuns($cutoff)->each(function (BackupRun $run) use ($runBackup, $reason, &$backupCount): void {
            $runBackup->markFailed($run, new RuntimeException($reason));
            $backupCount++;
        });

        $restoreCount = 0;
        $this->staleRestoreRuns($cutoff)->each(function (RestoreRun $run) use ($runRestore, $reason, &$restoreCount): void {
            $runRestore->markFailed($run, new RuntimeException($reason));
            $restoreCount++;
        });

        // Runs the sweep just failed (or runs whose worker died during restart)
        // may still have application containers stopped. Restart them now.
        $restartedCount = 0;
        $this->runsWithStoppedContainers()->each(function (BackupRun $run) use ($runBackup, &$restartedCount): void {
            try {
                $runBackup->restartStoppedContainers($run);
                $restartedCount++;
            } catch (Throwable $exception) {
                $this->warn("Failed to restart containers for backup run {$run->id}: {$exception->getMessage()}");
            }
        });

        $this->info("Reconciled {$backupCount} stale backup run(s) and {$restoreCount} stale restore run(s); restarted containers for {$restartedCount} interrupted run(s).");

        return self::SUCCESS;
    }

    /** @return Collection<int, BackupRun> */
    private function staleBackupRuns(CarbonInterface $cutoff): Collection
    {
        return BackupRun::query()
            ->whereIn('status', [BackupRun::STATUS_QUEUED, BackupRun::STATUS_RUNNING])
            ->where(fn ($query) => $this->staleConstraint($query, $cutoff, BackupRun::STATUS_RUNNING))
            ->get();
    }

    /** @return Collection<int, RestoreRun> */
    private function staleRestoreRuns(CarbonInterface $cutoff): Collection
    {
        return RestoreRun::query()
            ->whereIn('status', [RestoreRun::STATUS_QUEUED, RestoreRun::STATUS_RUNNING])
            ->where(fn ($query) => $this->staleConstraint($query, $cutoff, RestoreRun::STATUS_RUNNING))
            ->get();
    }

    /**
     * Terminal runs whose application containers were stopped for the backup
     * but never restarted (worker crash between stop and restart).
     *
     * @return Collection<int, BackupRun>
     */
    private function runsWithStoppedContainers(): Collection
    {
        return BackupRun::query()
            ->whereIn('status', [BackupRun::STATUS_SUCCESS, BackupRun::STATUS_FAILED, BackupRun::STATUS_CANCELLED])
            ->whereNotNull('stopped_container_ids')
            ->where('stopped_container_ids', '!=', '[]')
            ->get();
    }

    /**
     * Running runs are aged from started_at; queued runs that a worker never
     * picked up are aged from created_at.
     */
    private function staleConstraint($query, CarbonInterface $cutoff, string $runningStatus): void
    {
        $query
            ->where(fn ($q) => $q->where('status', $runningStatus)->where('started_at', '<', $cutoff))
            ->orWhere(fn ($q) => $q->where('status', '!=', $runningStatus)->where('created_at', '<', $cutoff));
    }
}
