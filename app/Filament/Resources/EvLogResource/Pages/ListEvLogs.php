<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use App\Models\EvLog;
use App\Models\EvLogItem;
use App\Models\Obd2Logs;
use App\Models\ObdItem;
use Filament\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Concerns\InteractsWithTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListEvLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('obdImport')
                ->label('Obd Import')
                ->form(\evlog::obdImportForm())
                ->action(function (array $data){
                    $evLog = EvLog::create($data);
                    EvLogResource::obdImport($data,$evLog);
//                    $csv = Reader::createFromPath(Storage::path($data['obd_file']),'r');
//                    $csv->setDelimiter(';');
//                    $obdFile = $data['obd_file'];
//                    $obdFileArray =explode("/",$obdFile);
//                    $obdFileName =end($obdFileArray);
//                    $obdFileNameArray = explode(".",$obdFileName);
//                    $data['date'] = $obdFileNameArray[0];
//                    $evLog = EvLog::create($data);
//                    foreach ($csv->getRecords() as $index=>$record){
//                        if($index >=200) break;
//                        $item = ObdItem::where('pid',$record[1])->first();
//                        if(!empty($item) && $item->id){
//                            $evLog->items()->firstOrCreate(
//                                ['item_id'=>$item->id],
//                                ['value'=>$record[2], 'latitude'=>$record[3], 'longitude'=>$record[4]]);
//                        }
//                    }

                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //EvLogResource\Widgets\EvLogOverview::class,
            EvLogResource\Widgets\ChargingCycleOverview::make(),
        ];
    }
}
