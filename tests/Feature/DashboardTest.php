<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Attendance;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->organization = Organization::factory()->create();
    }

    public function test_can_get_overview_statistics()
    {
        // Create test data
        Organization::factory()->count(2)->create();
        Employee::factory()->count(5)->create();
        Loan::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/dashboard/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_organizations',
                    'total_employees',
                    'total_loans',
                    'total_loan_amount',
                    'total_paid_amount',
                    'total_pending_amount'
                ]
            ]);
    }

    public function test_can_get_organization_statistics()
    {
        // Create test data for the organization
        Employee::factory()->count(3)->create(['organization_id' => $this->organization->id]);
        Loan::factory()->count(2)->create(['organization_id' => $this->organization->id]);
        Attendance::factory()->count(5)->create(['organization_id' => $this->organization->id]);
        Salary::factory()->count(3)->create(['organization_id' => $this->organization->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/dashboard/organization/{$this->organization->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_employees',
                    'total_loans',
                    'total_loan_amount',
                    'total_paid_amount',
                    'total_pending_amount',
                    'average_loan_amount',
                    'total_salary_paid',
                    'attendance_rate'
                ]
            ]);
    }

    public function test_can_get_loan_statistics()
    {
        // Create test data
        Loan::factory()->count(5)->create(['organization_id' => $this->organization->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/dashboard/loans?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_loans',
                    'total_amount',
                    'status_distribution',
                    'monthly_trend'
                ]
            ]);
    }

    public function test_can_get_attendance_statistics()
    {
        // Create test data
        Attendance::factory()->count(10)->create([
            'organization_id' => $this->organization->id,
            'status' => 'present'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/dashboard/attendance?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_records',
                    'status_distribution',
                    'average_work_hours',
                    'total_overtime_hours',
                    'daily_trend'
                ]
            ]);
    }

    public function test_can_get_salary_statistics()
    {
        // Create test data
        Salary::factory()->count(5)->create([
            'organization_id' => $this->organization->id,
            'status' => 'paid'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/dashboard/salaries?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_paid',
                    'total_pending',
                    'average_salary',
                    'monthly_distribution',
                    'department_distribution'
                ]
            ]);
    }

    public function test_can_filter_statistics_by_date_range()
    {
        // Create test data with specific dates
        $startDate = now()->subDays(30);
        $endDate = now();

        Attendance::factory()->count(5)->create([
            'organization_id' => $this->organization->id,
            'date' => $this->faker->dateTimeBetween($startDate, $endDate)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/dashboard/attendance?organization_id={$this->organization->id}&start_date={$startDate->format('Y-m-d')}&end_date={$endDate->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_records',
                    'status_distribution',
                    'average_work_hours',
                    'total_overtime_hours',
                    'daily_trend'
                ]
            ]);
    }

    public function test_can_filter_salary_statistics_by_year()
    {
        // Create test data for specific year
        $year = 2024;
        Salary::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'year' => $year
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/dashboard/salaries?organization_id={$this->organization->id}&year={$year}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_paid',
                    'total_pending',
                    'average_salary',
                    'monthly_distribution',
                    'department_distribution'
                ]
            ]);
    }
} 