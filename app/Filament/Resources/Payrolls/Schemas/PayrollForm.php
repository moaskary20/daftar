<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Models\BankAccount;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم المسير')
                    ->placeholder('يُولّد تلقائياً'),
                TextInput::make('period_month')
                    ->label('شهر الرواتب')
                    ->type('month')
                    ->placeholder('YYYY-MM')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'posted' => 'مرحّل'])
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('total_earnings')
                    ->label('إجمالي الاستحقاقات')
                    ->prefix('ج.م')
                    ->disabled(),
                TextInput::make('total_deductions')
                    ->label('إجمالي الاستقطاعات')
                    ->prefix('ج.م')
                    ->disabled(),
                TextInput::make('net_total')
                    ->label('صافي الرواتب')
                    ->prefix('ج.م')
                    ->disabled(),
                DatePicker::make('payment_date')->label('تاريخ الصرف'),
                Select::make('payment_fund_type')
                    ->label('نوع حساب الصرف')
                    ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                    ->live(),
                Select::make('payment_fund_id')
                    ->label('حساب صرف الرواتب')
                    ->options(fn (Get $get): array => match ($get('payment_fund_type')) {
                        'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        default => [],
                    }),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }
}
