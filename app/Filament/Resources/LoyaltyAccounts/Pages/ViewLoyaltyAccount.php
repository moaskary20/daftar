<?php

namespace App\Filament\Resources\LoyaltyAccounts\Pages;

use App\Filament\Resources\LoyaltyAccounts\LoyaltyAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLoyaltyAccount extends ViewRecord
{
    protected static string $resource = LoyaltyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
