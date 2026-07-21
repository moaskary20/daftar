<?php

namespace App\Filament\Resources\InstallmentPlans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InstallmentPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('sales_document_id')
                    ->numeric(),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('down_payment')
                    ->numeric(),
                TextEntry::make('installment_amount')
                    ->numeric(),
                TextEntry::make('installments_count')
                    ->numeric(),
                TextEntry::make('frequency'),
                TextEntry::make('first_due_date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
