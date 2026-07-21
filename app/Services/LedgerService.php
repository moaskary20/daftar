<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function customerEntry(
        Customer|int $customer,
        string $type,
        float $debit = 0,
        float $credit = 0,
        ?Model $reference = null,
        ?string $notes = null,
        ?string $paymentMethod = null,
        mixed $transactionDate = null,
        ?string $fundType = null,
        ?int $fundId = null,
    ): CustomerTransaction {
        $this->validateAmounts($debit, $credit);
        $customerId = $customer instanceof Customer ? $customer->getKey() : $customer;

        $transaction = DB::transaction(function () use ($customerId, $type, $debit, $credit, $reference, $notes, $paymentMethod, $transactionDate): CustomerTransaction {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customerId);
            $balance = round((float) $customer->current_balance + $debit - $credit, 4);
            $customer->update(['current_balance' => $balance]);

            return CustomerTransaction::query()->create([
                'customer_id' => $customer->id,
                'created_by' => auth()->id(),
                'number' => 'CTX-'.Str::upper((string) Str::ulid()),
                'type' => $type,
                'debit' => $debit,
                'credit' => $credit,
                'balance_after' => $balance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'transaction_date' => $transactionDate ?? today(),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);
        });

        if ($type === CustomerTransaction::TYPE_PAYMENT && $fundId) {
            app(AccountingService::class)->postCustomerPayment($transaction, $fundType, $fundId);
        }

        return $transaction->fresh();
    }

    public function supplierEntry(
        Supplier|int $supplier,
        string $type,
        float $debit = 0,
        float $credit = 0,
        ?Model $reference = null,
        ?string $notes = null,
        ?string $paymentMethod = null,
        array $check = [],
        mixed $transactionDate = null,
        ?string $fundType = null,
        ?int $fundId = null,
    ): SupplierTransaction {
        $this->validateAmounts($debit, $credit);
        $supplierId = $supplier instanceof Supplier ? $supplier->getKey() : $supplier;

        $transaction = DB::transaction(function () use ($supplierId, $type, $debit, $credit, $reference, $notes, $paymentMethod, $check, $transactionDate): SupplierTransaction {
            $supplier = Supplier::query()->lockForUpdate()->findOrFail($supplierId);
            $balance = round((float) $supplier->current_balance + $credit - $debit, 4);
            $supplier->update(['current_balance' => $balance]);

            return SupplierTransaction::query()->create([
                'supplier_id' => $supplier->id,
                'created_by' => auth()->id(),
                'number' => 'STX-'.Str::upper((string) Str::ulid()),
                'type' => $type,
                'debit' => $debit,
                'credit' => $credit,
                'balance_after' => $balance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'transaction_date' => $transactionDate ?? today(),
                'payment_method' => $paymentMethod,
                'check_number' => $check['check_number'] ?? null,
                'check_due_date' => $check['check_due_date'] ?? null,
                'bank_name' => $check['bank_name'] ?? null,
                'check_status' => $check['check_status'] ?? null,
                'notes' => $notes,
            ]);
        });

        if (in_array($type, [SupplierTransaction::TYPE_PAYMENT, SupplierTransaction::TYPE_CHECK], true) && $fundId) {
            app(AccountingService::class)->postSupplierPayment($transaction, $fundType, $fundId);
        }

        return $transaction->fresh();
    }

    private function validateAmounts(float $debit, float $credit): void
    {
        if ($debit < 0 || $credit < 0 || ($debit === 0.0 && $credit === 0.0)) {
            throw ValidationException::withMessages([
                'amount' => 'يجب إدخال مبلغ صحيح أكبر من صفر.',
            ]);
        }
    }
}
