<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanExcessResource;
use App\Models\Loan;
use App\Models\LoanExcess;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Loan Excess",
 *     description="API Endpoints for managing loan excess payments"
 * )
 */
class LoanExcessController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/loan-excess",
     *     summary="Get all loan excess payments",
     *     tags={"Loan Excess"},
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
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "processed", "refunded"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loan excess payments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LoanExcess")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = LoanExcess::query()
            ->with(['loan', 'employee']);

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return LoanExcessResource::collection($query->paginate(10));
    }

    /**
     * @OA\Post(
     *     path="/api/loan-excess",
     *     summary="Create a new loan excess payment",
     *     tags={"Loan Excess"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"loan_id", "amount", "payment_date"},
     *             @OA\Property(property="loan_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Loan excess payment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanExcess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $loan = Loan::findOrFail($request->loan_id);
            
            $excess = LoanExcess::create([
                'loan_id' => $request->loan_id,
                'employee_id' => $loan->employee_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'status' => 'pending',
                'remarks' => $request->remarks
            ]);

            // Log the transaction
            TransactionLog::create([
                'loan_id' => $request->loan_id,
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'excess_payment',
                'amount' => $request->amount,
                'status' => 'completed',
                'remarks' => 'Excess payment recorded'
            ]);

            DB::commit();

            return new LoanExcessResource($excess->load(['loan', 'employee']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating excess payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loan-excess/{id}",
     *     summary="Get loan excess payment details",
     *     tags={"Loan Excess"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan excess payment details",
     *         @OA\JsonContent(ref="#/components/schemas/LoanExcess")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan excess payment not found"
     *     )
     * )
     */
    public function show($id)
    {
        $excess = LoanExcess::with(['loan', 'employee'])->findOrFail($id);
        return new LoanExcessResource($excess);
    }

    /**
     * @OA\Put(
     *     path="/api/loan-excess/{id}",
     *     summary="Update loan excess payment status",
     *     tags={"Loan Excess"},
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
     *             @OA\Property(property="status", type="string", enum={"pending", "processed", "refunded"}),
     *             @OA\Property(property="remarks", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan excess payment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/LoanExcess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processed,refunded',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $excess = LoanExcess::findOrFail($id);
            $oldStatus = $excess->status;
            $excess->update([
                'status' => $request->status,
                'remarks' => $request->remarks
            ]);

            // Log the status change
            TransactionLog::create([
                'loan_id' => $excess->loan_id,
                'employee_id' => $excess->employee_id,
                'transaction_type' => 'excess_status_update',
                'amount' => $excess->amount,
                'status' => 'completed',
                'remarks' => "Excess payment status changed from {$oldStatus} to {$request->status}"
            ]);

            DB::commit();

            return new LoanExcessResource($excess->load(['loan', 'employee']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating excess payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/loan-excess/{id}",
     *     summary="Delete a loan excess payment",
     *     tags={"Loan Excess"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan excess payment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan excess payment not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $excess = LoanExcess::findOrFail($id);
        $excess->delete();
        return response()->json(['message' => 'Loan excess payment deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loanId}/excess",
     *     summary="Get all excess payments for a specific loan",
     *     tags={"Loan Excess"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loanId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of excess payments for the loan",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LoanExcess")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function loanExcess($loanId)
    {
        $excess = LoanExcess::with(['loan', 'employee'])
            ->where('loan_id', $loanId)
            ->paginate(10);

        return LoanExcessResource::collection($excess);
    }
}
