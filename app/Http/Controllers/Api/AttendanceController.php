<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="API Endpoints for managing employee attendance"
 * )
 */
class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendance records.
     *
     * @OA\Get(
     *     path="/api/attendance",
     *     tags={"Attendance"},
     *     summary="Get all attendance records",
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
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"present", "absent", "late", "half_day", "leave"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of attendance records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AttendanceResource"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Attendance::query()
            ->with(['employee', 'organization', 'verifiedBy']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attendance = $query->paginate(10);

        return AttendanceResource::collection($attendance);
    }

    /**
     * Store a newly created attendance record.
     *
     * @OA\Post(
     *     path="/api/attendance",
     *     tags={"Attendance"},
     *     summary="Create a new attendance record",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id", "organization_id", "date"},
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="check_in", type="string", format="date-time"),
     *             @OA\Property(property="check_out", type="string", format="date-time"),
     *             @OA\Property(property="status", type="string", enum={"present", "absent", "late", "half_day", "leave"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attendance record created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AttendanceResource")
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
            'date' => 'required|date',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date|after:check_in',
            'status' => 'required|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate attendance record
        $exists = Attendance::where('employee_id', $request->employee_id)
            ->where('date', $request->date)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'An attendance record already exists for this employee on the specified date'
            ], 422);
        }

        $data = $validator->validated();

        // Calculate work hours if check-in and check-out are provided
        if ($data['check_in'] && $data['check_out']) {
            $checkIn = \Carbon\Carbon::parse($data['check_in']);
            $checkOut = \Carbon\Carbon::parse($data['check_out']);
            $data['work_hours'] = $checkOut->diffInHours($checkIn);
            
            // Calculate overtime (assuming 8 hours is regular work day)
            $data['overtime_hours'] = max(0, $data['work_hours'] - 8);
        }

        $attendance = DB::transaction(function () use ($data) {
            $attendance = Attendance::create($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => Attendance::class,
                'model_id' => $attendance->id,
                'description' => 'Created attendance record',
                'old_values' => null,
                'new_values' => $attendance->toArray(),
            ]);

            return $attendance;
        });

        return new AttendanceResource($attendance);
    }

    /**
     * Display the specified attendance record.
     *
     * @OA\Get(
     *     path="/api/attendance/{attendance}",
     *     tags={"Attendance"},
     *     summary="Get attendance details",
     *     @OA\Parameter(
     *         name="attendance",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance details",
     *         @OA\JsonContent(ref="#/components/schemas/AttendanceResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attendance record not found"
     *     )
     * )
     */
    public function show(Attendance $attendance)
    {
        $attendance->load(['employee', 'organization', 'verifiedBy']);
        return new AttendanceResource($attendance);
    }

    /**
     * Update the specified attendance record.
     *
     * @OA\Put(
     *     path="/api/attendance/{attendance}",
     *     tags={"Attendance"},
     *     summary="Update attendance details",
     *     @OA\Parameter(
     *         name="attendance",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="check_in", type="string", format="date-time"),
     *             @OA\Property(property="check_out", type="string", format="date-time"),
     *             @OA\Property(property="status", type="string", enum={"present", "absent", "late", "half_day", "leave"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AttendanceResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attendance record not found"
     *     )
     * )
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date|after:check_in',
            'status' => 'sometimes|required|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $attendance->toArray();
        $data = $validator->validated();

        // Recalculate work hours if check-in or check-out is updated
        if (isset($data['check_in']) || isset($data['check_out'])) {
            $checkIn = \Carbon\Carbon::parse($data['check_in'] ?? $attendance->check_in);
            $checkOut = \Carbon\Carbon::parse($data['check_out'] ?? $attendance->check_out);
            $data['work_hours'] = $checkOut->diffInHours($checkIn);
            $data['overtime_hours'] = max(0, $data['work_hours'] - 8);
        }

        $attendance = DB::transaction(function () use ($attendance, $data, $oldValues) {
            $attendance->update($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => Attendance::class,
                'model_id' => $attendance->id,
                'description' => 'Updated attendance record',
                'old_values' => $oldValues,
                'new_values' => $attendance->toArray(),
            ]);

            return $attendance;
        });

        return new AttendanceResource($attendance);
    }

    /**
     * Remove the specified attendance record.
     *
     * @OA\Delete(
     *     path="/api/attendance/{attendance}",
     *     tags={"Attendance"},
     *     summary="Delete an attendance record",
     *     @OA\Parameter(
     *         name="attendance",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance record deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attendance record not found"
     *     )
     * )
     */
    public function destroy(Attendance $attendance)
    {
        $oldValues = $attendance->toArray();

        DB::transaction(function () use ($attendance, $oldValues) {
            $attendance->delete();

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => Attendance::class,
                'model_id' => $attendance->id,
                'description' => 'Deleted attendance record',
                'old_values' => $oldValues,
                'new_values' => null,
            ]);
        });

        return response()->json(['message' => 'Attendance record deleted successfully']);
    }

    /**
     * Verify an attendance record.
     *
     * @OA\Post(
     *     path="/api/attendance/{attendance}/verify",
     *     tags={"Attendance"},
     *     summary="Verify an attendance record",
     *     @OA\Parameter(
     *         name="attendance",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance record verified successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AttendanceResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attendance record not found"
     *     )
     * )
     */
    public function verify(Request $request, Attendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $attendance->toArray();
        $data = [
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'notes' => $request->notes,
        ];

        $attendance = DB::transaction(function () use ($attendance, $data, $oldValues) {
            $attendance->update($data);

            TransactionLog::create([
                'user_id' => auth()->id(),
                'action' => 'verify',
                'model_type' => Attendance::class,
                'model_id' => $attendance->id,
                'description' => 'Verified attendance record',
                'old_values' => $oldValues,
                'new_values' => $attendance->toArray(),
            ]);

            return $attendance;
        });

        return new AttendanceResource($attendance);
    }

    /**
     * Get attendance summary statistics.
     *
     * @OA\Get(
     *     path="/api/attendance/summary",
     *     tags={"Attendance"},
     *     summary="Get attendance summary statistics",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         description="Filter by organization ID",
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
     *         description="Attendance summary statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_records", type="integer"),
     *             @OA\Property(property="total_work_hours", type="number", format="float"),
     *             @OA\Property(property="total_overtime_hours", type="number", format="float"),
     *             @OA\Property(property="status_counts", type="object",
     *                 @OA\Property(property="present", type="integer"),
     *                 @OA\Property(property="absent", type="integer"),
     *                 @OA\Property(property="late", type="integer"),
     *                 @OA\Property(property="half_day", type="integer"),
     *                 @OA\Property(property="leave", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function summary(Request $request)
    {
        $query = Attendance::query();

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $totalRecords = $query->count();
        $totalWorkHours = $query->sum('work_hours');
        $totalOvertimeHours = $query->sum('overtime_hours');

        $statusCounts = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'total_records' => $totalRecords,
            'total_work_hours' => $totalWorkHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'status_counts' => [
                'present' => $statusCounts['present'] ?? 0,
                'absent' => $statusCounts['absent'] ?? 0,
                'late' => $statusCounts['late'] ?? 0,
                'half_day' => $statusCounts['half_day'] ?? 0,
                'leave' => $statusCounts['leave'] ?? 0,
            ],
        ]);
    }
} 