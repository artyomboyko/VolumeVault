<?php

namespace App\Http\Controllers;

use App\Actions\Docker\SyncDockerVolumes;
use App\Models\DockerVolume;
use App\Services\Volumes\VolumeBackupSummaries;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VolumeController extends Controller
{
    public function index(VolumeBackupSummaries $volumeBackupSummaries): Response
    {
        $volumes = DockerVolume::query()
            ->orderByDesc('exists')
            ->orderBy('name')
            ->get();

        return Inertia::render('Volumes/Index', [
            'volumes' => $volumeBackupSummaries->forVolumes($volumes),
        ]);
    }

    public function sync(SyncDockerVolumes $syncDockerVolumes)
    {
        try {
            $result = $syncDockerVolumes->handle();

            return back()->with('success', "Synced {$result['found']} Docker volumes. {$result['marked_missing']} marked missing. {$result['removed']} removed.");
        } catch (Throwable $exception) {
            return back()->with('error', 'Unable to sync Docker volumes: '.str($exception->getMessage())->limit(500)->toString());
        }
    }
}
