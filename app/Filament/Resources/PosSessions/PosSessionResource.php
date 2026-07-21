<?php

namespace App\Filament\Resources\PosSessions;

use App\Filament\Resources\PosSessions\Pages\CreatePosSession;
use App\Filament\Resources\PosSessions\Pages\EditPosSession;
use App\Filament\Resources\PosSessions\Pages\ListPosSessions;
use App\Filament\Resources\PosSessions\Pages\ViewPosSession;
use App\Filament\Resources\PosSessions\Schemas\PosSessionForm;
use App\Filament\Resources\PosSessions\Schemas\PosSessionInfolist;
use App\Filament\Resources\PosSessions\Tables\PosSessionsTable;
use App\Models\PosSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PosSessionResource extends Resource
{
    protected static ?string $model = PosSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'نقطة البيع POS';

    protected static ?string $navigationLabel = 'ورديات الكاشير';

    protected static ?string $modelLabel = 'وردية';

    protected static ?string $pluralModelLabel = 'ورديات الكاشير';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PosSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PosSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosSessionsTable::configure($table);
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
            'index' => ListPosSessions::route('/'),
            'create' => CreatePosSession::route('/create'),
            'view' => ViewPosSession::route('/{record}'),
            'edit' => EditPosSession::route('/{record}/edit'),
        ];
    }
}
