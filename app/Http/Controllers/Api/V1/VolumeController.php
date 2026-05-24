<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Docker\SyncDockerVolumes;
use App\Http\Controllers\Controller;
use App\Models\DockerVolume;
use App\Services\Volumes\VolumeBackupSummaries;
use Illuminate\Http\JsonResponse;
use Throwable;

class VolumeController extends Controller
{
    public function index(VolumeBackupSummaries $volumeBackupSummaries): JsonResponse
    {
        $volumes = DockerVolume::query()
            ->orderByDesc('exists')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $volumeBackupSummaries->forVolumes($volumes),
        ]);
    }

    public function sync(SyncDockerVolumes $syncDockerVolumes): JsonResponse
    {
        try {
            return response()->json(['data' => $syncDockerVolumes->handle()]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to sync Docker volumes.',
                'error' => str($exception->getMessage())->limit(500)->toString(),
            ], 422);
        }
    }
}
