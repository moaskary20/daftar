<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\ManageAttendances;
use App\Models\Attendance;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'الموارد البشرية';

    protected static ?string $navigationLabel = 'الحضور والانصراف';

    protected static ?string $modelLabel = 'حضور';

    protected static ?string $pluralModelLabel = 'الحضور والانصراف';

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
                DatePicker::make('attendance_date')
                    ->label('التاريخ')
                    ->default(today())
                    ->required(),
                DateTimePicker::make('check_in')->label('وقت الحضور'),
                DateTimePicker::make('check_out')->label('وقت الانصراف'),
                Select::make('status')
                    ->label('الحالة')
                    ->options(Attendance::statusLabels())
                    ->required()
                    ->default('present'),
                TextInput::make('late_minutes')
                    ->label('دقائق التأخير')
                    ->numeric()
                    ->default(0),
                TextInput::make('overtime_hours')
                    ->label('ساعات إضافية')
                    ->numeric()
                    ->default(0),
                Select::make('source')
                    ->label('المصدر')
                    ->options(['manual' => 'يدوي', 'fingerprint' => 'بصمة'])
                    ->default('manual'),
                Textarea::make('notes')
                    ->label('ملاحظات')
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
                TextColumn::make('attendance_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('الحضور')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('الانصراف')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state): string => Attendance::statusLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('late_minutes')
                    ->label('التأخير')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_hours')
                    ->label('الإضافي')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('المصدر')
                    ->searchable(),
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
            'index' => ManageAttendances::route('/'),
        ];
    }
}
