<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\LoyaltyAccount;
use App\Models\PosPayment;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\SalesDocument;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function openSession(PosTerminal $terminal, float $openingBalance = 0): PosSession
    {
        $existing = PosSession::query()
            ->where('pos_terminal_id', $terminal->id)
            ->where('user_id', auth()->id())
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        return $existing ?? PosSession::query()->create([
            'pos_terminal_id' => $terminal->id,
            'user_id' => auth()->id(),
            'warehouse_id' => $terminal->warehouse_id,
            'opening_balance' => $openingBalance,
            'expected_balance' => $openingBalance,
        ]);
    }

    public function closeSession(PosSession $session, float $closingBalance, ?string $notes = null): PosSession
    {
        $this->assertOpenSession($session);
        $cash = (float) $session->payments()
            ->where('method', PosPayment::METHOD_CASH)
            ->where('status', 'completed')
            ->sum('amount');
        $refunds = (float) $session->payments()
            ->where('method', PosPayment::METHOD_CASH)
            ->where('status', 'refunded')
            ->sum('amount');
        $expected = (float) $session->opening_balance + $cash - $refunds;
        $session->update([
            'status' => PosSession::STATUS_CLOSED,
            'expected_balance' => $expected,
            'closing_balance' => $closingBalance,
            'difference' => $closingBalance - $expected,
            'closed_at' => now(),
            'notes' => $notes,
        ]);

        return $session->fresh();
    }

    public function collectInstallment(InstallmentPayment $installment, float $amount, string $method = PosPayment::METHOD_CASH): InstallmentPayment
    {
        return DB::transaction(function () use ($installment, $amount, $method): InstallmentPayment {
            $installment = InstallmentPayment::query()->with('plan.document')->lockForUpdate()->findOrFail($installment->id);
            $remaining = (float) $installment->amount - (float) $installment->paid_amount;
            if ($amount <= 0 || $amount > $remaining + 0.01) {
                throw ValidationException::withMessages(['amount' => 'مبلغ التحصيل غير صالح أو يتجاوز القسط المتبقي.']);
            }
            $terminal = PosTerminal::query()->where('is_active', true)->first();
            $fund = $this->resolveFund($terminal, $method);
            $payment = $installment->plan->document->payments()->create([
                'method' => $method,
                'amount' => $amount,
                'fund_type' => $fund->getMorphClass(),
                'fund_id' => $fund->id,
                'reference' => 'قسط رقم '.$installment->sequence,
                'paid_at' => now(),
                'metadata' => ['installment_payment_id' => $installment->id],
            ]);
            app(LedgerService::class)->customerEntry(
                $installment->plan->customer_id,
                CustomerTransaction::TYPE_PAYMENT,
                credit: $amount,
                reference: $payment,
                notes: 'تحصيل قسط للفاتورة '.$installment->plan->document->number,
                paymentMethod: $method,
                fundType: $fund instanceof Treasury ? 'treasury' : 'bank',
                fundId: $fund->id,
            );
            $installment->increment('paid_amount', $amount);
            $installment->refresh();
            if ((float) $installment->paid_amount >= (float) $installment->amount - 0.01) {
                $installment->update(['status' => 'paid', 'paid_at' => now()]);
            } else {
                $installment->update(['status' => 'partial']);
            }
            $hasPending = $installment->plan->installments()->where('status', '!=', 'paid')->exists();
            if (! $hasPending) {
                $installment->plan->update(['status' => 'completed']);
            }

            return $installment->fresh();
        });
    }

    public function hold(array $data): SalesDocument
    {
        return $this->createDocument($data, post: false);
    }

    public function checkout(array $data): SalesDocument
    {
        if (! empty($data['client_uuid'])) {
            $existing = SalesDocument::query()->where('client_uuid', $data['client_uuid'])->first();
            if ($existing) {
                return $existing->load(['items.product', 'payments', 'customer']);
            }
        }

        return $this->createDocument($data, post: true);
    }

    public function returnInvoice(SalesDocument $original, string $method = PosPayment::METHOD_CASH): SalesDocument
    {
        if ($original->status !== SalesDocument::STATUS_POSTED || $original->type !== SalesDocument::TYPE_INVOICE) {
            throw ValidationException::withMessages(['invoice' => 'يمكن استرجاع فاتورة مبيعات مرحّلة فقط.']);
        }
        if (SalesDocument::query()->where('source_document_id', $original->id)->where('type', SalesDocument::TYPE_RETURN)->exists()) {
            throw ValidationException::withMessages(['invoice' => 'تم استرجاع هذه الفاتورة مسبقاً.']);
        }

        return DB::transaction(function () use ($original, $method): SalesDocument {
            $return = SalesDocument::query()->create([
                'customer_id' => $original->customer_id,
                'warehouse_id' => $original->warehouse_id,
                'pos_session_id' => $original->pos_session_id,
                'source_document_id' => $original->id,
                'type' => SalesDocument::TYPE_RETURN,
                'channel' => 'pos',
                'payment_type' => $method,
                'document_date' => today(),
                'invoice_discount' => $original->invoice_discount,
                'notes' => 'استرجاع كامل للفاتورة '.$original->number,
            ]);

            foreach ($original->items as $item) {
                $return->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'description' => $item->description,
                    'serial_number' => $item->serial_number,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_rate' => $item->tax_rate,
                ]);
            }

            $return->recalculateTotals();
            app(SalesService::class)->post($return->fresh(['items', 'customer', 'sourceDocument']));
            foreach ($original->items->whereNotNull('serial_number') as $item) {
                ProductSerial::query()->where('serial_number', $item->serial_number)->update([
                    'status' => 'available',
                    'sales_document_item_id' => null,
                ]);
            }

            $originalPayments = $original->payments()
                ->where('status', 'completed')
                ->with('fund')
                ->get();
            $refundAmount = min((float) $return->grand_total, (float) $originalPayments->sum('amount'));
            if ($refundAmount > 0) {
                $remainingRefund = $refundAmount;
                $allocations = collect();
                foreach ($originalPayments as $payment) {
                    if ($remainingRefund <= 0) {
                        break;
                    }
                    $amount = min($remainingRefund, (float) $payment->amount);
                    $fund = $payment->fund ?? $this->resolveFund($original->posSession?->terminal, $method);
                    if ((float) $fund->current_balance < $amount) {
                        throw ValidationException::withMessages(['refund' => 'رصيد وسيلة الدفع غير كافٍ لإتمام الاسترجاع.']);
                    }
                    $allocations->push(compact('payment', 'fund', 'amount'));
                    $remainingRefund -= $amount;
                }

                $refund = app(LedgerService::class)->customerEntry(
                    $original->customer_id,
                    CustomerTransaction::TYPE_ADJUSTMENT,
                    debit: $refundAmount,
                    reference: $return,
                    notes: 'رد قيمة '.$return->number,
                    paymentMethod: 'refund',
                );
                $lines = [['account' => app(AccountingService::class)->systemAccount('1200'), 'debit' => $refundAmount]];
                foreach ($allocations as $allocation) {
                    $lines[] = ['account' => $allocation['fund']->chart_account_id, 'credit' => $allocation['amount']];
                }
                $entry = app(AccountingService::class)->createAndPost(
                    'رد قيمة للعميل - '.$return->number,
                    $lines,
                    $refund,
                    today(),
                    $return->number,
                );
                $refund->update(['journal_entry_id' => $entry->id]);

                foreach ($allocations as $allocation) {
                    $allocation['fund']->decrement('current_balance', $allocation['amount']);
                    $return->payments()->create([
                        'pos_session_id' => $original->pos_session_id,
                        'method' => $allocation['payment']->method,
                        'amount' => $allocation['amount'],
                        'fund_type' => $allocation['fund']->getMorphClass(),
                        'fund_id' => $allocation['fund']->id,
                        'status' => 'refunded',
                        'paid_at' => now(),
                    ]);
                }
            }
            $loyalty = LoyaltyAccount::query()->where('customer_id', $original->customer_id)->lockForUpdate()->first();
            if ($loyalty) {
                $deduct = min((int) $loyalty->points_balance, (int) $original->loyalty_points_earned);
                if ($deduct > 0) {
                    $loyalty->decrement('points_balance', $deduct);
                    $loyalty->transactions()->create([
                        'sales_document_id' => $return->id,
                        'type' => 'return',
                        'points' => -$deduct,
                        'balance_after' => $loyalty->fresh()->points_balance,
                        'description' => 'إلغاء نقاط الفاتورة '.$original->number,
                    ]);
                }
                if ($original->loyalty_points_redeemed > 0) {
                    $loyalty->increment('points_balance', $original->loyalty_points_redeemed);
                    $loyalty->transactions()->create([
                        'sales_document_id' => $return->id,
                        'type' => 'restore',
                        'points' => $original->loyalty_points_redeemed,
                        'balance_after' => $loyalty->fresh()->points_balance,
                        'description' => 'استعادة نقاط الفاتورة '.$original->number,
                    ]);
                }
            }

            return $return->fresh(['items.product', 'payments', 'customer']);
        });
    }

    private function createDocument(array $data, bool $post): SalesDocument
    {
        return DB::transaction(function () use ($data, $post): SalesDocument {
            $session = PosSession::query()->with('terminal')->lockForUpdate()->findOrFail($data['pos_session_id']);
            $this->assertOpenSession($session);
            $customer = ! empty($data['customer_id'])
                ? Customer::query()->findOrFail($data['customer_id'])
                : $this->walkInCustomer();
            if (in_array($data['payment_type'] ?? 'cash', ['credit', 'installment'], true) && empty($data['customer_id'])) {
                throw ValidationException::withMessages(['customer_id' => 'اختر عميلاً مسجلاً للبيع الآجل أو بالتقسيط.']);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages(['items' => 'أضف منتجاً واحداً على الأقل.']);
            }

            $document = SalesDocument::query()->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $session->warehouse_id,
                'pos_session_id' => $session->id,
                'client_uuid' => $data['client_uuid'] ?? (string) Str::uuid(),
                'type' => SalesDocument::TYPE_INVOICE,
                'channel' => 'pos',
                'payment_type' => $data['payment_type'] ?? 'cash',
                'document_date' => today(),
                'notes' => $data['notes'] ?? null,
                'held_at' => $post ? null : now(),
            ]);

            foreach ($data['items'] as $line) {
                $this->createLine($document, $session, $line);
            }

            $document->recalculateTotals();
            $discount = max(0, (float) ($data['invoice_discount'] ?? 0));

            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()->where('code', Str::upper($data['coupon_code']))->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsable((float) $document->grand_total)) {
                    throw ValidationException::withMessages(['coupon' => 'الكوبون غير صالح أو لا يحقق الحد الأدنى.']);
                }
                $discount += $coupon->discountFor((float) $document->subtotal);
                $document->coupon()->associate($coupon);
            }

            $redeemedPoints = (int) ($data['loyalty_points'] ?? 0);
            if ($redeemedPoints > 0) {
                $account = LoyaltyAccount::query()->firstOrCreate(['customer_id' => $customer->id]);
                if ($account->points_balance < $redeemedPoints) {
                    throw ValidationException::withMessages(['loyalty_points' => 'رصيد نقاط الولاء غير كافٍ.']);
                }
                $discount += $redeemedPoints / 100;
            }

            $document->update([
                'invoice_discount' => min($discount, (float) $document->subtotal),
                'loyalty_points_redeemed' => $redeemedPoints,
            ]);
            $document->recalculateTotals();

            if (! $post) {
                return $document->fresh(['items.product', 'customer']);
            }

            $paymentType = $data['payment_type'] ?? 'cash';
            $payments = $paymentType === 'credit'
                ? collect()
                : collect($data['payments'] ?? [])->filter(fn (array $payment): bool => (float) ($payment['amount'] ?? 0) > 0);
            $paid = (float) $payments->sum('amount');

            if (in_array($paymentType, ['cash', 'mixed'], true) && abs($paid - (float) $document->grand_total) > 0.01) {
                throw ValidationException::withMessages(['payments' => 'إجمالي المدفوعات يجب أن يساوي إجمالي الفاتورة.']);
            }
            if ($paid > (float) $document->grand_total + 0.01) {
                throw ValidationException::withMessages(['payments' => 'إجمالي المدفوعات أكبر من قيمة الفاتورة.']);
            }

            app(SalesService::class)->post($document->fresh(['items', 'customer', 'sourceDocument']));

            foreach ($payments as $paymentData) {
                $this->recordPayment($document, $session, $paymentData);
            }

            if ($paymentType === 'installment') {
                $this->createInstallmentPlan($document, $data['installment'] ?? [], $paid);
            }

            $this->applyLoyalty($document, $customer, $redeemedPoints);
            if ($document->coupon_id) {
                $document->coupon()->increment('usage_count');
            }

            return $document->fresh(['items.product', 'items.variant', 'payments', 'customer', 'installmentPlan.installments']);
        });
    }

    private function createLine(SalesDocument $document, PosSession $session, array $line): void
    {
        $product = Product::query()->findOrFail($line['product_id']);
        $variant = ! empty($line['product_variant_id'])
            ? ProductVariant::query()->where('product_id', $product->id)->findOrFail($line['product_variant_id'])
            : null;
        $quantity = (float) ($line['quantity'] ?? 1);
        $basePrice = (float) ($variant?->selling_price ?: $product->selling_price);
        $price = isset($line['unit_price']) ? (float) $line['unit_price'] : $basePrice;
        $overridden = abs($price - $basePrice) > 0.0001;

        if ($overridden && ! $this->canOverridePrice()) {
            throw ValidationException::withMessages(['unit_price' => 'ليس لديك صلاحية تعديل سعر البيع.']);
        }

        $promotion = Promotion::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('product_id', $product->id)->orWhere('category_id', $product->category_id))
            ->get()
            ->first(fn (Promotion $promotion): bool => $promotion->isActiveFor($product, $quantity));
        $discount = max(0, (float) ($line['discount_amount'] ?? 0));
        if ($promotion) {
            $promotionDiscount = $promotion->discount_type === 'percentage'
                ? $quantity * $price * ((float) $promotion->value / 100)
                : (float) $promotion->value;
            $discount = max($discount, $promotionDiscount);
        }

        $serial = null;
        if (! empty($line['serial_number'])) {
            $serial = ProductSerial::query()
                ->where('serial_number', $line['serial_number'])
                ->where('product_id', $product->id)
                ->where('warehouse_id', $session->warehouse_id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->firstOrFail();
            if ($quantity !== 1.0) {
                throw ValidationException::withMessages(['serial_number' => 'كل رقم سيريال يمثل وحدة واحدة فقط.']);
            }
        }

        $item = $document->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'serial_number' => $serial?->serial_number,
            'quantity' => $quantity,
            'unit_price' => $price,
            'price_overridden' => $overridden,
            'promotion_id' => $promotion?->id,
            'discount_amount' => min($discount, $quantity * $price),
            'tax_rate' => (float) ($line['tax_rate'] ?? 15),
        ]);

        $serial?->update([
            'status' => $document->held_at ? 'reserved' : 'sold',
            'sales_document_item_id' => $item->id,
        ]);
    }

    private function recordPayment(SalesDocument $document, PosSession $session, array $data): PosPayment
    {
        $method = $data['method'] ?? PosPayment::METHOD_CASH;
        $fund = $this->resolveFund($session->terminal, $method, $data['fund_type'] ?? null, $data['fund_id'] ?? null);
        $payment = $document->payments()->create([
            'pos_session_id' => $session->id,
            'method' => $method,
            'amount' => $data['amount'],
            'fund_type' => $fund->getMorphClass(),
            'fund_id' => $fund->id,
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
        ]);
        app(LedgerService::class)->customerEntry(
            $document->customer_id,
            CustomerTransaction::TYPE_PAYMENT,
            credit: (float) $payment->amount,
            reference: $payment,
            notes: $document->number,
            paymentMethod: $method,
            fundType: $fund instanceof Treasury ? 'treasury' : 'bank',
            fundId: $fund->id,
        );

        return $payment;
    }

    private function createInstallmentPlan(SalesDocument $document, array $data, float $paid): InstallmentPlan
    {
        $count = max(1, (int) ($data['count'] ?? 1));
        $remaining = max(0, (float) $document->grand_total - $paid);
        $plan = $document->installmentPlan()->create([
            'customer_id' => $document->customer_id,
            'total_amount' => $document->grand_total,
            'down_payment' => $paid,
            'installment_amount' => round($remaining / $count, 4),
            'installments_count' => $count,
            'frequency' => $data['frequency'] ?? 'monthly',
            'first_due_date' => $data['first_due_date'] ?? today()->addMonth(),
        ]);
        $date = Carbon::parse($plan->first_due_date);
        $allocated = 0.0;
        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $amount = $sequence === $count ? $remaining - $allocated : round($remaining / $count, 4);
            $plan->installments()->create([
                'sequence' => $sequence,
                'due_date' => $date,
                'amount' => $amount,
            ]);
            $allocated += $amount;
            $date = $plan->frequency === 'weekly' ? $date->copy()->addWeek() : $date->copy()->addMonth();
        }

        return $plan;
    }

    private function applyLoyalty(SalesDocument $document, Customer $customer, int $redeemed): void
    {
        $account = LoyaltyAccount::query()->lockForUpdate()->firstOrCreate(['customer_id' => $customer->id]);
        if ($redeemed > 0) {
            $account->decrement('points_balance', $redeemed);
            $account->transactions()->create([
                'sales_document_id' => $document->id,
                'type' => 'redeem',
                'points' => -$redeemed,
                'balance_after' => $account->fresh()->points_balance,
                'description' => 'استبدال نقاط في '.$document->number,
            ]);
        }
        $earned = (int) floor((float) $document->grand_total / 10);
        if ($earned > 0) {
            $account->increment('points_balance', $earned);
            $account->increment('lifetime_points', $earned);
            $account->transactions()->create([
                'sales_document_id' => $document->id,
                'type' => 'earn',
                'points' => $earned,
                'balance_after' => $account->fresh()->points_balance,
                'description' => 'نقاط مكتسبة من '.$document->number,
            ]);
        }
        $document->update(['loyalty_points_earned' => $earned]);
    }

    private function resolveFund(
        ?PosTerminal $terminal,
        string $method,
        ?string $fundType = null,
        ?int $fundId = null,
    ): Treasury|BankAccount {
        if ($fundId && $fundType) {
            return app(AccountingService::class)->fund($fundType, $fundId, true);
        }
        if ($method === PosPayment::METHOD_CASH) {
            return $terminal?->treasury ?? app(AccountingService::class)->defaultFund();
        }

        return BankAccount::query()->where('is_active', true)->first()
            ?? throw ValidationException::withMessages(['payments' => 'أضف حساباً بنكياً لمدفوعات البطاقة أو المحفظة.']);
    }

    private function walkInCustomer(): Customer
    {
        return Customer::query()->firstOrCreate(
            ['code' => 'WALK-IN'],
            ['name' => 'عميل نقدي', 'credit_limit' => 0, 'is_active' => true],
        );
    }

    private function canOverridePrice(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('pos_price_override', 'update');
    }

    private function assertOpenSession(PosSession $session): void
    {
        if ($session->status !== PosSession::STATUS_OPEN) {
            throw ValidationException::withMessages(['session' => 'وردية الكاشير مغلقة.']);
        }
        if ($session->user_id !== auth()->id() && ! auth()->user()?->hasRole('manager')) {
            throw ValidationException::withMessages(['session' => 'هذه الوردية تخص مستخدماً آخر.']);
        }
    }
}
