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
            ->get('/admin/messages?state=unread')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Messages')
                ->has('messages.data', 1)
                ->where('counts.unread', 1));
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
            'sku' => $sku,
            'stock' => $stock,
        ]);

        return $product;
    }

    private function order(string $number, string $name, OrderStatus $status): void
    {
        Order::query()->create([
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
