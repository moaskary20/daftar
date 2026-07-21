<?php

namespace App\Filament\Resources\WarehouseStocks;

use App\Filament\Resources\WarehouseStocks\Pages\ManageWarehouseStocks;
use App\Models\WarehouseStock;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WarehouseStockResource extends Resource
{
    protected static ?string $model = WarehouseStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخازن';

    protected static ?string $navigationLabel = 'أرصدة المخازن';

    protected static ?string $modelLabel = 'رصيد مخزن';

    protected static ?string $pluralModelLabel = 'أرصدة المخازن';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('product_id')
                    ->label('المنتج')
                    ->relationship('product', 'name')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Select::make('product_variant_id')
                    ->label('المتغير')
                    ->relationship('variant', 'name')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('quantity')
                    ->label('الرصيد')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reserved_quantity')
                    ->label('الكمية المحجوزة')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reorder_level')
                    ->label('حد إعادة الطلب')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bin_location')
                    ->label('موقع التخزين'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable(),
                TextColumn::make('variant.name')
                    ->label('المتغير')
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label('الرصيد')
                    ->numeric()
                    ->sortable()
                    ->color(fn (WarehouseStock $record): string => $record->is_low_stock ? 'danger' : 'success')
                    ->weight('bold'),
                TextColumn::make('reserved_quantity')
                    ->label('محجوز')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reorder_level')
                    ->label('حد إعادة الطلب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bin_location')
                    ->label('الموقع')
                    ->searchable(),
                IconColumn::make('is_low_stock')
                    ->label('تنبيه')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('low_stock')
                    ->label('أقل من حد إعادة الطلب')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('quantity', '<=', 'reorder_level')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('quantity');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWarehouseStocks::route('/'),
        ];
    }
}
