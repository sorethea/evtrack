<?php

namespace Modules\SA\Services;

use Illuminate\Support\Facades\Log;
use WebSocket\Client;
use WebSocket\TimeoutException;
use Throwable;

class SAWebSocket
{
    protected Client $client;
    protected string $deviceIp;
    protected string $password;
    protected array $latestMetrics = [];
    protected bool $useRestFallback = false; // set to true if WebSocket fails

    public function __construct()
    {
        $this->deviceIp = config('sa.device_ip', env('SOLAR_DEVICE_IP', ''));
        $this->password = config('sa.password', env('SOLAR_PASSWORD', ''));
    }

    /**
     * Connect and listen – runs forever with auto‑reconnect.
     */
    public function listen(?callable $onMessage = null, ?callable $onTick = null, int $interval = 5): void
    {
        if ($this->useRestFallback) {
            $this->restPoll($onTick, $interval);
            return;
        }

        while (true) {
            try {
                Log::info('[SA] Connecting to Solar Assistant...');
                $encodedPassword = urlencode($this->password);
                $url = "ws://{$this->deviceIp}/api/websocket?password={$encodedPassword}";
                $this->client = new Client($url);

                // Send Phoenix join message
                $joinMessage = json_encode([
                    'topic'   => 'metrics',
                    'event'   => 'phx_join',
                    'payload' => [],
                    'ref'     => '1'
                ]);
                $this->client->text($joinMessage);
                Log::info('[SA] Joined metrics channel');

                $lastTick = microtime(true);
                $lastPing = microtime(true);

                while (true) {
                    try {
                        $response = $this->client->receive(0.1); // non‑blocking

                        if ($response) {
                            $this->handleResponse($response, $onMessage);
                        }
                    } catch (TimeoutException $e) {
                        // No message – fine
                    } catch (Throwable $e) {
                        Log::error('[SA] Receive error: ' . $e->getMessage());
                        break; // break inner loop to reconnect
                    }

                    $now = microtime(true);

                    // Send heartbeat (phx_ping) every 25 seconds
                    if ($now - $lastPing >= 25) {
                        $this->client->text(json_encode([
                            'topic'   => 'metrics',
                            'event'   => 'phx_ping',
                            'payload' => [],
                            'ref'     => 'ping_' . time()
                        ]));
                        $lastPing = $now;
                        Log::debug('[SA] Sent phx_ping');
                    }

                    // Fire tick every $interval seconds
                    if ($now - $lastTick >= $interval && $onTick !== null) {
                        $onTick($this->latestMetrics);
                        Log::info('[SA] Tick executed at ' . now()->toDateTimeString());
                        $lastTick = $now;
                    }

                    usleep(10000); // 10ms – prevent CPU spin
                }
            } catch (Throwable $e) {
                Log::error('[SA] Connection lost – reconnecting in 5 seconds: ' . $e->getMessage());
                sleep(5);
            }
        }
    }

    /**
     * Handle incoming WebSocket messages.
     */
    protected function handleResponse(string $response, ?callable $onMessage): void
    {
        $data = json_decode($response, true);
        if (!is_array($data)) {
            Log::warning('[SA] Invalid JSON: ' . substr($response, 0, 200));
            return;
        }

        // Log raw for debugging (optional)
        // Log::debug('[SA] Raw: ' . json_encode($data));

        $event = $data['event'] ?? null;

        // Ignore system messages
        if (in_array($event, ['definition', 'phx_reply', 'phx_pong'])) {
            return;
        }

        if ($event === 'data' && isset($data['payload']['metrics'])) {
            foreach ($data['payload']['metrics'] as $metric) {
                $this->latestMetrics[$metric['topic']] = $metric['value'];
            }
            if ($onMessage !== null) {
                $onMessage($data);
            }
        }
    }

    /**
     * REST API fallback – simpler and more reliable.
     */
    protected function restPoll(?callable $onTick, int $interval): void
    {
        Log::info('[SA] Using REST API fallback');

        while (true) {
            try {
                $response = \Illuminate\Support\Facades\Http::get("http://{$this->deviceIp}/api/status");
                if ($response->successful()) {
                    $metrics = $response->json();
                    // Convert to same format as WebSocket
                    $snapshot = [];
                    foreach ($metrics as $key => $value) {
                        $snapshot[$key] = $value;
                    }
                    if ($onTick !== null) {
                        $onTick($snapshot);
                    }
                    Log::info('[SA] REST snapshot stored at ' . now()->toDateTimeString());
                }
            } catch (Throwable $e) {
                Log::error('[SA] REST error: ' . $e->getMessage());
            }
            sleep($interval);
        }
    }

    public function close(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
