<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\EmployeeRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="API Endpoints for employee management"
 * )
 */
class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/employees",
     *     summary="Get all employees",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="department",
     *         in="query",
     *         description="Filter by department",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="designation",
     *         in="query",
     *         description="Filter by designation",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of employees",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Employee")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('designation')) {
            $query->where('designation', $request->designation);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->with(['organization', 'user', 'loans'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return EmployeeResource::collection($employees);
    }

    /**
     * @OA\Post(
     *     path="/api/employees",
     *     summary="Create a new employee",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Employee created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     )
     * )
     */
    public function store(EmployeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $employee = Employee::create($data);

            // Log the employee creation
            TransactionLog::create([
                'employee_id' => $employee->id,
                'transaction_type' => 'employee_created',
                'status' => 'completed',
                'remarks' => "Employee {$employee->full_name} created"
            ]);

            DB::commit();

            return new EmployeeResource($employee->load(['organization', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/employees/{id}",
     *     summary="Get employee details",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee details",
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     )
     * )
     */
    public function show(Employee $employee)
    {
        return new EmployeeResource($employee->load(['organization', 'user', 'loans']));
    }

    /**
     * @OA\Put(
     *     path="/api/employees/{id}",
     *     summary="Update employee details",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Employee")
     *     )
     * )
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $employee->update($data);

            // Log the update
            TransactionLog::create([
                'employee_id' => $employee->id,
                'transaction_type' => 'employee_updated',
                'status' => 'completed',
                'remarks' => "Employee {$employee->full_name} updated"
            ]);

            DB::commit();

            return new EmployeeResource($employee->load(['organization', 'user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/employees/{id}",
     *     summary="Delete an employee",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee deleted successfully"
     *     )
     * )
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Check if employee has active loans
            if ($employee->loans()->where('status', 'active')->exists()) {
                return response()->json([
                    'message' => 'Cannot delete employee with active loans'
                ], 422);
            }

            // Log the deletion
            TransactionLog::create([
                'employee_id' => $employee->id,
                'transaction_type' => 'employee_deleted',
                'status' => 'completed',
                'remarks' => "Employee {$employee->full_name} deleted"
            ]);

            $employee->delete();

            DB::commit();

            return response()->json(['message' => 'Employee deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/employees/statistics",
     *     summary="Get employee statistics",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Employee statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_employees", type="integer"),
     *             @OA\Property(property="active_employees", type="integer"),
     *             @OA\Property(property="department_distribution", type="object"),
     *             @OA\Property(property="designation_distribution", type="object"),
     *             @OA\Property(property="employment_type_distribution", type="object"),
     *             @OA\Property(property="monthly_joining_trend", type="array")
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        $query = Employee::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $stats = [
            'total_employees' => $query->count(),
            'active_employees' => $query->where('status', 'active')->count(),
            'department_distribution' => $query->select('department', DB::raw('count(*) as count'))
                ->groupBy('department')
                ->get(),
            'designation_distribution' => $query->select('designation', DB::raw('count(*) as count'))
                ->groupBy('designation')
                ->get(),
            'employment_type_distribution' => $query->select('employment_type', DB::raw('count(*) as count'))
                ->groupBy('employment_type')
                ->get(),
            'monthly_joining_trend' => $query->select(
                DB::raw('YEAR(date_of_joining) as year'),
                DB::raw('MONTH(date_of_joining) as month'),
                DB::raw('count(*) as count')
            )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
        ];

        return response()->json($stats);
    }

    public function export(Request $request)
    {
        $query = Employee::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $employees = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees.csv"',
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'ID',
                'Employee ID',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Address',
                'City',
                'State',
                'Country',
                'Postal Code',
                'Date of Birth',
                'Date of Joining',
                'Designation',
                'Department',
                'Salary',
                'Status',
                'Employment Type',
                'Bank Name',
                'Bank Account Number',
                'Bank IFSC Code',
                'Emergency Contact Name',
                'Emergency Contact Phone',
                'Emergency Contact Relationship'
            ]);

            // Add data
            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->id,
                    $employee->employee_id,
                    $employee->first_name,
                    $employee->last_name,
                    $employee->email,
                    $employee->phone,
                    $employee->address,
                    $employee->city,
                    $employee->state,
                    $employee->country,
                    $employee->postal_code,
                    $employee->date_of_birth,
                    $employee->date_of_joining,
                    $employee->designation,
                    $employee->department,
                    $employee->salary,
                    $employee->status,
                    $employee->employment_type,
                    $employee->bank_name,
                    $employee->bank_account_number,
                    $employee->bank_ifsc_code,
                    $employee->emergency_contact_name,
                    $employee->emergency_contact_phone,
                    $employee->emergency_contact_relationship
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function uploadProfilePhoto(UploadedFile $file)
    {
        $path = $file->store('employee-photos', 'public');
        return $path;
    }
} 