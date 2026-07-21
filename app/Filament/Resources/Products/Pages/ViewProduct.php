<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printLabels')
                ->label('طباعة الباركود')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('products.labels', $this->record))
                ->openUrlInNewTab(),
            EditAction::make()->label('تعديل'),
        ];
    }
}
