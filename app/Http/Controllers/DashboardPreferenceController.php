<?php

namespace App\Http\Controllers;

use App\Support\DashboardWidgets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stats' => ['present', 'array'],
            'stats.*.key' => ['required', 'string'],
            'stats.*.visible' => ['required', 'boolean'],
            'sections' => ['present', 'array'],
            'sections.*.key' => ['required', 'string'],
            'sections.*.visible' => ['required', 'boolean'],
        ]);

        // Re-normalize before saving: drops unknown keys, restores any missing
        // canonical widget, and guarantees a consistent stored shape.
        $preferences = DashboardWidgets::normalize($data);

        $request->user()->forceFill([
            'dashboard_preferences' => $preferences,
        ])->save();

        return back();
    }
}
