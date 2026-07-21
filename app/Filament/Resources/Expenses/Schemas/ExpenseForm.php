<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Treasury;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expense_category_id')
                    ->label('فئة المصروف')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('employee_id')
                    ->label('الموظف المرتبط')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('number')
                    ->label('رقم المصروف')
                    ->placeholder('يُولّد تلقائياً'),
                Select::make('expense_type')
                    ->label('نوع المصروف')
                    ->options(Expense::typeLabels())
                    ->default('general')
                    ->required(),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('ج.م'),
                DatePicker::make('expense_date')
                    ->label('تاريخ المصروف')
                    ->default(today())
                    ->required(),
                Select::make('payment_fund_type')
                    ->label('نوع حساب الدفع')
                    ->options(['treasury' => 'خزينة', 'bank' => 'حساب بنكي'])
                    ->required()
                    ->live(),
                Select::make('payment_fund_id')
                    ->label('حساب الدفع')
                    ->options(fn (Get $get): array => match ($get('payment_fund_type')) {
                        'treasury' => Treasury::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        'bank' => BankAccount::query()->where('is_active', true)->pluck('name', 'id')->all(),
                        default => [],
                    })
                    ->required(),
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
