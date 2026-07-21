<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $quantity = (float) $data['quantity'];

        if ($data['type'] === StockMovement::TYPE_ISSUE) {
            $quantity = -abs($quantity);
        } elseif ($data['type'] === StockMovement::TYPE_RECEIPT) {
            $quantity = abs($quantity);
        }

        return app(InventoryService::class)->adjust(
            $data['warehouse_id'],
            $data['product_id'],
            $quantity,
            $data['type'],
            $data['product_variant_id'] ?? null,
            isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            notes: $data['notes'] ?? null,
        );
    }
}
