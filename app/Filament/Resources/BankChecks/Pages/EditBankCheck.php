<?php

namespace App\Filament\Resources\BankChecks\Pages;

use App\Filament\Resources\BankChecks\BankCheckResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBankCheck extends EditRecord
{
    protected static string $resource = BankCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
