<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JPayrollService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $companyArea;

    public function __construct()
    {
        $this->baseUrl = config('services.jpayroll.url');
        $this->apiKey = config('services.jpayroll.key');
        $this->companyArea = config('services.jpayroll.company_area');
    }

    /**
     * Fetch all employees from JPayroll Master API
     */
    public function fetchAllEmployees()
    {
        try {
            $response = Http::timeout(120)->withHeaders([
                'Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/API_View_Master_Employee.php", [
                'CompanyArea' => $this->companyArea,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Ensure the response status is 200
                if (isset($data['data'])) {
                    return $data['data'] ?? [];
                }

                Log::error('JPayroll API returned non-200 status in payload', ['response' => $data]);

                return [];
            }

            Log::error('JPayroll API HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('JPayroll API Exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Fetch attendance from JPayroll API
     *
     * @param  string  $date1  Start date (Format: d/m/Y)
     * @param  string  $date2  End date (Format: d/m/Y)
     * @param  string|null  $nik  Optional Employee NIK
     */
    public function fetchAttendance(string $date1, string $date2, ?string $nik = null): array
    {
        try {
            $payload = [
                'CompanyArea' => $this->companyArea,
                'Date1' => $date1,
                'Date2' => $date2,
            ];

            if ($nik !== null) {
                $payload['NIK'] = $nik;
            }

            $response = Http::timeout(180)->withHeaders([
                'Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/API_View_Attendance.php", $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    return $data['data'] ?? [];
                }

                Log::error('JPayroll API (Attendance) returned non-200 status in payload', ['response' => $data]);

                return [];
            }

            Log::error('JPayroll API (Attendance) HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('JPayroll API (Attendance) Exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Fetch annual leave details from JPayroll API
     */
    public function fetchAnnualLeave(string $year, string $nik): ?array
    {
        try {
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/API_View_AnnualLeave.php", [
                'CompanyArea' => $this->companyArea,
                'Year' => $year,
                'NIK' => $nik,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    $records = $data['data'];
                    if (is_array($records) && count($records) > 0) {
                        return $records[0];
                    }

                    return is_array($records) ? null : $records;
                }

                return $data;
            }

            Log::error('JPayroll API (Annual Leave) HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('JPayroll API (Annual Leave) Exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
