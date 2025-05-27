<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrganizationSettingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $organization;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and organization
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();

        // Get authentication token
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->token = $response->json('token');
    }

    public function test_can_list_organization_settings()
    {
        // Create some settings
        OrganizationSetting::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/organization-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'organization_id',
                        'key',
                        'value',
                        'description',
                        'is_public',
                        'updated_by',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_create_organization_setting()
    {
        $settingData = [
            'organization_id' => $this->organization->id,
            'key' => 'test_setting',
            'value' => 'test_value',
            'description' => 'Test setting description',
            'is_public' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/organization-settings', $settingData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'organization_id',
                    'key',
                    'value',
                    'description',
                    'is_public',
                    'updated_by',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('organization_settings', [
            'key' => 'test_setting',
            'value' => 'test_value',
        ]);
    }

    public function test_cannot_create_duplicate_setting()
    {
        // Create an existing setting
        OrganizationSetting::factory()->create([
            'organization_id' => $this->organization->id,
            'key' => 'test_setting',
        ]);

        $settingData = [
            'organization_id' => $this->organization->id,
            'key' => 'test_setting',
            'value' => 'test_value',
            'description' => 'Test setting description',
            'is_public' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/organization-settings', $settingData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_can_update_organization_setting()
    {
        $setting = OrganizationSetting::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $updateData = [
            'value' => 'updated_value',
            'description' => 'Updated description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/organization-settings/{$setting->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'value' => 'updated_value',
                    'description' => 'Updated description',
                ],
            ]);
    }

    public function test_can_delete_organization_setting()
    {
        $setting = OrganizationSetting::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/organization-settings/{$setting->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('organization_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_get_setting_by_key()
    {
        $setting = OrganizationSetting::factory()->create([
            'organization_id' => $this->organization->id,
            'key' => 'test_key',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organization-settings/key/test_key");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'key' => 'test_key',
                    'value' => $setting->value,
                ],
            ]);
    }
} 