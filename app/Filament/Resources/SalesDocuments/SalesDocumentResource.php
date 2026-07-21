<?php

namespace App\Filament\Resources\SalesDocuments;

use App\Filament\Resources\SalesDocuments\Pages\CreateSalesDocument;
use App\Filament\Resources\SalesDocuments\Pages\EditSalesDocument;
use App\Filament\Resources\SalesDocuments\Pages\ListSalesDocuments;
use App\Filament\Resources\SalesDocuments\Pages\ViewSalesDocument;
use App\Filament\Resources\SalesDocuments\Schemas\SalesDocumentForm;
use App\Filament\Resources\SalesDocuments\Schemas\SalesDocumentInfolist;
use App\Filament\Resources\SalesDocuments\Tables\SalesDocumentsTable;
use App\Models\SalesDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SalesDocumentResource extends Resource
{
    protected static ?string $model = SalesDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'مستندات المبيعات';

    protected static ?string $modelLabel = 'مستند بيع';

    protected static ?string $pluralModelLabel = 'مستندات المبيعات';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SalesDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesDocumentsTable::configure($table);
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
            'index' => ListSalesDocuments::route('/'),
            'create' => CreateSalesDocument::route('/create'),
            'view' => ViewSalesDocument::route('/{record}'),
            'edit' => EditSalesDocument::route('/{record}/edit'),
        ];
    }
}
