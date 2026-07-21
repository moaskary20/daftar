<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestSuppliersTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = ['lg' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('أحدث الموردين')
            ->query(fn (): Builder => Supplier::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('المورد')
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('-'),
                TextColumn::make('current_balance')
                    ->label('الرصيد')
                    ->money('EGP')
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'success'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->since(),
            ])
            ->emptyStateHeading('لا يوجد موردون بعد')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
