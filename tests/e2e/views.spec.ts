import { test, expect } from '@playwright/test';

/**
 * Tests de fumée E2E — Nouveaux Layouts & Vues EMSP Docs
 */

test.describe('Vues EMSP Docs @smoke', () => {
  test('La bibliothèque /documents est accessible', async ({ page }) => {
    const response = await page.goto('/documents');
    expect(response?.status()).toBe(200);
    await expect(page.locator('.docs-workspace-root')).toBeVisible();
  });

  test('La page de dépôt /upload réclame une connexion ou s\'affiche', async ({ page }) => {
    const response = await page.goto('/upload');
    expect([200, 302]).toContain(response?.status());
  });

  test('La page mon profil /mon-profil est protégée ou accessible', async ({ page }) => {
    const response = await page.goto('/mon-profil');
    expect([200, 302]).toContain(response?.status());
  });

  test('L\'historique /historique est protégé ou accessible', async ({ page }) => {
    const response = await page.goto('/historique');
    expect([200, 302]).toContain(response?.status());
  });
});
