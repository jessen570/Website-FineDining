<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'document_id', 'quotation_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'document_id', 'order_id');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($sub) use ($search) {
            $sub->where('approval_code', 'like', "%{$search}%")
                ->orWhere('document_id', 'like', "%{$search}%")
                ->orWhere('requested_by', 'like', "%{$search}%")
                ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
        });
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        if (!$status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function getRouteKeyName(): string
    {
        return 'approval_code';
    }
}
