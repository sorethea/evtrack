<?php

namespace Modules\EV5\Filament\Resources\HealthTests;

use App\Models\HealthTest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\EV5\Filament\Resources\HealthTests\Pages\CreateHealthTest;
use Modules\EV5\Filament\Resources\HealthTests\Pages\EditHealthTest;
use Modules\EV5\Filament\Resources\HealthTests\Pages\ListHealthTests;
use Modules\EV5\Filament\Resources\HealthTests\Schemas\HealthTestForm;
use Modules\EV5\Filament\Resources\HealthTests\Tables\HealthTestsTable;

class HealthTestResource extends Resource
{
    protected static ?string $model = HealthTest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'HealthTest';

    public static function form(Schema $schema): Schema
    {
        return HealthTestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HealthTestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHealthTests::route('/'),
            'create' => CreateHealthTest::route('/create'),
            'edit' => EditHealthTest::route('/{record}/edit'),
        ];
    }
}
