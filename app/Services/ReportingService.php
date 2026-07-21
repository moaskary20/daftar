<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\SalesDocument;
use App\Models\StockMovement;
use App\Models\Treasury;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function salesSummary(
        mixed $from,
        mixed $to,
        ?int $customerId = null,
        ?int $userId = null,
        ?int $warehouseId = null,
    ): array {
        $documents = $this->salesQuery($from, $to, $customerId, $userId, $warehouseId)
            ->with(['items.product'])
            ->get();

        $revenue = 0.0;
        $cost = 0.0;

        foreach ($documents as $document) {
            $sign = $document->type === SalesDocument::TYPE_RETURN ? -1 : 1;

            foreach ($document->items as $item) {
                $revenue += $sign * (float) $item->line_total;
                $cost += $sign * abs((float) $item->quantity) * (float) $item->product->average_cost;
            }

            $revenue += $sign * (float) $document->shipping_cost;
        }

        $grossProfit = $revenue - $cost;
        $expenses = (float) Expense::query()
            ->where('status', 'posted')
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');
        $netProfit = $grossProfit - $expenses;

        return [
            'documents_count' => $documents->count(),
            'sales_total' => round($revenue, 4),
            'cost_total' => round($cost, 4),
            'gross_profit' => round($grossProfit, 4),
            'expenses' => round($expenses, 4),
            'net_profit' => round($netProfit, 4),
            'profit_margin' => $revenue !== 0.0 ? round(($grossProfit / $revenue) * 100, 2) : 0.0,
        ];
    }

    public function salesByPeriod(mixed $from, mixed $to, string $period = 'daily'): Collection
    {
        $documents = $this->salesQuery($from, $to)->get();

        return $documents
            ->groupBy(fn (SalesDocument $document): string => match ($period) {
                'weekly' => $document->document_date->format('o-\WW'),
                'monthly' => $document->document_date->format('Y-m'),
                'yearly' => $document->document_date->format('Y'),
                default => $document->document_date->format('Y-m-d'),
            })
            ->map(function (Collection $group, string $label): array {
                $total = $group->sum(fn (SalesDocument $document): float => (
                    $document->type === SalesDocument::TYPE_RETURN ? -1 : 1
                ) * (float) $document->grand_total);

                return ['period' => $label, 'documents' => $group->count(), 'total' => round($total, 4)];
            })
            ->values();
    }

    public function salesBreakdown(mixed $from, mixed $to, string $dimension): Collection
    {
        $documents = $this->salesQuery($from, $to)
            ->with(['customer', 'creator', 'warehouse'])
            ->get();

        return $documents
            ->groupBy(fn (SalesDocument $document): string => match ($dimension) {
                'employee' => $document->creator?->name ?? 'غير محدد',
                'warehouse' => $document->warehouse?->name ?? 'غير محدد',
                default => $document->customer?->name ?? 'غير محدد',
            })
            ->map(fn (Collection $group, string $label): array => [
                'label' => $label,
                'documents' => $group->count(),
                'total' => round($group->sum(fn (SalesDocument $document): float => (
                    $document->type === SalesDocument::TYPE_RETURN ? -1 : 1
                ) * (float) $document->grand_total), 4),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function itemMovement(int $productId, mixed $from, mixed $to, ?int $warehouseId = null): Collection
    {
        return StockMovement::query()
            ->with(['warehouse', 'product', 'variant'])
            ->where('product_id', $productId)
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->whereBetween('moved_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->orderBy('moved_at')
            ->get();
    }

    public function stagnantProducts(int $days = 90): Collection
    {
        $activeProductIds = StockMovement::query()
            ->whereIn('type', [StockMovement::TYPE_SALE, StockMovement::TYPE_DELIVERY])
            ->where('moved_at', '>=', now()->subDays($days))
            ->pluck('product_id');

        return WarehouseStock::query()
            ->with(['product', 'warehouse', 'variant'])
            ->where('quantity', '>', 0)
            ->whereNotIn('product_id', $activeProductIds)
            ->orderByDesc('quantity')
            ->get();
    }

    public function topSellingProducts(mixed $from, mixed $to, int $limit = 20): Collection
    {
        return DB::table('sales_document_items')
            ->join('sales_documents', 'sales_documents.id', '=', 'sales_document_items.sales_document_id')
            ->join('products', 'products.id', '=', 'sales_document_items.product_id')
            ->where('sales_documents.status', SalesDocument::STATUS_POSTED)
            ->whereBetween('sales_documents.document_date', [$from, $to])
            ->selectRaw(
                'products.id, products.name,
                SUM(CASE WHEN sales_documents.type = ? THEN -sales_document_items.quantity ELSE sales_document_items.quantity END) as sold_quantity,
                SUM(CASE WHEN sales_documents.type = ? THEN -sales_document_items.line_total ELSE sales_document_items.line_total END) as sales_total',
                [SalesDocument::TYPE_RETURN, SalesDocument::TYPE_RETURN],
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold_quantity')
            ->limit($limit)
            ->get();
    }

    public function lowStockProducts(): Collection
    {
        return WarehouseStock::query()
            ->with(['product', 'variant', 'warehouse'])
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->get();
    }

    public function trialBalance(mixed $from, mixed $to): Collection
    {
        return ChartAccount::query()
            ->where('allow_posting', true)
            ->orderBy('code')
            ->get()
            ->map(function (ChartAccount $account) use ($from, $to): array {
                $lines = $account->lines()
                    ->whereHas('entry', fn (Builder $query) => $query
                        ->where('status', JournalEntry::STATUS_POSTED)
                        ->whereBetween('entry_date', [$from, $to]));
                $debit = (float) (clone $lines)->sum('debit');
                $credit = (float) (clone $lines)->sum('credit');

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => round($debit, 4),
                    'credit' => round($credit, 4),
                    'balance' => round(
                        $account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit,
                        4,
                    ),
                ];
            })
            ->filter(fn (array $row): bool => $row['debit'] !== 0.0 || $row['credit'] !== 0.0)
            ->values();
    }

    public function incomeStatement(mixed $from, mixed $to): array
    {
        $rows = $this->trialBalance($from, $to);
        $revenue = $rows->where('type', ChartAccount::TYPE_REVENUE)->sum('balance');
        $expenses = $rows->where('type', ChartAccount::TYPE_EXPENSE)->sum('balance');

        return [
            'revenue_accounts' => $rows->where('type', ChartAccount::TYPE_REVENUE)->values(),
            'expense_accounts' => $rows->where('type', ChartAccount::TYPE_EXPENSE)->values(),
            'total_revenue' => round($revenue, 4),
            'total_expenses' => round($expenses, 4),
            'net_income' => round($revenue - $expenses, 4),
        ];
    }

    public function balanceSheet(mixed $asOf): array
    {
        $rows = $this->trialBalance('1900-01-01', $asOf);
        $currentEarnings = $this->incomeStatement('1900-01-01', $asOf)['net_income'];
        $equity = $rows->where('type', ChartAccount::TYPE_EQUITY)->values();

        if ($currentEarnings !== 0.0) {
            $equity->push([
                'code' => 'CURRENT',
                'name' => 'صافي أرباح الفترة',
                'type' => ChartAccount::TYPE_EQUITY,
                'debit' => 0,
                'credit' => 0,
                'balance' => $currentEarnings,
            ]);
        }

        return [
            'assets' => $rows->where('type', ChartAccount::TYPE_ASSET)->values(),
            'liabilities' => $rows->where('type', ChartAccount::TYPE_LIABILITY)->values(),
            'equity' => $equity,
            'total_assets' => round($rows->where('type', ChartAccount::TYPE_ASSET)->sum('balance'), 4),
            'total_liabilities' => round($rows->where('type', ChartAccount::TYPE_LIABILITY)->sum('balance'), 4),
            'total_equity' => round($equity->sum('balance'), 4),
        ];
    }

    public function cashFlow(mixed $from, mixed $to): array
    {
        $accountIds = Treasury::query()->pluck('chart_account_id')
            ->merge(BankAccount::query()->pluck('chart_account_id'))
            ->unique();
        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->whereIn('journal_entry_lines.chart_account_id', $accountIds)
            ->selectRaw('SUM(journal_entry_lines.debit) as inflows, SUM(journal_entry_lines.credit) as outflows')
            ->first();
        $inflows = (float) ($lines->inflows ?? 0);
        $outflows = (float) ($lines->outflows ?? 0);

        return [
            'inflows' => round($inflows, 4),
            'outflows' => round($outflows, 4),
            'net_cash_flow' => round($inflows - $outflows, 4),
            'treasury_balances' => (float) Treasury::query()->sum('current_balance'),
            'bank_balances' => (float) BankAccount::query()->sum('current_balance'),
        ];
    }

    private function salesQuery(
        mixed $from,
        mixed $to,
        ?int $customerId = null,
        ?int $userId = null,
        ?int $warehouseId = null,
    ): Builder {
        return SalesDocument::query()
            ->where('status', SalesDocument::STATUS_POSTED)
            ->whereIn('type', [SalesDocument::TYPE_INVOICE, SalesDocument::TYPE_RETURN])
            ->whereBetween('document_date', [$from, $to])
            ->when($customerId, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($userId, fn (Builder $query) => $query->where('created_by', $userId))
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId));
    }
}
