<?php

namespace App\Http\Controllers;

use App\Concerns\PaginateWithPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    use PaginateWithPreference;

    public function index(Request $request): Response
    {
        $perPage = $this->perPageForRequest($request);
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        $query = PersonalAccessToken::query()
            ->with('tokenable:id,name,email,role')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $tokens = $this->paginateForInertia(
            $query,
            $perPage,
            fn (PersonalAccessToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
                'user' => $token->tokenable?->only(['id', 'name', 'email', 'role']),
            ],
        );

        return Inertia::render('ApiTokens/Index', [
            'users' => $users,
            'tokens' => $tokens,
            'defaultPerPage' => $request->user()->default_per_page ?? 10,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(['read', 'write'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $expiresAt = filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null;
        $token = $user->createToken($data['name'], array_values(array_unique($data['abilities'])), $expiresAt);

        return redirect()
            ->route('api-tokens.index')
            ->with('success', 'API token created. Copy it now; it will not be shown again.')
            ->with('api_token', $token->plainTextToken);
    }

    public function destroy(PersonalAccessToken $apiToken)
    {
        $apiToken->delete();

        return redirect()->route('api-tokens.index')->with('success', 'API token revoked.');
    }
}
