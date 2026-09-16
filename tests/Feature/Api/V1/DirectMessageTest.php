<?php

namespace Tests\Feature\Api\V1;

use App\Models\DirectMessage;
use Tests\TestCase;

class DirectMessageTest extends TestCase
{
    public function test_user_can_send_message_to_allowed_recipient(): void
    {
        $admin = $this->createUser('admin');
        $guardian = $this->createUser('guardian');

        $this->actingAs($admin)
            ->postJson(route('api.messages.store'), [
                'recipient_id' => $guardian->id,
                'subject' => 'Consulta',
                'body' => 'Mensaje de prueba',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('direct_messages', [
            'sender_id' => $admin->id,
            'recipient_id' => $guardian->id,
            'subject' => 'Consulta',
        ]);
    }

    public function test_user_cannot_send_message_to_unrelated_recipient(): void
    {
        $teacher = $this->createUser('teacher');
        $guardian = $this->createUser('guardian');

        $this->actingAs($teacher)
            ->postJson(route('api.messages.store'), [
                'recipient_id' => $guardian->id,
                'subject' => 'Consulta',
                'body' => 'Mensaje de prueba',
            ])
            ->assertForbidden();
    }

    public function test_user_can_list_received_messages(): void
    {
        $sender = $this->createUser('teacher');
        $recipient = $this->createUser('guardian');

        DirectMessage::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'subject' => 'Hola',
            'body' => 'Contenido',
        ]);

        $this->actingAs($recipient)
            ->getJson(route('api.messages.index'))
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Hola');
    }

    public function test_recipient_can_mark_message_as_read(): void
    {
        $sender = $this->createUser('teacher');
        $recipient = $this->createUser('guardian');

        $message = DirectMessage::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->actingAs($recipient)
            ->postJson(route('api.messages.read', $message))
            ->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_unread_count_returns_correct_value(): void
    {
        $sender = $this->createUser('teacher');
        $recipient = $this->createUser('guardian');

        DirectMessage::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'read_at' => null,
        ]);

        $this->actingAs($recipient)
            ->getJson(route('api.messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 3);
    }
}
