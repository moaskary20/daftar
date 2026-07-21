<div
    class="pos-app"
    x-data="posApp($wire)"
    x-init="init()"
    @pos-sale-completed.window="saleCompleted($event.detail)"
    @pos-print.window="printUrl($event.detail.url)"
    @pos-cart-updated.window="updateCustomerDisplay($event.detail)"
>
    @if(!$sessionId)
        <div class="pos-boot">
            <x-filament::icon icon="heroicon-o-building-storefront" class="pos-boot-icon"/>
            <h2>يلزم إعداد مخزن ونقطة بيع أولاً</h2>
            <p>أنشئ مخزناً ثم أعد فتح شاشة الكاشير.</p>
            <a href="{{ url('/admin') }}" class="pos-boot-link">العودة إلى لوحة التحكم</a>
        </div>
    @else
        <div class="pos-screen">
            {{-- ================= المنتجات (تظهر يميناً في RTL) ================= --}}
            <section class="pos-main">
                <header class="main-head">
                    <a href="{{ url('/admin') }}" class="head-btn" title="العودة إلى لوحة التحكم">
                        <x-filament::icon icon="heroicon-o-home"/>
                    </a>
                    <nav class="crumb">
                        <button type="button" wire:click="$set('categoryId', null)">الكل</button>
                        @if($categoryId)
                            <span>‹</span>
                            <strong>{{ $this->categories->firstWhere('id', $categoryId)?->name }}</strong>
                        @endif
                    </nav>
                    <span class="online-pill" :class="online ? 'is-online' : 'is-offline'" x-text="online ? 'متصل' : 'بدون إنترنت'"></span>
                    <div class="head-search">
                        <x-filament::icon icon="heroicon-o-magnifying-glass"/>
                        <input
                            id="pos-search"
                            type="search"
                            wire:model.live.debounce.350ms="search"
                            placeholder="ابحث عن منتج أو امسح الباركود — F2"
                            autocomplete="off"
                        >
                    </div>
                    <select class="head-terminal" wire:change="selectTerminal($event.target.value)" aria-label="نقطة البيع">
                        @foreach(\App\Models\PosTerminal::query()->where('is_active', true)->get() as $terminal)
                            <option value="{{ $terminal->id }}" @selected($terminalId === $terminal->id)>{{ $terminal->name }}</option>
                        @endforeach
                    </select>
                    @if($this->terminal?->scale_enabled)
                        <button type="button" class="head-btn" @click="readScale()" title="قراءة الميزان"><x-filament::icon icon="heroicon-o-scale"/></button>
                    @endif
                    @if($this->terminal?->customer_display_enabled)
                        <a class="head-btn" href="{{ route('pos.customer-display') }}" target="_blank" title="شاشة العميل"><x-filament::icon icon="heroicon-o-tv"/></a>
                    @endif
                    @if($this->terminal?->cash_drawer_enabled)
                        <button type="button" class="head-btn" @click="openDrawer()" title="فتح الدرج"><x-filament::icon icon="heroicon-o-inbox-arrow-down"/></button>
                    @endif
                </header>

                <nav class="category-strip">
                    <button type="button" wire:click="$set('categoryId', null)" @class(['active' => !$categoryId])>الكل</button>
                    @foreach($this->categories as $category)
                        <button type="button" wire:click="$set('categoryId', {{ $category->id }})" @class(['active' => $categoryId === $category->id])>
                            {{ $category->name }}
                        </button>
                    @endforeach
                </nav>

                <div class="product-grid">
                    @forelse($this->products as $product)
                        <button type="button" class="product-card" wire:key="product-{{ $product->id }}" wire:click="addProduct({{ $product->id }})">
                            <div class="product-image">
                                @if($product->image)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                @else
                                    <x-filament::icon icon="heroicon-o-cube"/>
                                @endif
                                @if($product->stock_quantity <= $product->minimum_stock)
                                    <span class="stock-warning">{{ number_format((float) $product->stock_quantity, 0) }}</span>
                                @endif
                            </div>
                            <strong>{{ $product->name }}</strong>
                            <span class="product-price">{{ number_format((float) $product->selling_price, 2) }} ج.م</span>
                        </button>
                    @empty
                        <div class="pos-boot product-empty">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="pos-boot-icon"/>
                            <h3>لا توجد منتجات مطابقة</h3>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- ================= السلة والأزرار (تظهر يساراً) ================= --}}
            <aside class="pos-side">
                <div class="side-lines">
                    @forelse($cart as $key => $line)
                        <button
                            type="button"
                            wire:key="cart-{{ $key }}"
                            wire:click="selectLine('{{ $key }}')"
                            @class(['side-line', 'selected' => $selectedKey === $key])
                        >
                            <span class="line-name">{{ $line['name'] }}</span>
                            <span class="line-total">{{ number_format(max(0, ($line['quantity'] * $line['unit_price']) - $line['discount_amount']), 2) }} ج.م</span>
                            <small class="line-meta">
                                {{ rtrim(rtrim(number_format((float) $line['quantity'], 3), '0'), '.') }}
                                × {{ number_format((float) $line['unit_price'], 2) }} ج.م / وحدة
                                @if($line['discount_amount'] > 0) — خصم {{ number_format((float) $line['discount_amount'], 2) }} @endif
                            </small>
                            @if($line['serial_number'])
                                <small class="line-note">سيريال: {{ $line['serial_number'] }}</small>
                            @endif
                        </button>
                    @empty
                        <div class="side-empty">
                            <x-filament::icon icon="heroicon-o-shopping-cart"/>
                            <p>المس منتجاً أو امسح الباركود لبدء البيع</p>
                        </div>
                    @endforelse
                </div>

                <div class="side-summary">
                    <div class="summary-total">الإجمالي: <strong>{{ number_format($this->totals['grand_total'], 2) }} ج.م</strong></div>
                    <div class="summary-tax">الضريبة: {{ number_format($this->totals['tax'], 2) }} ج.م</div>
                </div>

                <div class="side-actions">
                    <button type="button" wire:click="openPanel('invoices')" @class(['panel-active' => $activePanel === 'invoices'])>
                        <x-filament::icon icon="heroicon-o-arrow-uturn-right"/> استرجاع
                    </button>
                    <button type="button" wire:click="openPanel('customer')" @class(['panel-active' => $activePanel === 'customer', 'has-value' => filled($customerId)])>
                        <x-filament::icon icon="heroicon-o-user"/>
                        {{ $customerId ? \Illuminate\Support\Str::limit($this->customers->firstWhere('id', $customerId)?->name, 12) : 'العميل' }}
                    </button>
                    <button type="button" wire:click="openPanel('options')" @class(['panel-active' => $activePanel === 'options', 'has-value' => $invoiceDiscount > 0 || filled($couponCode) || $loyaltyPoints > 0 || filled($notes)])>
                        <x-filament::icon icon="heroicon-o-receipt-percent"/> خصم وملاحظة
                    </button>
                    <button type="button" wire:click="hold" @disabled(empty($cart))>
                        <x-filament::icon icon="heroicon-o-pause"/> تعليق F6
                    </button>
                    <button type="button" wire:click="openPanel('held')" @class(['panel-active' => $activePanel === 'held'])>
                        <x-filament::icon icon="heroicon-o-clock"/> المعلقة ({{ $this->heldDocuments->count() }})
                    </button>
                    <button type="button" wire:click="openPanel('session')" @class(['panel-active' => $activePanel === 'session'])>
                        <x-filament::icon icon="heroicon-o-lock-closed"/> الوردية
                    </button>
                </div>

                <div class="side-pay">
                    <button type="button" class="pay-button" wire:click="togglePayment" @disabled(empty($cart))>
                        <x-filament::icon icon="heroicon-o-banknotes"/>
                        <span>الدفع</span>
                        <small>{{ number_format($this->totals['grand_total'], 2) }} ج.م</small>
                    </button>

                    <div class="numpad">
                        @foreach([['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']] as $row)
                            @foreach($row as $digit)
                                <button type="button" class="np-key" wire:click="numpadPress('{{ $digit }}')">{{ $digit }}</button>
                            @endforeach
                            @if($loop->first)
                                <button type="button" @class(['np-mode', 'active' => $numpadMode === 'quantity']) wire:click="setNumpadMode('quantity')">الكمية</button>
                            @elseif($loop->iteration === 2)
                                <button type="button" @class(['np-mode', 'active' => $numpadMode === 'discount_amount']) wire:click="setNumpadMode('discount_amount')">خصم</button>
                            @else
                                <button type="button" @class(['np-mode', 'active' => $numpadMode === 'unit_price']) wire:click="setNumpadMode('unit_price')">السعر</button>
                            @endif
                        @endforeach
                        <button type="button" class="np-key" wire:click="numpadPress('sign')">+/−</button>
                        <button type="button" class="np-key" wire:click="numpadPress('0')">0</button>
                        <button type="button" class="np-key" wire:click="numpadPress('.')">.</button>
                        <button type="button" class="np-key np-danger" wire:click="numpadPress('backspace')">⌫</button>
                    </div>
                </div>
            </aside>
        </div>

        {{-- ================= لوحة الدفع ================= --}}
        @if($showPayment)
            <div class="pos-overlay" wire:click.self="togglePayment">
                <div class="pos-modal payment-modal">
                    <header>
                        <h3>الدفع — {{ number_format($this->totals['grand_total'], 2) }} ج.م</h3>
                        <button type="button" wire:click="togglePayment"><x-filament::icon icon="heroicon-o-x-mark"/></button>
                    </header>

                    <div class="payment-type">
                        @foreach(['cash' => 'نقدي', 'credit' => 'آجل', 'installment' => 'تقسيط', 'mixed' => 'دفع متعدد'] as $value => $label)
                            <button type="button" wire:click="$set('paymentType', '{{ $value }}')" @class(['active' => $paymentType === $value])>{{ $label }}</button>
                        @endforeach
                    </div>

                    @if(in_array($paymentType, ['cash', 'mixed', 'installment']))
                        <div class="payments">
                            @foreach($payments as $index => $payment)
                                <div class="payment-row" wire:key="payment-{{ $index }}">
                                    <select wire:model.live="payments.{{ $index }}.method">
                                        @foreach(\App\Models\PosPayment::methodLabels() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="0" step=".01" wire:model.live.debounce.300ms="payments.{{ $index }}.amount" placeholder="المبلغ">
                                    <input type="text" wire:model="payments.{{ $index }}.reference" placeholder="مرجع العملية">
                                    @if(count($payments) > 1)
                                        <button type="button" wire:click="removePayment({{ $index }})"><x-filament::icon icon="heroicon-o-trash"/></button>
                                    @endif
                                </div>
                            @endforeach
                            <button type="button" class="add-payment" wire:click="addPayment">+ إضافة وسيلة دفع</button>
                        </div>

                        @php
                            $paid = collect($payments)->sum(fn ($payment) => (float) ($payment['amount'] ?? 0));
                            $remaining = $this->totals['grand_total'] - $paid;
                        @endphp
                        <div class="payment-balance">
                            <span>المدفوع: <strong>{{ number_format($paid, 2) }}</strong></span>
                            <span @class(['balance-due' => $remaining > 0.009, 'balance-change' => $remaining < -0.009])>
                                {{ $remaining >= 0 ? 'المتبقي' : 'الباقي للعميل' }}: <strong>{{ number_format(abs($remaining), 2) }}</strong>
                            </span>
                        </div>
                    @endif

                    @if($paymentType === 'installment')
                        <div class="installment-options">
                            <label>عدد الأقساط<input type="number" min="1" wire:model="installmentCount"></label>
                            <label>التكرار<select wire:model="installmentFrequency"><option value="monthly">شهري</option><option value="weekly">أسبوعي</option></select></label>
                            <label>أول استحقاق<input type="date" wire:model="firstDueDate"></label>
                        </div>
                    @endif

                    <button type="button" class="confirm-button" @click="online ? $wire.checkout() : queueOffline()">
                        <span wire:loading.remove wire:target="checkout">تأكيد البيع F4</span>
                        <span wire:loading wire:target="checkout">جارٍ الحفظ...</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ================= لوحات جانبية ================= --}}
        @if($activePanel)
            <div class="pos-overlay" wire:click.self="openPanel(null)">
                <div class="pos-modal">
                    <header>
                        <h3>
                            @switch($activePanel)
                                @case('customer') اختيار العميل @break
                                @case('options') الخصومات والملاحظات @break
                                @case('held') الفواتير المعلقة @break
                                @case('invoices') آخر الفواتير — طباعة واسترجاع @break
                                @case('session') إغلاق وردية الكاشير @break
                            @endswitch
                        </h3>
                        <button type="button" wire:click="openPanel(null)"><x-filament::icon icon="heroicon-o-x-mark"/></button>
                    </header>

                    @if($activePanel === 'customer')
                        <div class="customer-list">
                            <button type="button" @class(['selected' => !$customerId]) wire:click="$set('customerId', null)">عميل نقدي</button>
                            @foreach($this->customers as $customer)
                                <button type="button" @class(['selected' => $customerId === $customer->id]) wire:click="$set('customerId', {{ $customer->id }})">
                                    {{ $customer->name }}
                                    <small>{{ number_format((float) $customer->current_balance, 2) }} ج.م</small>
                                </button>
                            @endforeach
                        </div>
                    @elseif($activePanel === 'options')
                        <div class="options-grid">
                            <label>خصم الفاتورة<input type="number" min="0" step=".01" wire:model.live.debounce.350ms="invoiceDiscount"></label>
                            <label>كود الكوبون<input type="text" wire:model.live.debounce.350ms="couponCode" placeholder="SALE10"></label>
                            <label>نقاط الولاء<input type="number" min="0" wire:model.live="loyaltyPoints"></label>
                            <label>ملاحظات الفاتورة<input type="text" wire:model="notes"></label>
                        </div>
                    @elseif($activePanel === 'held')
                        <div class="invoice-chips">
                            @forelse($this->heldDocuments as $document)
                                <button type="button" wire:click="resume({{ $document->id }})">
                                    {{ $document->number }} — {{ number_format((float) $document->grand_total, 2) }} ج.م
                                </button>
                            @empty
                                <p class="panel-empty">لا توجد فواتير معلقة.</p>
                            @endforelse
                        </div>
                    @elseif($activePanel === 'invoices')
                        <div class="invoice-rows">
                            @forelse($this->recentDocuments as $document)
                                <div class="invoice-row">
                                    <span>{{ $document->number }} — {{ number_format((float) $document->grand_total, 2) }} ج.م</span>
                                    <span class="invoice-row-actions">
                                        <button type="button" wire:click="reprint({{ $document->id }}, '80mm')">80mm</button>
                                        <button type="button" wire:click="reprint({{ $document->id }}, '58mm')">58mm</button>
                                        <button type="button" wire:click="reprint({{ $document->id }}, 'a4')">A4</button>
                                        <button type="button" class="danger" wire:click="returnInvoice({{ $document->id }})" wire:confirm="هل تريد استرجاع الفاتورة بالكامل؟">استرجاع</button>
                                    </span>
                                </div>
                            @empty
                                <p class="panel-empty">لا توجد فواتير في هذه الوردية بعد.</p>
                            @endforelse
                        </div>
                    @elseif($activePanel === 'session')
                        <div class="session-close">
                            <label>الرصيد الفعلي في الدرج<input type="number" min="0" step=".01" wire:model="closingBalance"></label>
                            <button type="button" class="danger" wire:click="closeCurrentSession" wire:confirm="هل تريد إغلاق الوردية الحالية؟">إغلاق الوردية</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <style>
        .pos-app {
            --pos-violet:#5458f0; --pos-violet-deep:#4238ca; --pos-violet-darker:#37309f;
            --pos-grad:linear-gradient(120deg, #5458f0, #4238ca);
            --pos-violet-soft:#eef0fe; --pos-violet-tint:rgba(84, 88, 240, .1);
            --pos-amber:#f59e0b; --pos-green:#16a34a; --pos-red:#dc2626;
            --pos-border:#e4e7f2; --pos-bg:#f1f3fa; --pos-card:#ffffff; --pos-muted:#64748b;
            position:fixed; inset:0;
            background:
                radial-gradient(circle at 8% 4%, rgba(84, 88, 240, .08), transparent 22rem),
                radial-gradient(circle at 94% 90%, rgba(245, 158, 11, .07), transparent 20rem),
                var(--pos-bg);
            direction:rtl; overflow:hidden; font-size:.95rem; z-index:10;
        }
        .dark .pos-app { --pos-border:#33395a; --pos-bg:#0b1020; --pos-card:#141a30; --pos-violet-soft:#1d2246; --pos-muted:#94a3b8; }
        .pos-app button { touch-action:manipulation; }

        .pos-boot { height:100%; display:grid; place-items:center; align-content:center; text-align:center; color:var(--pos-muted); gap:.4rem; padding:2rem; }
        .pos-boot h2,.pos-boot h3 { font-weight:900; color:#334155; } .dark .pos-boot h2,.dark .pos-boot h3 { color:#cbd5e1; }
        .pos-boot-icon { width:3.4rem; }
        .pos-boot-link { margin-top:.8rem; background:var(--pos-grad); color:#fff; font-weight:800; padding:.6rem 1.4rem; border-radius:.7rem; box-shadow:0 10px 24px rgba(66, 56, 202, .35); }

        .pos-screen { display:grid; grid-template-columns:minmax(0,1fr) clamp(360px, 32vw, 430px); height:100%; }

        /* ---------- المنتجات ---------- */
        .pos-main { display:flex; flex-direction:column; min-width:0; }
        .main-head { display:flex; align-items:center; gap:.5rem; padding:.55rem .7rem; background:var(--pos-card); border-bottom:1px solid var(--pos-border); box-shadow:0 4px 18px rgba(30, 27, 75, .05); }
        .head-btn { width:42px; height:42px; flex:none; border:1px solid var(--pos-border); border-radius:.6rem; display:grid; place-items:center; background:transparent; color:var(--pos-violet-deep); transition:.15s; }
        .head-btn:hover { background:var(--pos-violet-tint); border-color:var(--pos-violet); }
        .head-btn svg { width:1.25rem; }
        .crumb { display:flex; align-items:center; gap:.4rem; font-weight:800; white-space:nowrap; }
        .crumb button { color:var(--pos-muted); }
        .crumb strong { color:var(--pos-violet-deep); }
        .head-search { flex:1; display:flex; align-items:center; gap:.5rem; border:1.5px solid var(--pos-border); border-radius:.7rem; padding:.35rem .6rem; min-width:130px; background:var(--pos-bg); transition:.15s; }
        .head-search:focus-within { border-color:var(--pos-violet); background:var(--pos-card); box-shadow:0 0 0 4px rgba(84, 88, 240, .14); }
        .head-search svg { width:1.1rem; color:#94a3b8; }
        .head-search input { flex:1; border:0; outline:0; background:transparent; min-width:60px; color:inherit; }
        .head-terminal { border:1px solid var(--pos-border); border-radius:.6rem; min-height:42px; padding:.3rem .5rem; background:transparent; max-width:150px; }
        .online-pill { border-radius:999px; padding:.25rem .6rem; font-size:.7rem; color:white; white-space:nowrap; font-weight:800; }
        .is-online { background:var(--pos-green); box-shadow:0 4px 10px rgba(22, 163, 74, .35); }
        .is-offline { background:var(--pos-red); box-shadow:0 4px 10px rgba(220, 38, 38, .35); }

        .category-strip { display:flex; gap:.45rem; overflow-x:auto; padding:.55rem .7rem; scrollbar-width:thin; }
        .category-strip button { flex:none; min-height:40px; padding:.4rem .95rem; border:1px solid var(--pos-border); border-radius:999px; font-weight:700; background:var(--pos-card); color:var(--pos-muted); transition:.15s; }
        .category-strip button:hover { border-color:var(--pos-violet); color:var(--pos-violet-deep); }
        .category-strip button.active { background:var(--pos-grad); border-color:transparent; color:#fff; box-shadow:0 8px 18px rgba(66, 56, 202, .3); }

        .product-grid { flex:1; overflow-y:auto; padding:.3rem .7rem .9rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(128px, 1fr)); gap:.6rem; align-content:start; scrollbar-width:thin; }
        .product-card { background:var(--pos-card); border:1px solid var(--pos-border); border-radius:.85rem; overflow:hidden; display:flex; flex-direction:column; text-align:right; min-height:172px; transition:.15s; box-shadow:0 2px 10px rgba(30, 27, 75, .04); }
        .product-card:hover,.product-card:focus-visible { border-color:var(--pos-violet); box-shadow:0 10px 24px rgba(84, 88, 240, .22); transform:translateY(-3px); }
        .product-image { height:96px; background:linear-gradient(150deg, var(--pos-violet-soft), var(--pos-bg)); display:grid; place-items:center; position:relative; overflow:hidden; flex:none; }
        .product-image img { width:100%; height:100%; object-fit:cover; }
        .product-image svg { width:2.2rem; color:#a5b4fc; }
        .stock-warning { position:absolute; top:.3rem; left:.3rem; background:var(--pos-red); color:white; border-radius:999px; padding:.1rem .4rem; font-size:.68rem; font-weight:800; }
        .product-card strong { padding:.4rem .55rem 0; font-size:.82rem; line-height:1.35; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
        .product-price { padding:.15rem .55rem .5rem; margin-top:auto; color:var(--pos-violet-deep); font-weight:900; font-size:.85rem; }
        .dark .product-price { color:#a5b4fc; }
        .product-empty { grid-column:1/-1; }

        /* ---------- السلة ---------- */
        .pos-side { display:flex; flex-direction:column; background:var(--pos-card); border-inline-start:1px solid var(--pos-border); min-width:0; box-shadow:-8px 0 30px rgba(30, 27, 75, .06); }
        .side-lines { flex:1; overflow-y:auto; min-height:90px; scrollbar-width:thin; }
        .side-line { display:grid; grid-template-columns:1fr auto; width:100%; text-align:right; padding:.55rem .8rem; border-bottom:1px solid var(--pos-border); gap:0 .5rem; transition:background .15s; }
        .side-line:hover { background:var(--pos-violet-tint); }
        .side-line .line-name { font-weight:800; }
        .side-line .line-total { font-weight:900; color:var(--pos-green); white-space:nowrap; }
        .side-line .line-meta { grid-column:1/-1; color:var(--pos-muted); font-size:.74rem; }
        .side-line .line-note { grid-column:1/-1; color:#92400e; background:#fef3c7; border-radius:.4rem; padding:.15rem .45rem; margin-top:.2rem; font-size:.72rem; justify-self:start; }
        .side-line.selected { background:var(--pos-violet-soft); box-shadow:inset 4px 0 0 var(--pos-violet); }
        .side-line.selected .line-name { color:var(--pos-violet-deep); }
        .dark .side-line.selected .line-name { color:#c7d2fe; }
        .side-empty { display:grid; place-items:center; text-align:center; color:#94a3b8; padding:2.2rem 1rem; gap:.4rem; }
        .side-empty svg { width:2.6rem; color:#a5b4fc; }

        .side-summary { display:flex; align-items:baseline; justify-content:space-between; padding:.6rem .8rem; border-top:1px solid var(--pos-border); background:linear-gradient(150deg, var(--pos-violet-soft), transparent); }
        .summary-total { font-size:1.15rem; font-weight:900; }
        .summary-total strong { color:var(--pos-violet-deep); font-size:1.35rem; }
        .dark .summary-total strong { color:#a5b4fc; }
        .summary-tax { color:var(--pos-muted); font-size:.8rem; }

        .side-actions { display:grid; grid-template-columns:repeat(3, 1fr); gap:1px; background:var(--pos-border); border-block:1px solid var(--pos-border); }
        .side-actions button { display:flex; align-items:center; justify-content:center; gap:.35rem; background:var(--pos-card); min-height:48px; font-size:.78rem; font-weight:700; padding:.3rem; color:#475569; transition:.15s; }
        .dark .side-actions button { color:#cbd5e1; }
        .side-actions button:hover { background:var(--pos-violet-tint); color:var(--pos-violet-deep); }
        .side-actions button svg { width:1.05rem; flex:none; }
        .side-actions button:disabled { opacity:.4; }
        .side-actions button.panel-active,.side-actions button.has-value { background:var(--pos-grad); color:white; }

        .side-pay { display:grid; grid-template-columns:1fr 1.7fr; }
        .pay-button { background:var(--pos-grad); color:white; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.25rem; font-weight:900; font-size:1.15rem; transition:filter .15s; }
        .pay-button:hover { filter:brightness(1.08); }
        .pay-button svg { width:1.6rem; }
        .pay-button small { font-weight:700; font-size:.78rem; opacity:.85; }
        .pay-button:disabled { opacity:.55; }

        .numpad { display:grid; grid-template-columns:repeat(4, 1fr); }
        .numpad button { min-height:56px; border:1px solid var(--pos-border); font-weight:800; font-size:1.05rem; background:var(--pos-card); margin:-1px 0 0 -1px; color:inherit; transition:background .1s; }
        .numpad button:hover { background:var(--pos-violet-tint); }
        .numpad button:active { background:var(--pos-violet-soft); }
        .np-mode { font-size:.76rem !important; color:var(--pos-muted); background:var(--pos-bg) !important; }
        .np-mode.active { background:var(--pos-grad) !important; color:#fff !important; }
        .np-danger { color:var(--pos-red); }

        /* ---------- النوافذ ---------- */
        .pos-overlay { position:fixed; inset:0; background:rgba(30, 27, 75, .55); backdrop-filter:blur(3px); display:grid; place-items:center; padding:1rem; z-index:30; }
        .pos-modal { background:var(--pos-card); border-radius:1.1rem; width:min(560px, 100%); max-height:88vh; overflow-y:auto; padding:1rem; display:grid; gap:.8rem; direction:rtl; box-shadow:0 24px 60px rgba(30, 27, 75, .3); }
        .pos-modal header { display:flex; align-items:center; justify-content:space-between; }
        .pos-modal header h3 { font-weight:900; font-size:1.05rem; color:var(--pos-violet-deep); }
        .dark .pos-modal header h3 { color:#c7d2fe; }
        .pos-modal header svg { width:1.3rem; }
        .pos-modal input,.pos-modal select { border:1.5px solid var(--pos-border); border-radius:.6rem; min-height:44px; padding:.5rem .65rem; background:transparent; width:100%; color:inherit; }
        .pos-modal input:focus,.pos-modal select:focus { border-color:var(--pos-violet); outline:0; box-shadow:0 0 0 4px rgba(84, 88, 240, .14); }
        .pos-modal label { display:grid; gap:.25rem; font-size:.8rem; color:var(--pos-muted); font-weight:700; }

        .payment-type { display:grid; grid-template-columns:repeat(4, 1fr); gap:.4rem; }
        .payment-type button { min-height:46px; border:1px solid var(--pos-border); border-radius:.6rem; font-weight:800; color:var(--pos-muted); transition:.15s; }
        .payment-type button:hover { border-color:var(--pos-violet); color:var(--pos-violet-deep); }
        .payment-type button.active { background:var(--pos-grad); border-color:transparent; color:#fff; box-shadow:0 6px 14px rgba(66, 56, 202, .3); }
        .payments { display:grid; gap:.4rem; }
        .payment-row { display:grid; grid-template-columns:1fr .8fr 1fr auto; gap:.35rem; }
        .payment-row button svg { width:1.05rem; color:var(--pos-red); }
        .add-payment { justify-self:start; color:var(--pos-violet-deep); font-weight:800; font-size:.82rem; }
        .dark .add-payment { color:#a5b4fc; }
        .payment-balance { display:flex; justify-content:space-between; background:var(--pos-violet-soft); border-radius:.6rem; padding:.55rem .8rem; font-weight:700; }
        .balance-due { color:var(--pos-red); } .balance-change { color:var(--pos-green); }
        .installment-options { display:grid; grid-template-columns:repeat(3, 1fr); gap:.45rem; }
        .confirm-button { background:linear-gradient(120deg, #22c55e, #15803d); color:white; min-height:54px; border-radius:.7rem; font-weight:900; font-size:1.05rem; box-shadow:0 10px 22px rgba(22, 163, 74, .3); transition:filter .15s; }
        .confirm-button:hover { filter:brightness(1.06); }

        .customer-list { display:grid; gap:.35rem; }
        .customer-list button { display:flex; justify-content:space-between; align-items:center; border:1px solid var(--pos-border); border-radius:.6rem; padding:.6rem .8rem; font-weight:700; text-align:right; transition:.15s; }
        .customer-list button:hover { border-color:var(--pos-violet); }
        .customer-list button.selected { border-color:var(--pos-violet); background:var(--pos-violet-soft); color:var(--pos-violet-deep); }
        .dark .customer-list button.selected { color:#c7d2fe; }
        .customer-list small { color:var(--pos-muted); }
        .options-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.55rem; }
        .invoice-chips { display:flex; flex-wrap:wrap; gap:.4rem; }
        .invoice-chips button { border:1px solid var(--pos-border); border-radius:.55rem; padding:.5rem .7rem; font-weight:700; transition:.15s; }
        .invoice-chips button:hover { border-color:var(--pos-violet); background:var(--pos-violet-tint); color:var(--pos-violet-deep); }
        .invoice-rows { display:grid; gap:.4rem; }
        .invoice-row { display:flex; justify-content:space-between; align-items:center; gap:.5rem; border:1px solid var(--pos-border); border-radius:.6rem; padding:.5rem .7rem; flex-wrap:wrap; }
        .invoice-row-actions { display:flex; gap:.3rem; flex-wrap:wrap; }
        .invoice-row-actions button { border:1px solid var(--pos-border); border-radius:.45rem; padding:.25rem .5rem; font-size:.75rem; font-weight:700; }
        .invoice-row-actions button:hover { border-color:var(--pos-violet); color:var(--pos-violet-deep); }
        .invoice-row-actions .danger,.session-close .danger { background:var(--pos-red); color:white; border-color:var(--pos-red); }
        .invoice-row-actions .danger:hover { color:white; border-color:var(--pos-red); filter:brightness(1.1); }
        .session-close { display:grid; gap:.6rem; }
        .session-close .danger { min-height:48px; border-radius:.6rem; font-weight:800; }
        .panel-empty { color:#94a3b8; text-align:center; padding:1rem; }

        @media (max-width: 900px) {
            .pos-screen { grid-template-columns:1fr; grid-template-rows:1fr auto; }
            .pos-side { border-inline-start:0; border-top:1px solid var(--pos-border); max-height:56vh; }
            .side-lines { min-height:70px; }
            .numpad button { min-height:48px; }
            .main-head { flex-wrap:wrap; }
            .head-search { order:10; flex-basis:100%; }
        }
    </style>

    @script
    <script>
        Alpine.data('posApp', ($wire) => ({
            online: navigator.onLine,
            channel: null,
            init() {
                this.channel = 'BroadcastChannel' in window ? new BroadcastChannel('daftar-pos-display') : null;
                window.addEventListener('online', () => { this.online = true; this.syncOffline(); });
                window.addEventListener('offline', () => this.online = false);
                window.addEventListener('keydown', event => {
                    if (event.key === 'F2') { event.preventDefault(); document.getElementById('pos-search')?.focus(); }
                    if (event.key === 'F4') { event.preventDefault(); this.online ? $wire.checkout() : this.queueOffline(); }
                    if (event.key === 'F6') { event.preventDefault(); $wire.hold(); }
                    if (event.key === '+' && !['INPUT','TEXTAREA'].includes(event.target.tagName)) { event.preventDefault(); $wire.incrementLast(); }
                    if (event.key === '-' && !['INPUT','TEXTAREA'].includes(event.target.tagName)) { event.preventDefault(); $wire.decrementLast(); }
                    if (event.key === 'Escape' && !['INPUT','TEXTAREA'].includes(event.target.tagName)) { event.preventDefault(); $wire.clearCart(); }
                });
                if ('serviceWorker' in navigator) navigator.serviceWorker.register('{{ route('pos.service-worker') }}');
                this.syncOffline();
            },
            async db() {
                return await new Promise((resolve, reject) => {
                    const request = indexedDB.open('daftar-pos', 1);
                    request.onupgradeneeded = () => request.result.createObjectStore('sales', { keyPath: 'client_uuid' });
                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error);
                });
            },
            async queueOffline() {
                const payload = await $wire.offlinePayload();
                const db = await this.db();
                const tx = db.transaction('sales', 'readwrite');
                tx.objectStore('sales').put(payload);
                await new Promise(resolve => tx.oncomplete = resolve);
                alert('تم حفظ الفاتورة على الجهاز وستتم مزامنتها عند عودة الاتصال.');
            },
            async syncOffline() {
                if (!navigator.onLine) return;
                const db = await this.db();
                const tx = db.transaction('sales', 'readonly');
                const request = tx.objectStore('sales').getAll();
                request.onsuccess = async () => {
                    for (const sale of request.result) {
                        try {
                            const response = await fetch('{{ route('pos.sync') }}', {
                                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                body:JSON.stringify(sale),
                            });
                            if (response.ok) {
                                const remove = db.transaction('sales','readwrite');
                                remove.objectStore('sales').delete(sale.client_uuid);
                            }
                        } catch (_) {}
                    }
                };
            },
            printUrl(url) { window.open(url, '_blank', 'width=500,height=760'); },
            saleCompleted(detail) {
                this.printUrl(detail.printUrl);
                if (detail.openDrawer) this.openDrawer();
                if (detail.kitchenUrl) window.open(detail.kitchenUrl, '_blank', 'width=500,height=760');
            },
            updateCustomerDisplay(detail) { this.channel?.postMessage(detail); },
            async openDrawer() {
                if (window.qz?.websocket) {
                    try {
                        if (!qz.websocket.isActive()) await qz.websocket.connect();
                        const config = qz.configs.create('{{ $this->terminal?->printer_name }}');
                        await qz.print(config, [{ type:'raw', format:'command', flavor:'plain', data:'\\x1B\\x70\\x00\\x19\\xFA' }]);
                        return;
                    } catch (_) {}
                }
                alert('يحتاج فتح الدرج المباشر إلى QZ Tray أو طابعة تدعم أمر ESC/POS.');
            },
            async readScale() {
                if (!('serial' in navigator)) return alert('المتصفح لا يدعم الاتصال بالميزان عبر Serial.');
                try {
                    const port = await navigator.serial.requestPort();
                    await port.open({ baudRate: 9600 });
                    const reader = port.readable.getReader();
                    const { value } = await reader.read();
                    reader.releaseLock();
                    await port.close();
                    const weight = new TextDecoder().decode(value).match(/[0-9.]+/)?.[0];
                    if (weight) alert('قراءة الميزان: ' + weight);
                } catch (_) {}
            },
        }));
    </script>
    @endscript
</div>
