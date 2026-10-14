// Public pages: no sign-in.
const { config, check, newPage, settle, text } = require('../lib.cjs');

module.exports = [
    {
        id: 'P1',
        name: 'The portal chooser links to the public Verify page',
        async run({ browser }) {
            const page = await newPage(browser);
            await page.goto(config.site + '/', { waitUntil: 'load', timeout: 60000 });
            await settle(page, 800);
            const link = page.locator('a[href*="/verify"]').first();
            check(await link.count(), 'no link to /verify on the portal chooser');
            await link.click();
            await page.waitForURL(/\/verify/, { timeout: 30000 });
            await settle(page, 800);
            check(await page.locator('#verify-code').isVisible(), 'the Verify page shows no code field');
        },
    },
    {
        id: 'P2',
        name: 'Verify answers "not found" for an invented code, without signing in',
        async run({ browser }) {
            const page = await newPage(browser);
            await page.goto(config.site + '/verify/?code=NOTAREALCODE1', { waitUntil: 'load', timeout: 60000 });
            await settle(page, 2500);
            const call = page.apiCalls.find((c) => /\/verify\//.test(c.url));
            check(call, 'the page did not ask the API about the code from the QR link');
            check(call.status === 404, `the API answered ${call.status} for an invented code, not 404`);
            check(/not found|hindi natagpuan|walang/i.test(await text(page)), 'no "not found" message on the page');
        },
    },
    {
        id: 'P3',
        name: 'A dropped connection shows the offline notice, and its return clears it',
        async run({ browser }) {
            const page = await newPage(browser);
            await page.goto(config.site + '/verify/', { waitUntil: 'load', timeout: 60000 });
            await settle(page, 800);
            await page.context().setOffline(true);
            await page.waitForSelector('.offline-bar', { timeout: 5000 });
            check(/internet|connection|koneksyon/i.test(await page.locator('.offline-bar').innerText()), 'the offline notice says nothing');
            await page.context().setOffline(false);
            await page.waitForFunction(() => {
                const bar = document.querySelector('.offline-bar');
                return !bar || bar.dataset.state === 'online';
            }, null, { timeout: 5000 });
        },
    },
];
