<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyWorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'activity',
        'remarks',
        'proof_path',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate duration in hours between start_time and end_time.
     */
    public function getDurationInHoursAttribute(): float
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', $this->start_time);
            $end = Carbon::createFromFormat('H:i:s', $this->end_time);
        } catch (\Exception $e) {
            try {
                $start = Carbon::createFromFormat('H:i', substr($this->start_time, 0, 5));
                $end = Carbon::createFromFormat('H:i', substr($this->end_time, 0, 5));
            } catch (\Exception $ex) {
                return 0;
            }
        }

        return $end->greaterThan($start)
            ? ($end->timestamp - $start->timestamp) / 3600
            : 0;
    }
}
