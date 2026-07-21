<?php

namespace App\Filament\Resources\SalesDocuments\Pages;

use App\Filament\Resources\SalesDocuments\SalesDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesDocument extends CreateRecord
{
    protected static string $resource = SalesDocumentResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
