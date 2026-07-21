<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('ترحيل القيد')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === JournalEntry::STATUS_DRAFT)
                ->action(function (): void {
                    app(AccountingService::class)->postEntry($this->record->fresh('lines.account'));
                    $this->redirect(JournalEntryResource::getUrl('view', ['record' => $this->record]));
                }),
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف')->visible(fn (): bool => $this->record->status === JournalEntry::STATUS_DRAFT),
        ];
    }
}
