<?php

namespace App\Http\Controllers;

use App\Services\Changelog\AvailableUpdateChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvailableUpdateController extends Controller
{
    public function dismiss(Request $request, AvailableUpdateChecker $availableUpdateChecker): RedirectResponse
    {
        $availableUpdateChecker->dismissForUser($request->user());

        return back();
    }
}
