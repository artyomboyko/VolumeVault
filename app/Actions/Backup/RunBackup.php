<?php

namespace App\Actions\Backup;

use App\Actions\Docker\FindContainersUsingVolume;
use App\Actions\Docker\InspectDockerVolume;
use App\Actions\Docker\RunBackupContainer;
use App\Actions\Docker\StartDockerContainers;
use App\Actions\Docker\StopDockerContainers;
use App\Models\ActivityLog;
use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\DockerVolume;
use App\Services\BackupDestinations\ListBackupObjects;
use App\Services\BackupSources\HostPathPolicy;
use App\Services\Logging\AppendRunLog;
use App\Services\Notifications\SendShoutrrrNotification;
use App\Services\Scheduling\BackupScheduleCalculator;
use RuntimeException;
use Throwable;

class RunBackup
{
    public function __construct(
        private readonly InspectDockerVolume $inspectDockerVolume,
        private readonly FindContainersUsingVolume $findContainersUsingVolume,
        private readonly StopDockerContainers $stopDockerContainers,
        private readonly StartDockerContainers $startDockerContainers,
        private readonly RunBackupContainer $runBackupContainer,
        private readonly AppendRunLog $appendRunLog,
        private readonly ListBackupObjects $listBackupObjects,
        private readonly HostPathPolicy $hostPathPolicy,
        private readonly SendShoutrrrNotification $sendShoutrrrNotification,
        private readonly BackupScheduleCalculator $scheduleCalculator,
    ) {}

    public function handle(BackupRun $run): void
    {
        $run->loadMissing('job.destination');

        $job = $run->job;
        $startedAt = now();
        $stoppedContainers = [];

        $run->forceFill([
            'status' => BackupRun::STATUS_RUNNING,
            'started_at' => $startedAt,
        ])->save();

        $job->forceFill([
            'status' => BackupJob::STATUS_RUNNING,
            'last_run_at' => $startedAt,
        ])->save();

        ActivityLog::record('backup_run_started', 'Backup run started.', $run, [
            'backup_job_id' => $job->id,
        ]);

        try {
            if (! $job->destination?->is_active) {
                throw new RuntimeException('The backup destination is inactive.');
            }

            if ($job->isDockerVolumeSource()) {
                $this->inspectDockerVolume->handle($job->volume_name);
                DockerVolume::updateOrCreate(['name' => $job->volume_name], ['exists' => true, 'last_seen_at' => now()]);
            } else {
                $this->hostPathPolicy->assertValid((string) $job->host_path);
            }

            if ($job->isDockerVolumeSource() && $job->stop_containers_before_backup) {
                $containers = $this->findContainersUsingVolume->handle($job->volume_name);
                $stoppedContainers = collect($containers)->pluck('id')->filter()->values()->all();

                if ($stoppedContainers) {
                    // Persist the IDs before stopping so a worker crash mid-run
                    // can be reconciled later (the finally block clears them).
                    $run->forceFill(['stopped_container_ids' => $stoppedContainers])->save();
                    $this->appendRunLog->handle($run, 'Stopping containers before backup: '.implode(', ', $stoppedContainers));
                    $this->stopDockerContainers->handle($stoppedContainers);
                }
            }

            $result = $this->runBackupContainer->handle($run->fresh(['job.destination']));
            $this->appendRunLog->handle($run, $result->combinedOutput());

            if (! $result->successful()) {
                throw new RuntimeException($result->combinedOutput() ?: 'Backup container failed.');
            }

            $finishedAt = now();
            $run->forceFill([
                'status' => BackupRun::STATUS_SUCCESS,
                'finished_at' => $finishedAt,
                'duration_seconds' => $startedAt->diffInSeconds($finishedAt),
            ])->save();

            $job->forceFill([
                'status' => BackupJob::STATUS_ACTIVE,
                'last_success_at' => $finishedAt,
                'last_error' => null,
                'last_error_at' => null,
                'pause_reason' => null,
                'next_run_at' => $this->scheduleCalculator->nextRunAt($job->schedule_type, $job->schedule_config ?? [], $finishedAt),
            ])->save();

            $this->recordBackupArchiveMetadata($run->fresh(['job.destination']));
            $this->sendNotifications($run->fresh(['job.destination']));
        } catch (Throwable $exception) {
            $this->markFailed($run, $exception);
        } finally {
            if ($stoppedContainers) {
                try {
                    $this->startDockerContainers->handle($stoppedContainers);
                    $run->forceFill(['stopped_container_ids' => null])->save();
                    $this->appendRunLog->handle($run->fresh(), 'Restarted containers: '.implode(', ', $stoppedContainers));
                } catch (Throwable $exception) {
                    // Leave stopped_container_ids set so reconciliation can retry.
                    $this->appendRunLog->handle($run->fresh(), 'Failed to restart containers: '.$exception->getMessage());
                }
            }
        }
    }

