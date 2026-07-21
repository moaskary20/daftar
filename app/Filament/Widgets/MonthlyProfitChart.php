<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\ChartWidget;

class MonthlyProfitChart extends ChartWidget
{
    protected ?string $heading = 'الأرباح الشهرية';

    protected ?string $description = 'إجمالي وصافي الأرباح خلال آخر 12 شهراً';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['lg' => 2];

    protected function getData(): array
    {
        $service = app(ReportingService::class);
        $labels = [];
        $gross = [];
        $net = [];

        foreach (range(11, 0) as $offset) {
            $month = today()->subMonthsNoOverflow($offset);
            $summary = $service->salesSummary(
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            );
            $labels[] = $month->format('Y-m');
            $gross[] = $summary['gross_profit'];
            $net[] = $summary['net_profit'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'إجمالي الأرباح',
                    'data' => $gross,
                    'backgroundColor' => 'rgba(84, 88, 240, .8)',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'صافي الأرباح',
                    'data' => $net,
                    'backgroundColor' => 'rgba(16, 185, 129, .8)',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
