<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanDeficit;
use App\Models\LoanExcess;
use App\Models\Organization;
use App\Models\TransactionLog;
use App\Models\Attendance;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for dashboard analytics and reporting"
 * )
 */
class DashboardController extends Controller
{
    /**
     * Get overall system statistics.
     *
     * @OA\Get(
     *     path="/api/dashboard/overview",
     *     tags={"Dashboard"},
     *     summary="Get overall system statistics",
     *     @OA\Response(
     *         response=200,
     *         description="System statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_organizations", type="integer"),
     *             @OA\Property(property="total_employees", type="integer"),
     *             @OA\Property(property="total_loans", type="integer"),
     *             @OA\Property(property="total_loan_amount", type="number"),
     *             @OA\Property(property="total_paid_amount", type="number"),
     *             @OA\Property(property="total_pending_amount", type="number")
     *         )
     *     )
     * )
     */
    public function overview()
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'total_employees' => Employee::count(),
            'total_loans' => Loan::count(),
            'total_loan_amount' => Loan::sum('amount'),
            'total_paid_amount' => Loan::where('status', 'completed')->sum('amount'),
            'total_pending_amount' => Loan::whereIn('status', ['pending', 'active'])->sum('amount'),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get organization-specific statistics.
     *
     * @OA\Get(
     *     path="/api/dashboard/organization/{organization}",
     *     tags={"Dashboard"},
     *     summary="Get organization-specific statistics",
     *     @OA\Parameter(
     *         name="organization",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Organization statistics"
     *     )
     * )
     */
    public function organizationStats(Organization $organization)
    {
        $stats = [
            'total_employees' => $organization->employees()->count(),
            'total_loans' => $organization->loans()->count(),
            'total_loan_amount' => $organization->loans()->sum('amount'),
            'total_paid_amount' => $organization->loans()->where('status', 'completed')->sum('amount'),
            'total_pending_amount' => $organization->loans()->whereIn('status', ['pending', 'active'])->sum('amount'),
            'average_loan_amount' => $organization->loans()->avg('amount'),
            'total_salary_paid' => $organization->salaries()->where('status', 'paid')->sum('net_salary'),
            'attendance_rate' => $this->calculateAttendanceRate($organization),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get loan statistics.
     *
     * @OA\Get(
     *     path="/api/dashboard/loans",
     *     tags={"Dashboard"},
     *     summary="Get loan statistics",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Loan statistics"
     *     )
     * )
     */
    public function loanStats(Request $request)
    {
        $query = Loan::query();
        
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $stats = [
            'total_loans' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'status_distribution' => $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'monthly_trend' => $query->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total_amount')
            )
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get attendance statistics.
     *
     * @OA\Get(
     *     path="/api/dashboard/attendance",
     *     tags={"Dashboard"},
     *     summary="Get attendance statistics",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance statistics"
     *     )
     * )
     */
    public function attendanceStats(Request $request)
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

        $stats = [
            'total_records' => $query->count(),
            'status_distribution' => $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'average_work_hours' => $query->avg('work_hours'),
            'total_overtime_hours' => $query->sum('overtime_hours'),
            'daily_trend' => $query->select(
                'date',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "present" then 1 else 0 end) as present'),
                DB::raw('sum(case when status = "absent" then 1 else 0 end) as absent'),
                DB::raw('sum(case when status = "late" then 1 else 0 end) as late')
            )
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get salary statistics.
     *
     * @OA\Get(
     *     path="/api/dashboard/salaries",
     *     tags={"Dashboard"},
     *     summary="Get salary statistics",
     *     @OA\Parameter(
     *         name="organization_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salary statistics"
     *     )
     * )
     */
    public function salaryStats(Request $request)
    {
        $query = Salary::query();
        
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $stats = [
            'total_paid' => $query->where('status', 'paid')->sum('net_salary'),
            'total_pending' => $query->where('status', 'pending')->sum('net_salary'),
            'average_salary' => $query->avg('net_salary'),
            'monthly_distribution' => $query->select(
                'month',
                DB::raw('sum(net_salary) as total_amount'),
                DB::raw('count(*) as employee_count')
            )
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'department_distribution' => $query->join('employees', 'salaries.employee_id', '=', 'employees.id')
                ->select(
                    'employees.department',
                    DB::raw('avg(salaries.net_salary) as average_salary'),
                    DB::raw('count(*) as employee_count')
                )
                ->groupBy('employees.department')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Calculate attendance rate for an organization.
     */
    private function calculateAttendanceRate(Organization $organization)
    {
        $totalDays = $organization->attendances()->count();
        if ($totalDays === 0) {
            return 0;
        }

        $presentDays = $organization->attendances()
            ->where('status', 'present')
            ->count();

        return round(($presentDays / $totalDays) * 100, 2);
    }
} 