    /**
     * Restart application containers that a previous run stopped but never
     * restarted (worker crash between stop and restart).
     *
     * Used by the stale-run reconciliation command. Idempotent: re-running
     * `docker start` on an already-running container succeeds, and the IDs are
     * only cleared once every container is back up.
     */
    public function restartStoppedContainers(BackupRun $run): void
    {
        $containerIds = $run->stopped_container_ids ?? [];

        if (! $containerIds) {
            return;
        }

        $this->startDockerContainers->handle($containerIds);

        $run->forceFill(['stopped_container_ids' => null])->save();

        $message = 'Restarted containers left stopped after an interrupted run: '.implode(', ', $containerIds);
        $this->appendRunLog->handle($run, $message);

        ActivityLog::record('backup_run_containers_reconciled', $message, $run, [
            'backup_job_id' => $run->backup_job_id,
        ]);
    }

    /**
     * Force a run into the FAILED state and reschedule its job.
     *
     * Shared by the in-process catch block, the queue job's failed() hook
     * (worker timeout / restart) and the stale-run reconciliation command.
     * Idempotent: runs that already reached a terminal state are left untouched.
     */
    public function markFailed(BackupRun $run, Throwable $exception): void
    {
        $run->loadMissing('job.destination');

        if (in_array($run->status, [BackupRun::STATUS_SUCCESS, BackupRun::STATUS_FAILED, BackupRun::STATUS_CANCELLED], true)) {
            return;
        }

        $job = $run->job;
        $finishedAt = now();
        $startedAt = $run->started_at ?? $finishedAt;
        $message = str($exception->getMessage() ?: 'Backup failed.')->limit(1000)->toString();

        $run->forceFill([
            'status' => BackupRun::STATUS_FAILED,
            'finished_at' => $finishedAt,
            'duration_seconds' => $startedAt->diffInSeconds($finishedAt),
            'error_message' => $message,
        ])->save();

        $this->appendRunLog->handle($run, $message);

        if ($job) {
            $job->forceFill([
                'status' => BackupJob::STATUS_ERROR,
                'last_error' => $message,
                'last_error_at' => $finishedAt,
                'next_run_at' => $this->scheduleCalculator->nextRunAt($job->schedule_type, $job->schedule_config ?? [], $finishedAt),
            ])->save();
        }

        ActivityLog::record('backup_run_failed', 'Backup run failed.', $run, [
            'backup_job_id' => $job?->id,
        ]);

        $this->sendNotifications($run->fresh(['job.destination']));
    }

    private function recordBackupArchiveMetadata(BackupRun $run): void
    {
        $expectedFilename = $this->runBackupContainer->backupFilename($run);

        try {
            $object = collect($this->listBackupObjects->handle($run->job->destination))
                ->first(fn (array $object): bool => $this->matchesExpectedBackupObject($object, $expectedFilename));
        } catch (Throwable) {
            $this->appendRunLog->handle($run, 'Backup archive size could not be detected.');

            return;
        }

        if (! $object) {
            $this->appendRunLog->handle($run, 'Backup archive size could not be detected.');

            return;
        }

        $run->forceFill([
            'backup_key' => (string) ($object['key'] ?? $object['display_name'] ?? $expectedFilename),
            'backup_size_bytes' => array_key_exists('size', $object) ? (int) $object['size'] : null,
        ])->save();
    }

    private function matchesExpectedBackupObject(array $object, string $expectedFilename): bool
    {
        foreach (['key', 'display_name'] as $field) {
            $value = (string) ($object[$field] ?? '');

            if ($value === $expectedFilename || str_ends_with($value, '/'.$expectedFilename)) {
                return true;
            }
        }

        return false;
    }

    private function sendNotifications(BackupRun $run): void
    {
        try {
            $this->sendShoutrrrNotification->sendBackupRunFinished($run);
        } catch (Throwable $exception) {
            ActivityLog::record('notification_send_failed', 'Backup notification failed.', $run, [
                'error' => str($exception->getMessage())->limit(1000)->toString(),
            ]);
        }
    }
}
