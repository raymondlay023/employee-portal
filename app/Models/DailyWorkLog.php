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
     * Calculate duration in minutes between start_time and end_time.
     */
    public function getDurationMinutesAttribute(): int
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
            ? (int) round(($end->timestamp - $start->timestamp) / 60)
            : 0;
    }

    /**
     * Calculate duration in hours between start_time and end_time.
     */
    public function getDurationInHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }

    /**
     * Format total minutes into human-readable hours and minutes string.
     * Examples: 70 mins -> "1 hour 10 mins" (or "1h 10m" if $short = true).
     */
    public static function formatMinutes(int|float $minutes, bool $short = false): string
    {
        $totalMinutes = (int) round($minutes);
        if ($totalMinutes <= 0) {
            return $short ? '0m' : '0 '.__('mins');
        }

        $hours = (int) floor($totalMinutes / 60);
        $mins = $totalMinutes % 60;

        $parts = [];
        if ($hours > 0) {
            $hourUnit = $short ? 'h' : ($hours === 1 ? __('hour') : __('hours'));
            $parts[] = $short ? "{$hours}{$hourUnit}" : "{$hours} {$hourUnit}";
        }

        if ($mins > 0) {
            $minUnit = $short ? 'm' : __('mins');
            $parts[] = $short ? "{$mins}{$minUnit}" : "{$mins} {$minUnit}";
        }

        return implode(' ', $parts);
    }

    /**
     * Accessor for compact formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        return static::formatMinutes($this->duration_minutes, true);
    }
}
