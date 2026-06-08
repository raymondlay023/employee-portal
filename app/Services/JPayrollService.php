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
            $response = Http::withHeaders([
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
                'body' => $response->body()
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
     * @param string $date1 Start date (Format: d/m/Y)
     * @param string $date2 End date (Format: d/m/Y)
     * @param string|null $nik Optional Employee NIK
     * @return array
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

            $response = Http::withHeaders([
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
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('JPayroll API (Attendance) Exception', ['message' => $e->getMessage()]);
            return [];
        }
    }
}

