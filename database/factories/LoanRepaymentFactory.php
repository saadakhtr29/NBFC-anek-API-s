<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanRepaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'employee_id' => Employee::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'principal_amount' => $this->faker->randomFloat(2, 100, 1000),
            'interest_amount' => $this->faker->randomFloat(2, 10, 100),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'cheque']),
            'payment_type' => $this->faker->randomElement(['standard', 'excess']),
            'transaction_id' => 'TRX' . $this->faker->unique()->numerify('######'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'remarks' => $this->faker->sentence(),
        ];
    }
} 