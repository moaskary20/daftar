<?php

namespace App\Filament\Resources\SalesDocuments\Schemas;

use App\Models\SalesDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SalesDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات مستند البيع')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        Select::make('type')->label('نوع المستند')->options(SalesDocument::typeLabels())->required()->live(),
                        TextInput::make('number')->label('رقم المستند')->placeholder('يُولّد تلقائياً')->unique(ignoreRecord: true),
                        Select::make('customer_id')
                            ->label('العميل')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('مخزن الصرف / الإرجاع')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => in_array($get('type'), [
                                SalesDocument::TYPE_INVOICE,
                                SalesDocument::TYPE_RETURN,
                            ], true)),
                        Select::make('source_document_id')
                            ->label('المستند المرجعي')
                            ->relationship('sourceDocument', 'number')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('الحالة')
                            ->options(SalesDocument::statusLabels())
                            ->default(SalesDocument::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated(),
                        DatePicker::make('document_date')->label('تاريخ المستند')->default(today())->required(),
                        DatePicker::make('expected_date')->label('موعد التسليم المتوقع'),
                        TextInput::make('customer_reference')->label('مرجع العميل'),
                        Select::make('currency')
                            ->label('العملة')
                            ->options(['EGP' => 'جنيه مصري', 'USD' => 'دولار', 'EUR' => 'يورو'])
                            ->default('EGP')
                            ->required(),
                        Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('الأصناف')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('المنتج')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Select::make('product_variant_id')
                                    ->label('المتغير')
                                    ->relationship('variant', 'name', fn ($query, Get $get) => $query->where('product_id', $get('product_id')))
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')->label('الكمية')->numeric()->minValue(0.001)->required(),
                                TextInput::make('unit_price')->label('سعر الوحدة')->numeric()->prefix('ج.م')->required(),
                                TextInput::make('discount_amount')->label('الخصم')->numeric()->prefix('ج.م')->default(0),
                                TextInput::make('tax_rate')->label('الضريبة %')->numeric()->suffix('%')->default(15),
                                TextInput::make('delivered_quantity')->label('تم تسليمه')->numeric()->disabled()->dehydrated(),
                                Textarea::make('description')->label('وصف'),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة صنف')
                            ->collapsible()
                            ->cloneable(),
                    ]),
                Section::make('الإجماليات')
                    ->schema([
                        TextInput::make('shipping_cost')->label('تكلفة التوصيل')->numeric()->prefix('ج.م')->default(0),
                        TextInput::make('subtotal')->label('صافي الأصناف')->prefix('ج.م')->disabled(),
                        TextInput::make('discount_total')->label('إجمالي الخصم')->prefix('ج.م')->disabled(),
                        TextInput::make('tax_total')->label('إجمالي الضريبة')->prefix('ج.م')->disabled(),
                        TextInput::make('grand_total')->label('الإجمالي النهائي')->prefix('ج.م')->disabled(),
                    ])
                    ->columns(5),
            ]);
    }
}
