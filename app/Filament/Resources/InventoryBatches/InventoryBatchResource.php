<?php

namespace App\Filament\Resources\InventoryBatches;

use App\Filament\Resources\InventoryBatches\Pages\CreateInventoryBatch;
use App\Filament\Resources\InventoryBatches\Pages\EditInventoryBatch;
use App\Filament\Resources\InventoryBatches\Pages\ListInventoryBatches;
use App\Filament\Resources\InventoryBatches\Pages\ViewInventoryBatch;
use App\Filament\Resources\InventoryBatches\Schemas\InventoryBatchForm;
use App\Filament\Resources\InventoryBatches\Schemas\InventoryBatchInfolist;
use App\Filament\Resources\InventoryBatches\Tables\InventoryBatchesTable;
use App\Models\InventoryBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InventoryBatchResource extends Resource
{
    protected static ?string $model = InventoryBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخازن';

    protected static ?string $navigationLabel = 'دفعات الصلاحية';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'دفعة مخزون';

    protected static ?string $pluralModelLabel = 'دفعات المخزون والصلاحية';

    protected static ?string $recordTitleAttribute = 'batch_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryBatchesTable::configure($table);
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
            'index' => ListInventoryBatches::route('/'),
            'create' => CreateInventoryBatch::route('/create'),
            'view' => ViewInventoryBatch::route('/{record}'),
            'edit' => EditInventoryBatch::route('/{record}/edit'),
        ];
    }
}
