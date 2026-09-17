<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    public function test_user_can_get_and_update_notification_preferences(): void
    {
        $user = $this->createUser('student');

        $this->actingAs($user)
            ->getJson(route('notification-preferences.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Preferencias de notificación obtenidas exitosamente')
            ->assertJsonPath('data.user_id', $user->id);

        $this->actingAs($user)
            ->putJson(route('notification-preferences.update'), [
                'push_scores' => false,
                'push_messages' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Preferencias de notificación actualizadas exitosamente')
            ->assertJsonPath('data.push_scores', false)
            ->assertJsonPath('data.push_messages', false);
    }
}
