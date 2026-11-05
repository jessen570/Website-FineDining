<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_code', 'document_type', 'document_id', 'customer_id',
        'credit_limit', 'opening_ar', 'unpaid_invoices', 'confirmed_so_amount',
        'current_exposure', 'candidate_amount', 'projected_exposure',
        'passed', 'exceeded_by', 'validated_by',
    ];

    protected $casts = [
        'credit_limit'        => 'decimal:2',
        'opening_ar'          => 'decimal:2',
        'unpaid_invoices'     => 'decimal:2',
        'confirmed_so_amount' => 'decimal:2',
        'current_exposure'    => 'decimal:2',
        'candidate_amount'    => 'decimal:2',
        'projected_exposure'  => 'decimal:2',
        'exceeded_by'         => 'decimal:2',
        'passed'              => 'boolean',
    ];
}
