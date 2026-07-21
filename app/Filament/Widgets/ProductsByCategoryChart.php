<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class ProductsByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'توزيع المنتجات حسب التصنيفات';

    protected ?string $description = 'أكثر التصنيفات احتواءً على المنتجات';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'المنتجات',
                    'data' => $categories->pluck('products_count')->all(),
                    'backgroundColor' => [
                        '#5458f0',
                        '#8b5cf6',
                        '#06b6d4',
                        '#10b981',
                        '#f59e0b',
                        '#f43f5e',
                        '#3b82f6',
                        '#14b8a6',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
