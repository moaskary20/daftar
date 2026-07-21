<?php

namespace App\Filament\Resources\SupplierTransactions\Pages;

use App\Filament\Resources\SupplierTransactions\SupplierTransactionResource;
use App\Services\LedgerService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierTransaction extends CreateRecord
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LedgerService::class)->supplierEntry(
            $data['supplier_id'],
            $data['type'],
            debit: (float) ($data['debit'] ?? 0),
            credit: (float) ($data['credit'] ?? 0),
            notes: $data['notes'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            check: [
                'check_number' => $data['check_number'] ?? null,
                'check_due_date' => $data['check_due_date'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'check_status' => $data['check_status'] ?? null,
            ],
            transactionDate: $data['transaction_date'] ?? null,
            fundType: $data['fund_type'] ?? null,
            fundId: isset($data['fund_id']) ? (int) $data['fund_id'] : null,
        );
    }
}
