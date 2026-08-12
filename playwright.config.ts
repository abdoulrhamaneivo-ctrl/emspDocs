import { defineConfig, devices } from '@playwright/test';

/**
 * EMSP Docs — Configuration Playwright
 *
 * Utilisation :
 *   npm test              → lance tous les tests E2E
 *   npm run test:smoke    → lance uniquement les tests tagués @smoke
 *
 * Pré-requis :
 *   Le serveur PHP doit tourner avant de lancer les tests :
 *     php -S localhost:8000 -t . router.php
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [['html', { open: 'never' }]],

  use: {
    baseURL: process.env.EMSP_BASE_URL || 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  projects: [
    {
      name: 'desktop-chrome',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 7'] },
    },
  ],
});
