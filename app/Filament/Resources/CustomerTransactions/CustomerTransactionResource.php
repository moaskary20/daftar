<?php

namespace App\Filament\Resources\CustomerTransactions;

use App\Filament\Resources\CustomerTransactions\Pages\CreateCustomerTransaction;
use App\Filament\Resources\CustomerTransactions\Pages\EditCustomerTransaction;
use App\Filament\Resources\CustomerTransactions\Pages\ListCustomerTransactions;
use App\Filament\Resources\CustomerTransactions\Pages\ViewCustomerTransaction;
use App\Filament\Resources\CustomerTransactions\Schemas\CustomerTransactionForm;
use App\Filament\Resources\CustomerTransactions\Schemas\CustomerTransactionInfolist;
use App\Filament\Resources\CustomerTransactions\Tables\CustomerTransactionsTable;
use App\Models\CustomerTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerTransactionResource extends Resource
{
    protected static ?string $model = CustomerTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء';

    protected static ?string $navigationLabel = 'كشف حساب العملاء';

    protected static ?string $modelLabel = 'حركة عميل';

    protected static ?string $pluralModelLabel = 'كشف حساب العملاء';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CustomerTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerTransactionsTable::configure($table);
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
            'index' => ListCustomerTransactions::route('/'),
            'create' => CreateCustomerTransaction::route('/create'),
            'view' => ViewCustomerTransaction::route('/{record}'),
            'edit' => EditCustomerTransaction::route('/{record}/edit'),
        ];
    }
}
