import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 90_000,
  expect: {
    timeout: 10_000,
  },
  fullyParallel: false,
  retries: 0,
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
  ],
  use: {
    baseURL: 'http://127.0.0.1:5173',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  webServer: [
    {
      command: 'C:\\xampp\\php\\php.exe -S 127.0.0.1:8010 -t public public/index.php',
      cwd: '../backend',
      port: 8010,
      reuseExistingServer: true,
      timeout: 120_000,
    },
    {
      command: 'C:\\Program Files\\nodejs\\npm.cmd run dev -- --host=127.0.0.1 --port=5173',
      cwd: '.',
      port: 5173,
      reuseExistingServer: true,
      timeout: 120_000,
    },
  ],
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
