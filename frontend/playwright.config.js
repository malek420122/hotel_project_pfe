import { defineConfig, devices } from '@playwright/test';

const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:5173';

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
    baseURL: baseUrl,
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
      command: 'cmd /c "\"C:\\Program Files\\nodejs\\npm.cmd\" run dev"',
      cwd: '.',
      url: baseUrl,
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
