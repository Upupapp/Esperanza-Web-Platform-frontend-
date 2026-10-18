/**
 * Esperanza Web Platform - accessibility gate (2026-10-17).
 *
 * Every page of the BUILT site (dist/, as the gate exports it), opened in
 * headless Chrome, checked two ways:
 *
 *   1. axe-core, the WCAG 2.0/2.1/2.2 A and AA rules (tags wcag2a, wcag2aa,
 *      wcag21a, wcag21aa, wcag22aa). A serious or critical violation fails.
 *   2. The keyboard, for real: Tab through the page (up to MAX_TABS presses),
 *      and at each stop check that focus moved, that the focused element is on
 *      screen and visibly marked (an outline, a ring or a change of look), and
 *      that the first Tab reaches a "skip to content" link where the page has
 *      one main region. Shift+Tab must come back. Escape must close an open
 *      dialog (checked on pages that open one).
 *
 * Offline and repeatable: the page and its assets come from dist/ (the
 * export's gate.invalid origin is answered from the folder), the API is
 * answered with small sample data (an approved resident, a Super Admin, empty
 * lists), and every other host is refused. A page is judged on its own markup
 * and behaviour, not on whatever staging held that day.
 *
 *   node scripts/a11y.mjs                 # after the gate's export
 *   A11Y_ONLY=/citizen/profile/ node scripts/a11y.mjs
 *   A11Y_REPORT=1 node scripts/a11y.mjs   # every violation, not only failing ones
 *
 * Exit 1 when any page fails. A keyboard-only and screen-reader pass by a
 * person is still needed for what a script cannot judge (whether the order
 * and the announcements make sense): see docs/ACCESSIBILITY.md.
 */
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const REPO = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DIST = path.join(REPO, 'dist');
const CDP_PORT = Number(process.env.A11Y_CDP_PORT || 9334);
const WIDTH = Number(process.env.A11Y_WIDTH || 1280);
const HEIGHT = Number(process.env.A11Y_HEIGHT || 800);
const MAX_TABS = Number(process.env.A11Y_TABS || 30);
const ONLY = process.env.A11Y_ONLY || '';
const REPORT_ALL = process.env.A11Y_REPORT === '1';
const ORIGIN = 'http://gate.invalid';
const AXE = path.join(REPO, 'node_modules', 'axe-core', 'axe.min.js');
const FAIL_IMPACTS = ['serious', 'critical'];

const CHROME_CANDIDATES = [
  process.env.CHROME,
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
].filter(Boolean);

const TYPES = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.svg': 'image/svg+xml', '.webp': 'image/webp',
  '.ico': 'image/x-icon', '.woff2': 'font/woff2', '.woff': 'font/woff', '.pdf': 'application/pdf',
};

// ------------------------------------------------------------ Sample data

const RESIDENT = {
  account_no: 'ESP-RES-2026-9999', first_name: 'Audit', middle_name: null, last_name: 'Resident', suffix: null,
  name: 'Audit Resident', email: 'audit@esperanza.test', mobile: '09170000000', barangay: 'Poblacion',
  purok: null, address: 'Poblacion, Esperanza', birthdate: '1990-01-01', sex: 'Female', civil_status: 'Single',
  occupation: 'Teacher', resident_since: '2010', status: 'Approved', access_level: 'Verified',
  full_service_access: true, identity_status: 'Verified', capabilities: {}, profile_completeness: 80,
  identity_locked: true, photo: null,
};
const PAGE = { page: 1, per_page: 25, total: 0, last_page: 1 };

/** What the API answers, by path: small, fixed, enough for each page to draw itself. */
function apiAnswer(method, url, admin) {
  const p = new URL(url).pathname.replace(/^.*\/api\/v\d+/, '');
  if (method !== 'GET') return { data: {} };
  if (p === '/auth/me') return { data: admin };
  if (p === '/citizen/profile') return { data: RESIDENT };
  if (p === '/citizen/verification/id') return { data: { identity: { status: 'Verified', reasons: [] }, id_types: [], can_submit: false, submission: null, capture: 'hosted', session: null } };
  if (p === '/hotlines') return { data: [{ office: 'MDRRMO Esperanza', position: 'Disaster Response Hotline', contact: '(056) 333-1090' }] };
  if (p === '/directory') return { data: [{ office: 'Office of the Mayor', official_name: 'Hon. Sample Mayor', contact: '(056) 333-1000', email: 'mayor@esperanza.test' }] };
  if (p === '/barangays') return { data: [{ name: 'Poblacion', punong_barangay: 'Hon. Sample Captain', barangay_secretary: 'Sample Secretary', contact: '09170000001', seal_path: null, history: null, officials_roster: [] }] };
  if (/^\/(statuses|portal-settings|site-content\/[^/]+|settings\/[^/]+)$/.test(p)) return { data: {} };
  return { data: [], meta: PAGE };
}

