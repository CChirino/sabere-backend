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
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mensaje enviado exitosamente')
            ->assertJsonPath('data.subject', 'Consulta');

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
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No puedes enviar mensajes a este destinatario.');
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
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mensajes recibidos obtenidos exitosamente')
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
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mensaje marcado como leído exitosamente')
            ->assertJsonPath('data.id', $message->id);

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
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cantidad de mensajes no leídos obtenida exitosamente')
            ->assertJsonPath('data.count', 3);
    }

    public function test_sent_show_and_recipients_use_standard_responses(): void
    {
        $admin = $this->createUser('admin');
        $guardian = $this->createUser('guardian');
        $message = DirectMessage::factory()->create([
            'sender_id' => $admin->id,
            'recipient_id' => $guardian->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('api.messages.sent', ['per_page' => 5]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mensajes enviados obtenidos exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('data.0.id', $message->id);

        $this->actingAs($admin)
            ->getJson(route('api.messages.show', $message))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mensaje obtenido exitosamente')
            ->assertJsonPath('data.id', $message->id);

        $this->actingAs($admin)
            ->getJson(route('api.messages.recipients'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Destinatarios obtenidos exitosamente');
    }
}
