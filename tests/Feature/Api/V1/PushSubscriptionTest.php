<?php

namespace Tests\Feature\Api\V1;

use App\Models\PushSubscription;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    public function test_user_can_register_push_subscription(): void
    {
        $user = $this->createUser('student');

        $this->actingAs($user)
            ->postJson('/api/v1/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
                'keys' => [
                    'p256dh' => 'p256dh-key',
                    'auth' => 'auth-token',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Suscripción registrada exitosamente')
            ->assertJsonPath('data.endpoint', 'https://fcm.googleapis.com/fcm/send/test-123');

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
        ]);
    }

    public function test_user_can_delete_push_subscription(): void
    {
        $user = $this->createUser('student');

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
            'p256dh_key' => 'p256dh',
            'auth_token' => 'auth',
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Suscripción eliminada exitosamente');

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
        ]);
    }
}
