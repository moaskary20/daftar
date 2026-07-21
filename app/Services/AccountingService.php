<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Expense;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\PurchaseDocument;
use App\Models\SalesDocument;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\Treasury;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function postEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status === JournalEntry::STATUS_POSTED) {
            return $entry;
        }

        return DB::transaction(function () use ($entry): JournalEntry {
            $entry->load('lines.account');
            $debit = round((float) $entry->lines->sum('debit'), 4);
            $credit = round((float) $entry->lines->sum('credit'), 4);

            if ($entry->lines->count() < 2 || $debit <= 0 || abs($debit - $credit) > 0.0001) {
                throw ValidationException::withMessages([
                    'lines' => 'القيد غير متوازن، ويجب أن يتساوى إجمالي المدين والدائن.',
                ]);
            }

            foreach ($entry->lines as $line) {
                if (! $line->account->allow_posting || $line->account->is_group) {
                    throw ValidationException::withMessages([
                        'lines' => "لا يسمح بالترحيل على الحساب {$line->account->name}.",
                    ]);
                }

                if ((float) $line->debit > 0 && (float) $line->credit > 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'لا يمكن أن يكون سطر القيد مديناً ودائناً في الوقت نفسه.',
                    ]);
                }
            }

            $entry->update([
                'total_debit' => $debit,
                'total_credit' => $credit,
                'status' => JournalEntry::STATUS_POSTED,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $entry->fresh('lines.account');
        });
    }

    public function createAndPost(
        string $description,
        array $lines,
        ?Model $source = null,
        mixed $date = null,
        ?string $reference = null,
    ): JournalEntry {
        if ($source) {
            $existing = JournalEntry::query()
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->where('status', JournalEntry::STATUS_POSTED)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($description, $lines, $source, $date, $reference): JournalEntry {
            $entry = JournalEntry::query()->create([
                'entry_date' => $date ?? today(),
                'entry_type' => $source ? 'automatic' : 'manual',
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'reference' => $reference,
                'description' => $description,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'chart_account_id' => $line['account'] instanceof ChartAccount
                        ? $line['account']->id
                        : $line['account'],
                    'description' => $line['description'] ?? $description,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'cost_center' => $line['cost_center'] ?? null,
                ]);
            }

            return $this->postEntry($entry);
        });
    }

    public function postSales(SalesDocument $document): JournalEntry
    {
        $isReturn = $document->type === SalesDocument::TYPE_RETURN;
        $receivable = $this->systemAccount('1200');
        $revenue = $this->systemAccount('4100');
        $tax = $this->systemAccount('2200');
        $netRevenue = (float) $document->subtotal
            + (float) $document->shipping_cost
            - (float) $document->invoice_discount;

        $lines = $isReturn
            ? [
                ['account' => $revenue, 'debit' => $netRevenue],
                ['account' => $tax, 'debit' => (float) $document->tax_total],
                ['account' => $receivable, 'credit' => (float) $document->grand_total],
            ]
            : [
                ['account' => $receivable, 'debit' => (float) $document->grand_total],
                ['account' => $revenue, 'credit' => $netRevenue],
                ['account' => $tax, 'credit' => (float) $document->tax_total],
            ];

        return $this->createAndPost(
            ($isReturn ? 'مرتجع مبيعات ' : 'فاتورة مبيعات ').$document->number,
            array_values(array_filter($lines, fn (array $line): bool => (float) ($line['debit'] ?? $line['credit'] ?? 0) > 0)),
            $document,
            $document->document_date,
            $document->number,
        );
    }

    public function postPurchase(PurchaseDocument $document): JournalEntry
    {
        $isReturn = $document->type === PurchaseDocument::TYPE_RETURN;
        $inventory = $this->systemAccount('1300');
        $payable = $this->systemAccount('2100');
        $inputTax = $this->systemAccount('1150');
        $net = (float) $document->grand_total - (float) $document->tax_total;

        $lines = $isReturn
            ? [
                ['account' => $payable, 'debit' => (float) $document->grand_total],
                ['account' => $inventory, 'credit' => $net],
                ['account' => $inputTax, 'credit' => (float) $document->tax_total],
            ]
            : [
                ['account' => $inventory, 'debit' => $net],
                ['account' => $inputTax, 'debit' => (float) $document->tax_total],
                ['account' => $payable, 'credit' => (float) $document->grand_total],
            ];

        return $this->createAndPost(
            ($isReturn ? 'مرتجع شراء ' : 'فاتورة شراء ').$document->number,
            array_values(array_filter($lines, fn (array $line): bool => (float) ($line['debit'] ?? $line['credit'] ?? 0) > 0)),
            $document,
            $document->document_date,
            $document->number,
        );
    }

    public function postFinancialTransaction(FinancialTransaction $transaction): JournalEntry
    {
        if ($transaction->status === 'posted') {
            return $transaction->journalEntry;
        }

        return DB::transaction(function () use ($transaction): JournalEntry {
            $amount = (float) $transaction->amount;

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'يجب أن يكون المبلغ أكبر من صفر.']);
            }

            $source = $transaction->source_fund_id
                ? $this->fund($transaction->source_fund_type, $transaction->source_fund_id, true)
                : null;
            $destination = $transaction->destination_fund_id
                ? $this->fund($transaction->destination_fund_type, $transaction->destination_fund_id, true)
                : null;

            if ($transaction->type === FinancialTransaction::TYPE_TRANSFER && (! $source || ! $destination)) {
                throw ValidationException::withMessages(['destination_fund_id' => 'حدد حساب المصدر والوجهة للتحويل.']);
            }

            if ($source && (float) $source->current_balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'رصيد حساب المصدر غير كافٍ.']);
            }

            $lines = match ($transaction->type) {
                FinancialTransaction::TYPE_DEPOSIT => [
                    ['account' => $destination?->chart_account_id ?? $this->defaultFund()->chart_account_id, 'debit' => $amount],
                    ['account' => $this->systemAccount('3100'), 'credit' => $amount],
                ],
                FinancialTransaction::TYPE_WITHDRAWAL => [
                    ['account' => $this->systemAccount('5100'), 'debit' => $amount],
                    ['account' => $source?->chart_account_id ?? $this->defaultFund()->chart_account_id, 'credit' => $amount],
                ],
                FinancialTransaction::TYPE_TRANSFER => [
                    ['account' => $destination->chart_account_id, 'debit' => $amount],
                    ['account' => $source->chart_account_id, 'credit' => $amount],
                ],
                default => throw ValidationException::withMessages(['type' => 'نوع الحركة المالية غير صالح.']),
            };

            $entry = $this->createAndPost(
                FinancialTransaction::typeLabels()[$transaction->type].' '.$transaction->number,
                $lines,
                $transaction,
                $transaction->transaction_date,
                $transaction->number,
            );

            if ($source) {
                $source->decrement('current_balance', $amount);
            }
            if ($destination) {
                $destination->increment('current_balance', $amount);
            }
            if ($transaction->type === FinancialTransaction::TYPE_DEPOSIT && ! $destination) {
                $this->defaultFund()->increment('current_balance', $amount);
            }
            if ($transaction->type === FinancialTransaction::TYPE_WITHDRAWAL && ! $source) {
                $this->defaultFund()->decrement('current_balance', $amount);
            }

            $transaction->update([
                'journal_entry_id' => $entry->id,
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return $entry;
        });
    }

    public function postCustomerPayment(
        CustomerTransaction $transaction,
        ?string $fundType = null,
        ?int $fundId = null,
    ): JournalEntry {
        return DB::transaction(function () use ($transaction, $fundType, $fundId): JournalEntry {
            $fund = $fundId ? $this->fund($fundType, $fundId, true) : $this->defaultFund();
            $amount = (float) $transaction->credit;
            $entry = $this->createAndPost(
                'تحصيل من العميل - '.$transaction->number,
                [
                    ['account' => $fund->chart_account_id, 'debit' => $amount],
                    ['account' => $this->systemAccount('1200'), 'credit' => $amount],
                ],
                $transaction,
                $transaction->transaction_date,
                $transaction->number,
            );

            $fund->increment('current_balance', $amount);
            $transaction->update([
                'journal_entry_id' => $entry->id,
                'fund_type' => $fund instanceof Treasury ? 'treasury' : 'bank',
                'fund_id' => $fund->id,
            ]);

            return $entry;
        });
    }

    public function postSupplierPayment(
        SupplierTransaction $transaction,
        ?string $fundType = null,
        ?int $fundId = null,
    ): JournalEntry {
        return DB::transaction(function () use ($transaction, $fundType, $fundId): JournalEntry {
            $fund = $fundId ? $this->fund($fundType, $fundId, true) : $this->defaultFund();
            $amount = (float) $transaction->debit;

            if ((float) $fund->current_balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'رصيد حساب الدفع غير كافٍ.']);
            }

            $entry = $this->createAndPost(
                'سداد للمورد - '.$transaction->number,
                [
                    ['account' => $this->systemAccount('2100'), 'debit' => $amount],
                    ['account' => $fund->chart_account_id, 'credit' => $amount],
                ],
                $transaction,
                $transaction->transaction_date,
                $transaction->number,
            );

            $fund->decrement('current_balance', $amount);
            $transaction->update([
                'journal_entry_id' => $entry->id,
                'fund_type' => $fund instanceof Treasury ? 'treasury' : 'bank',
                'fund_id' => $fund->id,
            ]);

            return $entry;
        });
    }

    public function postExpense(Expense $expense): JournalEntry
    {
        return DB::transaction(function () use ($expense): JournalEntry {
            $fund = $this->fund($expense->payment_fund_type, $expense->payment_fund_id, true);

            if ((float) $fund->current_balance < (float) $expense->amount) {
                throw ValidationException::withMessages(['amount' => 'رصيد حساب الدفع غير كافٍ.']);
            }

            $entry = $this->createAndPost(
                'مصروف '.$expense->number.' - '.$expense->description,
                [
                    ['account' => $expense->category->chart_account_id, 'debit' => (float) $expense->amount],
                    ['account' => $fund->chart_account_id, 'credit' => (float) $expense->amount],
                ],
                $expense,
                $expense->expense_date,
                $expense->number,
            );

            $fund->decrement('current_balance', (float) $expense->amount);
            $expense->update(['journal_entry_id' => $entry->id, 'status' => 'posted', 'posted_at' => now()]);

            return $entry;
        });
    }

    public function postVoucher(Voucher $voucher): JournalEntry
    {
        if ($voucher->status === 'posted') {
            return $voucher->journalEntry;
        }

        return DB::transaction(function () use ($voucher): JournalEntry {
            $fund = $this->fund($voucher->fund_type, $voucher->fund_id, true);
            $amount = (float) $voucher->amount;

            if ($voucher->type === Voucher::TYPE_PAYMENT && (float) $fund->current_balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'رصيد حساب الدفع غير كافٍ.']);
            }

            if ($voucher->party_type === (new Customer)->getMorphClass() && $voucher->type === Voucher::TYPE_RECEIPT) {
                $transaction = app(LedgerService::class)->customerEntry(
                    $voucher->party_id,
                    CustomerTransaction::TYPE_PAYMENT,
                    credit: $amount,
                    reference: $voucher,
                    notes: $voucher->description,
                    paymentMethod: $voucher->payment_method,
                    transactionDate: $voucher->voucher_date,
                    fundType: $voucher->fund_type,
                    fundId: $voucher->fund_id,
                );
                $entry = $transaction->journalEntry()->firstOrFail();
            } elseif ($voucher->party_type === (new Supplier)->getMorphClass() && $voucher->type === Voucher::TYPE_PAYMENT) {
                $transaction = app(LedgerService::class)->supplierEntry(
                    $voucher->party_id,
                    SupplierTransaction::TYPE_PAYMENT,
                    debit: $amount,
                    reference: $voucher,
                    notes: $voucher->description,
                    paymentMethod: $voucher->payment_method,
                    transactionDate: $voucher->voucher_date,
                    fundType: $voucher->fund_type,
                    fundId: $voucher->fund_id,
                );
                $entry = $transaction->journalEntry()->firstOrFail();
            } else {
                $entry = $this->createAndPost(
                    Voucher::typeLabels()[$voucher->type].' '.$voucher->number,
                    $voucher->type === Voucher::TYPE_RECEIPT
                        ? [
                            ['account' => $fund->chart_account_id, 'debit' => $amount],
                            ['account' => $this->systemAccount('3100'), 'credit' => $amount],
                        ]
                        : [
                            ['account' => $this->systemAccount('5100'), 'debit' => $amount],
                            ['account' => $fund->chart_account_id, 'credit' => $amount],
                        ],
                    $voucher,
                    $voucher->voucher_date,
                    $voucher->number,
                );

                $voucher->type === Voucher::TYPE_RECEIPT
                    ? $fund->increment('current_balance', $amount)
                    : $fund->decrement('current_balance', $amount);
            }

            $voucher->update(['journal_entry_id' => $entry->id, 'status' => 'posted', 'posted_at' => now()]);

            return $entry;
        });
    }

    public function fund(?string $type, int $id, bool $lock = false): Treasury|BankAccount
    {
        $query = match ($type) {
            'treasury' => Treasury::query(),
            'bank' => BankAccount::query(),
            default => throw ValidationException::withMessages(['fund_type' => 'نوع الحساب المالي غير صالح.']),
        };

        return ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
    }

    public function defaultFund(): Treasury
    {
        return Treasury::query()->where('is_default', true)->first()
            ?? Treasury::query()->create([
                'chart_account_id' => $this->systemAccount('1101')->id,
                'name' => 'الخزينة الرئيسية',
                'code' => 'MAIN',
                'is_default' => true,
            ]);
    }

    public function systemAccount(string $code): ChartAccount
    {
        $definitions = [
            '1101' => ['النقدية بالصندوق', ChartAccount::TYPE_ASSET, 'debit'],
            '1150' => ['ضريبة القيمة المضافة المدخلة', ChartAccount::TYPE_ASSET, 'debit'],
            '1200' => ['العملاء', ChartAccount::TYPE_ASSET, 'debit'],
            '1250' => ['سلف وعهد الموظفين', ChartAccount::TYPE_ASSET, 'debit'],
            '1300' => ['المخزون', ChartAccount::TYPE_ASSET, 'debit'],
            '2100' => ['الموردون', ChartAccount::TYPE_LIABILITY, 'credit'],
            '2200' => ['ضريبة القيمة المضافة المستحقة', ChartAccount::TYPE_LIABILITY, 'credit'],
            '3100' => ['رأس المال', ChartAccount::TYPE_EQUITY, 'credit'],
            '4100' => ['إيرادات المبيعات', ChartAccount::TYPE_REVENUE, 'credit'],
            '5100' => ['مصروفات عمومية', ChartAccount::TYPE_EXPENSE, 'debit'],
            '5200' => ['مصروف الرواتب', ChartAccount::TYPE_EXPENSE, 'debit'],
            '5300' => ['مصروف الإيجار', ChartAccount::TYPE_EXPENSE, 'debit'],
            '5400' => ['مصروف الصيانة', ChartAccount::TYPE_EXPENSE, 'debit'],
        ];

        if (! isset($definitions[$code])) {
            throw ValidationException::withMessages(['account' => "الحساب النظامي {$code} غير معرّف."]);
        }

        [$name, $type, $normal] = $definitions[$code];

        return ChartAccount::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'normal_balance' => $normal],
        );
    }
}
