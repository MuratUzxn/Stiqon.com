import { fileURLToPath } from 'node:url';
import AxeBuilder from '@axe-core/playwright';
import { test as base, expect } from '@playwright/test';

/** Absolute path of a file in tests/fixtures. */
export function fixture(name) {
    return fileURLToPath(new URL(`../fixtures/${name}`, import.meta.url));
}

/**
 * Every test fails when the page logs a console error, throws, or requests
 * anything from another origin. The shop promises to work without third-party
 * requests (no font CDN, no trackers, no external avatars) – this guard keeps
 * that promise honest on every page a test visits. Violations of the Content
 * Security Policy show up as console errors, so they fail a test as well.
 *
 * A test that opens an error page on purpose lists the expected status codes:
 *
 *     test.use({ expectedStatuses: [404] });
 */
export const test = base.extend({
    expectedStatuses: [[], { option: true }],

    page: async ({ page, baseURL, expectedStatuses }, use) => {
        const problems = [];
        const origin = new URL(baseURL).origin;

        page.on('console', (message) => {
            if (message.type() !== 'error') {
                return;
            }

            const status = /status of (\d{3})/.exec(message.text());

            if (status && expectedStatuses.includes(Number(status[1]))) {
                return;
            }

            problems.push(`console error: ${message.text()}`);
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

/**
 * Runs axe against the current page and returns the violations in a form that
 * reads well in a failure message. Target: WCAG 2.2 AA.
 */
export async function accessibilityViolations(page) {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();

    return results.violations.map((violation) => ({
        rule: violation.id,
        impact: violation.impact,
        help: violation.help,
        nodes: violation.nodes.map((node) => node.target.join(' ')),
    }));
}

export { expect };
