// The Admin Portal, signed in as a Super Admin test account (E2E_STAFF_ID).
const { check, skip, signInStaff, open, settle, text } = require('../lib.cjs');

const answered = (page, re) => page.apiCalls.find((c) => re.test(c.url));
function loaded(page, re, what) {
    const call = answered(page, re);
    check(call, `${what} was not asked for`);
    check(call.status < 400, `${what} answered ${call.status}`);
}

module.exports = [
    {
        id: 'S1',
        name: 'An officer signs in and reaches the Dashboard',
        async run({ browser }) {
            const page = await signInStaff(browser);
            check(/\/admin\/dashboard/.test(page.url()), 'not on the Dashboard after signing in');
        },
    },
    {
        id: 'S2',
        name: 'Dashboard > Appointments shows the day\'s bookings and the schedules',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/dashboard/appointments/');
            loaded(page, /\/admin\/appointments(\?|$)/, 'the bookings');
            check(/Bookings/.test(await text(page)), 'no Bookings section');
            await page.locator('button[role=tab]:has-text("Schedules")').first().click().catch(() => {});
            await settle(page, 1200);
            loaded(page, /\/admin\/appointment-schedules/, 'the schedules');
        },
    },
    {
        id: 'S3',
        name: 'Dokyu: the Overdue and Assigned-to-me filters ask the server',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/document-requests/');
            await page.locator('[aria-label="Show only"] button:has-text("Overdue")').first().click();
            await settle(page, 1500);
            loaded(page, /\/admin\/dokyu\/requests\?.*overdue=1/, 'the overdue list');
            await page.locator('[aria-label="Show only"] button:has-text("Assigned to me")').first().click();
            await settle(page, 1500);
            loaded(page, /\/admin\/dokyu\/requests\?.*assigned=me/, 'the "assigned to me" list');
        },
    },
    {
        id: 'S4',
        name: 'Tulong: the queue loads with its filters',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/assistance-requests/');
            loaded(page, /\/admin\/tulong\/requests/, 'the Tulong queue');
            check(await page.locator('[aria-label="Show only"] button:has-text("Overdue")').count(), 'no Overdue filter on the Tulong queue');
        },
    },
    {
        id: 'S5',
        name: 'Communications > Feedback lists the tickets',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/communications/feedback/');
            loaded(page, /\/admin\/support\/tickets(\?|$)/, 'the ticket queue');
        },
    },
    {
        id: 'S6',
        name: 'Settings > Service Catalog lists every service',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/settings/service-catalog/');
            loaded(page, /\/admin\/settings\/catalog(\?|$)/, 'the catalog');
        },
    },
    {
        id: 'S7',
        name: 'Settings > Document Templates lists the templates',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/settings/templates/');
            loaded(page, /\/admin\/settings\/templates(\?|$)/, 'the templates');
        },
    },
    {
        id: 'S8',
        name: 'Settings > System shows health, the scheduler and the last backup',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/settings/system/');
            loaded(page, /\/admin\/system\/status/, 'the system status');
            const body = await text(page);
            check(/Background scheduler/.test(body), 'no scheduler card');
            check(/backup/i.test(body), 'no backup card');
        },
    },
    {
        id: 'S9',
        name: 'Settings > Audit Logs exports the filtered log as CSV',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/settings/audit-logs/');
            loaded(page, /\/admin\/audit-logs/, 'the audit log');
            const button = page.locator('button:has-text("Export CSV")').first();
            if (!(await button.isVisible())) skip('this account cannot export the audit log');
            const [download] = await Promise.all([page.waitForEvent('download', { timeout: 30000 }), button.click()]);
            check(/\.csv$/i.test(download.suggestedFilename()), `downloaded ${download.suggestedFilename()}, not a .csv`);
        },
    },
    {
        id: 'S10',
        name: 'User Management > Departments lists the offices',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/users/departments/');
            loaded(page, /\/admin\/offices(\?|$)/, 'the offices');
        },
    },
    {
        id: 'S11',
        name: 'Constituents: the data requests open from Citizen Verification',
        async run({ browser }) {
            const page = await signInStaff(browser);
            await open(page, '/admin/constituents/');
            // Citizen Verification is the "To Verify" tab.
            await page.locator('button:has-text("To Verify")').first().click();
            await settle(page, 1200);
            const view = page.locator('button:has-text("Data requests")').first();
            check(await view.count(), 'no "Data requests" view in Citizen Verification');
            await view.click();
            await settle(page, 1500);
            loaded(page, /\/admin\/data-requests/, 'the data requests');
        },
    },
];
