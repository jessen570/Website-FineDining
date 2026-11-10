<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['table', 'assignedServer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('booking_id', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $bookings = $query->orderByDesc('start_time')->paginate(15)->withQueryString();
        $statuses = ['Draft', 'Confirmed', 'Checked-In', 'Done', 'Cancelled'];
        $tables   = RestaurantTable::all();

        return view('bookings.index', compact('bookings', 'statuses', 'tables'));
    }

    public function create()
    {
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $tables    = RestaurantTable::where('status', 'available')->orderBy('table_id')->get();
        $servers   = Employee::where('status', 'Aktif')->whereIn('department', ['Restaurant'])->orderBy('name')->get();
        return view('bookings.create', compact('customers', 'tables', 'servers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'        => 'required|string|exists:contacts,contact_id',
            'table_id'           => 'required|string|exists:restaurant_tables,table_id',
            'party_size'         => 'required|integer|min:1',
            'booking_date'       => 'required|date',
            'start_time'         => 'required|date_format:H:i',
            'assigned_server_id' => 'nullable|string|exists:employees,employee_id',
            'is_vip'             => 'boolean',
            'notes'              => 'nullable|string',
        ]);

        $table = RestaurantTable::where('table_id', $request->table_id)->firstOrFail();

        if ($request->party_size > $table->seats) {
            return back()->withInput()->with('error', "Jumlah tamu ({$request->party_size}) melebihi kapasitas meja ({$table->seats} kursi).");
        }

        $startTime = Carbon::parse($request->booking_date . ' ' . $request->start_time);

        // SR-04 (regular) & SR-03 (VIP 30-min overlap allowed) & SR-22 (135 min window)
        $isVip    = $request->boolean('is_vip');
        $conflict = Booking::checkConflict($request->table_id, $startTime, null, $isVip);
        if ($conflict) {
            $msg = $isVip
                ? "Meja {$request->table_id} tidak dapat dikonfirmasi: irisan waktu dengan reservasi {$conflict->booking_id} melebihi 30 menit (SR-03 VIP)."
                : "Meja {$request->table_id} sudah dipesan pada waktu tersebut (Booking: {$conflict->booking_id}). Pilih meja atau waktu lain (SR-04).";
            return back()->withInput()->with('error', $msg);
        }

        $seq       = Booking::max('id') + 1;
        $bookingId = 'BK' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $endTime       = $startTime->copy()->addMinutes(120);
        $bufferEndTime = $startTime->copy()->addMinutes(135);

        $booking = Booking::create([
            'booking_id'         => $bookingId,
            'customer_id'        => $request->customer_id,
            'table_id'           => $request->table_id,
            'party_size'         => $request->party_size,
            'booking_date'       => $request->booking_date,
            'start_time'         => $startTime,
            'end_time'           => $endTime,
            'buffer_end_time'    => $bufferEndTime,
            'status'             => 'Draft',
            'assigned_server_id' => $request->assigned_server_id,
            'is_vip'             => $request->boolean('is_vip'),
            'notes'              => $request->notes,
        ]);

        AuditLog::log('CREATE', 'Booking', $bookingId, null, null,
            ['table_id' => $request->table_id, 'start_time' => $startTime->toDateTimeString()],
            "Reservasi {$bookingId} dibuat.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('bookings.show', $bookingId)
            ->with('success', "Reservasi {$bookingId} berhasil dibuat.");
    }

    public function show(string $id)
    {
        $booking = Booking::with(['table', 'assignedServer', 'customer'])
            ->where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))
            ->firstOrFail();
        return view('bookings.show', compact('booking'));
    }

    public function edit(string $id)
    {
        $booking   = Booking::where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))->firstOrFail();
        $customers = Contact::where('contact_type', 'Customer')->orderBy('name')->get();
        $tables    = RestaurantTable::orderBy('table_id')->get();
        $servers   = Employee::where('status', 'Aktif')->orderBy('name')->get();
        return view('bookings.edit', compact('booking', 'customers', 'tables', 'servers'));
    }

    public function update(Request $request, string $id)
    {
        $booking = Booking::where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))->firstOrFail();

        if (in_array($booking->status, ['Done', 'Cancelled'])) {
            return back()->with('error', 'Reservasi yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        $request->validate([
            'customer_id'        => 'required|string|exists:contacts,contact_id',
            'table_id'           => 'required|string|exists:restaurant_tables,table_id',
            'party_size'         => 'required|integer|min:1',
            'booking_date'       => 'required|date',
            'start_time'         => 'required|date_format:H:i',
            'assigned_server_id' => 'nullable|string|exists:employees,employee_id',
            'notes'              => 'nullable|string',
        ]);

        $startTime = Carbon::parse($request->booking_date . ' ' . $request->start_time);

        // Re-check conflict excluding this booking (SR-03 for VIP, SR-04 for regular)
        $isVip    = $request->boolean('is_vip', $booking->is_vip);
        $conflict = Booking::checkConflict($request->table_id, $startTime, $id, $isVip);
        if ($conflict) {
            $msg = $isVip
                ? "Meja {$request->table_id} tidak dapat dikonfirmasi: irisan waktu dengan reservasi {$conflict->booking_id} melebihi 30 menit (SR-03 VIP)."
                : "Meja {$request->table_id} sudah dipesan pada waktu tersebut (Booking: {$conflict->booking_id}) (SR-04).";
            return back()->withInput()->with('error', $msg);
        }

        $before = $booking->toArray();
        $booking->update([
            'customer_id'        => $request->customer_id,
            'table_id'           => $request->table_id,
            'party_size'         => $request->party_size,
            'booking_date'       => $request->booking_date,
            'start_time'         => $startTime,
            'end_time'           => $startTime->copy()->addMinutes(120),
            'buffer_end_time'    => $startTime->copy()->addMinutes(135),
            'assigned_server_id' => $request->assigned_server_id,
            'notes'              => $request->notes,
        ]);

        AuditLog::log('UPDATE', 'Booking', $id, null, $before, $booking->fresh()->toArray(),
            "Reservasi {$id} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('bookings.show', $id)->with('success', "Reservasi diperbarui.");
    }

    public function confirm(Request $request, string $id)
    {
        $booking = Booking::where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))->firstOrFail();

        if ($booking->status !== 'Draft') {
            return back()->with('error', 'Hanya reservasi berstatus Draft yang dapat dikonfirmasi.');
        }

        $conflict = Booking::checkConflict(
            $booking->table_id,
            Carbon::parse($booking->start_time),
            $booking->booking_id,
            (bool) $booking->is_vip
        );
        if ($conflict) {
            return back()->with('error', "Konfirmasi gagal: konflik dengan reservasi {$conflict->booking_id}.");
        }

        $booking->update(['status' => 'Confirmed']);
        RestaurantTable::where('table_id', $booking->table_id)->update(['status' => 'reserved']);

        AuditLog::log('CONFIRM', 'Booking', $booking->booking_id, null, ['status' => 'Draft'], ['status' => 'Confirmed'],
            "Reservasi {$booking->booking_id} dikonfirmasi.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('bookings.show', $booking->booking_id)->with('success', "Reservasi dikonfirmasi.");
    }

    public function checkin(Request $request, string $id)
    {
        $booking = Booking::where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))->firstOrFail();

        if ($booking->status !== 'Confirmed') {
            return back()->with('error', 'Hanya reservasi berstatus Confirmed yang dapat Check-In.');
        }

        $booking->update(['status' => 'Checked-In']);
        RestaurantTable::where('table_id', $booking->table_id)->update(['status' => 'occupied']);

        AuditLog::log('CHECKIN', 'Booking', $booking->booking_id, null, ['status' => 'Confirmed'], ['status' => 'Checked-In'],
            "Reservasi {$booking->booking_id} check-in.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('bookings.show', $booking->booking_id)->with('success', "Check-in berhasil. Meja sekarang occupied.");
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::where(fn($q) => $q->where('booking_id', $id)->orWhere('id', $id))->firstOrFail();

        if (in_array($booking->status, ['Done', 'Cancelled'])) {
            return back()->with('error', 'Reservasi ini tidak dapat dibatalkan.');
        }

        $prevStatus = $booking->status;
        $booking->update(['status' => 'Cancelled']);
        RestaurantTable::where('table_id', $booking->table_id)->update(['status' => 'available']);

        AuditLog::log('CANCEL', 'Booking', $id, null, ['status' => $prevStatus], ['status' => 'Cancelled'],
            "Reservasi {$id} dibatalkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('bookings.show', $id)->with('success', "Reservasi dibatalkan.");
    }
}
