<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Store/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
            'email' => app()->getLocale() === 'ar'
                ? 'البريد أو كلمة المرور غير صحيحة.'
                : 'The email or password is incorrect.',
        ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->isAdmin() ? route('admin.dashboard') : route('account.orders'));
    }

    public function register(): Response
    {
        return Inertia::render('Store/Register');
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            ...$data,
            'role' => 'customer',
        ]);

        Auth::login($user);

        return redirect()->route('account.orders')->with('success', __('store.account_created'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
