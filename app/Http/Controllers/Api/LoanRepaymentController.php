<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanRepaymentResource;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoanRepaymentRequest;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Loan Repayments",
 *     description="API Endpoints for loan repayment management"
 * )
 */
class LoanRepaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/loans/{loan_id}/repayments",
     *     summary="Get all repayments for a loan",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan repayments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LoanRepayment")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Loan $loan)
    {
        $repayments = $loan->repayments()
            ->with('loan')
            ->latest()
            ->paginate(15);

        return LoanRepaymentResource::collection($repayments);
    }

    /**
     * @OA\Post(
     *     path="/api/loans/{loan_id}/repayments",
     *     summary="Create a new loan repayment",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "payment_date", "payment_method"},
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "check"}),
     *             @OA\Property(property="transaction_id", type="string"),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Loan repayment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanRepayment")
     *     )
     * )
     */
    public function store(LoanRepaymentRequest $request, Loan $loan)
    {
        try {
            DB::beginTransaction();

            // Check if loan is active or disbursed
            if (!in_array($loan->status, ['active', 'disbursed', 'pending'])) {
                return response()->json([
                    'message' => 'Cannot add repayment for loan with status: ' . $loan->status
                ], 422);
            }

            // Check if loan is already fully paid
            $totalPaid = $loan->repayments()->sum('amount');
            if ($totalPaid >= $loan->amount) {
                return response()->json([
                    'message' => 'Loan is already fully paid'
                ], 422);
            }

            $data = $request->validated();
            $data['loan_id'] = $loan->id;
            $data['employee_id'] = $loan->employee_id;
            $data['principal_amount'] = $data['amount'];
            $data['interest_amount'] = 0;

            $repayment = LoanRepayment::create($data);

            // Update loan status if fully paid
            $newTotalPaid = $totalPaid + $repayment->amount;
            if ($newTotalPaid >= $loan->amount) {
                $loan->update(['status' => 'closed']);
            }

            // Log the repayment
            TransactionLog::create([
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'loan_repayment',
                'status' => 'completed',
                'remarks' => "Loan repayment of {$repayment->amount} received"
            ]);

            DB::commit();

            return new LoanRepaymentResource($repayment->load('loan'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loan_id}/repayments/{id}",
     *     summary="Get loan repayment details",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan repayment details",
     *         @OA\JsonContent(ref="#/components/schemas/LoanRepayment")
     *     )
     * )
     */
    public function show(Loan $loan, LoanRepayment $repayment)
    {
        return new LoanRepaymentResource($repayment->load('loan'));
    }

    /**
     * @OA\Put(
     *     path="/api/loans/{loan_id}/repayments/{id}",
     *     summary="Update loan repayment details",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "bank_transfer", "check"}),
     *             @OA\Property(property="transaction_id", type="string"),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan repayment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanRepayment")
     *     )
     * )
     */
    public function update(LoanRepaymentRequest $request, Loan $loan, LoanRepayment $repayment)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $repayment->update($data);

            // Recalculate loan status
            $totalPaid = $loan->repayments()->sum('amount');
            if ($totalPaid >= $loan->amount) {
                $loan->update(['status' => 'closed']);
            } else {
                $loan->update(['status' => 'active']);
            }

            // Log the update
            TransactionLog::create([
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'loan_repayment_updated',
                'status' => 'completed',
                'remarks' => "Loan repayment updated to {$repayment->amount}"
            ]);

            DB::commit();

            return new LoanRepaymentResource($repayment->load('loan'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/loans/{loan_id}/repayments/{id}",
     *     summary="Delete a loan repayment",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan repayment deleted successfully"
     *     )
     * )
     */
    public function destroy(Loan $loan, LoanRepayment $repayment)
    {
        try {
            DB::beginTransaction();

            $repayment->delete();

            // Recalculate loan status
            $totalPaid = $loan->repayments()->sum('amount');
            if ($totalPaid >= $loan->amount) {
                $loan->update(['status' => 'closed']);
            } else {
                $loan->update(['status' => 'active']);
            }

            // Log the deletion
            TransactionLog::create([
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'loan_repayment_deleted',
                'status' => 'completed',
                'remarks' => "Loan repayment of {$repayment->amount} deleted"
            ]);

            DB::commit();

            return response()->json(['message' => 'Repayment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loan_id}/repayments/summary",
     *     summary="Get loan repayment summary",
     *     tags={"Loan Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan repayment summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_paid", type="number"),
     *             @OA\Property(property="remaining_amount", type="number"),
     *             @OA\Property(property="next_payment_date", type="string", format="date"),
     *             @OA\Property(property="is_overdue", type="boolean"),
     *             @OA\Property(property="days_overdue", type="integer")
     *         )
     *     )
     * )
     */
    public function summary(Loan $loan)
    {
        $totalPaid = $loan->repayments()->sum('amount');
        $remainingAmount = $loan->amount - $totalPaid;
        $lastPayment = $loan->repayments()->latest('payment_date')->first();
        $nextPaymentDate = $lastPayment 
            ? $lastPayment->payment_date->addMonth() 
            : $loan->start_date;
        $isOverdue = $nextPaymentDate < now();
        $daysOverdue = $isOverdue ? now()->diffInDays($nextPaymentDate) : 0;

        return response()->json([
            'total_paid' => $totalPaid,
            'remaining_amount' => $remainingAmount,
            'next_payment_date' => $nextPaymentDate,
            'is_overdue' => $isOverdue,
            'days_overdue' => $daysOverdue
        ]);
    }
}
