<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => Setting::content(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $keys = array_keys(Setting::content());
        $rules = [];

        foreach ($keys as $key) {
            $rules[$key] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        return back()->with('success', __('store.saved'));
    }
}
