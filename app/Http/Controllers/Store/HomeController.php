<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $category = $request->string('category')->toString();
        $search = trim($request->string('q')->toString());

        $products = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->when($category !== '' && $category !== 'all', function ($query) use ($category) {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = addcslashes($search, '%_\\');
                $query->where(function ($inner) use ($term) {
                    $inner->where('name_ar', 'like', "%{$term}%")
                        ->orWhere('name_en', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => Catalog::card($product));

        return Inertia::render('Store/Home', [
            'filters' => [
                'category' => $category === '' ? 'all' : $category,
                'q' => $search,
            ],
            'products' => $products,
            'settings' => Setting::content(),
            'slides' => [
                ['src' => asset('brand/formal.jpg'), 'label' => 'THE ART OF QUIET ELEGANCE'],
                ['src' => asset('brand/winter.jpg'), 'label' => 'THE ART OF QUIET ELEGANCE'],
                ['src' => asset('brand/summer.jpg'), 'label' => 'THE ART OF QUIET ELEGANCE'],
            ],
        ]);
    }
}
