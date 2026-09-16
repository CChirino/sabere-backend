<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use Tests\TestCase;

class SettingTest extends TestCase
{
    public function test_admin_can_access_settings_page(): void
    {
        $this->withoutVite();

        $admin = $this->createUser('admin');

        Setting::firstOrCreate(
            ['group' => 'institution', 'key' => 'name'],
            ['value' => 'Test School', 'type' => 'string', 'is_public' => true]
        );

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_director_can_update_setting(): void
    {
        $director = $this->createUser('director');

        Setting::firstOrCreate(
            ['group' => 'institution', 'key' => 'name'],
            ['value' => 'Old', 'type' => 'string', 'is_public' => true]
        );

        $this->actingAs($director)
            ->put(route('admin.settings.update'), [
                'group' => 'institution',
                'key' => 'name',
                'value' => 'New Name',
                'type' => 'string',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'institution',
            'key' => 'name',
            'value' => '"New Name"',
        ]);
    }

    public function test_coordinator_cannot_update_setting(): void
    {
        $coordinator = $this->createUser('coordinator');

        Setting::firstOrCreate(
            ['group' => 'institution', 'key' => 'name'],
            ['value' => 'Old', 'type' => 'string', 'is_public' => true]
        );

        $this->actingAs($coordinator)
            ->put(route('admin.settings.update'), [
                'group' => 'institution',
                'key' => 'name',
                'value' => 'New Name',
                'type' => 'string',
            ])
            ->assertForbidden();
    }
}
