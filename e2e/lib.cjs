// Shared pieces of the browser end-to-end checks (see e2e/README.md).
const fs = require('node:fs');
const path = require('node:path');

const env = (name, fallback = '') => (process.env[name] ?? fallback).trim();

const config = {
    site: env('E2E_SITE', 'https://esperanza-web-platform-frontend.netlify.app').replace(/\/$/, ''),
    staffId: env('E2E_STAFF_ID'),
    residentId: env('E2E_RESIDENT_ID'),
    password: env('E2E_PASSWORD'),
    write: env('E2E_WRITE') === '1',
    headed: env('E2E_HEADED') === '1',
    out: path.resolve(__dirname, env('E2E_OUT', 'results')),
};

// Every page a check opens, so the runner can read their problems and close them.
const opened = [];

class Skip extends Error {}
const skip = (why) => { throw new Skip(why); };

function check(ok, message) {
    if (!ok) throw new Error(message);
}

/**
 * A page that records what went wrong while a check ran: script errors,
 * console errors and API answers of 500 or more. A check fails on any of
 * them unless it says it expects it.
 */
async function newPage(browser, { viewport = { width: 1366, height: 860 }, offline = false } = {}) {
    const context = await browser.newContext({ viewport, acceptDownloads: true });
    if (offline) await context.setOffline(true);
    const page = await context.newPage();
    opened.push(page);
    page.problems = [];
    page.apiCalls = [];
    page.on('pageerror', (e) => page.problems.push('script error: ' + String(e.message).slice(0, 200)));
    page.on('console', (m) => {
        if (m.type() !== 'error') return;
        const text = m.text();
        // A refused request is reported by the response handler below.
        if (/Failed to load resource/.test(text)) return;
        page.problems.push('console error: ' + text.slice(0, 200));
    });
    page.on('response', (r) => {
        if (!/\/api\/v1\//.test(r.url())) return;
        const call = `${r.request().method()} ${r.url().replace(/^.*\/api\/v1/, '').split('?')[0]} ${r.status()}`;
        page.apiCalls.push({ call, url: r.url(), status: r.status() });
        if (r.status() >= 500) page.problems.push('API ' + call);
    });
    page.on('dialog', (d) => d.dismiss().catch(() => {}));
    return page;
}

/** Close the pop-ups the resident site opens by itself (alerts, banners, greetings). */
async function closePopups(page) {
    for (let i = 0; i < 3; i++) {
        const close = page.locator('[role=dialog]:visible button[aria-label*="lose" i], [role=dialog]:visible button[aria-label*="Isara" i]').first();
        if (await close.count()) await close.click().catch(() => {});
        await page.keyboard.press('Escape').catch(() => {});
        await page.waitForTimeout(300);
    }
}

async function settle(page, ms = 1500) {
    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(ms);
}

async function signInResident(browser, opts = {}) {
    if (!config.residentId || !config.password) skip('E2E_RESIDENT_ID and E2E_PASSWORD are not set');
    const page = await newPage(browser, opts);
    await page.goto(config.site + '/citizen/login/', { waitUntil: 'load', timeout: 60000 });
    await settle(page, 500);
    await page.fill('input[name=identifier]', config.residentId);
    await page.fill('#password', config.password);
    await page.locator('form button[type=submit]:visible').first().click();
    await page.waitForURL(/\/citizen\/dashboard/, { timeout: 60000 });
    await settle(page);
    await closePopups(page);
    return page;
}

async function signInStaff(browser, id = config.staffId, opts = {}) {
    if (!id || !config.password) skip('E2E_STAFF_ID and E2E_PASSWORD are not set');
    const page = await newPage(browser, opts);
    await page.goto(config.site + '/admin/login/', { waitUntil: 'load', timeout: 60000 });
    await settle(page, 500);
    await page.fill('input[name=employee_id]', id);
    await page.fill('#password', config.password);
    await page.locator('form button[type=submit]:visible').click();
    const outcome = await Promise.race([
        page.waitForURL(/\/admin\/dashboard/, { timeout: 60000 }).then(() => 'in'),
        page.locator('input[name=mfa_code]').waitFor({ state: 'visible', timeout: 60000 }).then(() => 'code'),
    ]).catch(() => 'stuck');
    if (outcome === 'code') skip(`${id} needs an emailed sign-in code; use a test account that does not`);
    check(outcome === 'in', `${id} did not reach the dashboard after signing in`);
    await settle(page);
    return page;
}

/** Go to a page of the signed-in site (the session is kept in the browser). */
async function open(page, pathName) {
    await page.goto(config.site + pathName, { waitUntil: 'load', timeout: 60000 });
    await settle(page);
}

async function shot(page, name) {
    fs.mkdirSync(config.out, { recursive: true });
    const file = path.join(config.out, name.replace(/[^a-z0-9-]+/gi, '-').toLowerCase() + '.png');
    await page.screenshot({ path: file, fullPage: false }).catch(() => {});
    return file;
}

/** Visible text of the main area, for checks that read what a person reads. */
async function text(page) {
    return page.locator('body').innerText().catch(() => '');
}

module.exports = { config, opened, check, skip, Skip, newPage, closePopups, settle, signInResident, signInStaff, open, shot, text };
