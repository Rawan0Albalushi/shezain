<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Messages', [
            'messages' => Message::query()->latest()->paginate(20)->through(fn (Message $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'body' => $message->body,
                'read_at' => $message->read_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
                'created_at' => $message->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function update(Message $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ?? now()]);

        return back();
    }
}
