<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\LoanRepayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class LoanRepaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $organization;
    protected $employee;
    protected $loan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        // Create organization
        $this->organization = Organization::factory()->create();

        // Create employee
        $this->employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        // Create active loan
        $this->loan = Loan::factory()->create([
            'employee_id' => $this->employee->id,
            'organization_id' => $this->organization->id,
            'status' => 'active',
            'amount' => 10000,
            'term_months' => 12,
            'interest_rate' => 5.5,
            'start_date' => now()->subMonths(2)
        ]);
    }

    /** @test */
    public function test_can_list_loan_repayments()
    {
        // Create some repayments
        LoanRepayment::factory()->count(3)->create([
            'loan_id' => $this->loan->id
        ]);

        $response = $this->getJson("/api/loans/{$this->loan->id}/repayments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'loan_id',
                        'amount',
                        'payment_date',
                        'payment_method',
                        'transaction_id',
                        'remarks',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_can_create_loan_repayment()
    {
        $repaymentData = [
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'loan_id',
                'amount',
                'payment_date',
                'payment_method',
                'transaction_id',
                'remarks',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('loan_repayments', [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_method' => 'bank_transfer'
        ]);
    }

    /** @test */
    public function test_cannot_create_repayment_for_non_active_loan()
    {
        $this->loan->update(['status' => 'pending']);

        $repaymentData = [
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot add repayment for non-active loan'
            ]);
    }

    /** @test */
    public function test_cannot_create_repayment_for_fully_paid_loan()
    {
        // Create repayments that sum up to the loan amount
        LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => $this->loan->amount
        ]);

        $repaymentData = [
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Loan is already fully paid'
            ]);
    }

    /** @test */
    public function test_can_get_repayment_details()
    {
        $repayment = LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id
        ]);

        $response = $this->getJson("/api/loans/{$this->loan->id}/repayments/{$repayment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'loan_id',
                'amount',
                'payment_date',
                'payment_method',
                'transaction_id',
                'remarks',
                'created_at',
                'updated_at'
            ]);
    }

    /** @test */
    public function test_can_update_repayment()
    {
        $repayment = LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => 1000
        ]);

        $updateData = [
            'amount' => 1500,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'transaction_id' => 'TRX789012',
            'remarks' => 'Updated payment'
        ];

        $response = $this->putJson("/api/loans/{$this->loan->id}/repayments/{$repayment->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'amount' => 1500,
                'payment_method' => 'cash',
                'remarks' => 'Updated payment'
            ]);

        $this->assertDatabaseHas('loan_repayments', [
            'id' => $repayment->id,
            'amount' => 1500,
            'payment_method' => 'cash'
        ]);
    }

    /** @test */
    public function test_can_delete_repayment()
    {
        $repayment = LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id
        ]);

        $response = $this->deleteJson("/api/loans/{$this->loan->id}/repayments/{$repayment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Repayment deleted successfully']);

        $this->assertDatabaseMissing('loan_repayments', ['id' => $repayment->id]);
    }

    /** @test */
    public function test_can_get_repayment_summary()
    {
        // Create repayments
        LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => 3000,
            'payment_date' => now()->subMonths(2)
        ]);

        LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => 2000,
            'payment_date' => now()->subMonth()
        ]);

        $response = $this->getJson("/api/loans/{$this->loan->id}/repayments/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_paid',
                'remaining_amount',
                'next_payment_date',
                'is_overdue',
                'days_overdue'
            ])
            ->assertJson([
                'total_paid' => 5000,
                'remaining_amount' => 5000
            ]);
    }

    /** @test */
    public function test_validates_repayment_amount()
    {
        $repaymentData = [
            'amount' => -1000, // Invalid amount
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function test_validates_payment_method()
    {
        $repaymentData = [
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'invalid_method',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function test_validates_payment_date()
    {
        $repaymentData = [
            'amount' => 1000,
            'payment_date' => 'invalid_date',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Monthly payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_date']);
    }

    /** @test */
    public function test_marks_loan_as_closed_when_fully_paid()
    {
        $repaymentData = [
            'amount' => $this->loan->amount,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TRX123456',
            'remarks' => 'Full payment'
        ];

        $response = $this->postJson("/api/loans/{$this->loan->id}/repayments", $repaymentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('loans', [
            'id' => $this->loan->id,
            'status' => 'closed'
        ]);
    }
} 