<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $products = Product::query()->count();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $lowStock = WarehouseStock::query()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();
        $inventoryValue = Product::query()
            ->selectRaw('COALESCE(SUM(average_cost * stock_quantity), 0) as total')
            ->value('total');

        return [
            Stat::make('إجمالي المنتجات', number_format($products))
                ->description($activeProducts.' منتج نشط')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-cube')
                ->color('primary')
                ->chart([4, 7, 6, 9, 8, 12, max($products, 1)]),
            Stat::make('قيمة المخزون', number_format((float) $inventoryValue, 2).' ج.م')
                ->description('بحسب متوسط التكلفة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-wallet')
                ->color('success')
                ->chart([8, 7, 9, 10, 11, 10, 13]),
            Stat::make('تنبيهات المخزون', number_format($lowStock))
                ->description($lowStock > 0 ? 'تحتاج إلى إعادة طلب' : Warehouse::query()->count().' مخزن بحالة جيدة')
                ->descriptionIcon($lowStock > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-bell-alert')
                ->color($lowStock > 0 ? 'danger' : 'success'),
            Stat::make('متغيرات المنتجات', number_format(ProductVariant::query()->count()))
                ->description('ألوان ومقاسات وأوزان')
                ->descriptionIcon('heroicon-m-swatch')
                ->icon('heroicon-o-swatch')
                ->color('info')
                ->chart([2, 3, 5, 4, 7, 8, 10]),
        ];
    }
}
