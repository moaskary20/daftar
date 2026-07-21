<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesTodayOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $summary = app(ReportingService::class)->salesSummary(today(), today());

        return [
            Stat::make('مبيعات اليوم', number_format($summary['sales_total'], 2).' ج.م')
                ->description('صافي المبيعات بعد المرتجعات')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->chart([5, 8, 6, 10, 9, 12, max(1, (int) ($summary['sales_total'] / 100))]),
            Stat::make('أرباح اليوم', number_format($summary['net_profit'], 2).' ج.م')
                ->description('هامش الربح '.$summary['profit_margin'].'%')
                ->descriptionIcon($summary['net_profit'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-chart-bar')
                ->color($summary['net_profit'] >= 0 ? 'success' : 'danger')
                ->chart([3, 5, 4, 7, 6, 9, max(1, (int) abs($summary['net_profit'] / 100))]),
            Stat::make('مصروفات اليوم', number_format($summary['expenses'], 2).' ج.م')
                ->description('المصروفات المرحّلة اليوم')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->icon('heroicon-o-receipt-refund')
                ->color($summary['expenses'] > 0 ? 'warning' : 'gray'),
            Stat::make('فواتير اليوم', number_format($summary['documents_count']))
                ->description('فواتير ومرتجعات مرحّلة')
                ->descriptionIcon('heroicon-m-document-text')
                ->icon('heroicon-o-receipt-percent')
                ->color('info'),
        ];
    }
}
