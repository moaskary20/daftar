<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Role;
use App\Models\Stocktake;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_cashier_can_manage_customers_but_cannot_access_accounting(): void
    {
        $cashier = $this->userWithRole('cashier');

        $this->assertTrue($cashier->hasPermission('customers', 'view'));
        $this->assertTrue(Gate::forUser($cashier)->allows('viewAny', Customer::class));
        $this->assertTrue(Gate::forUser($cashier)->allows('create', Customer::class));
        $this->assertTrue(Gate::forUser($cashier)->allows('update', new Customer));
        $this->assertFalse(Gate::forUser($cashier)->allows('delete', new Customer));
        $this->assertFalse(Gate::forUser($cashier)->allows('viewAny', JournalEntry::class));
    }

    public function test_warehouse_role_can_manage_stocktakes_but_not_customers(): void
    {
        $warehouse = $this->userWithRole('warehouse');

        $this->assertTrue(Gate::forUser($warehouse)->allows('viewAny', Stocktake::class));
        $this->assertTrue(Gate::forUser($warehouse)->allows('create', Stocktake::class));
        $this->assertTrue(Gate::forUser($warehouse)->allows('delete', new Stocktake));
        $this->assertFalse(Gate::forUser($warehouse)->allows('viewAny', Customer::class));
    }

    public function test_manager_has_all_crud_permissions_and_can_open_security_pages(): void
    {
        $manager = $this->userWithRole('manager');

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', JournalEntry::class));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Customer::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', new Customer));
        $this->assertTrue(Gate::forUser($manager)->allows('delete', new Customer));

        $this->actingAs($manager)
            ->get('/admin/roles')
            ->assertOk();

        $this->get('/admin/roles/'.Role::query()->where('slug', 'manager')->value('id').'/edit')
            ->assertOk();
    }

    public function test_unauthorized_resource_page_is_forbidden(): void
    {
        $cashier = $this->userWithRole('cashier');

        $this->actingAs($cashier)
            ->get('/admin/journal-entries')
            ->assertForbidden();

        $this->get('/admin/customers')
            ->assertOk();

        $this->get('/admin/sales-report')
            ->assertOk();

        $this->get('/admin/financial-report')
            ->assertForbidden();
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', $slug)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}
