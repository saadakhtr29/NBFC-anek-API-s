<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionLogResource;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Transaction Logs",
 *     description="API Endpoints for managing transaction logs"
 * )
 */
class TransactionLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/transaction-logs",
     *     summary="Get all transaction logs",
     *     tags={"Transaction Logs"},
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
     *         name="transaction_type",
     *         in="query",
     *         description="Filter by transaction type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="List of transaction logs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionLog")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = TransactionLog::query()
            ->with(['loan', 'employee']);

        if ($request->has('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return TransactionLogResource::collection($query->latest()->paginate(20));
    }

    /**
     * @OA\Get(
     *     path="/api/transaction-logs/{id}",
     *     summary="Get transaction log details",
     *     tags={"Transaction Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction log details",
     *         @OA\JsonContent(ref="#/components/schemas/TransactionLog")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction log not found"
     *     )
     * )
     */
    public function show($id)
    {
        $log = TransactionLog::with(['loan', 'employee'])->findOrFail($id);
        return new TransactionLogResource($log);
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{loanId}/transactions",
     *     summary="Get all transactions for a specific loan",
     *     tags={"Transaction Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="loanId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of transactions for the loan",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionLog")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function loanTransactions($loanId)
    {
        $logs = TransactionLog::with(['loan', 'employee'])
            ->where('loan_id', $loanId)
            ->latest()
            ->paginate(20);

        return TransactionLogResource::collection($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/employees/{employeeId}/transactions",
     *     summary="Get all transactions for a specific employee",
     *     tags={"Transaction Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employeeId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of transactions for the employee",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransactionLog")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function employeeTransactions($employeeId)
    {
        $logs = TransactionLog::with(['loan', 'employee'])
            ->where('employee_id', $employeeId)
            ->latest()
            ->paginate(20);

        return TransactionLogResource::collection($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/transaction-logs/summary",
     *     summary="Get transaction summary",
     *     tags={"Transaction Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for summary",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for summary",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_transactions", type="integer"),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="by_type", type="object"),
     *             @OA\Property(property="by_status", type="object")
     *         )
     *     )
     * )
     */
    public function summary(Request $request)
    {
        $query = TransactionLog::query();

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $totalTransactions = $query->count();
        $totalAmount = $query->sum('amount');

        $byType = $query->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->get()
            ->pluck('total', 'transaction_type');

        $byStatus = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'by_type' => $byType,
            'by_status' => $byStatus
        ]);
    }
}
