<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'employee_id' => 'required|unique:employees,employee_id,' . $employeeId,
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email|unique:employees,email,' . $employeeId,
            'department_id' => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'phone' => 'nullable|string',
            'joined_at' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,on_leave',
        ];
    }
}
