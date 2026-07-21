<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل النشاط')
                    ->schema([
                        TextEntry::make('description')
                            ->label('الوصف'),
                        TextEntry::make('event')
                            ->label('الحدث')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'created' => 'إنشاء',
                                'updated' => 'تحديث',
                                'deleted' => 'حذف',
                                default => $state ?? '—',
                            }),
                        TextEntry::make('log_name')
                            ->label('اسم السجل')
                            ->placeholder('—'),
                        TextEntry::make('subject_type')
                            ->label('نوع العنصر')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                        TextEntry::make('subject_id')
                            ->label('معرّف العنصر')
                            ->placeholder('—'),
                        TextEntry::make('causer.name')
                            ->label('المنفّذ')
                            ->placeholder('النظام'),
                        TextEntry::make('created_at')
                            ->label('التاريخ')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
                Section::make('التغييرات')
                    ->schema([
                        TextEntry::make('properties.old')
                            ->label('قبل')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—')
                            ->columnSpanFull(),
                        TextEntry::make('properties.attributes')
                            ->label('بعد')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
