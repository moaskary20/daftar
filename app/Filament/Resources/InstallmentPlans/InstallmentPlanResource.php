<?php

namespace App\Filament\Resources\InstallmentPlans;

use App\Filament\Resources\InstallmentPlans\Pages\CreateInstallmentPlan;
use App\Filament\Resources\InstallmentPlans\Pages\EditInstallmentPlan;
use App\Filament\Resources\InstallmentPlans\Pages\ListInstallmentPlans;
use App\Filament\Resources\InstallmentPlans\Pages\ViewInstallmentPlan;
use App\Filament\Resources\InstallmentPlans\Schemas\InstallmentPlanForm;
use App\Filament\Resources\InstallmentPlans\Schemas\InstallmentPlanInfolist;
use App\Filament\Resources\InstallmentPlans\Tables\InstallmentPlansTable;
use App\Models\InstallmentPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstallmentPlanResource extends Resource
{
    protected static ?string $model = InstallmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'نقطة البيع POS';

    protected static ?string $navigationLabel = 'خطط التقسيط';

    protected static ?string $modelLabel = 'خطة تقسيط';

    protected static ?string $pluralModelLabel = 'خطط التقسيط';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InstallmentPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstallmentPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallmentPlans::route('/'),
            'create' => CreateInstallmentPlan::route('/create'),
            'view' => ViewInstallmentPlan::route('/{record}'),
            'edit' => EditInstallmentPlan::route('/{record}/edit'),
        ];
    }
}
