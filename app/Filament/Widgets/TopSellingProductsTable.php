<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\SalesDocument;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSellingProductsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['lg' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('أفضل المنتجات مبيعاً')
            ->description('آخر 30 يوماً بحسب الكمية المباعة')
            ->query(fn (): Builder => Product::query()
                ->join('sales_document_items', 'sales_document_items.product_id', '=', 'products.id')
                ->join('sales_documents', 'sales_documents.id', '=', 'sales_document_items.sales_document_id')
                ->where('sales_documents.status', SalesDocument::STATUS_POSTED)
                ->whereIn('sales_documents.type', [SalesDocument::TYPE_INVOICE, SalesDocument::TYPE_RETURN])
                ->whereBetween('sales_documents.document_date', [today()->subDays(29), today()])
                ->groupBy('products.id')
                ->selectRaw(
                    'products.*,
                    SUM(CASE WHEN sales_documents.type = ? THEN -sales_document_items.quantity ELSE sales_document_items.quantity END) as sold_quantity,
                    SUM(CASE WHEN sales_documents.type = ? THEN -sales_document_items.line_total ELSE sales_document_items.line_total END) as sales_total',
                    [SalesDocument::TYPE_RETURN, SalesDocument::TYPE_RETURN],
                )
                ->orderByDesc('sold_quantity'))
            ->columns([
                TextColumn::make('name')
                    ->label('المنتج')
                    ->weight('bold'),
                TextColumn::make('sku')
                    ->label('الكود'),
                TextColumn::make('sold_quantity')
                    ->label('الكمية المباعة')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color('success'),
                TextColumn::make('sales_total')
                    ->label('إجمالي المبيعات')
                    ->money('EGP'),
            ])
            ->emptyStateHeading('لا توجد مبيعات بعد')
            ->emptyStateDescription('ستظهر المنتجات الأكثر مبيعاً هنا بعد ترحيل الفواتير.')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
