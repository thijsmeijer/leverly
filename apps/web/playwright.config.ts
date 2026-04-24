import { defineConfig, devices } from '@playwright/test'

const port = process.env.LEVERLY_PLAYWRIGHT_PORT ?? '4173'
const baseURL = `http://127.0.0.1:${port}`

export default defineConfig({
  testDir: './e2e/tests',
  webServer: {
    command: `corepack pnpm dev --host 127.0.0.1 --port ${port} --strictPort`,
    reuseExistingServer: true,
    url: baseURL,
  },
  use: {
    baseURL,
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
