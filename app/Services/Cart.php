<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Cart
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return array<int, int>
     */
    public function quantities(): array
    {
        $cart = $this->request->session()->get('cart', []);

        return collect($cart)
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity) => $quantity > 0)
            ->all();
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function lines(): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->with(['product.category', 'product.images'])
            ->whereIn('id', array_keys($quantities))
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->get()
            ->keyBy('id');

        $lines = collect();
        $changed = false;

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if ($variant === null) {
                $changed = true;

                continue;
            }

            $available = min($quantity, $variant->stock, 10);

            if ($available < 1) {
                $changed = true;

                continue;
            }

            if ($available !== $quantity) {
                $changed = true;
            }

            $product = $variant->product;
            $image = $product->images->sortBy('sort')->first();

            $lines->push([
                'variant_id' => $variant->id,
                'product_slug' => $product->slug,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'size' => $variant->size,
                'quantity' => $available,
                'stock' => $variant->stock,
                'unit_price' => $product->price,
                'unit_price_formatted' => Money::format($product->price),
                'line_total' => $product->price * $available,
                'line_total_formatted' => Money::format($product->price * $available),
                'image' => $image === null ? null : (\str_starts_with($image->path, 'brand/') ? asset($image->path) : asset('storage/'.$image->path)),
            ]);
        }

        if ($changed) {
            $this->replace($lines->mapWithKeys(fn ($line) => [$line['variant_id'] => $line['quantity']])->all());
        }

        return $lines->values();
    }

    public function add(int $variantId, int $quantity): void
    {
        $variant = ProductVariant::query()->with('product')->find($variantId);

        if ($variant === null || ! $variant->product?->is_active) {
            throw ValidationException::withMessages(['variant_id' => __('store.unavailable')]);
        }

        $current = $this->quantities()[$variantId] ?? 0;
        $next = min($current + $quantity, $variant->stock, 10);

        if ($next < 1) {
            throw ValidationException::withMessages(['variant_id' => __('store.out_of_stock')]);
        }

        $cart = $this->quantities();
        $cart[$variantId] = $next;
        $this->replace($cart);
    }

    public function update(int $variantId, int $quantity): void
    {
        $cart = $this->quantities();

        if ($quantity < 1) {
            unset($cart[$variantId]);
            $this->replace($cart);

            return;
        }

        $variant = ProductVariant::query()->find($variantId);
        $cart[$variantId] = min($quantity, $variant?->stock ?? 0, 10);

        if ($cart[$variantId] < 1) {
            unset($cart[$variantId]);
        }

        $this->replace($cart);
    }

    public function clear(): void
    {
        $this->request->session()->forget('cart');
    }

    /**
     * @param  array<int, int>  $cart
     */
    private function replace(array $cart): void
    {
        $this->request->session()->put('cart', $cart);
    }
}