// ------------------------------------------------------------ The browser

let ws;
let nextId = 1;
const pending = new Map();
const listeners = [];

function send(method, params, sessionId) {
  return new Promise((resolve, reject) => {
    const id = nextId++;
    pending.set(id, { resolve, reject });
    ws.send(JSON.stringify({ id, method, params: params || {}, sessionId }));
    setTimeout(() => { if (pending.has(id)) { pending.delete(id); reject(new Error('timeout ' + method)); } }, 30000);
  });
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function fileFor(urlPath) {
  const p = decodeURIComponent(urlPath.split('?')[0].split('#')[0]);
  let file = path.join(DIST, p);
  if (fs.existsSync(file) && fs.statSync(file).isDirectory()) file = path.join(file, 'index.html');
  else if (!fs.existsSync(file)) file = path.join(DIST, p, 'index.html');
  return fs.existsSync(file) ? file : null;
}

// What runs in the page: axe, then the keyboard.
const RUN_AXE = `(async () => {
  const r = await axe.run(document, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
    resultTypes: ['violations'],
  });
  return r.violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help,
    nodes: v.nodes.slice(0, 4).map((n) => ({ target: n.target.join(' '), summary: (n.failureSummary || '').split('\\n').slice(1, 3).join(' ').slice(0, 220) })),
    count: v.nodes.length }));
})()`;

// The focused element, described; whether it is visible and visibly marked.
const FOCUS_PROBE = `(() => {
  const el = document.activeElement;
  if (!el || el === document.body || el === document.documentElement || el.id === '__a11y_start') return { none: true };
  const r = el.getBoundingClientRect();
  const cs = getComputedStyle(el);
  const marked = (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0)
    || (cs.boxShadow && cs.boxShadow !== 'none')
    || el.matches(':focus-visible') && (cs.textDecorationLine || '').includes('underline');
  let ringed = marked;
  if (!ringed) {
    // A change of look counts too: compare with the element blurred.
    const focusedStyle = [cs.backgroundColor, cs.color, cs.borderColor, cs.boxShadow, cs.outlineStyle].join('|');
    el.blur();
    const rest = getComputedStyle(el);
    const restStyle = [rest.backgroundColor, rest.color, rest.borderColor, rest.boxShadow, rest.outlineStyle].join('|');
    el.focus({ preventScroll: true });
    ringed = focusedStyle !== restStyle;
  }
  const label = (el.getAttribute('aria-label') || el.innerText || el.value || el.getAttribute('title') || el.getAttribute('placeholder') || el.id || '').trim().replace(/\\s+/g, ' ').slice(0, 50);
  // Each stop gets a number, so two elements with the same words are told apart.
  window.__a11yStops = window.__a11yStops || 0;
  if (!el.dataset.a11yStop) el.dataset.a11yStop = String(++window.__a11yStops);
  const onScreen = r.width > 0 && r.height > 0 && r.bottom > 0 && r.right > 0 && r.top < innerHeight && r.left < innerWidth
    && cs.visibility !== 'hidden' && cs.opacity !== '0';
  const sel = el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (label ? ' "' + label + '"' : '');
  const skip = /skip|laktawan|lumaktaw/i.test(label) && el.tagName === 'A' && (el.getAttribute('href') || '').startsWith('#');
  const inDialog = !!el.closest('[role=dialog],[aria-modal=true]');
  return { sel, id: el.dataset.a11yStop, onScreen, ringed, skip, inDialog };
})()`;

async function main() {
  if (!fs.existsSync(path.join(DIST, 'index.html'))) { console.error('dist/ is missing: run the gate export first.'); process.exit(1); }
  if (!fs.existsSync(AXE)) { console.error('axe-core is not installed: npm install.'); process.exit(1); }
  const chromeBin = CHROME_CANDIDATES.find((c) => fs.existsSync(c));
  if (!chromeBin) { console.error('No Chrome/Edge found. Set CHROME=/path/to/chrome.'); process.exit(1); }

  const php = process.env.PHP || 'php';
  const dump = spawnSync(php, ['-r', [
    'require "vendor/autoload.php";',
    '$a = require "bootstrap/app.php";',
    '$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();',
    'require "build/export_manifest.php";',
    '$routes = array_keys(esperanza_export_routes(app("router")->getRoutes()));',
    '$admin = collect(config("esperanza_rbac.accounts"))->firstWhere("id", "SA-001");',
    'echo json_encode(["routes" => $routes, "admin" => $admin]);',
  ].join(' ')], { cwd: REPO, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });
  if (dump.status !== 0) { console.error('Could not read the routes:\n' + dump.stderr); process.exit(1); }
  const { routes, admin } = JSON.parse(dump.stdout);
  const norm = (r) => r.replace(/\/+$/, '') || '/';
  const pages = routes.filter((r) => !ONLY || norm(r) === norm(ONLY));
  if (!pages.length) { console.error('No page matches ' + (ONLY || 'the route list') + '.'); process.exit(1); }
  const axeSource = fs.readFileSync(AXE, 'utf8');

  const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'esperanza-a11y-'));
  const chrome = spawn(chromeBin, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--remote-debugging-port=' + CDP_PORT, '--user-data-dir=' + profile, 'about:blank'], { stdio: 'ignore' });
  let version = null;
  for (let i = 0; i < 60 && !version; i++) {
    try { version = await (await fetch('http://127.0.0.1:' + CDP_PORT + '/json/version')).json(); } catch { await sleep(500); }
  }
  if (!version) throw new Error('Chrome debugger never came up');

  ws = new WebSocket(version.webSocketDebuggerUrl);
  ws.addEventListener('message', (ev) => {
    const m = JSON.parse(ev.data);
    if (m.id && pending.has(m.id)) {
      const { resolve, reject } = pending.get(m.id);
      pending.delete(m.id);
      if (m.error) reject(new Error(m.error.message)); else resolve(m.result);
    } else if (m.method) {
      listeners.forEach((fn) => fn(m));
    }
  });
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));

  const created = await send('Target.createTarget', { url: 'about:blank' });
  const attached = await send('Target.attachToTarget', { targetId: created.targetId, flatten: true });
  const sid = attached.sessionId;
  const S = (m, p) => send(m, p, sid);

  // Every request: the site from dist/, the API from the sample data, nothing else.
  listeners.push(async (m) => {
    if (m.method !== 'Fetch.requestPaused' || m.sessionId !== sid) return;
    const { requestId, request } = m.params;
    const u = new URL(request.url);
    try {
      if (u.origin === ORIGIN) {
        const file = fileFor(u.pathname);
        if (!file) { await S('Fetch.fulfillRequest', { requestId, responseCode: 404, body: '' }); return; }
        await S('Fetch.fulfillRequest', { requestId, responseCode: 200,
          responseHeaders: [{ name: 'Content-Type', value: TYPES[path.extname(file)] || 'application/octet-stream' }],
          body: fs.readFileSync(file).toString('base64') });
        return;
      }
      if (/\/api\/v\d+\//.test(u.pathname)) {
        const headers = [{ name: 'Content-Type', value: 'application/json' }, { name: 'Access-Control-Allow-Origin', value: ORIGIN },
          { name: 'Access-Control-Allow-Headers', value: '*' }, { name: 'Access-Control-Allow-Methods', value: '*' }, { name: 'Access-Control-Allow-Credentials', value: 'true' }];
        if (request.method === 'OPTIONS') { await S('Fetch.fulfillRequest', { requestId, responseCode: 204, responseHeaders: headers, body: '' }); return; }
        const body = Buffer.from(JSON.stringify(apiAnswer(request.method, request.url, { ...admin, roleKey: admin.roleKey || 'super_admin' }))).toString('base64');
        await S('Fetch.fulfillRequest', { requestId, responseCode: 200, responseHeaders: headers, body });
        return;
      }
      await S('Fetch.failRequest', { requestId, errorReason: 'BlockedByClient' });
    } catch { /* the page moved on */ }
  });

  await S('Fetch.enable', { patterns: [{ urlPattern: '*', requestStage: 'Request' }] });
  await S('Page.enable');
  await S('Runtime.enable');
  await S('Emulation.setDeviceMetricsOverride', { width: WIDTH, height: HEIGHT, deviceScaleFactor: 1, mobile: false });
  // Signed in as both, so the resident's and the staff pages draw themselves;
  // reduced motion, so nothing is caught mid-animation.
  await S('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  await S('Page.addScriptToEvaluateOnNewDocument', { source: `try {
    localStorage.setItem('esperanza_token', 'a11y-gate');
    localStorage.setItem('esperanza_session', ${JSON.stringify(JSON.stringify({ ...admin, roleKey: admin.roleKey || 'super_admin' }))});
    localStorage.setItem('esperanza_citizen_session', ${JSON.stringify(JSON.stringify({ id: RESIDENT.account_no, firstName: 'Audit', lastName: 'Resident', fullName: 'Audit Resident', barangay: 'Poblacion', status: 'Approved', accessLevel: 'Verified', fullServiceAccess: true, identityStatus: 'Verified', identityLocked: true }))});
    localStorage.setItem('esperanza_shown_alerts', '[]');
  } catch (e) {}` });

  const key = async (k, shift = false) => {
    const codes = { Tab: [9, 'Tab'], Escape: [27, 'Escape'] };
    const [code, name] = codes[k];
    if (shift) await S('Input.dispatchKeyEvent', { type: 'rawKeyDown', key: 'Shift', code: 'ShiftLeft', windowsVirtualKeyCode: 16, nativeVirtualKeyCode: 16, modifiers: 8 });
    for (const type of ['rawKeyDown', 'keyUp']) {
      await S('Input.dispatchKeyEvent', { type, key: name, code: name, windowsVirtualKeyCode: code, nativeVirtualKeyCode: code, modifiers: shift ? 8 : 0 });
    }
    if (shift) await S('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Shift', code: 'ShiftLeft', windowsVirtualKeyCode: 16, nativeVirtualKeyCode: 16, modifiers: 0 });
    await sleep(60);
  };
  const evalIn = async (expression, awaitPromise = false) => {
    const out = await S('Runtime.evaluate', { expression, returnByValue: true, awaitPromise });
    if (out.exceptionDetails) throw new Error(out.exceptionDetails.exception?.description || out.exceptionDetails.text);
    return out.result.value;
  };

  const results = [];
  for (const uri of pages) {
    const res = { uri, axe: [], keyboard: [] };
    try {
      await S('Page.navigate', { url: ORIGIN + uri });
      // Settled, not a fixed pause: under load a page still building its
      // list read as broken (2026-10-17). Loaded, then the same markup for
      // half a second, up to 12 s; never shorter than the old 1.6 s.
      await sleep(800);
      let last = -1;
      let same = 0;
      for (const until = Date.now() + 12000; Date.now() < until;) {
        const size = await evalIn(`document.readyState === 'complete' ? document.body.innerHTML.length : -1`).catch(() => -1);
        same = size > 0 && size === last ? same + 1 : 0;
        last = size;
        if (same >= 5) break;
        await sleep(100);
      }
      await sleep(300);
      // A pop-up that opens by itself on arrival (an alert, a welcome) is closed first.
      await evalIn(`document.querySelectorAll('[role=dialog]').length`);
      await key('Escape');
      await evalIn(axeSource + ';true');
      res.axe = await evalIn(RUN_AXE, true);

      // ---- the keyboard, from the top of the page as a visit starts. A page
      // that moves focus by itself on arrival is noted (not failed: a sign-in
      // form focusing its first field is deliberate).
      const grabbed = await evalIn(`(() => { const a = document.activeElement; return a && a !== document.body ? a.tagName.toLowerCase() + (a.id ? '#' + a.id : '') : ''; })()`);
      if (grabbed) res.notes = ['focus was on ' + grabbed + ' when the page opened'];
      await evalIn(`(() => {
        if (document.activeElement) document.activeElement.blur();
        let start = document.getElementById('__a11y_start');
        if (!start) { start = document.createElement('span'); start.id = '__a11y_start'; start.tabIndex = -1; document.body.prepend(start); }
        start.focus({ preventScroll: true });
        window.scrollTo(0, 0);
        return true;
      })()`);
      const stops = [];
      let exited = false;
      for (let i = 0; i < MAX_TABS; i++) {
        await key('Tab');
        const f = await evalIn(FOCUS_PROBE);
        if (f.none) { if (i === 0) res.keyboard.push('the first Tab focused nothing'); exited = true; break; }
        stops.push(f);
      }
      const mains = await evalIn(`document.querySelectorAll('main,[role=main]').length`);
      if (mains === 1 && stops.length && !stops[0].skip) res.keyboard.push('the first Tab is not a "skip to content" link (it is ' + stops[0].sel + ')');
      const hidden = stops.filter((s) => !s.onScreen);
      const unmarked = stops.filter((s) => s.onScreen && !s.ringed);
      if (hidden.length) res.keyboard.push(hidden.length + ' Tab stop(s) off screen or invisible: ' + hidden.slice(0, 3).map((s) => s.sel).join('; '));
      if (unmarked.length) res.keyboard.push(unmarked.length + ' Tab stop(s) with no visible focus: ' + unmarked.slice(0, 3).map((s) => s.sel).join('; '));
      // Trapped: the same element over and over with nothing else (a page of one control is fine).
      const distinct = new Set(stops.map((s) => s.id)).size;
      if (stops.length >= 6 && distinct <= 2) res.keyboard.push('focus is trapped: Tab keeps returning to ' + [...new Set(stops.map((s) => s.sel))].join(', '));
      if (stops.length >= 3) {
        // Shift+Tab goes back one stop; after focus has left the page past the
        // last control, it comes back to that last control.
        const before = exited ? stops[stops.length - 1] : stops[stops.length - 2];
        await key('Tab', true);
        const back = await evalIn(FOCUS_PROBE);
        if (!back.none && back.id !== before.id && distinct > 2) res.keyboard.push('Shift+Tab does not go back (from ' + stops[stops.length - 1].sel + ' to ' + back.sel + ', expected ' + before.sel + ')');
      }
      // A dialog left open must close on Escape.
      const open = await evalIn(`[...document.querySelectorAll('[role=dialog],[aria-modal=true]')].filter((d) => d.offsetWidth || d.offsetHeight).length`);
      if (open) {
        await key('Escape');
        await sleep(400);
        const still = await evalIn(`[...document.querySelectorAll('[role=dialog],[aria-modal=true]')].filter((d) => d.offsetWidth || d.offsetHeight).length`);
        if (still >= open) res.keyboard.push('Escape does not close the open dialog');
      }
      res.stops = stops.length;
    } catch (e) {
      res.error = String(e.message || e).slice(0, 300);
    }
    results.push(res);
  }

  // ---- the report
  let failed = 0;
  const byRule = {};
  for (const r of results) {
    const serious = r.axe.filter((v) => FAIL_IMPACTS.includes(v.impact));
    const fails = serious.length > 0 || r.keyboard.length > 0 || !!r.error;
    if (fails) failed++;
    for (const v of r.axe) {
      byRule[v.id] = byRule[v.id] || { impact: v.impact, help: v.help, pages: 0, nodes: 0 };
      byRule[v.id].pages++;
      byRule[v.id].nodes += v.count;
    }
    if (!fails && !REPORT_ALL) continue;
    console.log((fails ? 'FAIL ' : 'ok   ') + r.uri + (r.stops !== undefined ? '  (' + r.stops + ' Tab stops)' : ''));
    if (r.error) console.log('     error: ' + r.error);
    for (const v of (REPORT_ALL ? r.axe : serious)) {
      console.log('     axe ' + v.impact + ' ' + v.id + ' x' + v.count + ': ' + v.help);
      for (const n of v.nodes.slice(0, 2)) console.log('         ' + n.target + (n.summary ? ' - ' + n.summary : ''));
    }
    for (const k of r.keyboard) console.log('     keyboard: ' + k);
    if (REPORT_ALL) for (const n of (r.notes || [])) console.log('     note: ' + n);
  }
  console.log('');
  console.log('axe rules seen (all impacts), pages / elements:');
  for (const [id, v] of Object.entries(byRule).sort((a, b) => b[1].pages - a[1].pages)) console.log('  ' + v.impact.padEnd(9) + id.padEnd(32) + v.pages + ' / ' + v.nodes + '  ' + v.help);
  console.log('');
  console.log('pages checked: ' + results.length + ', failing: ' + failed + ' (serious/critical axe violations, keyboard problems or errors)');
  fs.writeFileSync(path.join(os.tmpdir(), 'esperanza-a11y.json'), JSON.stringify(results, null, 2));

  ws.close();
  chrome.kill();
  process.exit(failed ? 1 : 0);
}

main().catch((e) => { console.error('A11Y GATE FAILED TO RUN: ' + e.message); process.exit(1); });
