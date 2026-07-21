<?php

namespace App\Filament\Pages;

use App\Models\SystemNotification;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class NotificationCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'النظام';

    protected static ?string $navigationLabel = 'الإشعارات';

    protected static ?string $title = 'مركز الإشعارات';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.notification-center';

    public string $filter = 'unread';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('manager') || $user?->hasPermission('notifications', 'view');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SystemNotification::query()->whereNull('read_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public function generate(): void
    {
        app(NotificationService::class)->generateAll();
        Notification::make()->title('تم تحديث الإشعارات')->success()->send();
    }

    public function markRead(int $id): void
    {
        SystemNotification::query()->findOrFail($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        SystemNotification::query()->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function getNotificationsProperty()
    {
        return SystemNotification::query()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->filter !== 'all' && $this->filter !== 'unread', fn ($query) => $query->where('type', $this->filter))
            ->latest()
            ->limit(100)
            ->get();
    }
}
