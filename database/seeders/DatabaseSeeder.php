<?php

namespace Database\Seeders;

use App\Models\ChartAccount;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\PosTerminal;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'admin@daftar.test',
        ], [
            'name' => 'المدير',
            'password' => 'password',
        ]);

        Department::query()->firstOrCreate(
            ['name' => 'القسم العام'],
            ['code' => 'GENERAL', 'is_active' => true],
        );

        Warehouse::query()->firstOrCreate([
            'code' => 'MAIN',
        ], [
            'name' => 'المخزن الرئيسي',
            'is_active' => true,
            'is_default' => true,
        ]);

        foreach ([
            ['name' => 'قطعة', 'symbol' => 'قطعة', 'decimal_places' => 0],
            ['name' => 'كيلوجرام', 'symbol' => 'كجم', 'decimal_places' => 3],
            ['name' => 'لتر', 'symbol' => 'لتر', 'decimal_places' => 3],
            ['name' => 'متر', 'symbol' => 'م', 'decimal_places' => 2],
        ] as $unit) {
            Unit::query()->firstOrCreate(['symbol' => $unit['symbol']], $unit + ['is_active' => true]);
        }

        $accounts = [
            ['code' => '1000', 'name' => 'الأصول', 'type' => 'asset', 'normal_balance' => 'debit', 'is_group' => true, 'parent' => null],
            ['code' => '1100', 'name' => 'الأصول المتداولة', 'type' => 'asset', 'normal_balance' => 'debit', 'is_group' => true, 'parent' => '1000'],
            ['code' => '1101', 'name' => 'النقدية بالصندوق', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1150', 'name' => 'ضريبة القيمة المضافة المدخلة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1200', 'name' => 'العملاء', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1250', 'name' => 'سلف وعهد الموظفين', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1300', 'name' => 'المخزون', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000'],
            ['code' => '2000', 'name' => 'الخصوم', 'type' => 'liability', 'normal_balance' => 'credit', 'is_group' => true, 'parent' => null],
            ['code' => '2100', 'name' => 'الموردون', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '2200', 'name' => 'ضريبة القيمة المضافة المستحقة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '3000', 'name' => 'رأس المال وحقوق الملكية', 'type' => 'equity', 'normal_balance' => 'credit', 'is_group' => true, 'parent' => null],
            ['code' => '3100', 'name' => 'رأس المال', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],
            ['code' => '4000', 'name' => 'الإيرادات', 'type' => 'revenue', 'normal_balance' => 'credit', 'is_group' => true, 'parent' => null],
            ['code' => '4100', 'name' => 'إيرادات المبيعات', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],
            ['code' => '5000', 'name' => 'المصروفات', 'type' => 'expense', 'normal_balance' => 'debit', 'is_group' => true, 'parent' => null],
            ['code' => '5100', 'name' => 'مصروفات عمومية', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5200', 'name' => 'مصروف الرواتب', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5300', 'name' => 'مصروف الإيجار', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5400', 'name' => 'مصروف الصيانة', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
        ];

        foreach ($accounts as $data) {
            $parentCode = $data['parent'];
            unset($data['parent']);
            $data['parent_id'] = $parentCode
                ? ChartAccount::query()->where('code', $parentCode)->value('id')
                : null;
            $data['allow_posting'] = ! ($data['is_group'] ?? false);
            ChartAccount::query()->updateOrCreate(['code' => $data['code']], $data);
        }

        $cashAccount = ChartAccount::query()->where('code', '1101')->firstOrFail();
        $treasury = Treasury::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'chart_account_id' => $cashAccount->id,
                'name' => 'الخزينة الرئيسية',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        PosTerminal::query()->firstOrCreate(
            ['code' => 'POS-MAIN'],
            [
                'warehouse_id' => Warehouse::query()->where('code', 'MAIN')->value('id'),
                'treasury_id' => $treasury->id,
                'name' => 'الكاشير الرئيسي',
                'receipt_size' => '80mm',
                'offline_enabled' => true,
                'is_active' => true,
            ],
        );

        foreach ([
            ['name' => 'مصروفات عامة', 'code' => 'GENERAL', 'account' => '5100'],
            ['name' => 'مرتبات', 'code' => 'SALARY', 'account' => '5200'],
            ['name' => 'إيجار', 'code' => 'RENT', 'account' => '5300'],
            ['name' => 'صيانة', 'code' => 'MAINTENANCE', 'account' => '5400'],
        ] as $category) {
            ExpenseCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'chart_account_id' => ChartAccount::query()->where('code', $category['account'])->value('id'),
                ],
            );
        }

        $this->call(RbacSeeder::class);
        $this->call(ProductImagesSeeder::class);
    }
}
