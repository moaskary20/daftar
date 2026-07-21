<?php

namespace App\Filament\Resources\SalesDeliveries;

use App\Filament\Resources\SalesDeliveries\Pages\CreateSalesDelivery;
use App\Filament\Resources\SalesDeliveries\Pages\EditSalesDelivery;
use App\Filament\Resources\SalesDeliveries\Pages\ListSalesDeliveries;
use App\Filament\Resources\SalesDeliveries\Pages\ViewSalesDelivery;
use App\Filament\Resources\SalesDeliveries\Schemas\SalesDeliveryForm;
use App\Filament\Resources\SalesDeliveries\Schemas\SalesDeliveryInfolist;
use App\Filament\Resources\SalesDeliveries\Tables\SalesDeliveriesTable;
use App\Models\SalesDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SalesDeliveryResource extends Resource
{
    protected static ?string $model = SalesDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'التسليمات';

    protected static ?string $modelLabel = 'تسليم';

    protected static ?string $pluralModelLabel = 'تسليمات المبيعات';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SalesDeliveryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesDeliveryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesDeliveriesTable::configure($table);
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
            'index' => ListSalesDeliveries::route('/'),
            'create' => CreateSalesDelivery::route('/create'),
            'view' => ViewSalesDelivery::route('/{record}'),
            'edit' => EditSalesDelivery::route('/{record}/edit'),
        ];
    }
}
