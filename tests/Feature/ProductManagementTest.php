<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_generates_barcode_qr_and_supports_prices_and_variants(): void
    {
        $category = Category::query()->create([
            'name' => 'ملابس',
            'slug' => 'clothes',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'قميص قطني',
            'slug' => 'cotton-shirt',
            'type' => Product::TYPE_VARIABLE,
            'selling_price' => 120,
        ]);

        $product->prices()->create([
            'name' => 'سعر الجملة',
            'price' => 95,
            'minimum_quantity' => 10,
            'is_active' => true,
        ]);

        $variant = $product->variants()->create([
            'name' => 'أحمر / XL',
            'color' => 'أحمر',
            'size' => 'XL',
            'selling_price' => 125,
            'stock_quantity' => 7,
            'is_active' => true,
        ]);

        $this->assertMatchesRegularExpression('/^\d{13}$/', $product->barcode);
        $this->assertSame('product:'.$product->sku, $product->qr_code);
        $this->assertMatchesRegularExpression('/^\d{13}$/', $variant->barcode);
        $this->assertSame('سعر الجملة', $product->prices()->first()->name);
        $this->assertSame('XL', $product->variants()->first()->size);
    }

    public function test_authenticated_user_can_open_printable_product_label(): void
    {
        $user = User::factory()->create();
        $product = Product::query()->create([
            'name' => 'منتج تجريبي',
            'slug' => 'sample-product',
            'selling_price' => 25,
        ]);

        $this->actingAs($user)
            ->get(route('products.labels', $product))
            ->assertOk()
            ->assertSee('طباعة باركود')
            ->assertSee($product->barcode)
            ->assertSee('<svg', escape: false);
    }
}
