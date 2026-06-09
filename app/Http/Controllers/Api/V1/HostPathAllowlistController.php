<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BackupSources\HostPathPolicy;
use Illuminate\Http\JsonResponse;

class HostPathAllowlistController extends Controller
{
    public function __invoke(HostPathPolicy $policy): JsonResponse
    {
        $prefixes = $policy->allowedPrefixes();

        return response()->json([
            'data' => [
                // Fail-closed: with no prefix configured, host-path backup
                // sources and local destinations are refused entirely.
                'configured' => $prefixes !== [],
                'prefixes' => $prefixes,
            ],
        ]);
    }
}
