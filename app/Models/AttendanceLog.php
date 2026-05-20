<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    protected $dates = ['clock_in_at','clock_out_at'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
