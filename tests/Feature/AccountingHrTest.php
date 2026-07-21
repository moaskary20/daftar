<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\Treasury;
use App\Services\AccountingService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingHrTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_journal_entry_must_be_balanced_before_posting(): void
    {
        $accounting = app(AccountingService::class);
        $cash = $accounting->systemAccount('1101');
        $capital = $accounting->systemAccount('3100');
        $entry = JournalEntry::query()->create([
            'entry_date' => today(),
            'description' => 'إثبات رأس المال',
        ]);
        $entry->lines()->createMany([
            ['chart_account_id' => $cash->id, 'debit' => 1000],
            ['chart_account_id' => $capital->id, 'credit' => 1000],
        ]);

        $accounting->postEntry($entry);

        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->fresh()->status);
        $this->assertSame(1000.0, (float) $entry->fresh()->total_debit);

        $unbalanced = JournalEntry::query()->create([
            'entry_date' => today(),
            'description' => 'قيد غير متوازن',
        ]);
        $unbalanced->lines()->createMany([
            ['chart_account_id' => $cash->id, 'debit' => 100],
            ['chart_account_id' => $capital->id, 'credit' => 90],
        ]);

        $this->expectException(ValidationException::class);
        $accounting->postEntry($unbalanced);
    }

    public function test_transfer_between_treasury_and_bank_updates_balances_and_creates_entry(): void
    {
        $accounting = app(AccountingService::class);
        $cash = $accounting->systemAccount('1101');
        $bankLedger = ChartAccount::query()->create([
            'code' => '1102',
            'name' => 'البنك',
            'type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
        ]);
        $treasury = Treasury::query()->create([
            'chart_account_id' => $cash->id,
            'name' => 'الخزينة',
            'code' => 'CASH',
            'opening_balance' => 1000,
        ]);
        $bank = BankAccount::query()->create([
            'chart_account_id' => $bankLedger->id,
            'name' => 'الحساب الجاري',
            'bank_name' => 'البنك',
            'opening_balance' => 0,
        ]);
        $transfer = FinancialTransaction::query()->create([
            'type' => FinancialTransaction::TYPE_TRANSFER,
            'source_fund_type' => 'treasury',
            'source_fund_id' => $treasury->id,
            'destination_fund_type' => 'bank',
            'destination_fund_id' => $bank->id,
            'amount' => 300,
            'transaction_date' => today(),
        ]);

        $entry = $accounting->postFinancialTransaction($transfer);

        $this->assertSame(700.0, (float) $treasury->fresh()->current_balance);
        $this->assertSame(300.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(300.0, (float) $entry->total_debit);
        $this->assertSame('posted', $transfer->fresh()->status);
    }

    public function test_expense_posts_to_its_account_and_reduces_treasury(): void
    {
        $accounting = app(AccountingService::class);
        $treasury = Treasury::query()->create([
            'chart_account_id' => $accounting->systemAccount('1101')->id,
            'name' => 'الخزينة',
            'code' => 'MAIN',
            'opening_balance' => 500,
        ]);
        $category = ExpenseCategory::query()->create([
            'chart_account_id' => $accounting->systemAccount('5300')->id,
            'name' => 'إيجار',
            'code' => 'RENT',
        ]);
        $expense = Expense::query()->create([
            'expense_category_id' => $category->id,
            'expense_type' => 'rent',
            'amount' => 100,
            'expense_date' => today(),
            'payment_fund_type' => 'treasury',
            'payment_fund_id' => $treasury->id,
            'description' => 'إيجار المكتب',
        ]);

        $accounting->postExpense($expense->fresh('category'));

        $this->assertSame(400.0, (float) $treasury->fresh()->current_balance);
        $this->assertSame('posted', $expense->fresh()->status);
        $this->assertNotNull($expense->fresh()->journal_entry_id);
    }

    public function test_payroll_uses_attendance_incentives_penalties_and_advance_installments(): void
    {
        $accounting = app(AccountingService::class);
        $treasury = Treasury::query()->create([
            'chart_account_id' => $accounting->systemAccount('1101')->id,
            'name' => 'خزينة الرواتب',
            'code' => 'PAY',
            'opening_balance' => 5000,
        ]);
        $employee = Employee::query()->create([
            'employee_number' => 'EMP-1',
            'name' => 'موظف أول',
            'hire_date' => today()->subYear(),
            'basic_salary' => 3000,
            'fixed_allowances' => 300,
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => today()->startOfMonth(),
            'status' => 'absent',
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => today()->startOfMonth()->addDay(),
            'status' => 'present',
            'overtime_hours' => 8,
        ]);
        EmployeeAdjustment::query()->create([
            'employee_id' => $employee->id,
            'type' => EmployeeAdjustment::TYPE_INCENTIVE,
            'amount' => 200,
            'adjustment_date' => today(),
            'reason' => 'تميز',
        ]);
        EmployeeAdjustment::query()->create([
            'employee_id' => $employee->id,
            'type' => EmployeeAdjustment::TYPE_PENALTY,
            'amount' => 50,
            'adjustment_date' => today(),
            'reason' => 'تأخير',
        ]);
        $advance = EmployeeAdjustment::query()->create([
            'employee_id' => $employee->id,
            'type' => EmployeeAdjustment::TYPE_ADVANCE,
            'amount' => 300,
            'installments' => 3,
            'adjustment_date' => today(),
            'reason' => 'سلفة',
        ]);
        $payroll = Payroll::query()->create([
            'period_month' => today()->format('Y-m'),
            'payment_fund_type' => 'treasury',
            'payment_fund_id' => $treasury->id,
        ]);

        $service = app(PayrollService::class);
        $service->generate($payroll);
        $item = $payroll->items()->first();

        $this->assertSame(3400.0, (float) $item->net_salary);

        $service->post($payroll->fresh('items.employee'));

        $this->assertSame('posted', $payroll->fresh()->status);
        $this->assertSame(1600.0, (float) $treasury->fresh()->current_balance);
        $this->assertSame(100.0, (float) $advance->fresh()->settled_amount);
        $this->assertSame('partial', $advance->fresh()->status);
    }
}
