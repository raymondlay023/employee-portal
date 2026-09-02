<?php

namespace App\Livewire\Attendance;

use App\Authorization\Permissions;
use App\Services\PermitImportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadPermitCsv extends Component
{
    use WithFileUploads;

    public $csv_file;

    public $month;

    public $year;

    public bool $showModal = false;

    public ?array $importResult = null;

    public function mount(): void
    {
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
    }

    public function openModal(): void
    {
        $this->reset(['csv_file', 'importResult']);
        $this->resetValidation();
        $this->month = (int) ($this->month ?: now()->month);
        $this->year = (int) ($this->year ?: now()->year);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['csv_file', 'importResult']);
        $this->resetValidation();
    }

    public function updatedCsvFile(): void
    {
        $this->resetValidation('csv_file');

        if ($this->csv_file) {
            $filename = $this->csv_file->getClientOriginalName();

            $month = $this->inferMonthFromFilename($filename);
            if ($month) {
                $this->month = $month;
            }

            $year = $this->inferYearFromFilename($filename);
            if ($year) {
                $this->year = $year;
            }
        }
    }

    public function importPermit(PermitImportService $service): void
    {
        if (! Auth::user()->can(Permissions::MANAGE_ATTENDANCE)) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'csv_file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile) {
                        $ext = strtolower($value->getClientOriginalExtension());
                        if (! in_array($ext, ['csv', 'txt', 'tsv'])) {
                            $fail(__('The file must be a file of type: csv, txt.'));
                        }
                    }
                },
            ],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2035'],
        ]);

        $result = $service->import(
            file: $this->csv_file,
            month: (int) $this->month,
            year: (int) $this->year,
            triggeredByUserId: Auth::id(),
            triggerType: 'manual'
        );

        if ($result['status'] === 'success') {
            $this->importResult = $result;
            $monthName = Carbon::createFromDate((int) $this->year, (int) $this->month, 1)->format('F Y');
            $permitDays = $result['total_permit_days'];
            session()->flash(
                'permit_upload_success',
                "Permit CSV imported successfully for {$monthName}! ({$result['employees_processed']} employees processed, {$permitDays} permit days assigned)."
            );

            $this->dispatch('permit-uploaded');
        } else {
            $this->addError('csv_file', $result['error'] ?? 'Failed to import Permit CSV file.');
        }
    }

    /**
     * Infer month integer from filename (supports Indonesian and English month names).
     */
    private function inferMonthFromFilename(string $filename): ?int
    {
        $lower = Str::lower($filename);

        $monthMap = [
            'januari' => 1, 'january' => 1, 'jan' => 1,
            'februari' => 2, 'february' => 2, 'feb' => 2,
            'maret' => 3, 'march' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'june' => 6, 'jun' => 6,
            'juli' => 7, 'july' => 7, 'jul' => 7,
            'agustus' => 8, 'august' => 8, 'agt' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'oktober' => 10, 'october' => 10, 'okt' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'december' => 12, 'des' => 12, 'dec' => 12,
        ];

        foreach ($monthMap as $name => $num) {
            if (Str::contains($lower, $name)) {
                return $num;
            }
        }

        return null;
    }

    /**
     * Infer 4-digit year from filename.
     */
    private function inferYearFromFilename(string $filename): ?int
    {
        if (preg_match('/\b(20\d\d)\b/', $filename, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function render(): View
    {
        $availableYears = range(now()->year - 2, now()->year + 2);

        return view('livewire.attendance.upload-permit-csv', [
            'availableYears' => $availableYears,
        ]);
    }
}
