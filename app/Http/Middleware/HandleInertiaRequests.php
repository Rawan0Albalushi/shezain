<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Message;
use App\Models\Setting;
use App\Services\Cart;
use App\Services\Thawani\ThawaniClient;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user ? [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_admin' => $user->isAdmin(),
                ] : null,
            ],
            'cartCount' => fn () => app(Cart::class)->count(),
            'categories' => fn () => Category::query()->orderBy('sort')->get(['slug', 'name_ar', 'name_en']),
            'settings' => fn () => Setting::content(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'thawaniSandbox' => fn () => app(ThawaniClient::class)->isSandbox(),
            'adminNav' => function () use ($request, $user) {
                if (! $user?->isAdmin() || ! $request->is('admin', 'admin/*')) {
                    return null;
                }

                return [
                    'unread' => Message::query()->whereNull('read_at')->count(),
                ];
            },
        ];
    }
}
