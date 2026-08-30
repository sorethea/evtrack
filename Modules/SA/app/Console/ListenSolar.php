<?php

namespace Modules\SA\Console;

use Illuminate\Console\Command;
use Modules\SA\Models\Metric;
use Modules\SA\Services\SAWebSocket;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ListenSolar extends Command
{
    protected $signature = 'solar:listen';
    protected $description = 'Listen to Solar Assistant and store data every 5 seconds';

    public function handle(SAWebSocket $ws)
    {
        $this->info('[SA] WebSocket listener started – storing every 5 seconds...');

        $ws->listen(
            null,
            function (array $latestMetrics) {
                $this->storeSnapshot($latestMetrics);
                $this->info('[SA] Snapshot stored at ' . now()->toDateTimeString());
            },
            5
        );
    }

    protected function storeSnapshot(array $metrics): void
    {
        foreach ($metrics as $topic => $value) {
            Metric::updateOrCreate(
                ['topic' => $topic],
                ['value' => $value]
            );
        }
    }
}
