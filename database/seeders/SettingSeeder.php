<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['group' => 'institution', 'key' => 'name', 'value' => 'Unidad Educativa Sabere', 'type' => 'string', 'is_public' => true, 'description' => 'Nombre oficial del plantel'],
            ['group' => 'institution', 'key' => 'short_name', 'value' => 'Sabere', 'type' => 'string', 'is_public' => true, 'description' => 'Nombre corto del plantel'],
            ['group' => 'institution', 'key' => 'rif', 'value' => '', 'type' => 'string', 'is_public' => false, 'description' => 'RIF del plantel'],
            ['group' => 'institution', 'key' => 'dea_code', 'value' => '', 'type' => 'string', 'is_public' => false, 'description' => 'Código DEA o código plantel'],
            ['group' => 'institution', 'key' => 'address', 'value' => '', 'type' => 'string', 'is_public' => true, 'description' => 'Dirección del plantel'],
            ['group' => 'institution', 'key' => 'phone', 'value' => '', 'type' => 'string', 'is_public' => true, 'description' => 'Teléfono de contacto'],
            ['group' => 'institution', 'key' => 'email', 'value' => '', 'type' => 'string', 'is_public' => true, 'description' => 'Correo institucional'],

            ['group' => 'academic', 'key' => 'min_passing_score', 'value' => 10, 'type' => 'integer', 'is_public' => false, 'description' => 'Nota mínima aprobatoria'],
            ['group' => 'academic', 'key' => 'term_count', 'value' => 3, 'type' => 'integer', 'is_public' => false, 'description' => 'Número de lapsos por año escolar'],
            ['group' => 'academic', 'key' => 'qualitative_levels', 'value' => ['A' => 'Sobresaliente', 'B' => 'Notable', 'C' => 'Bien', 'D' => 'Suficiente', 'E' => 'Insuficiente'], 'type' => 'json', 'is_public' => false, 'description' => 'Niveles cualitativos para primaria'],

            ['group' => 'branding', 'key' => 'primary_color', 'value' => '#2563eb', 'type' => 'string', 'is_public' => true, 'description' => 'Color primario del plantel'],
            ['group' => 'branding', 'key' => 'secondary_color', 'value' => '#1e40af', 'type' => 'string', 'is_public' => true, 'description' => 'Color secundario del plantel'],
            ['group' => 'branding', 'key' => 'logo_path', 'value' => '', 'type' => 'file', 'is_public' => true, 'description' => 'Logo del plantel'],
            ['group' => 'branding', 'key' => 'seal_path', 'value' => '', 'type' => 'file', 'is_public' => false, 'description' => 'Sello digital del plantel'],
            ['group' => 'branding', 'key' => 'director_signature_path', 'value' => '', 'type' => 'file', 'is_public' => false, 'description' => 'Firma digitalizada del director'],

            ['group' => 'pwa', 'key' => 'short_name', 'value' => 'Sabere', 'type' => 'string', 'is_public' => true, 'description' => 'Nombre corto para la PWA'],
            ['group' => 'pwa', 'key' => 'theme_color', 'value' => '#2563eb', 'type' => 'string', 'is_public' => true, 'description' => 'Color del tema PWA'],
            ['group' => 'pwa', 'key' => 'background_color', 'value' => '#ffffff', 'type' => 'string', 'is_public' => true, 'description' => 'Color de fondo del splash PWA'],

            ['group' => 'backup', 'key' => 'retention_days', 'value' => 30, 'type' => 'integer', 'is_public' => false, 'description' => 'Días de retención de backups automáticos'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }
    }
}
