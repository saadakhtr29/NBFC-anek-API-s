<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanRepaymentController;
use App\Http\Controllers\Api\LoanDeficitController;
use App\Http\Controllers\Api\LoanExcessController;
use App\Http\Controllers\Api\LoanDocumentController;
use App\Http\Controllers\Api\TransactionLogController;
use App\Http\Controllers\Api\RepaymentController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\OrganizationSettingController;
use App\Http\Controllers\Api\BulkUploadController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

Route::get('/route-check', function () {
    return response()->json(['message' => 'API is loading']);
});

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/organization/login', [AuthController::class, 'organizationLogin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Employee routes
    Route::apiResource('employees', EmployeeController::class);
    Route::get('employees/statistics', [EmployeeController::class, 'statistics']);
    Route::get('employees/export', [EmployeeController::class, 'export']);

    // Loan routes
    Route::apiResource('loans', LoanController::class);
    Route::get('loans/summary', [LoanController::class, 'summary']);
    Route::get('loans/statistics', [LoanController::class, 'statistics']);
    Route::get('loans/{loan}/status', [LoanController::class, 'status']);
    Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
    Route::post('loans/{loan}/reject', [LoanController::class, 'reject']);
    Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse']);

    // Loan repayment routes
    Route::get('loans/{loan}/repayments', [LoanRepaymentController::class, 'index']);
    Route::post('loans/{loan}/repayments', [LoanRepaymentController::class, 'store']);
    Route::get('loans/{loan}/repayments/{repayment}', [LoanRepaymentController::class, 'show']);
    Route::put('loans/{loan}/repayments/{repayment}', [LoanRepaymentController::class, 'update']);
    Route::delete('loans/{loan}/repayments/{repayment}', [LoanRepaymentController::class, 'destroy']);
    Route::get('loans/{loan}/repayments/summary', [LoanRepaymentController::class, 'summary']);

    // Loan deficit routes
    Route::apiResource('loan-deficits', LoanDeficitController::class);
    Route::get('loans/{loan}/deficits', [LoanDeficitController::class, 'loanDeficits']);

    // Loan excess routes
    Route::apiResource('loan-excesses', LoanExcessController::class);
    Route::get('loans/{loan}/excesses', [LoanExcessController::class, 'loanExcesses']);

    // Loan document routes
    Route::apiResource('loan-documents', LoanDocumentController::class);
    Route::get('loans/{loan}/documents', [LoanDocumentController::class, 'loanDocuments']);
    Route::post('loan-documents/verify/{document}', [LoanDocumentController::class, 'verify']);

    // Transaction log routes
    Route::apiResource('transaction-logs', TransactionLogController::class);
    Route::get('loans/{loan}/transactions', [TransactionLogController::class, 'loanTransactions']);

    // Repayment routes
    Route::get('/repayments', [RepaymentController::class, 'index']);
    Route::post('/repayments', [RepaymentController::class, 'store']);
    Route::get('/repayments/{repayment}', [RepaymentController::class, 'show']);
    Route::put('/repayments/{repayment}', [RepaymentController::class, 'update']);
    Route::delete('/repayments/{repayment}', [RepaymentController::class, 'destroy']);
    Route::get('/repayments/summary', [RepaymentController::class, 'summary']);
    Route::post('/repayments/{repayment}/approve', [\App\Http\Controllers\Api\RepaymentController::class, 'approve']);
    Route::post('/repayments/{repayment}/reject', [\App\Http\Controllers\Api\RepaymentController::class, 'reject']);
    Route::get('/repayments/statistics', [\App\Http\Controllers\Api\RepaymentController::class, 'statistics']);

    // Salary Management Routes
    Route::get('/salaries', [SalaryController::class, 'index']);
    Route::post('/salaries', [SalaryController::class, 'store']);
    Route::get('/salaries/{salary}', [SalaryController::class, 'show']);
    Route::put('/salaries/{salary}', [SalaryController::class, 'update']);
    Route::delete('/salaries/{salary}', [SalaryController::class, 'destroy']);
    Route::get('/salaries/summary', [SalaryController::class, 'summary']);

    // Attendance Management Routes
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show']);
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update']);
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy']);
    Route::post('/attendance/{attendance}/verify', [AttendanceController::class, 'verify']);
    Route::get('/attendance/summary', [AttendanceController::class, 'summary']);

    // Organization Settings Routes
    Route::get('/organization-settings', [OrganizationSettingController::class, 'index']);
    Route::post('/organization-settings', [OrganizationSettingController::class, 'store']);
    Route::get('/organization-settings/{setting}', [OrganizationSettingController::class, 'show']);
    Route::put('/organization-settings/{setting}', [OrganizationSettingController::class, 'update']);
    Route::delete('/organization-settings/{setting}', [OrganizationSettingController::class, 'destroy']);
    Route::get('/organization-settings/key/{key}', [OrganizationSettingController::class, 'getByKey']);

    // Bulk Upload Routes
    Route::post('/bulk-upload/employees', [BulkUploadController::class, 'uploadEmployees']);
    Route::post('/bulk-upload/loans', [BulkUploadController::class, 'uploadLoans']);
    Route::post('/bulk-upload/attendance', [BulkUploadController::class, 'uploadAttendance']);
    Route::post('/bulk-upload/salaries', [BulkUploadController::class, 'uploadSalaries']);
    Route::get('/bulk-upload/template/{type}', [BulkUploadController::class, 'downloadTemplate']);

    // System Settings Routes
    Route::get('/system-settings', [SystemSettingController::class, 'index']);
    Route::post('/system-settings', [SystemSettingController::class, 'store']);
    Route::get('/system-settings/{setting}', [SystemSettingController::class, 'show']);
    Route::put('/system-settings/{setting}', [SystemSettingController::class, 'update']);
    Route::delete('/system-settings/{setting}', [SystemSettingController::class, 'destroy']);
    Route::get('/system-settings/key/{key}', [SystemSettingController::class, 'getByKey']);

    // Dashboard Routes
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/organization/{organization}', [DashboardController::class, 'organizationStats']);
    Route::get('/dashboard/loans', [DashboardController::class, 'loanStats']);
    Route::get('/dashboard/attendance', [DashboardController::class, 'attendanceStats']);
    Route::get('/dashboard/salaries', [DashboardController::class, 'salaryStats']);
    Route::get('/dashboard/employee/{employee}', [DashboardController::class, 'employeeStats']);

    // Organization routes
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/statistics', [OrganizationController::class, 'statistics']);
});

// OpenAPI JSON route
Route::get('/api-docs.json', function () {
    return response()->file(storage_path('api-docs/api-docs.json'));
}); 