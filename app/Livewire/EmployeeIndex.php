<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Department;

class EmployeeIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $department = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'department' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartment()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Employee::active()->with(['department', 'designation']);

        if (!empty($this->search)) {
            $q = $this->search;
            $query->where(function($r) use ($q) {
                $r->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('employee_id', 'like', "%{$q}%");
            });
        }

        if (!empty($this->department)) {
            $query->where('department_id', $this->department);
        }

        $employees = $query->paginate(15);
        $departments = Department::all();

        return view('livewire.employee-index', [
            'employees' => $employees,
            'departments' => $departments,
        ]);
    }
}
