<?php

namespace App\Filament\Resources\Treasuries;

use App\Filament\Resources\Treasuries\Pages\ManageTreasuries;
use App\Models\Treasury;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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

class TreasuryResource extends Resource
{
    protected static ?string $model = Treasury::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'الحسابات';

    protected static ?string $navigationLabel = 'الخزائن';

    protected static ?string $modelLabel = 'خزينة';

    protected static ?string $pluralModelLabel = 'الخزائن';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('chart_account_id')
                    ->label('الحساب المحاسبي')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الخزينة')
                    ->required(),
                TextInput::make('code')
                    ->label('الكود')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('opening_balance')
                    ->label('الرصيد الافتتاحي')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),
                TextInput::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->numeric()
                    ->prefix('ج.م')
                    ->disabled()
                    ->default(0),
                Toggle::make('is_default')
                    ->label('الخزينة الافتراضية'),
                Toggle::make('is_active')
                    ->label('نشطة')
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
                    ->label('الخزينة')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable(),
                TextColumn::make('opening_balance')
                    ->label('رصيد افتتاحي')
                    ->money('EGP')
                    ->sortable(),
                TextColumn::make('current_balance')
                    ->label('الرصيد الحالي')
                    ->money('EGP')
                    ->weight('bold')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('افتراضية')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('نشطة')
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
            'index' => ManageTreasuries::route('/'),
        ];
    }
}
