import { test, expect } from '@playwright/test';

// Credenciales del UserSeeder (php artisan db:seed)
const ADMIN_EMAIL = 'admin@sabere.com';
const ADMIN_PASSWORD = 'password';

test.describe('Autenticación', () => {
    test('la raíz redirige al login', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole('heading', { name: 'Inicia sesión' })).toBeVisible();
    });

    test('el admin puede iniciar sesión y acceder al dashboard', async ({ page }) => {
        await page.goto('/login');

        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Ingresar' }).click();

        await expect(page).toHaveURL(/\/dashboard$/);
    });

    test('credenciales inválidas muestran error y no acceden', async ({ page }) => {
        await page.goto('/login');

        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Contraseña').fill('contrasena-incorrecta');
        await page.getByRole('button', { name: 'Ingresar' }).click();

        await expect(page).toHaveURL(/\/login$/);
    });
});
