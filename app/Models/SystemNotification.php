<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemNotification extends Model
{
    protected $fillable = [
        'unique_key', 'type', 'severity', 'title', 'message', 'reference_type',
        'reference_id', 'due_date', 'action_url', 'metadata', 'read_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'metadata' => 'array', 'read_at' => 'datetime'];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public static function typeLabels(): array
    {
        return [
            'expiry' => 'قرب انتهاء الصلاحية',
            'out_of_stock' => 'نفاد المخزون',
            'low_stock' => 'مخزون منخفض',
            'customer_debt' => 'مديونية عميل',
            'overdue_invoice' => 'فاتورة مستحقة',
            'overdue_installment' => 'قسط متأخر',
            'backup' => 'نسخة احتياطية',
        ];
    }
}
