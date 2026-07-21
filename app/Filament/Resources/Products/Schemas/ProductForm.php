<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المنتج الأساسية')
                    ->description('الاسم والتصنيف والبيانات المرجعية للمنتج')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المنتج')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->label('الرابط المختصر')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('type')
                            ->label('نوع المنتج')
                            ->options([
                                Product::TYPE_SIMPLE => 'منتج بسيط',
                                Product::TYPE_VARIABLE => 'منتج بمتغيرات',
                            ])
                            ->default(Product::TYPE_SIMPLE)
                            ->required()
                            ->live(),
                        TextInput::make('sku')
                            ->label('رمز الصنف SKU')
                            ->placeholder('يُولّد تلقائياً عند تركه فارغاً')
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('التصنيف')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('اسم التصنيف')->required(),
                                TextInput::make('slug')->label('الرابط')->required(),
                            ]),
                        Select::make('department_id')
                            ->label('القسم')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('brand_id')
                            ->label('الماركة')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('primary_supplier_id')
                            ->label('المورد الرئيسي')
                            ->relationship('primarySupplier', 'name')
                            ->searchable()
                            ->preload(),
                        FileUpload::make('image')
                            ->label('صورة المنتج')
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('200')
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('يفضّل صورة مربعة بجودة واضحة تظهر في نقطة البيع.'),
                        Textarea::make('description')
                            ->label('الوصف')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('التكلفة والأسعار')
                    ->description('أسعار الشراء والتكلفة المتوسطة وسعر البيع الأساسي')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('purchase_price')
                            ->label('سعر الشراء')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('ر.س'),
                        TextInput::make('average_cost')
                            ->label('متوسط التكلفة')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('ر.س'),
                        TextInput::make('selling_price')
                            ->label('سعر البيع الأساسي')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('ر.س'),
                        Repeater::make('prices')
                            ->label('قوائم أسعار البيع')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم السعر')
                                    ->placeholder('جملة، موزع، تجزئة...')
                                    ->required(),
                                TextInput::make('price')
                                    ->label('السعر')
                                    ->numeric()
                                    ->required()
                                    ->prefix('ر.س'),
                                TextInput::make('minimum_quantity')
                                    ->label('أقل كمية')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Toggle::make('is_default')
                                    ->label('السعر الافتراضي'),
                                Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة سعر بيع')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('المخزون والتعريف')
                    ->icon('heroicon-o-qr-code')
                    ->schema([
                        TextInput::make('stock_quantity')
                            ->label('الكمية الحالية')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('minimum_stock')
                            ->label('حد إعادة الطلب')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('weight')
                            ->label('الوزن')
                            ->numeric()
                            ->suffix('كجم'),
                        TextInput::make('barcode')
                            ->label('الباركود EAN-13')
                            ->placeholder('يُولّد تلقائياً')
                            ->unique(ignoreRecord: true)
                            ->maxLength(13),
                        TextInput::make('qr_code')
                            ->label('قيمة QR Code')
                            ->placeholder('تُولّد تلقائياً')
                            ->unique(ignoreRecord: true),
                        Toggle::make('track_stock')
                            ->label('تتبع المخزون')
                            ->default(true),
                        Toggle::make('send_to_kitchen')
                            ->label('إرسال إلى طابعة المطبخ')
                            ->helperText('فعّلها للأطعمة والمشروبات التي تحتاج أمر تجهيز')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make('متغيرات المنتج')
                    ->description('أنشئ تركيبات اللون والمقاس والوزن مع باركود ومخزون مستقل لكل تركيبة')
                    ->icon('heroicon-o-swatch')
                    ->visible(fn (Get $get): bool => $get('type') === Product::TYPE_VARIABLE)
                    ->schema([
                        Repeater::make('variants')
                            ->label('')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم المتغير')
                                    ->placeholder('أحمر / XL')
                                    ->required(),
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->placeholder('تلقائي'),
                                TextInput::make('color')
                                    ->label('اللون'),
                                TextInput::make('color_hex')
                                    ->label('كود اللون')
                                    ->type('color'),
                                TextInput::make('size')
                                    ->label('المقاس'),
                                TextInput::make('weight')
                                    ->label('الوزن')
                                    ->numeric()
                                    ->suffix('كجم'),
                                TextInput::make('purchase_price')
                                    ->label('سعر الشراء')
                                    ->numeric()
                                    ->prefix('ر.س'),
                                TextInput::make('average_cost')
                                    ->label('متوسط التكلفة')
                                    ->numeric()
                                    ->prefix('ر.س'),
                                TextInput::make('selling_price')
                                    ->label('سعر البيع')
                                    ->numeric()
                                    ->prefix('ر.س'),
                                TextInput::make('stock_quantity')
                                    ->label('المخزون')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('barcode')
                                    ->label('الباركود')
                                    ->placeholder('تلقائي'),
                                TextInput::make('qr_code')
                                    ->label('QR Code')
                                    ->placeholder('تلقائي'),
                                KeyValue::make('attributes')
                                    ->label('خصائص إضافية')
                                    ->keyLabel('الخاصية')
                                    ->valueLabel('القيمة')
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة متغير')
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'متغير جديد')
                            ->collapsible()
                            ->cloneable(),
                    ]),
            ]);
    }
}
