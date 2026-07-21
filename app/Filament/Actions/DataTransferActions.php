<?php

namespace App\Filament\Actions;

use App\Services\DataTransferService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class DataTransferActions
{
    /**
     * @param  class-string<Model>  $modelClass
     * @return array<ActionGroup>
     */
    public static function make(string $resourceClass, string $modelClass): array
    {
        return [
            ActionGroup::make([
                Action::make('importData')
                    ->label('استيراد Excel / CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (): bool => $resourceClass::canCreate())
                    ->schema([
                        FileUpload::make('file')
                            ->label('ملف البيانات')
                            ->helperText('يجب أن يكون الصف الأول أسماء الحقول. يدعم النظام ملفات XLSX وCSV حتى 20 ميجابايت.')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(20 * 1024)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->modalHeading('استيراد البيانات')
                    ->modalSubmitActionLabel('بدء الاستيراد')
                    ->action(function (array $data) use ($modelClass): void {
                        $file = $data['file'] ?? null;

                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new RuntimeException('تعذر قراءة الملف المرفوع.');
                        }

                        $result = app(DataTransferService::class)->import($modelClass, $file->getRealPath());
                        $body = "تم إنشاء {$result['created']} وتحديث {$result['updated']} سجل.";

                        if ($result['failed'] > 0) {
                            $body .= " تعذر استيراد {$result['failed']} صف.";

                            if ($result['errors'] !== []) {
                                $body .= "\n".implode("\n", $result['errors']);
                            }
                        }

                        Notification::make()
                            ->title($result['failed'] > 0 ? 'اكتمل الاستيراد مع ملاحظات' : 'تم الاستيراد بنجاح')
                            ->body($body)
                            ->color($result['failed'] > 0 ? 'warning' : 'success')
                            ->persistent($result['failed'] > 0)
                            ->send();
                    }),
                Action::make('exportXlsx')
                    ->label('تصدير Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn (HasTable $livewire) => app(DataTransferService::class)->export(
                        $livewire->getTableQueryForExport(),
                        $modelClass,
                        'xlsx',
                    )),
                Action::make('exportCsv')
                    ->label('تصدير CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->action(fn (HasTable $livewire) => app(DataTransferService::class)->export(
                        $livewire->getTableQueryForExport(),
                        $modelClass,
                        'csv',
                    )),
            ])
                ->label('الاستيراد والتصدير')
                ->icon('heroicon-o-arrows-up-down')
                ->button()
                ->color('primary'),
        ];
    }
}
