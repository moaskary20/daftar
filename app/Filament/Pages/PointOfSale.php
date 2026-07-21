<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\PosPayment;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\SalesDocument;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\PosService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PointOfSale extends Page
{
    protected static string $layout = 'filament-panels::components.layout.base';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = 'نقطة البيع POS';

    protected static ?string $navigationLabel = 'شاشة البيع';

    protected static ?string $title = 'نقطة البيع';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.point-of-sale';

    public ?int $terminalId = null;

    public ?int $sessionId = null;

    public string $search = '';

    public ?int $categoryId = null;

    public ?int $customerId = null;

    public array $cart = [];

    public string $paymentType = 'cash';

    public float $invoiceDiscount = 0;

    public string $couponCode = '';

    public int $loyaltyPoints = 0;

    public string $notes = '';

    public array $payments = [];

    public int $installmentCount = 3;

    public string $installmentFrequency = 'monthly';

    public ?string $firstDueDate = null;

    public float $closingBalance = 0;

    public ?string $selectedKey = null;

    public string $numpadMode = 'quantity';

    public string $numpadBuffer = '';

    public bool $showPayment = false;

    public ?string $activePanel = null;

    public function mount(): void
    {
        $terminal = PosTerminal::query()->where('is_active', true)->first();
        if (! $terminal && ($warehouse = Warehouse::query()->first())) {
            $treasury = app(AccountingService::class)->defaultFund();
            $terminal = PosTerminal::query()->create([
                'warehouse_id' => $warehouse->id,
                'treasury_id' => $treasury->id,
                'name' => 'الكاشير الرئيسي',
                'code' => 'POS-MAIN',
            ]);
        }
        if ($terminal) {
            $this->selectTerminal($terminal->id);
        }
        $this->firstDueDate = today()->addMonth()->toDateString();
        $this->payments = [['method' => PosPayment::METHOD_CASH, 'amount' => 0, 'reference' => '']];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('pos', 'view');
    }

    public function selectTerminal(int $terminalId): void
    {
        $terminal = PosTerminal::query()->where('is_active', true)->findOrFail($terminalId);
        $session = app(PosService::class)->openSession($terminal);
        $this->terminalId = $terminal->id;
        $this->sessionId = $session->id;
        $this->resetSale();
    }

    public function updatedSearch(string $value): void
    {
        $term = trim($value);
        if ($term === '') {
            return;
        }

        $serial = ProductSerial::query()
            ->where('serial_number', $term)
            ->where('status', 'available')
            ->first();
        if ($serial) {
            $this->addProduct($serial->product_id, $serial->product_variant_id, $serial->serial_number);
            $this->search = '';

            return;
        }

        $variant = ProductVariant::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->where('barcode', $term)->orWhere('sku', $term))
            ->first();
        if ($variant) {
            $this->addProduct($variant->product_id, $variant->id);
            $this->search = '';

            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->where('barcode', $term)->orWhere('sku', $term))
            ->first();
        if ($product) {
            $this->addProduct($product->id);
            $this->search = '';
        }
    }

    public function addProduct(int $productId, ?int $variantId = null, ?string $serial = null): void
    {
        $product = Product::query()->with('variants')->findOrFail($productId);
        $variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;
        if ($product->type === Product::TYPE_VARIABLE && ! $variant) {
            $variant = $product->variants->where('is_active', true)->first();
            if (! $variant) {
                Notification::make()->title('لا توجد متغيرات نشطة لهذا المنتج')->warning()->send();

                return;
            }
        }

        $key = $product->id.'-'.($variant?->id ?? 0).'-'.($serial ?? '');
        if (isset($this->cart[$key]) && ! $serial) {
            $this->cart[$key]['quantity']++;
        } else {
            $price = (float) ($variant?->selling_price ?: $product->selling_price);
            $this->cart[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'name' => $product->name.($variant ? ' - '.$variant->name : ''),
                'image' => $product->image ? Storage::url($product->image) : null,
                'serial_number' => $serial,
                'quantity' => 1,
                'unit_price' => $price,
                'discount_amount' => 0,
                'tax_rate' => 15,
            ];
        }
        $this->selectedKey = $key;
        $this->numpadBuffer = '';
        $this->syncSinglePayment();
        $this->dispatch('pos-cart-updated', cart: array_values($this->cart), totals: $this->totals);
    }

    public function increment(string $key): void
    {
        if (isset($this->cart[$key]) && ! $this->cart[$key]['serial_number']) {
            $this->cart[$key]['quantity']++;
            $this->syncSinglePayment();
        }
    }

    public function decrement(string $key): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }
        if ($this->cart[$key]['quantity'] <= 1) {
            $this->removeItem($key);

            return;
        }
        $this->cart[$key]['quantity']--;
        $this->syncSinglePayment();
    }

    public function removeItem(string $key): void
    {
        unset($this->cart[$key]);
        if ($this->selectedKey === $key) {
            $this->selectedKey = array_key_last($this->cart);
            $this->numpadBuffer = '';
        }
        $this->syncSinglePayment();
    }

    public function incrementLast(): void
    {
        if ($key = array_key_last($this->cart)) {
            $this->increment($key);
        }
    }

    public function decrementLast(): void
    {
        if ($key = array_key_last($this->cart)) {
            $this->decrement($key);
        }
    }

    public function clearCart(): void
    {
        $this->resetSale();
    }

    public function selectLine(string $key): void
    {
        if (isset($this->cart[$key])) {
            $this->selectedKey = $key;
            $this->numpadBuffer = '';
        }
    }

    public function setNumpadMode(string $mode): void
    {
        if (in_array($mode, ['quantity', 'discount_amount', 'unit_price'], true)) {
            $this->numpadMode = $mode;
            $this->numpadBuffer = '';
        }
    }

    public function numpadPress(string $key): void
    {
        if (! $this->selectedKey || ! isset($this->cart[$this->selectedKey])) {
            return;
        }

        if ($key === 'backspace') {
            if ($this->numpadBuffer === '') {
                $this->removeItem($this->selectedKey);

                return;
            }
            $this->numpadBuffer = mb_substr($this->numpadBuffer, 0, -1);
        } elseif ($key === 'sign') {
            $this->decrement($this->selectedKey);
            $this->numpadBuffer = '';

            return;
        } elseif ($key === '.') {
            if (! str_contains($this->numpadBuffer, '.')) {
                $this->numpadBuffer .= ($this->numpadBuffer === '' ? '0.' : '.');
            }
        } elseif (ctype_digit($key)) {
            $this->numpadBuffer .= $key;
        } else {
            return;
        }

        if ($this->numpadBuffer === '' || $this->numpadBuffer === '0.') {
            return;
        }

        $this->updateLine($this->selectedKey, $this->numpadMode, (float) $this->numpadBuffer);
    }

    public function togglePayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('السلة فارغة')->warning()->send();

            return;
        }
        $this->showPayment = ! $this->showPayment;
        $this->activePanel = null;
    }

    public function openPanel(?string $panel): void
    {
        $this->activePanel = $this->activePanel === $panel ? null : $panel;
        $this->showPayment = false;
    }

    public function updateLine(string $key, string $field, mixed $value): void
    {
        if (! isset($this->cart[$key]) || ! in_array($field, ['quantity', 'unit_price', 'discount_amount'], true)) {
            return;
        }
        if ($field === 'unit_price' && ! $this->canOverridePrice()) {
            Notification::make()->title('ليس لديك صلاحية تعديل السعر')->danger()->send();

            return;
        }
        $this->cart[$key][$field] = max($field === 'quantity' ? 0.001 : 0, (float) $value);
        $this->syncSinglePayment();
    }

    public function addPayment(): void
    {
        $this->payments[] = ['method' => PosPayment::METHOD_CARD, 'amount' => 0, 'reference' => ''];
    }

    public function removePayment(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->syncSinglePayment();
    }

    public function updatedInvoiceDiscount(): void
    {
        $this->syncSinglePayment();
    }

    public function updatedCouponCode(): void
    {
        $this->syncSinglePayment();
    }

    public function updatedLoyaltyPoints(): void
    {
        $this->syncSinglePayment();
    }

    public function updatedPaymentType(string $value): void
    {
        $this->payments = $value === 'credit'
            ? []
            : [['method' => PosPayment::METHOD_CASH, 'amount' => 0, 'reference' => '']];
        $this->syncSinglePayment();
    }

    public function checkout(): void
    {
        try {
            $document = app(PosService::class)->checkout($this->payload());
            Notification::make()->title('تم إتمام البيع بنجاح')->body($document->number)->success()->send();
            $this->dispatch(
                'pos-sale-completed',
                printUrl: route('pos.print', ['document' => $document, 'size' => $this->terminal?->receipt_size ?? '80mm']),
                kitchenUrl: route('pos.kitchen', $document),
                openDrawer: (bool) $this->terminal?->cash_drawer_enabled,
            );
            $this->resetSale();
        } catch (ValidationException $exception) {
            Notification::make()->title('تعذر إتمام البيع')->body(collect($exception->errors())->flatten()->first())->danger()->send();
        }
    }

    public function hold(): void
    {
        try {
            $document = app(PosService::class)->hold($this->payload());
            Notification::make()->title('تم تعليق الفاتورة')->body($document->number)->success()->send();
            $this->resetSale();
        } catch (ValidationException $exception) {
            Notification::make()->title('تعذر تعليق الفاتورة')->body(collect($exception->errors())->flatten()->first())->danger()->send();
        }
    }

    public function resume(int $documentId): void
    {
        $document = SalesDocument::query()
            ->where('pos_session_id', $this->sessionId)
            ->where('status', SalesDocument::STATUS_DRAFT)
            ->whereNotNull('held_at')
            ->with('items.product')
            ->findOrFail($documentId);
        $this->resetSale();
        $this->customerId = $document->customer_id;
        foreach ($document->items as $item) {
            if ($item->serial_number) {
                ProductSerial::query()->where('serial_number', $item->serial_number)->update([
                    'status' => 'available', 'sales_document_item_id' => null,
                ]);
            }
            $key = $item->product_id.'-'.($item->product_variant_id ?? 0).'-'.($item->serial_number ?? '');
            $this->cart[$key] = [
                'key' => $key, 'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'name' => $item->product->name, 'image' => $item->product->image ? Storage::url($item->product->image) : null,
                'serial_number' => $item->serial_number, 'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price, 'discount_amount' => (float) $item->discount_amount,
                'tax_rate' => (float) $item->tax_rate,
            ];
        }
        $document->delete();
        $this->syncSinglePayment();
    }

    public function reprint(int $documentId, string $size = '80mm'): void
    {
        $document = SalesDocument::query()->where('channel', 'pos')->findOrFail($documentId);
        $this->dispatch('pos-print', url: route('pos.print', ['document' => $document, 'size' => $size]));
    }

    public function returnInvoice(int $documentId): void
    {
        try {
            $original = SalesDocument::query()
                ->where('pos_session_id', $this->sessionId)
                ->findOrFail($documentId);
            $return = app(PosService::class)->returnInvoice($original);
            Notification::make()->title('تم استرجاع الفاتورة')->body($return->number)->success()->send();
            $this->dispatch('pos-print', url: route('pos.print', ['document' => $return, 'size' => $this->terminal?->receipt_size ?? '80mm']));
        } catch (ValidationException $exception) {
            Notification::make()->title('تعذر الاسترجاع')->body(collect($exception->errors())->flatten()->first())->danger()->send();
        }
    }

    public function closeCurrentSession(): void
    {
        $session = PosSession::query()->findOrFail($this->sessionId);
        $closed = app(PosService::class)->closeSession($session, $this->closingBalance);
        Notification::make()
            ->title('تم إغلاق الوردية')
            ->body('الفرق: '.number_format((float) $closed->difference, 2).' ج.م')
            ->success()
            ->send();
        $this->sessionId = null;
    }

    public function getProductsProperty()
    {
        $term = trim($this->search);

        return Product::query()
            ->with(['category', 'variants'])
            ->where('is_active', true)
            ->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId))
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('serials', fn (Builder $serial) => $serial->where('serial_number', 'like', "%{$term}%"));
            }))
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    public function getCategoriesProperty()
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getCustomersProperty()
    {
        return Customer::query()->where('is_active', true)->orderBy('name')->limit(200)->get();
    }

    public function getHeldDocumentsProperty()
    {
        return SalesDocument::query()
            ->where('pos_session_id', $this->sessionId)
            ->where('status', SalesDocument::STATUS_DRAFT)
            ->whereNotNull('held_at')
            ->latest('held_at')
            ->get();
    }

    public function getRecentDocumentsProperty()
    {
        return SalesDocument::query()
            ->where('pos_session_id', $this->sessionId)
            ->where('status', SalesDocument::STATUS_POSTED)
            ->latest('posted_at')
            ->limit(10)
            ->get();
    }

    public function getTotalsProperty(): array
    {
        $subtotal = collect($this->cart)->sum(fn (array $line): float => max(
            0,
            ((float) $line['quantity'] * (float) $line['unit_price']) - $this->effectiveLineDiscount($line),
        ));
        $tax = collect($this->cart)->sum(function (array $line): float {
            $net = max(0, ((float) $line['quantity'] * (float) $line['unit_price']) - $this->effectiveLineDiscount($line));

            return $net * ((float) $line['tax_rate'] / 100);
        });
        $couponDiscount = 0.0;
        if ($this->couponCode !== '') {
            $coupon = Coupon::query()->where('code', strtoupper(trim($this->couponCode)))->first();
            if ($coupon?->isUsable($subtotal + $tax)) {
                $couponDiscount = $coupon->discountFor($subtotal);
            }
        }
        $grand = max(
            0,
            $subtotal + $tax - (float) $this->invoiceDiscount - $couponDiscount - ($this->loyaltyPoints / 100),
        );

        return ['subtotal' => $subtotal, 'tax' => $tax, 'coupon_discount' => $couponDiscount, 'grand_total' => $grand];
    }

    public function getTerminalProperty(): ?PosTerminal
    {
        return $this->terminalId ? PosTerminal::query()->find($this->terminalId) : null;
    }

    public function offlinePayload(): array
    {
        $payload = $this->payload();
        $payload['terminal_id'] = $this->terminalId;

        return $payload;
    }

    private function payload(): array
    {
        return [
            'pos_session_id' => $this->sessionId,
            'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $this->customerId,
            'items' => array_values($this->cart),
            'payment_type' => $this->paymentType,
            'invoice_discount' => $this->invoiceDiscount,
            'coupon_code' => $this->couponCode,
            'loyalty_points' => $this->loyaltyPoints,
            'notes' => $this->notes,
            'payments' => array_values($this->payments),
            'installment' => [
                'count' => $this->installmentCount,
                'frequency' => $this->installmentFrequency,
                'first_due_date' => $this->firstDueDate,
            ],
        ];
    }

    private function syncSinglePayment(): void
    {
        if (count($this->payments) === 1 && in_array($this->paymentType, ['cash', 'mixed'], true)) {
            $this->payments[0]['amount'] = round($this->totals['grand_total'], 2);
        }
    }

    private function resetSale(): void
    {
        $this->cart = [];
        $this->customerId = null;
        $this->paymentType = 'cash';
        $this->invoiceDiscount = 0;
        $this->couponCode = '';
        $this->loyaltyPoints = 0;
        $this->notes = '';
        $this->payments = [['method' => PosPayment::METHOD_CASH, 'amount' => 0, 'reference' => '']];
        $this->selectedKey = null;
        $this->numpadBuffer = '';
        $this->numpadMode = 'quantity';
        $this->showPayment = false;
        $this->activePanel = null;
    }

    private function canOverridePrice(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('pos_price_override', 'update');
    }

    private function effectiveLineDiscount(array $line): float
    {
        $manual = max(0, (float) $line['discount_amount']);
        $product = Product::query()->find($line['product_id']);
        if (! $product) {
            return $manual;
        }
        $promotion = Promotion::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->where('product_id', $product->id)->orWhere('category_id', $product->category_id))
            ->get()
            ->first(fn (Promotion $promotion): bool => $promotion->isActiveFor($product, (float) $line['quantity']));
        if (! $promotion) {
            return $manual;
        }
        $promotionDiscount = $promotion->discount_type === 'percentage'
            ? (float) $line['quantity'] * (float) $line['unit_price'] * ((float) $promotion->value / 100)
            : (float) $promotion->value;

        return max($manual, $promotionDiscount);
    }
}
