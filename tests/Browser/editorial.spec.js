import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('public reading, registration, email verification and language selection', async ({ page }) => {
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'Stories worth sharing.' })).toBeVisible();
  await expect(page.getByText('No articles have been published yet.')).toBeVisible();

  const accessibility = await new AxeBuilder({ page }).analyze();
  expect(accessibility.violations.filter(v => ['critical', 'serious'].includes(v.impact))).toEqual([]);

  await page.goto('/register');
  await page.getByLabel('Name', { exact: true }).fill('Browser Test');
  await page.getByLabel('Email address').fill('browser-' + Date.now() + '@example.test');
  await page.getByLabel('Password (at least 12 characters)').fill('browser-test-password-123');
  await page.getByLabel('Confirm password').fill('browser-test-password-123');
  await page.getByRole('button', { name: 'Create an account' }).click();
  await expect(page).toHaveURL(/\/email\/verify$/);

  await page.goto('/dashboard');
  await expect(page).toHaveURL(/\/email\/verify$/);

  await page.goto('/');
  await page.getByLabel('Language').selectOption('de');
  await expect(page.locator('html')).toHaveAttribute('lang', 'de');
  await expect(page.getByRole('heading', { name: 'Geschichten, die es wert sind, geteilt zu werden.' })).toBeVisible();
});

test('verified editor can create a draft and preview Markdown', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('editor-browser@example.test');
  await page.getByLabel('Password', { exact: true }).fill('browser-test-password-123');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/dashboard$/);

  await page.goto('/posts/create');
  await page.getByLabel('Title', { exact: true }).fill('Browser editorial draft');
  await expect(page.getByLabel('URL slug')).toHaveValue('browser-editorial-draft');
  await page.getByLabel('Content', { exact: true }).fill('Important **reviewed** content.');
  await page.getByRole('button', { name: 'Preview', exact: true }).click();
  await expect(page.locator('#markdown-preview strong')).toHaveText('reviewed');
  await page.getByRole('button', { name: 'Save draft' }).click();
  await expect(page).toHaveURL(/\/posts\/\d+\/edit$/);
  await expect(page.getByText('Draft created.')).toBeVisible();
});
