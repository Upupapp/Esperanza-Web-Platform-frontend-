#!/usr/bin/env node
// Runs the browser end-to-end checks (e2e/tests/*.cjs) in Chromium against a
// deployed website and its API. See e2e/README.md.
//
//   node e2e/run.cjs            every check
//   node e2e/run.cjs verify     only checks whose id or name contains "verify"
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { config, opened, Skip, shot } = require('./lib.cjs');

(async () => {
    const filter = (process.argv[2] || '').toLowerCase();
    const dir = path.join(__dirname, 'tests');
    const cases = fs.readdirSync(dir).filter((f) => f.endsWith('.cjs')).sort()
        .flatMap((f) => require(path.join(dir, f)));
    const chosen = cases.filter((c) => !filter || `${c.id} ${c.name}`.toLowerCase().includes(filter));
    console.log(`Esperanza end-to-end checks: ${config.site}`);
    console.log(`${chosen.length} of ${cases.length} checks${config.write ? ', including those that create data' : ''}\n`);

    const browser = await chromium.launch({ headless: !config.headed });
    const tally = { PASS: 0, FAIL: 0, SKIP: 0 };
    for (const c of chosen) {
        const started = Date.now();
        let status = 'PASS';
        let note = '';
        opened.length = 0;
        if (c.writes && !config.write) {
            status = 'SKIP';
            note = 'creates data: set E2E_WRITE=1 to run it';
        } else {
            try {
                await c.run({ browser });
                const allowed = c.allow || [];
                const problems = opened.flatMap((p) => p.problems || []).filter((x) => !allowed.some((re) => re.test(x)));
                if (problems.length) throw new Error([...new Set(problems)].slice(0, 4).join(' | '));
            } catch (e) {
                if (e instanceof Skip) {
                    status = 'SKIP';
                    note = e.message;
                } else {
                    status = 'FAIL';
                    note = String(e.message || e).split('\n')[0].slice(0, 300);
                    const last = opened[opened.length - 1];
                    if (last) note += `  [screenshot: ${path.relative(process.cwd(), await shot(last, c.id))}]`;
                }
            }
        }
        for (const p of opened) await p.context().close().catch(() => {});
        tally[status]++;
        const secs = ((Date.now() - started) / 1000).toFixed(1);
        console.log(`${status}  ${c.id.padEnd(5)} ${c.name}${status === 'PASS' ? ` (${secs}s)` : ''}${note ? `\n        ${note}` : ''}`);
    }
    await browser.close();
    console.log(`\n${tally.PASS} passed, ${tally.FAIL} failed, ${tally.SKIP} skipped`);
    process.exit(tally.FAIL ? 1 : 0);
})().catch((e) => {
    console.error(e);
    process.exit(2);
});
