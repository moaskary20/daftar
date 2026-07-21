<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ExportsReportData;
use App\Services\ReportingService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class InventoryReport extends Page
{
    use ExportsReportData;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقارير المخزون';

    protected static ?string $title = 'تقارير المخزون';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.inventory-report';

    public string $from;

    public string $to;

    public string $reportType = 'low_stock';

    public ?int $productId = null;

    public ?int $warehouseId = null;

    public int $stagnantDays = 90;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('inventory_reports', 'view');
    }

    public function getRowsProperty()
    {
        $service = app(ReportingService::class);

        return match ($this->reportType) {
            'movement' => $this->productId
                ? $service->itemMovement($this->productId, $this->from, $this->to, $this->warehouseId)
                : collect(),
            'stagnant' => $service->stagnantProducts($this->stagnantDays),
            'top_selling' => $service->topSellingProducts($this->from, $this->to),
            default => $service->lowStockProducts(),
        };
    }

    protected function getReportExportRows(): array
    {
        return collect($this->rows)->map(function ($row): array {
            return match ($this->reportType) {
                'movement' => [
                    'التاريخ' => $row->moved_at?->format('Y-m-d H:i:s'),
                    'الصنف' => $row->product?->name,
                    'المتغير' => $row->variant?->name,
                    'المخزن' => $row->warehouse?->name,
                    'نوع الحركة' => $row->type,
                    'الكمية' => $row->quantity,
                    'قبل' => $row->balance_before,
                    'بعد' => $row->balance_after,
                ],
                'top_selling' => [
                    'الصنف' => $row->name,
                    'الكمية المباعة' => $row->sold_quantity,
                    'قيمة المبيعات' => $row->sales_total,
                ],
                default => [
                    'الصنف' => $row->product?->name,
                    'المتغير' => $row->variant?->name,
                    'المخزن' => $row->warehouse?->name,
                    'الكمية' => $row->quantity,
                    'حد إعادة الطلب' => $row->reorder_level,
                ],
            };
        })->values()->all();
    }

    protected function getReportExportFileName(): string
    {
        return "inventory-{$this->reportType}-report";
    }
}
