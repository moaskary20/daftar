<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Services\DataTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class DataTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_csv_and_updates_records_by_id(): void
    {
        $category = Category::query()->create([
            'name' => 'قديم',
            'slug' => 'old',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'categories-').'.csv';
        file_put_contents($path, implode("\n", [
            'id,name,slug,is_active,sort_order',
            "{$category->id},محدّث,updated,false,5",
            ',جديد,new,true,2',
        ]));

        $result = app(DataTransferService::class)->import(Category::class, $path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'محدّث',
            'is_active' => false,
            'sort_order' => 5,
        ]);
        $this->assertDatabaseHas('categories', ['slug' => 'new']);

        unlink($path);
    }

    public function test_it_imports_xlsx_files(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'categories-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['name', 'slug', 'is_active', 'sort_order']));
        $writer->addRow(Row::fromValues(['Excel تصنيف', 'excel-category', true, 3]));
        $writer->close();

        $result = app(DataTransferService::class)->import(Category::class, $path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('categories', [
            'slug' => 'excel-category',
            'sort_order' => 3,
        ]);

        unlink($path);
    }

    public function test_it_exports_filtered_records_to_xlsx(): void
    {
        Category::query()->create([
            'name' => 'سيظهر',
            'slug' => 'included',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Category::query()->create([
            'name' => 'لن يظهر',
            'slug' => 'excluded',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = app(DataTransferService::class)->export(
            Category::query()->where('is_active', true),
            Category::class,
            'xlsx',
        );
        $path = $response->getFile()->getPathname();
        $reader = new Reader;
        $reader->open($path);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();

        $this->assertContains('slug', $rows[0]);
        $this->assertContains('included', $rows[1]);
        $this->assertCount(2, $rows);

        unlink($path);
    }

    public function test_it_exports_report_rows_to_csv(): void
    {
        $response = app(DataTransferService::class)->exportRows([
            ['البيان' => 'إجمالي المبيعات', 'القيمة' => 125.50],
        ], 'csv', 'sales-report');
        $path = $response->getFile()->getPathname();
        $contents = file_get_contents($path);

        $this->assertStringContainsString('البيان', $contents);
        $this->assertStringContainsString('إجمالي المبيعات', $contents);

        unlink($path);
    }
}
