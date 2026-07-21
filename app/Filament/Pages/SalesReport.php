<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ExportsReportData;
use App\Services\ReportingService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SalesReport extends Page
{
    use ExportsReportData;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'المبيعات والأرباح';

    protected static ?string $title = 'تقرير المبيعات والأرباح';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-report';

    public string $from;

    public string $to;

    public string $period = 'daily';

    public ?int $customerId = null;

    public ?int $userId = null;

    public ?int $warehouseId = null;

    public string $dimension = 'customer';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('sales_reports', 'view');
    }

    public function getSummaryProperty(): array
    {
        return app(ReportingService::class)->salesSummary(
            $this->from,
            $this->to,
            $this->customerId,
            $this->userId,
            $this->warehouseId,
        );
    }

    public function getTrendProperty()
    {
        return app(ReportingService::class)->salesByPeriod($this->from, $this->to, $this->period);
    }

    public function getBreakdownProperty()
    {
        return app(ReportingService::class)->salesBreakdown($this->from, $this->to, $this->dimension);
    }

    protected function getReportExportRows(): array
    {
        $rows = collect([
            'sales_total' => 'إجمالي المبيعات',
            'cost_total' => 'تكلفة المبيعات',
            'gross_profit' => 'إجمالي الأرباح',
            'expenses' => 'المصروفات',
            'net_profit' => 'صافي الأرباح',
            'profit_margin' => 'هامش الربح',
        ])->map(fn (string $label, string $key): array => [
            'القسم' => 'الملخص',
            'البيان' => $label,
            'المستندات' => null,
            'الإجمالي' => null,
            'القيمة' => $this->summary[$key] ?? 0,
        ]);

        $trend = collect($this->trend)->map(fn ($row): array => [
            'القسم' => 'المبيعات حسب الفترة',
            'البيان' => data_get($row, 'period'),
            'المستندات' => data_get($row, 'documents'),
            'الإجمالي' => data_get($row, 'total'),
            'القيمة' => null,
        ]);

        $breakdown = collect($this->breakdown)->map(fn ($row): array => [
            'القسم' => 'تحليل المبيعات',
            'البيان' => data_get($row, 'label'),
            'المستندات' => data_get($row, 'documents'),
            'الإجمالي' => data_get($row, 'total'),
            'القيمة' => null,
        ]);

        return $rows->concat($trend)->concat($breakdown)->values()->all();
    }

    protected function getReportExportFileName(): string
    {
        return 'sales-report';
    }
}
