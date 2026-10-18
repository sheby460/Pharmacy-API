<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_obtain_an_api_token(): void
    {
        User::factory()->inactive()->create([
            'username' => 'suspended-user',
            'password' => 'Password!123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'suspended-user',
            'password' => 'Password!123',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_active_user_can_log_in(): void
    {
        User::factory()->create([
            'username' => 'active-user',
            'password' => 'Password!123',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'active-user',
            'password' => 'Password!123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['user', 'token', 'token_type'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
