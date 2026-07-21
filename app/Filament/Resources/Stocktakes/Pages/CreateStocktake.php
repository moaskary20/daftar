<?php

namespace App\Filament\Resources\Stocktakes\Pages;

use App\Filament\Resources\Stocktakes\StocktakeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStocktake extends CreateRecord
{
    protected static string $resource = StocktakeResource::class;
}
