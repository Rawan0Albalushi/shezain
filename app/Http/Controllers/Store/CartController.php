<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Support\Catalog;
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
            'suggestions' => $lines->isEmpty() ? $this->suggestions() : [],
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

    /**
     * @return list<array<string, mixed>>
     */
    private function suggestions(): array
    {
        return Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->whereHas('variants', fn ($query) => $query->where('stock', '>', 0))
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Product $product) => Catalog::card($product))
            ->all();
    }

    public function destroy(ProductVariant $variant, Cart $cart): RedirectResponse
    {
        $cart->update($variant->id, 0);

        return back();
    }
}
