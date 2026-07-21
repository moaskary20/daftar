<?php

namespace App\Filament\Resources\PurchaseDocuments\Pages;

use App\Filament\Resources\PurchaseDocuments\PurchaseDocumentResource;
use App\Filament\Support\DocumentWorkflowActions;
use App\Models\PurchaseDocument;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseDocument extends ViewRecord
{
    protected static string $resource = PurchaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...DocumentWorkflowActions::forPurchases($this->record),
            EditAction::make()
                ->label('تعديل')
                ->visible(fn (): bool => ! in_array($this->record->status, [
                    PurchaseDocument::STATUS_POSTED,
                    PurchaseDocument::STATUS_CANCELLED,
                ], true)),
        ];
    }
}
