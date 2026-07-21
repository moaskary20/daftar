<?php

namespace App\Filament\Resources\ProductSerials;

use App\Filament\Clusters\ProductManagement;
use App\Filament\Resources\ProductSerials\Pages\CreateProductSerial;
use App\Filament\Resources\ProductSerials\Pages\EditProductSerial;
use App\Filament\Resources\ProductSerials\Pages\ListProductSerials;
use App\Filament\Resources\ProductSerials\Schemas\ProductSerialForm;
use App\Filament\Resources\ProductSerials\Tables\ProductSerialsTable;
use App\Models\ProductSerial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductSerialResource extends Resource
{
    protected static ?string $model = ProductSerial::class;

    protected static ?string $cluster = ProductManagement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'الأرقام التسلسلية';

    protected static ?string $modelLabel = 'رقم تسلسلي';

    protected static ?string $pluralModelLabel = 'الأرقام التسلسلية';

    protected static ?string $recordTitleAttribute = 'serial_number';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return ProductSerialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSerialsTable::configure($table);
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
            'index' => ListProductSerials::route('/'),
            'create' => CreateProductSerial::route('/create'),
            'edit' => EditProductSerial::route('/{record}/edit'),
        ];
    }
}
