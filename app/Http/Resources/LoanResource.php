<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Loan",
 *     title="Loan",
 *     description="Loan model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="organization_id", type="integer", example=1),
 *     @OA\Property(property="employee_id", type="integer", example=1),
 *     @OA\Property(property="loan_number", type="string", example="LOAN001"),
 *     @OA\Property(property="type", type="string", example="Personal Loan"),
 *     @OA\Property(property="amount", type="number", format="float", example=10000.00),
 *     @OA\Property(property="interest_rate", type="number", format="float", example=5.5),
 *     @OA\Property(property="term_months", type="integer", example=12),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "approved", "rejected", "disbursed", "active", "completed", "defaulted"},
 *         example="active"
 *     ),
 *     @OA\Property(property="purpose", type="string", example="Home renovation"),
 *     @OA\Property(property="collateral", type="string", example="Property documents"),
 *     @OA\Property(property="guarantor_name", type="string", example="John Doe"),
 *     @OA\Property(property="guarantor_contact", type="string", example="+1234567890"),
 *     @OA\Property(property="guarantor_relationship", type="string", example="Brother"),
 *     @OA\Property(property="approved_by", type="integer", example=1),
 *     @OA\Property(property="approved_at", type="string", format="date-time"),
 *     @OA\Property(property="rejected_by", type="integer", example=1),
 *     @OA\Property(property="rejected_at", type="string", format="date-time"),
 *     @OA\Property(property="rejection_reason", type="string", example="Insufficient income"),
 *     @OA\Property(property="disbursed_by", type="integer", example=1),
 *     @OA\Property(property="disbursed_at", type="string", format="date-time"),
 *     @OA\Property(property="disbursement_method", type="string", example="Bank Transfer"),
 *     @OA\Property(property="disbursement_details", type="object"),
 *     @OA\Property(property="documents", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="remarks", type="string", example="Additional notes"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="statistics",
 *         type="object",
 *         @OA\Property(property="total_interest", type="number", format="float", example=550.00),
 *         @OA\Property(property="total_amount", type="number", format="float", example=10550.00),
 *         @OA\Property(property="monthly_payment", type="number", format="float", example=879.17),
 *         @OA\Property(property="total_repaid", type="number", format="float", example=5275.00),
 *         @OA\Property(property="remaining_amount", type="number", format="float", example=5275.00),
 *         @OA\Property(property="next_payment_due_date", type="string", format="date", example="2024-07-01"),
 *         @OA\Property(property="days_overdue", type="integer", example=0),
 *         @OA\Property(property="is_overdue", type="boolean", example=false)
 *     ),
 *     @OA\Property(
 *         property="payment_history",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="date", type="string", format="date"),
 *             @OA\Property(property="amount", type="number", format="float"),
 *             @OA\Property(property="principal", type="number", format="float"),
 *             @OA\Property(property="interest", type="number", format="float"),
 *             @OA\Property(property="late_fee", type="number", format="float")
 *         )
 *     ),
 *     @OA\Property(
 *         property="payment_schedule",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="payment_number", type="integer"),
 *             @OA\Property(property="due_date", type="string", format="date"),
 *             @OA\Property(property="payment_amount", type="number", format="float"),
 *             @OA\Property(property="principal_amount", type="number", format="float"),
 *             @OA\Property(property="interest_amount", type="number", format="float"),
 *             @OA\Property(property="remaining_amount", type="number", format="float")
 *         )
 *     )
 * )
 */
class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalInterest = $this->amount * ($this->interest_rate / 100);
        $totalAmount = $this->amount + $totalInterest;
        $monthlyPayment = $totalAmount / $this->term_months;
        $totalRepaid = $this->repayments()->where('status', 'completed')->sum('amount');
        $remainingAmount = $totalAmount - $totalRepaid;
        
        $nextPaymentDue = null;
        $daysOverdue = 0;
        $isOverdue = false;
        
        if ($this->status === 'disbursed' || $this->status === 'active') {
            $lastPayment = $this->repayments()->where('status', 'completed')->latest('payment_date')->first();
            $nextPaymentDue = $lastPayment ? $lastPayment->payment_date->addMonth() : $this->start_date;
            
            if ($nextPaymentDue < now()) {
                $daysOverdue = now()->diffInDays($nextPaymentDue);
                $isOverdue = true;
            }
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'employee_id' => $this->employee_id,
            'loan_number' => $this->loan_number,
            'type' => $this->type,
            'amount' => $this->amount,
            'interest_rate' => $this->interest_rate,
            'term_months' => $this->term_months,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'collateral' => $this->collateral,
            'guarantor_name' => $this->guarantor_name,
            'guarantor_contact' => $this->guarantor_contact,
            'guarantor_relationship' => $this->guarantor_relationship,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,
            'rejection_reason' => $this->rejection_reason,
            'disbursed_by' => $this->disbursed_by,
            'disbursed_at' => $this->disbursed_at,
            'disbursement_method' => $this->disbursement_method,
            'disbursement_details' => $this->disbursement_details,
            'documents' => $this->documents,
            'remarks' => $this->remarks,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'rejector' => new UserResource($this->whenLoaded('rejector')),
            'disburser' => new UserResource($this->whenLoaded('disburser')),
            'repayments' => LoanRepaymentResource::collection($this->whenLoaded('repayments')),
            'loan_documents' => LoanDocumentResource::collection($this->whenLoaded('loanDocuments')),
            'total_interest' => $totalInterest,
            'total_amount' => $totalAmount,
            'monthly_payment' => $monthlyPayment,
            'total_repaid' => $totalRepaid,
            'remaining_amount' => $remainingAmount,
            'next_payment_due_date' => $nextPaymentDue,
            'days_overdue' => $daysOverdue,
            'is_overdue' => $isOverdue,
            'payment_history' => $this->repayments()->where('status', 'completed')->get()->map(function ($repayment) {
                return [
                    'date' => $repayment->payment_date,
                    'amount' => $repayment->amount,
                    'principal' => $repayment->principal_amount,
                    'interest' => $repayment->interest_amount,
                    'late_fee' => 0
                ];
            }),
            'payment_schedule' => collect(range(1, $this->term_months))->map(function ($month) use ($monthlyPayment, $totalAmount) {
                $dueDate = $this->start_date->copy()->addMonths($month - 1);
                $remainingAmount = $totalAmount - ($monthlyPayment * ($month - 1));
                return [
                    'payment_number' => $month,
                    'due_date' => $dueDate,
                    'payment_amount' => $monthlyPayment,
                    'principal_amount' => $monthlyPayment * 0.8, // Assuming 80% principal, 20% interest
                    'interest_amount' => $monthlyPayment * 0.2,
                    'remaining_amount' => $remainingAmount
                ];
            })
        ];
    }
}
