<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $paidStatuses = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'paid_total' => Money::format((int) Order::query()->whereIn('status', $paidStatuses)->sum('total')),
                'pending' => Order::query()->where('status', OrderStatus::PendingPayment)->count(),
                'to_fulfill' => Order::query()->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])->count(),
                'products' => Product::query()->count(),
                'unread' => Message::query()->whereNull('read_at')->count(),
            ],
            'orders' => Order::query()->latest()->limit(8)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]),
        ]);
    }
}
