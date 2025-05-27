<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SalaryResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="employee_id", type="integer"),
 *     @OA\Property(property="organization_id", type="integer"),
 *     @OA\Property(property="month", type="integer"),
 *     @OA\Property(property="year", type="integer"),
 *     @OA\Property(property="basic_salary", type="number", format="float"),
 *     @OA\Property(property="allowances", type="number", format="float"),
 *     @OA\Property(property="deductions", type="number", format="float"),
 *     @OA\Property(property="net_salary", type="number", format="float"),
 *     @OA\Property(property="payment_date", type="string", format="date"),
 *     @OA\Property(property="payment_method", type="string"),
 *     @OA\Property(property="reference_number", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed"}),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="employee",
 *         ref="#/components/schemas/EmployeeResource"
 *     ),
 *     @OA\Property(
 *         property="organization",
 *         ref="#/components/schemas/OrganizationResource"
 *     )
 * )
 */
class SalaryResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'organization_id' => $this->organization_id,
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => $this->basic_salary,
            'allowances' => $this->allowances,
            'deductions' => $this->deductions,
            'net_salary' => $this->net_salary,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
        ];
    }
} 