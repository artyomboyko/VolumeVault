<?php

namespace App\Concerns;

use Illuminate\Http\Request;

trait PaginateWithPreference
{
    private const VALID_PER_PAGE = [10, 20, 50, 100];

    private function perPageForRequest(Request $request): int
    {
        $userPerPage = $request->user()?->default_per_page ?? 10;
        $requested = $request->input('per_page');

        if ($requested === 'all' || $requested === '0' || $requested === 0) {
            return 0;
        }

        return in_array((int) $requested, self::VALID_PER_PAGE)
            ? (int) $requested
            : $userPerPage;
    }
}
