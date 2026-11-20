<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Catalog;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'images', 'variants']);

        return Inertia::render('Store/Product', [
            'product' => Catalog::detail($product),
        ]);
    }
}
