<?php

namespace App\Http\Controllers;

use App\Models\SalesDocument;
use Illuminate\Http\Request;

class PosPrintController extends Controller
{
    public function receipt(Request $request, SalesDocument $document)
    {
        $this->authorizePos($request);
        abort_unless($document->channel === 'pos', 404);
        $size = in_array($request->string('size')->toString(), ['58mm', '80mm', 'a4'], true)
            ? $request->string('size')->toString()
            : '80mm';
        $document->increment('print_count');

        return view('pos.receipt', [
            'document' => $document->load(['items.product', 'items.variant', 'payments', 'customer', 'creator']),
            'size' => $size,
        ]);
    }

    public function kitchen(Request $request, SalesDocument $document)
    {
        $this->authorizePos($request);
        abort_unless($document->channel === 'pos', 404);

        return view('pos.kitchen', [
            'document' => $document->load(['items' => fn ($query) => $query->whereHas('product', fn ($product) => $product->where('send_to_kitchen', true)), 'items.product']),
        ]);
    }

    public function customerDisplay(Request $request)
    {
        $this->authorizePos($request);

        return view('pos.customer-display');
    }

    private function authorizePos(Request $request): void
    {
        $user = $request->user();
        abort_unless($user?->hasRole('manager') || $user?->hasPermission('pos', 'view'), 403);
    }
}
