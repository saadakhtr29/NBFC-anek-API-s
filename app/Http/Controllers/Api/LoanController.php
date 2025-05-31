<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Loans",
 *     description="API Endpoints for loan management"
 * )
 */
class LoanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/loans",
     *     summary="List all loans",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by loan status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected", "disbursed", "active", "completed", "defaulted"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by loan type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
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
     *         name="search",
     *         in="query",
     *         description="Search in loan number, purpose, or guarantor name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of loans",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Loan")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        \Log::info('Authenticated user in LoanController@index:', ['user' => auth()->user()]);
        $query = Loan::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('guarantor_name', 'like', "%{$search}%");
            });
        }

        $loans = $query->with(['organization', 'employee', 'approver', 'rejector', 'disburser'])
            ->latest()
            ->paginate();

        return LoanResource::collection($loans);
    }

    /**
     * @OA\Post(
     *     path="/api/loans",
     *     summary="Create a new loan",
     *     tags={"Loans"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Loan created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(LoanRequest $request)
    {
        $data = $request->validated();
        
        if (empty($data['loan_number'])) {
            $data['loan_number'] = 'LOAN' . mt_rand(1000, 9999);
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }

        // Remove documents from $data before create
        if (isset($data['documents'])) {
            unset($data['documents']);
        }

        try {
            DB::beginTransaction();

            $loan = Loan::create($data);

            if ($request->hasFile('documents')) {
                $documents = [];
                foreach ($request->file('documents') as $document) {
                    $path = $document->store('loan-documents');
                    $documents[] = $path;
                }
                $loan->update(['documents' => $documents]);
            }

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan created'
            ]);

            DB::commit();

            return (new LoanResource($loan->load(['organization', 'employee'])))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/loans/{id}",
     *     summary="Get loan details",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan details",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     )
     * )
     */
    public function show(Loan $loan)
    {
        return new LoanResource($loan->load([
            'organization',
            'employee',
            'approver',
            'rejector',
            'disburser',
            'repayments',
            'loanDocuments'
        ]));
    }

    /**
     * @OA\Put(
     *     path="/api/loans/{id}",
     *     summary="Update loan details",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     )
     * )
     */
    public function update(Request $request, Loan $loan)
    {
        $data = $request->all();
        // Remove documents from $data before update
        if (isset($data['documents'])) {
            unset($data['documents']);
        }
        if ($request->hasFile('documents')) {
            // Delete old documents
            if ($loan->documents) {
                foreach ($loan->documents as $document) {
                    \Storage::delete($document);
                }
            }
            // Upload new documents
            $documents = [];
            foreach ($request->file('documents') as $document) {
                $path = $document->store('loan-documents');
                $documents[] = $path;
            }
            $loan->update(['documents' => $documents]);
        }
        try {
            DB::beginTransaction();
            $loan->update($data);
            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan updated'
            ]);
            DB::commit();
            return new LoanResource($loan->load(['organization', 'employee']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/loans/{id}",
     *     summary="Delete a loan",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete loan with active repayments"
     *     )
     * )
     */
    public function destroy(Loan $loan)
    {
        if ($loan->repayments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete loan with active repayments'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Delete associated documents
            if ($loan->documents) {
                foreach ($loan->documents as $document) {
                    Storage::delete($document);
                }
            }

            $loan->delete();

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan deleted'
            ]);

            DB::commit();

            return response()->json(['message' => 'Loan deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/loans/{id}/approve",
     *     summary="Approve a loan",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan approved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Loan cannot be approved"
     *     )
     * )
     */
    public function approve(Loan $loan)
    {
        if ($loan->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending loans can be approved'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $loan->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now()
            ]);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'approve',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan approved'
            ]);

            DB::commit();

            // Return message as expected by tests
            return response()->json(['message' => 'Loan approved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/loans/{id}/reject",
     *     summary="Reject a loan",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="rejection_reason", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan rejected successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Loan cannot be rejected"
     *     )
     * )
     */
    public function reject(Request $request, Loan $loan)
    {
        if ($loan->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending loans can be rejected'
            ], 400);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $loan->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'reject',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan rejected: ' . $request->rejection_reason
            ]);

            DB::commit();

            // Return message as expected by tests
            return response()->json(['message' => 'Loan rejected successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/loans/{id}/disburse",
     *     summary="Disburse a loan",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="disbursement_method", type="string"),
     *             @OA\Property(property="disbursement_details", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan disbursed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Loan")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Loan cannot be disbursed"
     *     )
     * )
     */
    public function disburse(Request $request, Loan $loan)
    {
        if ($loan->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved loans can be disbursed'
            ], 400);
        }

        $request->validate([
            'disbursement_method' => 'required|string|max:50',
            'disbursement_details' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $loan->update([
                'status' => 'disbursed',
                'disbursed_by' => auth()->id(),
                'disbursed_at' => now(),
                'disbursement_method' => $request->disbursement_method,
                'disbursement_details' => $request->disbursement_details
            ]);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'disburse',
                'model_type' => Loan::class,
                'model_id' => $loan->id,
                'details' => 'Loan disbursed via ' . $request->disbursement_method
            ]);

            DB::commit();

            // Return message as expected by tests
            return response()->json(['message' => 'Loan disbursed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get loan statistics
     *
     * @OA\Get(
     *     path="/api/loans/statistics",
     *     summary="Get loan statistics",
     *     tags={"Loans"},
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_loans", type="integer"),
     *             @OA\Property(property="total_amount", type="number"),
     *             @OA\Property(property="total_interest", type="number"),
     *             @OA\Property(property="total_repaid", type="number"),
     *             @OA\Property(property="total_outstanding", type="number"),
     *             @OA\Property(property="status_distribution", type="object"),
     *             @OA\Property(property="type_distribution", type="object"),
     *             @OA\Property(property="monthly_trends", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $query = Loan::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $totalLoans = $query->count();
        $totalAmount = $query->sum('amount');
        $totalInterest = $query->sum('interest_amount');
        $totalRepaid = $query->whereHas('repayments', function ($q) {
            $q->where('status', 'completed');
        })->sum('amount');
        $totalOutstanding = $totalAmount - $totalRepaid;

        $statusDistribution = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $typeDistribution = $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $monthlyTrends = $query->select(
            DB::raw('strftime("%Y-%m", created_at) as month'),
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
            'total_loans' => $totalLoans,
            'total_amount' => $totalAmount,
            'total_interest' => $totalInterest,
            'total_repaid' => $totalRepaid,
            'total_outstanding' => $totalOutstanding,
            'status_distribution' => $statusDistribution,
            'type_distribution' => $typeDistribution,
            'monthly_trends' => $monthlyTrends
        ]);
    }
}
