<?php

namespace App\Http\Controllers;

use App\Models\PosTerminal;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSyncController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('manager') || $user?->hasPermission('pos', 'create'), 403);
        $data = $request->validate([
            'terminal_id' => ['required', 'integer', 'exists:pos_terminals,id'],
            'client_uuid' => ['required', 'uuid'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_type' => ['required', 'in:cash,credit,installment,mixed'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.serial_number' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'gte:0'],
            'payments' => ['array'],
            'payments.*.method' => ['required', 'in:cash,card,wallet,bank'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'invoice_discount' => ['nullable', 'numeric', 'gte:0'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'loyalty_points' => ['nullable', 'integer', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'installment' => ['nullable', 'array'],
        ]);
        $terminal = PosTerminal::query()->where('is_active', true)->findOrFail($data['terminal_id']);
        $data['pos_session_id'] = app(PosService::class)->openSession($terminal)->id;
        $document = app(PosService::class)->checkout($data);
        $document->update(['offline_synced_at' => now()]);

        return response()->json([
            'id' => $document->id,
            'number' => $document->number,
            'print_url' => route('pos.print', $document),
        ], 201);
    }
}
