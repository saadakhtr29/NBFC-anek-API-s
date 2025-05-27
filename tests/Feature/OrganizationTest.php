<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    }

    /** @test */
    public function it_can_list_organizations()
    {
        // Create some test organizations
        Organization::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/organizations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'type',
                        'registration_number',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(4, 'data'); // Including the one created in setUp
    }

    /** @test */
    public function it_can_filter_organizations_by_status()
    {
        Organization::factory()->active()->count(2)->create();
        Organization::factory()->inactive()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/organizations?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // Including the one created in setUp
    }

    /** @test */
    public function it_can_filter_organizations_by_type()
    {
        Organization::factory()->count(2)->create(['type' => 'Private Limited']);
        Organization::factory()->count(1)->create(['type' => 'Public Limited']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/organizations?type=Private Limited');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // Including the one created in setUp
    }

    /** @test */
    public function it_can_search_organizations()
    {
        $searchTerm = 'Test Company';
        Organization::factory()->create(['name' => $searchTerm]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/organizations?search={$searchTerm}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_create_an_organization()
    {
        Storage::fake('public');

        $data = [
            'name' => 'Test Organization',
            'code' => 'TEST001',
            'type' => 'Private Limited',
            'registration_number' => 'REG123456',
            'tax_number' => 'TAX789012',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'postal_code' => '12345',
            'phone' => '1234567890',
            'email' => 'test@organization.com',
            'website' => 'https://test.com',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
            'description' => 'Test description',
            'status' => 'active',
            'founding_date' => '2020-01-01',
            'industry' => 'Technology',
            'size' => 'medium',
            'annual_revenue' => 1000000.00,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'settings' => [
                'theme' => 'light',
                'language' => 'en'
            ],
            'remarks' => 'Test remarks'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/organizations', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'type',
                    'registration_number',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => $data['name'],
            'code' => $data['code'],
            'registration_number' => $data['registration_number']
        ]);

        Storage::disk('public')->assertExists('organizations/logos/' . $data['logo']->hashName());
    }

    /** @test */
    public function it_can_get_organization_details()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/organizations/{$this->organization->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'type',
                    'registration_number',
                    'status',
                    'created_at',
                    'updated_at',
                    'statistics' => [
                        'active_employees',
                        'active_loans',
                        'total_loan_amount',
                        'remaining_loan_amount',
                        'documents'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_update_organization_details()
    {
        Storage::fake('public');

        $data = [
            'name' => 'Updated Organization',
            'code' => 'UPD001',
            'type' => 'Public Limited',
            'registration_number' => 'REG654321',
            'tax_number' => 'TAX210987',
            'address' => '456 Update Street',
            'city' => 'Update City',
            'state' => 'Update State',
            'country' => 'Update Country',
            'postal_code' => '54321',
            'phone' => '0987654321',
            'email' => 'update@organization.com',
            'website' => 'https://update.com',
            'logo' => UploadedFile::fake()->image('updated_logo.jpg'),
            'description' => 'Updated description',
            'status' => 'active',
            'founding_date' => '2021-01-01',
            'industry' => 'Finance',
            'size' => 'large',
            'annual_revenue' => 2000000.00,
            'currency' => 'EUR',
            'timezone' => 'Europe/London',
            'settings' => [
                'theme' => 'dark',
                'language' => 'fr'
            ],
            'remarks' => 'Updated remarks'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/organizations/{$this->organization->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'type',
                    'registration_number',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'name' => $data['name'],
            'code' => $data['code'],
            'registration_number' => $data['registration_number']
        ]);

        Storage::disk('public')->assertExists('organizations/logos/' . $data['logo']->hashName());
    }

    /** @test */
    public function it_can_delete_an_organization()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/organizations/{$this->organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Organization deleted successfully'
            ]);

        $this->assertSoftDeleted('organizations', [
            'id' => $this->organization->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_organization_with_active_employees()
    {
        // Create an active employee for the organization
        $this->organization->employees()->create([
            'user_id' => $this->user->id,
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'date_of_joining' => now(),
            'designation' => 'Manager',
            'department' => 'IT',
            'salary' => 5000.00,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/organizations/{$this->organization->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete organization with active employees'
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id
        ]);
    }

    /** @test */
    public function it_can_get_organization_statistics()
    {
        // Create organizations with different statuses and sizes
        Organization::factory()->active()->count(2)->create();
        Organization::factory()->inactive()->count(1)->create();
        Organization::factory()->suspended()->count(1)->create();

        Organization::factory()->small()->count(2)->create();
        Organization::factory()->medium()->count(1)->create();
        Organization::factory()->large()->count(1)->create();
        Organization::factory()->enterprise()->count(1)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/organizations/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_organizations',
                'active_organizations',
                'total_employees',
                'active_employees',
                'total_loans',
                'active_loans',
                'total_loan_amount',
                'remaining_loan_amount',
                'organizations_by_status' => [
                    'active',
                    'inactive',
                    'suspended'
                ],
                'organizations_by_size' => [
                    'small',
                    'medium',
                    'large',
                    'enterprise'
                ],
                'organizations_by_industry'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_organization()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/organizations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'code',
                'type',
                'registration_number',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
                'phone',
                'email',
                'status',
                'founding_date',
                'industry',
                'size',
                'currency',
                'timezone'
            ]);
    }

    /** @test */
    public function it_validates_unique_fields_when_creating_organization()
    {
        $existingOrg = Organization::factory()->create([
            'code' => 'TEST001',
            'registration_number' => 'REG123456',
            'email' => 'test@example.com'
        ]);

        $data = [
            'name' => 'Test Organization',
            'code' => 'TEST001', // Duplicate code
            'type' => 'Private Limited',
            'registration_number' => 'REG123456', // Duplicate registration number
            'email' => 'test@example.com', // Duplicate email
            'status' => 'active',
            'founding_date' => '2020-01-01',
            'industry' => 'Technology',
            'size' => 'medium',
            'currency' => 'USD',
            'timezone' => 'UTC'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/organizations', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'code',
                'registration_number',
                'email'
            ]);
    }
} 