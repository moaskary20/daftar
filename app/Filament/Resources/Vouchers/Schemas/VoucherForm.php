<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\Voucher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم السند')
                    ->placeholder('يُولّد تلقائياً'),
                Select::make('type')
                    ->label('نوع السند')
                    ->options(Voucher::typeLabels())
                    ->required()
                    ->live(),
                Select::make('party_type')
                    ->label('نوع الطرف')
                    ->options([
                        Customer::class => 'عميل',
                        Supplier::class => 'مورد',
                    ])
                    ->live(),
                Select::make('party_id')
                    ->label('الطرف')
                    ->options(fn (Get $get): array => match ($get('party_type')) {
                        Customer::class => Customer::query()->pluck('name', 'id')->all(),
                        Supplier::class => Supplier::query()->pluck('name', 'id')->all(),
                        default => [],
                    })
                    ->searchable(),
                Select::make('fund_type')
                    ->label('نوع الحساب المالي')
                    ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                    ->required()
                    ->live(),
                Select::make('fund_id')
                    ->label('الحساب المالي')
                    ->options(fn (Get $get): array => match ($get('fund_type')) {
                        'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        default => [],
                    })
                    ->required(),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('ج.م'),
                DatePicker::make('voucher_date')
                    ->label('التاريخ')
                    ->default(today())
                    ->required(),
                Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options(['cash' => 'نقدي', 'bank' => 'تحويل بنكي', 'check' => 'شيك'])
                    ->default('cash')
                    ->required(),
                Select::make('bank_check_id')
                    ->label('الشيك')
                    ->relationship('check', 'check_number')
                    ->visible(fn (Get $get): bool => $get('payment_method') === 'check'),
                Select::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'posted' => 'مرحّل'])
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('description')
                    ->label('البيان')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
