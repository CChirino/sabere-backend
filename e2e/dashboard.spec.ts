import { test, expect } from '@playwright/test';

// Credenciales del UserSeeder (php artisan db:seed)
const ADMIN_EMAIL = 'admin@sabere.com';
const ADMIN_PASSWORD = 'password';

test.describe('Dashboard de administrador', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Ingresar' }).click();
        await page.waitForURL('**/dashboard');
    });

    test('muestra el menú completo del rol admin', async ({ page }) => {
        await expect(page.getByRole('link', { name: 'Usuarios' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Roles' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Notificaciones Masivas' })).toBeVisible();
    });

    test('muestra las estadísticas del sistema', async ({ page }) => {
        // El dashboard de admin recibe stats (total_users, total_students, ...)
        await expect(page.getByText('Administrador', { exact: false }).first()).toBeVisible();
        await expect(page.locator('main')).not.toBeEmpty();
    });

    test('puede abrir Notificaciones Masivas', async ({ page }) => {
        await page.goto('/admin/bulk-notifications');
        await expect(page.locator('main')).not.toBeEmpty();
        await expect(page.getByText('Internal Server Error')).toHaveCount(0);
    });
});
