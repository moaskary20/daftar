<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ProductManagement extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'إدارة المنتجات';

    protected static ?string $clusterBreadcrumb = 'إدارة المنتجات';

    protected static ?int $navigationSort = 4;
}
