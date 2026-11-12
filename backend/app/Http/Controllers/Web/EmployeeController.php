<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('employee_id', 'like', '%'.$request->search.'%')
                  ->orWhere('department', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $employees  = $query->orderBy('employee_id')->paginate(15)->withQueryString();
        $departments = Employee::distinct()->pluck('department')->sort()->values();
        $role        = Auth::user()->role;

        return view('employees.index', compact('employees', 'departments', 'role'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'     => 'required|string|unique:employees,employee_id',
            'name'            => 'required|string|max:255',
            'job_position'    => 'required|string|max:255',
            'department'      => 'required|string|max:255',
            'status'          => 'required|in:Aktif,Tidak Aktif',
            'private_phone'   => 'nullable|string|max:20',
            'private_email'   => 'nullable|email|max:255',
            'private_address' => 'nullable|string',
        ]);

        $employee = Employee::create($validated);

        AuditLog::log('CREATE', 'Employee', $employee->employee_id, null, null, $validated,
            "Karyawan {$employee->name} ditambahkan.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', "Karyawan {$employee->name} berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $employee = Employee::where(fn($q) => $q->where('employee_id', $id)->orWhere('id', $id))->firstOrFail();
        $role     = Auth::user()->role;
        return view('employees.show', compact('employee', 'role'));
    }

    public function edit(string $id)
    {
        $this->authorizeOwner();
        $employee = Employee::where(fn($q) => $q->where('employee_id', $id)->orWhere('id', $id))->firstOrFail();
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeOwner();
        $employee = Employee::where(fn($q) => $q->where('employee_id', $id)->orWhere('id', $id))->firstOrFail();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'job_position'    => 'required|string|max:255',
            'department'      => 'required|string|max:255',
            'status'          => 'required|in:Aktif,Tidak Aktif',
            'private_phone'   => 'nullable|string|max:20',
            'private_email'   => 'nullable|email|max:255',
            'private_address' => 'nullable|string',
        ]);

        $before = $employee->toArray();
        $employee->update($validated);

        AuditLog::log('UPDATE', 'Employee', $employee->employee_id, null, $before, $validated,
            "Data karyawan {$employee->name} diperbarui.", Auth::user()->name, Auth::user()->role);

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', "Data karyawan berhasil diperbarui.");
    }

    private function authorizeOwner(): void
    {
        $role = Auth::user()->role;
        if (!in_array($role, ['owner', 'hr'])) {
            abort(403, 'Hanya Owner/HR yang dapat mengubah data karyawan.');
        }
    }
}
