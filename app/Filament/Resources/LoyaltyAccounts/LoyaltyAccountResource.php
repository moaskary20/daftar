<?php

namespace App\Filament\Resources\LoyaltyAccounts;

use App\Filament\Resources\LoyaltyAccounts\Pages\CreateLoyaltyAccount;
use App\Filament\Resources\LoyaltyAccounts\Pages\EditLoyaltyAccount;
use App\Filament\Resources\LoyaltyAccounts\Pages\ListLoyaltyAccounts;
use App\Filament\Resources\LoyaltyAccounts\Pages\ViewLoyaltyAccount;
use App\Filament\Resources\LoyaltyAccounts\Schemas\LoyaltyAccountForm;
use App\Filament\Resources\LoyaltyAccounts\Schemas\LoyaltyAccountInfolist;
use App\Filament\Resources\LoyaltyAccounts\Tables\LoyaltyAccountsTable;
use App\Models\LoyaltyAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoyaltyAccountResource extends Resource
{
    protected static ?string $model = LoyaltyAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'نقطة البيع POS';

    protected static ?string $navigationLabel = 'نقاط الولاء';

    protected static ?string $modelLabel = 'حساب ولاء';

    protected static ?string $pluralModelLabel = 'نقاط ولاء العملاء';

    protected static ?int $navigationSort = 5;

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
        return LoyaltyAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoyaltyAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyAccountsTable::configure($table);
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
            'index' => ListLoyaltyAccounts::route('/'),
            'create' => CreateLoyaltyAccount::route('/create'),
            'view' => ViewLoyaltyAccount::route('/{record}'),
            'edit' => EditLoyaltyAccount::route('/{record}/edit'),
        ];
    }
}
