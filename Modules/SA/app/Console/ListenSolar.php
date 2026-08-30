<?php

namespace Modules\SA\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\SA\Models\Metric;
use Modules\SA\Services\SAWebSocket;
class ListenSolar extends Command
{
    protected $signature = 'solar:listen';
    protected $description = 'Listen to Solar Assistant and store JSON snapshots every 5 seconds';

    public function handle(SAWebSocket $ws)
    {
        $this->info('[SA] WebSocket listener started – storing JSON snapshots every 5s...');
        $ws->listen(
            null,
            function (array $latestMetrics) {
                $this->storeSnapshot($latestMetrics);
                $this->info('[SA] Metrics stored at ' . now()->toDateTimeString());
            },
            5
        );
    }

    protected function storeSnapshot(array $metrics): void
    {
        if (empty($metrics)) {
            return;
        }
        $this->info($metrics);

        Metric::create([
            'recorded_at' => now(),
            'metadata' => $metrics, // Eloquent will automatically JSON‑encode this
        ]);
    }
}
