<?php

namespace App\Filament\Resources\CustomerTransactions\Schemas;

use App\Models\BankAccount;
use App\Models\CustomerTransaction;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('دفعة أو تسوية عميل')
                    ->schema([
                        Select::make('customer_id')
                            ->label('العميل')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('نوع الحركة')
                            ->options([
                                CustomerTransaction::TYPE_PAYMENT => 'تحصيل دفعة من العميل',
                                CustomerTransaction::TYPE_ADJUSTMENT => 'تسوية حساب',
                            ])
                            ->default(CustomerTransaction::TYPE_PAYMENT)
                            ->required()
                            ->live(),
                        TextInput::make('credit')
                            ->label(fn (Get $get): string => $get('type') === CustomerTransaction::TYPE_PAYMENT ? 'المبلغ المحصل' : 'دائن')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->required(),
                        TextInput::make('debit')
                            ->label('مدين')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->visible(fn (Get $get): bool => $get('type') === CustomerTransaction::TYPE_ADJUSTMENT),
                        DatePicker::make('transaction_date')->label('التاريخ')->default(today())->required(),
                        Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'نقدي',
                                'bank' => 'تحويل بنكي',
                                'card' => 'بطاقة',
                                'check' => 'شيك',
                            ]),
                        Select::make('fund_type')
                            ->label('نوع حساب التحصيل')
                            ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                            ->visible(fn (Get $get): bool => $get('type') === CustomerTransaction::TYPE_PAYMENT)
                            ->required(fn (Get $get): bool => $get('type') === CustomerTransaction::TYPE_PAYMENT)
                            ->live(),
                        Select::make('fund_id')
                            ->label('حساب التحصيل')
                            ->options(fn (Get $get): array => match ($get('fund_type')) {
                                'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
                                'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
                                default => [],
                            })
                            ->visible(fn (Get $get): bool => $get('type') === CustomerTransaction::TYPE_PAYMENT)
                            ->required(fn (Get $get): bool => $get('type') === CustomerTransaction::TYPE_PAYMENT),
                        Textarea::make('notes')->label('البيان')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
