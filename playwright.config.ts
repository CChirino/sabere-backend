import { defineConfig, devices } from '@playwright/test';

/**
 * Configuración de tests E2E con Playwright.
 * Requiere el servidor corriendo (composer dev o php artisan serve --port=5500)
 * y la base de datos con seeders (php artisan db:seed).
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:5500',
        trace: 'on-first-retry',
        locale: 'es-VE',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
