<?php

namespace App\Filament\Resources\SupplierTransactions\Schemas;

use App\Models\BankAccount;
use App\Models\SupplierTransaction;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SupplierTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('دفعة أو شيك مورد')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('نوع الحركة')
                            ->options([
                                SupplierTransaction::TYPE_PAYMENT => 'دفعة مورد',
                                SupplierTransaction::TYPE_CHECK => 'شيك مورد',
                                SupplierTransaction::TYPE_ADJUSTMENT => 'تسوية حساب',
                            ])
                            ->default(SupplierTransaction::TYPE_PAYMENT)
                            ->required()
                            ->live(),
                        TextInput::make('debit')
                            ->label(fn (Get $get): string => in_array($get('type'), [
                                SupplierTransaction::TYPE_PAYMENT,
                                SupplierTransaction::TYPE_CHECK,
                            ], true) ? 'المبلغ المدفوع' : 'مدين')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->required(),
                        TextInput::make('credit')
                            ->label('دائن')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->visible(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_ADJUSTMENT),
                        DatePicker::make('transaction_date')->label('التاريخ')->default(today())->required(),
                        Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options(['cash' => 'نقدي', 'bank' => 'تحويل بنكي', 'card' => 'بطاقة', 'check' => 'شيك']),
                        Select::make('fund_type')
                            ->label('نوع حساب الدفع')
                            ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                            ->visible(fn (Get $get): bool => in_array($get('type'), [
                                SupplierTransaction::TYPE_PAYMENT,
                                SupplierTransaction::TYPE_CHECK,
                            ], true))
                            ->required()
                            ->live(),
                        Select::make('fund_id')
                            ->label('حساب الدفع')
                            ->options(fn (Get $get): array => match ($get('fund_type')) {
                                'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
                                'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
                                default => [],
                            })
                            ->visible(fn (Get $get): bool => in_array($get('type'), [
                                SupplierTransaction::TYPE_PAYMENT,
                                SupplierTransaction::TYPE_CHECK,
                            ], true))
                            ->required(),
                        TextInput::make('check_number')
                            ->label('رقم الشيك')
                            ->required(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_CHECK)
                            ->visible(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_CHECK),
                        DatePicker::make('check_due_date')
                            ->label('تاريخ استحقاق الشيك')
                            ->visible(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_CHECK),
                        TextInput::make('bank_name')
                            ->label('البنك')
                            ->visible(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_CHECK),
                        Select::make('check_status')
                            ->label('حالة الشيك')
                            ->options(SupplierTransaction::checkStatusLabels())
                            ->default('pending')
                            ->visible(fn (Get $get): bool => $get('type') === SupplierTransaction::TYPE_CHECK),
                        Textarea::make('notes')->label('البيان')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
