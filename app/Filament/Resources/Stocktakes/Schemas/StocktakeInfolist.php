<?php

namespace App\Filament\Resources\Stocktakes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StocktakeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('number'),
                TextEntry::make('type'),
                TextEntry::make('status'),
                TextEntry::make('stocktake_date')
                    ->date(),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
