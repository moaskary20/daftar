<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الدور')
                    ->required(),
                TextInput::make('slug')
                    ->label('المعرّف')
                    ->helperText('يُستخدم داخلياً، مثل: sales_manager')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull(),
                Toggle::make('is_system')
                    ->label('دور نظامي')
                    ->disabled()
                    ->dehydrated(),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                CheckboxList::make('permissions')
                    ->label('الصلاحيات')
                    ->relationship('permissions', 'name')
                    ->options(fn (): array => Permission::query()
                        ->orderBy('resource')
                        ->orderBy('action')
                        ->pluck('name', 'id')
                        ->all())
                    ->bulkToggleable()
                    ->searchable()
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}
