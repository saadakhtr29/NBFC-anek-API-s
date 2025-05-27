<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_list_employees()
    {
        // Create 3 employees
        Employee::factory()->count(3)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'first_name',
                        'last_name',
                        'email',
                        'organization',
                        'user',
                        'loans'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_an_employee()
    {
        $employeeData = [
            'organization_id' => $this->organization->id,
            'employee_id' => 'EMP' . $this->faker->unique()->numberBetween(1000, 9999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'date_of_birth' => $this->faker->date(),
            'date_of_joining' => $this->faker->date(),
            'designation' => 'Manager',
            'department' => 'IT',
            'salary' => 50000,
            'status' => 'active',
            'employment_type' => 'full_time',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '1234567890',
            'bank_ifsc_code' => 'TEST0001234',
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => 'Spouse',
            'documents' => [
                'id_proof' => 'test.jpg',
                'address_proof' => 'test.pdf'
            ],
            'remarks' => 'Test remarks'
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_id',
                    'first_name',
                    'last_name',
                    'email',
                    'organization',
                    'user'
                ]
            ]);

        $this->assertDatabaseHas('employees', [
            'employee_id' => $employeeData['employee_id'],
            'email' => $employeeData['email']
        ]);
    }

    /** @test */
    public function it_can_get_employee_details()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_id',
                    'first_name',
                    'last_name',
                    'email',
                    'organization',
                    'user',
                    'loans'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'email' => $employee->email
                ]
            ]);
    }

    /** @test */
    public function it_can_update_employee_details()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'designation' => 'Senior Manager',
            'department' => 'HR',
            'salary' => 75000
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_id',
                    'first_name',
                    'last_name',
                    'email',
                    'organization',
                    'user'
                ]
            ])
            ->assertJson([
                'data' => [
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                    'designation' => 'Senior Manager',
                    'department' => 'HR',
                    'salary' => '75000.00'
                ]
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ]);
    }

    /** @test */
    public function it_can_delete_an_employee()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Employee deleted successfully']);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    /** @test */
    public function it_cannot_delete_employee_with_active_loans()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        // Create an active loan for the employee
        $employee->loans()->create([
            'organization_id' => $this->organization->id,
            'amount' => 1000,
            'status' => 'active'
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete employee with active loans']);

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    /** @test */
    public function it_can_get_employee_statistics()
    {
        // Create employees with different statuses and departments
        Employee::factory()->count(5)->active()->create([
            'organization_id' => $this->organization->id,
            'department' => 'IT'
        ]);

        Employee::factory()->count(3)->inactive()->create([
            'organization_id' => $this->organization->id,
            'department' => 'HR'
        ]);

        Employee::factory()->count(2)->onLeave()->create([
            'organization_id' => $this->organization->id,
            'department' => 'Finance'
        ]);

        $response = $this->getJson('/api/employees/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_employees',
                'active_employees',
                'department_distribution',
                'designation_distribution',
                'employment_type_distribution',
                'monthly_joining_trend'
            ])
            ->assertJson([
                'total_employees' => 10,
                'active_employees' => 5
            ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_employee()
    {
        $response = $this->postJson('/api/employees', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'organization_id',
                'employee_id',
                'first_name',
                'last_name',
                'email',
                'date_of_joining',
                'designation',
                'department',
                'salary',
                'status',
                'employment_type'
            ]);
    }

    /** @test */
    public function it_validates_unique_fields_when_creating_employee()
    {
        $existingEmployee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $employeeData = [
            'organization_id' => $this->organization->id,
            'employee_id' => $existingEmployee->employee_id,
            'email' => $existingEmployee->email,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_joining' => $this->faker->date(),
            'designation' => 'Manager',
            'department' => 'IT',
            'salary' => 50000,
            'status' => 'active',
            'employment_type' => 'full_time'
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'employee_id',
                'email'
            ]);
    }

    public function test_can_upload_profile_photo()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'profile_photo' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profile_photo'
            ]);

        Storage::disk('public')->assertExists('employee-photos/' . $file->hashName());
    }

    public function test_can_search_employees()
    {
        Employee::factory()->create([
            'name' => 'John Doe',
            'organization_id' => $this->organization->id
        ]);

        Employee::factory()->create([
            'name' => 'Jane Smith',
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/employees?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_can_filter_employees_by_department()
    {
        Employee::factory()->create([
            'department' => 'IT',
            'organization_id' => $this->organization->id
        ]);

        Employee::factory()->create([
            'department' => 'HR',
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/employees?department=IT');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.department', 'IT');
    }

    public function test_can_export_employees()
    {
        Employee::factory()->count(3)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->getJson('/api/employees/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="employees.csv"');
    }

    public function test_validates_profile_photo()
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'profile_photo' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_photo']);
    }
} 