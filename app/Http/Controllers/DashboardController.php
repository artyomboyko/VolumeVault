<?php

namespace App\Http\Controllers;

use App\Models\BackupJob;
use App\Models\BackupRun;
use App\Models\DockerVolume;
use App\Models\RestoreRun;
use App\Services\Volumes\VolumeBackupSummaries;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(VolumeBackupSummaries $volumeBackupSummaries): Response
    {
        $volumeSummaries = $volumeBackupSummaries->forVolumes(DockerVolume::query()->get());
        $coverageStats = $volumeBackupSummaries->coverageStats($volumeSummaries);
        $lastBackupRun = BackupRun::with('job')->latest()->first();
        $lastSuccessfulBackupRun = BackupRun::query()
            ->where('status', BackupRun::STATUS_SUCCESS)
            ->orderByDesc('finished_at')
            ->orderByDesc('created_at')
            ->first();
        $nextJob = BackupJob::with('destination')
            ->where('status', BackupJob::STATUS_ACTIVE)
            ->whereNotNull('next_run_at')
            ->orderBy('next_run_at')
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_volumes' => DockerVolume::count(),
                'existing_volumes' => DockerVolume::where('exists', true)->count(),
                'missing_volumes' => DockerVolume::where('exists', false)->count(),
                'backed_up_volumes' => $coverageStats['backed_up_volumes'],
                'configured_volumes' => $coverageStats['configured_volumes'],
                'unprotected_volumes' => $coverageStats['unprotected_volumes'],
                'total_jobs' => BackupJob::count(),
                'active_jobs' => BackupJob::where('status', BackupJob::STATUS_ACTIVE)->count(),
                'paused_jobs' => BackupJob::where('status', BackupJob::STATUS_PAUSED)->count(),
                'error_jobs' => BackupJob::where('status', BackupJob::STATUS_ERROR)->count(),
                'last_backup_run_status' => $lastBackupRun?->status,
                'last_successful_backup_size' => $lastSuccessfulBackupRun?->backup_size_bytes,
                'next_scheduled_backup' => $nextJob?->next_run_at,
            ],
            'recentBackupRuns' => BackupRun::with('job')->latest()->limit(8)->get(),
            'recentRestoreRuns' => RestoreRun::with('job')->latest()->limit(8)->get(),
            'jobsWithErrors' => BackupJob::with('destination')
                ->where('status', BackupJob::STATUS_ERROR)
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
