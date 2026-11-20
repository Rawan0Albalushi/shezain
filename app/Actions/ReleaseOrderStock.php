<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ReleaseOrderStock
{
    public function handle(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status->isPaid() || ! $locked->stock_held) {
                return $locked;
            }

            foreach ($locked->items()->get() as $item) {
                if ($item->variant_id) {
                    ProductVariant::query()->whereKey($item->variant_id)->increment('stock', $item->quantity);
                }
            }

            $locked->update([
                'stock_held' => false,
                'status' => $status,
            ]);

            return $locked->fresh();
        });
    }
}
