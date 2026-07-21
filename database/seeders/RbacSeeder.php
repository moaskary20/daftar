<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = [
            'activities' => 'سجل النشاط',
            'attendances' => 'الحضور والانصراف',
            'bank_accounts' => 'الحسابات البنكية',
            'bank_checks' => 'الشيكات',
            'brands' => 'الماركات',
            'categories' => 'التصنيفات',
            'chart_accounts' => 'دليل الحسابات',
            'customer_transactions' => 'كشف حساب العملاء',
            'customers' => 'العملاء',
            'departments' => 'الأقسام',
            'employee_adjustments' => 'سلف وجزاءات وحوافز الموظفين',
            'employees' => 'الموظفون',
            'expense_categories' => 'فئات المصروفات',
            'expenses' => 'المصروفات',
            'financial_transactions' => 'الحركات المالية',
            'financial_reports' => 'التقارير المالية',
            'inventory_batches' => 'دفعات المخزون والصلاحية',
            'inventory_reports' => 'تقارير المخزون',
            'journal_entries' => 'القيود اليومية',
            'payrolls' => 'الرواتب',
            'backups' => 'النسخ الاحتياطية',
            'notifications' => 'الإشعارات',
            'pos' => 'نقطة البيع',
            'pos_price_override' => 'تعديل سعر البيع في الكاشير',
            'pos_terminals' => 'إعدادات نقاط البيع',
            'pos_sessions' => 'ورديات الكاشير',
            'coupons' => 'كوبونات الخصم',
            'promotions' => 'العروض والخصومات',
            'product_serials' => 'الأرقام التسلسلية',
            'installment_plans' => 'خطط التقسيط',
            'loyalty_accounts' => 'نقاط ولاء العملاء',
            'permissions' => 'الصلاحيات',
            'products' => 'المنتجات',
            'purchase_documents' => 'مستندات المشتريات',
            'roles' => 'الأدوار',
            'sales_deliveries' => 'تسليمات المبيعات',
            'sales_documents' => 'مستندات المبيعات',
            'sales_reports' => 'تقارير المبيعات والأرباح',
            'stock_movements' => 'حركات المخزون',
            'stock_transfers' => 'تحويلات المخزون',
            'stocktakes' => 'الجرد',
            'supplier_transactions' => 'كشف حساب الموردين',
            'suppliers' => 'الموردون',
            'treasuries' => 'الخزائن',
            'units' => 'الوحدات',
            'users' => 'المستخدمون',
            'vouchers' => 'سندات القبض والصرف',
            'warehouse_stocks' => 'أرصدة المخازن',
            'warehouses' => 'المخازن',
        ];

        $now = now();
        $permissionRows = collect($resources)
            ->flatMap(fn (string $label, string $resource) => collect(Permission::actionLabels())
                ->map(fn (string $actionLabel, string $action): array => [
                    'resource' => $resource,
                    'action' => $action,
                    'name' => "{$actionLabel} {$label}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values())
            ->values()
            ->all();
        Permission::query()->upsert($permissionRows, ['resource', 'action'], ['name', 'updated_at']);

        $roles = [
            'manager' => ['name' => 'مدير النظام', 'description' => 'صلاحية كاملة على النظام'],
            'cashier' => ['name' => 'كاشير', 'description' => 'المبيعات والتحصيل وخدمة العملاء'],
            'accountant' => ['name' => 'محاسب', 'description' => 'الحسابات والقيود والمدفوعات والتقارير المالية'],
            'warehouse' => ['name' => 'مسؤول مخزن', 'description' => 'المخزون والتحويلات والجرد'],
            'branch_manager' => ['name' => 'مدير فرع', 'description' => 'إدارة العمليات اليومية للفرع'],
        ];

        $roleRows = collect($roles)
            ->map(fn (array $data, string $slug): array => $data + [
                'slug' => $slug,
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();
        Role::query()->upsert($roleRows, ['slug'], ['name', 'description', 'is_system', 'is_active', 'updated_at']);

        $allPermissionIds = Permission::query()->pluck('id');
        Role::query()->where('slug', 'manager')->firstOrFail()->permissions()->sync($allPermissionIds);

        $this->syncRole('cashier', [
            'products' => ['view'],
            'customers' => ['view', 'create', 'update'],
            'sales_documents' => ['view', 'create', 'update'],
            'sales_deliveries' => ['view', 'create', 'update'],
            'customer_transactions' => ['view', 'create'],
            'vouchers' => ['view', 'create', 'update'],
            'warehouse_stocks' => ['view'],
            'sales_reports' => ['view'],
            'notifications' => ['view'],
            'pos' => ['view', 'create', 'update'],
            'pos_sessions' => ['view'],
            'coupons' => ['view'],
            'promotions' => ['view'],
            'installment_plans' => ['view'],
            'loyalty_accounts' => ['view'],
        ]);

        $this->syncRole('accountant', [
            'chart_accounts' => ['view', 'create', 'update'],
            'journal_entries' => ['view', 'create', 'update'],
            'treasuries' => ['view', 'create', 'update'],
            'bank_accounts' => ['view', 'create', 'update'],
            'financial_transactions' => ['view', 'create', 'update'],
            'bank_checks' => ['view', 'create', 'update'],
            'vouchers' => ['view', 'create', 'update'],
            'expense_categories' => ['view', 'create', 'update'],
            'expenses' => ['view', 'create', 'update'],
            'customer_transactions' => ['view', 'create'],
            'supplier_transactions' => ['view', 'create'],
            'customers' => ['view', 'update'],
            'suppliers' => ['view', 'update'],
            'sales_documents' => ['view'],
            'purchase_documents' => ['view'],
            'payrolls' => ['view'],
            'sales_reports' => ['view'],
            'inventory_reports' => ['view'],
            'financial_reports' => ['view'],
            'notifications' => ['view'],
            'pos_sessions' => ['view'],
            'installment_plans' => ['view', 'update'],
            'loyalty_accounts' => ['view'],
        ]);

        $this->syncRole('warehouse', [
            'products' => ['view'],
            'warehouses' => ['view'],
            'warehouse_stocks' => ['view'],
            'stock_movements' => ['view', 'create'],
            'stock_transfers' => ['view', 'create', 'update', 'delete'],
            'stocktakes' => ['view', 'create', 'update', 'delete'],
            'inventory_batches' => ['view', 'update'],
            'inventory_reports' => ['view'],
            'notifications' => ['view'],
            'product_serials' => ['view', 'create', 'update'],
        ]);

        $businessResources = array_diff(array_keys($resources), ['roles', 'permissions', 'users', 'backups']);
        $branchPermissions = Permission::query()
            ->whereIn('resource', $businessResources)
            ->pluck('id');
        Role::query()->where('slug', 'branch_manager')->firstOrFail()->permissions()->sync($branchPermissions);

        $admin = User::query()->where('email', 'admin@daftar.test')->first();
        $manager = Role::query()->where('slug', 'manager')->firstOrFail();
        $admin?->roles()->syncWithoutDetaching([$manager->id]);
    }

    private function syncRole(string $slug, array $matrix): void
    {
        $ids = [];

        foreach ($matrix as $resource => $actions) {
            $ids = array_merge(
                $ids,
                Permission::query()
                    ->where('resource', $resource)
                    ->whereIn('action', $actions)
                    ->pluck('id')
                    ->all(),
            );
        }

        Role::query()->where('slug', $slug)->firstOrFail()->permissions()->sync($ids);
    }
}
