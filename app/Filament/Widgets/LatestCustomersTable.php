<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestCustomersTable extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = ['lg' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('أحدث العملاء')
            ->query(fn (): Builder => Customer::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('العميل')
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('-'),
                TextColumn::make('current_balance')
                    ->label('الرصيد')
                    ->money('EGP')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->since(),
            ])
            ->emptyStateHeading('لا يوجد عملاء بعد')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
