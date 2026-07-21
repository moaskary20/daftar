<x-filament-panels::page>
    <div style="display:grid;gap:1rem">
        <x-filament::section heading="خيارات التقرير المالي" icon="heroicon-o-funnel">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem">
                <label>التقرير
                    <select class="fi-select-input" wire:model.live="reportType">
                        <option value="trial_balance">ميزان المراجعة</option>
                        <option value="income_statement">قائمة الدخل</option>
                        <option value="balance_sheet">الميزانية</option>
                        <option value="cash_flow">التدفقات النقدية</option>
                    </select>
                </label>
                <label>من<input class="fi-input" type="date" wire:model.live="from"></label>
                <label>إلى<input class="fi-input" type="date" wire:model.live="to"></label>
            </div>
        </x-filament::section>

        @if($reportType === 'trial_balance')
            <x-filament::section heading="ميزان المراجعة">
                <div style="overflow:auto">
                    <table class="fi-ta-table" style="width:100%">
                        <thead><tr><th>الكود</th><th>الحساب</th><th>مدين</th><th>دائن</th><th>الرصيد</th></tr></thead>
                        <tbody>
                        @forelse($this->report['rows'] as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td>
                                <td>{{ number_format($row['debit'], 2) }}</td>
                                <td>{{ number_format($row['credit'], 2) }}</td>
                                <td>{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:2rem">لا توجد قيود مرحّلة</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @elseif($reportType === 'income_statement')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem">
                <x-filament::section heading="الإيرادات">
                    @foreach($this->report['revenue_accounts'] as $row)
                        <div style="display:flex;justify-content:space-between;padding:.55rem;border-bottom:1px solid #e5e7eb">
                            <span>{{ $row['code'] }} - {{ $row['name'] }}</span><strong>{{ number_format($row['balance'], 2) }}</strong>
                        </div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.8rem;font-weight:800"><span>إجمالي الإيرادات</span><span>{{ number_format($this->report['total_revenue'], 2) }} ج.م</span></div>
                </x-filament::section>
                <x-filament::section heading="المصروفات">
                    @foreach($this->report['expense_accounts'] as $row)
                        <div style="display:flex;justify-content:space-between;padding:.55rem;border-bottom:1px solid #e5e7eb">
                            <span>{{ $row['code'] }} - {{ $row['name'] }}</span><strong>{{ number_format($row['balance'], 2) }}</strong>
                        </div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.8rem;font-weight:800"><span>إجمالي المصروفات</span><span>{{ number_format($this->report['total_expenses'], 2) }} ج.م</span></div>
                </x-filament::section>
            </div>
            <x-filament::section><div style="display:flex;justify-content:space-between;font-size:1.3rem;font-weight:900"><span>صافي الدخل</span><span>{{ number_format($this->report['net_income'], 2) }} ج.م</span></div></x-filament::section>
        @elseif($reportType === 'balance_sheet')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:1rem">
                @foreach(['assets' => 'الأصول', 'liabilities' => 'الخصوم', 'equity' => 'حقوق الملكية'] as $key => $label)
                    <x-filament::section :heading="$label">
                        @foreach($this->report[$key] as $row)
                            <div style="display:flex;justify-content:space-between;padding:.55rem;border-bottom:1px solid #e5e7eb"><span>{{ $row['code'] }} - {{ $row['name'] }}</span><strong>{{ number_format($row['balance'], 2) }}</strong></div>
                        @endforeach
                        <div style="display:flex;justify-content:space-between;padding:.8rem;font-weight:800"><span>الإجمالي</span><span>{{ number_format($this->report['total_'.$key], 2) }} ج.م</span></div>
                    </x-filament::section>
                @endforeach
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
                @foreach([
                    'inflows' => 'التدفقات الداخلة',
                    'outflows' => 'التدفقات الخارجة',
                    'net_cash_flow' => 'صافي التدفق النقدي',
                    'treasury_balances' => 'أرصدة الخزائن',
                    'bank_balances' => 'أرصدة البنوك',
                ] as $key => $label)
                    <x-filament::section><div style="color:#64748b">{{ $label }}</div><div style="font-size:1.4rem;font-weight:900;margin-top:.4rem">{{ number_format($this->report[$key], 2) }} ج.م</div></x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
