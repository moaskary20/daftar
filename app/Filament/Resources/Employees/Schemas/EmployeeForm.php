<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_number')
                    ->label('الرقم الوظيفي')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الموظف')
                    ->required(),
                TextInput::make('job_title')->label('المسمى الوظيفي'),
                TextInput::make('department_name')->label('القسم'),
                TextInput::make('national_id')->label('رقم الهوية')->unique(ignoreRecord: true),
                TextInput::make('fingerprint_id')->label('رقم البصمة')->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                Textarea::make('address')
                    ->label('العنوان')
                    ->columnSpanFull(),
                DatePicker::make('hire_date')
                    ->label('تاريخ التعيين')
                    ->default(today())
                    ->required(),
                TextInput::make('basic_salary')
                    ->label('الراتب الأساسي')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),
                TextInput::make('fixed_allowances')
                    ->label('البدلات الثابتة')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),
                TextInput::make('bank_name')->label('البنك'),
                TextInput::make('iban')->label('IBAN'),
                Toggle::make('is_active')
                    ->label('على رأس العمل')
                    ->default(true),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }
}
