<?php

namespace App\Filament\Pages;

use App\Models\BackupRecord;
use App\Services\BackupService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class BackupManager extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?string $navigationLabel = 'النسخ الاحتياطية';

    protected static ?string $title = 'إدارة النسخ الاحتياطية';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.backup-manager';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('backups', 'view');
    }

    public function createBackup(): void
    {
        abort_unless(auth()->user()->hasRole('manager') || auth()->user()->hasPermission('backups', 'create'), 403);
        $record = app(BackupService::class)->create();
        Notification::make()
            ->title('اكتملت النسخة الاحتياطية')
            ->body($record->filename)
            ->success()
            ->send();
    }

    public function deleteBackup(int $id): void
    {
        abort_unless(auth()->user()->hasRole('manager') || auth()->user()->hasPermission('backups', 'delete'), 403);
        app(BackupService::class)->delete(BackupRecord::query()->findOrFail($id));
    }

    public function getBackupsProperty()
    {
        return BackupRecord::query()->latest()->get();
    }
}
