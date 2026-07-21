<?php

namespace App\Filament\Resources\BankChecks\Pages;

use App\Filament\Resources\BankChecks\BankCheckResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBankCheck extends ViewRecord
{
    protected static string $resource = BankCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
