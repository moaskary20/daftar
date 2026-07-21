<?php

namespace App\Filament\Resources\PurchaseDocuments\Schemas;

use App\Models\PurchaseDocument;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PurchaseDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات مستند الشراء')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->schema([
                        Select::make('type')
                            ->label('نوع المستند')
                            ->options(PurchaseDocument::typeLabels())
                            ->required()
                            ->live(),
                        TextInput::make('number')
                            ->label('رقم المستند')
                            ->placeholder('يُولّد تلقائياً')
                            ->unique(ignoreRecord: true),
                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('مخزن الاستلام / الصرف')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => in_array($get('type'), [
                                PurchaseDocument::TYPE_INVOICE,
                                PurchaseDocument::TYPE_RETURN,
                            ], true)),
                        Select::make('source_document_id')
                            ->label('المستند المرجعي')
                            ->relationship('sourceDocument', 'number')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('الحالة')
                            ->options(PurchaseDocument::statusLabels())
                            ->default(PurchaseDocument::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated(),
                        DatePicker::make('document_date')
                            ->label('تاريخ المستند')
                            ->default(today())
                            ->required(),
                        DatePicker::make('expected_date')
                            ->label('تاريخ التوريد المتوقع'),
                        TextInput::make('supplier_reference')
                            ->label('مرجع المورد'),
                        Select::make('currency')
                            ->label('العملة')
                            ->options(['EGP' => 'جنيه مصري', 'USD' => 'دولار', 'EUR' => 'يورو'])
                            ->default('EGP')
                            ->required(),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
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
                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->required(),
                                TextInput::make('unit_cost')
                                    ->label('تكلفة الوحدة')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->required(),
                                TextInput::make('discount_amount')
                                    ->label('الخصم')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->default(0),
                                TextInput::make('tax_rate')
                                    ->label('الضريبة %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(15),
                                TextInput::make('batch_number')
                                    ->label('رقم الدفعة'),
                                DatePicker::make('production_date')
                                    ->label('تاريخ الإنتاج'),
                                DatePicker::make('expiry_date')
                                    ->label('تاريخ الصلاحية')
                                    ->afterOrEqual('production_date'),
                                Textarea::make('description')
                                    ->label('وصف')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة صنف')
                            ->collapsible()
                            ->cloneable(),
                    ]),
                Section::make('التكاليف الإضافية والمصروفات')
                    ->schema([
                        TextInput::make('shipping_cost')
                            ->label('تكلفة الشحن')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),
                        TextInput::make('customs_cost')
                            ->label('تكلفة الجمارك')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),
                        Repeater::make('expenses')
                            ->label('مصروفات الشراء')
                            ->relationship()
                            ->schema([
                                Select::make('category')
                                    ->label('نوع المصروف')
                                    ->options([
                                        'transport' => 'نقل',
                                        'insurance' => 'تأمين',
                                        'handling' => 'مناولة',
                                        'inspection' => 'فحص',
                                        'other' => 'أخرى',
                                    ])
                                    ->required(),
                                TextInput::make('description')
                                    ->label('البيان'),
                                TextInput::make('amount')
                                    ->label('المبلغ')
                                    ->numeric()
                                    ->prefix('ج.م')
                                    ->required(),
                                DatePicker::make('expense_date')
                                    ->label('التاريخ')
                                    ->default(today())
                                    ->required(),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة مصروف')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('إجماليات المستند')
                    ->schema([
                        TextInput::make('subtotal')->label('صافي الأصناف')->prefix('ج.م')->disabled(),
                        TextInput::make('discount_total')->label('إجمالي الخصم')->prefix('ج.م')->disabled(),
                        TextInput::make('tax_total')->label('إجمالي الضريبة')->prefix('ج.م')->disabled(),
                        TextInput::make('expense_total')->label('إجمالي المصروفات')->prefix('ج.م')->disabled(),
                        TextInput::make('grand_total')->label('الإجمالي النهائي')->prefix('ج.م')->disabled(),
                    ])
                    ->columns(5)
                    ->collapsible(),
            ]);
    }
}
