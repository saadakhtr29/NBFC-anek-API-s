<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('08:00', '10:00');
        $checkOut = $this->faker->dateTimeBetween('17:00', '19:00');
        $workHours = round(($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600, 2);
        $overtimeHours = max(0, $workHours - 8);

        return [
            'employee_id' => Employee::factory(),
            'organization_id' => Organization::factory(),
            'date' => $this->faker->date(),
            'check_in' => $checkIn->format('H:i:s'),
            'check_out' => $checkOut->format('H:i:s'),
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'half_day', 'leave']),
            'work_hours' => $workHours,
            'overtime_hours' => $overtimeHours,
            'notes' => $this->faker->optional()->sentence,
            'verified_by' => User::factory(),
            'verified_at' => $this->faker->optional()->dateTime(),
        ];
    }
} 