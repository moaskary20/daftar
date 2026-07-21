<?php

namespace App\Filament\Resources\CustomerTransactions\Pages;

use App\Filament\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerTransactions extends ListRecords
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
