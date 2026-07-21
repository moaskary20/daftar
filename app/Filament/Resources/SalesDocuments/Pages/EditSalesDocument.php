<?php

namespace App\Filament\Resources\SalesDocuments\Pages;

use App\Filament\Resources\SalesDocuments\SalesDocumentResource;
use App\Filament\Support\DocumentWorkflowActions;
use App\Models\SalesDocument;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesDocument extends EditRecord
{
    protected static string $resource = SalesDocumentResource::class;

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (in_array($this->record->status, [
            SalesDocument::STATUS_POSTED,
            SalesDocument::STATUS_CANCELLED,
            SalesDocument::STATUS_DELIVERED,
        ], true)) {
            $this->redirect(SalesDocumentResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ...DocumentWorkflowActions::forSales($this->record),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn (): bool => in_array($this->record->status, [
                    SalesDocument::STATUS_DRAFT,
                    SalesDocument::STATUS_APPROVED,
                ], true)),
        ];
    }
}
