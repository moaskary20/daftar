<?php

namespace App\Filament\Resources\SupplierTransactions\Pages;

use App\Filament\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierTransaction extends ViewRecord
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
