<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
use App\Models\User;
use App\Policies\DirectMessagePolicy;
use App\Services\WebPushService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $messages = DirectMessage::where('recipient_id', $request->user()->id)
            ->with(['sender'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return response()->json($messages);
    }

    public function sent(Request $request): JsonResponse
    {
        $messages = DirectMessage::where('sender_id', $request->user()->id)
            ->with(['recipient'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return response()->json($messages);
    }

    public function show(Request $request, DirectMessage $message): JsonResponse
    {
        $this->authorize('view', $message);

        if ($request->user()->id === $message->recipient_id && ! $message->read_at) {
            $message->markAsRead();
        }

        $message->load(['sender', 'recipient', 'replies']);

        return response()->json($message);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:direct_messages,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $recipient = User::findOrFail($data['recipient_id']);

        if (! app(DirectMessagePolicy::class)->canMessage($request->user(), $recipient)) {
            return response()->json(['message' => 'No puedes enviar mensajes a este destinatario.'], 403);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('message-attachments', 'public');
        }

        $message = DirectMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipient->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $message->load(['sender', 'recipient']);

        // Enviar push notification
        $title = $data['subject'] ?? 'Nuevo mensaje de '.$request->user()->name;
        $bodyPreview = strlen($data['body']) > 80 ? substr($data['body'], 0, 80).'...' : $data['body'];
        app(WebPushService::class)->sendToUser(
            $recipient,
            'message',
            $title,
            $bodyPreview,
            url("/messages/{$message->id}")
        );

        return response()->json($message, 201);
    }

    public function markAsRead(Request $request, DirectMessage $message): JsonResponse
    {
        $this->authorize('view', $message);

        if ($request->user()->id === $message->recipient_id) {
            $message->markAsRead();
        }

        return response()->json($message);
    }

    public function recipients(Request $request): JsonResponse
    {
        $user = $request->user();
        $policy = app(DirectMessagePolicy::class);

        $recipients = User::where('id', '!=', $user->id)
            ->where(function (Builder $query) use ($user) {
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'director', 'coordinator', 'teacher']);
                });

                if ($user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher'])) {
                    $query->orWhereHas('roles', function ($q) {
                        $q->where('name', 'guardian');
                    });
                }
            })
            ->get()
            ->filter(fn (User $u) => $policy->canMessage($user, $u))
            ->values();

        return response()->json($recipients);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = DirectMessage::where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
