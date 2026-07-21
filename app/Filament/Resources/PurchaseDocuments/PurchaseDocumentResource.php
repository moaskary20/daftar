<?php

namespace App\Filament\Resources\PurchaseDocuments;

use App\Filament\Resources\PurchaseDocuments\Pages\CreatePurchaseDocument;
use App\Filament\Resources\PurchaseDocuments\Pages\EditPurchaseDocument;
use App\Filament\Resources\PurchaseDocuments\Pages\ListPurchaseDocuments;
use App\Filament\Resources\PurchaseDocuments\Pages\ViewPurchaseDocument;
use App\Filament\Resources\PurchaseDocuments\Schemas\PurchaseDocumentForm;
use App\Filament\Resources\PurchaseDocuments\Schemas\PurchaseDocumentInfolist;
use App\Filament\Resources\PurchaseDocuments\Tables\PurchaseDocumentsTable;
use App\Models\PurchaseDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseDocumentResource extends Resource
{
    protected static ?string $model = PurchaseDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'مستندات المشتريات';

    protected static ?string $modelLabel = 'مستند شراء';

    protected static ?string $pluralModelLabel = 'مستندات المشتريات';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PurchaseDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseDocumentsTable::configure($table);
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
            'index' => ListPurchaseDocuments::route('/'),
            'create' => CreatePurchaseDocument::route('/create'),
            'view' => ViewPurchaseDocument::route('/{record}'),
            'edit' => EditPurchaseDocument::route('/{record}/edit'),
        ];
    }
}
