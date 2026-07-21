<?php

namespace App\Filament\Widgets;

use App\Models\SalesDocument;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'المبيعات اليومية';

    protected ?string $description = 'صافي المبيعات خلال آخر 30 يوماً';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['lg' => 2];

    protected function getData(): array
    {
        $from = today()->subDays(29);
        $documents = SalesDocument::query()
            ->where('status', SalesDocument::STATUS_POSTED)
            ->whereIn('type', [SalesDocument::TYPE_INVOICE, SalesDocument::TYPE_RETURN])
            ->whereBetween('document_date', [$from, today()])
            ->get(['type', 'document_date', 'grand_total'])
            ->groupBy(fn (SalesDocument $document): string => $document->document_date->format('Y-m-d'));

        $labels = [];
        $totals = [];

        foreach (range(0, 29) as $offset) {
            $day = $from->copy()->addDays($offset);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('m/d');
            $totals[] = round((float) ($documents->get($key)?->sum(
                fn (SalesDocument $document): float => ($document->type === SalesDocument::TYPE_RETURN ? -1 : 1)
                    * (float) $document->grand_total,
            ) ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (ج.م)',
                    'data' => $totals,
                    'borderColor' => '#5458f0',
                    'backgroundColor' => 'rgba(84, 88, 240, .15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
