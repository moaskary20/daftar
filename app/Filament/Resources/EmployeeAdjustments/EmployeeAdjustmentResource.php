<?php

namespace App\Filament\Resources\EmployeeAdjustments;

use App\Filament\Resources\EmployeeAdjustments\Pages\ManageEmployeeAdjustments;
use App\Models\EmployeeAdjustment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeAdjustmentResource extends Resource
{
    protected static ?string $model = EmployeeAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'الموارد البشرية';

    protected static ?string $navigationLabel = 'السلف والجزاءات والحوافز';

    protected static ?string $modelLabel = 'تسوية موظف';

    protected static ?string $pluralModelLabel = 'السلف والجزاءات والحوافز';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('الموظف')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label('النوع')
                    ->options(EmployeeAdjustment::typeLabels())
                    ->required(),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->prefix('ج.م'),
                DatePicker::make('adjustment_date')
                    ->label('التاريخ')
                    ->default(today())
                    ->required(),
                TextInput::make('installments')
                    ->label('عدد الأقساط')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('settled_amount')
                    ->label('المبلغ المسوّى')
                    ->numeric()
                    ->disabled()
                    ->default(0),
                Select::make('status')
                    ->label('الحالة')
                    ->options(['pending' => 'معلق', 'partial' => 'مسدد جزئياً', 'settled' => 'مسدد'])
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(),
                Textarea::make('reason')
                    ->label('السبب')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state): string => EmployeeAdjustment::typeLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('adjustment_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('installments')
                    ->label('الأقساط')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('settled_amount')
                    ->label('المسوّى')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployeeAdjustments::route('/'),
        ];
    }
}
