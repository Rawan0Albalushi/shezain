<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_desk_filters_catalog_orders_and_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'slug' => 'formal',
            'name_ar' => 'عبايات رسمية',
            'name_en' => 'Formal abayas',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'sort' => 1,
        ]);

        $low = $this->product($category, 'layali', 'عباية ليالي', 'LAYALI-54', 2);
        $this->product($category, 'naseem', 'عباية نسيم', 'NASEEM-54', 8);
        $this->order('SZ100001', 'روضة', OrderStatus::Paid);
        $this->order('SZ100002', 'ميساء', OrderStatus::PendingPayment);
        Message::query()->create([
            'name' => 'أمل',
            'email' => 'amal@example.com',
            'phone' => '96891111111',
            'body' => 'هل مقاس ٥٦ متوفر؟',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.low_stock', 1)
                ->where('stats.out_of_stock', 0)
                ->where('stats.to_fulfill', 1)
                ->where('stats.pending', 1)
                ->where('stats.unread', 1)
                ->has('orders', 2));

        $this->actingAs($admin)
            ->get('/admin/products?stock=low')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.slug', 'layali')
                ->where('totals.low', 1));

        $this->actingAs($admin)
            ->get('/admin/products?q=LAYALI-54')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));

        $this->actingAs($admin)
            ->patch('/admin/products/'.$low->id.'/visibility')
            ->assertRedirect();

        $this->assertFalse($low->fresh()->is_active);

        $this->actingAs($admin)
            ->get('/admin/orders?status=fulfill')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.number', 'SZ100001')
                ->where('counts.fulfill', 1));

        $this->actingAs($admin)
            ->get('/admin/orders?q=ميساء')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('orders.data.0.customer_name', 'ميساء'));

        $this->actingAs($admin)
            ->post('/admin/products', $this->payload($category->id, [
                $this->variantRow('54', 'أسود', 'Black', '#1C1C1C', 'LAYALI-BLK-54', 4),
                $this->variantRow('54', 'أسود', 'Black', '#1C1C1C', 'LAYALI-BLK-56', 1),
            ]))
            ->assertSessionHasErrors('variants.1.color_ar');

        $this->actingAs($admin)
            ->post('/admin/products', $this->payload($category->id, [
                $this->variantRow('54', 'أسود', 'Black', '#1C1C1C', 'MISK-BLK-54', 4),
                $this->variantRow('54', 'كحلي', 'Navy', '#1B2A4A', 'MISK-NVY-54', 2),
            ], 'عباية مسك', 'Misk abaya'))
            ->assertRedirect();

        $created = Product::query()->where('slug', 'misk-abaya')->firstOrFail();
        $black = $created->variants()->where('sku', 'MISK-BLK-54')->firstOrFail();
        $navy = $created->variants()->where('sku', 'MISK-NVY-54')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/products/'.$created->id, $this->payload($category->id, [
                $this->variantRow('54', 'كحلي', 'Navy', '#1B2A4A', 'MISK-BLK-54', 4, $black->id),
                $this->variantRow('54', 'أسود', 'Black', '#1C1C1C', 'MISK-NVY-54', 2, $navy->id),
            ], 'عباية مسك', 'Misk abaya'))
            ->assertRedirect();

        $this->assertSame('كحلي', $black->fresh()->color_ar);
        $this->assertSame('أسود', $navy->fresh()->color_ar);

        $this->get('/products/misk-abaya')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Store/Product')
                ->has('product.colors', 2)
                ->has('product.variants', 2));

        $this->actingAs($admin)
            ->get('/admin/messages?state=unread')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Messages')
                ->has('messages.data', 1)
                ->where('counts.unread', 1));
    }

    public function test_table_view_returns_details_without_changing_the_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $category = Category::query()->create([
            'slug' => 'formal',
            'name_ar' => 'عبايات رسمية',
            'name_en' => 'Formal abayas',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'sort' => 1,
        ]);
        $product = $this->product($category, 'layali', 'عباية ليالي', 'LAYALI-54', 2);
        $order = $this->order('SZ100020', 'روضة', OrderStatus::Paid);
        $order->items()->create([
            'name_ar' => 'عباية ليالي',
            'name_en' => 'Layali',
            'size' => '54',
            'color_ar' => 'أسود',
            'color_en' => 'Black',
            'color_hex' => '#1C1C1C',
            'sku' => 'LAYALI-54',
            'unit_price' => 45000,
            'quantity' => 1,
            'line_total' => 45000,
        ]);

        $this->actingAs($customer)
            ->get('/admin/products/'.$product->id)
            ->assertForbidden();

        $this->actingAs($customer)
            ->get('/admin/orders/'.$order->id)
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/products/'.$product->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Show')
                ->where('product.name_ar', 'عباية ليالي')
                ->where('product.price', '45.000')
                ->where('product.stock', 2)
                ->where('product.variants.0.sku', 'LAYALI-54')
                ->missing('transitions'));

        $this->assertTrue($product->fresh()->is_active);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.number', 'SZ100020')
                ->where('order.customer_name', 'روضة')
                ->where('order.items.0.color_ar', 'أسود')
                ->where('order.items.0.line_total', '45.000'));

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     * @return array<string, mixed>
     */
    private function payload(int $categoryId, array $variants, string $nameAr = 'عباية ليالي', string $nameEn = 'Layali abaya'): array
    {
        return [
            'category_id' => $categoryId,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'description_ar' => 'وصف العباية',
            'description_en' => 'Abaya description',
            'fabric_ar' => 'كريب',
            'fabric_en' => 'Crepe',
            'price' => '45.000',
            'is_active' => true,
            'variants' => $variants,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function variantRow(string $size, string $colorAr, string $colorEn, string $hex, string $sku, int $stock, ?int $id = null): array
    {
        return [
            'id' => $id,
            'size' => $size,
            'color_ar' => $colorAr,
            'color_en' => $colorEn,
            'color_hex' => $hex,
            'sku' => $sku,
            'stock' => $stock,
        ];
    }

    private function product(Category $category, string $slug, string $name, string $sku, int $stock): Product
    {
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => $slug,
            'name_ar' => $name,
            'name_en' => $slug,
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'price' => 45000,
            'is_active' => true,
        ]);

        $product->variants()->create([
            'size' => '54',
            'color_ar' => 'أسود',
            'color_en' => 'Black',
            'color_hex' => '#1C1C1C',
            'sku' => $sku,
            'stock' => $stock,
        ]);

        return $product;
    }

    public function test_admin_status_update_names_the_new_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order('SZ100010', 'ليلى', OrderStatus::Paid);

        $this->actingAs($admin)
            ->from('/admin/orders/'.$order->id)
            ->patch('/admin/orders/'.$order->id, ['status' => OrderStatus::Processing->value])
            ->assertRedirect('/admin/orders/'.$order->id)
            ->assertSessionHas('success', 'أصبحت الحالة: قيد التجهيز.');

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => OrderStatus::Paid->value])
            ->assertSessionHasErrors('status');
    }

    private function order(string $number, string $name, OrderStatus $status): Order
    {
        return Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'number' => $number,
            'customer_name' => $name,
            'phone' => '96890000000',
            'email' => 'customer@example.com',
            'governorate_ar' => 'مسقط',
            'governorate_en' => 'Muscat',
            'city' => 'السيب',
            'address_line' => 'شارع المعرفة',
            'subtotal' => 45000,
            'shipping_fee' => 2000,
            'total' => 47000,
            'status' => $status,
            'stock_held' => false,
        ]);
    }
}
