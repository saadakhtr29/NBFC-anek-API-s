<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Attendance;
use App\Models\Salary;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Writer;

/**
 * @OA\Tag(
 *     name="Bulk Upload",
 *     description="API Endpoints for bulk data upload"
 * )
 */
class BulkUploadController extends Controller
{
    /**
     * Upload employees in bulk.
     *
     * @OA\Post(
     *     path="/api/bulk-upload/employees",
     *     tags={"Bulk Upload"},
     *     summary="Upload multiple employees via CSV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employees uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="success", type="integer"),
     *             @OA\Property(property="failed", type="integer"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function uploadEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $csv = Reader::createFromPath($request->file('file')->getPathname());
        $csv->setHeaderOffset(0);

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($csv as $record) {
                $total++;
                try {
                    $validator = Validator::make($record, [
                        'organization_id' => 'required|exists:organizations,id',
                        'name' => 'required|string|max:255',
                        'email' => 'required|email|unique:employees,email',
                        'phone' => 'required|string|max:20',
                        'address' => 'required|string',
                        'position' => 'required|string|max:255',
                        'department' => 'required|string|max:255',
                        'joining_date' => 'required|date',
                        'salary' => 'required|numeric|min:0',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row {$total}: " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    $employee = Employee::create($record);
                    $success++;

                    TransactionLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'create',
                        'model_type' => Employee::class,
                        'model_id' => $employee->id,
                        'description' => 'Created employee via bulk upload',
                        'old_values' => null,
                        'new_values' => $employee->toArray(),
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$total}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Upload completed',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * Upload loans in bulk.
     *
     * @OA\Post(
     *     path="/api/bulk-upload/loans",
     *     tags={"Bulk Upload"},
     *     summary="Upload multiple loans via CSV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loans uploaded successfully"
     *     )
     * )
     */
    public function uploadLoans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $csv = Reader::createFromPath($request->file('file')->getPathname());
        $csv->setHeaderOffset(0);

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($csv as $record) {
                $total++;
                try {
                    $validator = Validator::make($record, [
                        'organization_id' => 'required|exists:organizations,id',
                        'employee_id' => 'required|exists:employees,id',
                        'amount' => 'required|numeric|min:0',
                        'interest_rate' => 'required|numeric|min:0',
                        'term_months' => 'required|integer|min:1',
                        'start_date' => 'required|date',
                        'purpose' => 'required|string',
                        'status' => 'required|in:pending,approved,rejected,active,completed',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row {$total}: " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    $loan = Loan::create($record);
                    $success++;

                    TransactionLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'create',
                        'model_type' => Loan::class,
                        'model_id' => $loan->id,
                        'description' => 'Created loan via bulk upload',
                        'old_values' => null,
                        'new_values' => $loan->toArray(),
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$total}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Upload completed',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * Upload attendance records in bulk.
     *
     * @OA\Post(
     *     path="/api/bulk-upload/attendance",
     *     tags={"Bulk Upload"},
     *     summary="Upload multiple attendance records via CSV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance records uploaded successfully"
     *     )
     * )
     */
    public function uploadAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $csv = Reader::createFromPath($request->file('file')->getPathname());
        $csv->setHeaderOffset(0);

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($csv as $record) {
                $total++;
                try {
                    $validator = Validator::make($record, [
                        'employee_id' => 'required|exists:employees,id',
                        'organization_id' => 'required|exists:organizations,id',
                        'date' => 'required|date',
                        'check_in' => 'required|date_format:H:i:s',
                        'check_out' => 'required|date_format:H:i:s',
                        'status' => 'required|in:present,absent,late,half_day,leave',
                        'notes' => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row {$total}: " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    // Calculate work hours and overtime
                    $checkIn = \DateTime::createFromFormat('H:i:s', $record['check_in']);
                    $checkOut = \DateTime::createFromFormat('H:i:s', $record['check_out']);
                    $workHours = round(($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600, 2);
                    $overtimeHours = max(0, $workHours - 8);

                    $record['work_hours'] = $workHours;
                    $record['overtime_hours'] = $overtimeHours;
                    $record['verified_by'] = auth()->id();
                    $record['verified_at'] = now();

                    $attendance = Attendance::create($record);
                    $success++;

                    TransactionLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'create',
                        'model_type' => Attendance::class,
                        'model_id' => $attendance->id,
                        'description' => 'Created attendance record via bulk upload',
                        'old_values' => null,
                        'new_values' => $attendance->toArray(),
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$total}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Upload completed',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * Upload salary records in bulk.
     *
     * @OA\Post(
     *     path="/api/bulk-upload/salaries",
     *     tags={"Bulk Upload"},
     *     summary="Upload multiple salary records via CSV",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary records uploaded successfully"
     *     )
     * )
     */
    public function uploadSalaries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $csv = Reader::createFromPath($request->file('file')->getPathname());
        $csv->setHeaderOffset(0);

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($csv as $record) {
                $total++;
                try {
                    $validator = Validator::make($record, [
                        'employee_id' => 'required|exists:employees,id',
                        'organization_id' => 'required|exists:organizations,id',
                        'month' => 'required|integer|between:1,12',
                        'year' => 'required|integer|min:2000',
                        'basic_salary' => 'required|numeric|min:0',
                        'allowances' => 'required|numeric|min:0',
                        'deductions' => 'required|numeric|min:0',
                        'payment_date' => 'required|date',
                        'payment_method' => 'required|string',
                        'reference_number' => 'required|string',
                        'status' => 'required|in:pending,paid,failed',
                        'notes' => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row {$total}: " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    // Calculate net salary
                    $record['net_salary'] = $record['basic_salary'] + $record['allowances'] - $record['deductions'];

                    $salary = Salary::create($record);
                    $success++;

                    TransactionLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'create',
                        'model_type' => Salary::class,
                        'model_id' => $salary->id,
                        'description' => 'Created salary record via bulk upload',
                        'old_values' => null,
                        'new_values' => $salary->toArray(),
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$total}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Upload completed',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * Download template for bulk upload.
     *
     * @OA\Get(
     *     path="/api/bulk-upload/template/{type}",
     *     tags={"Bulk Upload"},
     *     summary="Download CSV template for bulk upload",
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", enum={"employees", "loans", "attendance", "salaries"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV template downloaded successfully"
     *     )
     * )
     */
    public function downloadTemplate($type)
    {
        $headers = [];
        $filename = '';

        switch ($type) {
            case 'employees':
                $headers = [
                    'organization_id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'position',
                    'department',
                    'joining_date',
                    'salary'
                ];
                $filename = 'employees_template.csv';
                break;

            case 'loans':
                $headers = [
                    'organization_id',
                    'employee_id',
                    'amount',
                    'interest_rate',
                    'term_months',
                    'start_date',
                    'purpose',
                    'status'
                ];
                $filename = 'loans_template.csv';
                break;

            case 'attendance':
                $headers = [
                    'employee_id',
                    'organization_id',
                    'date',
                    'check_in',
                    'check_out',
                    'status',
                    'notes'
                ];
                $filename = 'attendance_template.csv';
                break;

            case 'salaries':
                $headers = [
                    'employee_id',
                    'organization_id',
                    'month',
                    'year',
                    'basic_salary',
                    'allowances',
                    'deductions',
                    'payment_date',
                    'payment_method',
                    'reference_number',
                    'status',
                    'notes'
                ];
                $filename = 'salaries_template.csv';
                break;

            default:
                return response()->json(['error' => 'Invalid template type'], 400);
        }

        $csv = Writer::createFromString('');
        $csv->insertOne($headers);

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
} 