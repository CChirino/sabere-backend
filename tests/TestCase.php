<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->seeded) {
            Artisan::call('db:seed', ['--class' => PermissionSeeder::class]);
            Artisan::call('db:seed', ['--class' => RoleSeeder::class]);
            $this->seeded = true;
        }
    }

    protected function createUser(string $role, array $attributes = []): User
    {
        return User::factory()->withRole($role)->create($attributes);
    }

    protected function actingAsRole(string $role, array $attributes = []): User
    {
        $user = $this->createUser($role, $attributes);

        return $this->actingAs($user);
    }
}
