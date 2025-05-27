<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_system_settings()
    {
        SystemSetting::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/system-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'description',
                        'type',
                        'is_public',
                        'updated_by',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_can_create_system_setting()
    {
        $data = [
            'key' => 'test_setting',
            'value' => 'test_value',
            'description' => 'Test setting description',
            'type' => 'string',
            'is_public' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/system-settings', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'key',
                    'value',
                    'description',
                    'type',
                    'is_public',
                    'updated_by',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'test_setting',
            'value' => json_encode('test_value'),
            'type' => 'string',
            'is_public' => true
        ]);
    }

    public function test_cannot_create_duplicate_setting()
    {
        SystemSetting::factory()->create([
            'key' => 'test_setting'
        ]);

        $data = [
            'key' => 'test_setting',
            'value' => 'test_value',
            'description' => 'Test setting description',
            'type' => 'string',
            'is_public' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/system-settings', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_can_update_system_setting()
    {
        $setting = SystemSetting::factory()->create();

        $data = [
            'value' => 'updated_value',
            'description' => 'Updated description',
            'is_public' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/system-settings/{$setting->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'value' => json_encode('updated_value'),
                    'description' => 'Updated description',
                    'is_public' => false
                ]
            ]);
    }

    public function test_can_delete_system_setting()
    {
        $setting = SystemSetting::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/system-settings/{$setting->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('system_settings', [
            'id' => $setting->id
        ]);
    }

    public function test_can_get_setting_by_key()
    {
        $setting = SystemSetting::factory()->create([
            'key' => 'test_key'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/system-settings/key/test_key");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $setting->id,
                    'key' => 'test_key'
                ]
            ]);
    }

    public function test_validates_setting_type()
    {
        $data = [
            'key' => 'test_setting',
            'value' => 'test_value',
            'description' => 'Test setting description',
            'type' => 'invalid_type',
            'is_public' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/system-settings', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_handles_different_value_types()
    {
        $types = [
            'string' => 'test_value',
            'integer' => 123,
            'float' => 123.45,
            'boolean' => true,
            'array' => ['item1', 'item2'],
            'object' => ['key' => 'value']
        ];

        foreach ($types as $type => $value) {
            $data = [
                'key' => "test_setting_{$type}",
                'value' => $value,
                'description' => "Test {$type} setting",
                'type' => $type,
                'is_public' => true
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/system-settings', $data);

            $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'type' => $type,
                        'value' => json_encode($value)
                    ]
                ]);
        }
    }
} 