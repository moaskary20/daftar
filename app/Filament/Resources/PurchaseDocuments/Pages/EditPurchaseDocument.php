<?php

namespace App\Filament\Resources\PurchaseDocuments\Pages;

use App\Filament\Resources\PurchaseDocuments\PurchaseDocumentResource;
use App\Filament\Support\DocumentWorkflowActions;
use App\Models\PurchaseDocument;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseDocument extends EditRecord
{
    protected static string $resource = PurchaseDocumentResource::class;

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (in_array($this->record->status, [
            PurchaseDocument::STATUS_POSTED,
            PurchaseDocument::STATUS_CANCELLED,
        ], true)) {
            $this->redirect(PurchaseDocumentResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ...DocumentWorkflowActions::forPurchases($this->record),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn (): bool => in_array($this->record->status, [
                    PurchaseDocument::STATUS_DRAFT,
                    PurchaseDocument::STATUS_APPROVED,
                ], true)),
        ];
    }
}
