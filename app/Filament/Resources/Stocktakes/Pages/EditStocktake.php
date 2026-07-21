<?php

namespace App\Filament\Resources\Stocktakes\Pages;

use App\Filament\Resources\Stocktakes\StocktakeResource;
use App\Models\Stocktake;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStocktake extends EditRecord
{
    protected static string $resource = StocktakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label('بدء الجرد')
                ->icon('heroicon-o-play')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === Stocktake::STATUS_DRAFT)
                ->action(function (): void {
                    app(InventoryService::class)->prepareStocktake($this->record);
                    $this->redirect(StocktakeResource::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('complete')
                ->label('اعتماد فروقات الجرد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('سيتم إنشاء تسويات مخزون بجميع الفروقات المسجلة.')
                ->visible(fn (): bool => $this->record->status === Stocktake::STATUS_COUNTING)
                ->action(function (): void {
                    app(InventoryService::class)->completeStocktake($this->record->fresh('items'));
                    $this->redirect(StocktakeResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn (): bool => $this->record->status === Stocktake::STATUS_DRAFT),
        ];
    }
}
