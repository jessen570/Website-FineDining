<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'customer_id',
        'name',
        'stage',
        'expected_revenue',
        'converted_opportunity_id',
        'is_converted',
        'notes',
    ];

    protected $casts = [
        'expected_revenue' => 'decimal:2',
        'is_converted' => 'boolean',
    ];
}

