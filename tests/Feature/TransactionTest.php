<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Organization;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_transactions()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->count(3)->create([
            'organization_id' => $organization->id
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'amount',
                        'description',
                        'status',
                        'organization_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_transaction()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create([
            'organization_id' => $organization->id
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'type',
                'amount',
                'description',
                'status',
                'organization_id',
                'created_at',
                'updated_at'
            ]);
    }

    public function test_can_create_transaction()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $loan = Loan::factory()->create([
            'organization_id' => $organization->id
        ]);
        Sanctum::actingAs($user);

        $transactionData = [
            'type' => 'loan_disbursement',
            'amount' => 1000.00,
            'description' => 'Loan disbursement',
            'status' => 'completed',
            'organization_id' => $organization->id,
            'loan_id' => $loan->id
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'type',
                'amount',
                'description',
                'status',
                'organization_id',
                'loan_id',
                'created_at',
                'updated_at'
            ])
            ->assertJson($transactionData);

        $this->assertDatabaseHas('transactions', $transactionData);
    }

    public function test_can_update_transaction()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create([
            'organization_id' => $organization->id
        ]);

        $updateData = [
            'status' => 'cancelled',
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/transactions/{$transaction->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('transactions', array_merge(
            ['id' => $transaction->id],
            $updateData
        ));
    }

    public function test_can_delete_transaction()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create([
            'organization_id' => $organization->id
        ]);

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transaction deleted successfully'
            ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id
        ]);
    }

    public function test_validates_transaction_creation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transactions', [
            'type' => 'invalid_type',
            'amount' => -100,
            'description' => '',
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'amount', 'description', 'status']);
    }

    public function test_can_get_transaction_summary()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->create([
            'type' => 'loan_disbursement',
            'amount' => 1000.00,
            'organization_id' => $organization->id
        ]);

        Transaction::factory()->create([
            'type' => 'repayment',
            'amount' => 500.00,
            'organization_id' => $organization->id
        ]);

        $response = $this->getJson('/api/transactions/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_disbursements',
                'total_repayments',
                'net_amount',
                'transaction_count'
            ])
            ->assertJson([
                'total_disbursements' => 1000.00,
                'total_repayments' => 500.00,
                'net_amount' => 500.00,
                'transaction_count' => 2
            ]);
    }

    public function test_can_filter_transactions_by_date_range()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->create([
            'organization_id' => $organization->id,
            'created_at' => now()->subDays(5)
        ]);

        Transaction::factory()->create([
            'organization_id' => $organization->id,
            'created_at' => now()->subDays(2)
        ]);

        $response = $this->getJson('/api/transactions?start_date=' . now()->subDays(3)->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_transactions_by_type()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->create([
            'type' => 'loan_disbursement',
            'organization_id' => $organization->id
        ]);

        Transaction::factory()->create([
            'type' => 'repayment',
            'organization_id' => $organization->id
        ]);

        $response = $this->getJson('/api/transactions?type=loan_disbursement');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'loan_disbursement');
    }

    public function test_can_get_transaction_statistics()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'status' => 'completed'
        ]);

        Transaction::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/transactions/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_transactions',
                'completed_transactions',
                'pending_transactions',
                'average_amount',
                'total_amount'
            ])
            ->assertJson([
                'total_transactions' => 7,
                'completed_transactions' => 5,
                'pending_transactions' => 2
            ]);
    }
} 