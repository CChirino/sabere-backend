<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\GradeSubjectSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\SubjectAreaSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TermSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SchoolProvisionCommand extends Command
{
    protected $signature = 'school:provision
                            {--name= : Nombre del plantel}
                            {--rif= : RIF del plantel}
                            {--code= : Código DEA/plantel}
                            {--admin-email= : Email del administrador}
                            {--admin-password= : Contraseña del administrador}
                            {--demo : Incluir datos de demostración}';

    protected $description = 'Provisionar una nueva instancia de Sabere para un colegio';

    public function handle(): int
    {
        $this->info('Provisionando nueva instancia de Sabere...');
        $this->newLine();

        // 1. Ejecutar migraciones
        $this->info('Ejecutando migraciones...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('Migraciones completadas.');

        // 2. Ejecutar seeders base
        $this->info('Configurando datos base...');
        $this->callSeeders();
        $this->info('Datos base configurados.');

        // 3. Solicitar datos del plantel
        $name = $this->option('name')
            ?? ($this->input->isInteractive() ? $this->ask('Nombre del plantel', 'Unidad Educativa') : 'Unidad Educativa');
        $rif = $this->option('rif')
            ?? ($this->input->isInteractive() ? $this->ask('RIF del plantel (dejar vacío si no tiene)', '') : '');
        $code = $this->option('code')
            ?? ($this->input->isInteractive() ? $this->ask('Código DEA/plantel (dejar vacío si no tiene)', '') : '');

        // 4. Actualizar settings
        Setting::set('institution.name', $name);
        Setting::set('institution.short_name', $name);
        if ($rif) {
            Setting::set('institution.rif', $rif);
        }
        if ($code) {
            Setting::set('institution.dea_code', $code);
        }

        $this->info("Plantel configurado: {$name}");

        // 5. Crear usuario admin
        $this->createAdminUser();

        // 6. Demo data si se solicita
        if ($this->option('demo')) {
            $this->info('Creando datos de demostración...');
            $this->call('db:seed', ['--class' => TermSeeder::class, '--force' => true]);
            $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
            $this->info('Datos de demostración creados.');
        }

        // 7. Generar APP_KEY si no existe
        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('APP_KEY generada.');
        }

        $this->newLine();
        $this->info('Instancia provisionada exitosamente.');

        return self::SUCCESS;
    }

    private function callSeeders(): void
    {
        $seeders = [
            PermissionSeeder::class,
            RoleSeeder::class,
            EducationLevelSeeder::class,
            GradeSeeder::class,
            SubjectAreaSeeder::class,
            SubjectSeeder::class,
            GradeSubjectSeeder::class,
            SettingSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }
    }

    private function createAdminUser(): void
    {
        $email = $this->option('admin-email')
            ?? ($this->input->isInteractive() ? $this->ask('Email del administrador', 'admin@sabere.com') : 'admin@sabere.com');

        if (User::where('email', $email)->exists()) {
            $this->warn("El usuario {$email} ya existe, omitiendo creación.");

            return;
        }

        $password = $this->option('admin-password')
            ?? ($this->input->isInteractive() ? $this->secret('Contraseña del administrador (mínimo 8 caracteres)') : null);

        if (! $password) {
            $password = 'password';
            $this->warn('Se usará la contraseña por defecto: "password". Cámbiela lo antes posible.');
        }

        $user = User::create([
            'name' => 'Administrador',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        $this->info("Usuario administrador creado: {$email}");
    }
}
