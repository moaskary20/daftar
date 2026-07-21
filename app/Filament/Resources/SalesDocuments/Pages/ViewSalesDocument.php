<?php

namespace App\Filament\Resources\SalesDocuments\Pages;

use App\Filament\Resources\SalesDocuments\SalesDocumentResource;
use App\Filament\Support\DocumentWorkflowActions;
use App\Models\SalesDocument;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesDocument extends ViewRecord
{
    protected static string $resource = SalesDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...DocumentWorkflowActions::forSales($this->record),
            EditAction::make()
                ->label('تعديل')
                ->visible(fn (): bool => ! in_array($this->record->status, [
                    SalesDocument::STATUS_POSTED,
                    SalesDocument::STATUS_CANCELLED,
                    SalesDocument::STATUS_DELIVERED,
                ], true)),
        ];
    }
}
