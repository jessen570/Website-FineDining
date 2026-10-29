<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'job_position',
        'department',
        'status',
        'private_phone',
        'private_email',
        'private_address',
    ];

    /**
     * Return role-scoped presentation to fulfill SR-14.
     */
    public function toRoleScopedArray(?string $role = null): array
    {
        $canSeePrivate = in_array(strtolower($role ?? ''), ['owner', 'hr', 'general manager']);

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'job_position' => $this->job_position,
            'department' => $this->department,
            'status' => $this->status,
            'is_active' => $this->status === 'Aktif',
            'private_phone' => $canSeePrivate ? $this->private_phone : 'HR/Owner only',
            'private_email' => $canSeePrivate ? $this->private_email : 'HR/Owner only',
            'private_address' => $canSeePrivate ? $this->private_address : 'HR/Owner only',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
