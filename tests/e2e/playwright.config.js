import { defineConfig, devices } from '@playwright/test';

// E2E_PORT lets a second checkout run its own server next to a running preview.
const baseURL = `http://127.0.0.1:${process.env.E2E_PORT ?? '8123'}`;

/**
 * Browser tests run against a WordPress site that is built from scratch for
 * each run (see server.sh): WordPress, WooCommerce and Germanized in the pinned
 * versions, with this repository's theme and plugin. Two projects: a desktop
 * and a phone viewport.
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
        command: 'bash tests/e2e/server.sh',
        cwd: '../..',
        url: `${baseURL}/`,
        reuseExistingServer: !process.env.CI,
        // The first run downloads WordPress and the plugins.
        timeout: 300_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
