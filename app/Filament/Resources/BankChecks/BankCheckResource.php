<?php

namespace App\Filament\Resources\BankChecks;

use App\Filament\Resources\BankChecks\Pages\CreateBankCheck;
use App\Filament\Resources\BankChecks\Pages\EditBankCheck;
use App\Filament\Resources\BankChecks\Pages\ListBankChecks;
use App\Filament\Resources\BankChecks\Pages\ViewBankCheck;
use App\Filament\Resources\BankChecks\Schemas\BankCheckForm;
use App\Filament\Resources\BankChecks\Schemas\BankCheckInfolist;
use App\Filament\Resources\BankChecks\Tables\BankChecksTable;
use App\Models\BankCheck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankCheckResource extends Resource
{
    protected static ?string $model = BankCheck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'الحسابات';

    protected static ?string $navigationLabel = 'الشيكات';

    protected static ?string $modelLabel = 'شيك';

    protected static ?string $pluralModelLabel = 'الشيكات';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return BankCheckForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankCheckInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankChecksTable::configure($table);
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
            'index' => ListBankChecks::route('/'),
            'create' => CreateBankCheck::route('/create'),
            'view' => ViewBankCheck::route('/{record}'),
            'edit' => EditBankCheck::route('/{record}/edit'),
        ];
    }
}
