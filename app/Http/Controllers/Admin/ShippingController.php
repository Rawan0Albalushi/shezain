<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingController extends Controller
{
    public function edit(): Response
    {
        $rates = ShippingRate::query()->orderBy('sort')->get();
        $activeFees = $rates->where('is_active', true)->pluck('fee');

        return Inertia::render('Admin/Shipping', [
            'rates' => $rates->map(fn (ShippingRate $rate) => [
                'id' => $rate->id,
                'governorate_ar' => $rate->governorate_ar,
                'governorate_en' => $rate->governorate_en,
                'fee' => Money::format($rate->fee),
                'is_active' => $rate->is_active,
            ]),
            'totals' => [
                'count' => $rates->count(),
                'active' => $rates->where('is_active', true)->count(),
                'paused' => $rates->where('is_active', false)->count(),
                'highest' => Money::format((int) ($activeFees->max() ?? 0)),
            ],
        ]);
    }

    public function show(ShippingRate $rate): Response
    {
        return Inertia::render('Admin/Shipping/Show', [
            'rate' => [
                'id' => $rate->id,
                'governorate_ar' => $rate->governorate_ar,
                'governorate_en' => $rate->governorate_en,
                'fee' => Money::format($rate->fee),
                'is_active' => $rate->is_active,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*.id' => ['required', 'integer', 'exists:shipping_rates,id'],
            'rates.*.fee' => ['required', 'regex:/^\d+(\.\d{1,3})?$/'],
            'rates.*.is_active' => ['sometimes', 'boolean'],
        ]);

        foreach ($data['rates'] as $row) {
            ShippingRate::query()->whereKey($row['id'])->update([
                'fee' => Money::fromOmr($row['fee']),
                'is_active' => (bool) ($row['is_active'] ?? false),
            ]);
        }

        return back()->with('success', __('store.saved'));
    }
}
