<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'total_debit',
        'total_credit',
        'is_posted',
    ];

    protected $casts = [
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'entry_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'entry_id';
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'entry_id', 'entry_id');
    }
}

