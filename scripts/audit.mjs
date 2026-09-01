/**
 * Esperanza Web Platform - rendered-DOM audit.
 *
 * Runs headless Chrome over the BUILT static site in dist/ and measures what
 * source greps cannot: horizontal overflow at real viewport widths, images with
 * no alt, and form controls with no accessible name -- after Alpine has rendered.
 *
 * No dependencies: Node's global WebSocket speaks the DevTools protocol.
 *
 *   npm run build && php export.php && node scripts/audit.mjs
 *   AUDIT_WIDTH=1024 node scripts/audit.mjs
 *
 * TWO THINGS THIS SCRIPT REFUSES TO GUESS AT, because both silently produce a
 * clean result that was never earned:
 *
 *   1. The stylesheet must actually apply. export.php bakes APP_URL into
 *      ABSOLUTE asset URLs and forces port 80/443, so dist/ can only be served
 *      from http://127.0.0.1 (port 80). Served anywhere else every stylesheet
 *      404s, overflow-x falls back to `visible`, and wide tables inside a
 *      perfectly good overflow-x-auto wrapper get reported as defects. The
 *      style guard below aborts rather than measure raw HTML.
 *
 *   2. An admin session must be seeded. Client permission gating fails CLOSED
 *      (TAB 08), so without a session every admin surface is hidden and the
 *      audit would report a clean bill of health for an empty shell.
 */
import http from 'node:http';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const REPO = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DIST = path.join(REPO, 'dist');
const PORT = 80; // not negotiable -- see the header note
const CDP_PORT = 9333;
const WIDTH = Number(process.env.AUDIT_WIDTH || 768);
const HEIGHT = Number(process.env.AUDIT_HEIGHT || 1024);

const CHROME_CANDIDATES = [
  process.env.CHROME,
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
].filter(Boolean);

const PHP_CANDIDATES = [
  process.env.PHP,
  'php',
  ...(fs.existsSync('C:/laragon/bin/php')
    ? fs.readdirSync('C:/laragon/bin/php').map((d) => 'C:/laragon/bin/php/' + d + '/php.exe')
    : []),
  'C:/xampp/php/php.exe',
].filter(Boolean);

function firstThatWorks(candidates, probeArgs) {
  for (const c of candidates) {
    try {
      if (c !== 'php' && !fs.existsSync(c)) continue;
      const r = spawnSync(c, probeArgs, { encoding: 'utf8' });
      if (r.status === 0) return c;
    } catch { /* keep looking */ }
  }
  return null;
}

const TYPES = {
  '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon', '.woff2': 'font/woff2', '.woff': 'font/woff', '.pdf': 'application/pdf',
};

const server = http.createServer((req, res) => {
  const p = decodeURIComponent(req.url.split('?')[0]);
  let file = path.join(DIST, p);
  try {
    if (fs.existsSync(file) && fs.statSync(file).isDirectory()) file = path.join(file, 'index.html');
    else if (!fs.existsSync(file)) file = path.join(DIST, p, 'index.html');
    if (!fs.existsSync(file)) { res.writeHead(404); res.end('not found'); return; }
    res.writeHead(200, { 'Content-Type': TYPES[path.extname(file)] || 'application/octet-stream' });
    res.end(fs.readFileSync(file));
  } catch { res.writeHead(500); res.end('error'); }
});

let ws;
let nextId = 1;
const pending = new Map();

