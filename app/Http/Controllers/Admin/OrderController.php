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
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $allowed = array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases());
        $status = $request->string('status')->toString();

        if ($status !== 'fulfill' && ! in_array($status, $allowed, true)) {
            $status = '';
        }

        $search = Str::of($request->string('q')->trim()->toString())->limit(80, '')->toString();
        $fromInput = $request->string('from')->toString();
        $toInput = $request->string('to')->toString();
        $from = $this->muscatBoundary($fromInput, true);
        $to = $this->muscatBoundary($toInput, false);

        $orders = Order::query()
            ->withCount('items')
            ->when($status === 'fulfill', fn ($query) => $query->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing]))
            ->when($status !== '' && $status !== 'fulfill', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'phone' => $order->phone,
                'email' => $order->email,
                'city' => $order->city,
                'governorate_ar' => $order->governorate_ar,
                'governorate_en' => $order->governorate_en,
                'status' => $order->status->value,
                'items_count' => $order->items_count,
                'total' => Money::format($order->total),
                'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]);

        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn (Order $order) => [$order->status->value => (int) $order->aggregate]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'from' => $from ? $fromInput : '',
                'to' => $to ? $toInput : '',
            ],
            'counts' => [
                'all' => (int) $counts->sum(),
                'fulfill' => (int) ($counts[OrderStatus::Paid->value] ?? 0) + (int) ($counts[OrderStatus::Processing->value] ?? 0),
                ...collect($allowed)->mapWithKeys(fn (string $value) => [$value => (int) ($counts[$value] ?? 0)])->all(),
            ],
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

        return back()->with('success', __('store.status_updated', [
            'status' => __('store.statuses.'.$next->value),
        ]));
    }

    private function muscatBoundary(string $value, bool $start): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, 'Asia/Muscat');
        } catch (\Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return ($start ? $date->startOfDay() : $date->endOfDay())->utc();
    }
}
