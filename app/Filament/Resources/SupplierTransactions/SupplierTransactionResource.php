<?php

namespace App\Filament\Resources\SupplierTransactions;

use App\Filament\Resources\SupplierTransactions\Pages\CreateSupplierTransaction;
use App\Filament\Resources\SupplierTransactions\Pages\EditSupplierTransaction;
use App\Filament\Resources\SupplierTransactions\Pages\ListSupplierTransactions;
use App\Filament\Resources\SupplierTransactions\Pages\ViewSupplierTransaction;
use App\Filament\Resources\SupplierTransactions\Schemas\SupplierTransactionForm;
use App\Filament\Resources\SupplierTransactions\Schemas\SupplierTransactionInfolist;
use App\Filament\Resources\SupplierTransactions\Tables\SupplierTransactionsTable;
use App\Models\SupplierTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplierTransactionResource extends Resource
{
    protected static ?string $model = SupplierTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'حسابات ومدفوعات الموردين';

    protected static ?string $modelLabel = 'حركة مورد';

    protected static ?string $pluralModelLabel = 'كشف حساب الموردين';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SupplierTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierTransactionsTable::configure($table);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
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
            'index' => ListSupplierTransactions::route('/'),
            'create' => CreateSupplierTransaction::route('/create'),
            'view' => ViewSupplierTransaction::route('/{record}'),
            'edit' => EditSupplierTransaction::route('/{record}/edit'),
        ];
    }
}
