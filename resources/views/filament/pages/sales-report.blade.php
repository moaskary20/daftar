<x-filament-panels::page>
    <div style="display:grid;gap:1rem">
        <x-filament::section heading="فلاتر التقرير" icon="heroicon-o-funnel">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem">
                <label>من
                    <input class="fi-input" type="date" wire:model.live="from">
                </label>
                <label>إلى
                    <input class="fi-input" type="date" wire:model.live="to">
                </label>
                <label>التجميع الزمني
                    <select class="fi-select-input" wire:model.live="period">
                        <option value="daily">يومي</option>
                        <option value="weekly">أسبوعي</option>
                        <option value="monthly">شهري</option>
                        <option value="yearly">سنوي</option>
                    </select>
                </label>
                <label>العميل
                    <select class="fi-select-input" wire:model.live="customerId">
                        <option value="">الكل</option>
                        @foreach(\App\Models\Customer::query()->orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>الموظف
                    <select class="fi-select-input" wire:model.live="userId">
                        <option value="">الكل</option>
                        @foreach(\App\Models\User::query()->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>الفرع / المخزن
                    <select class="fi-select-input" wire:model.live="warehouseId">
                        <option value="">الكل</option>
                        @foreach(\App\Models\Warehouse::query()->orderBy('name')->get() as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </x-filament::section>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            @foreach([
                'sales_total' => ['إجمالي المبيعات', 'primary'],
                'cost_total' => ['تكلفة المبيعات', 'gray'],
                'gross_profit' => ['إجمالي الأرباح', 'success'],
                'expenses' => ['المصروفات', 'warning'],
                'net_profit' => ['صافي الأرباح', 'success'],
            ] as $key => [$label, $color])
                <x-filament::section>
                    <div style="font-size:.8rem;color:#64748b">{{ $label }}</div>
                    <div style="font-size:1.45rem;font-weight:800;margin-top:.35rem">
                        {{ number_format($this->summary[$key], 2) }} ج.م
                    </div>
                </x-filament::section>
            @endforeach
            <x-filament::section>
                <div style="font-size:.8rem;color:#64748b">هامش الربح</div>
                <div style="font-size:1.45rem;font-weight:800;margin-top:.35rem">{{ $this->summary['profit_margin'] }}%</div>
            </x-filament::section>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:1rem">
            <x-filament::section heading="المبيعات حسب الفترة">
                <div style="overflow:auto">
                    <table class="fi-ta-table" style="width:100%">
                        <thead><tr><th>الفترة</th><th>المستندات</th><th>الإجمالي</th></tr></thead>
                        <tbody>
                        @forelse($this->trend as $row)
                            <tr><td>{{ $row['period'] }}</td><td>{{ $row['documents'] }}</td><td>{{ number_format($row['total'], 2) }} ج.م</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:2rem">لا توجد بيانات</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section heading="تحليل المبيعات">
                <div style="margin-bottom:1rem">
                    <select class="fi-select-input" wire:model.live="dimension">
                        <option value="customer">حسب العميل</option>
                        <option value="employee">حسب الموظف</option>
                        <option value="warehouse">حسب الفرع / المخزن</option>
                    </select>
                </div>
                <div style="overflow:auto">
                    <table class="fi-ta-table" style="width:100%">
                        <thead><tr><th>البيان</th><th>المستندات</th><th>الإجمالي</th></tr></thead>
                        <tbody>
                        @forelse($this->breakdown as $row)
                            <tr><td>{{ $row['label'] }}</td><td>{{ $row['documents'] }}</td><td>{{ number_format($row['total'], 2) }} ج.م</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:2rem">لا توجد بيانات</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
