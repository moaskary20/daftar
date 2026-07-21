<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('كود الكوبون')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الكوبون')
                    ->required(),
                Select::make('discount_type')
                    ->label('نوع الخصم')
                    ->options(['fixed' => 'مبلغ ثابت', 'percentage' => 'نسبة مئوية'])
                    ->required()
                    ->default('fixed'),
                TextInput::make('value')
                    ->label('قيمة الخصم')
                    ->required()
                    ->numeric(),
                TextInput::make('minimum_total')
                    ->label('الحد الأدنى للفاتورة')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('maximum_discount')
                    ->label('أقصى خصم')
                    ->numeric(),
                TextInput::make('usage_limit')
                    ->label('حد مرات الاستخدام')
                    ->numeric(),
                TextInput::make('usage_count')
                    ->label('مرات الاستخدام')
                    ->disabled()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('starts_at')->label('يبدأ في'),
                DateTimePicker::make('ends_at')->label('ينتهي في')->after('starts_at'),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }
}
