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

    public function getRouteKeyName(): string
    {
        return 'booking_id';
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id', 'table_id');
    }

    public function assignedServer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_server_id', 'employee_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id', 'contact_id');
    }

    /**
     * Check if a proposed booking window (start_time to buffer_end_time)
     * conflicts with any confirmed booking on the same table.
     *
     * SR-22: each booking = 120 min service + 15 min buffer (total 135 min window)
     * SR-04: regular bookings — reject any overlap
     * SR-03: VIP bookings — allow up to 30 min overlap with existing bookings
     *
     * @param string $tableId
     * @param Carbon $startTime    Proposed start time
     * @param string|null $excludeBookingId  Exclude this booking from check (for updates)
     * @param bool $isVip          Whether the new booking is VIP (allows 30 min overlap)
     */
    public static function checkConflict(
        string $tableId,
        Carbon $startTime,
        ?string $excludeBookingId = null,
        bool $isVip = false
    ): ?self {
        $bufferEnd = $startTime->copy()->addMinutes(135); // 120m service + 15m cleaning

        $query = self::where('table_id', $tableId)
            ->whereIn('status', ['Confirmed', 'Checked-In'])
            ->when($excludeBookingId, fn($q) => $q->where('booking_id', '!=', $excludeBookingId))
            ->where(function ($query) use ($startTime, $bufferEnd) {
                $query->whereBetween('start_time', [$startTime, $bufferEnd])
                    ->orWhereBetween('buffer_end_time', [$startTime, $bufferEnd])
                    ->orWhere(function ($sub) use ($startTime, $bufferEnd) {
                        $sub->where('start_time', '<=', $startTime)
                            ->where('buffer_end_time', '>=', $bufferEnd);
                    });
            });

        $conflicts = $query->get();

        if ($conflicts->isEmpty()) {
            return null; // No conflict
        }

        // SR-03: For VIP bookings, allow overlap up to 30 minutes
        // An overlap is acceptable if the overlap duration <= 30 minutes for all conflicts
        if ($isVip) {
            foreach ($conflicts as $existing) {
                $existingStart  = Carbon::parse($existing->start_time);
                $existingBuffer = Carbon::parse($existing->buffer_end_time);

                // Calculate actual overlap duration in minutes
                $overlapStart = $startTime->max($existingStart);
                $overlapEnd   = $bufferEnd->min($existingBuffer);
                $overlapMinutes = max(0, $overlapStart->diffInMinutes($overlapEnd));

                // SR-03: VIP allows max 30 min overlap
                if ($overlapMinutes > 30) {
                    return $existing; // Still a conflict — overlap exceeds 30 min
                }
            }
            return null; // All overlaps within 30-min VIP allowance
        }

        // SR-04: Regular booking — any overlap is a conflict
        return $conflicts->first();
    }
}

