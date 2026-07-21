<?php

namespace App\Filament\Pages\Concerns;

use App\Services\DataTransferService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

trait ExportsReportData
{
    /**
     * @return array<ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportReportXlsx')
                    ->label('تصدير Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn () => app(DataTransferService::class)->exportRows(
                        $this->getReportExportRows(),
                        'xlsx',
                        $this->getReportExportFileName(),
                    )),
                Action::make('exportReportCsv')
                    ->label('تصدير CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->action(fn () => app(DataTransferService::class)->exportRows(
                        $this->getReportExportRows(),
                        'csv',
                        $this->getReportExportFileName(),
                    )),
            ])
                ->label('تصدير التقرير')
                ->icon('heroicon-o-arrow-down-tray')
                ->button()
                ->color('primary'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function getReportExportRows(): array;

    abstract protected function getReportExportFileName(): string;
}
