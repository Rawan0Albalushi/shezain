<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\Thawani\WebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThawaniCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.thawani.base_url' => 'https://uatcheckout.thawani.om/api/v1',
            'services.thawani.checkout_url' => 'https://uatcheckout.thawani.om',
            'services.thawani.secret_key' => 'test-secret',
            'services.thawani.publishable_key' => 'test-public',
            'services.thawani.webhook_secret' => 'hook-secret',
        ]);
    }

    public function test_checkout_reserves_stock_and_redirects_to_thawani(): void
    {
        [$variant] = $this->catalog();

        Http::fake([
            'uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
                'success' => true,
                'data' => ['session_id' => 'sess_123'],
            ]),
        ]);

        $this->post('/cart', ['variant_id' => $variant->id, 'quantity' => 2]);

        $this->post('/checkout', $this->checkoutPayload())
            ->assertRedirect('https://uatcheckout.thawani.om/pay/sess_123?key=test-public');

        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertDatabaseHas('orders', ['status' => OrderStatus::PendingPayment->value, 'total' => 92000]);
        $this->assertDatabaseHas('payments', ['session_id' => 'sess_123', 'amount_baisa' => 92000, 'status' => 'unpaid']);
    }

    public function test_return_url_does_not_mark_an_unpaid_session_as_paid(): void
    {
        [$variant] = $this->catalog();
        $order = $this->pendingOrder($variant);

        Http::fake([
            'uatcheckout.thawani.om/api/v1/checkout/session/sess_open' => Http::response([
                'success' => true,
                'data' => [
                    'payment_status' => 'unpaid',
                    'total_amount' => $order->total,
                    'client_reference_id' => $order->number,
                ],
            ]),
        ]);

        $this->get('/checkout/return/'.$order->public_id)->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(3, $variant->fresh()->stock);
    }

    public function test_paid_session_is_confirmed_once_and_amount_must_match(): void
    {
        [$variant] = $this->catalog();
        $order = $this->pendingOrder($variant, 'sess_paid');
        $payment = $order->payments()->first();

        Http::fake([
            'https://uatcheckout.thawani.om/api/v1/checkout/session/sess_paid' => Http::response([
                'success' => true,
                'data' => [
                    'payment_status' => 'paid',
                    'total_amount' => $order->total,
                    'client_reference_id' => $order->number,
                ],
            ]),
        ]);

        $this->get('/checkout/return/'.$order->public_id)->assertOk();
        $this->get('/checkout/return/'.$order->public_id)->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(3, $variant->fresh()->stock);

        $mismatched = $this->pendingOrder($variant, 'sess_bad', 100);
        Http::fake([
            'uatcheckout.thawani.om/api/v1/checkout/session/sess_bad' => Http::response([
                'success' => true,
                'data' => [
                    'payment_status' => 'paid',
                    'total_amount' => 100,
                    'client_reference_id' => $mismatched->number,
                ],
            ]),
        ]);

        $this->get('/checkout/return/'.$mismatched->public_id)->assertOk();
        $this->assertSame(OrderStatus::PendingPayment, $mismatched->fresh()->status);
    }

    public function test_cancel_releases_stock_once(): void
    {
        [$variant] = $this->catalog();
        $order = $this->pendingOrder($variant);

        Http::fake([
            'uatcheckout.thawani.om/api/v1/checkout/session/sess_open' => Http::response([
                'success' => true,
                'data' => [
                    'payment_status' => 'unpaid',
                    'total_amount' => $order->total,
                    'client_reference_id' => $order->number,
                ],
            ]),
        ]);

        $this->get('/checkout/cancel/'.$order->public_id)->assertOk();
        $this->get('/checkout/cancel/'.$order->public_id)->assertOk();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_held);
        $this->assertSame(4, $variant->fresh()->stock);
    }

    public function test_webhook_rejects_a_bad_signature_and_confirms_a_valid_one(): void
    {
        [$variant] = $this->catalog();
        $order = $this->pendingOrder($variant, 'sess_hook');
        $body = json_encode(['data' => ['session_id' => 'sess_hook']]);
        $timestamp = (string) time();

        $this->call('POST', '/payments/thawani/webhook', [], [], [], [
            'HTTP_THAWANI_TIMESTAMP' => $timestamp,
            'HTTP_THAWANI_SIGNATURE' => 'nope',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertUnauthorized();

        Http::fake([
            'uatcheckout.thawani.om/api/v1/checkout/session/sess_hook' => Http::response([
                'success' => true,
                'data' => [
                    'payment_status' => 'paid',
                    'total_amount' => $order->total,
                    'client_reference_id' => $order->number,
                ],
            ]),
        ]);

        $signature = hash_hmac('sha256', $body.'-'.$timestamp, 'hook-secret');

        $this->call('POST', '/payments/thawani/webhook', [], [], [], [
            'HTTP_THAWANI_TIMESTAMP' => $timestamp,
            'HTTP_THAWANI_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertNoContent();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertTrue(WebhookSignature::valid($body, $timestamp, $signature, 'hook-secret'));
    }

    public function test_guest_cannot_open_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_customer_cannot_open_admin(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_the_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    /**
     * @return array{0: \App\Models\ProductVariant, 1: Product}
     */
    private function catalog(): array
    {
        $category = Category::query()->create([
            'slug' => 'formal',
            'name_ar' => 'رسمية',
            'name_en' => 'Formal',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'sort' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'layali',
            'name_ar' => 'ليالي',
            'name_en' => 'Layali',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'price' => 45000,
            'is_active' => true,
        ]);

        $variant = $product->variants()->create([
            'size' => '54',
            'sku' => 'LAYALI-54',
            'stock' => 4,
        ]);

        ShippingRate::query()->create([
            'governorate_ar' => 'مسقط',
            'governorate_en' => 'Muscat',
            'fee' => 2000,
            'is_active' => true,
            'sort' => 1,
        ]);

        return [$variant, $product];
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'روضة',
            'phone' => '96890000000',
            'email' => 'rawdha@example.com',
            'governorate_id' => ShippingRate::query()->first()->id,
            'city' => 'السيب',
            'address_line' => 'شارع المعرفة',
        ];
    }

    private function pendingOrder($variant, string $sessionId = 'sess_open', ?int $storedAmount = null): Order
    {
        $variant->decrement('stock');

        $order = Order::query()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'number' => 'SZ'.random_int(100000, 999999),
            'customer_name' => 'روضة',
            'phone' => '96890000000',
            'email' => 'rawdha@example.com',
            'governorate_ar' => 'مسقط',
            'governorate_en' => 'Muscat',
            'city' => 'السيب',
            'address_line' => 'شارع المعرفة',
            'subtotal' => 45000,
            'shipping_fee' => 2000,
            'total' => 47000,
            'status' => OrderStatus::PendingPayment,
            'stock_held' => true,
        ]);

        $order->items()->create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'name_ar' => 'ليالي',
            'name_en' => 'Layali',
            'size' => '54',
            'sku' => 'LAYALI-54',
            'unit_price' => 45000,
            'quantity' => 1,
            'line_total' => 45000,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'thawani',
            'session_id' => $sessionId,
            'client_reference' => $order->number,
            'amount_baisa' => $storedAmount ?? $order->total,
            'status' => 'unpaid',
        ]);

        return $order;
    }
}
