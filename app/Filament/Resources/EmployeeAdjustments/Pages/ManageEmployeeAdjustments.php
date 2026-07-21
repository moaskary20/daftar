<?php

namespace App\Filament\Resources\EmployeeAdjustments\Pages;

use App\Filament\Resources\EmployeeAdjustments\EmployeeAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmployeeAdjustments extends ManageRecords
{
    protected static string $resource = EmployeeAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
