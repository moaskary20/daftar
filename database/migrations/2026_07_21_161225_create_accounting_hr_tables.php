<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Uses hasTable/hasColumn guards because MySQL cannot roll back DDL
     * when a multi-statement migration fails mid-way.
     */
    public function up(): void
    {
        if (! Schema::hasTable('chart_accounts')) {
            Schema::create('chart_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('type');
                $table->string('normal_balance');
                $table->boolean('is_group')->default(false);
                $table->boolean('allow_posting')->default(true);
                $table->boolean('is_active')->default(true);
                $table->decimal('opening_debit', 16, 4)->default(0);
                $table->decimal('opening_credit', 16, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number')->unique();
                $table->date('entry_date');
                $table->string('entry_type')->default('manual');
                $table->string('status')->default('draft');
                $table->nullableMorphs('source');
                $table->string('reference')->nullable();
                $table->text('description');
                $table->decimal('total_debit', 16, 4)->default(0);
                $table->decimal('total_credit', 16, 4)->default(0);
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['entry_date', 'status']);
            });
        }

        if (! Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
                $table->foreignId('chart_account_id')->constrained()->restrictOnDelete();
                $table->text('description')->nullable();
                $table->decimal('debit', 16, 4)->default(0);
                $table->decimal('credit', 16, 4)->default(0);
                $table->string('cost_center')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('treasuries')) {
            Schema::create('treasuries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chart_account_id')->constrained()->restrictOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->decimal('opening_balance', 16, 4)->default(0);
                $table->decimal('current_balance', 16, 4)->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chart_account_id')->constrained()->restrictOnDelete();
                $table->string('name');
                $table->string('bank_name');
                $table->string('account_number')->nullable()->unique();
                $table->string('iban')->nullable()->unique();
                $table->string('currency', 3)->default('EGP');
                $table->decimal('opening_balance', 16, 4)->default(0);
                $table->decimal('current_balance', 16, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('customer_transactions') && ! Schema::hasColumn('customer_transactions', 'journal_entry_id')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                $table->foreignId('journal_entry_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
                $table->string('fund_type')->nullable()->after('payment_method');
                $table->unsignedBigInteger('fund_id')->nullable()->after('fund_type');
            });
        }

        if (Schema::hasTable('supplier_transactions') && ! Schema::hasColumn('supplier_transactions', 'journal_entry_id')) {
            Schema::table('supplier_transactions', function (Blueprint $table) {
                $table->foreignId('journal_entry_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
                $table->string('fund_type')->nullable()->after('payment_method');
                $table->unsignedBigInteger('fund_id')->nullable()->after('fund_type');
            });
        }

        if (! Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number')->unique();
                $table->string('type');
                $table->string('source_fund_type')->nullable();
                $table->unsignedBigInteger('source_fund_id')->nullable();
                $table->string('destination_fund_type')->nullable();
                $table->unsignedBigInteger('destination_fund_id')->nullable();
                $table->decimal('amount', 16, 4);
                $table->date('transaction_date');
                $table->string('status')->default('draft');
                $table->string('beneficiary')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['source_fund_type', 'source_fund_id'], 'ft_source_fund_index');
                $table->index(['destination_fund_type', 'destination_fund_id'], 'ft_destination_fund_index');
            });
        }

        if (! Schema::hasTable('bank_checks')) {
            Schema::create('bank_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->nullableMorphs('party');
                $table->string('number')->unique();
                $table->string('check_number');
                $table->string('direction');
                $table->string('bank_name')->nullable();
                $table->decimal('amount', 16, 4);
                $table->date('issue_date');
                $table->date('due_date');
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['direction', 'status', 'due_date']);
            });
        }

        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('bank_check_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->nullableMorphs('party');
                $table->string('number')->unique();
                $table->string('type');
                $table->string('fund_type');
                $table->unsignedBigInteger('fund_id');
                $table->decimal('amount', 16, 4);
                $table->date('voucher_date');
                $table->string('payment_method')->default('cash');
                $table->string('status')->default('draft');
                $table->text('description');
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['fund_type', 'fund_id']);
            });
        }

        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chart_account_id')->constrained()->restrictOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('employee_number')->unique();
                $table->string('name');
                $table->string('job_title')->nullable();
                $table->string('department_name')->nullable();
                $table->string('national_id')->nullable()->unique();
                $table->string('fingerprint_id')->nullable()->unique();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->date('hire_date');
                $table->decimal('basic_salary', 14, 4)->default(0);
                $table->decimal('fixed_allowances', 14, 4)->default(0);
                $table->string('bank_name')->nullable();
                $table->string('iban')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->date('attendance_date');
                $table->dateTime('check_in')->nullable();
                $table->dateTime('check_out')->nullable();
                $table->string('status')->default('present');
                $table->decimal('late_minutes', 8, 2)->default(0);
                $table->decimal('overtime_hours', 8, 2)->default(0);
                $table->string('source')->default('manual');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'attendance_date']);
            });
        }

        if (! Schema::hasTable('employee_adjustments')) {
            Schema::create('employee_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->decimal('amount', 14, 4);
                $table->date('adjustment_date');
                $table->unsignedInteger('installments')->default(1);
                $table->decimal('settled_amount', 14, 4)->default(0);
                $table->string('status')->default('pending');
                $table->text('reason');
                $table->timestamps();

                $table->index(['employee_id', 'type', 'status']);
            });
        }

        if (! Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number')->unique();
                $table->string('period_month')->unique();
                $table->string('status')->default('draft');
                $table->decimal('total_earnings', 16, 4)->default(0);
                $table->decimal('total_deductions', 16, 4)->default(0);
                $table->decimal('net_total', 16, 4)->default(0);
                $table->date('payment_date')->nullable();
                $table->string('payment_fund_type')->nullable();
                $table->unsignedBigInteger('payment_fund_id')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->restrictOnDelete();
                $table->decimal('basic_salary', 14, 4)->default(0);
                $table->decimal('allowances', 14, 4)->default(0);
                $table->decimal('incentives', 14, 4)->default(0);
                $table->decimal('overtime_amount', 14, 4)->default(0);
                $table->decimal('advances', 14, 4)->default(0);
                $table->decimal('penalties', 14, 4)->default(0);
                $table->decimal('absence_deductions', 14, 4)->default(0);
                $table->decimal('other_deductions', 14, 4)->default(0);
                $table->decimal('net_salary', 14, 4)->default(0);
                $table->timestamps();

                $table->unique(['payroll_id', 'employee_id']);
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number')->unique();
                $table->string('expense_type')->default('general');
                $table->decimal('amount', 16, 4);
                $table->date('expense_date');
                $table->string('payment_fund_type');
                $table->unsignedBigInteger('payment_fund_id');
                $table->string('status')->default('draft');
                $table->text('description');
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['expense_type', 'expense_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employee_adjustments');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('bank_checks');
        Schema::dropIfExists('financial_transactions');

        if (Schema::hasTable('supplier_transactions') && Schema::hasColumn('supplier_transactions', 'journal_entry_id')) {
            Schema::table('supplier_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('journal_entry_id');
                $table->dropColumn(['fund_type', 'fund_id']);
            });
        }

        if (Schema::hasTable('customer_transactions') && Schema::hasColumn('customer_transactions', 'journal_entry_id')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('journal_entry_id');
                $table->dropColumn(['fund_type', 'fund_id']);
            });
        }

        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('treasuries');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_accounts');
    }
};
