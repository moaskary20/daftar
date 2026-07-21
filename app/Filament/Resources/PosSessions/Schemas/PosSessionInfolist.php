<?php

namespace App\Filament\Resources\PosSessions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PosSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('pos_terminal_id')
                    ->numeric(),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('number'),
                TextEntry::make('status'),
                TextEntry::make('opening_balance')
                    ->numeric(),
                TextEntry::make('expected_balance')
                    ->numeric(),
                TextEntry::make('closing_balance')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('difference')
                    ->numeric(),
                TextEntry::make('opened_at')
                    ->dateTime(),
                TextEntry::make('closed_at')
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
