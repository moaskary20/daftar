<?php

namespace App\Filament\Resources\PurchaseDocuments\Pages;

use App\Filament\Resources\PurchaseDocuments\PurchaseDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseDocuments extends ListRecords
{
    protected static string $resource = PurchaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
