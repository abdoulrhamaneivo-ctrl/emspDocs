import { test, expect } from '@playwright/test';

test.describe('Security contracts @smoke', () => {
  test('logout is not exposed as a GET mutation', async ({ request, baseURL }) => {
    const response = await request.get(new URL('/logout', baseURL).toString(), {
      maxRedirects: 0,
    });
    expect([404, 405]).toContain(response.status());
  });

  test('logout POST requires CSRF', async ({ request, baseURL }) => {
    const response = await request.post(new URL('/logout', baseURL).toString(), {
      maxRedirects: 0,
      form: {},
    });
    expect(response.status()).toBe(302);
    expect(response.headers()['location']).toMatch(/login/);
  });
});
