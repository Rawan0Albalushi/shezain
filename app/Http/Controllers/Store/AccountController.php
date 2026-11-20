<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Support\Catalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function orders(Request $request): Response
    {
        $orders = $request->user()->orders()->latest()->paginate(10)->through(fn (Order $order) => [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'total' => Money::format($order->total),
            'created_at' => $order->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Store/Account/Orders', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items');

        return Inertia::render('Store/Account/Order', [
            'order' => Catalog::order($order),
        ]);
    }

    public function addresses(Request $request): Response
    {
        return Inertia::render('Store/Account/Addresses', [
            'addresses' => $request->user()->addresses()->with('shippingRate')->latest()->get()->map(fn (Address $address) => [
                'id' => $address->id,
                'label' => $address->label,
                'name' => $address->name,
                'phone' => $address->phone,
                'governorate_ar' => $address->shippingRate->governorate_ar,
                'governorate_en' => $address->shippingRate->governorate_en,
                'city' => $address->city,
                'address_line' => $address->address_line,
                'is_default' => $address->is_default,
            ]),
            'rates' => ShippingRate::query()->where('is_active', true)->orderBy('sort')->get(['id', 'governorate_ar', 'governorate_en']),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'shipping_rate_id' => ['required', 'integer', 'exists:shipping_rates,id'],
            'city' => ['required', 'string', 'max:120'],
            'address_line' => ['required', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_default') || $request->user()->addresses()->doesntExist()) {
            $request->user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $request->user()->addresses()->create($data);

        return back()->with('success', __('store.saved'));
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('success', __('store.saved'));
    }
}
