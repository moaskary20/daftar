<?php

namespace App\Filament\Resources\CustomerTransactions\Tables;

use App\Models\CustomerTransaction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('رقم الحركة')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->formatStateUsing(fn ($state): string => CustomerTransaction::labels()[$state] ?? $state)
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
                SelectFilter::make('customer_id')->label('العميل')->relationship('customer', 'name')->searchable()->preload(),
                SelectFilter::make('type')->label('نوع الحركة')->options(CustomerTransaction::labels()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
