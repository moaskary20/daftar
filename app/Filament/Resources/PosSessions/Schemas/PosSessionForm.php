<?php

namespace App\Filament\Resources\PosSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PosSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pos_terminal_id')
                    ->required()
                    ->numeric(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->required(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('opening_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('expected_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('closing_balance')
                    ->numeric(),
                TextInput::make('difference')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('opened_at')
                    ->required(),
                DateTimePicker::make('closed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
