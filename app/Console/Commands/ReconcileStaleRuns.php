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

class ReconcileStaleRuns extends Command
{
    protected $signature = 'volumevault:reconcile-stale-runs
        {--minutes= : Age threshold in minutes before a queued/running run is considered stale}';

    protected $description = 'Mark backup/restore runs stuck in queued/running as failed after a worker crash, timeout or restart.';

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

        $this->info("Reconciled {$backupCount} stale backup run(s) and {$restoreCount} stale restore run(s).");

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
