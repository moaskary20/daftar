<x-filament-panels::page>
    <div style="display:grid;gap:1rem">
        <x-filament::section heading="خيارات تقرير المخزون" icon="heroicon-o-funnel">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
                <label>نوع التقرير
                    <select class="fi-select-input" wire:model.live="reportType">
                        <option value="movement">حركة صنف</option>
                        <option value="stagnant">الأصناف الراكدة</option>
                        <option value="top_selling">الأصناف الأكثر مبيعاً</option>
                        <option value="low_stock">الأصناف قليلة المخزون</option>
                    </select>
                </label>
                @if($reportType === 'movement')
                    <label>الصنف
                        <select class="fi-select-input" wire:model.live="productId">
                            <option value="">اختر الصنف</option>
                            @foreach(\App\Models\Product::query()->orderBy('name')->get() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>المخزن
                        <select class="fi-select-input" wire:model.live="warehouseId">
                            <option value="">كل المخازن</option>
                            @foreach(\App\Models\Warehouse::query()->orderBy('name')->get() as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if(in_array($reportType, ['movement', 'top_selling']))
                    <label>من<input class="fi-input" type="date" wire:model.live="from"></label>
                    <label>إلى<input class="fi-input" type="date" wire:model.live="to"></label>
                @endif
                @if($reportType === 'stagnant')
                    <label>عدد أيام الركود<input class="fi-input" type="number" min="1" wire:model.live.debounce.500ms="stagnantDays"></label>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="overflow:auto">
                <table class="fi-ta-table" style="width:100%">
                    @if($reportType === 'movement')
                        <thead><tr><th>التاريخ</th><th>المخزن</th><th>نوع الحركة</th><th>الكمية</th><th>قبل</th><th>بعد</th></tr></thead>
                        <tbody>
                        @forelse($this->rows as $row)
                            <tr>
                                <td>{{ $row->moved_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $row->warehouse?->name }}</td>
                                <td>{{ \App\Models\StockMovement::labels()[$row->type] ?? $row->type }}</td>
                                <td>{{ number_format((float)$row->quantity, 3) }}</td>
                                <td>{{ number_format((float)$row->balance_before, 3) }}</td>
                                <td>{{ number_format((float)$row->balance_after, 3) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:2rem">اختر صنفاً أو لا توجد حركات</td></tr>
                        @endforelse
                        </tbody>
                    @elseif($reportType === 'top_selling')
                        <thead><tr><th>الصنف</th><th>الكمية المباعة</th><th>قيمة المبيعات</th></tr></thead>
                        <tbody>
                        @forelse($this->rows as $row)
                            <tr><td>{{ $row->name }}</td><td>{{ number_format((float)$row->sold_quantity, 3) }}</td><td>{{ number_format((float)$row->sales_total, 2) }} ج.م</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:2rem">لا توجد بيانات</td></tr>
                        @endforelse
                        </tbody>
                    @else
                        <thead><tr><th>الصنف</th><th>المتغير</th><th>المخزن</th><th>الكمية</th><th>حد إعادة الطلب</th></tr></thead>
                        <tbody>
                        @forelse($this->rows as $row)
                            <tr>
                                <td>{{ $row->product?->name }}</td>
                                <td>{{ $row->variant?->name ?? '—' }}</td>
                                <td>{{ $row->warehouse?->name }}</td>
                                <td>{{ number_format((float)$row->quantity, 3) }}</td>
                                <td>{{ number_format((float)$row->reorder_level, 3) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:2rem">لا توجد بيانات</td></tr>
                        @endforelse
                        </tbody>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
