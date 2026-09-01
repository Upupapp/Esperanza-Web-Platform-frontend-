/**
 * Rendered-HTML snapshot, for refactors that must not change output.
 *
 * TAB 04 moves 166 inline fixture arrays out of 43 Blade views. Its acceptance
 * is that all 55 routes still render IDENTICALLY afterwards, so this captures a
 * hash per exported page before the change and compares after it. A refactor
 * this size is unverifiable by reading.
 *
 *   node scripts/render-snapshot.mjs --save     # before
 *   node scripts/render-snapshot.mjs --check    # after
 *
 * Both modes re-export first, with a FIXED APP_URL. export.php bakes APP_URL
 * into absolute asset URLs, so exporting twice under different values would
 * change every page and drown the real diff.
 *
 * The comparison ignores the Vite asset hash: `npm run build` emits a new
 * content hash whenever app.js or app.css changes, which is a real change but
 * not one this tool is asking about. Everything else is compared byte for byte.
 */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const REPO = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DIST = path.join(REPO, 'dist');
const SNAPSHOT = path.join(REPO, 'storage', 'render-snapshot.json');
const APP_URL = 'http://127.0.0.1';

const mode = process.argv.includes('--save') ? 'save'
  : process.argv.includes('--check') ? 'check'
  : null;

if (!mode) {
  console.error('Usage: node scripts/render-snapshot.mjs --save | --check');
  process.exit(2);
}

function resolvePhp() {
  const candidates = [process.env.PHP, 'php'];
  if (fs.existsSync('C:/laragon/bin/php')) {
    for (const d of fs.readdirSync('C:/laragon/bin/php')) {
      candidates.push('C:/laragon/bin/php/' + d + '/php.exe');
    }
  }
  candidates.push('C:/xampp/php/php.exe');

  for (const c of candidates) {
    if (!c) continue;
    try {
      if (c !== 'php' && !fs.existsSync(c)) continue;
      if (spawnSync(c, ['-v'], { encoding: 'utf8' }).status === 0) return c;
    } catch { /* keep looking */ }
  }
  return null;
}

function walkHtml(dir, out = []) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walkHtml(p, out);
    else if (e.name.endsWith('.html')) out.push(p);
  }
  return out;
}

// Two kinds of legitimate churn are normalised away, because neither is the
// question this tool asks:
//
//   1. Vite content hashes, which change whenever app.js/app.css changes.
//   2. The one minute-precision timestamp in the export -- a datetime-local
//      default on admin/sakuna/incidents. Note this is a symptom of a REAL
//      defect: now() in a static export bakes the BUILD time into the page, so
//      a visitor next month is shown the build date as "today". Normalising it
//      here keeps refactor diffs readable; it does not fix that.
const normalise = (html) => html
  .replace(/app-[A-Za-z0-9_-]{8}\.(js|css)/g, 'app-HASH.$1')
  .replace(/(chart|html2pdf)-[A-Za-z0-9_-]{8}\.js/g, '$1-HASH.js')
  .replace(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/g, 'TIMESTAMP');

const php = resolvePhp();
if (!php) { console.error('No PHP interpreter found. Set PHP=/path/to/php.'); process.exit(1); }

console.log('exporting with APP_URL=' + APP_URL + ' ...');
fs.rmSync(DIST, { recursive: true, force: true });
const run = spawnSync(php, ['export.php'], {
  cwd: REPO,
  encoding: 'utf8',
  env: { ...process.env, APP_URL },
  maxBuffer: 64 * 1024 * 1024,
});

if (run.status !== 0) {
  console.error('export failed:\n' + (run.stdout || '') + (run.stderr || ''));
  process.exit(1);
}

const pages = {};
for (const file of walkHtml(DIST)) {
  const rel = path.relative(DIST, file).split(path.sep).join('/');
  pages[rel] = crypto.createHash('sha256').update(normalise(fs.readFileSync(file, 'utf8'))).digest('hex');
}

const count = Object.keys(pages).length;

if (mode === 'save') {
  fs.mkdirSync(path.dirname(SNAPSHOT), { recursive: true });
  fs.writeFileSync(SNAPSHOT, JSON.stringify({ takenAt: new Date().toISOString(), pages }, null, 2));
  console.log('saved ' + count + ' page hashes to storage/render-snapshot.json');
  process.exit(0);
}

if (!fs.existsSync(SNAPSHOT)) {
  console.error('No baseline. Run --save on the tree BEFORE the refactor.');
  process.exit(1);
}

const base = JSON.parse(fs.readFileSync(SNAPSHOT, 'utf8')).pages;
const changed = [];
const added = [];
const removed = [];

for (const [rel, hash] of Object.entries(pages)) {
  if (!(rel in base)) added.push(rel);
  else if (base[rel] !== hash) changed.push(rel);
}
for (const rel of Object.keys(base)) {
  if (!(rel in pages)) removed.push(rel);
}

console.log('baseline pages: ' + Object.keys(base).length + '   current: ' + count);

if (changed.length === 0 && added.length === 0 && removed.length === 0) {
  console.log('=== IDENTICAL: all ' + count + ' pages render byte-for-byte as before ===');
  process.exit(0);
}

for (const r of changed) console.log('  CHANGED  ' + r);
for (const r of added) console.log('  ADDED    ' + r);
for (const r of removed) console.log('  REMOVED  ' + r);
console.log('=== DIFFERENT: ' + changed.length + ' changed, ' + added.length + ' added, ' + removed.length + ' removed ===');
process.exit(1);
