<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Muscat'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_month_report_uses_muscat_dates_and_confirmed_orders_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $today = $this->order('SZ-TODAY', OrderStatus::Paid, '2026-11-20 06:00:00', 45000, 2000, 'مسقط', 'Muscat');
        $this->item($today, 'عباية ليالي', 'Layali abaya', 1, 45000);

        $midnight = $this->order('SZ-MIDNIGHT', OrderStatus::Paid, '2026-11-19 20:30:00', 20000, 0, 'مسقط', 'Muscat');
        $this->item($midnight, 'عباية ليالي', 'Layali abaya', 1, 20000);

        $early = $this->order('SZ-EARLY', OrderStatus::Processing, '2026-11-02 06:00:00', 15000, 0, 'ظفار', 'Dhofar');
        $this->item($early, 'عباية نسيم', 'Naseem abaya', 2, 15000);

        $pending = $this->order('SZ-WAIT', OrderStatus::PendingPayment, '2026-11-20 07:00:00', 10000, 0, 'مسقط', 'Muscat');
        $this->item($pending, 'عباية مخفية', 'Hidden abaya', 1, 10000);

        $this->order('SZ-OCT', OrderStatus::Delivered, '2026-10-02 06:00:00', 30000, 0, 'مسقط', 'Muscat');
        $this->order('SZ-NEXT', OrderStatus::Shipped, '2026-11-20 20:30:00', 5000, 0, 'مسقط', 'Muscat');

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports')
                ->where('filters.period', 'month')
                ->where('filters.from', '2026-11-01')
                ->where('filters.to', '2026-11-20')
                ->where('report.grain', 'day')
                ->where('report.summary.orders', 3)
                ->where('report.summary.subtotal', '80.000')
                ->where('report.summary.shipping', '2.000')
                ->where('report.summary.total', '82.000')
                ->where('report.summary.average', '27.333')
                ->where('report.summary.awaiting_orders', 1)
                ->where('report.summary.awaiting_total', '10.000')
                ->has('report.series', 20)
                ->where('report.series.0.key', '2026-11-01')
                ->where('report.series.0.orders', 0)
                ->where('report.series.1.key', '2026-11-02')
                ->where('report.series.1.label', '2 نوفمبر')
                ->where('report.series.1.orders', 1)
                ->where('report.series.1.total', '15.000')
                ->where('report.series.19.key', '2026-11-20')
                ->where('report.series.19.orders', 2)
                ->where('report.series.19.share', 100)
                ->has('report.governorates', 2)
                ->where('report.governorates.0.name_ar', 'مسقط')
                ->where('report.governorates.0.orders', 2)
                ->where('report.governorates.0.total', '67.000')
                ->where('report.governorates.0.share', 82)
                ->where('report.governorates.1.name_ar', 'ظفار')
                ->has('report.products', 2)
                ->where('report.products.0.name_ar', 'عباية ليالي')
                ->where('report.products.0.quantity', 2)
                ->where('report.products.0.total', '65.000')
                ->where('report.products.0.share', 81)
                ->where('report.products.1.name_ar', 'عباية نسيم')
                ->where('report.orders.0.number', 'SZ-TODAY')
                ->has('report.orders', 3));

        $this->actingAs($admin)
            ->get('/admin/reports?period=today')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'today')
                ->where('report.summary.orders', 2)
                ->where('report.summary.total', '67.000')
                ->where('report.summary.average', '33.500')
                ->has('report.series', 1)
                ->where('report.series.0.orders', 2));

        $this->actingAs($admin)
            ->get('/admin/reports?period=year')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.grain', 'month')
                ->where('report.summary.orders', 4)
                ->where('report.summary.total', '112.000')
                ->has('report.series', 11)
                ->where('report.series.9.key', '2026-10')
                ->where('report.series.9.orders', 1)
                ->where('report.series.10.key', '2026-11')
                ->where('report.series.10.orders', 3));

        $this->actingAs($admin)
            ->get('/admin/reports?period=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'all')
                ->where('filters.from', '')
                ->where('report.summary.orders', 5)
                ->where('report.summary.total', '117.000'));

        $this->actingAs($admin)
            ->get('/admin/reports?period=custom&from=2026-11-21&to=2026-11-02')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'custom')
                ->where('filters.from', '2026-11-02')
                ->where('filters.to', '2026-11-21')
                ->where('report.summary.orders', 4)
                ->where('report.summary.total', '87.000'));

        $this->actingAs($admin)
            ->get('/admin/reports?period=nope')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'month')
                ->where('report.summary.total', '82.000'));
    }

    public function test_reports_are_limited_to_admins(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/reports')
            ->assertForbidden();
    }

    private function order(
        string $number,
        OrderStatus $status,
        string $at,
        int $subtotal,
        int $shipping,
        string $governorateAr,
        string $governorateEn,
    ): Order {
        $moment = Carbon::parse($at, 'UTC');

        $order = Order::query()->create([
            'public_id' => (string) Str::uuid(),
            'number' => $number,
            'customer_name' => 'روضة',
            'phone' => '96890000000',
            'email' => 'customer@example.com',
            'governorate_ar' => $governorateAr,
            'governorate_en' => $governorateEn,
            'city' => 'السيب',
            'address_line' => 'شارع المعرفة',
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'total' => $subtotal + $shipping,
            'status' => $status,
            'stock_held' => false,
        ]);

        $order->created_at = $moment;
        $order->updated_at = $moment;
        $order->save();

        return $order;
    }

    private function item(Order $order, string $nameAr, string $nameEn, int $quantity, int $line): void
    {
        $order->items()->create([
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'size' => '54',
            'sku' => 'SKU-54',
            'unit_price' => intdiv($line, $quantity),
            'quantity' => $quantity,
            'line_total' => $line,
        ]);
    }
}
