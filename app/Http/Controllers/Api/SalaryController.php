<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalaryResource;
use App\Models\Salary;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Salaries",
 *     description="API Endpoints for managing employee salaries"
 * )
 */
class SalaryController extends Controller
{
    /**
     * Display a listing of the salaries.
     *
     * @OA\Get(
     *     path="/api/salaries",
     *     tags={"Salaries"},
     *     summary="Get all salaries",
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "paid", "failed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of salaries",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SalaryResource"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Salary::query()
            ->with(['employee', 'organization']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $salaries = $query->paginate(10);

        return SalaryResource::collection($salaries);
    }

    /**
     * Store a newly created salary in storage.
     *
     * @OA\Post(
     *     path="/api/salaries",
     *     tags={"Salaries"},
     *     summary="Create a new salary record",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id", "organization_id", "month", "year", "basic_salary"},
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="month", type="integer"),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="basic_salary", type="number", format="float"),
     *             @OA\Property(property="allowances", type="number", format="float"),
     *             @OA\Property(property="deductions", type="number", format="float"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Salary created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SalaryResource")
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
            'employee_id' => 'required|exists:employees,id',
            'organization_id' => 'required|exists:organizations,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate salary record
        $exists = Salary::where('employee_id', $request->employee_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A salary record already exists for this employee for the specified month and year'
            ], 422);
        }

        $data = $validator->validated();
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        $data['status'] = 'pending';

        $salary = DB::transaction(function () use ($data) {
            $salary = Salary::create($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => Salary::class,
                'model_id' => $salary->id,
                'description' => 'Created salary record',
                'old_values' => null,
                'new_values' => $salary->toArray(),
            ]);

            return $salary;
        });

        return new SalaryResource($salary);
    }

    /**
     * Display the specified salary.
     *
     * @OA\Get(
     *     path="/api/salaries/{salary}",
     *     tags={"Salaries"},
     *     summary="Get salary details",
     *     @OA\Parameter(
     *         name="salary",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary details",
     *         @OA\JsonContent(ref="#/components/schemas/SalaryResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Salary not found"
     *     )
     * )
     */
    public function show(Salary $salary)
    {
        $salary->load(['employee', 'organization']);
        return new SalaryResource($salary);
    }

    /**
     * Update the specified salary in storage.
     *
     * @OA\Put(
     *     path="/api/salaries/{salary}",
     *     tags={"Salaries"},
     *     summary="Update salary details",
     *     @OA\Parameter(
     *         name="salary",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="basic_salary", type="number", format="float"),
     *             @OA\Property(property="allowances", type="number", format="float"),
     *             @OA\Property(property="deductions", type="number", format="float"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string"),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "paid", "failed"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SalaryResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Salary not found"
     *     )
     * )
     */
    public function update(Request $request, Salary $salary)
    {
        $validator = Validator::make($request->all(), [
            'basic_salary' => 'sometimes|required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'status' => 'sometimes|required|in:pending,paid,failed',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $salary->toArray();
        $data = $validator->validated();

        // Recalculate net salary if any of the components are updated
        if (isset($data['basic_salary']) || isset($data['allowances']) || isset($data['deductions'])) {
            $data['basic_salary'] = $data['basic_salary'] ?? $salary->basic_salary;
            $data['allowances'] = $data['allowances'] ?? $salary->allowances;
            $data['deductions'] = $data['deductions'] ?? $salary->deductions;
            $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        }

        $salary = DB::transaction(function () use ($salary, $data, $oldValues) {
            $salary->update($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => Salary::class,
                'model_id' => $salary->id,
                'description' => 'Updated salary record',
                'old_values' => $oldValues,
                'new_values' => $salary->toArray(),
            ]);

            return $salary;
        });

        return new SalaryResource($salary);
    }

    /**
     * Remove the specified salary from storage.
     *
     * @OA\Delete(
     *     path="/api/salaries/{salary}",
     *     tags={"Salaries"},
     *     summary="Delete a salary record",
     *     @OA\Parameter(
     *         name="salary",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Salary not found"
     *     )
     * )
     */
    public function destroy(Salary $salary)
    {
        $oldValues = $salary->toArray();

        DB::transaction(function () use ($salary, $oldValues) {
            $salary->delete();

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => Salary::class,
                'model_id' => $salary->id,
                'description' => 'Deleted salary record',
                'old_values' => $oldValues,
                'new_values' => null,
            ]);
        });

        return response()->json(['message' => 'Salary deleted successfully']);
    }

    /**
     * Get salary summary statistics.
     *
     * @OA\Get(
     *     path="/api/salaries/summary",
     *     tags={"Salaries"},
     *     summary="Get salary summary statistics",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary summary statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_salaries", type="integer"),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="average_salary", type="number", format="float"),
     *             @OA\Property(property="status_counts", type="object",
     *                 @OA\Property(property="pending", type="integer"),
     *                 @OA\Property(property="paid", type="integer"),
     *                 @OA\Property(property="failed", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function summary(Request $request)
    {
        $query = Salary::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $totalSalaries = $query->count();
        $totalAmount = $query->sum('net_salary');
        $averageSalary = $totalSalaries > 0 ? $totalAmount / $totalSalaries : 0;

        $statusCounts = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'total_salaries' => $totalSalaries,
            'total_amount' => $totalAmount,
            'average_salary' => $averageSalary,
            'status_counts' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'paid' => $statusCounts['paid'] ?? 0,
                'failed' => $statusCounts['failed'] ?? 0,
            ],
        ]);
    }
} 