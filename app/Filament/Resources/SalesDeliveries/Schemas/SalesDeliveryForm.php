<?php

namespace App\Filament\Resources\SalesDeliveries\Schemas;

use App\Models\SalesDelivery;
use App\Models\SalesDocument;
use App\Models\SalesDocumentItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SalesDeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التسليم')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextInput::make('number')->label('رقم التسليم')->placeholder('يُولّد تلقائياً'),
                        Select::make('sales_document_id')
                            ->label('أمر البيع')
                            ->relationship(
                                'document',
                                'number',
                                fn ($query) => $query
                                    ->where('type', SalesDocument::TYPE_ORDER)
                                    ->whereNotIn('status', [SalesDocument::STATUS_DELIVERED, SalesDocument::STATUS_CANCELLED]),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('warehouse_id')
                            ->label('مخزن الصرف')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('الحالة')
                            ->options(SalesDelivery::statusLabels())
                            ->default(SalesDelivery::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated(),
                        DatePicker::make('delivery_date')->label('تاريخ التسليم')->default(today())->required(),
                        Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('الكميات المسلّمة')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Select::make('sales_document_item_id')
                                    ->label('الصنف')
                                    ->options(function (Get $get): array {
                                        $documentId = $get('../../sales_document_id');

                                        if (! $documentId) {
                                            return [];
                                        }

                                        return SalesDocumentItem::query()
                                            ->with(['product', 'variant'])
                                            ->where('sales_document_id', $documentId)
                                            ->get()
                                            ->filter(fn (SalesDocumentItem $item): bool => $item->remaining_quantity > 0)
                                            ->mapWithKeys(fn (SalesDocumentItem $item): array => [
                                                $item->id => $item->product->name
                                                    .($item->variant ? ' - '.$item->variant->name : '')
                                                    .' (متبقي: '.$item->remaining_quantity.')',
                                            ])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('كمية التسليم')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة صنف للتسليم'),
                    ]),
            ]);
    }
}
