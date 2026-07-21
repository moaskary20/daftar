<?php

namespace App\Filament\Resources\PosTerminals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PosTerminalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('treasury_id')
                    ->label('الخزينة النقدية')
                    ->relationship('treasury', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('اسم نقطة البيع')
                    ->required(),
                TextInput::make('code')
                    ->label('الكود')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('receipt_size')
                    ->label('مقاس الإيصال')
                    ->options(['80mm' => 'حراري 80mm', '58mm' => 'حراري 58mm', 'a4' => 'A4'])
                    ->required()
                    ->default('80mm'),
                TextInput::make('printer_name')->label('اسم طابعة الفواتير'),
                TextInput::make('kitchen_printer_name')->label('اسم طابعة المطبخ'),
                Toggle::make('cash_drawer_enabled')
                    ->label('فتح درج الكاشير')
                    ->default(false),
                Toggle::make('customer_display_enabled')
                    ->label('شاشة العميل')
                    ->default(false),
                Toggle::make('scale_enabled')
                    ->label('الميزان الإلكتروني')
                    ->default(false),
                Toggle::make('offline_enabled')
                    ->label('العمل دون إنترنت')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('نشطة')
                    ->default(true),
            ]);
    }
}
