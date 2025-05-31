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
    protected $repayment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->organization = Organization::factory()->create();
        $this->employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);
        $this->loan = Loan::factory()->create([
            'employee_id' => $this->employee->id,
            'organization_id' => $this->organization->id,
            'status' => 'disbursed',
            'amount' => 10000,
            'term_months' => 12,
            'interest_rate' => 5.5,
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
            'disbursed_by' => $this->user->id,
            'disbursed_at' => now(),
            'disbursement_method' => 'bank_transfer'
        ]);
        $this->repayment = LoanRepayment::factory()->create([
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'remarks' => 'Monthly installment'
        ]);
    }

    /** @test */
    public function test_can_list_repayments()
    {
        $response = $this->getJson('/api/repayments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'loan_id',
                        'amount',
                        'payment_date',
                        'payment_method',
                        'status',
                        'remarks',
                        'loan'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /** @test */
    public function test_can_create_repayment()
    {
        $repaymentData = [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'remarks' => 'Monthly installment'
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'loan_id',
                    'amount',
                    'payment_date',
                    'payment_method',
                    'status',
                    'remarks',
                    'loan'
                ]
            ]);

        $this->assertDatabaseHas('repayments', [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'remarks' => 'Monthly installment'
        ]);
    }

    /** @test */
    public function test_can_get_repayment_details()
    {
        $response = $this->getJson("/api/repayments/{$this->repayment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'loan_id',
                    'amount',
                    'payment_date',
                    'payment_method',
                    'status',
                    'remarks',
                    'loan'
                ]
            ]);
    }

    /** @test */
    public function test_can_approve_repayment()
    {
        $response = $this->postJson("/api/repayments/{$this->repayment->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Repayment approved successfully'
            ]);

        $this->assertDatabaseHas('repayments', [
            'id' => $this->repayment->id,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function test_can_reject_repayment()
    {
        $rejectionData = [
            'rejection_reason' => 'Insufficient funds'
        ];

        $response = $this->postJson("/api/repayments/{$this->repayment->id}/reject", $rejectionData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Repayment rejected successfully'
            ]);

        $this->assertDatabaseHas('repayments', [
            'id' => $this->repayment->id,
            'status' => 'failed'
        ]);
    }

    /** @test */
    public function test_can_get_repayment_statistics()
    {
        $response = $this->getJson('/api/repayments/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_repayments',
                'total_amount',
                'status_distribution',
                'payment_method_distribution',
                'monthly_trends'
            ]);
    }

    /** @test */
    public function test_cannot_create_repayment_for_non_disbursed_loan()
    {
        // Update loan status to pending
        $this->loan->update(['status' => 'pending']);

        $repaymentData = [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'remarks' => 'Monthly installment'
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot create repayment for non-disbursed loan'
            ]);
    }

    /** @test */
    public function test_cannot_create_repayment_for_rejected_loan()
    {
        // Update loan status to rejected
        $this->loan->update(['status' => 'rejected']);

        $repaymentData = [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'remarks' => 'Monthly installment'
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot create repayment for rejected loan'
            ]);
    }

    /** @test */
    public function test_cannot_create_repayment_for_completed_loan()
    {
        // Update loan status to completed
        $this->loan->update(['status' => 'completed']);

        $repaymentData = [
            'loan_id' => $this->loan->id,
            'amount' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'remarks' => 'Monthly installment'
        ];

        $response = $this->postJson('/api/repayments', $repaymentData);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot create repayment for completed loan'
            ]);
    }
} 