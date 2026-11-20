<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Support\Money;
use App\Support\SitePages;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $page): Response
    {
        $content = SitePages::find($page);
        abort_unless($content !== null, 404);

        return Inertia::render('Store/Page', [
            'page' => $content,
            'rates' => $page === 'shipping'
                ? ShippingRate::query()->where('is_active', true)->orderBy('sort')->get()->map(fn (ShippingRate $rate) => [
                    'governorate_ar' => $rate->governorate_ar,
                    'governorate_en' => $rate->governorate_en,
                    'fee' => Money::format($rate->fee),
                ])
                : [],
        ]);
    }
}
