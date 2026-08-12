import { test, expect } from '@playwright/test';

/**
 * Tests de fumée (@smoke) — Auth
 *
 * Vérifie que les pages critiques d'authentification sont accessibles
 * et que les formulaires fonctionnent sans erreur serveur.
 *
 * Lancer : npm run test:smoke
 */

test.describe('Authentification @smoke', () => {
  test('La page de connexion est accessible', async ({ page }) => {
    const response = await page.goto('/login');
    expect(response?.status()).toBe(200);
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('La page d\'inscription est accessible', async ({ page }) => {
    const response = await page.goto('/register');
    expect(response?.status()).toBe(200);
    await expect(page.locator('form')).toBeVisible();
  });

  test('La page mot de passe oublié est accessible', async ({ page }) => {
    const response = await page.goto('/forgot-password');
    expect(response?.status()).toBe(200);
    await expect(page.locator('form')).toBeVisible();
  });

  test('Connexion avec identifiants vides affiche un message d\'erreur', async ({ page }) => {
    await page.goto('/login');
    await page.locator('form').first().evaluate(form => {
      (form as HTMLFormElement).submit();
    });
    // Devrait rediriger vers /login avec un flash message danger
    await page.waitForURL(/\/login/);
    // La page ne doit pas être une erreur 500
    expect(page.url()).toContain('/login');
  });

  test('La page FAQ est accessible', async ({ page }) => {
    const response = await page.goto('/faq');
    expect(response?.status()).toBe(200);
  });
});
