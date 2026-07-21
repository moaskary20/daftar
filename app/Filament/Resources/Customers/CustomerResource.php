<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء';

    protected static ?string $navigationLabel = 'العملاء';

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم العميل')
                    ->required(),
                TextInput::make('code')->label('كود العميل')->unique(ignoreRecord: true),
                TextInput::make('contact_person')->label('مسؤول التواصل'),
                TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                Textarea::make('address')
                    ->label('العنوان')
                    ->columnSpanFull(),
                TextInput::make('tax_number')->label('الرقم الضريبي'),
                TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م')
                    ->disabledOn('edit'),
                TextInput::make('current_balance')
                    ->label('المديونية الحالية')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م')
                    ->disabled(),
                TextInput::make('credit_limit')
                    ->label('حد الائتمان')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('ج.م'),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('العميل')
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
                TextColumn::make('opening_balance')
                    ->label('رصيد افتتاحي')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('current_balance')
                    ->label('المديونية')
                    ->money('EGP')
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success')
                    ->weight('bold')
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
