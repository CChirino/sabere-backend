<?php

namespace Tests\Feature\Api\V1;

use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminApiResponseTest extends TestCase
{
    public function test_users_list_uses_standard_response(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuarios obtenidos exitosamente')
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total']);
    }

    public function test_user_crud_uses_standard_responses(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Usuario API',
                'email' => 'usuario.api@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => ['teacher'],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario creado exitosamente')
            ->assertJsonPath('data.email', 'usuario.api@example.com');

        $userId = $response->json('data.id');

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/users/{$userId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario obtenido exitosamente');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/users/{$userId}", ['name' => 'Usuario Actualizado'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario actualizado exitosamente')
            ->assertJsonPath('data.name', 'Usuario Actualizado');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/users/{$userId}")
            ->assertNoContent();
    }

    public function test_protected_user_error_uses_standard_response(): void
    {
        $admin = $this->createUser('admin');
        $protectedUser = $this->createUser('admin');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/users/{$protectedUser->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se puede eliminar un usuario con rol de administrador');
    }

    public function test_roles_crud_uses_standard_responses(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/roles?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Roles obtenidos exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/roles', ['name' => 'custom_role'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Rol creado exitosamente')
            ->assertJsonPath('data.name', 'custom_role');

        $roleId = $response->json('data.id');

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/roles/{$roleId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Rol obtenido exitosamente');

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/roles/{$roleId}", ['name' => 'custom_role_updated'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Rol actualizado exitosamente')
            ->assertJsonPath('data.name', 'custom_role_updated');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/roles/{$roleId}")
            ->assertNoContent();
    }

    public function test_protected_role_error_uses_standard_response(): void
    {
        $admin = $this->createUser('admin');
        $role = Role::findByName('admin');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/roles/{$role->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se puede eliminar este rol del sistema');
    }
}
