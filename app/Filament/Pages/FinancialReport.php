<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ExportsReportData;
use App\Services\ReportingService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class FinancialReport extends Page
{
    use ExportsReportData;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'التقارير المالية';

    protected static ?string $title = 'التقارير المالية';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.financial-report';

    public string $from;

    public string $to;

    public string $reportType = 'trial_balance';

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('financial_reports', 'view');
    }

    public function getReportProperty(): array
    {
        $service = app(ReportingService::class);

        return match ($this->reportType) {
            'income_statement' => $service->incomeStatement($this->from, $this->to),
            'balance_sheet' => $service->balanceSheet($this->to),
            'cash_flow' => $service->cashFlow($this->from, $this->to),
            default => ['rows' => $service->trialBalance($this->from, $this->to)],
        };
    }

    protected function getReportExportRows(): array
    {
        $report = $this->report;

        if ($this->reportType === 'trial_balance') {
            return collect($report['rows'])->map(fn (array $row): array => [
                'القسم' => 'ميزان المراجعة',
                'الكود' => $row['code'],
                'الحساب' => $row['name'],
                'مدين' => $row['debit'],
                'دائن' => $row['credit'],
                'الرصيد' => $row['balance'],
            ])->all();
        }

        if ($this->reportType === 'income_statement') {
            $rows = collect($report['revenue_accounts'])->map(fn (array $row): array => [
                'القسم' => 'الإيرادات',
                'الكود' => $row['code'],
                'الحساب' => $row['name'],
                'القيمة' => $row['balance'],
            ])->concat(collect($report['expense_accounts'])->map(fn (array $row): array => [
                'القسم' => 'المصروفات',
                'الكود' => $row['code'],
                'الحساب' => $row['name'],
                'القيمة' => $row['balance'],
            ]));

            return $rows->push([
                'القسم' => 'النتيجة',
                'الكود' => null,
                'الحساب' => 'صافي الدخل',
                'القيمة' => $report['net_income'],
            ])->values()->all();
        }

        if ($this->reportType === 'balance_sheet') {
            return collect([
                'assets' => 'الأصول',
                'liabilities' => 'الخصوم',
                'equity' => 'حقوق الملكية',
            ])->flatMap(fn (string $label, string $key) => collect($report[$key])->map(fn (array $row): array => [
                'القسم' => $label,
                'الكود' => $row['code'],
                'الحساب' => $row['name'],
                'القيمة' => $row['balance'],
            ]))->values()->all();
        }

        return collect([
            'inflows' => 'التدفقات الداخلة',
            'outflows' => 'التدفقات الخارجة',
            'net_cash_flow' => 'صافي التدفق النقدي',
            'treasury_balances' => 'أرصدة الخزائن',
            'bank_balances' => 'أرصدة البنوك',
        ])->map(fn (string $label, string $key): array => [
            'البيان' => $label,
            'القيمة' => $report[$key],
        ])->values()->all();
    }

    protected function getReportExportFileName(): string
    {
        return "financial-{$this->reportType}-report";
    }
}
