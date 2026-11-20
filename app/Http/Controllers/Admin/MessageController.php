<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $state = $request->string('state')->toString();

        if (! in_array($state, ['unread', 'read'], true)) {
            $state = '';
        }

        $search = Str::of($request->string('q')->trim()->toString())->limit(80, '')->toString();
        $unread = Message::query()->whereNull('read_at')->count();
        $all = Message::query()->count();

        $messages = Message::query()
            ->when($state === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($state === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Message $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'body' => $message->body,
                'read_at' => $message->read_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
                'created_at' => $message->created_at?->timezone('Asia/Muscat')->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Messages', [
            'messages' => $messages,
            'filters' => [
                'q' => $search,
                'state' => $state,
            ],
            'counts' => [
                'all' => $all,
                'unread' => $unread,
                'read' => $all - $unread,
            ],
        ]);
    }

    public function update(Message $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ?? now()]);

        return back();
    }
}
