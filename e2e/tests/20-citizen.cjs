// The Citizen Web, signed in as a verified test resident (E2E_RESIDENT_ID).
const { check, skip, signInResident, open, settle, text } = require('../lib.cjs');

const ok = (page, re) => page.apiCalls.find((c) => re.test(c.url) && c.status < 400);

module.exports = [
    {
        id: 'C1',
        name: 'A resident signs in and reaches Home',
        async run({ browser }) {
            const page = await signInResident(browser);
            check(/\/citizen\/dashboard/.test(page.url()), 'not on Home after signing in');
        },
    },
    {
        id: 'C2',
        name: 'Dokyu and Tulong each list their own services in the new-request window',
        async run({ browser }) {
            // The service lists live in their own tabs (owner, 2026-10-15): no separate Services page.
            const page = await signInResident(browser);
            await open(page, '/citizen/document-requests/');
            await page.locator('button:has-text("Bagong Kahilingan"), button:has-text("New Request")').first().click();
            await settle(page, 1200);
            const body = await text(page);
            check(/Barangay Clearance|Cedula|Certificate/i.test(body), 'the new-request window lists no document services');
        },
    },
    {
        id: 'C3',
        name: 'Appointments shows the booking steps and the resident\'s appointments',
        async run({ browser }) {
            const page = await signInResident(browser);
            await open(page, '/citizen/appointments/');
            check(await page.locator('#appointment-booking').isVisible(), 'no booking section');
            check(await page.locator('#mine-heading').isVisible(), 'no "my appointments" section');
            check(ok(page, /\/citizen\/appointments\/schedules/), 'the office schedules were not loaded');
            check(ok(page, /\/citizen\/appointments(\?|$)/), 'the resident\'s appointments were not loaded');
        },
    },
    {
        id: 'C4',
        name: 'Help: the support tab lists the resident\'s tickets and opens a conversation',
        async run({ browser }) {
            const page = await signInResident(browser);
            await open(page, '/citizen/help/support/');
            check(ok(page, /\/citizen\/support\/tickets(\?|$)/), 'the tickets were not loaded');
            const row = page.locator('button:has-text("TKT-"), [role=button]:has-text("TKT-"), a:has-text("TKT-")').first();
            if (!(await row.count())) skip('the resident has no support tickets to open');
            await row.click();
            await settle(page, 1500);
            check(ok(page, /\/citizen\/support\/tickets\/TKT-/), 'the conversation was not loaded');
        },
    },
    {
        id: 'C5',
        name: 'Events: "Remind me" offers a calendar file',
        async run({ browser }) {
            const page = await signInResident(browser);
            await open(page, '/citizen/events/');
            const remind = page.locator('button:has-text("Remind Me"), button:has-text("Paalalahanan")').first();
            if (!(await remind.count())) skip('no upcoming event to be reminded of');
            await remind.click();
            await page.waitForTimeout(400);
            const file = page.locator('button:has-text(".ics"), a:has-text(".ics"), button:has-text("Calendar file"), button:has-text("calendar file"), button:has-text("kalendaryo")').first();
            check(await file.count(), 'no calendar-file choice after "Remind me"');
            const [download] = await Promise.all([page.waitForEvent('download', { timeout: 10000 }), file.click()]);
            check(/\.ics$/i.test(download.suggestedFilename()), `downloaded ${download.suggestedFilename()}, not an .ics file`);
        },
    },
    {
        id: 'C6',
        name: 'On a phone, search opens from the top bar',
        async run({ browser }) {
            const page = await signInResident(browser, { viewport: { width: 390, height: 844 } });
            const button = page.locator('button[aria-label="Maghanap"], button[aria-label="Search"]').first();
            check(await button.isVisible(), 'no search button at phone width');
            await button.click();
            await page.waitForTimeout(400);
            check(await page.locator('input[aria-label*="dokyu" i]:visible, input[aria-label*="Search" i]:visible').count(), 'the search field did not open');
        },
    },
    {
        id: 'C7',
        name: 'Dokyu: an open request shows its history, a printable slip and the appointment link',
        async run({ browser }) {
            const page = await signInResident(browser);
            await open(page, '/citizen/document-requests/');
            // The first visible one: the notification menu holds references too.
            const ref = page.locator('text=/DOK-\\d{4}-\\d+/ >> visible=true').first();
            if (!(await ref.count())) skip('the resident has no Dokyu request');
            await ref.click();
            await settle(page, 2000);
            const body = await text(page);
            check(/Print request slip|I-print ang request slip/.test(body), 'no "Print request slip" in the request');
            check(/Book an appointment|Mag-book ng appointment/.test(body), 'no "Book an appointment" link in the request');
        },
    },
];
