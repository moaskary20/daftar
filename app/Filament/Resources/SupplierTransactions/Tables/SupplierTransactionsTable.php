<?php

namespace App\Filament\Resources\SupplierTransactions\Tables;

use App\Models\SupplierTransaction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم الحركة')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->formatStateUsing(fn ($state): string => SupplierTransaction::labels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('debit')
                    ->label('مدين')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('credit')
                    ->label('دائن')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->label('الرصيد بعد الحركة')
                    ->money('EGP')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->searchable(),
                TextColumn::make('check_number')
                    ->label('رقم الشيك')
                    ->searchable(),
                TextColumn::make('check_due_date')
                    ->label('استحقاق الشيك')
                    ->date()
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label('البنك')
                    ->searchable(),
                TextColumn::make('check_status')
                    ->label('حالة الشيك')
                    ->formatStateUsing(fn ($state): string => SupplierTransaction::checkStatusLabels()[$state] ?? $state)
                    ->badge(),
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
                SelectFilter::make('supplier_id')->label('المورد')->relationship('supplier', 'name')->searchable()->preload(),
                SelectFilter::make('type')->label('نوع الحركة')->options(SupplierTransaction::labels()),
                SelectFilter::make('check_status')->label('حالة الشيك')->options(SupplierTransaction::checkStatusLabels()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
