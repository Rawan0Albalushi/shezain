<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $paidStatuses = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        $counts = $this->statusCounts();
        $today = Carbon::now('Asia/Muscat')->toDateString();

        return Inertia::render('Admin/Dashboard', [
            'today' => $today,
            'stats' => [
                'paid_total' => Money::format((int) Order::query()->whereIn('status', $paidStatuses)->sum('total')),
                'pending' => (int) ($counts[OrderStatus::PendingPayment->value] ?? 0),
                'to_fulfill' => (int) ($counts[OrderStatus::Paid->value] ?? 0) + (int) ($counts[OrderStatus::Processing->value] ?? 0),
                'shipped' => (int) ($counts[OrderStatus::Shipped->value] ?? 0),
                'delivered' => (int) ($counts[OrderStatus::Delivered->value] ?? 0),
                'low_stock' => Product::query()->lowStock()->count(),
                'out_of_stock' => Product::query()->outOfStock()->count(),
                'unread' => Message::query()->whereNull('read_at')->count(),
                'today' => Order::query()->where('created_at', '>=', Carbon::parse($today, 'Asia/Muscat')->startOfDay()->utc())->count(),
            ],
            'orders' => Order::query()->latest()->limit(8)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'phone' => $order->phone,
                'city' => $order->city,
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        return Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn (Order $order) => [$order->status->value => (int) $order->aggregate])
            ->all();
    }
}
