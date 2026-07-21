<?php

namespace App\Filament\Resources\ChartAccounts\Tables;

use App\Models\ChartAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChartAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('الحساب')
                    ->formatStateUsing(fn ($state, ChartAccount $record): string => str_repeat('— ', $record->depth).$state)
                    ->weight(fn (ChartAccount $record): string => $record->is_group ? 'bold' : 'medium')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('التصنيف')
                    ->formatStateUsing(fn ($state): string => ChartAccount::typeLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('normal_balance')
                    ->label('الطبيعة')
                    ->formatStateUsing(fn ($state): string => $state === 'debit' ? 'مدين' : 'دائن'),
                TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->money('EGP')
                    ->weight('bold'),
                IconColumn::make('is_group')
                    ->label('تجميعي')
                    ->boolean(),
                IconColumn::make('allow_posting')
                    ->label('ترحيل')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('opening_debit')
                    ->label('افتتاحي مدين')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('opening_credit')
                    ->label('افتتاحي دائن')
                    ->money('EGP')
                    ->sortable(),
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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
