<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\PosPayment;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_sale_posts_inventory_ledger_payment_loyalty_and_can_be_returned(): void
    {
        [$terminal, $product, $user] = $this->fixtures();
        $this->actingAs($user);
        $session = app(PosService::class)->openSession($terminal, 50);

        $invoice = app(PosService::class)->checkout([
            'pos_session_id' => $session->id,
            'payment_type' => 'cash',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'discount_amount' => 0,
                'tax_rate' => 15,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 115]],
        ]);

        $this->assertSame('posted', $invoice->status);
        $this->assertSame(115.0, (float) $invoice->grand_total);
        $this->assertSame(1, $invoice->payments->count());
        $this->assertSame(11, $invoice->loyalty_points_earned);
        $this->assertSame(9.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(0.0, (float) $invoice->customer->fresh()->current_balance);
        $this->assertSame(115.0, (float) $terminal->treasury->fresh()->current_balance);

        $return = app(PosService::class)->returnInvoice($invoice);

        $this->assertSame('return', $return->type);
        $this->assertSame(10.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(0.0, (float) $invoice->customer->fresh()->current_balance);
        $this->assertSame(0.0, (float) $terminal->treasury->fresh()->current_balance);
    }

    public function test_mixed_payment_coupon_and_serial_sale_are_supported(): void
    {
        [$terminal, $product, $user] = $this->fixtures();
        $this->actingAs($user);
        $session = app(PosService::class)->openSession($terminal);
        $bank = BankAccount::query()->create([
            'chart_account_id' => app(AccountingService::class)->systemAccount('1101')->id,
            'name' => 'فيزا',
            'bank_name' => 'البنك',
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'SALE10',
            'name' => 'خصم 10%',
            'discount_type' => 'percentage',
            'value' => 10,
        ]);
        $serial = ProductSerial::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $terminal->warehouse_id,
            'serial_number' => 'SN-100',
        ]);

        $invoice = app(PosService::class)->checkout([
            'pos_session_id' => $session->id,
            'payment_type' => 'mixed',
            'coupon_code' => 'SALE10',
            'items' => [[
                'product_id' => $product->id,
                'serial_number' => $serial->serial_number,
                'quantity' => 1,
                'unit_price' => 100,
                'tax_rate' => 15,
            ]],
            'payments' => [
                ['method' => PosPayment::METHOD_CASH, 'amount' => 55],
                ['method' => PosPayment::METHOD_CARD, 'amount' => 50, 'fund_type' => 'bank', 'fund_id' => $bank->id],
            ],
        ]);

        $this->assertSame(105.0, (float) $invoice->grand_total);
        $this->assertSame(2, $invoice->payments->count());
        $this->assertSame(1, $coupon->fresh()->usage_count);
        $this->assertSame('sold', $serial->fresh()->status);
        $this->assertSame(55.0, (float) $terminal->treasury->fresh()->current_balance);
        $this->assertSame(50.0, (float) $bank->fresh()->current_balance);

        $return = app(PosService::class)->returnInvoice($invoice);
        $this->assertSame(105.0, (float) $return->grand_total);
        $this->assertSame('available', $serial->fresh()->status);
        $this->assertSame(0.0, (float) $terminal->treasury->fresh()->current_balance);
        $this->assertSame(0.0, (float) $bank->fresh()->current_balance);
    }

    public function test_installment_sale_creates_schedule_and_outstanding_customer_balance(): void
    {
        [$terminal, $product, $user] = $this->fixtures();
        $customer = Customer::query()->create(['name' => 'عميل تقسيط', 'credit_limit' => 1000]);
        $this->actingAs($user);
        $session = app(PosService::class)->openSession($terminal);

        $invoice = app(PosService::class)->checkout([
            'pos_session_id' => $session->id,
            'customer_id' => $customer->id,
            'payment_type' => 'installment',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'tax_rate' => 0,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 20]],
            'installment' => ['count' => 4, 'frequency' => 'monthly', 'first_due_date' => today()->addMonth()],
        ]);

        $this->assertSame(80.0, (float) $customer->fresh()->current_balance);
        $this->assertSame(20.0, (float) $invoice->installmentPlan->installment_amount);
        $this->assertCount(4, $invoice->installmentPlan->installments);
    }

    public function test_cashier_cannot_override_price_without_permission(): void
    {
        [$terminal, $product] = $this->fixtures();
        $cashier = User::factory()->create();
        $role = Role::query()->create(['name' => 'كاشير', 'slug' => 'cashier']);
        $cashier->roles()->attach($role);
        $this->actingAs($cashier);
        $session = app(PosService::class)->openSession($terminal);

        $this->expectException(ValidationException::class);
        app(PosService::class)->checkout([
            'pos_session_id' => $session->id,
            'payment_type' => 'cash',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50,
                'tax_rate' => 0,
            ]],
            'payments' => [['method' => 'cash', 'amount' => 50]],
        ]);
    }

    private function fixtures(): array
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'المدير', 'slug' => 'manager']);
        $user->roles()->attach($role);
        $warehouse = Warehouse::query()->create(['name' => 'الرئيسي', 'code' => 'MAIN']);
        $treasury = Treasury::query()->create([
            'chart_account_id' => app(AccountingService::class)->systemAccount('1101')->id,
            'name' => 'الخزينة',
            'code' => 'CASH',
            'is_default' => true,
        ]);
        $terminal = PosTerminal::query()->create([
            'warehouse_id' => $warehouse->id,
            'treasury_id' => $treasury->id,
            'name' => 'كاشير',
            'code' => 'POS-1',
        ]);
        $product = Product::query()->create([
            'name' => 'منتج POS',
            'slug' => 'pos-product',
            'selling_price' => 100,
            'average_cost' => 60,
        ]);
        app(InventoryService::class)->adjust($warehouse, $product, 10, StockMovement::TYPE_RECEIPT);

        return [$terminal, $product, $user];
    }
}
