import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './e2e/tests',
  webServer: {
    command: 'corepack pnpm dev --host 127.0.0.1',
    reuseExistingServer: true,
    url: 'http://127.0.0.1:5173',
  },
  use: {
    baseURL: 'http://127.0.0.1:5173',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'mobile',
      use: { ...devices['Pixel 7'] },
    },
  ],
})
