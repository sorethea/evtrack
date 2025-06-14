<?php

namespace App\Filament\Imports;

use App\Models\Obd2Logs;
use App\Models\ObdItem;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use League\Csv\Exception;
use League\Csv\Reader;

class Obd2LogsImporter extends Importer
{
    protected static ?string $model = Obd2Logs::class;

    protected int $count =0;
    protected int $limit =300;


    public static function getColumns(): array
    {
        return [
            ImportColumn::make('seconds')
                //->requiredMapping()
                ->label('SECONDS'),
            ImportColumn::make('pid')
                ->label("PID"),
                //->requiredMapping(),
            ImportColumn::make('value')
                ->label("VALUE"),
                //->requiredMapping(),
        ];
    }


    public function resolveRecord(): ?Obd2Logs
    {

            return Obd2Logs::firstOrCreate(['pid'=>$this->data['PID'],'value' => $this->data['VALUE']]);

//        return new Obd2Logs();


    }


    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your obd2 logs import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
