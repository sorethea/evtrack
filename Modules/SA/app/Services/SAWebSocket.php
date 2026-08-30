<?php

namespace Modules\SA\Services;
use WebSocket\Client;

class SAWebSocket
{
    protected Client $client;
    protected string $deviceIp;
    protected string $password;

    public function __construct()
    {
        $this->deviceIp = config('sa.device_ip');
        $this->password = config('sa.password');
    }

    public function listen(callable $onMessage): void
    {
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

    }

    public function close(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
