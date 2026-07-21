<?php

namespace App\Filament\Resources\ChartAccounts\Schemas;

use App\Models\ChartAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChartAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('الحساب الأب')
                    ->relationship('parent', 'name')
                    ->getOptionLabelFromRecordUsing(fn (ChartAccount $record): string => "{$record->code} - {$record->name}")
                    ->searchable()
                    ->preload(),
                TextInput::make('code')
                    ->label('كود الحساب')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الحساب')
                    ->required(),
                Select::make('type')
                    ->label('نوع الحساب')
                    ->options(ChartAccount::typeLabels())
                    ->required(),
                Select::make('normal_balance')
                    ->label('طبيعة الرصيد')
                    ->options(['debit' => 'مدين', 'credit' => 'دائن'])
                    ->required(),
                Toggle::make('is_group')
                    ->label('حساب تجميعي'),
                Toggle::make('allow_posting')
                    ->label('يسمح بالترحيل')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                TextInput::make('opening_debit')
                    ->label('رصيد افتتاحي مدين')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),
                TextInput::make('opening_credit')
                    ->label('رصيد افتتاحي دائن')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }
}
