<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'الموردون';

    protected static ?string $modelLabel = 'مورد';

    protected static ?string $pluralModelLabel = 'الموردون';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المورد')
                    ->required(),
                TextInput::make('code')
                    ->label('كود المورد')
                    ->unique(ignoreRecord: true),
                TextInput::make('contact_person')
                    ->label('مسؤول التواصل'),
                TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                Textarea::make('address')
                    ->label('العنوان')
                    ->columnSpanFull(),
                TextInput::make('tax_number')
                    ->label('الرقم الضريبي'),
                TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م')
                    ->disabledOn('edit'),
                TextInput::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->numeric()
                    ->prefix('ج.م')
                    ->disabled(),
                TextInput::make('credit_limit')
                    ->label('حد الائتمان')
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م'),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('المورد')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->label('مسؤول التواصل')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('tax_number')
                    ->label('الرقم الضريبي')
                    ->searchable(),
                TextColumn::make('current_balance')
                    ->label('الرصيد المستحق')
                    ->money('EGP')
                    ->weight('bold')
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->label('حد الائتمان')
                    ->money('EGP')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
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
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
