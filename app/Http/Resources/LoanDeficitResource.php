<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="LoanDeficit",
 *     title="Loan Deficit",
 *     description="Loan deficit model",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="loan_id", type="integer"),
 *     @OA\Property(property="employee_id", type="integer"),
 *     @OA\Property(property="amount", type="number", format="float"),
 *     @OA\Property(property="due_date", type="string", format="date"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "waived"}),
 *     @OA\Property(property="fee_amount", type="number", format="float"),
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
class LoanDeficitResource extends JsonResource
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
            'amount' => $this->amount,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'fee_amount' => $this->fee_amount,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'loan' => new LoanResource($this->whenLoaded('loan')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }
}
