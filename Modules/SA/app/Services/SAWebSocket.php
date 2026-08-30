<?php

namespace Modules\SA\Services;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;
use WebSocket\TimeoutException;

class SAWebSocket
{
    protected Client $client;
    protected string $deviceIp;
    protected string $password;
    protected array $latestMetrics = [];

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
        while (true) { // Outer loop – reconnects forever
            try {
                Log::info('[SA] Connecting to Solar Assistant...');
                $this->client = new Client("ws://{$this->deviceIp}/api/websocket?password={$this->password}");

                $joinMessage = json_encode([
                    'topic'   => 'metrics',
                    'event'   => 'phx_join',
                    'payload' => [],
                    'ref'     => '1'
                ]);
                $this->client->text($joinMessage);
                Log::info('[SA] Joined metrics channel');

                $lastTick = microtime(true);

                // Inner loop – receive messages and fire ticks
                while (true) {
                    try {
                        $response = $this->client->receive(0.1); // non‑blocking

                        if ($response) {
                            $data = json_decode($response, true);
                            if (isset($data['event']) && $data['event'] === 'data' && isset($data['payload']['metrics'])) {
                                foreach ($data['payload']['metrics'] as $metric) {
                                    $this->latestMetrics[$metric['topic']] = $metric['value'];
                                }
                                if ($onMessage !== null) {
                                    $onMessage($data);
                                }
                            }
                        }
                    } catch (TimeoutException $e) {
                        // No message – that's fine
                    } catch (\Exception $e) {
                        Log::error('[SA] Receive error: ' . $e->getMessage());
                        break; // break inner loop and reconnect
                    }

                    // Fire tick every $interval seconds
                    $now = microtime(true);
                    if ($now - $lastTick >= $interval && $onTick !== null) {
                        $onTick($this->latestMetrics);
                        Log::info('[SA] Tick executed at ' . now()->toDateTimeString());
                        $lastTick = $now;
                    }

                    usleep(10000); // 10ms – prevent CPU overuse
                }
            } catch (\Exception $e) {
                Log::error('[SA] Connection lost – reconnecting in 5 seconds: ' . $e->getMessage());
                sleep(5);
                // outer loop retries
            }
        }
    }

    public function close(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
