<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_code', 'document_type', 'document_id', 'customer_id',
        'discount_percent', 'document_amount', 'requested_by', 'requested_by_role',
        'status', 'approved_by', 'approved_by_role', 'approved_at',
        'notes', 'rejection_reason',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'document_amount'  => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'approval_code';
    }
}
