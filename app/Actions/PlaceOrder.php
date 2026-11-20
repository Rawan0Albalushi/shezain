<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    public function handle(?User $user, array $data, Collection $lines): Order
    {
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => __('store.cart_empty')]);
        }

        $shipping = ShippingRate::query()->where('is_active', true)->find($data['governorate_id']);

        if ($shipping === null) {
            throw ValidationException::withMessages(['governorate_id' => __('store.shipping_unavailable')]);
        }

        return DB::transaction(function () use ($user, $data, $lines, $shipping) {
            $subtotal = 0;
            $snapshots = [];

            foreach ($lines as $line) {
                $affected = ProductVariant::query()
                    ->whereKey($line['variant_id'])
                    ->where('stock', '>=', $line['quantity'])
                    ->decrement('stock', $line['quantity']);

                if ($affected !== 1) {
                    throw ValidationException::withMessages(['cart' => __('store.stock_changed')]);
                }

                $variant = ProductVariant::query()->with('product')->findOrFail($line['variant_id']);
                $price = $variant->product->price;
                $lineTotal = $price * $line['quantity'];
                $subtotal += $lineTotal;

                $snapshots[] = [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'name_ar' => $variant->product->name_ar,
                    'name_en' => $variant->product->name_en,
                    'size' => $variant->size,
                    'sku' => $variant->sku,
                    'unit_price' => $price,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $total = $subtotal + $shipping->fee;

            if ($total < 100) {
                throw ValidationException::withMessages(['cart' => __('store.minimum_amount')]);
            }

            $order = Order::query()->create([
                'public_id' => (string) Str::uuid(),
                'number' => 'TMP-'.Str::upper(Str::random(8)),
                'user_id' => $user?->id,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'governorate_ar' => $shipping->governorate_ar,
                'governorate_en' => $shipping->governorate_en,
                'city' => $data['city'],
                'address_line' => $data['address_line'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping->fee,
                'total' => $total,
                'status' => OrderStatus::PendingPayment,
                'stock_held' => true,
            ]);

            $order->update(['number' => 'SZ'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
            $order->items()->createMany($snapshots);

            if ($user && ($data['save_address'] ?? false)) {
                if ($data['is_default'] ?? false) {
                    $user->addresses()->update(['is_default' => false]);
                }

                Address::query()->create([
                    'user_id' => $user->id,
                    'label' => $data['address_label'] ?? null,
                    'name' => $data['customer_name'],
                    'phone' => $data['phone'],
                    'shipping_rate_id' => $shipping->id,
                    'city' => $data['city'],
                    'address_line' => $data['address_line'],
                    'is_default' => (bool) ($data['is_default'] ?? $user->addresses()->doesntExist()),
                ]);
            }

            return $order->fresh(['items']);
        });
    }
}
