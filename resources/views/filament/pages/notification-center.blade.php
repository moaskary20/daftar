<x-filament-panels::page>
    <div style="display:grid;gap:1rem">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:space-between">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <select class="fi-select-input" wire:model.live="filter">
                    <option value="unread">غير المقروءة</option>
                    <option value="all">الكل</option>
                    @foreach(\App\Models\SystemNotification::typeLabels() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-filament::button wire:click="generate" icon="heroicon-o-arrow-path">تحديث الإشعارات</x-filament::button>
            </div>
            <x-filament::button wire:click="markAllRead" color="gray" icon="heroicon-o-check">تحديد الكل كمقروء</x-filament::button>
        </div>

        @forelse($this->notifications as $notification)
            <x-filament::section>
                <div style="display:flex;gap:1rem;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                            <strong style="font-size:1.05rem">{{ $notification->title }}</strong>
                            <span style="padding:.18rem .55rem;border-radius:99px;font-size:.72rem;background:
                                {{ $notification->severity === 'danger' ? '#fee2e2' : ($notification->severity === 'success' ? '#dcfce7' : '#fef3c7') }}">
                                {{ \App\Models\SystemNotification::typeLabels()[$notification->type] ?? $notification->type }}
                            </span>
                        </div>
                        <p style="color:#64748b;margin-top:.45rem">{{ $notification->message }}</p>
                        <small style="color:#94a3b8">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div style="display:flex;gap:.5rem">
                        @if($notification->action_url)
                            <x-filament::button tag="a" :href="$notification->action_url" size="sm" color="gray">فتح</x-filament::button>
                        @endif
                        @if(!$notification->read_at)
                            <x-filament::button wire:click="markRead({{ $notification->id }})" size="sm">تمت القراءة</x-filament::button>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div style="text-align:center;padding:3rem;color:#64748b">لا توجد إشعارات حالياً</div>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
