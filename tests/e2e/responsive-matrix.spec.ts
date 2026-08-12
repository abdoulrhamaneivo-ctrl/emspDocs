import { test, expect } from '@playwright/test';

const viewports = [
  [320, 568], [360, 800], [375, 812], [390, 844], [414, 896],
  [430, 932], [600, 960], [768, 1024], [820, 1180], [1024, 768],
  [1280, 720], [1366, 768], [1440, 900], [1600, 900], [1920, 1080],
];

test.describe('Responsive matrix — Lot 10.2', () => {
  test('critical public shell has no horizontal overflow at target viewports', async ({ page }) => {
    test.slow();

    for (const [width, height] of viewports) {
      await test.step(`${width}x${height}`, async () => {
        await page.setViewportSize({ width, height });
        const response = await page.goto('/login', { waitUntil: 'networkidle' });
        expect(response?.status()).toBe(200);

        await expect(page.locator('body')).toBeVisible();
        await expect(page.locator('main#main-content-anchor')).toBeVisible();
        await expect(page.locator('meta[name="viewport"]')).toHaveAttribute(
          'content',
          /width=device-width/i,
        );

        const layout = await page.evaluate(() => ({
          viewportWidth: window.innerWidth,
          documentWidth: document.documentElement.scrollWidth,
          bodyWidth: document.body.scrollWidth,
          horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1
            || document.body.scrollWidth > window.innerWidth + 1,
        }));

        expect(layout.horizontalOverflow, `${width}x${height}: horizontal overflow detected`).toBe(false);
        expect(layout.documentWidth).toBeLessThanOrEqual(layout.viewportWidth + 1);
        expect(layout.bodyWidth).toBeLessThanOrEqual(layout.viewportWidth + 1);

        await page.screenshot({
          path: `test-results/lot-10.2/${width}x${height}/login.png`,
          fullPage: true,
        });
      });
    }
  });
});
