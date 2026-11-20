<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Catalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $orders = Order::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'phone' => $order->phone,
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'status' => $status,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load('items');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [...Catalog::order($order), 'id' => $order->id],
            'transitions' => array_map(fn (OrderStatus $status) => $status->value, $order->status->adminTransitions()),
        ]);
    }

    public function update(Request $request, Order $order, ReleaseOrderStock $release): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn (OrderStatus $status) => $status->value, $order->status->adminTransitions()))],
        ]);

        $next = OrderStatus::from($data['status']);

        if ($next === OrderStatus::Cancelled && $order->status === OrderStatus::PendingPayment) {
            $release->handle($order, OrderStatus::Cancelled);
        } else {
            $order->update(['status' => $next]);
        }

        return back()->with('success', __('store.saved'));
    }
}
