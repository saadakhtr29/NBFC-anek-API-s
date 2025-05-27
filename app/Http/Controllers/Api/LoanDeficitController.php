<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanDeficitResource;
use App\Models\Loan;
use App\Models\LoanDeficit;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Loan Deficits",
 *     description="API Endpoints for loan deficit management"
 * )
 */
class LoanDeficitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/loan-deficits",
     *     tags={"Loan Deficits"},
     *     summary="Get all loan deficits",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="query",
     *         description="Filter by loan ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by deficit status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan deficits",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/LoanDeficit")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = LoanDeficit::with(['loan', 'employee']);

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deficits = $query->latest()->paginate(10);

        return LoanDeficitResource::collection($deficits);
    }

    /**
     * @OA\Post(
     *     path="/api/loan-deficits",
     *     tags={"Loan Deficits"},
     *     summary="Create a new loan deficit",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"loan_id","amount","due_date"},
     *             @OA\Property(property="loan_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="fee_amount", type="number", format="float"),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Loan deficit created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDeficit")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'fee_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($request->loan_id);

            $deficit = LoanDeficit::create([
                'loan_id' => $loan->id,
                'employee_id' => $loan->employee_id,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'fee_amount' => $request->fee_amount,
                'remarks' => $request->remarks,
            ]);

            // Update loan status to overdue if not already
            if ($loan->status !== 'overdue') {
                $loan->update(['status' => 'overdue']);
            }

            // Create transaction log
            TransactionLog::create([
                'loan_id' => $loan->id,
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'deficit_created',
                'amount' => $request->amount,
                'status' => 'success',
                'metadata' => [
                    'fee_amount' => $request->fee_amount,
                    'due_date' => $request->due_date,
                ],
            ]);

            DB::commit();
            return new LoanDeficitResource($deficit);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loan-deficits/{id}",
     *     tags={"Loan Deficits"},
     *     summary="Get loan deficit details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan deficit details",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDeficit")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan deficit not found"
     *     )
     * )
     */
    public function show(LoanDeficit $loanDeficit)
    {
        return new LoanDeficitResource($loanDeficit->load(['loan', 'employee']));
    }

    /**
     * @OA\Put(
     *     path="/api/loan-deficits/{id}",
     *     tags={"Loan Deficits"},
     *     summary="Update loan deficit status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "paid", "waived"}),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan deficit updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanDeficit")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, LoanDeficit $loanDeficit)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,waived',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $loanDeficit->status;
            $loanDeficit->update($request->only(['status', 'remarks']));

            // Create transaction log
            TransactionLog::create([
                'loan_id' => $loanDeficit->loan_id,
                'employee_id' => $loanDeficit->employee_id,
                'transaction_type' => 'deficit_status_change',
                'amount' => $loanDeficit->amount,
                'status' => 'success',
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'fee_amount' => $loanDeficit->fee_amount,
                ],
            ]);

            // If all deficits are paid/waived, update loan status
            if ($request->status === 'paid' || $request->status === 'waived') {
                $loan = $loanDeficit->loan;
                $hasPendingDeficits = $loan->deficits()
                    ->where('status', 'pending')
                    ->exists();

                if (!$hasPendingDeficits) {
                    $loan->update(['status' => 'open']);
                }
            }

            DB::commit();
            return new LoanDeficitResource($loanDeficit);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/loan-deficits/{id}",
     *     tags={"Loan Deficits"},
     *     summary="Delete a loan deficit",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan deficit deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan deficit not found"
     *     )
     * )
     */
    public function destroy(LoanDeficit $loanDeficit)
    {
        $loanDeficit->delete();
        return response()->json(['message' => 'Loan deficit deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loan}/deficits",
     *     tags={"Loan Deficits"},
     *     summary="Get all deficits for a specific loan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan deficits",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/LoanDeficit")
     *         )
     *     )
     * )
     */
    public function loanDeficits(Loan $loan)
    {
        $deficits = $loan->deficits()
            ->with(['employee'])
            ->latest()
            ->paginate(10);

        return LoanDeficitResource::collection($deficits);
    }
}
