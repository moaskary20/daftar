<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales_documents', 'purchase_documents', 'bank_accounts'] as $table) {
            DB::table($table)->where('currency', 'SAR')->update(['currency' => 'EGP']);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('currency', 3)->default('EGP')->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['sales_documents', 'purchase_documents', 'bank_accounts'] as $table) {
            DB::table($table)->where('currency', 'EGP')->update(['currency' => 'SAR']);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('currency', 3)->default('SAR')->change();
            });
        }
    }
};
