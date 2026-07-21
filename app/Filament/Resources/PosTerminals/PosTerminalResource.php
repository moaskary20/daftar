<?php

namespace App\Filament\Resources\PosTerminals;

use App\Filament\Resources\PosTerminals\Pages\CreatePosTerminal;
use App\Filament\Resources\PosTerminals\Pages\EditPosTerminal;
use App\Filament\Resources\PosTerminals\Pages\ListPosTerminals;
use App\Filament\Resources\PosTerminals\Schemas\PosTerminalForm;
use App\Filament\Resources\PosTerminals\Tables\PosTerminalsTable;
use App\Models\PosTerminal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosTerminalResource extends Resource
{
    protected static ?string $model = PosTerminal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|\UnitEnum|null $navigationGroup = 'نقطة البيع POS';

    protected static ?string $navigationLabel = 'إعدادات نقاط البيع';

    protected static ?string $modelLabel = 'نقطة بيع';

    protected static ?string $pluralModelLabel = 'نقاط البيع';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return PosTerminalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosTerminalsTable::configure($table);
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
            'index' => ListPosTerminals::route('/'),
            'create' => CreatePosTerminal::route('/create'),
            'edit' => EditPosTerminal::route('/{record}/edit'),
        ];
    }
}
