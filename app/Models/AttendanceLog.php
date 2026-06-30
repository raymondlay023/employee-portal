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

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDurationAttribute()
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return '—';
        }

        $mins = $this->clock_in_at->diffInMinutes($this->clock_out_at);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }
}
