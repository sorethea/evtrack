<?php

namespace Modules\SA\Console;

use Illuminate\Console\Command;
use Modules\SA\Models\Metric;
use Modules\SA\Services\SAWebSocket;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ListenSolar extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'solar:listen';

    /**
     * The console command description.
     */
    protected $description = 'Listen to Solar Websocket and store metrics.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(SAWebSocket $wsClient) {
        $this->info('SA Module: WebSocket listener started...');

        $wsClient->listen(null,function (array $message) {
            $this->storeMetrics($message);
            $this->info('Metric stored at ' . now()->toDateTimeString());
        });
    }

    protected function storeMetrics(array $metrics): void
    {
        foreach ($metrics as $topic => $value) {
            // Update or create a record for the latest value
            Metric::create(
                ['topic' => $topic],
                ['value' => $value, 'updated_at' => now()]
            );
        }
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
