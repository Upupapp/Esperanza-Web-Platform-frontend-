// Parses every Alpine expression in the exported site, without running any.
//
// An Alpine component is JavaScript inside a double-quoted HTML attribute. A
// stray double quote (in a comment, a string, or JSON that Blade printed with
// @json instead of @js) ends the attribute early: the rest of the component is
// printed onto the page as text, and the page still exports with a 200. On
// 2026-10-04 that had broken every Sakuna page, Payments, both Dokyu and both
// Tulong screens, and 41 bindings of the document editor's toolbar, with every
// other gate green. Checking the rendered HTML catches what Blade emits too.
//
// usage: node scripts/check-alpine.mjs dist   (exit 1 on any failure)
import fs from 'node:fs';
import path from 'node:path';

const dist = process.argv[2] || 'dist';
const AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
const decode = (s) => s.replace(/&quot;/g, '"').replace(/&#0?39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
// Statements: x-init, x-effect and event handlers. Everything else listed is an
// expression. x-for, x-model and x-ref are not plain JavaScript and are skipped.
const ATTRIBUTE = /\s((?:x-data|x-init|x-show|x-text|x-html|x-effect|x-if)|@[\w.:-]+|x-on:[\w.:-]+|:[\w.-]+|x-bind:[\w.-]+)="([^"]*)"/g;

function htmlFiles(dir, out = []) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, entry.name);
        if (entry.isDirectory()) htmlFiles(p, out);
        else if (entry.name.endsWith('.html')) out.push(p);
    }
    return out;
}

let checked = 0;
const failures = new Map();
for (const file of htmlFiles(dist)) {
    const html = fs.readFileSync(file, 'utf8');
    for (const [, name, raw] of html.matchAll(ATTRIBUTE)) {
        const code = decode(raw);
        if (!code.trim()) continue;
        const statement = name === 'x-init' || name === 'x-effect' || name.startsWith('@') || name.startsWith('x-on:');
        checked++;
        try {
            if (statement) new AsyncFunction(code);
            else new AsyncFunction('return (' + code + '\n)');
        } catch (e) {
            const key = `${name}: ${e.message} -- ${code.replace(/\s+/g, ' ').slice(0, 100)}`;
            if (!failures.has(key)) failures.set(key, new Set());
            failures.get(key).add(path.relative(dist, file));
        }
    }
}

for (const [key, files] of failures) {
    const list = [...files];
    console.log(`   ${key}\n     in ${list.slice(0, 3).join(', ')}${list.length > 3 ? ` (+${list.length - 3} more)` : ''}`);
}
console.log(`   ${checked} Alpine expressions parsed, ${failures.size} distinct failure(s)`);
process.exit(checked > 0 && failures.size === 0 ? 0 : 1);
