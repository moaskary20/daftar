<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use App\Models\BankAccount;
use App\Models\FinancialTransaction;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم الحركة')
                    ->placeholder('يُولّد تلقائياً'),
                Select::make('type')
                    ->label('نوع الحركة')
                    ->options(FinancialTransaction::typeLabels())
                    ->required()
                    ->live(),
                Select::make('source_fund_type')
                    ->label('نوع حساب المصدر')
                    ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['withdrawal', 'transfer'], true))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['withdrawal', 'transfer'], true))
                    ->live(),
                Select::make('source_fund_id')
                    ->label('حساب المصدر')
                    ->options(fn (Get $get): array => self::fundOptions($get('source_fund_type')))
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['withdrawal', 'transfer'], true))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['withdrawal', 'transfer'], true)),
                Select::make('destination_fund_type')
                    ->label('نوع حساب الوجهة')
                    ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['deposit', 'transfer'], true))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['deposit', 'transfer'], true))
                    ->live(),
                Select::make('destination_fund_id')
                    ->label('حساب الوجهة')
                    ->options(fn (Get $get): array => self::fundOptions($get('destination_fund_type')))
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['deposit', 'transfer'], true))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['deposit', 'transfer'], true)),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('ج.م'),
                DatePicker::make('transaction_date')
                    ->label('التاريخ')
                    ->default(today())
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'posted' => 'مرحّل'])
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('beneficiary')->label('المستفيد / المودع'),
                Textarea::make('description')
                    ->label('البيان')
                    ->columnSpanFull(),
            ]);
    }

    private static function fundOptions(?string $type): array
    {
        return match ($type) {
            'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
            'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
            default => [],
        };
    }
}
