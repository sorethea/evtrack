<?php

namespace App\Filament\Imports;

use App\Models\DrivingLog;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class DrivingLogImporter extends Importer
{
    protected static ?string $model = DrivingLog::class;

    public static function getColumns(): array
    {

        return [
            ImportColumn::make("date")
                ->rules(['required','date']),
        ];
    }

    public function resolveRecord(): ?DrivingLog
    {
        // return DrivingLog::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new DrivingLog();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your driving log import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
