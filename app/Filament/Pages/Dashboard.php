<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestCustomersTable;
use App\Filament\Widgets\LatestSuppliersTable;
use App\Filament\Widgets\LowStockProductsTable;
use App\Filament\Widgets\MonthlyProfitChart;
use App\Filament\Widgets\ProductsByCategoryChart;
use App\Filament\Widgets\ProductStatsOverview;
use App\Filament\Widgets\SalesTodayOverview;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopSellingProductsTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    public function getHeading(): string
    {
        return 'مرحباً بك في دفتر';
    }

    public function getSubheading(): ?string
    {
        return 'نظرة سريعة على المبيعات والأرباح والمخزون';
    }

    public function getColumns(): int|array
    {
        return ['lg' => 4];
    }

    public function getWidgets(): array
    {
        return [
            SalesTodayOverview::class,
            SalesTrendChart::class,
            MonthlyProfitChart::class,
            TopSellingProductsTable::class,
            LowStockProductsTable::class,
            LatestCustomersTable::class,
            LatestSuppliersTable::class,
            ProductStatsOverview::class,
            ProductsByCategoryChart::class,
        ];
    }
}
