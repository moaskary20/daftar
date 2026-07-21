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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->string('color')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('size')->nullable();
            $table->decimal('weight', 12, 3)->nullable();
            $table->decimal('purchase_price', 14, 4)->nullable();
            $table->decimal('average_cost', 14, 4)->nullable();
            $table->decimal('selling_price', 14, 4)->nullable();
            $table->decimal('stock_quantity', 14, 3)->default(0);
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
