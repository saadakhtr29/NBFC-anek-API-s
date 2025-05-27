<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_can_organization_login()
    {
        $organization = Organization::factory()->create([
            'email' => 'org@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/organization/login', [
            'email' => 'org@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'organization' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_cannot_organization_login_with_invalid_credentials()
    {
        $organization = Organization::factory()->create([
            'email' => 'org@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/organization/login', [
            'email' => 'org@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old_password')
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/change-password', [
            'current_password' => 'old_password',
            'new_password' => 'new_password',
            'new_password_confirmation' => 'new_password'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password changed successfully'
            ]);

        // Verify new password works
        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'new_password'
        ])->assertStatus(200);
    }

    public function test_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old_password')
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/change-password', [
            'current_password' => 'wrong_password',
            'new_password' => 'new_password',
            'new_password_confirmation' => 'new_password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_validates_password_change_input()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/change-password', [
            'current_password' => 'old_password',
            'new_password' => 'short', // Too short
            'new_password_confirmation' => 'different' // Doesn't match
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_requires_authentication_for_protected_routes()
    {
        $response = $this->getJson('/api/loans');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_token_expiration()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'], now()->addSeconds(1))->plainTextToken;

        // Wait for token to expire
        sleep(2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/loans');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/refresh-token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }
} 