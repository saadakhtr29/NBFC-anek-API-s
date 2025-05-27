<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = Organization::all();
        $admin = User::where('email', 'admin@example.com')->first();

        foreach ($organizations as $organization) {
            $employees = Employee::where('organization_id', $organization->id)->get();

            foreach ($employees as $employee) {
                // Create attendance records for the last 30 days
                for ($i = 0; $i < 30; $i++) {
                    $date = now()->subDays($i);
                    
                    // Skip weekends
                    if ($date->isWeekend()) {
                        continue;
                    }

                    // Randomly determine attendance status
                    $status = rand(1, 100) <= 90 ? 'present' : 
                             (rand(1, 100) <= 50 ? 'late' : 
                             (rand(1, 100) <= 50 ? 'half_day' : 'leave'));

                    if ($status === 'present' || $status === 'late' || $status === 'half_day') {
                        $checkIn = $date->copy()->setHour(rand(8, 10))->setMinute(rand(0, 59));
                        $checkOut = $date->copy()->setHour(rand(17, 19))->setMinute(rand(0, 59));
                        $workHours = round(($checkOut->timestamp - $checkIn->timestamp) / 3600, 2);
                        $overtimeHours = max(0, $workHours - 8);

                        Attendance::create([
                            'employee_id' => $employee->id,
                            'organization_id' => $organization->id,
                            'date' => $date->format('Y-m-d'),
                            'check_in' => $checkIn->format('H:i:s'),
                            'check_out' => $checkOut->format('H:i:s'),
                            'status' => $status,
                            'work_hours' => $workHours,
                            'overtime_hours' => $overtimeHours,
                            'notes' => $status === 'late' ? 'Late arrival' : null,
                            'verified_by' => $admin->id,
                            'verified_at' => $date->addHours(rand(1, 3)),
                        ]);
                    } else {
                        Attendance::create([
                            'employee_id' => $employee->id,
                            'organization_id' => $organization->id,
                            'date' => $date->format('Y-m-d'),
                            'status' => $status,
                            'work_hours' => 0,
                            'overtime_hours' => 0,
                            'notes' => 'On leave',
                            'verified_by' => $admin->id,
                            'verified_at' => $date->addHours(rand(1, 3)),
                        ]);
                    }
                }
            }
        }
    }
} 