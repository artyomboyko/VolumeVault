<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumTokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_default_token_expiration_is_configured(): void
    {
        // 60 days, in minutes. A non-null default caps the blast radius of a
        // leaked token (#8). If this is ever set back to null, the guard stops
        // expiring tokens that have no explicit expires_at.
        $this->assertSame(60 * 24 * 60, config('sanctum.expiration'));
    }

    public function test_a_token_without_explicit_expiry_still_works_inside_the_default_window(): void
    {
        $window = (int) config('sanctum.expiration');
        $token = User::factory()->user()->create()->createToken('cli', ['read'])->plainTextToken;

        $this->travel($window - 60)->minutes();

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    }

    public function test_a_token_is_rejected_once_the_default_expiration_has_passed(): void
    {
        $window = (int) config('sanctum.expiration');
        $token = User::factory()->user()->create()->createToken('cli', ['read'])->plainTextToken;

        // Past the default cap, even though the token has no explicit expires_at.
        $this->travel($window + 60)->minutes();

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
