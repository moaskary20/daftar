<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Ean13Barcode;
use chillerlan\QRCode\QRCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductLabelController extends Controller
{
    public function __invoke(Request $request, Product $product): View
    {
        $selectedVariant = null;

        if ($request->filled('variant')) {
            $selectedVariant = $product->variants()->findOrFail($request->integer('variant'));
        }

        $items = match (true) {
            $selectedVariant instanceof ProductVariant => collect([$selectedVariant]),
            $product->type === Product::TYPE_VARIABLE && $product->variants()->where('is_active', true)->exists() => $product->variants()->where('is_active', true)->get(),
            default => collect([$product]),
        };

        $labels = $items->map(function (Product|ProductVariant $item): array {
            $isVariant = $item instanceof ProductVariant;
            $barcodeValue = $item->barcode ?: Product::generateEan13();
            $qrValue = $item->qr_code ?: ($isVariant
                ? 'variant:'.$item->sku
                : 'product:'.$item->sku);

            return [
                'item' => $item,
                'variantName' => $isVariant ? $item->name : null,
                'barcodeSvg' => Ean13Barcode::svg($barcodeValue),
                'qrSvg' => (new QRCode)->render($qrValue),
            ];
        });

        return view('products.labels', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'copies' => min(max($request->integer('copies', 1), 1), 100),
            'labels' => $labels,
        ]);
    }
}
