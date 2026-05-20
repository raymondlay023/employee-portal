<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department','designation']);

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function($r) use ($q) {
                $r->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('employee_id', 'like', "%{$q}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->input('department'));
        }

        $employees = $query->paginate(15);

        $departments = Department::all();

        return view('employees.index', compact('employees','departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    // Removed because employees are now synced from JPayroll API

    /**
     * Store a newly created resource in storage.
     */
    // Removed because employees are now synced from JPayroll API

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load(['department','designation']);
        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    // Removed because employees are now synced from JPayroll API

    /**
     * Update the specified resource in storage.
     */
    // Removed because employees are now synced from JPayroll API

    /**
     * Remove the specified resource from storage.
     */
    // Removed because employees are now synced from JPayroll API
}
