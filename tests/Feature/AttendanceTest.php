<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Attendance::factory()->count(3)->create([
            'employee_id' => $employee->id
        ]);

        $response = $this->getJson('/api/attendance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'date',
                        'check_in',
                        'check_out',
                        'status',
                        'notes',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $attendanceData = [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
            'notes' => 'Regular working day'
        ];

        $response = $this->postJson('/api/attendance', $attendanceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'employee_id',
                'date',
                'check_in',
                'check_out',
                'status',
                'notes'
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->format('Y-m-d')
        ]);
    }

    public function test_can_update_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id
        ]);
        Sanctum::actingAs($user);

        $updateData = [
            'check_out' => '18:00:00',
            'notes' => 'Overtime work'
        ];

        $response = $this->putJson("/api/attendance/{$attendance->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'check_out' => '18:00:00',
                'notes' => 'Overtime work'
            ]);
    }

    public function test_can_delete_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/attendance/{$attendance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Attendance record deleted successfully'
            ]);

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id
        ]);
    }

    public function test_can_get_attendance_by_date_range()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $startDate = Carbon::today()->subDays(5);
        $endDate = Carbon::today();

        Attendance::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'date' => $startDate->format('Y-m-d')
        ]);

        $response = $this->getJson("/api/attendance?start_date={$startDate->format('Y-m-d')}&end_date={$endDate->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_attendance_statistics()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Attendance::factory()->count(5)->create([
            'employee_id' => $employee->id,
            'status' => 'present'
        ]);

        Attendance::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'status' => 'absent'
        ]);

        $response = $this->getJson('/api/attendance/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_days',
                'present_days',
                'absent_days',
                'late_days',
                'early_leaving_days',
                'attendance_rate'
            ])
            ->assertJson([
                'total_days' => 7,
                'present_days' => 5,
                'absent_days' => 2
            ]);
    }

    public function test_can_export_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        Attendance::factory()->count(3)->create([
            'employee_id' => $employee->id
        ]);

        $response = $this->getJson('/api/attendance/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="attendance.csv"');
    }

    public function test_validates_attendance_creation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/attendance', [
            'employee_id' => '',
            'date' => 'invalid-date',
            'check_in' => 'invalid-time',
            'status' => 'invalid-status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'employee_id',
                'date',
                'check_in',
                'status'
            ]);
    }

    public function test_validates_check_out_time()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/attendance', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in' => '17:00:00',
            'check_out' => '09:00:00',
            'status' => 'present'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_can_mark_bulk_attendance()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $employees = Employee::factory()->count(3)->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $attendanceData = [
            'date' => Carbon::today()->format('Y-m-d'),
            'attendance' => $employees->map(function ($employee) {
                return [
                    'employee_id' => $employee->id,
                    'status' => 'present',
                    'check_in' => '09:00:00',
                    'check_out' => '17:00:00'
                ];
            })->toArray()
        ];

        $response = $this->postJson('/api/attendance/bulk', $attendanceData);

        $response->assertStatus(201)
            ->assertJsonCount(3, 'data');

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('attendances', [
                'employee_id' => $employee->id,
                'date' => Carbon::today()->format('Y-m-d')
            ]);
        }
    }
} 