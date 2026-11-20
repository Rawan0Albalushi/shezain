<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'slug', 'name_ar', 'name_en', 'description_ar', 'description_en', 'fabric_ar', 'fabric_en', 'price', 'is_active'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('size');
    }

    #[Scope]
    protected function lowStock(Builder $query): Builder
    {
        return $query->whereIn('products.id', function ($subquery) {
            $subquery->select('product_id')
                ->from('product_variants')
                ->groupBy('product_id')
                ->havingRaw('sum(stock) between 1 and 3');
        });
    }

    #[Scope]
    protected function outOfStock(Builder $query): Builder
    {
        return $query->whereDoesntHave('variants', fn (Builder $variants) => $variants->where('stock', '>', 0));
    }

    #[Scope]
    protected function stockState(Builder $query, string $state): Builder
    {
        return match ($state) {
            'low' => $query->lowStock(),
            'out' => $query->outOfStock(),
            'in' => $query->whereIn('products.id', function ($subquery) {
                $subquery->select('product_id')
                    ->from('product_variants')
                    ->groupBy('product_id')
                    ->havingRaw('sum(stock) > 0');
            }),
            default => $query,
        };
    }
}