function send(method, params, sessionId) {
  return new Promise((resolve, reject) => {
    const id = nextId++;
    pending.set(id, { resolve, reject });
    ws.send(JSON.stringify({ id, method, params: params || {}, sessionId }));
    setTimeout(() => {
      if (pending.has(id)) { pending.delete(id); reject(new Error('timeout ' + method)); }
    }, 30000);
  });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const STYLE_GUARD = `(() => {
  const el = document.createElement('div');
  el.className = 'hidden';
  document.body.appendChild(el);
  const applied = getComputedStyle(el).display;
  el.remove();
  return { sheets: document.styleSheets.length, hiddenDisplay: applied };
})()`;

const PAGE_PROBE = `(() => {
  const de = document.scrollingElement || document.documentElement;
  const overflow = de.scrollWidth - de.clientWidth;
  const offenders = [];
  if (overflow > 1) {
    for (const el of document.querySelectorAll('body *')) {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || r.height === 0) continue;
      if (r.right <= de.clientWidth + 1) continue;
      let contained = false;
      for (let p = el.parentElement; p; p = p.parentElement) {
        const ov = getComputedStyle(p).overflowX;
        if (ov === 'auto' || ov === 'scroll' || ov === 'hidden') { contained = true; break; }
      }
      if (contained) continue;
      const cls = el.className && el.className.toString ? el.className.toString() : '';
      offenders.push({ tag: el.tagName.toLowerCase(), cls: cls.slice(0, 90), right: Math.round(r.right) });
      if (offenders.length >= 4) break;
    }
  }
  const controls = document.querySelectorAll('input:not([type=hidden]),select,textarea');
  let unnamed = 0;
  for (const i of controls) {
    const byAria = i.getAttribute('aria-label') || i.getAttribute('aria-labelledby');
    const byFor = i.id ? document.querySelector('label[for=' + JSON.stringify(i.id) + ']') : null;
    if (!byAria && !byFor && !i.closest('label')) unnamed++;
  }
  const imgs = document.querySelectorAll('img');
  let imgsNoAlt = 0;
  for (const im of imgs) if (!im.hasAttribute('alt')) imgsNoAlt++;
  return { overflow, offenders, imgsNoAlt, imgs: imgs.length, unnamed, controls: controls.length };
})()`;

async function main() {
  if (!fs.existsSync(path.join(DIST, 'index.html'))) {
    console.error('dist/ is missing or empty. Run: npm run build && php export.php');
    process.exit(1);
  }

  const php = firstThatWorks(PHP_CANDIDATES, ['-v']);
  if (!php) { console.error('No PHP interpreter found. Set PHP=/path/to/php.'); process.exit(1); }

  const chromeBin = CHROME_CANDIDATES.find((c) => fs.existsSync(c));
  if (!chromeBin) { console.error('No Chrome/Edge found. Set CHROME=/path/to/chrome.'); process.exit(1); }

  // Routes and a full-access account, straight from the app's own config.
  const dump = spawnSync(php, ['-r', [
    'require "vendor/autoload.php";',
    '$a = require "bootstrap/app.php";',
    '$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();',
    'require "build/export_manifest.php";',
    '$routes = array_keys(esperanza_export_routes(app("router")->getRoutes()));',
    '$admin = collect(config("esperanza_rbac.accounts"))->firstWhere("id", "SA-001");',
    'echo json_encode(["routes" => $routes, "admin" => $admin]);',
  ].join(' ')], { cwd: REPO, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });

  if (dump.status !== 0) { console.error('Could not read routes/accounts:\n' + dump.stderr); process.exit(1); }
  const { routes, admin } = JSON.parse(dump.stdout);

  await new Promise((resolve, reject) => {
    server.on('error', (e) => reject(new Error('cannot bind port 80 (' + e.code + '). '
      + 'dist/ hardcodes http://127.0.0.1 asset URLs, so the audit must serve there.')));
    server.listen(PORT, '127.0.0.1', resolve);
  });

  const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'esperanza-audit-'));
  const chrome = spawn(chromeBin, [
    '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--remote-debugging-port=' + CDP_PORT, '--user-data-dir=' + profile, 'about:blank',
  ], { stdio: 'ignore' });

  let version = null;
  for (let i = 0; i < 60 && !version; i++) {
    try { version = await (await fetch('http://127.0.0.1:' + CDP_PORT + '/json/version')).json(); }
    catch { await sleep(500); }
  }
  if (!version) throw new Error('Chrome debugger never came up');

  ws = new WebSocket(version.webSocketDebuggerUrl);
  ws.addEventListener('message', (ev) => {
    const m = JSON.parse(ev.data);
    if (m.id && pending.has(m.id)) {
      const { resolve, reject } = pending.get(m.id);
      pending.delete(m.id);
      if (m.error) reject(new Error(m.error.message)); else resolve(m.result);
    }
  });
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));

  const created = await send('Target.createTarget', { url: 'about:blank' });
  const attached = await send('Target.attachToTarget', { targetId: created.targetId, flatten: true });
  const S = (m, p) => send(m, p, attached.sessionId);

  await S('Page.enable');
  await S('Runtime.enable');
  await S('Emulation.setDeviceMetricsOverride', { width: WIDTH, height: HEIGHT, deviceScaleFactor: 1, mobile: false });
  await S('Page.addScriptToEvaluateOnNewDocument', {
    source: 'try { localStorage.setItem("esperanza_session", '
      + JSON.stringify(JSON.stringify(admin)) + '); } catch (e) {}',
  });

  const origin = 'http://127.0.0.1' + (PORT === 80 ? '' : ':' + PORT);

  await S('Page.navigate', { url: origin + '/' });
  await sleep(900);
  const g = (await S('Runtime.evaluate', { returnByValue: true, expression: STYLE_GUARD })).result.value;
  if (g.sheets === 0 || g.hiddenDisplay !== 'none') {
    throw new Error('The stylesheet did not load (sheets=' + g.sheets + ', .hidden=' + g.hiddenDisplay + '). '
      + 'Re-export with APP_URL=http://127.0.0.1 before trusting any measurement.');
  }
  console.log('style guard OK: ' + g.sheets + ' stylesheet(s); .hidden resolves to display:none');

  const results = [];
  for (const uri of routes) {
    try {
      await S('Page.navigate', { url: origin + uri });
      await sleep(650);
      const out = await S('Runtime.evaluate', { returnByValue: true, expression: PAGE_PROBE });
      if (out.exceptionDetails) results.push({ uri, error: out.exceptionDetails.text });
      else results.push({ uri, ...out.result.value });
    } catch (e) {
      results.push({ uri, error: e.message });
    }
  }

  const seeded = (await S('Runtime.evaluate', {
    returnByValue: true,
    expression: 'JSON.parse(localStorage.getItem("esperanza_session") || "null")?.id || "NONE"',
  })).result.value;
  if (seeded === 'NONE') throw new Error('admin session was not seeded - every admin surface was hidden');
  console.log('seeded admin session: ' + seeded);

  const errored = results.filter((r) => r.error);
  const overflowing = results.filter((r) => r.overflow > 1);

  console.log('viewport ' + WIDTH + 'px -- routes audited: ' + results.length + ' (errors: ' + errored.length + ')');
  console.log('routes with horizontal PAGE overflow: ' + overflowing.length);
  for (const r of overflowing) {
    console.log('  ' + r.uri + '  overflow ' + r.overflow + 'px');
    for (const o of r.offenders) console.log('      <' + o.tag + '> ' + o.cls + '  right=' + o.right);
  }
  for (const r of errored) console.log('  ERROR ' + r.uri + ': ' + r.error);

  const t = results.filter((r) => !r.error).reduce((a, r) => ({
    imgsNoAlt: a.imgsNoAlt + r.imgsNoAlt, imgs: a.imgs + r.imgs,
    unnamed: a.unnamed + r.unnamed, controls: a.controls + r.controls,
  }), { imgsNoAlt: 0, imgs: 0, unnamed: 0, controls: 0 });

  console.log('rendered a11y totals:');
  console.log('  images without alt: ' + t.imgsNoAlt + ' of ' + t.imgs);
  console.log('  form controls with no accessible name: ' + t.unnamed + ' of ' + t.controls);

  fs.writeFileSync(path.join(os.tmpdir(), 'esperanza-audit-' + WIDTH + '.json'), JSON.stringify(results, null, 2));

  ws.close();
  chrome.kill();
  server.close();
  process.exit(overflowing.length > 0 || errored.length > 0 ? 1 : 0);
}

main().catch((e) => { console.error('AUDIT FAILED: ' + e.message); process.exit(1); });
