<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('الصورة')
                    ->square()
                    ->defaultImageUrl(fn (Product $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=5458f0&color=fff'),
                TextColumn::make('name')
                    ->label('المنتج')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => $record->sku),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Product::TYPE_VARIABLE ? 'متغير' : 'بسيط')
                    ->color(fn (string $state): string => $state === Product::TYPE_VARIABLE ? 'info' : 'gray'),
                TextColumn::make('barcode')
                    ->label('الباركود')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->badge()
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->label('الماركة')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('selling_price')
                    ->label('سعر البيع')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('المخزون')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Product $record): string => $record->track_stock && $record->stock_quantity <= $record->minimum_stock ? 'danger' : 'success')
                    ->weight('bold'),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('primarySupplier.name')
                    ->label('المورد')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('department.name')
                    ->label('القسم')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('average_cost')
                    ->label('متوسط التكلفة')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع المنتج')
                    ->options([
                        Product::TYPE_SIMPLE => 'بسيط',
                        Product::TYPE_VARIABLE => 'متغير',
                    ]),
                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand')
                    ->label('الماركة')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                Action::make('printLabels')
                    ->label('طباعة الباركود')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Product $record): string => route('products.labels', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
                    ForceDeleteBulkAction::make()->label('حذف نهائي'),
                    RestoreBulkAction::make()->label('استعادة'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
