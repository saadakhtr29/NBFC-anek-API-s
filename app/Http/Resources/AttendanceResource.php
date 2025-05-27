<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AttendanceResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="employee_id", type="integer"),
 *     @OA\Property(property="organization_id", type="integer"),
 *     @OA\Property(property="date", type="string", format="date"),
 *     @OA\Property(property="check_in", type="string", format="date-time"),
 *     @OA\Property(property="check_out", type="string", format="date-time"),
 *     @OA\Property(property="status", type="string", enum={"present", "absent", "late", "half_day", "leave"}),
 *     @OA\Property(property="work_hours", type="number", format="float"),
 *     @OA\Property(property="overtime_hours", type="number", format="float"),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="verified_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="employee",
 *         ref="#/components/schemas/EmployeeResource"
 *     ),
 *     @OA\Property(
 *         property="organization",
 *         ref="#/components/schemas/OrganizationResource"
 *     ),
 *     @OA\Property(
 *         property="verified_by",
 *         ref="#/components/schemas/UserResource"
 *     )
 * )
 */
class AttendanceResource extends JsonResource
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
            'date' => $this->date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'status' => $this->status,
            'work_hours' => $this->work_hours,
            'overtime_hours' => $this->overtime_hours,
            'notes' => $this->notes,
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'verified_by' => new UserResource($this->whenLoaded('verifiedBy')),
        ];
    }
} 