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
        Schema::table('purchase_document_items', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('description');
            $table->date('production_date')->nullable()->after('batch_number');
            $table->date('expiry_date')->nullable()->after('production_date');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_document_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'product_variant_id']);
            $table->index(['expiry_date', 'quantity']);
        });

        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('unique_key')->unique();
            $table->string('type');
            $table->string('severity')->default('warning');
            $table->string('title');
            $table->text('message');
            $table->nullableMorphs('reference');
            $table->date('due_date')->nullable();
            $table->string('action_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'read_at', 'due_date']);
        });

        Schema::create('backup_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status')->default('pending');
            $table->string('checksum')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_records');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('inventory_batches');

        Schema::table('purchase_document_items', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'production_date', 'expiry_date']);
        });
    }
};
