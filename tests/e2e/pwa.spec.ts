import { test, expect } from '@playwright/test';

test.describe('PWA contract @smoke', () => {
  test('manifest, start_url and scope are valid', async ({ page, baseURL }) => {
    await page.goto('/login');
    const href = await page.locator('link[rel="manifest"]').getAttribute('href');
    expect(href).toBeTruthy();

    const manifestUrl = new URL(href!, page.url()).toString();
    const response = await page.request.get(manifestUrl);
    expect(response.status()).toBe(200);

    const manifest = await response.json();
    expect(manifest.start_url).toBe('./');
    expect(manifest.scope).toBe('./');
  });

  test('Service Worker and pwa.js are exposed', async ({ page }) => {
    await page.goto('/login');
    const pwaHref = await page.locator('script[src*="assets/js/pwa.js"]').getAttribute('src');
    expect(pwaHref).toBeTruthy();

    const pwaResponse = await page.request.get(new URL(pwaHref!, page.url()).toString());
    expect(pwaResponse.status()).toBe(200);
    expect(pwaResponse.headers()['content-type']).toContain('javascript');

    const swUrl = new URL('sw.js', page.url()).toString();
    const swResponse = await page.request.get(swUrl);
    expect(swResponse.status()).toBe(200);
    expect(swResponse.headers()['content-type']).toContain('javascript');
  });

  test('layout exposes manifest and PWA runtime without legacy DB dependency', async ({ page }) => {
    const response = await page.goto('/login');
    expect(response?.status()).toBe(200);
    await expect(page.locator('link[rel="manifest"]')).toHaveCount(1);
    await expect(page.locator('script[src*="assets/js/pwa.js"]')).toHaveCount(1);
  });
});


test.describe('PWA runtime @smoke', () => {
  test('Service Worker registers successfully on the public shell', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'networkidle' });
    const registration = await page.evaluate(async () => {
      if (!('serviceWorker' in navigator)) return null;
      const reg = await navigator.serviceWorker.ready;
      return {
        scope: reg.scope,
        hasActiveWorker: !!reg.active,
      };
    });

    expect(registration).not.toBeNull();
    expect(registration?.hasActiveWorker).toBe(true);
    expect(registration?.scope).toContain(new URL('.', page.url()).pathname);
  });
});
