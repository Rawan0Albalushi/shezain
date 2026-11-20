<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Thawani\ThawaniClient;
use App\Services\Thawani\ThawaniException;

class StartThawaniCheckout
{
    public function __construct(
        private readonly ThawaniClient $thawani,
        private readonly SettleThawaniPayment $settle,
    ) {}

    public function handle(Order $order, bool $renew = false): string
    {
        $existing = $order->payments()->where('status', 'unpaid')->latest()->first();

        if ($existing?->session_id) {
            try {
                $this->settle->handle($existing);
            } catch (ThawaniException) {
                throw new ThawaniException('Thawani could not be reached to confirm the existing payment.');
            }

            $order->refresh();

            if ($order->status->isPaid()) {
                return route('checkout.return', $order->public_id);
            }

            $sessionIsOld = $existing->created_at !== null && $existing->created_at->lt(now()->subMinutes(65));

            if (! $renew || ! $sessionIsOld) {
                return $this->thawani->checkoutUrl($existing->session_id);
            }
        }

        $order->loadMissing('items');

        $products = $order->items->map(fn ($item) => [
            'name' => $item->name_ar,
            'quantity' => $item->quantity,
            'unit_amount' => $item->unit_price,
        ])->all();

        if ($order->shipping_fee > 0) {
            $products[] = [
                'name' => 'الشحن',
                'quantity' => 1,
                'unit_amount' => $order->shipping_fee,
            ];
        }

        $session = $this->thawani->createSession([
            'client_reference_id' => $order->number,
            'mode' => 'payment',
            'products' => $products,
            'success_url' => route('checkout.return', $order->public_id),
            'cancel_url' => route('checkout.cancel', $order->public_id),
            'expire_in_minutes' => 60,
            'metadata' => [
                'Customer name' => $order->customer_name,
                'Customer email' => $order->email,
                'Customer phone' => $order->phone,
                'order id' => $order->number,
            ],
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'thawani',
            'session_id' => $session['session_id'],
            'client_reference' => $order->number,
            'amount_baisa' => $order->total,
            'status' => 'unpaid',
            'payload' => $session,
        ]);

        return $this->thawani->checkoutUrl($session['session_id']);
    }
}
