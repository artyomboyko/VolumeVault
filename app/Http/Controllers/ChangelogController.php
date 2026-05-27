<?php

namespace App\Http\Controllers;

use App\Services\Changelog\Changelog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChangelogController extends Controller
{
    public function index(Request $request, Changelog $changelog): Response
    {
        return Inertia::render('Changelog/Index', [
            'changelog' => $changelog->page($request->user()),
        ]);
    }

    public function seen(Request $request, Changelog $changelog): RedirectResponse
    {
        $changelog->markSeen($request->user());

        return back();
    }
}
