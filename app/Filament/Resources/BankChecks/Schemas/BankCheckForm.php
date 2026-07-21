<?php

namespace App\Filament\Resources\BankChecks\Schemas;

use App\Models\BankCheck;
use App\Models\Customer;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BankCheckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bank_account_id')
                    ->label('الحساب البنكي')
                    ->relationship('bankAccount', 'name'),
                Select::make('party_type')
                    ->label('نوع الطرف')
                    ->options([Customer::class => 'عميل', Supplier::class => 'مورد'])
                    ->live(),
                Select::make('party_id')
                    ->label('الطرف')
                    ->options(fn (Get $get): array => match ($get('party_type')) {
                        Customer::class => Customer::query()->pluck('name', 'id')->all(),
                        Supplier::class => Supplier::query()->pluck('name', 'id')->all(),
                        default => [],
                    })
                    ->searchable(),
                TextInput::make('number')
                    ->label('رقم السجل')
                    ->placeholder('يُولّد تلقائياً'),
                TextInput::make('check_number')
                    ->label('رقم الشيك')
                    ->required(),
                Select::make('direction')
                    ->label('اتجاه الشيك')
                    ->options(['incoming' => 'وارد', 'outgoing' => 'صادر'])
                    ->required(),
                TextInput::make('bank_name')->label('البنك'),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->prefix('ج.م'),
                DatePicker::make('issue_date')
                    ->label('تاريخ الإصدار')
                    ->default(today())
                    ->required(),
                DatePicker::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options(BankCheck::statusLabels())
                    ->default('pending')
                    ->required(),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }
}
