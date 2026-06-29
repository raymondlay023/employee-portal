<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JPayrollAttendance extends Model
{
    use HasFactory;

    protected $table = 'jpayroll_attendances';

    protected $fillable = [
        'employee_id',
        'shift_date',
        'alpha',
        'telat',
        'izin',
        'sakit',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'alpha'      => 'integer',
        'telat'      => 'integer',
        'izin'       => 'integer',
        'sakit'      => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('shift_date', [$from, $to]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true when the employee was fully present with no deductions.
     */
    public function isPresent(): bool
    {
        return $this->alpha === 0 
            && $this->izin === 0 
            && $this->sakit === 0;
    }

    /**
     * Returns a human-readable status label for the UI.
     */
    public function statusLabel(): string
    {
        if ($this->alpha > 0)  return 'Absent';
        if ($this->sakit > 0)  return 'Sick';
        if ($this->izin > 0)   return 'Leave';
        if ($this->telat > 0)  return 'Late';
        
        return 'Present';
    }
}
