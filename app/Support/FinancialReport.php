<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class FinancialReport
{
    /**
     * Confirmed sales follow the admin desk: paid, processing, shipped, and delivered.
     * Dates are the order's creation time, read on the Asia/Muscat calendar.
     *
     * @return array<string, mixed>
     */
    public static function make(?Carbon $from, ?Carbon $to): array
    {
        $paid = self::paidStatuses();
        $confirmed = self::totals($paid, $from, $to);
        $awaiting = self::totals([OrderStatus::PendingPayment->value], $from, $to);
        $rows = self::rows($paid, $from, $to);

        $orders = (int) $confirmed->orders_count;
        $total = (int) $confirmed->total;
        $subtotal = (int) $confirmed->subtotal;
        [$grain, $series] = self::series($rows, $from, $to);

        return [
            'grain' => $grain,
            'summary' => [
                'orders' => $orders,
                'subtotal' => Money::format($subtotal),
                'shipping' => Money::format((int) $confirmed->shipping),
                'total' => Money::format($total),
                'average' => Money::format($orders > 0 ? (int) round($total / $orders) : 0),
                'awaiting_orders' => (int) $awaiting->orders_count,
                'awaiting_total' => Money::format((int) $awaiting->total),
            ],
            'series' => $series,
            'governorates' => self::governorates($paid, $from, $to, $total),
            'products' => self::products($paid, $from, $to, $subtotal),
            'orders' => self::recent($paid, $from, $to),
        ];
    }

    /**
     * @return list<string>
     */
    private static function paidStatuses(): array
    {
        return array_values(array_map(
            fn (OrderStatus $status) => $status->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $status) => $status->isPaid()),
        ));
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function constrain(Builder $query, array $statuses, ?Carbon $from, ?Carbon $to): Builder
    {
        return $query
            ->whereIn('status', $statuses)
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to));
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function totals(array $statuses, ?Carbon $from, ?Carbon $to): object
    {
        return self::constrain(Order::query(), $statuses, $from, $to)
            ->toBase()
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(shipping_fee), 0) as shipping')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->first();
    }

    /**
     * @param  list<string>  $statuses
     * @return Collection<int, Order>
     */
    private static function rows(array $statuses, ?Carbon $from, ?Carbon $to): Collection
    {
        return self::constrain(Order::query(), $statuses, $from, $to)->get(['created_at', 'total']);
    }

    /**
     * Day buckets stay readable for a month. Longer spans use calendar months.
     * Grouping happens in PHP so Muscat midnight is the same on SQLite and MySQL.
     *
     * @param  Collection<int, Order>  $rows
     * @return array{0: string, 1: list<array<string, mixed>>}
     */
    private static function series(Collection $rows, ?Carbon $from, ?Carbon $to): array
    {
        if ($from && $to) {
            $start = $from->copy()->timezone('Asia/Muscat')->startOfDay();
            $end = $to->copy()->timezone('Asia/Muscat')->startOfDay();
        } elseif ($rows->isNotEmpty()) {
            $start = Carbon::createFromTimestamp((int) $rows->min(fn (Order $order) => $order->created_at->getTimestamp()), 'UTC')
                ->timezone('Asia/Muscat')
                ->startOfDay();
            $end = Carbon::createFromTimestamp((int) $rows->max(fn (Order $order) => $order->created_at->getTimestamp()), 'UTC')
                ->timezone('Asia/Muscat')
                ->startOfDay();
        } else {
            return ['day', []];
        }

        $grain = (int) $start->diffInDays($end, true) > 62 ? 'month' : 'day';

        if ($rows->isEmpty()) {
            return [$grain, []];
        }

        $cursor = $grain === 'month' ? $start->copy()->startOfMonth() : $start->copy();
        $last = $grain === 'month' ? $end->copy()->startOfMonth() : $end->copy();
        $points = [];

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $grain === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $points[$key] = [
                'key' => $key,
                'label' => self::label($cursor, $grain),
                'orders' => 0,
                'total' => 0,
            ];
            $grain === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        foreach ($rows as $row) {
            $local = $row->created_at->copy()->timezone('Asia/Muscat');
            $key = $grain === 'month' ? $local->format('Y-m') : $local->format('Y-m-d');

            if (! isset($points[$key])) {
                continue;
            }

            $points[$key]['orders']++;
            $points[$key]['total'] += (int) $row->total;
        }

        if (count($points) > 48) {
            $points = array_filter($points, fn (array $point) => $point['orders'] > 0);
        }

        $max = max(array_column($points, 'total') ?: [0]);

        $series = array_map(function (array $point) use ($max) {
            $share = $max > 0 ? (int) round($point['total'] / $max * 100) : 0;

            if ($point['orders'] > 0 && $share < 2) {
                $share = 2;
            }

            $point['share'] = $share;
            $point['total'] = Money::format($point['total']);

            return $point;
        }, array_values($points));

        return [$grain, $series];
    }

    /**
     * @param  list<string>  $statuses
     * @return list<array<string, mixed>>
     */
    private static function governorates(array $statuses, ?Carbon $from, ?Carbon $to, int $total): array
    {
        return self::constrain(Order::query(), $statuses, $from, $to)
            ->toBase()
            ->select('governorate_ar', 'governorate_en')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupBy('governorate_ar', 'governorate_en')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (object $row) => [
                'name_ar' => $row->governorate_ar,
                'name_en' => $row->governorate_en,
                'orders' => (int) $row->orders_count,
                'total' => Money::format((int) $row->revenue),
                'share' => $total > 0 ? (int) round(((int) $row->revenue) / $total * 100) : 0,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $statuses
     * @return list<array<string, mixed>>
     */
    private static function products(array $statuses, ?Carbon $from, ?Carbon $to, int $subtotal): array
    {
        $orders = self::constrain(Order::query(), $statuses, $from, $to)->select('id');

        return OrderItem::query()
            ->select('name_ar', 'name_en')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('COALESCE(SUM(line_total), 0) as revenue')
            ->whereIn('order_id', $orders)
            ->groupBy('name_ar', 'name_en')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn (OrderItem $item) => [
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'quantity' => (int) $item->quantity,
                'total' => Money::format((int) $item->revenue),
                'share' => $subtotal > 0 ? (int) round(((int) $item->revenue) / $subtotal * 100) : 0,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $statuses
     * @return list<array<string, mixed>>
     */
    private static function recent(array $statuses, ?Carbon $from, ?Carbon $to): array
    {
        return self::constrain(Order::query(), $statuses, $from, $to)
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ])
            ->all();
    }

    private static function label(Carbon $date, string $grain): string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $months = [
            'ar' => [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'],
            'en' => [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'],
        ];
        $month = $months[$locale][$date->month];

        return $grain === 'month' ? $month.' '.$date->year : $date->day.' '.$month;
    }
}
