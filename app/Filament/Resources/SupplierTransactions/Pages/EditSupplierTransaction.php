<?php

namespace App\Filament\Resources\SupplierTransactions\Pages;

use App\Filament\Resources\SupplierTransactions\SupplierTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierTransaction extends EditRecord
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
