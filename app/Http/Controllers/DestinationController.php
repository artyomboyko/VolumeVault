<?php

namespace App\Http\Controllers;

use App\Actions\Destinations\NormalizeDestinationData;
use App\Concerns\PaginateWithPreference;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\ActivityLog;
use App\Models\BackupDestination;
use App\Services\BackupDestinations\TestBackupDestination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    use PaginateWithPreference;

    public function index(Request $request): Response
    {
        $perPage = $this->perPageForRequest($request);
        $query = BackupDestination::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->latest();

        return Inertia::render('Destinations/Index', [
            'destinations' => $this->paginateForInertia($query, $perPage, fn (BackupDestination $d): array => $d->safeForFrontend()),
            'defaultPerPage' => $request->user()->default_per_page ?? 10,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Destinations/Form', [
            'destination' => null,
            'providers' => BackupDestination::providerOptions(),
        ]);
    }

    public function store(StoreDestinationRequest $request, NormalizeDestinationData $normalizeDestinationData)
    {
        $data = $normalizeDestinationData->handle([
            ...$request->validated(),
            'use_path_style_endpoint' => $request->boolean('use_path_style_endpoint'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $destination = BackupDestination::create($data);

        ActivityLog::record('backup_destination_created', 'Backup destination created.', $destination);

        return redirect()->route('destinations.index')->with('success', 'Destination created.');
    }

    public function edit(BackupDestination $destination): Response
    {
        return Inertia::render('Destinations/Form', [
            'destination' => $destination->safeForFrontend(),
            'providers' => BackupDestination::providerOptions(),
        ]);
    }

    public function update(UpdateDestinationRequest $request, BackupDestination $destination, NormalizeDestinationData $normalizeDestinationData)
    {
        $data = $normalizeDestinationData->handle([
            ...$request->validated(),
            'use_path_style_endpoint' => $request->boolean('use_path_style_endpoint'),
            'is_active' => $request->boolean('is_active'),
        ], $destination);

        $destination->update($data);

        return redirect()->route('destinations.index')->with('success', 'Destination updated.');
    }

    public function destroy(BackupDestination $destination)
    {
        $destination->delete();

        return redirect()->route('destinations.index')->with('success', 'Destination deleted.');
    }

    public function updateActive(Request $request, BackupDestination $destination)
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $destination->forceFill([
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('success', $destination->is_active ? 'Destination enabled.' : 'Destination disabled.');
    }

    public function test(BackupDestination $destination, TestBackupDestination $testBackupDestination)
    {
        $result = $testBackupDestination->handle($destination);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
