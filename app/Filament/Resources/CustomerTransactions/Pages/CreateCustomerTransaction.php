<?php

namespace App\Filament\Resources\CustomerTransactions\Pages;

use App\Filament\Resources\CustomerTransactions\CustomerTransactionResource;
use App\Services\LedgerService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomerTransaction extends CreateRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LedgerService::class)->customerEntry(
            $data['customer_id'],
            $data['type'],
            debit: (float) ($data['debit'] ?? 0),
            credit: (float) ($data['credit'] ?? 0),
            notes: $data['notes'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            transactionDate: $data['transaction_date'] ?? null,
            fundType: $data['fund_type'] ?? null,
            fundId: isset($data['fund_id']) ? (int) $data['fund_id'] : null,
        );
    }
}
