<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use App\Services\AccountingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialTransaction extends EditRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('اعتماد وترحيل')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'draft')
                ->action(function (): void {
                    app(AccountingService::class)->postFinancialTransaction($this->record);
                    $this->redirect(FinancialTransactionResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف')->visible(fn (): bool => $this->record->status === 'draft'),
        ];
    }
}
