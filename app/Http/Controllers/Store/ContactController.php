<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        $settings = Setting::content();

        return Inertia::render('Store/Contact', [
            'phone' => $settings['phone'],
            'email' => $settings['email'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        Message::query()->create($data);

        return back()->with('success', __('store.sent'));
    }
}
