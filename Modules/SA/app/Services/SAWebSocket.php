<?php

namespace Modules\SA\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SAWebSocket
{
    protected string $deviceIp;
    protected string $password; // kept for consistency, not used in REST (but maybe for future)
    protected array $latestMetrics = [];

    public function __construct()
    {
        $this->deviceIp = config('sa.device_ip', env('SOLAR_DEVICE_IP', ''));
        $this->password = config('sa.password', env('SOLAR_PASSWORD', ''));
    }

    /**
     * Poll the REST API every $interval seconds.
     */
    public function listen(?callable $onMessage = null, ?callable $onTick = null, int $interval = 5): void
    {
        Log::info('[SA] REST listener started – polling every ' . $interval . 's');

        while (true) {
            try {
                $response = Http::timeout(5)->get("http://{$this->deviceIp}/api/status");

                if ($response->successful()) {
                    $metrics = $response->json();

                    if (is_array($metrics) && !empty($metrics)) {
                        // Update latest metrics
                        $this->latestMetrics = $metrics;

                        // Fire the tick callback with the snapshot
                        if ($onTick !== null) {
                            $onTick($metrics);
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
        // No-op for REST
    }
}
