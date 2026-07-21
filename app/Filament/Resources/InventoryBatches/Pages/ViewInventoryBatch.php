<?php

namespace App\Filament\Resources\InventoryBatches\Pages;

use App\Filament\Resources\InventoryBatches\InventoryBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryBatch extends ViewRecord
{
    protected static string $resource = InventoryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
