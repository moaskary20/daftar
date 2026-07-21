<?php

namespace App\Filament\Resources\PurchaseDocuments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('supplier.name')
                    ->label('المورد'),
                TextEntry::make('warehouse.name')
                    ->label('المخزن')
                    ->placeholder('-'),
                TextEntry::make('sourceDocument.id')
                    ->label('المستند المرجعي')
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('number')->label('رقم المستند'),
                TextEntry::make('type')->label('النوع'),
                TextEntry::make('status')->label('الحالة'),
                TextEntry::make('document_date')
                    ->label('تاريخ المستند')
                    ->date(),
                TextEntry::make('expected_date')
                    ->label('تاريخ التوريد المتوقع')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('supplier_reference')
                    ->label('مرجع المورد')
                    ->placeholder('-'),
                TextEntry::make('currency')->label('العملة'),
                TextEntry::make('subtotal')
                    ->label('صافي الأصناف')
                    ->numeric(),
                TextEntry::make('discount_total')
                    ->label('الخصم')
                    ->numeric(),
                TextEntry::make('tax_total')
                    ->label('الضريبة')
                    ->numeric(),
                TextEntry::make('shipping_cost')
                    ->label('الشحن')
                    ->money('EGP'),
                TextEntry::make('customs_cost')
                    ->label('الجمارك')
                    ->money('EGP'),
                TextEntry::make('expense_total')
                    ->label('المصروفات')
                    ->numeric(),
                TextEntry::make('grand_total')
                    ->label('الإجمالي النهائي')
                    ->numeric(),
                TextEntry::make('posted_at')
                    ->label('تاريخ الترحيل')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
