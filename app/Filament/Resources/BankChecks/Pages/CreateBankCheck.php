<?php

namespace App\Filament\Resources\BankChecks\Pages;

use App\Filament\Resources\BankChecks\BankCheckResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankCheck extends CreateRecord
{
    protected static string $resource = BankCheckResource::class;
}
