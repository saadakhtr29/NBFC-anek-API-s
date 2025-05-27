<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class SalaryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_salaries()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Salary::factory()->count(3)->create([
            'employee_id' => $employee->id
        ]);

        $response = $this->getJson('/api/salaries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'month',
                        'year',
                        'basic_salary',
                        'allowances',
                        'deductions',
                        'net_salary',
                        'payment_date',
                        'payment_status',
                        'payment_method',
                        'notes',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_salary()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $salaryData = [
            'employee_id' => $employee->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'basic_salary' => 50000,
            'allowances' => [
                'housing' => 5000,
                'transport' => 2000,
                'medical' => 1000
            ],
            'deductions' => [
                'tax' => 5000,
                'insurance' => 2000
            ],
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'notes' => 'Monthly salary payment'
        ];

        $response = $this->postJson('/api/salaries', $salaryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'employee_id',
                'month',
                'year',
                'basic_salary',
                'allowances',
                'deductions',
                'net_salary',
                'payment_date',
                'payment_status',
                'payment_method',
                'notes'
            ]);

        $this->assertDatabaseHas('salaries', [
            'employee_id' => $employee->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year
        ]);
    }

    public function test_can_update_salary()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        $salary = Salary::factory()->create([
            'employee_id' => $employee->id
        ]);
        Sanctum::actingAs($user);

        $updateData = [
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'notes' => 'Updated payment details'
        ];

        $response = $this->putJson("/api/salaries/{$salary->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'notes' => 'Updated payment details'
            ]);
    }

    public function test_can_delete_salary()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        $salary = Salary::factory()->create([
            'employee_id' => $employee->id
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/salaries/{$salary->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Salary record deleted successfully'
            ]);

        $this->assertDatabaseMissing('salaries', [
            'id' => $salary->id
        ]);
    }

    public function test_can_get_salary_by_month_year()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        Salary::factory()->create([
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year
        ]);

        $response = $this->getJson("/api/salaries?month={$month}&year={$year}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_salary_statistics()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Salary::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'payment_status' => 'paid'
        ]);

        Salary::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'payment_status' => 'pending'
        ]);

        $response = $this->getJson('/api/salaries/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_salaries',
                'total_paid',
                'total_pending',
                'average_salary',
                'salary_distribution',
                'payment_method_distribution'
            ])
            ->assertJson([
                'total_salaries' => 5,
                'total_paid' => 3,
                'total_pending' => 2
            ]);
    }

    public function test_can_export_salaries()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Salary::factory()->count(3)->create([
            'employee_id' => $employee->id
        ]);

        $response = $this->getJson('/api/salaries/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="salaries.csv"');
    }

    public function test_validates_salary_creation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/salaries', [
            'employee_id' => '',
            'month' => 'invalid-month',
            'year' => 'invalid-year',
            'basic_salary' => 'not-a-number',
            'payment_status' => 'invalid-status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'employee_id',
                'month',
                'year',
                'basic_salary',
                'payment_status'
            ]);
    }

    public function test_validates_salary_month_year()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        // Create existing salary record
        Salary::factory()->create([
            'employee_id' => $employee->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year
        ]);

        // Try to create duplicate salary record
        $response = $this->postJson('/api/salaries', [
            'employee_id' => $employee->id,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'basic_salary' => 50000,
            'payment_status' => 'pending'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['month', 'year']);
    }

    public function test_can_process_bulk_salary()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employees = Employee::factory()->count(3)->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $salaryData = [
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'salaries' => $employees->map(function ($employee) {
                return [
                    'employee_id' => $employee->id,
                    'basic_salary' => 50000,
                    'allowances' => [
                        'housing' => 5000,
                        'transport' => 2000
                    ],
                    'deductions' => [
                        'tax' => 5000
                    ]
                ];
            })->toArray()
        ];

        $response = $this->postJson('/api/salaries/bulk', $salaryData);

        $response->assertStatus(201)
            ->assertJsonCount(3, 'data');

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('salaries', [
                'employee_id' => $employee->id,
                'month' => Carbon::now()->month,
                'year' => Carbon::now()->year
            ]);
        }
    }
} 