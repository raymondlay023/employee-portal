<?php

namespace App\DataTransferObjects;

final readonly class AttendanceSummary
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public int $totalDays,
        public int $presentDays,
        public int $absentDays,
        public int $lateDays,
        public int $sickDays,
        public int $leaveDays,
    ) {}

    /**
     * Attendance rate as a percentage (0-100).
     */
    public function attendanceRate(): float
    {
        if ($this->totalDays === 0) {
            return 0.0;
        }

        return round(($this->presentDays / $this->totalDays) * 100, 1);
    }
}
