<?php

namespace Tests\Feature\Console;

use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class SchoolProvisionTest extends TestCase
{
    public function test_provision_creates_settings_and_admin(): void
    {
        $this->artisan('school:provision', [
            '--name' => 'Colegio Test',
            '--rif' => 'J-12345678-0',
            '--code' => 'DEA-001',
            '--admin-email' => 'admin@colegiotest.com',
            '--admin-password' => 'securepass123',
            '--no-interaction' => true,
        ])->assertSuccessful();

        // Verificar settings actualizados
        $this->assertEquals('Colegio Test', Setting::get('institution.name'));
        $this->assertEquals('J-12345678-0', Setting::get('institution.rif'));
        $this->assertEquals('DEA-001', Setting::get('institution.dea_code'));

        // Verificar usuario admin creado
        $admin = User::where('email', 'admin@colegiotest.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_provision_skips_existing_admin(): void
    {
        // Pre-crear usuario admin
        $this->createUser('admin', ['email' => 'admin@sabere.com']);

        $this->artisan('school:provision', [
            '--name' => 'Colegio Test',
            '--admin-email' => 'admin@sabere.com',
            '--admin-password' => 'whatever',
            '--no-interaction' => true,
        ])->assertSuccessful();

        // Solo debe haber 1 usuario con ese email
        $this->assertEquals(1, User::where('email', 'admin@sabere.com')->count());
    }
}
