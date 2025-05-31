<?php

namespace Database\Factories;

use App\Models\TransactionLog;
use App\Models\Organization;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = TransactionLog::class;

    public function definition(): array
    {
        return [
            'loan_id' => null,
            'employee_id' => Employee::factory(),
            'transaction_type' => $this->faker->randomElement(['loan_disbursement', 'loan_repayment', 'salary_payment']),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'reference_id' => 'TRX' . $this->faker->unique()->numerify('######'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'metadata' => [],
            'remarks' => $this->faker->sentence(),
        ];
    }
} 