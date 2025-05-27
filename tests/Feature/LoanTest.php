<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanDeficit;
use App\Models\LoanExcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LoanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $organization;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->organization = Organization::factory()->create();
        $this->employee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);
    }

    /** @test */
    public function it_can_list_loans()
    {
        Loan::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'organization_id',
                        'amount',
                        'term_months',
                        'interest_rate',
                        'monthly_payment',
                        'purpose',
                        'status',
                        'start_date',
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
    public function it_can_filter_loans_by_status()
    {
        Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        Loan::factory()->approved()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    /** @test */
    public function it_can_filter_loans_by_type()
    {
        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'type' => 'Personal Loan'
        ]);

        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'type' => 'Business Loan'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans?type=Personal Loan');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'Personal Loan');
    }

    /** @test */
    public function it_can_filter_loans_by_organization()
    {
        $otherOrganization = Organization::factory()->create();

        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        Loan::factory()->create([
            'organization_id' => $otherOrganization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.organization_id', $this->organization->id);
    }

    /** @test */
    public function it_can_filter_loans_by_employee()
    {
        $otherEmployee = Employee::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $otherEmployee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans?employee_id=' . $this->employee->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_id', $this->employee->id);
    }

    /** @test */
    public function it_can_search_loans()
    {
        Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN1234',
            'purpose' => 'Home renovation',
            'guarantor_name' => 'John Doe'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans?search=LOAN1234');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.loan_number', 'LOAN1234');
    }

    /** @test */
    public function it_can_create_a_loan()
    {
        Storage::fake('local');

        $loanData = [
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'type' => 'Personal Loan',
            'amount' => 10000,
            'interest_rate' => 5.5,
            'term_months' => 12,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(12)->format('Y-m-d'),
            'purpose' => 'Home renovation',
            'documents' => [
                UploadedFile::fake()->create('document1.pdf'),
                UploadedFile::fake()->create('document2.pdf')
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans', $loanData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'organization_id',
                    'employee_id',
                    'loan_number',
                    'type',
                    'amount',
                    'interest_rate',
                    'term_months',
                    'start_date',
                    'end_date',
                    'status',
                    'purpose',
                    'documents',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('loans', [
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'type' => 'Personal Loan',
            'amount' => 10000,
            'interest_rate' => 5.5,
            'term_months' => 12,
            'purpose' => 'Home renovation'
        ]);

        Storage::disk('local')->assertExists('loan-documents/' . $loanData['documents'][0]->hashName());
        Storage::disk('local')->assertExists('loan-documents/' . $loanData['documents'][1]->hashName());
    }

    /** @test */
    public function it_validates_required_fields_when_creating_loan()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'organization_id',
                'employee_id',
                'type',
                'amount',
                'interest_rate',
                'term_months',
                'start_date',
                'end_date'
            ]);
    }

    /** @test */
    public function it_can_show_loan_details()
    {
        $loan = Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans/' . $loan->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'organization_id',
                    'employee_id',
                    'loan_number',
                    'type',
                    'amount',
                    'interest_rate',
                    'term_months',
                    'start_date',
                    'end_date',
                    'status',
                    'purpose',
                    'organization',
                    'employee',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function it_can_update_loan_details()
    {
        Storage::fake('local');

        $loan = Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $updateData = [
            'type' => 'Business Loan',
            'amount' => 20000,
            'interest_rate' => 6.5,
            'term_months' => 24,
            'purpose' => 'Business expansion',
            'documents' => [
                UploadedFile::fake()->create('new_document.pdf')
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/loans/' . $loan->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'Business Loan')
            ->assertJsonPath('data.amount', 20000)
            ->assertJsonPath('data.interest_rate', 6.5)
            ->assertJsonPath('data.term_months', 24)
            ->assertJsonPath('data.purpose', 'Business expansion');

        Storage::disk('local')->assertExists('loan-documents/' . $updateData['documents'][0]->hashName());
    }

    /** @test */
    public function it_can_delete_a_loan()
    {
        $loan = Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/loans/' . $loan->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted($loan);
    }

    /** @test */
    public function it_cannot_delete_loan_with_repayments()
    {
        $loan = Loan::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        // Create a repayment for the loan
        $loan->repayments()->create([
            'amount' => 1000,
            'payment_date' => now(),
            'status' => 'completed'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/loans/' . $loan->id);

        $response->assertBadRequest()
            ->assertJsonPath('message', 'Cannot delete loan with active repayments');

        $this->assertNotSoftDeleted($loan);
    }

    /** @test */
    public function it_can_approve_a_loan()
    {
        $loan = Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/approve');

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', $this->user->id)
            ->assertNotNull('data.approved_at');
    }

    /** @test */
    public function it_cannot_approve_non_pending_loan()
    {
        $loan = Loan::factory()->approved()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/approve');

        $response->assertBadRequest()
            ->assertJsonPath('message', 'Only pending loans can be approved');
    }

    /** @test */
    public function it_can_reject_a_loan()
    {
        $loan = Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/reject', [
            'rejection_reason' => 'Insufficient income'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejected_by', $this->user->id)
            ->assertJsonPath('data.rejection_reason', 'Insufficient income')
            ->assertNotNull('data.rejected_at');
    }

    /** @test */
    public function it_cannot_reject_non_pending_loan()
    {
        $loan = Loan::factory()->approved()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/reject', [
            'rejection_reason' => 'Insufficient income'
        ]);

        $response->assertBadRequest()
            ->assertJsonPath('message', 'Only pending loans can be rejected');
    }

    /** @test */
    public function it_can_disburse_a_loan()
    {
        $loan = Loan::factory()->approved()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/disburse', [
            'disbursement_method' => 'Bank Transfer',
            'disbursement_details' => [
                'account_number' => '1234567890',
                'bank_name' => 'Test Bank',
                'transaction_id' => 'TRX123'
            ]
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'disbursed')
            ->assertJsonPath('data.disbursed_by', $this->user->id)
            ->assertJsonPath('data.disbursement_method', 'Bank Transfer')
            ->assertNotNull('data.disbursed_at');
    }

    /** @test */
    public function it_cannot_disburse_non_approved_loan()
    {
        $loan = Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/loans/' . $loan->id . '/disburse', [
            'disbursement_method' => 'Bank Transfer',
            'disbursement_details' => [
                'account_number' => '1234567890',
                'bank_name' => 'Test Bank',
                'transaction_id' => 'TRX123'
            ]
        ]);

        $response->assertBadRequest()
            ->assertJsonPath('message', 'Only approved loans can be disbursed');
    }

    /** @test */
    public function it_can_get_loan_statistics()
    {
        // Create loans with different statuses
        Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'amount' => 10000
        ]);

        Loan::factory()->approved()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'amount' => 20000
        ]);

        Loan::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'amount' => 30000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_loans',
                'total_amount',
                'total_interest',
                'total_repaid',
                'total_outstanding',
                'overdue_loans',
                'overdue_amount',
                'status_distribution'
            ])
            ->assertJsonPath('total_loans', 3)
            ->assertJsonPath('total_amount', 60000);
    }

    /** @test */
    public function it_can_filter_loan_statistics_by_organization()
    {
        $otherOrganization = Organization::factory()->create();

        // Create loans for first organization
        Loan::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $this->employee->id,
            'amount' => 10000
        ]);

        // Create loans for second organization
        Loan::factory()->approved()->create([
            'organization_id' => $otherOrganization->id,
            'employee_id' => $this->employee->id,
            'amount' => 20000
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/loans/statistics?organization_id=' . $this->organization->id);

        $response->assertOk()
            ->assertJsonPath('total_loans', 1)
            ->assertJsonPath('total_amount', 10000);
    }
} 