<?php

namespace App\Filament\Resources\CustomerTransactions\Pages;

use App\Filament\Resources\CustomerTransactions\CustomerTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerTransaction extends ViewRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
