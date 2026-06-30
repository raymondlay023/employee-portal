<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'gender',
        'department_id',
        'designation_id',
        'phone',
        'joined_at',
        'end_date',
        'status',
        'account_type',
        'organization_structure',
        'branch',
        'skills',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'end_date' => 'date',
        'skills' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->whereNull('end_date')->where('status', 'active');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getInitialsAttribute()
    {
        $first = substr($this->first_name ?? '', 0, 1);
        $last = $this->last_name ? substr($this->last_name, 0, 1) : '';
        return strtoupper($first . $last);
    }
}
