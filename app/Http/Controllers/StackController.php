<?php

namespace App\Http\Controllers;

use App\Models\DockerVolume;
use App\Services\Volumes\VolumeBackupSummaries;
use Inertia\Inertia;
use Inertia\Response;

class StackController extends Controller
{
    public function index(VolumeBackupSummaries $volumeBackupSummaries): Response
    {
        $volumes = DockerVolume::query()
            ->orderByDesc('exists')
            ->orderBy('name')
            ->get();

        return Inertia::render('Stacks/Index', [
            'stacks' => $volumeBackupSummaries->forStacks($volumes),
        ]);
    }
}
