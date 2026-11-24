import { test as base, expect } from '@playwright/test';

/**
 * Every test fails when the page logs a console error, throws, or requests
 * anything from another origin. The shop promises to work without third-party
 * requests (no font CDN, no trackers, no external avatars) – this guard keeps
 * that promise honest on every page a test visits.
 */
export const test = base.extend({
    page: async ({ page, baseURL }, use) => {
        const problems = [];
        const origin = new URL(baseURL).origin;

        page.on('console', (message) => {
            if (message.type() === 'error') {
                problems.push(`console error: ${message.text()}`);
            }
        });

        page.on('pageerror', (error) => {
            problems.push(`page error: ${error.message}`);
        });

        page.on('request', (request) => {
            const url = request.url();

            if (/^(data|blob|about):/.test(url)) {
                return;
            }

            if (new URL(url).origin !== origin) {
                problems.push(`third-party request: ${url}`);
            }
        });

        await use(page);

        expect(problems, problems.join('\n')).toEqual([]);
    },
});

export { expect };
