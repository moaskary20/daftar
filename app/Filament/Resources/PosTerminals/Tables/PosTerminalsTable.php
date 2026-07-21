<?php

namespace App\Filament\Resources\PosTerminals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PosTerminalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->searchable(),
                TextColumn::make('treasury.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('receipt_size')
                    ->searchable(),
                TextColumn::make('printer_name')
                    ->searchable(),
                TextColumn::make('kitchen_printer_name')
                    ->searchable(),
                IconColumn::make('cash_drawer_enabled')
                    ->boolean(),
                IconColumn::make('customer_display_enabled')
                    ->boolean(),
                IconColumn::make('scale_enabled')
                    ->boolean(),
                IconColumn::make('offline_enabled')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
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
