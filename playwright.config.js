import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  timeout: 30_000,
  retries: 0,
  use: {
    baseURL: 'http://127.0.0.1:8010',
    browserName: 'chromium',
    viewport: { width: 1440, height: 1000 },
    launchOptions: process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : {},
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'sh scripts/browser-server.sh',
    url: 'http://127.0.0.1:8010/login',
    reuseExistingServer: false,
    gracefulShutdown: { signal: 'SIGTERM', timeout: 10000 },
    timeout: 60000,
  },
});
