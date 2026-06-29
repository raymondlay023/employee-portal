<?php

namespace App\Livewire\Pages;

use App\Models\DailyWorkLog;
use Livewire\Component;
use Livewire\WithFileUploads;

class WorkLogs extends Component
{
    use WithFileUploads;

    public string $date = '';
    public array $logs = [];
    public array $newProofs = [];
    public array $pendingDeletions = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadLogs();
    }

    /**
     * Load work logs for the selected date.
     */
    public function loadLogs(): void
    {
        $dbLogs = DailyWorkLog::where('user_id', auth()->id())
            ->where('date', $this->date)
            ->orderBy('start_time', 'asc')
            ->get();

        if ($dbLogs->isEmpty()) {
            $this->logs = [
                [
                    'id' => null,
                    'user_id' => auth()->id(),
                    'date' => $this->date,
                    'start_time' => '',
                    'end_time' => '',
                    'activity' => '',
                    'remarks' => '',
                    'proof_path' => null,
                ]
            ];
        } else {
            $this->logs = $dbLogs->toArray();
        }
        
        $this->newProofs = [];
        $this->pendingDeletions = [];
    }

    /**
     * Listener for date updates.
     */
    public function updatedDate(): void
    {
        $this->loadLogs();
    }

    public function addHourSlot(): void
    {
        $nextStart = '';
        $nextEnd = '';

        if (!empty($this->logs)) {
            // Find the last non-empty end_time from the end
            for ($i = count($this->logs) - 1; $i >= 0; $i--) {
                $candidate = $this->logs[$i]['end_time'] ?? '';
                $candidate = trim(substr($candidate, 0, 5));
                if ($candidate !== '') {
                    if (preg_match('/^\d{1,2}:\d{2}$/', $candidate)) {
                        try {
                            $start = \Carbon\Carbon::createFromFormat('H:i', $candidate);
                            $end = $start->copy()->addHour();
                            $nextStart = $start->format('H:i');
                            $nextEnd = $end->format('H:i');
                        } catch (\Exception $e) {
                            // ignore parse errors and fall back to empty
                            $nextStart = '';
                            $nextEnd = '';
                        }
                    }
                    break;
                }
            }
        }

        // If we found a valid nextStart, prefill both start and end (end = +1 hour).
        // Otherwise append an empty slot for manual input.
        $this->logs[] = [
            'id' => null,
            'user_id' => auth()->id(),
            'date' => $this->date,
            'start_time' => $nextStart,
            'end_time' => $nextEnd,
            'activity' => '',
            'remarks' => '',
            'proof_path' => null,
        ];
    }

    /**
     * Remove the last chronological hour slot.
     */
    public function removeLastSlot(): void
    {
        if (count($this->logs) > 1) {
            $lastIndex = count($this->logs) - 1;
            $this->removeSlot($lastIndex);
        }
    }

    /**
     * Remove a specific hour slot.
     */
    public function removeSlot(int $index): void
    {
        if (isset($this->logs[$index])) {
            $logData = $this->logs[$index];
            if (!empty($logData['id'])) {
                $dbLog = DailyWorkLog::find($logData['id']);
                if ($dbLog) {
                    if ($dbLog->proof_path) {
                        \Storage::disk('public')->delete($dbLog->proof_path);
                    }
                    $dbLog->delete();
                }
            }
            
            unset($this->logs[$index]);
            $this->logs = array_values($this->logs);
            
            // Clear temporary arrays to prevent index misalignment
            $this->newProofs = [];
            $this->pendingDeletions = [];

            if (empty($this->logs)) {
                $this->logs = [
                    [
                        'id' => null,
                        'user_id' => auth()->id(),
                        'date' => $this->date,
                        'start_time' => '',
                        'end_time' => '',
                        'activity' => '',
                        'remarks' => '',
                        'proof_path' => null,
                    ]
                ];
            }

            $this->dispatch('toast', ['message' => 'Time slot removed.', 'type' => 'success']);
        }
    }

    /**
     * Clear all logs for the current day.
     */
    public function clearAllLogs(): void
    {
        $dbLogs = DailyWorkLog::where('user_id', auth()->id())->where('date', $this->date)->get();
        foreach ($dbLogs as $dbLog) {
            if ($dbLog->proof_path) {
                \Storage::disk('public')->delete($dbLog->proof_path);
            }
            $dbLog->delete();
        }

        $this->logs = [
            [
                'id' => null,
                'user_id' => auth()->id(),
                'date' => $this->date,
                'start_time' => '',
                'end_time' => '',
                'activity' => '',
                'remarks' => '',
                'proof_path' => null,
            ]
        ];
        $this->newProofs = [];
        $this->pendingDeletions = [];

        $this->dispatch('toast', ['message' => 'All logs for this day have been cleared.', 'type' => 'success']);
    }

    /**
     * Delete an uploaded proof for the specified index.
     */
    public function deleteProof(int $index): void
    {
        if (isset($this->newProofs[$index])) {
            unset($this->newProofs[$index]);
        }

        $logData = $this->logs[$index] ?? null;
        if ($logData) {
            if (!empty($logData['id'])) {
                $this->pendingDeletions[$index] = true;
            } else {
                $this->logs[$index]['proof_path'] = null;
            }
        }

        $this->dispatch('toast', ['message' => 'Proof image marked for deletion.', 'type' => 'success']);
    }

    /**
     * Undo the deletion of a proof for the specified index.
     */
    public function undoDeleteProof(int $index): void
    {
        if (isset($this->pendingDeletions[$index])) {
            unset($this->pendingDeletions[$index]);
        }

        $this->dispatch('toast', ['message' => 'Proof image deletion cancelled.', 'type' => 'success']);
    }

    /**
     * Save all logs in the current timesheet.
     */
    public function save(): void
    {
        $this->validate([
            'logs.*.start_time' => 'required',
            'logs.*.end_time' => 'required',
            'logs.*.activity' => 'required|string|max:255',
            'logs.*.remarks' => 'nullable|string|max:1000',
            'newProofs.*' => 'nullable|image|max:5120', // 5MB max (in KB)
        ], [
            'logs.*.activity.required' => 'The activity field is required.',
            'logs.*.start_time.required' => 'Start time is required.',
            'logs.*.end_time.required' => 'End time is required.',
            'newProofs.*.image' => 'The proof must be an image file.',
            'newProofs.*.max' => 'The proof must not be larger than 5MB.',
        ]);

        // Custom validation: check start_time < end_time and proof presence/size
        $hasErrors = false;
        foreach ($this->logs as $index => $log) {
            $start = $log['start_time'];
            $end = $log['end_time'];
            if ($start >= $end) {
                $msg = "Start time must be before end time.";
                $this->addError("logs.{$index}.start_time", $msg);
                $errorMessages[] = $msg;
                $hasErrors = true;
            }

            // // Proof presence: either existing proof_path (not marked for deletion) or a newly uploaded proof is required
            // $hasExistingProof = !empty($log['proof_path']) && !isset($this->pendingDeletions[$index]);
            $hasNewProof = isset($this->newProofs[$index]) && $this->newProofs[$index];

            // if (!$hasExistingProof && !$hasNewProof) {
            //     $msg = "Proof image is required for this time slot.";
            //     $this->addError("newProofs.{$index}", $msg);
            //     $errorMessages[] = $msg;
            //     $hasErrors = true;
            // }

            // If a new proof exists, double-check its size (bytes) to be <= 5MB
            if ($hasNewProof) {
                try {
                    $file = $this->newProofs[$index];
                    $sizeBytes = null;
                    if (is_object($file)) {
                        if (method_exists($file, 'getSize')) {
                            $sizeBytes = $file->getSize();
                        } elseif (method_exists($file, 'getClientSize')) {
                            $sizeBytes = $file->getClientSize();
                        }
                    }

                    if ($sizeBytes !== null && $sizeBytes > 5 * 1024 * 1024) {
                        $msg = "The proof must not be larger than 5MB.";
                        $this->addError("newProofs.{$index}", $msg);
                        $errorMessages[] = $msg;
                        $hasErrors = true;
                    }
                } catch (\Exception $e) {
                    // ignore size-check failures, rely on validation rules
                }
            }
        }

        // Custom validation: check overlaps
        $count = count($this->logs);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $startA = $this->logs[$i]['start_time'];
                $endA = $this->logs[$i]['end_time'];
                $startB = $this->logs[$j]['start_time'];
                $endB = $this->logs[$j]['end_time'];

                // Overlap check: A.start < B.end && A.end > B.start
                if ($startA < $endB && $endA > $startB) {
                    $msg = "This time slot overlaps with another slot.";
                    $this->addError("logs.{$i}.start_time", $msg);
                    $this->addError("logs.{$j}.start_time", $msg);
                    $errorMessages[] = $msg;
                    $hasErrors = true;
                }
            }
        }

        if ($hasErrors) {
            // Show inline errors only; no error toast
            return;
        }

        // Save logs and process new file uploads
        foreach ($this->logs as $index => $logData) {
            $proofPath = $logData['proof_path'] ?? null;

            if (isset($logData['id']) && $logData['id']) {
                $log = DailyWorkLog::find($logData['id']);
                if ($log) {
                    if (isset($this->newProofs[$index]) && $this->newProofs[$index]) {
                        // Delete old proof from storage
                        if ($log->proof_path) {
                            \Storage::disk('public')->delete($log->proof_path);
                        }
                        $proofPath = $this->newProofs[$index]->store('proofs/' . auth()->id(), 'public');
                        $this->logs[$index]['proof_path'] = $proofPath;
                        if (isset($this->pendingDeletions[$index])) {
                            unset($this->pendingDeletions[$index]);
                        }
                    } elseif (isset($this->pendingDeletions[$index])) {
                        // Delete old proof from storage since they confirmed deletion without upload
                        if ($log->proof_path) {
                            \Storage::disk('public')->delete($log->proof_path);
                        }
                        $proofPath = null;
                        $this->logs[$index]['proof_path'] = null;
                        unset($this->pendingDeletions[$index]);
                    } else {
                        // Keep whatever proof path is in logs, fallback to original if not modified
                        $proofPath = $logData['proof_path'] ?? $log->proof_path;
                    }

                    $log->update([
                        'start_time' => $logData['start_time'],
                        'end_time' => $logData['end_time'],
                        'activity' => $logData['activity'],
                        'remarks' => $logData['remarks'],
                        'proof_path' => $proofPath,
                    ]);
                }
            } else {
                if (isset($this->newProofs[$index]) && $this->newProofs[$index]) {
                    $proofPath = $this->newProofs[$index]->store('proofs/' . auth()->id(), 'public');
                    $this->logs[$index]['proof_path'] = $proofPath;
                }

                $newLog = DailyWorkLog::create([
                    'user_id' => auth()->id(),
                    'date' => $this->date,
                    'start_time' => $logData['start_time'],
                    'end_time' => $logData['end_time'],
                    'activity' => $logData['activity'],
                    'remarks' => $logData['remarks'],
                    'proof_path' => $proofPath,
                ]);
                $this->logs[$index]['id'] = $newLog->id;
            }
        }

        // Clear temporary uploads and pending deletions arrays
        $this->newProofs = [];
        $this->pendingDeletions = [];

        $this->dispatch('toast', ['message' => 'Daily work logs saved successfully.', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.pages.work-logs');
    }
}
