<?php

namespace Modules\EV\Helpers;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use League\Csv\Reader;
use Modules\EV\Models\EvLogItem;
use Modules\EV\Models\ObdItem;

class EvLog
{
    public static function getItemValue(\Modules\EV\Models\EvLog $evLog, int $item_id):float {
        return $evLog->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getParentItemValue(\Modules\EV\Models\EvLog $evLog, int $item_id):float {
        return $evLog->parent->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getCycleItemValue(\Modules\EV\Models\EvLog $evLog, int $item_id):float {
        return $evLog->cycle->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getDistance(\Modules\EV\Models\EvLog $evLog):float
    {
        return (self::getItemValue($evLog,1) - self::getParentItemValue($evLog,1))??0;
    }

    public static function getCycleOverview($log):array
    {
        $distance = $log?->cycleView?->distance??0;
        $cycleDistanceArray = $log?->cycleView?->logs?->pluck('distance')->toArray();
        $soc = $log->detail->soc;
        $lastSoc = $log?->cycleView?->last_soc??0;
        $rootSoc = $log?->cycleView?->root_soc??0;
        $usedSoC = $rootSoc-$lastSoc;
        //$remainRange = ($rootSoc-$lastSoc)>0?$lastSoc * ($distance/($rootSoc-$lastSoc)):0;
        $vehicleCapacity = $log->vehicle->capacity;
        $cycleSoCArray = $log?->cycleView?->logs->pluck('soc')->toArray();
        $voltage =  $log->detail->voltage;
        $cycleVoltageArray = $log?->cycleView?->logs->pluck('voltage')->toArray();
        $avgVoltage = $voltage/200;
        $voltageBasedSoC = self::socVoltageBased($avgVoltage);
        $netDischarge = $log?->cycleView?->discharge - $log->cycleView?->charge;
        $remainRange = ($vehicleCapacity*$distance/$netDischarge)-$distance;
        $regenPercentage = $log?->cycleView?->discharge>0?100*$log?->cycleView?->charge/$log?->cycleView?->discharge:0 ;
        $cycleDischargeArray = $log?->cycleView?->logs->pluck('discharge')->toArray();
        $cycleConsumptionArray = $log?->cycleView?->logs->pluck('a_consumption')->toArray();
        $consumption = $log?->cycleView?->a_consumption;
        $socConsumption = $log?->cycleView?->consumption;
        $capacity = $log?->cycleView?->capacity;
        $cycleCapacityArray = $log?->cycleView?->logs->pluck('capacity')->toArray();
        $capacityVariant = Number::format(100*($capacity-$vehicleCapacity)/$vehicleCapacity,1);
        $deltaVoltage = 1000*$log?->cycleView?->v_spread??0;
        $deltaTemp = 1000*$log?->cycleView?->t_spread??0;
        return [
            Stat::make(trans('ev.distance'),Number::format($distance).'km')
                ->color(Color::Green)
                ->description('Remaining range: '.Number::format($remainRange,1).' km')
                ->chart($cycleDistanceArray),
            Stat::make(trans('ev.soc').'('.$rootSoc.'%)',Number::format($lastSoc).'%')
                ->description('Cell voltage based SoC: '.Number::format($voltageBasedSoC,1).'%')
                ->color(Color::Red)
                ->chart($cycleSoCArray),
            Stat::make(trans('ev.battery_voltage')."({$log->cycleView->root_voltage}V)",Number::format($voltage).'V')
                ->color(Color::Yellow)
                ->description('Average cell voltage: '.Number::format($avgVoltage,3).'V')
                ->chart($cycleVoltageArray),
            Stat::make(trans('ev.used_energy'),Number::format($netDischarge).'kWh')
                ->description("Added({$log?->cycleView?->charge})/Gross({$log?->cycleView?->discharge}): ".Number::format($regenPercentage??0,1).'%')
                ->chart($cycleDischargeArray)
                ->color(Color::Teal),
            Stat::make(trans('ev.consumption'),Number::format($consumption,0).'Wh/km')
                ->description("SoC based: ".Number::format($socConsumption,0)."Wh/km")
                ->chart($cycleConsumptionArray)
                ->color(Color::Cyan),
            Stat::make(trans('ev.capacity'),Number::format($capacity,1).'kWh')
                ->description( "Capacity variant: {$capacityVariant}%")
                ->chart($cycleCapacityArray)
                ->color(Color::Pink),
            Stat::make(trans('ev.voltage'),Number::format($deltaVoltage,0).'mV')
                ->description( "Highest cell voltage: {$log?->cycleView?->last_hvc}V")
                ->chart($cycleCapacityArray)
                ->color(Color::Purple),
            Stat::make(trans('ev.temperature'),Number::format($deltaTemp,0).'C')
                ->description( "Capacity variant: {$capacityVariant}%")
                ->chart($cycleCapacityArray)
                ->color(Color::Orange),
        ];
    }

    public static function socVoltageBased($voltage):float {
        $table = config('ev.socVoltage');
// Find the range where the voltage falls
        $lowerSoc = null;
        $upperSoc = null;

// Sort by voltage in descending order
        arsort($table);

        foreach ($table as $soc => $v) {
            if ($voltage <= $v) {
                $upperSoc = $soc;
                $upperVoltage = $v;
            } else {
                $lowerSoc = $soc;
                $lowerVoltage = $v;
                break;
            }
        }

// If voltage is outside the range
        if ($upperSoc === null) {
            $estimatedSoc = 100;
        } elseif ($lowerSoc === null) {
            $estimatedSoc = 0;
        } else {
            // Linear interpolation
            $voltageRange = $upperVoltage - $lowerVoltage;
            $socRange = $upperSoc - $lowerSoc;
            $voltageDiff = $voltage - $lowerVoltage;

            $estimatedSoc = $lowerSoc + ($voltageDiff / $voltageRange) * $socRange;
        }
        return $estimatedSoc;

    }

    public static function obdImportAction(array $data, \Modules\EV\Models\EvLog $evLog): void
    {
        $csv = Reader::createFromPath(Storage::path($data['obd_file']), 'r');
        $csv->setDelimiter(';');
        $obdFile = $data['obd_file'];
        $obdFileArray = explode("/", $obdFile);
        $obdFileName = end($obdFileArray);
        $obdFileNameArray = explode(".", $obdFileName);
        $evLog->update([
            'date' => $obdFileNameArray[0],
            'obd_file' => $obdFile,
        ]);
        foreach ($csv->getRecords() as $index => $row) {
            //if($index >=200) break;
            $item = ObdItem::where('pid', $row[1])->first();
            if (!empty($item) && $item->id && $evLog->id) {
                $latitude = !empty($row[4]) ? $row[4] : 0.0;
                $longitude = !empty($row[5]) ? $row[5] : 0.0;
                EvLogItem::query()->firstOrCreate(
                    ['item_id' => $item->id, 'log_id' => $evLog->id],
                    ['value' => $row[2], 'latitude' => $latitude, 'longitude' => $longitude]);
            }
        }
    }

    public static function obdImportForm():array
    {
        return [
            Fieldset::make()->schema([
//                        TextInput::make('date')
//                            ->label(trans('ev.date'))
//                            ->required(),
                Select::make("log_type")
                    ->live()
                    ->label(trans('ev.log_types.name'))
                    ->options(trans("ev.log_types.options"))
                    ->default('driving')
                    ->nullable(),
                Select::make('parent_id')
                    ->label(trans('ev.parent'))
                    ->options(\Modules\EV\Models\EvLog::select(['id','date'])->orderBy('date','desc')->get()->pluck('date','id'))
                    ->default(fn()=> \Modules\EV\Models\EvLog::max('id'))
                    ->searchable(['id','date'])
                    ->nullable(),
                Select::make("cycle_id")
                    ->reactive()
                    ->label(trans('ev.cycle'))
                    ->options(\Modules\EV\Models\EvLog::select(['id','date'])->where('log_type','charging')->orderBy('date','desc')->get()->pluck('date','id'))
                    //->relationship('cycle','date')
                    ->hidden(fn(Get $get)=>$get("log_type")=="charging")
                    ->default(fn()=> \Modules\EV\Models\EvLog::where("log_type","charging")->max('id'))
                    ->searchable(['id','date'])
                    ->nullable(),
                Select::make("charge_type")
                    ->label(trans('ev.charge_types.name'))
                    ->options(trans("ev.charge_types.options"))
                    ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                    ->nullable(),
                FileUpload::make('obd_file')
                    ->preserveFilenames()
                    ->disk('local')
                    ->directory('obd2'),
            ])->columns(2),

        ];
    }
}
