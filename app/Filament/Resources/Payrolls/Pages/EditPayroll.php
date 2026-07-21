<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('احتساب الرواتب')
                ->icon('heroicon-o-calculator')
                ->visible(fn (): bool => $this->record->status === 'draft')
                ->action(function (): void {
                    app(PayrollService::class)->generate($this->record);
                    $this->refreshFormData(['total_earnings', 'total_deductions', 'net_total']);
                }),
            Action::make('post')
                ->label('صرف وترحيل الرواتب')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'draft' && $this->record->items()->exists())
                ->action(function (): void {
                    app(PayrollService::class)->post($this->record);
                    $this->redirect(PayrollResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف')->visible(fn (): bool => $this->record->status === 'draft'),
        ];
    }
}
