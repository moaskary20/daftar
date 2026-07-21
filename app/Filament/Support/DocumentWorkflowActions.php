<?php

namespace App\Filament\Support;

use App\Filament\Resources\PurchaseDocuments\PurchaseDocumentResource;
use App\Filament\Resources\SalesDeliveries\SalesDeliveryResource;
use App\Filament\Resources\SalesDocuments\SalesDocumentResource;
use App\Models\PurchaseDocument;
use App\Models\SalesDocument;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DocumentWorkflowActions
{
    /**
     * @return array<int, Action>
     */
    public static function forSales(SalesDocument $record, bool $redirectAfter = true): array
    {
        return [
            Action::make('approveSales')
                ->label('اعتماد')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->status === SalesDocument::STATUS_DRAFT)
                ->action(function () use ($record, $redirectAfter) {
                    app(SalesService::class)->approve($record->fresh());
                    self::notify('تم اعتماد مستند البيع.');

                    return $redirectAfter
                        ? redirect(SalesDocumentResource::getUrl('view', ['record' => $record]))
                        : null;
                }),
            Action::make('convertToOrder')
                ->label('تحويل إلى أمر بيع')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === SalesDocument::TYPE_QUOTATION
                    && in_array($record->status, [SalesDocument::STATUS_DRAFT, SalesDocument::STATUS_APPROVED], true))
                ->action(function () use ($record) {
                    $order = app(SalesService::class)->convertToOrder($record->fresh(['items']));
                    self::notify('تم إنشاء أمر البيع '.$order->number);

                    return redirect(SalesDocumentResource::getUrl('edit', ['record' => $order]));
                }),
            Action::make('createDelivery')
                ->label('إنشاء تسليم')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === SalesDocument::TYPE_ORDER
                    && in_array($record->status, [SalesDocument::STATUS_APPROVED, SalesDocument::STATUS_PARTIAL, SalesDocument::STATUS_DRAFT], true))
                ->action(function () use ($record) {
                    $delivery = app(SalesService::class)->createDelivery($record->fresh(['items']));
                    self::notify('تم إنشاء مسودة التسليم '.$delivery->number);

                    return redirect(SalesDeliveryResource::getUrl('edit', ['record' => $delivery]));
                }),
            Action::make('convertToInvoice')
                ->label('تحويل إلى فاتورة')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === SalesDocument::TYPE_ORDER
                    && in_array($record->status, [
                        SalesDocument::STATUS_APPROVED,
                        SalesDocument::STATUS_PARTIAL,
                        SalesDocument::STATUS_DELIVERED,
                    ], true))
                ->action(function () use ($record) {
                    $invoice = app(SalesService::class)->convertToInvoice($record->fresh(['items']));
                    self::notify('تم إنشاء فاتورة المبيعات '.$invoice->number);

                    return redirect(SalesDocumentResource::getUrl('edit', ['record' => $invoice]));
                }),
            Action::make('createReturn')
                ->label('إنشاء مرتجع')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === SalesDocument::TYPE_INVOICE
                    && $record->status === SalesDocument::STATUS_POSTED)
                ->action(function () use ($record) {
                    $return = app(SalesService::class)->createReturn($record->fresh(['items']));
                    self::notify('تم إنشاء مرتجع المبيعات '.$return->number);

                    return redirect(SalesDocumentResource::getUrl('edit', ['record' => $return]));
                }),
            Action::make('postSales')
                ->label('ترحيل')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('سيتم تحديث المخزون وحساب العميل والقيود المحاسبية.')
                ->visible(fn (): bool => in_array($record->type, [
                    SalesDocument::TYPE_INVOICE,
                    SalesDocument::TYPE_RETURN,
                ], true) && ! in_array($record->status, [
                    SalesDocument::STATUS_POSTED,
                    SalesDocument::STATUS_CANCELLED,
                ], true))
                ->action(function () use ($record) {
                    app(SalesService::class)->post($record->fresh(['items', 'customer', 'sourceDocument.items', 'sourceDocument']));
                    self::notify('تم ترحيل المستند بنجاح.');

                    return redirect(SalesDocumentResource::getUrl('view', ['record' => $record]));
                }),
            Action::make('cancelSales')
                ->label('إلغاء')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($record->status, [
                    SalesDocument::STATUS_DRAFT,
                    SalesDocument::STATUS_APPROVED,
                ], true))
                ->action(function () use ($record, $redirectAfter) {
                    app(SalesService::class)->cancel($record->fresh());
                    self::notify('تم إلغاء المستند.');

                    return $redirectAfter
                        ? redirect(SalesDocumentResource::getUrl('view', ['record' => $record]))
                        : null;
                }),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public static function forPurchases(PurchaseDocument $record, bool $redirectAfter = true): array
    {
        return [
            Action::make('approvePurchase')
                ->label('اعتماد')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->status === PurchaseDocument::STATUS_DRAFT)
                ->action(function () use ($record, $redirectAfter) {
                    app(PurchaseService::class)->approve($record->fresh());
                    self::notify('تم اعتماد مستند الشراء.');

                    return $redirectAfter
                        ? redirect(PurchaseDocumentResource::getUrl('view', ['record' => $record]))
                        : null;
                }),
            Action::make('convertPurchaseToOrder')
                ->label('تحويل إلى أمر شراء')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === PurchaseDocument::TYPE_QUOTATION
                    && in_array($record->status, [PurchaseDocument::STATUS_DRAFT, PurchaseDocument::STATUS_APPROVED], true))
                ->action(function () use ($record) {
                    $order = app(PurchaseService::class)->convertToOrder($record->fresh(['items', 'expenses']));
                    self::notify('تم إنشاء أمر الشراء '.$order->number);

                    return redirect(PurchaseDocumentResource::getUrl('edit', ['record' => $order]));
                }),
            Action::make('convertPurchaseToInvoice')
                ->label('تحويل إلى فاتورة شراء')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === PurchaseDocument::TYPE_ORDER
                    && in_array($record->status, [PurchaseDocument::STATUS_DRAFT, PurchaseDocument::STATUS_APPROVED], true))
                ->action(function () use ($record) {
                    $invoice = app(PurchaseService::class)->convertToInvoice($record->fresh(['items', 'expenses']));
                    self::notify('تم إنشاء فاتورة الشراء '.$invoice->number);

                    return redirect(PurchaseDocumentResource::getUrl('edit', ['record' => $invoice]));
                }),
            Action::make('createPurchaseReturn')
                ->label('إنشاء مرتجع شراء')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $record->type === PurchaseDocument::TYPE_INVOICE
                    && $record->status === PurchaseDocument::STATUS_POSTED)
                ->action(function () use ($record) {
                    $return = app(PurchaseService::class)->createReturn($record->fresh(['items', 'expenses']));
                    self::notify('تم إنشاء مرتجع الشراء '.$return->number);

                    return redirect(PurchaseDocumentResource::getUrl('edit', ['record' => $return]));
                }),
            Action::make('postPurchase')
                ->label('ترحيل للمخزون')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('سيتم تحديث المخزون وحساب المورد والقيود المحاسبية.')
                ->visible(fn (): bool => in_array($record->type, [
                    PurchaseDocument::TYPE_INVOICE,
                    PurchaseDocument::TYPE_RETURN,
                ], true) && ! in_array($record->status, [
                    PurchaseDocument::STATUS_POSTED,
                    PurchaseDocument::STATUS_CANCELLED,
                ], true))
                ->action(function () use ($record) {
                    app(PurchaseService::class)->post($record->fresh(['items.product', 'sourceDocument.items']));
                    self::notify('تم ترحيل مستند الشراء بنجاح.');

                    return redirect(PurchaseDocumentResource::getUrl('view', ['record' => $record]));
                }),
            Action::make('cancelPurchase')
                ->label('إلغاء')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($record->status, [
                    PurchaseDocument::STATUS_DRAFT,
                    PurchaseDocument::STATUS_APPROVED,
                ], true))
                ->action(function () use ($record, $redirectAfter) {
                    app(PurchaseService::class)->cancel($record->fresh());
                    self::notify('تم إلغاء المستند.');

                    return $redirectAfter
                        ? redirect(PurchaseDocumentResource::getUrl('view', ['record' => $record]))
                        : null;
                }),
        ];
    }

    private static function notify(string $body): void
    {
        Notification::make()
            ->title('دورة العمل')
            ->body($body)
            ->success()
            ->send();
    }
}
