<?php

namespace App\Filament\Resources\InstallmentPlans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstallmentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sales_document_id')
                    ->required()
                    ->numeric(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('down_payment')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('installment_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('installments_count')
                    ->required()
                    ->numeric(),
                TextInput::make('frequency')
                    ->required()
                    ->default('monthly'),
                DatePicker::make('first_due_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
