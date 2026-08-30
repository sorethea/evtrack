<?php

namespace Modules\SA\Services;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class SAWebSocket
{
    protected Client $client;
    protected string $deviceIp;
    protected string $password;
    protected array $latestMetrics = [];

    public function __construct()
    {
        $this->deviceIp = config('sa.device_ip');
        $this->password = config('sa.password');
    }

    public function listen(?callable $onMessage=null,?callable $onTick=null,int $interval =5): void
    {
        $this->client = new Client("ws://{$this->deviceIp}/api/websocket?password={$this->password}");
        $joinMessage = json_encode([
            'topic'   => 'metrics',
            'event'   => 'phx_join',
            'payload' => [
                // Optional: filter topics to reduce load
                // 'topics' => [['topic' => 'total/*'], ['topic' => 'battery_1/*']]
            ],
            'ref'     => '1'
        ]);
        $this->client->text($joinMessage);
        $lastTick = microtime(true);
        while (true) {
            try {
                $response = $this->client->receive();
                $data = json_decode($response, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($data['event'])) {
                    $onMessage($data);
                }
            } catch (\Exception $e) {
                Log::error('SolarAssistant WebSocket error: ' . $e->getMessage());
                // Optionally break and let Supervisor restart the process
                break;
            }
        }
        // Check if it's time to fire the tick
        $now = microtime(true);
        if ($now - $lastTick >= $interval && $onTick !== null) {
            // Call the tick callback with the latest snapshot
            $onTick($this->latestMetrics);
            $lastTick = $now;
        }

        // Prevent CPU spinning
        usleep(10000); // 10ms

    }

    public function close(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
