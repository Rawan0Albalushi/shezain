<?php

namespace App\Http\Controllers\Store;

use App\Actions\PlaceOrder;
use App\Actions\ReleaseOrderStock;
use App\Actions\SettleThawaniPayment;
use App\Actions\StartThawaniCheckout;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\Cart;
use App\Services\Thawani\ThawaniException;
use App\Support\Catalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function create(Cart $cart): Response|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $user = Auth::user();

        return Inertia::render('Store/Checkout', [
            'lines' => $lines,
            'subtotal' => Money::format($lines->sum('line_total')),
            'subtotal_baisa' => $lines->sum('line_total'),
            'rates' => ShippingRate::query()->where('is_active', true)->orderBy('sort')->get()->map(fn (ShippingRate $rate) => [
                'id' => $rate->id,
                'governorate_ar' => $rate->governorate_ar,
                'governorate_en' => $rate->governorate_en,
                'fee' => $rate->fee,
                'fee_formatted' => Money::format($rate->fee),
            ]),
            'addresses' => $user?->addresses()->with('shippingRate')->latest()->get()->map(fn ($address) => [
                'id' => $address->id,
                'label' => $address->label,
                'name' => $address->name,
                'phone' => $address->phone,
                'governorate_id' => $address->shipping_rate_id,
                'city' => $address->city,
                'address_line' => $address->address_line,
            ]) ?? [],
        ]);
    }

    public function store(
        CheckoutRequest $request,
        Cart $cart,
        PlaceOrder $placeOrder,
        StartThawaniCheckout $checkout,
        ReleaseOrderStock $release,
    ): HttpResponse {
        $user = $request->user();

        if ($request->boolean('create_account') && $user === null) {
            $user = User::query()->create([
                'name' => $request->string('customer_name')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->string('phone')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => 'customer',
            ]);
            Auth::login($user);
        }

        $order = $placeOrder->handle($user, $request->validated(), $cart->lines());

        try {
            $url = $checkout->handle($order);
        } catch (ThawaniException) {
            $release->handle($order, OrderStatus::PaymentFailed);

            return back()->with('error', __('store.payment_unavailable'));
        }

        $cart->clear();

        return Inertia::location($url);
    }

    public function returned(Order $order, SettleThawaniPayment $settle): Response
    {
        $this->confirm($order, $settle);

        return Inertia::render('Store/OrderStatus', [
            'order' => Catalog::order($order->fresh(['items'])),
            'outcome' => 'return',
        ]);
    }

    public function cancelled(Order $order, SettleThawaniPayment $settle, ReleaseOrderStock $release): Response
    {
        $this->confirm($order, $settle);
        $order->refresh();

        if ($order->status === OrderStatus::PendingPayment) {
            $release->handle($order, OrderStatus::Cancelled);
            $order->refresh();
        }

        return Inertia::render('Store/OrderStatus', [
            'order' => Catalog::order($order->load('items')),
            'outcome' => 'cancel',
        ]);
    }

    public function pay(Order $order, StartThawaniCheckout $checkout): HttpResponse
    {
        abort_unless($order->status === OrderStatus::PendingPayment, 404);

        try {
            $url = $checkout->handle($order, renew: true);
        } catch (ThawaniException) {
            return back()->with('error', __('store.payment_unavailable'));
        }

        return Inertia::location($url);
    }

    private function confirm(Order $order, SettleThawaniPayment $settle): void
    {
        $payment = $order->payments()->latest()->first();

        if ($payment?->session_id && $order->status === OrderStatus::PendingPayment) {
            try {
                $settle->handle($payment);
            } catch (ThawaniException) {
                // The return page stays pending until Thawani can be reached.
            }
        }
    }
}
