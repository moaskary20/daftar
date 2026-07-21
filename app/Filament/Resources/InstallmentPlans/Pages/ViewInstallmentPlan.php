<?php

namespace App\Filament\Resources\InstallmentPlans\Pages;

use App\Filament\Resources\InstallmentPlans\InstallmentPlanResource;
use App\Models\PosPayment;
use App\Services\PosService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallmentPlan extends ViewRecord
{
    protected static string $resource = InstallmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('collect')
                ->label('تحصيل القسط التالي')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->form([
                    Select::make('method')
                        ->label('وسيلة الدفع')
                        ->options(PosPayment::methodLabels())
                        ->default(PosPayment::METHOD_CASH)
                        ->required(),
                    TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required()
                        ->default(fn () => $this->record->installments()
                            ->where('status', '!=', 'paid')
                            ->orderBy('sequence')
                            ->value('amount')),
                ])
                ->action(function (array $data): void {
                    $installment = $this->record->installments()
                        ->where('status', '!=', 'paid')
                        ->orderBy('sequence')
                        ->firstOrFail();
                    app(PosService::class)->collectInstallment(
                        $installment,
                        (float) $data['amount'],
                        $data['method'],
                    );
                    Notification::make()->title('تم تحصيل القسط')->success()->send();
                    $this->refreshFormData([]);
                }),
        ];
    }
}
