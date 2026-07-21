<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FinancialTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('journalEntry.id')
                    ->label('Journal entry')
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('number'),
                TextEntry::make('type'),
                TextEntry::make('source_fund_type')
                    ->placeholder('-'),
                TextEntry::make('source_fund_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('destination_fund_type')
                    ->placeholder('-'),
                TextEntry::make('destination_fund_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('beneficiary')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
