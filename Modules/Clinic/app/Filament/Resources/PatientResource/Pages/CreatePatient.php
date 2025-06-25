<?php

namespace Modules\Clinic\Filament\Resources\PatientResource\Pages;

use Modules\Clinic\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
}
