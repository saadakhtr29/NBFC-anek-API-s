<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Http\Resources\LoanRepaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RepaymentController extends Controller
{
    /**
     * Display a listing of the repayments.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        \Log::info('Authenticated user in RepaymentController@index:', ['user' => auth()->user()]);
        $repayments = LoanRepayment::with(['loan'])->paginate(10);
        return LoanRepaymentResource::collection($repayments);
    }

    /**
     * Store a newly created repayment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);

        // Check for rejected and completed first
        if ($loan->status === 'rejected') {
            return response()->json([
                'message' => 'Cannot create repayment for rejected loan'
            ], 400);
        }
        if ($loan->status === 'completed') {
            return response()->json([
                'message' => 'Cannot create repayment for completed loan'
            ], 400);
        }
        if ($loan->status !== 'disbursed') {
            return response()->json([
                'message' => 'Cannot create repayment for non-disbursed loan'
            ], 400);
        }

        // Calculate interest and principal (for now, assume all is principal)
        $principal = $request->amount;
        $interest = 0;

        $repayment = LoanRepayment::create([
            'loan_id' => $request->loan_id,
            'employee_id' => $loan->employee_id,
            'amount' => $request->amount,
            'principal_amount' => $principal,
            'interest_amount' => $interest,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'remarks' => $request->remarks
        ]);

        return (new LoanRepaymentResource($repayment->load('loan')))->response()->setStatusCode(201);
    }

    /**
     * Display the specified repayment.
     *
     * @param  int  $id
     * @return \App\Http\Resources\LoanRepaymentResource
     */
    public function show($id)
    {
        $repayment = LoanRepayment::with(['loan'])->findOrFail($id);
        return new LoanRepaymentResource($repayment);
    }

    /**
     * Approve the specified repayment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        $repayment = LoanRepayment::findOrFail($id);

        if ($repayment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve pending repayments'
            ], 400);
        }

        DB::transaction(function () use ($repayment) {
            $repayment->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now()
            ]);

            $loan = $repayment->loan;
            $totalPaid = $loan->repayments()
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalPaid >= $loan->amount) {
                $loan->update(['status' => 'completed']);
            }
        });

        return response()->json([
            'message' => 'Repayment approved successfully'
        ]);
    }

    /**
     * Reject the specified repayment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $repayment = LoanRepayment::findOrFail($id);

        if ($repayment->status !== 'pending') {
            return response()->json([
                'message' => 'Can only reject pending repayments'
            ], 400);
        }

        $repayment->update([
            'status' => 'failed',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason
        ]);

        return response()->json([
            'message' => 'Repayment rejected successfully'
        ]);
    }

    /**
     * Get repayment statistics
     *
     * @OA\Get(
     *     path="/api/repayments/statistics",
     *     summary="Get repayment statistics",
     *     tags={"Repayments"},
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repayment statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_repayments", type="integer"),
     *             @OA\Property(property="total_amount", type="number"),
     *             @OA\Property(property="status_distribution", type="object"),
     *             @OA\Property(property="payment_method_distribution", type="object"),
     *             @OA\Property(property="monthly_trends", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $query = LoanRepayment::query();

        if ($request->has('organization_id')) {
            $query->whereHas('loan', function ($q) use ($request) {
                $q->where('organization_id', $request->organization_id);
            });
        }

        $totalRepayments = $query->count();
        $totalAmount = $query->where('status', 'completed')->sum('amount');

        $statusDistribution = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $paymentMethodDistribution = $query->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method')
            ->toArray();

        $monthlyTrends = $query->select(
            DB::raw('strftime("%Y-%m", payment_date) as month'),
            DB::raw('count(*) as count'),
            DB::raw('sum(amount) as total_amount')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'count' => $item->count,
                    'total_amount' => $item->total_amount
                ];
            });

        return response()->json([
            'total_repayments' => $totalRepayments,
            'total_amount' => $totalAmount,
            'status_distribution' => $statusDistribution,
            'payment_method_distribution' => $paymentMethodDistribution,
            'monthly_trends' => $monthlyTrends
        ]);
    }
} 