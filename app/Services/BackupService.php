<?php

namespace App\Services;

use App\Models\BackupRecord;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public function create(): BackupRecord
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "daftar-backup-{$timestamp}.zip";
        $relativePath = 'backups/'.$filename;
        $record = BackupRecord::query()->create([
            'created_by' => auth()->id(),
            'filename' => $filename,
            'path' => $relativePath,
            'status' => 'pending',
        ]);

        try {
            Storage::disk('local')->makeDirectory('backups');
            $target = Storage::disk('local')->path($relativePath);
            $zip = new ZipArchive;

            if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('تعذر إنشاء ملف النسخة الاحتياطية.');
            }

            $databasePath = config('database.connections.'.config('database.default').'.database');

            if (config('database.default') === 'sqlite' && is_file($databasePath)) {
                DB::statement('PRAGMA wal_checkpoint(FULL)');
                $zip->addFile($databasePath, 'database/database.sqlite');
            } else {
                $zip->addFromString(
                    'database/database.json',
                    json_encode($this->databaseSnapshot(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                );
            }

            $zip->addFromString('manifest.json', json_encode([
                'application' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'database_driver' => config('database.default'),
                'laravel_version' => app()->version(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $zip->close();

            $record->update([
                'size' => Storage::disk('local')->size($relativePath),
                'status' => 'completed',
                'checksum' => hash_file('sha256', $target),
                'completed_at' => now(),
            ]);

            SystemNotification::query()->updateOrCreate(
                ['unique_key' => 'backup:'.$record->id],
                [
                    'type' => 'backup',
                    'severity' => 'success',
                    'title' => 'اكتملت النسخة الاحتياطية',
                    'message' => "تم إنشاء {$filename} بنجاح.",
                    'reference_type' => $record->getMorphClass(),
                    'reference_id' => $record->id,
                    'action_url' => url('/admin/backups'),
                ],
            );
        } catch (\Throwable $exception) {
            $record->update(['status' => 'failed', 'notes' => $exception->getMessage()]);
            throw $exception;
        }

        return $record->fresh();
    }

    public function delete(BackupRecord $record): void
    {
        Storage::disk($record->disk)->delete($record->path);
        $record->delete();
    }

    private function databaseSnapshot(): array
    {
        $driver = config('database.default');
        $tables = match ($driver) {
            'mysql' => collect(DB::select('SHOW TABLES'))->flatten()->all(),
            'pgsql' => collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"))
                ->pluck('tablename')
                ->all(),
            default => [],
        };

        return collect($tables)->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all(),
        ])->all();
    }
}
