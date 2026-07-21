<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('منتج محدد')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('category_id')
                    ->label('أو تصنيف كامل')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('اسم العرض')
                    ->required(),
                Select::make('discount_type')
                    ->label('نوع الخصم')
                    ->options(['fixed' => 'مبلغ ثابت', 'percentage' => 'نسبة مئوية'])
                    ->required()
                    ->default('percentage'),
                TextInput::make('value')
                    ->label('قيمة الخصم')
                    ->required()
                    ->numeric(),
                TextInput::make('minimum_quantity')
                    ->label('أقل كمية')
                    ->required()
                    ->numeric()
                    ->default(1),
                DateTimePicker::make('starts_at')->label('يبدأ في'),
                DateTimePicker::make('ends_at')->label('ينتهي في')->after('starts_at'),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }
}
