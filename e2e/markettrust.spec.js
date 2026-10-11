
import { test, expect } from '@playwright/test';

test('website MarketTrust dapat dibuka', async ({ page }) => {
  const response = await page.goto('http://127.0.0.1:8000');

  expect(response.status()).toBe(200);
});