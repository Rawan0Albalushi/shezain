<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale', 'ar');
        App::setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}
