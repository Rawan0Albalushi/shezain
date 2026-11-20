<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        abort_unless(in_array($locale, ['ar', 'en'], true), 422);

        return back()->cookie('locale', $locale, 60 * 24 * 365);
    }
}
