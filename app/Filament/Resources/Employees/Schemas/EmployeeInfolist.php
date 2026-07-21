<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee_number'),
                TextEntry::make('name'),
                TextEntry::make('job_title')
                    ->placeholder('-'),
                TextEntry::make('department_name')
                    ->placeholder('-'),
                TextEntry::make('national_id')
                    ->placeholder('-'),
                TextEntry::make('fingerprint_id')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('hire_date')
                    ->date(),
                TextEntry::make('basic_salary')
                    ->numeric(),
                TextEntry::make('fixed_allowances')
                    ->numeric(),
                TextEntry::make('bank_name')
                    ->placeholder('-'),
                TextEntry::make('iban')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Employee $record): bool => $record->trashed()),
            ]);
    }
}
