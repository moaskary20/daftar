<?php

namespace App\Filament\Resources\SalesDeliveries\Pages;

use App\Filament\Resources\SalesDeliveries\SalesDeliveryResource;
use App\Models\SalesDelivery;
use App\Services\SalesService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesDelivery extends EditRecord
{
    protected static string $resource = SalesDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('اعتماد التسليم')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('سيتم صرف الكميات من المخزن وتحديث حالة أمر البيع.')
                ->visible(fn (): bool => $this->record->status === SalesDelivery::STATUS_DRAFT)
                ->action(function (): void {
                    app(SalesService::class)->completeDelivery($this->record->fresh(['items.documentItem.product', 'document.items']));
                    $this->redirect(SalesDeliveryResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn (): bool => $this->record->status === SalesDelivery::STATUS_DRAFT),
        ];
    }
}
