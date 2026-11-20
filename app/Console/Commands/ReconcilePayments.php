<?php

namespace App\Console\Commands;

use App\Actions\ReleaseOrderStock;
use App\Actions\SettleThawaniPayment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Thawani\ThawaniException;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Confirm or release Thawani payments that never returned to the store';

    public function handle(SettleThawaniPayment $settle, ReleaseOrderStock $release): int
    {
        Order::query()
            ->with(['payments' => fn ($query) => $query->latest()])
            ->where('status', OrderStatus::PendingPayment)
            ->where('created_at', '<=', now()->subMinutes(10))
            ->each(function (Order $order) use ($settle, $release) {
                $payment = $order->payments->first();

                if ($payment?->session_id) {
                    try {
                        $settle->handle($payment);
                        $order->refresh();
                    } catch (ThawaniException $exception) {
                        $this->warn($order->number.': '.$exception->getMessage());

                        return;
                    }
                }

                if ($order->status === OrderStatus::PendingPayment && $order->created_at?->lt(now()->subMinutes(70))) {
                    $release->handle($order, OrderStatus::Cancelled);
                    $this->info($order->number.' cancelled and stock released.');
                }
            });

        return self::SUCCESS;
    }
}
