<?php

namespace Database\Factories;

use App\Models\Salary;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryFactory extends Factory
{
    protected $model = Salary::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'organization_id' => Organization::factory(),
            'month' => $this->faker->numberBetween(1, 12),
            'year' => $this->faker->numberBetween(2000, 2030),
            'basic_salary' => $this->faker->randomFloat(2, 10000, 100000),
            'allowances' => $this->faker->randomFloat(2, 0, 10000),
            'deductions' => $this->faker->randomFloat(2, 0, 5000),
            'net_salary' => $this->faker->randomFloat(2, 8000, 95000),
            'payment_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'cheque']),
            'reference_number' => 'REF' . $this->faker->unique()->numerify('######'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'notes' => $this->faker->sentence(),
        ];
    }
} 