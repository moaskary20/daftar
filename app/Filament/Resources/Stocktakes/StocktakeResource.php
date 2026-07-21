<?php

namespace App\Filament\Resources\Stocktakes;

use App\Filament\Resources\Stocktakes\Pages\CreateStocktake;
use App\Filament\Resources\Stocktakes\Pages\EditStocktake;
use App\Filament\Resources\Stocktakes\Pages\ListStocktakes;
use App\Filament\Resources\Stocktakes\Pages\ViewStocktake;
use App\Filament\Resources\Stocktakes\Schemas\StocktakeForm;
use App\Filament\Resources\Stocktakes\Schemas\StocktakeInfolist;
use App\Filament\Resources\Stocktakes\Tables\StocktakesTable;
use App\Models\Stocktake;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StocktakeResource extends Resource
{
    protected static ?string $model = Stocktake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخازن';

    protected static ?string $navigationLabel = 'الجرد';

    protected static ?string $modelLabel = 'عملية جرد';

    protected static ?string $pluralModelLabel = 'عمليات الجرد';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return StocktakeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StocktakeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StocktakesTable::configure($table);
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
            'index' => ListStocktakes::route('/'),
            'create' => CreateStocktake::route('/create'),
            'view' => ViewStocktake::route('/{record}'),
            'edit' => EditStocktake::route('/{record}/edit'),
        ];
    }
}
