<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بطاقة المنتج')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        ImageEntry::make('image_url')
                            ->label('الصورة')
                            ->square()
                            ->defaultImageUrl(fn (Product $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=5458f0&color=fff'),
                        TextEntry::make('name')
                            ->label('اسم المنتج')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->copyable(),
                        TextEntry::make('type')
                            ->label('النوع')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === Product::TYPE_VARIABLE ? 'متغير' : 'بسيط'),
                        TextEntry::make('barcode')
                            ->label('الباركود')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('qr_code')
                            ->label('QR Code')
                            ->copyable(),
                        TextEntry::make('description')
                            ->label('الوصف')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('التصنيف والتوريد')
                    ->schema([
                        TextEntry::make('category.name')->label('التصنيف')->placeholder('—'),
                        TextEntry::make('department.name')->label('القسم')->placeholder('—'),
                        TextEntry::make('brand.name')->label('الماركة')->placeholder('—'),
                        TextEntry::make('unit.name')->label('الوحدة')->placeholder('—'),
                        TextEntry::make('primarySupplier.name')->label('المورد الرئيسي')->placeholder('—'),
                    ])
                    ->columns(3),
                Section::make('الأسعار والمخزون')
                    ->schema([
                        TextEntry::make('purchase_price')->label('سعر الشراء')->money('SAR'),
                        TextEntry::make('average_cost')->label('متوسط التكلفة')->money('SAR'),
                        TextEntry::make('selling_price')->label('سعر البيع')->money('SAR'),
                        TextEntry::make('stock_quantity')->label('المخزون')->numeric(),
                        TextEntry::make('minimum_stock')->label('حد إعادة الطلب')->numeric(),
                        TextEntry::make('weight')->label('الوزن')->suffix(' كجم')->placeholder('—'),
                        IconEntry::make('track_stock')->label('تتبع المخزون')->boolean(),
                        IconEntry::make('is_active')->label('نشط')->boolean(),
                    ])
                    ->columns(4),
                Section::make('أسعار البيع الإضافية')
                    ->schema([
                        RepeatableEntry::make('prices')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label('اسم السعر'),
                                TextEntry::make('price')->label('السعر')->money('SAR'),
                                TextEntry::make('minimum_quantity')->label('أقل كمية')->numeric(),
                                IconEntry::make('is_active')->label('نشط')->boolean(),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible(),
                Section::make('المتغيرات')
                    ->visible(fn (Product $record): bool => $record->type === Product::TYPE_VARIABLE)
                    ->schema([
                        RepeatableEntry::make('variants')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label('المتغير')->weight('bold'),
                                TextEntry::make('color')->label('اللون')->placeholder('—'),
                                TextEntry::make('size')->label('المقاس')->placeholder('—'),
                                TextEntry::make('weight')->label('الوزن')->suffix(' كجم')->placeholder('—'),
                                TextEntry::make('barcode')->label('الباركود')->copyable(),
                                TextEntry::make('selling_price')->label('سعر البيع')->money('SAR'),
                                TextEntry::make('stock_quantity')->label('المخزون')->numeric(),
                                IconEntry::make('is_active')->label('نشط')->boolean(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
