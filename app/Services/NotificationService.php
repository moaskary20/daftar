<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InstallmentPayment;
use App\Models\InventoryBatch;
use App\Models\SalesDocument;
use App\Models\SystemNotification;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationService
{
    public function generateAll(int $expiryDays = 30): Collection
    {
        $notifications = collect()
            ->merge($this->expiryNotifications($expiryDays))
            ->merge($this->stockNotifications())
            ->merge($this->customerDebtNotifications())
            ->merge($this->overdueInvoiceNotifications())
            ->merge($this->overdueInstallmentNotifications());

        return $notifications->sortByDesc('created_at')->values();
    }

    public function expiryNotifications(int $days = 30): Collection
    {
        $keys = [];
        $notifications = InventoryBatch::query()
            ->with(['product', 'warehouse'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), today()->addDays($days)])
            ->get()
            ->map(function (InventoryBatch $batch) use (&$keys): SystemNotification {
                $key = 'expiry:'.$batch->id;
                $keys[] = $key;

                return $this->store(
                    $key,
                    'expiry',
                    $batch->expiry_date->isToday() ? 'danger' : 'warning',
                    'قرب انتهاء صلاحية '.$batch->product->name,
                    "الدفعة {$batch->batch_number} في {$batch->warehouse->name} تنتهي بتاريخ {$batch->expiry_date->format('Y-m-d')}.",
                    $batch,
                    $batch->expiry_date,
                    url('/admin/inventory-batches'),
                    ['quantity' => (float) $batch->quantity],
                );
            });

        $this->removeResolved('expiry', $keys);

        return $notifications;
    }

    public function stockNotifications(): Collection
    {
        $keys = [];
        $notifications = WarehouseStock::query()
            ->with(['product', 'variant', 'warehouse'])
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->get()
            ->map(function (WarehouseStock $stock) use (&$keys): SystemNotification {
                $type = (float) $stock->quantity <= 0 ? 'out_of_stock' : 'low_stock';
                $key = $type.':'.$stock->id;
                $keys[] = $key;
                $name = $stock->product->name.($stock->variant ? ' - '.$stock->variant->name : '');

                return $this->store(
                    $key,
                    $type,
                    $type === 'out_of_stock' ? 'danger' : 'warning',
                    ($type === 'out_of_stock' ? 'نفاد مخزون ' : 'انخفاض مخزون ').$name,
                    "الرصيد في {$stock->warehouse->name}: {$stock->quantity}، وحد إعادة الطلب: {$stock->reorder_level}.",
                    $stock,
                    null,
                    url('/admin/warehouse-stocks'),
                );
            });

        $this->removeResolved('out_of_stock', array_filter($keys, fn (string $key): bool => str_starts_with($key, 'out_of_stock:')));
        $this->removeResolved('low_stock', array_filter($keys, fn (string $key): bool => str_starts_with($key, 'low_stock:')));

        return $notifications;
    }

    public function customerDebtNotifications(): Collection
    {
        $keys = [];
        $notifications = Customer::query()
            ->where('current_balance', '>', 0)
            ->get()
            ->map(function (Customer $customer) use (&$keys): SystemNotification {
                $key = 'customer_debt:'.$customer->id;
                $keys[] = $key;
                $exceeded = (float) $customer->credit_limit > 0
                    && (float) $customer->current_balance > (float) $customer->credit_limit;

                return $this->store(
                    $key,
                    'customer_debt',
                    $exceeded ? 'danger' : 'warning',
                    'مديونية العميل '.$customer->name,
                    'الرصيد المستحق: '.number_format((float) $customer->current_balance, 2).' ج.م'
                        .($exceeded ? ' — تجاوز حد الائتمان.' : ''),
                    $customer,
                    null,
                    url('/admin/customer-transactions?tableFilters[customer_id][value]='.$customer->id),
                );
            });

        $this->removeResolved('customer_debt', $keys);

        return $notifications;
    }

    public function overdueInvoiceNotifications(): Collection
    {
        $keys = [];
        $notifications = SalesDocument::query()
            ->with('customer')
            ->where('type', SalesDocument::TYPE_INVOICE)
            ->where('status', SalesDocument::STATUS_POSTED)
            ->whereNotNull('expected_date')
            ->whereDate('expected_date', '<', today())
            ->whereHas('customer', fn ($query) => $query->where('current_balance', '>', 0))
            ->get()
            ->map(function (SalesDocument $document) use (&$keys): SystemNotification {
                $key = 'overdue_invoice:'.$document->id;
                $keys[] = $key;

                return $this->store(
                    $key,
                    'overdue_invoice',
                    'danger',
                    'فاتورة مستحقة '.$document->number,
                    "فاتورة العميل {$document->customer->name} مستحقة منذ {$document->expected_date->format('Y-m-d')}.",
                    $document,
                    $document->expected_date,
                    url('/admin/sales-documents/'.$document->id),
                    ['amount' => (float) $document->grand_total],
                );
            });

        $this->removeResolved('overdue_invoice', $keys);

        return $notifications;
    }

    public function overdueInstallmentNotifications(): Collection
    {
        $keys = [];
        $notifications = InstallmentPayment::query()
            ->with('plan.customer')
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', today())
            ->get()
            ->map(function (InstallmentPayment $installment) use (&$keys): SystemNotification {
                $key = 'overdue_installment:'.$installment->id;
                $keys[] = $key;
                $remaining = (float) $installment->amount - (float) $installment->paid_amount;

                return $this->store(
                    $key,
                    'overdue_installment',
                    'danger',
                    'قسط متأخر للعميل '.$installment->plan->customer->name,
                    'القسط رقم '.$installment->sequence.' متأخر منذ '.$installment->due_date->format('Y-m-d')
                        .' والمتبقي '.number_format($remaining, 2).' ج.م.',
                    $installment,
                    $installment->due_date,
                    url('/admin/installment-plans/'.$installment->installment_plan_id),
                );
            });

        $this->removeResolved('overdue_installment', $keys);

        return $notifications;
    }

    private function store(
        string $key,
        string $type,
        string $severity,
        string $title,
        string $message,
        ?Model $reference = null,
        mixed $dueDate = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): SystemNotification {
        return SystemNotification::query()->updateOrCreate(
            ['unique_key' => $key],
            [
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'due_date' => $dueDate,
                'action_url' => $actionUrl,
                'metadata' => $metadata,
            ],
        );
    }

    private function removeResolved(string $type, array $activeKeys): void
    {
        SystemNotification::query()
            ->where('type', $type)
            ->when($activeKeys, fn ($query) => $query->whereNotIn('unique_key', $activeKeys))
            ->when(! $activeKeys, fn ($query) => $query)
            ->delete();
    }
}
