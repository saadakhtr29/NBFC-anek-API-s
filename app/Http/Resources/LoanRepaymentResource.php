<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="LoanRepayment",
 *     title="Loan Repayment",
 *     description="Loan repayment model",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="loan_id", type="integer"),
 *     @OA\Property(property="employee_id", type="integer"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="principal_amount", type="number", format="float"),
 *     @OA\Property(property="interest_amount", type="number", format="float"),
 *     @OA\Property(property="payment_date", type="string", format="date"),
 *     @OA\Property(property="payment_type", type="string", enum={"standard", "excess"}),
 *     @OA\Property(property="transaction_id", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}),
 *     @OA\Property(property="remarks", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="loan",
 *         ref="#/components/schemas/Loan"
 *     ),
 *     @OA\Property(
 *         property="employee",
 *         ref="#/components/schemas/Employee"
 *     )
 * )
 */
class LoanRepaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'loan' => new LoanResource($this->whenLoaded('loan')),
            'is_late' => $this->payment_date > $this->loan->start_date->addMonths($this->loan->repayments->count()),
            'days_late' => $this->payment_date > $this->loan->start_date->addMonths($this->loan->repayments->count())
                ? $this->payment_date->diffInDays($this->loan->start_date->addMonths($this->loan->repayments->count()))
                : 0
        ];
    }
}
