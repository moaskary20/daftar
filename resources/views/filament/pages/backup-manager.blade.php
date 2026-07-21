<x-filament-panels::page>
    <div style="display:grid;gap:1rem">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <p style="color:#64748b">أنشئ نسخة مضغوطة من قاعدة البيانات واحفظها في مساحة تخزين خاصة.</p>
            @if(auth()->user()->hasRole('manager') || auth()->user()->hasPermission('backups', 'create'))
                <x-filament::button wire:click="createBackup" wire:loading.attr="disabled" icon="heroicon-o-circle-stack">
                    <span wire:loading.remove wire:target="createBackup">إنشاء نسخة احتياطية</span>
                    <span wire:loading wire:target="createBackup">جارٍ الإنشاء...</span>
                </x-filament::button>
            @endif
        </div>

        <x-filament::section>
            <div style="overflow:auto">
                <table class="fi-ta-table" style="width:100%">
                    <thead><tr><th>الملف</th><th>الحجم</th><th>الحالة</th><th>أنشأها</th><th>التاريخ</th><th></th></tr></thead>
                    <tbody>
                    @forelse($this->backups as $backup)
                        <tr>
                            <td style="direction:ltr;text-align:right">{{ $backup->filename }}</td>
                            <td>{{ $backup->formatted_size }}</td>
                            <td>{{ ['pending'=>'قيد الإنشاء','completed'=>'مكتملة','failed'=>'فشلت'][$backup->status] ?? $backup->status }}</td>
                            <td>{{ $backup->creator?->name ?? 'النظام' }}</td>
                            <td>{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div style="display:flex;gap:.5rem">
                                    @if($backup->status === 'completed')
                                        <x-filament::button tag="a" :href="route('backups.download', $backup)" size="sm" color="success" icon="heroicon-o-arrow-down-tray">تنزيل</x-filament::button>
                                    @endif
                                    @if(auth()->user()->hasRole('manager') || auth()->user()->hasPermission('backups', 'delete'))
                                        <x-filament::button wire:click="deleteBackup({{ $backup->id }})" wire:confirm="هل تريد حذف النسخة؟" size="sm" color="danger">حذف</x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:3rem">لم يتم إنشاء نسخ احتياطية بعد</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
