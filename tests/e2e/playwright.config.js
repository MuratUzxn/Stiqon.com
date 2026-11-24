import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://127.0.0.1:8123';

/**
 * End-to-end tests run against the PHP built-in server with a fresh SQLite
 * database (see serve.sh). Two projects: a desktop and a phone viewport.
 */
export default defineConfig({
    testDir: '.',
    testMatch: /.*\.spec\.js$/,
    outputDir: '../../test-results',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    forbidOnly: !!process.env.CI,
    reporter: [['list']],
    use: {
        baseURL,
        locale: 'de-DE',
        timezoneId: 'Europe/Berlin',
        trace: 'off',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'desktop',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
        },
        {
            name: 'mobile',
            use: {
                browserName: 'chromium',
                viewport: { width: 390, height: 844 },
                isMobile: true,
                hasTouch: true,
            },
        },
    ],
    webServer: {
        command: 'bash tests/e2e/serve.sh',
        cwd: '../..',
        url: `${baseURL}/up`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
        stdout: 'ignore',
        stderr: 'ignore',
    },
});
