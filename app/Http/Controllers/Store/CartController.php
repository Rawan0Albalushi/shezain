<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function show(Cart $cart): Response
    {
        $lines = $cart->lines();

        return Inertia::render('Store/Cart', [
            'lines' => $lines,
            'subtotal' => Money::format($lines->sum('line_total')),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $cart->add($data['variant_id'], $data['quantity']);

        return back()->with('success', __('store.added'));
    }

    public function update(Request $request, ProductVariant $variant, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $cart->update($variant->id, $data['quantity']);

        return back();
    }

    public function destroy(ProductVariant $variant, Cart $cart): RedirectResponse
    {
        $cart->update($variant->id, 0);

        return back();
    }
}
