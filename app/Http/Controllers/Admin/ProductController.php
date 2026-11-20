<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Catalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = Str::of($request->string('q')->trim()->toString())->limit(80, '')->toString();
        $category = $request->string('category')->toString();
        $visibility = $request->string('visibility')->toString();
        $stock = $request->string('stock')->toString();

        if (! in_array($visibility, ['active', 'hidden'], true)) {
            $visibility = '';
        }

        if (! in_array($stock, ['in', 'low', 'out'], true)) {
            $stock = '';
        }

        $products = Product::query()
            ->with(['category:id,slug,name_ar,name_en', 'images'])
            ->withSum('variants', 'stock')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name_ar', 'like', $like)
                        ->orWhere('name_en', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', $like));
                });
            })
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categories) => $categories->where('slug', $category)))
            ->when($visibility === 'active', fn ($query) => $query->where('is_active', true))
            ->when($visibility === 'hidden', fn ($query) => $query->where('is_active', false))
            ->when($stock !== '', fn ($query) => $query->stockState($stock))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'category_ar' => $product->category?->name_ar,
                'category_en' => $product->category?->name_en,
                'price' => Money::format($product->price),
                'is_active' => $product->is_active,
                'stock' => (int) $product->variants_sum_stock,
                'image' => Catalog::imageUrl($product->images->first()),
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('sort')->get(['slug', 'name_ar', 'name_en']),
            'filters' => [
                'q' => $search,
                'category' => $category,
                'visibility' => $visibility,
                'stock' => $stock,
            ],
            'totals' => [
                'all' => Product::query()->count(),
                'active' => Product::query()->where('is_active', true)->count(),
                'hidden' => Product::query()->where('is_active', false)->count(),
                'in' => Product::query()->stockState('in')->count(),
                'low' => Product::query()->lowStock()->count(),
                'out' => Product::query()->outOfStock()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => Category::query()->orderBy('sort')->get(['id', 'name_ar', 'name_en']),
            'product' => null,
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $product = Product::query()->create([
            'category_id' => $data['category_id'],
            'slug' => $this->slug($data['name_en']),
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'description_ar' => $data['description_ar'],
            'description_en' => $data['description_en'],
            'fabric_ar' => $data['fabric_ar'] ?? null,
            'fabric_en' => $data['fabric_en'] ?? null,
            'price' => Money::fromOmr($data['price']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncVariants($product, $data['variants']);
        $this->storeImages($product, $request);

        return redirect()->route('admin.products.edit', $product)->with('success', __('store.saved'));
    }

    public function show(Product $product): Response
    {
        $product->load(['category:id,name_ar,name_en', 'images', 'variants']);

        return Inertia::render('Admin/Products/Show', [
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'description_ar' => $product->description_ar,
                'description_en' => $product->description_en,
                'fabric_ar' => $product->fabric_ar,
                'fabric_en' => $product->fabric_en,
                'price' => Money::format($product->price),
                'is_active' => $product->is_active,
                'category_ar' => $product->category?->name_ar,
                'category_en' => $product->category?->name_en,
                'stock' => (int) $product->variants->sum('stock'),
                'images' => $product->images
                    ->map(fn ($image) => Catalog::imageUrl($image))
                    ->filter()
                    ->values()
                    ->all(),
                'variants' => $product->variants->map(fn ($variant) => [
                    'size' => $variant->size,
                    'color_ar' => $variant->color_ar,
                    'color_en' => $variant->color_en,
                    'swatch' => $variant->color_hex,
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                ])->values()->all(),
            ],
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'variants']);

        return Inertia::render('Admin/Products/Form', [
            'categories' => Category::query()->orderBy('sort')->get(['id', 'name_ar', 'name_en']),
            'product' => [
                ...Catalog::detail($product),
                'category_id' => $product->category_id,
                'price_omr' => Money::format($product->price),
                'is_active' => $product->is_active,
                'image_records' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => Catalog::imageUrl($image),
                ])->values(),
            ],
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $product->update([
            'category_id' => $data['category_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'description_ar' => $data['description_ar'],
            'description_en' => $data['description_en'],
            'fabric_ar' => $data['fabric_ar'] ?? null,
            'fabric_en' => $data['fabric_en'] ?? null,
            'price' => Money::fromOmr($data['price']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncVariants($product, $data['variants']);
        $this->removeImages($product, $data['remove_images'] ?? []);
        $this->storeImages($product, $request);

        return back()->with('success', __('store.saved'));
    }

    public function visibility(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', __('store.saved'));
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        DB::transaction(function () use ($product, $variants) {
            $kept = [];

            foreach ($variants as $row) {
                if (! empty($row['id'])) {
                    $kept[] = (int) $row['id'];
                }
            }

            $product->variants()->whereNotIn('id', $kept)->get()->each(function (ProductVariant $variant) {
                if (OrderItem::query()->where('variant_id', $variant->id)->exists()) {
                    throw ValidationException::withMessages(['variants' => 'لا يمكن حذف مقاس ولون مرتبطين بطلب.']);
                }

                $variant->delete();
            });

            $product->variants()->whereIn('id', $kept)->get()->each(function (ProductVariant $variant) {
                $variant->update([
                    'size' => 'tmp',
                    'color_ar' => 'tmp-'.$variant->id,
                    'color_en' => 'tmp-'.$variant->id,
                    'sku' => 'tmp-'.$variant->id,
                ]);
            });

            foreach ($variants as $row) {
                $skuTaken = ProductVariant::query()
                    ->where('sku', $row['sku'])
                    ->when(! empty($row['id']), fn ($query) => $query->where('id', '!=', $row['id']))
                    ->exists();

                if ($skuTaken) {
                    throw ValidationException::withMessages(['variants' => 'رمز SKU مستخدم: '.$row['sku']]);
                }

                $attributes = [
                    'size' => $row['size'],
                    'color_ar' => $row['color_ar'],
                    'color_en' => $row['color_en'],
                    'color_hex' => $row['color_hex'] ?? null,
                    'sku' => $row['sku'],
                    'stock' => $row['stock'],
                ];

                if (! empty($row['id'])) {
                    $product->variants()->whereKey($row['id'])->firstOrFail()->update($attributes);
                } else {
                    $product->variants()->create($attributes);
                }
            }
        });
    }

    private function storeImages(Product $product, ProductRequest $request): void
    {
        $sort = (int) $product->images()->max('sort');

        foreach ($request->file('images', []) as $file) {
            $sort++;
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort' => $sort,
            ]);
        }
    }

    /**
     * @param  list<int>  $ids
     */
    private function removeImages(Product $product, array $ids): void
    {
        $product->images()->whereIn('id', $ids)->get()->each(function ($image) {
            if (! str_starts_with($image->path, 'brand/')) {
                Storage::disk('public')->delete($image->path);
            }

            $image->delete();
        });
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'abaya';
        $slug = $base;
        $i = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
