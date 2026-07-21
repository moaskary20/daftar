<?php

namespace App\Filament\Resources\PurchaseDocuments\Pages;

use App\Filament\Resources\PurchaseDocuments\PurchaseDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseDocument extends CreateRecord
{
    protected static string $resource = PurchaseDocumentResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
