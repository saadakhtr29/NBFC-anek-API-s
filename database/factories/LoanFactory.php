<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory()->create();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $amount = $this->faker->numberBetween(1000, 100000);
        $interestRate = $this->faker->randomFloat(2, 5, 15);
        $termMonths = $this->faker->randomElement([12, 24, 36, 48, 60]);
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify("+{$termMonths} months");

        return [
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'loan_number' => 'LOAN' . $this->faker->unique()->numberBetween(1000, 9999),
            'type' => $this->faker->randomElement([
                'Personal Loan',
                'Home Loan',
                'Business Loan',
                'Education Loan',
                'Vehicle Loan'
            ]),
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement([
                'pending',
                'approved',
                'rejected',
                'disbursed',
                'active',
                'completed',
                'defaulted'
            ]),
            'purpose' => $this->faker->sentence(),
            'collateral' => $this->faker->optional()->sentence(),
            'guarantor_name' => $this->faker->optional()->name(),
            'guarantor_contact' => $this->faker->optional()->phoneNumber(),
            'guarantor_relationship' => $this->faker->optional()->randomElement([
                'Spouse',
                'Parent',
                'Sibling',
                'Friend',
                'Relative'
            ]),
            'approved_by' => $this->faker->optional()->randomElement(User::pluck('id')->toArray()),
            'approved_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'rejected_by' => $this->faker->optional()->randomElement(User::pluck('id')->toArray()),
            'rejected_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'rejection_reason' => $this->faker->optional()->sentence(),
            'disbursed_by' => $this->faker->optional()->randomElement(User::pluck('id')->toArray()),
            'disbursed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'disbursement_method' => $this->faker->optional()->randomElement([
                'Bank Transfer',
                'Cash',
                'Check',
                'Wire Transfer'
            ]),
            'disbursement_details' => $this->faker->optional()->randomElement([
                [
                    'account_number' => $this->faker->bankAccountNumber,
                    'bank_name' => $this->faker->company,
                    'transaction_id' => $this->faker->uuid
                ]
            ]),
            'documents' => $this->faker->optional()->randomElement([
                [
                    'document_type' => $this->faker->randomElement(['ID', 'Proof of Income', 'Bank Statement']),
                    'file_name' => $this->faker->word . '.pdf',
                    'upload_date' => $this->faker->dateTimeThisMonth()->format('Y-m-d')
                ]
            ]),
            'remarks' => $this->faker->optional()->sentence(),
            'settings' => [
                'late_fee_rate' => $this->faker->randomFloat(2, 1, 5),
                'grace_period_days' => $this->faker->numberBetween(1, 7),
                'auto_debit' => $this->faker->boolean(),
                'notifications' => [
                    'email' => $this->faker->boolean(),
                    'sms' => $this->faker->boolean(),
                    'push' => $this->faker->boolean()
                ]
            ]
        ];
    }

    /**
     * Indicate that the loan is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => null,
            'disbursed_at' => null,
            'disbursement_method' => null,
            'disbursement_details' => null
        ]);
    }

    /**
     * Indicate that the loan is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory()->create()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => null,
            'disbursed_at' => null,
            'disbursement_method' => null,
            'disbursement_details' => null
        ]);
    }

    /**
     * Indicate that the loan is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => User::factory()->create()->id,
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
            'disbursed_by' => null,
            'disbursed_at' => null,
            'disbursement_method' => null,
            'disbursement_details' => null
        ]);
    }

    /**
     * Indicate that the loan is disbursed.
     */
    public function disbursed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disbursed',
            'approved_by' => User::factory()->create()->id,
            'approved_at' => now()->subDays(7),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => User::factory()->create()->id,
            'disbursed_at' => now(),
            'disbursement_method' => $this->faker->randomElement([
                'Bank Transfer',
                'Cash',
                'Check',
                'Wire Transfer'
            ]),
            'disbursement_details' => [
                'account_number' => $this->faker->bankAccountNumber,
                'bank_name' => $this->faker->company,
                'transaction_id' => $this->faker->uuid
            ]
        ]);
    }

    /**
     * Indicate that the loan is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'approved_by' => User::factory()->create()->id,
            'approved_at' => now()->subMonths(2),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => User::factory()->create()->id,
            'disbursed_at' => now()->subMonths(1),
            'disbursement_method' => $this->faker->randomElement([
                'Bank Transfer',
                'Cash',
                'Check',
                'Wire Transfer'
            ]),
            'disbursement_details' => [
                'account_number' => $this->faker->bankAccountNumber,
                'bank_name' => $this->faker->company,
                'transaction_id' => $this->faker->uuid
            ]
        ]);
    }

    /**
     * Indicate that the loan is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'approved_by' => User::factory()->create()->id,
            'approved_at' => now()->subMonths(13),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => User::factory()->create()->id,
            'disbursed_at' => now()->subMonths(12),
            'disbursement_method' => $this->faker->randomElement([
                'Bank Transfer',
                'Cash',
                'Check',
                'Wire Transfer'
            ]),
            'disbursement_details' => [
                'account_number' => $this->faker->bankAccountNumber,
                'bank_name' => $this->faker->company,
                'transaction_id' => $this->faker->uuid
            ]
        ]);
    }

    /**
     * Indicate that the loan is defaulted.
     */
    public function defaulted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'defaulted',
            'approved_by' => User::factory()->create()->id,
            'approved_at' => now()->subMonths(7),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'disbursed_by' => User::factory()->create()->id,
            'disbursed_at' => now()->subMonths(6),
            'disbursement_method' => $this->faker->randomElement([
                'Bank Transfer',
                'Cash',
                'Check',
                'Wire Transfer'
            ]),
            'disbursement_details' => [
                'account_number' => $this->faker->bankAccountNumber,
                'bank_name' => $this->faker->company,
                'transaction_id' => $this->faker->uuid
            ]
        ]);
    }

    /**
     * Indicate that the loan has a guarantor.
     */
    public function withGuarantor(): static
    {
        return $this->state(fn (array $attributes) => [
            'guarantor_name' => $this->faker->name(),
            'guarantor_contact' => $this->faker->phoneNumber(),
            'guarantor_relationship' => $this->faker->randomElement([
                'Spouse',
                'Parent',
                'Sibling',
                'Friend',
                'Relative'
            ])
        ]);
    }

    /**
     * Indicate that the loan has collateral.
     */
    public function withCollateral(): static
    {
        return $this->state(fn (array $attributes) => [
            'collateral' => $this->faker->sentence()
        ]);
    }

    /**
     * Indicate that the loan has documents.
     */
    public function withDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'documents' => [
                'loan_agreement.pdf',
                'income_proof.pdf',
                'bank_statement.pdf',
                'identity_proof.pdf'
            ]
        ]);
    }
} 