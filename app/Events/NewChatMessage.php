<?php

namespace App\Events;

use App\Models\SectionChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;
    public int $sectionId;

    /**
     * Create a new event instance.
     */
    public function __construct(SectionChatMessage $chatMessage)
    {
        $this->sectionId = $chatMessage->section_id;
        $this->message = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'type' => $chatMessage->type,
            'attachment_url' => $chatMessage->attachment_url,
            'attachment_name' => $chatMessage->attachment_name,
            'user' => [
                'id' => $chatMessage->user->id,
                'name' => $chatMessage->user->name,
            ],
            'created_at' => $chatMessage->created_at->format('Y-m-d H:i:s'),
            'time' => $chatMessage->created_at->format('H:i'),
            'date' => $chatMessage->created_at->format('d/m/Y'),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('section-chat.' . $this->sectionId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
