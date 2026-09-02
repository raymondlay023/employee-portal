<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JPayrollAttendance extends Model
{
    use HasFactory;

    protected $table = 'jpayroll_attendances';

    protected static array $biometricProofCache = [];

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
        'alpha' => 'integer',
        'telat' => 'integer',
        'izin' => 'integer',
        'sakit' => 'integer',
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
     * Returns true when the employee was fully present with biometric proof.
     */
    public function isPresent(): bool
    {
        return $this->alpha === 0
            && $this->izin === 0
            && $this->sakit === 0
            && $this->hasBiometricProof();
    }

    /**
     * Pre-load the biometric proof cache to prevent N+1 queries.
     *
     * @param  string  $employeeId  The employee string ID (e.g. EMP001)
     * @param  iterable  $logs  Collection of AttendanceLog instances
     * @param  string  $startDate  'Y-m-d'
     * @param  string  $endDate  'Y-m-d'
     */
    public static function seedBiometricLogsCache(string $employeeId, iterable $logs, string $startDate, string $endDate): void
    {
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->toDateString();
            self::$biometricProofCache["{$employeeId}_{$dateStr}"] = null;
            $currentDate->addDay();
        }

        foreach ($logs as $log) {
            if ($log->clock_in_at) {
                $dateStr = $log->clock_in_at->toDateString();
                self::$biometricProofCache["{$employeeId}_{$dateStr}"] = $log;
            }
        }
    }

    /**
     * Get the biometric or manual clock-in log for this shift date.
     */
    public function getBiometricLog(): ?AttendanceLog
    {
        $employeeId = $this->employee?->employee_id;
        if (! $employeeId) {
            return null;
        }

        $dateStr = $this->shift_date instanceof Carbon
            ? $this->shift_date->toDateString()
            : $this->shift_date;

        $cacheKey = "{$employeeId}_{$dateStr}";

        if (! array_key_exists($cacheKey, self::$biometricProofCache)) {
            self::$biometricProofCache[$cacheKey] = AttendanceLog::where('employee_id', $employeeId)
                ->whereDate('clock_in_at', $dateStr)
                ->first();
        }

        return self::$biometricProofCache[$cacheKey];
    }

    /**
     * Check if a biometric or manual clock-in log exists for this shift date.
     */
    public function hasBiometricProof(): bool
    {
        return $this->getBiometricLog() !== null;
    }

    /**
     * Check if there are any conflicting abnormalities between JPayroll and Biometrics.
     */
    public function getAbnormality(): ?string
    {
        if ($this->hasBiometricProof()) {
            if ($this->alpha > 0) {
                return 'Punched while marked Absent';
            }
            if ($this->sakit > 0) {
                return 'Punched while marked Sick';
            }
            if ($this->izin > 0) {
                return 'Punched while marked on Permit';
            }

            // Check for missing clock-out on past dates
            $shiftDate = $this->shift_date instanceof Carbon
                ? $this->shift_date
                : Carbon::parse($this->shift_date);

            if ($shiftDate->isBefore(today())) {
                $log = $this->getBiometricLog();
                if ($log && is_null($log->clock_out_at)) {
                    return 'Missing Clock-Out';
                }
            }
        } else {
            if ($this->alpha === 0 && $this->sakit === 0 && $this->izin === 0) {
                if ($this->telat > 0) {
                    return 'Marked Late but no Biometric Punch';
                }
            }
        }

        return null;
    }

    /**
     * Returns a human-readable status label for the UI.
     */
    public function statusLabel(): string
    {
        if ($this->alpha > 0) {
            return 'Absent';
        }
        if ($this->sakit > 0) {
            return 'Sick';
        }
        if ($this->izin > 0) {
            return 'Permit';
        }

        if (! $this->hasBiometricProof()) {
            if ($this->telat > 0) {
                return 'Absent (No Biometric)';
            }

            return 'Off Day';
        }

        if ($this->telat > 0) {
            return 'Late';
        }

        return 'Present';
    }
}
