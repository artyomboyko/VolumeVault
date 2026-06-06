<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_returns_paginated_inertia_shape(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(12)->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 10)
                ->where('users.meta.per_page', 10)
                ->where('users.meta.total', 13));
    }

    public function test_users_index_all_per_page_keeps_paginated_inertia_shape(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(12)->create();

        $this->actingAs($admin)
            ->get(route('users.index', ['per_page' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 13)
                ->where('users.meta.per_page', 0)
                ->where('users.meta.last_page', 1)
                ->where('users.meta.total', 13));
    }

    public function test_api_tokens_index_returns_paginated_inertia_shape(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 1; $i <= 12; $i++) {
            $admin->createToken('token-'.$i, ['read']);
        }

        $this->actingAs($admin)
            ->get(route('api-tokens.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ApiTokens/Index')
                ->has('tokens.data', 10)
                ->where('tokens.meta.per_page', 10)
                ->where('tokens.meta.total', 12));
    }
}
