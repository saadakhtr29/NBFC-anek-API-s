<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TransactionLog",
 *     title="Transaction Log",
 *     description="Transaction log model",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="loan_id", type="integer"),
 *     @OA\Property(property="employee_id", type="integer"),
 *     @OA\Property(property="transaction_type", type="string"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="status", type="string"),
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
class TransactionLogResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'transaction_type' => $this->transaction_type,
            'amount' => $this->amount,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'loan' => new LoanResource($this->whenLoaded('loan')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }
}
