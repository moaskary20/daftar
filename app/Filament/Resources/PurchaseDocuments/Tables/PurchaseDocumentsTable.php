<?php

namespace App\Filament\Resources\PurchaseDocuments\Tables;

use App\Models\PurchaseDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم المستند')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => PurchaseDocument::typeLabels()[$state] ?? $state)
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => PurchaseDocument::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseDocument::STATUS_POSTED => 'success',
                        PurchaseDocument::STATUS_CANCELLED => 'danger',
                        PurchaseDocument::STATUS_APPROVED => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('document_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('supplier_reference')
                    ->label('مرجع المورد')
                    ->searchable(),
                TextColumn::make('shipping_cost')
                    ->label('الشحن')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('customs_cost')
                    ->label('الجمارك')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('expense_total')
                    ->label('المصروفات')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('بواسطة')
                    ->placeholder('النظام'),
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
                SelectFilter::make('type')
                    ->label('نوع المستند')
                    ->options(PurchaseDocument::typeLabels()),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(PurchaseDocument::statusLabels()),
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn (PurchaseDocument $record): bool => ! in_array($record->status, [
                        PurchaseDocument::STATUS_POSTED,
                        PurchaseDocument::STATUS_CANCELLED,
                    ], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('document_date', 'desc');
    }
}
