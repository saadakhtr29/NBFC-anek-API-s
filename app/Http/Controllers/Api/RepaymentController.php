<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RepaymentResource;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Repayments",
 *     description="API Endpoints for loan repayments"
 * )
 */
class RepaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/repayments",
     *     summary="Get all repayments with optional filters",
     *     tags={"Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="query",
     *         description="Filter by loan ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "completed", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter by start date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter by end date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of repayments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RepaymentResource"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Repayment::with(['loan', 'loan.employee']);

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('employee_id')) {
            $query->whereHas('loan', function($q) use ($request) {
                $q->where('employee_id', $request->employee_id);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('payment_date', '<=', $request->end_date);
        }

        $repayments = $query->paginate(10);
        return RepaymentResource::collection($repayments);
    }

    /**
     * @OA\Post(
     *     path="/api/repayments",
     *     summary="Create a new repayment",
     *     tags={"Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"loan_id", "amount", "payment_date"},
     *             @OA\Property(property="loan_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string"),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Repayment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/RepaymentResource")
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
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($request->loan_id);
            
            // Check if loan is in a valid state for repayment
            if (!in_array($loan->status, ['open', 'overdue'])) {
                return response()->json([
                    'message' => 'Cannot create repayment for loan with status: ' . $loan->status
                ], 422);
            }

            $repayment = Repayment::create([
                'loan_id' => $request->loan_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            TransactionLog::create([
                'employee_id' => $loan->employee_id,
                'transaction_type' => 'repayment_created',
                'amount' => $request->amount,
                'description' => 'New repayment created'
            ]);

            DB::commit();
            return new RepaymentResource($repayment);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/repayments/{id}",
     *     summary="Get repayment details",
     *     tags={"Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repayment details",
     *         @OA\JsonContent(ref="#/components/schemas/RepaymentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Repayment not found"
     *     )
     * )
     */
    public function show($id)
    {
        $repayment = Repayment::with(['loan', 'loan.employee'])->findOrFail($id);
        return new RepaymentResource($repayment);
    }

    /**
     * @OA\Put(
     *     path="/api/repayments/{id}",
     *     summary="Update repayment details",
     *     tags={"Repayments"},
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
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string"),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repayment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/RepaymentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Repayment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $repayment = Repayment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,completed,failed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $repayment->status;
            $repayment->update($request->all());

            if ($request->has('status') && $oldStatus !== $request->status) {
                TransactionLog::create([
                    'employee_id' => $repayment->loan->employee_id,
                    'transaction_type' => 'repayment_status_changed',
                    'amount' => $repayment->amount,
                    'description' => "Repayment status changed from {$oldStatus} to {$request->status}"
                ]);
            }

            DB::commit();
            return new RepaymentResource($repayment);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/repayments/{id}",
     *     summary="Delete a repayment",
     *     tags={"Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repayment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Repayment not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $repayment = Repayment::findOrFail($id);

        DB::beginTransaction();
        try {
            TransactionLog::create([
                'employee_id' => $repayment->loan->employee_id,
                'transaction_type' => 'repayment_deleted',
                'amount' => $repayment->amount,
                'description' => 'Repayment deleted'
            ]);

            $repayment->delete();
            DB::commit();

            return response()->json(['message' => 'Repayment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting repayment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/repayments/summary",
     *     summary="Get repayment summary statistics",
     *     tags={"Repayments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loan_id",
     *         in="query",
     *         description="Filter by loan ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter by start date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter by end date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repayment summary statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_repayments", type="integer"),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="total_pending", type="number", format="float"),
     *             @OA\Property(property="total_completed", type="number", format="float"),
     *             @OA\Property(property="total_failed", type="number", format="float"),
     *             @OA\Property(
     *                 property="status_counts",
     *                 type="object",
     *                 @OA\Property(property="pending", type="integer"),
     *                 @OA\Property(property="completed", type="integer"),
     *                 @OA\Property(property="failed", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function summary(Request $request)
    {
        $query = Repayment::query();

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('employee_id')) {
            $query->whereHas('loan', function($q) use ($request) {
                $q->where('employee_id', $request->employee_id);
            });
        }

        if ($request->has('start_date')) {
            $query->where('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('payment_date', '<=', $request->end_date);
        }

        $totalRepayments = $query->count();
        $totalAmount = $query->sum('amount');
        $totalPending = $query->where('status', 'pending')->sum('amount');
        $totalCompleted = $query->where('status', 'completed')->sum('amount');
        $totalFailed = $query->where('status', 'failed')->sum('amount');

        $statusCounts = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'total_repayments' => $totalRepayments,
            'total_amount' => $totalAmount,
            'total_pending' => $totalPending,
            'total_completed' => $totalCompleted,
            'total_failed' => $totalFailed,
            'status_counts' => $statusCounts
        ]);
    }
} 