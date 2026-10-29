<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'type',
    ];

    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'account_code', 'account_code');
    }
}
