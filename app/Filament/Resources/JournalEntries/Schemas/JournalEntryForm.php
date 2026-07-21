<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم القيد')
                    ->placeholder('يُولّد تلقائياً'),
                DatePicker::make('entry_date')
                    ->label('تاريخ القيد')
                    ->default(today())
                    ->required(),
                Select::make('entry_type')
                    ->label('نوع القيد')
                    ->options(['manual' => 'يدوي', 'automatic' => 'تلقائي'])
                    ->default('manual')
                    ->disabled()
                    ->dehydrated(),
                Select::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'posted' => 'مرحّل', 'reversed' => 'معكوس'])
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('reference')->label('المرجع'),
                Textarea::make('description')
                    ->label('البيان')
                    ->required()
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->label('أطراف القيد')
                    ->relationship()
                    ->schema([
                        Select::make('chart_account_id')
                            ->label('الحساب')
                            ->relationship('account', 'name', fn ($query) => $query->where('allow_posting', true)->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('debit')->label('مدين')->numeric()->default(0)->prefix('ج.م'),
                        TextInput::make('credit')->label('دائن')->numeric()->default(0)->prefix('ج.م'),
                        TextInput::make('cost_center')->label('مركز التكلفة'),
                        TextInput::make('description')->label('بيان السطر'),
                    ])
                    ->columns(5)
                    ->minItems(2)
                    ->defaultItems(2)
                    ->addActionLabel('إضافة طرف'),
                TextInput::make('total_debit')->label('إجمالي المدين')->disabled()->prefix('ج.م'),
                TextInput::make('total_credit')->label('إجمالي الدائن')->disabled()->prefix('ج.م'),
            ]);
    }
}
