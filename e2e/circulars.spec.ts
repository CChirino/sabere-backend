import { test, expect } from '@playwright/test';

// Credenciales del UserSeeder (php artisan db:seed)
const ADMIN_EMAIL = 'admin@sabere.com';
const ADMIN_PASSWORD = 'password';

test.describe('Gestión de Circulares', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Ingresar' }).click();
        await page.waitForURL('**/dashboard');
    });

    test('el botón Nueva Circular abre el formulario', async ({ page }) => {
        await page.goto('/admin/circulars');

        await page.getByRole('button', { name: '+ Nueva Circular' }).click();

        await expect(page.getByRole('heading', { name: 'Nueva Circular' })).toBeVisible();
        await expect(page.getByText('Título *')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Crear', exact: true })).toBeVisible();
    });
});
