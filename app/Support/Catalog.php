<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;

final class Catalog
{
    public static function imageUrl(?ProductImage $image): ?string
    {
        if ($image === null) {
            return null;
        }

        if (str_starts_with($image->path, 'brand/')) {
            return asset($image->path);
        }

        return asset('storage/'.$image->path);
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'price' => $product->price,
            'price_formatted' => Money::format($product->price),
            'category' => [
                'slug' => $product->category->slug,
                'name_ar' => $product->category->name_ar,
                'name_en' => $product->category->name_en,
            ],
            'image' => self::imageUrl($product->images->sortBy('sort')->first()),
            'sizes' => $product->variants->pluck('size')->unique()->values()->all(),
            'colors' => $product->variants->unique('color_ar')->map(fn ($variant) => [
                'color_ar' => $variant->color_ar,
                'color_en' => $variant->color_en,
                'swatch' => $variant->color_hex,
            ])->values()->all(),
            'in_stock' => $product->variants->sum('stock') > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(Product $product): array
    {
        return [
            ...self::card($product),
            'description_ar' => $product->description_ar,
            'description_en' => $product->description_en,
            'fabric_ar' => $product->fabric_ar,
            'fabric_en' => $product->fabric_en,
            'images' => $product->images->sortBy('sort')->map(fn (ProductImage $image) => self::imageUrl($image))->values()->all(),
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'size' => $variant->size,
                'color_ar' => $variant->color_ar,
                'color_en' => $variant->color_en,
                'color_hex' => $variant->color_hex,
                'swatch' => $variant->color_hex,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function order(Order $order): array
    {
        return [
            'public_id' => $order->public_id,
            'number' => $order->number,
            'status' => $order->status->value,
            'customer_name' => $order->customer_name,
            'phone' => $order->phone,
            'email' => $order->email,
            'governorate_ar' => $order->governorate_ar,
            'governorate_en' => $order->governorate_en,
            'city' => $order->city,
            'address_line' => $order->address_line,
            'notes' => $order->notes,
            'subtotal' => Money::format($order->subtotal),
            'shipping_fee' => Money::format($order->shipping_fee),
            'total' => Money::format($order->total),
            'total_baisa' => $order->total,
            'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            'items' => $order->items->map(fn ($item) => [
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'size' => $item->size,
                'color_ar' => $item->color_ar,
                'color_en' => $item->color_en,
                'swatch' => $item->color_hex,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'line_total' => Money::format($item->line_total),
            ])->values()->all(),
        ];
    }
}
