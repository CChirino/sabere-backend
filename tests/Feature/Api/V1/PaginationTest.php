<?php

namespace Tests\Feature\Api\V1;

use App\Models\DirectMessage;
use App\Models\User;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    public function test_users_list_uses_default_per_page(): void
    {
        User::factory()->count(30)->create();
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('per_page', 15);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_users_list_respects_custom_per_page_and_limits_maximum(): void
    {
        User::factory()->count(60)->create();
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/users?per_page=5')
            ->assertOk()
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 13);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/users?per_page=100')
            ->assertOk()
            ->assertJsonPath('per_page', 50);
    }

    public function test_messages_list_is_paginated(): void
    {
        $sender = $this->createUser('teacher');
        $recipient = $this->createUser('guardian');

        DirectMessage::factory()->count(25)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->actingAs($recipient)
            ->getJson(route('api.messages.index'))
            ->assertOk()
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('last_page', 2);
    }
}
