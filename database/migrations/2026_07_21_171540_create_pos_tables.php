<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treasury_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('receipt_size')->default('80mm');
            $table->string('printer_name')->nullable();
            $table->string('kitchen_printer_name')->nullable();
            $table->boolean('cash_drawer_enabled')->default(false);
            $table->boolean('customer_display_enabled')->default(false);
            $table->boolean('scale_enabled')->default(false);
            $table->boolean('offline_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_terminal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('open');
            $table->decimal('opening_balance', 14, 4)->default(0);
            $table->decimal('expected_balance', 14, 4)->default(0);
            $table->decimal('closing_balance', 14, 4)->nullable();
            $table->decimal('difference', 14, 4)->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('discount_type')->default('fixed');
            $table->decimal('value', 14, 4);
            $table->decimal('minimum_total', 14, 4)->default(0);
            $table->decimal('maximum_discount', 14, 4)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('discount_type')->default('percentage');
            $table->decimal('value', 14, 4);
            $table->decimal('minimum_quantity', 14, 3)->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('send_to_kitchen')->default(false)->after('track_stock');
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->foreignId('pos_session_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->after('pos_session_id')->constrained()->nullOnDelete();
            $table->string('channel')->default('backoffice')->after('type');
            $table->string('payment_type')->nullable()->after('channel');
            $table->uuid('client_uuid')->nullable()->unique()->after('number');
            $table->decimal('invoice_discount', 14, 4)->default(0)->after('discount_total');
            $table->unsignedInteger('loyalty_points_earned')->default(0)->after('invoice_discount');
            $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('loyalty_points_earned');
            $table->unsignedInteger('print_count')->default(0)->after('loyalty_points_redeemed');
            $table->timestamp('held_at')->nullable()->after('posted_at');
            $table->timestamp('offline_synced_at')->nullable()->after('held_at');
        });

        Schema::table('sales_document_items', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('description');
            $table->boolean('price_overridden')->default(false)->after('unit_price');
            $table->foreignId('promotion_id')->nullable()->after('price_overridden')->constrained()->nullOnDelete();
        });

        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method');
            $table->decimal('amount', 14, 4);
            $table->nullableMorphs('fund');
            $table->string('reference')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('paid_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('points_balance')->default(0);
            $table->unsignedBigInteger('lifetime_points')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->bigInteger('points');
            $table->bigInteger('balance_after');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_document_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 14, 4);
            $table->decimal('down_payment', 14, 4)->default(0);
            $table->decimal('installment_amount', 14, 4);
            $table->unsignedInteger('installments_count');
            $table->string('frequency')->default('monthly');
            $table->date('first_due_date');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->date('due_date');
            $table->decimal('amount', 14, 4);
            $table->decimal('paid_amount', 14, 4)->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['installment_plan_id', 'sequence']);
        });

        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_document_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('status')->default('available');
            $table->date('warranty_expires_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('pos_payments');

        Schema::table('sales_document_items', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['serial_number', 'price_overridden', 'promotion_id']);
        });
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropForeign(['pos_session_id']);
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'pos_session_id', 'coupon_id', 'channel', 'payment_type', 'client_uuid',
                'invoice_discount', 'loyalty_points_earned', 'loyalty_points_redeemed',
                'print_count', 'held_at', 'offline_synced_at',
            ]);
        });
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('send_to_kitchen'));
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('pos_terminals');
    }
};
