<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Thawani\ThawaniClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SettleThawaniPayment
{
    public function __construct(private readonly ThawaniClient $thawani) {}

    public function handle(Payment $payment): Order
    {
        $session = $this->thawani->retrieveSession($payment->session_id);

        return DB::transaction(function () use ($payment, $session) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($order->status->isPaid()) {
                return $order;
            }

            $paid = ($session['payment_status'] ?? null) === 'paid';
            $amountMatches = (int) ($session['total_amount'] ?? -1) === (int) $order->total
                && (int) $lockedPayment->amount_baisa === (int) $order->total;
            $referenceMatches = (string) ($session['client_reference_id'] ?? '') === $order->number;

            if (! $paid || ! $amountMatches || ! $referenceMatches) {
                $lockedPayment->update(['payload' => $session]);

                if (! $amountMatches || ! $referenceMatches) {
                    Log::warning('Thawani session did not match the order.', [
                        'order' => $order->number,
                        'session_id' => $lockedPayment->session_id,
                    ]);
                }

                return $order;
            }

            $wasPaid = $lockedPayment->status === 'paid';

            $lockedPayment->update([
                'status' => 'paid',
                'paid_at' => $lockedPayment->paid_at ?? now(),
                'payload' => $session,
            ]);

            $order->update(['status' => OrderStatus::Paid]);

            if (! $wasPaid && $order->email) {
                DB::afterCommit(function () use ($order) {
                    try {
                        Mail::to($order->email)->send(new OrderPaid($order->fresh(['items'])));
                    } catch (\Throwable $exception) {
                        Log::warning('Order confirmation email failed.', [
                            'order' => $order->number,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                });
            }

            return $order->fresh();
        });
    }
}
