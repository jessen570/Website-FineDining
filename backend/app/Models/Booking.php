<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'table_id',
        'party_size',
        'booking_date',
        'start_time',
        'end_time',
        'buffer_end_time',
        'status',
        'assigned_server_id',
        'is_vip',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'buffer_end_time' => 'datetime',
        'is_vip' => 'boolean',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id', 'table_id');
    }

    public function assignedServer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_server_id', 'employee_id');
    }

    /**
     * Check if a proposed booking window (start_time to buffer_end_time)
     * conflicts with any confirmed booking on the same table (SR-04 & SR-22).
     */
    public static function checkConflict(string $tableId, Carbon $startTime, ?string $excludeBookingId = null): ?self
    {
        $bufferEnd = $startTime->copy()->addMinutes(135); // 120m service + 15m cleaning buffer

        return self::where('table_id', $tableId)
            ->whereIn('status', ['Confirmed', 'Checked-In'])
            ->when($excludeBookingId, fn($q) => $q->where('booking_id', '!=', $excludeBookingId))
            ->where(function ($query) use ($startTime, $bufferEnd) {
                $query->whereBetween('start_time', [$startTime, $bufferEnd])
                    ->orWhereBetween('buffer_end_time', [$startTime, $bufferEnd])
                    ->orWhere(function ($sub) use ($startTime, $bufferEnd) {
                        $sub->where('start_time', '<=', $startTime)
                            ->where('buffer_end_time', '>=', $bufferEnd);
                    });
            })
            ->first();
    }
}

