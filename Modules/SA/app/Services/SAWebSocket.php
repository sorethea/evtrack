<?php

namespace Modules\SA\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SAWebSocket
{
    protected string $deviceIp;
    protected string $password;
    protected array $latestMetrics = [];

    public function __construct()
    {
        $this->deviceIp = config('sa.device_ip', env('SOLAR_DEVICE_IP', ''));
        $this->password = config('sa.password', env('SOLAR_PASSWORD', ''));
    }

    /**
     * Poll the REST API every $interval seconds.
     * Transform the response into a flat key-value array.
     */
    public function listen(?callable $onMessage = null, ?callable $onTick = null, int $interval = 5): void
    {
        Log::info('[SA] REST listener started – polling every ' . $interval . 's');

        while (true) {
            try {
                $response = Http::timeout(5)
                    ->withBasicAuth('admin', $this->password)
                    ->get("http://{$this->deviceIp}/api/v1/metrics");

                if ($response->successful()) {
                    $data = $response->json();

                    if (is_array($data) && !empty($data)) {
                        // Transform array of objects into flat key-value
                        $transformed = [];
                        foreach ($data as $item) {
                            if (isset($item['topic'], $item['value'])) {
                                $transformed[$item['topic']] = $item['value'];
                            }
                        }

                        // Update latest metrics (flat format)
                        $this->latestMetrics = $transformed;

                        // Fire the tick callback with the flat array
                        if ($onTick !== null) {
                            $onTick($transformed);
                        }

                        Log::info('[SA] REST snapshot stored at ' . now()->toDateTimeString());
                    } else {
                        Log::warning('[SA] Empty or invalid response from API');
                    }
                } else {
                    Log::error('[SA] REST API error: ' . $response->status());
                }
            } catch (Throwable $e) {
                Log::error('[SA] REST polling error: ' . $e->getMessage());
            }

            sleep($interval);
        }
    }

    public function close(): void
    {
        // No-op
    }
}
