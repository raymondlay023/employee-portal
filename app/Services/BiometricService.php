<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BiometricService
{
    protected array $urls;

    /**
     * Accumulates saved raw XML paths: ['192.168.6.96' => 'biometric/raw_123_192.168.6.96_1721453245.xml']
     */
    public array $savedPayloadPaths = [];

    public function __construct()
    {
        $this->urls = config('services.biometric_device.urls', []);
    }

    /**
     * Fetch attendance logs from all configured biometric devices.
     * Only returns punches within the last $daysLimit days to optimize memory.
     */
    public function fetchAttendanceLogs(int $daysLimit = 7, ?int $syncLogId = null): ?array
    {
        $body = '<GetAttLog>
    <ArgComKey xsi:type="xsd:integer">0</ArgComKey>
    <Arg><PIN xsi:type="xsd:integer">All</PIN></Arg>
</GetAttLog>';

        $allRows = [];
        $cutoffDate = now()->subDays($daysLimit)->startOfDay();
        $successfulFetches = 0;
        $activeUrlsCount = 0;
        $this->savedPayloadPaths = [];

        foreach ($this->urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }

            $activeUrlsCount++;

            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Content-Type' => 'text/xml',
                    ])
                    ->withBody($body, 'text/xml')
                    ->post($url);

                if (! $response->successful()) {
                    Log::channel('biometric')->error("Biometric device HTTP error for URL: {$url}", [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 1000),
                    ]);

                    continue;
                }

                $rawBody = $response->body();

                // Save raw XML payload to disk for audit/traceability
                if ($syncLogId) {
                    $host = parse_url($url, PHP_URL_HOST) ?? 'device';
                    $filename = "biometric/raw_{$syncLogId}_{$host}_".time().'.xml';
                    Storage::put($filename, $rawBody);
                    $this->savedPayloadPaths[$host] = $filename;
                }

                $rows = $this->parseXmlStreaming($rawBody, $url, $cutoffDate);
                $allRows = array_merge($allRows, $rows);
                $successfulFetches++;
            } catch (\Exception $e) {
                Log::channel('biometric')->error("Biometric device API exception for URL: {$url}", ['message' => $e->getMessage()]);
            }
        }

        if ($activeUrlsCount > 0 && $successfulFetches === 0) {
            return null;
        }

        return $allRows;
    }

    /**
     * Stream parse XML response using XMLReader to keep memory usage extremely low.
     */
    protected function parseXmlStreaming(string $xmlContent, string $url, Carbon $cutoffDate): array
    {
        try {
            $reader = new \XMLReader;
            if (! $reader->xml($xmlContent)) {
                Log::channel('biometric')->error("Biometric service: Failed to parse XML response from {$url}");

                return [];
            }

            $rows = [];
            while ($reader->read()) {
                if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'Row') {
                    $node = new \SimpleXMLElement($reader->readOuterXml());
                    $dateTimeStr = (string) $node->DateTime;

                    try {
                        $time = Carbon::parse($dateTimeStr, 'Asia/Jakarta')->setTimezone('UTC');
                    } catch (\Exception $e) {
                        continue;
                    }

                    if ($time->lt($cutoffDate)) {
                        continue;
                    }

                    $rows[] = [
                        'pin' => (string) $node->PIN,
                        'date_time' => $dateTimeStr,
                        'time_parsed' => $time,
                        'verified' => (int) $node->Verified,
                        'status' => (int) $node->Status,
                        'work_code' => (int) $node->WorkCode,
                    ];
                }
            }
            $reader->close();

            return $rows;
        } catch (\Exception $e) {
            Log::channel('biometric')->error("Biometric service XML stream parse exception for URL: {$url}", ['message' => $e->getMessage()]);

            return [];
        }
    }
}
