<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $directory = public_path('images/products');
        File::ensureDirectoryExists($directory);

        $palette = [
            '#5458f0',
            '#10b981',
            '#f59e0b',
            '#f43f5e',
            '#06b6d4',
            '#8b5cf6',
            '#3b82f6',
            '#14b8a6',
        ];

        Product::query()->orderBy('id')->get()->each(function (Product $product, int $index) use ($directory, $palette): void {
            $slug = Str::slug($product->sku ?: ('product-'.$product->id)) ?: 'product-'.$product->id;
            $relative = 'images/products/'.$slug.'.svg';
            $absolute = $directory.'/'.$slug.'.svg';
            $color = $palette[$index % count($palette)];
            $label = e(Str::limit($product->name, 18, ''));

            File::put($absolute, <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$color}"/>
      <stop offset="100%" stop-color="#1e1b4b"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" rx="48" fill="url(#g)"/>
  <circle cx="120" cy="110" r="90" fill="rgba(255,255,255,.15)"/>
  <circle cx="420" cy="420" r="120" fill="rgba(255,255,255,.12)"/>
  <rect x="156" y="150" width="200" height="150" rx="28" fill="rgba(255,255,255,.2)"/>
  <circle cx="256" cy="225" r="36" fill="rgba(255,255,255,.35)"/>
  <text x="256" y="380" text-anchor="middle" font-family="Tahoma, Arial, sans-serif" font-size="36" font-weight="700" fill="#ffffff">{$label}</text>
</svg>
SVG);

            $product->update(['image' => $relative]);
        });
    }
}
