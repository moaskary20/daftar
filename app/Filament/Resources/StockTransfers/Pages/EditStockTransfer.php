<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStockTransfer extends EditRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('اعتماد التحويل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('سيتم خصم الكميات من المخزن المصدر وإضافتها إلى المخزن المستلم.')
                ->visible(fn (): bool => $this->record->status === StockTransfer::STATUS_DRAFT)
                ->action(function (): void {
                    app(InventoryService::class)->completeTransfer($this->record);
                    $this->redirect(StockTransferResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn (): bool => $this->record->status === StockTransfer::STATUS_DRAFT),
        ];
    }
}
