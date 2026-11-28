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
    // A run takes six to eight minutes. On CI one that takes three times as long
    // is stuck: it ends, and says how far it got, instead of running for hours.
    globalTimeout: process.env.CI ? 25 * 60_000 : 0,
    // Where the site itself is broken, every test fails, many of them by waiting
    // half a minute. Ten failures say as much as three hundred, and say it sooner.
    maxFailures: process.env.CI ? 10 : 0,
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
        // Never a server left over from an earlier run: the tests are written for
        // a site that was built for them. If the port is taken, the run stops and says so.
        reuseExistingServer: false,
        // The first run downloads WordPress and the plugins.
        timeout: 300_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
