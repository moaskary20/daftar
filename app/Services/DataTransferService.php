<?php

namespace App\Services;

use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DataTransferService
{
    private const SENSITIVE_COLUMNS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @param  Builder<Model>  $query
     */
    public function export(Builder $query, string $modelClass, string $format): BinaryFileResponse
    {
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('صيغة التصدير غير مدعومة.');
        }

        $model = new $modelClass;
        $columns = $this->exportableColumns($model);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'daftar-export-');
        $path = $temporaryPath.'.'.$format;
        unlink($temporaryPath);
        $writer = $format === 'xlsx' ? new XlsxWriter : new CsvWriter;

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($columns));

        foreach ($query->cursor() as $record) {
            $writer->addRow(Row::fromValues(array_map(
                fn (string $column): null|bool|DateInterval|DateTimeInterface|float|int|string => $this->exportValue($record->getAttribute($column)),
                $columns,
            )));
        }

        $writer->close();

        $fileName = Str::kebab(class_basename($modelClass)).'-'.now()->format('Y-m-d-His').'.'.$format;

        return response()
            ->download($path, $fileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function exportRows(array $rows, string $format, string $fileName): BinaryFileResponse
    {
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('صيغة التصدير غير مدعومة.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'daftar-report-');
        $path = $temporaryPath.'.'.$format;
        unlink($temporaryPath);
        $writer = $format === 'xlsx' ? new XlsxWriter : new CsvWriter;
        $columns = $rows === [] ? ['message'] : array_keys($rows[0]);

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($columns));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(
                fn (string $column): null|bool|DateInterval|DateTimeInterface|float|int|string => $this->exportValue($row[$column] ?? null),
                $columns,
            )));
        }

        if ($rows === []) {
            $writer->addRow(Row::fromValues(['لا توجد بيانات']));
        }

        $writer->close();

        return response()
            ->download($path, Str::slug($fileName).'-'.now()->format('Y-m-d-His').'.'.$format)
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array{created: int, updated: int, failed: int, errors: array<int, string>}
     */
    public function import(string $modelClass, string $path): array
    {
        $model = new $modelClass;
        $writableColumns = $this->writableColumns($model);

        if ($writableColumns === []) {
            throw new RuntimeException('لا توجد أعمدة قابلة للاستيراد في هذه الشاشة.');
        }

        $reader = ReaderFactory::createFromFileByMimeType($path);
        $reader->open($path);

        $result = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        $headers = [];
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $values = $row->toArray();

                    if ($rowNumber === 1) {
                        $headers = array_map(
                            fn (mixed $value): string => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
                            $values,
                        );

                        continue;
                    }

                    if ($this->isEmptyRow($values)) {
                        continue;
                    }

                    try {
                        $data = array_combine(
                            $headers,
                            array_pad(array_slice($values, 0, count($headers)), count($headers), null),
                        );

                        if ($data === false) {
                            throw new RuntimeException('عدد الأعمدة غير متوافق مع صف العناوين.');
                        }

                        DB::transaction(function () use ($modelClass, $model, $data, $writableColumns, &$result): void {
                            $record = $this->resolveRecord($modelClass, $data);
                            $exists = $record->exists;
                            $attributes = [];

                            foreach ($writableColumns as $column) {
                                if (! array_key_exists($column, $data)) {
                                    continue;
                                }

                                $attributes[$column] = $this->normalizeImportedValue(
                                    $data[$column],
                                    $model->getCasts()[$column] ?? null,
                                );
                            }

                            if ($attributes === []) {
                                throw new RuntimeException('لا يحتوي الصف على أعمدة قابلة للاستيراد.');
                            }

                            $record->fill($attributes);
                            $record->save();
                            $result[$exists ? 'updated' : 'created']++;
                        });
                    } catch (Throwable $exception) {
                        $result['failed']++;

                        if (count($result['errors']) < 10) {
                            $result['errors'][] = "الصف {$rowNumber}: {$exception->getMessage()}";
                        }
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        if ($headers === []) {
            throw new RuntimeException('الملف فارغ أو لا يحتوي على صف عناوين.');
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function exportableColumns(Model $model): array
    {
        $columns = Schema::getColumnListing($model->getTable());
        $fillable = $model->getFillable();
        $selected = $fillable === []
            ? $columns
            : array_values(array_intersect($columns, [$model->getKeyName(), ...$fillable]));

        return array_values(array_diff($selected, $model->getHidden(), self::SENSITIVE_COLUMNS));
    }

    /**
     * @return array<int, string>
     */
    public function writableColumns(Model $model): array
    {
        $columns = Schema::getColumnListing($model->getTable());
        $fillable = $model->getFillable();

        if ($fillable === []) {
            return [];
        }

        return array_values(array_intersect($columns, $fillable));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRecord(string $modelClass, array $data): Model
    {
        /** @var Model $model */
        $model = new $modelClass;
        $keyName = $model->getKeyName();

        if (filled($data[$keyName] ?? null)) {
            return $modelClass::query()->find($data[$keyName]) ?? new $modelClass;
        }

        foreach (['email', 'sku', 'barcode', 'code', 'number', 'slug'] as $column) {
            if (! in_array($column, Schema::getColumnListing($model->getTable()), true)) {
                continue;
            }

            if (blank($data[$column] ?? null)) {
                continue;
            }

            return $modelClass::query()->firstOrNew([$column => $data[$column]]);
        }

        return new $modelClass;
    }

    private function normalizeImportedValue(mixed $value, ?string $cast): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(str_contains((string) $cast, 'datetime') ? 'Y-m-d H:i:s' : 'Y-m-d');
        }

        if ($value === '') {
            return null;
        }

        $cast = strtolower((string) $cast);

        if (str_contains($cast, 'bool')) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if (str_contains($cast, 'array') || str_contains($cast, 'json') || str_contains($cast, 'collection')) {
            if (is_array($value)) {
                return $value;
            }

            return json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    private function exportValue(mixed $value): null|bool|DateInterval|DateTimeInterface|float|int|string
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface || $value instanceof DateInterval) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        return collect($values)->every(fn (mixed $value): bool => blank($value));
    }
}
