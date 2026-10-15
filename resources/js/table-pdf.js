// A list as a PDF (owner, 2026-10-15: "let them also download in pdf, not just
// in excel"). Every staff list that downloads a CSV or Excel copy downloads the
// same rows as a PDF too: landscape A4 with the LGU header, the title, when it
// was made and how many rows, then the table, repeated column heads on every
// page and "Page n of N". Drawn as text by jsPDF, loaded only when asked, so a
// long list (the whole registry) stays small and quick, unlike a picture of
// the page.

let jsPdfPromise = null;
const loadJsPdf = () => (jsPdfPromise ??= import('jspdf').then((m) => m.jsPDF || m.default));

// The standard PDF fonts know Latin-1 (n with tilde, accents, dashes) but not
// the peso sign.
const clean = (s) => String(s).replace(/₱\s?/g, 'PHP ').replace(/[^\x09\x0a\x0d\x20-\x7e\xa0-\xff–—‘’“”•…]/g, '');

/** What a cell shows: bilingual {fil, en} reads English, lists are joined, objects named. */
function text(value) {
    if (value === null || value === undefined) return '';
    if (Array.isArray(value)) return value.map(text).filter(Boolean).join(', ');
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return text(value.en ?? value.name ?? value.label ?? value.ref ?? '');
    return clean(value);
}

const heading = (key) => String(key).replace(/_/g, ' ').replace(/\s+/g, ' ').trim().replace(/^\w/, (c) => c.toUpperCase());

/**
 * rows: the same objects the CSV is made from.
 * options.columns: [{key, label}] or keys, in order; otherwise every key whose
 *   values are plain (not objects) in the rows, minus options.skip.
 */
export async function tablePdf(rows, options = {}) {
    const { filename = 'export.pdf', title = 'Export', subtitle = '', skip = [] } = options;
    if (!rows || !rows.length) throw new Error('No records to export.');
    const JsPdf = await loadJsPdf();

    const columns = options.columns
        ? options.columns.map((c) => (typeof c === 'string' ? { key: c, label: heading(c) } : { key: c.key, label: c.label || heading(c.key) }))
        : Object.keys(rows[0])
            .filter((k) => !skip.includes(k) && rows.some((r) => r[k] !== null && r[k] !== undefined && r[k] !== '') && rows.every((r) => r[k] === null || r[k] === undefined || typeof r[k] !== 'object' || Array.isArray(r[k]) || 'en' in r[k]))
            .map((k) => ({ key: k, label: heading(k) }));
    const body = rows.map((r) => columns.map((c) => text(r[c.key])));

    const doc = new JsPdf({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const page = { w: doc.internal.pageSize.getWidth(), h: doc.internal.pageSize.getHeight() };
    const margin = 10;
    const width = page.w - margin * 2;
    const size = columns.length > 14 ? 6.5 : columns.length > 9 ? 7.5 : 8.5;
    const line = size * 0.42;
    const pad = 1.4;

    // Column widths: by the longer of the heading and the typical cell (a
    // sample of rows), within limits. To fit the page the wide columns give
    // way first, never below the heading's longest word, so a heading wraps
    // between words ("Verification" was cut to "Verificati / on").
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(size);
    const sample = body.slice(0, 300);
    const sum = (xs) => xs.reduce((a, b) => a + b, 0);
    const want = [];
    const least = [];
    columns.forEach((c, i) => {
        const cells = sample.map((r) => Math.min(doc.getTextWidth(r[i] || ''), 70)).sort((a, b) => a - b);
        const typical = cells.length ? cells[Math.floor(cells.length * 0.9)] : 0;
        doc.setFont('helvetica', 'bold');
        const head = Math.min(doc.getTextWidth(c.label), 40);
        const word = Math.min(Math.max(...c.label.split(/\s+/).map((w) => doc.getTextWidth(w))), 30);
        doc.setFont('helvetica', 'normal');
        // Nor below the column's typical longest unbroken value: a reference
        // (RES-2026-0012), a date or a code stays on one line.
        const tokens = sample.map((r) => Math.max(0, ...String(r[i] || '').split(/\s+/).map((t) => doc.getTextWidth(t)))).sort((a, b) => a - b);
        const token = Math.min(tokens.length ? tokens[Math.floor(tokens.length * 0.9)] : 0, 32);
        least.push(Math.max(8, word, token) + pad * 2);
        want.push(Math.max(12, head, typical, word, token) + pad * 2);
    });
    let widths;
    if (sum(want) <= width) {
        widths = want.map((w) => w * (width / sum(want)));
    } else if (sum(least) < width) {
        const give = (width - sum(least)) / (sum(want) - sum(least));
        widths = want.map((w, i) => least[i] + (w - least[i]) * give);
    } else {
        widths = least.map((w) => w * (width / sum(least)));
    }

    const made = new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' });
    let y = margin;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(90);
    doc.text('Republic of the Philippines  •  Province of Masbate  •  Municipality of Esperanza', page.w / 2, y + 2, { align: 'center' });
    y += 7;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(20);
    doc.text(clean(title), page.w / 2, y + 2, { align: 'center' });
    y += 6;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(90);
    doc.text(clean((subtitle ? subtitle + '  •  ' : '') + 'Made ' + made + '  •  ' + rows.length.toLocaleString('en-PH') + (rows.length === 1 ? ' row' : ' rows')), page.w / 2, y + 2, { align: 'center' });
    y += 6;

    const head = () => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(size);
        const wrapped = columns.map((c, i) => doc.splitTextToSize(c.label, widths[i] - pad * 2));
        const h = Math.max(...wrapped.map((w) => w.length)) * line + pad * 2;
        doc.setFillColor(30, 58, 138);
        doc.rect(margin, y, width, h, 'F');
        doc.setTextColor(255);
        let x = margin;
        wrapped.forEach((w, i) => { doc.text(w, x + pad, y + pad + line * 0.8); x += widths[i]; });
        y += h;
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(30);
    };
    head();
    body.forEach((r, n) => {
        const wrapped = r.map((t, i) => doc.splitTextToSize(t, widths[i] - pad * 2));
        const h = Math.max(1, ...wrapped.map((w) => w.length)) * line + pad * 2;
        if (y + h > page.h - margin - 6) {
            doc.addPage();
            y = margin;
            head();
        }
        if (n % 2) {
            doc.setFillColor(241, 245, 249);
            doc.rect(margin, y, width, h, 'F');
        }
        let x = margin;
        wrapped.forEach((w, i) => { if (w.length) doc.text(w, x + pad, y + pad + line * 0.8); x += widths[i]; });
        doc.setDrawColor(226, 232, 240);
        doc.line(margin, y + h, margin + width, y + h);
        y += h;
    });

    const pages = doc.getNumberOfPages();
    for (let p = 1; p <= pages; p++) {
        doc.setPage(p);
        doc.setFontSize(7);
        doc.setTextColor(120);
        doc.text(clean(title) + '  •  Page ' + p + ' of ' + pages, page.w - margin, page.h - 5, { align: 'right' });
    }
    doc.save(filename.replace(/\.(csv|xlsx?)$/i, '') + (/\.pdf$/i.test(filename) ? '' : '.pdf'));
}

/** Rows from a CSV the server made (the audit log export), for tablePdf. */
export function rowsFromCsv(csv) {
    const out = [];
    let row = [];
    let field = '';
    let quoted = false;
    const s = String(csv).replace(/^﻿/, '');
    for (let i = 0; i < s.length; i++) {
        const c = s[i];
        if (quoted) {
            if (c === '"' && s[i + 1] === '"') { field += '"'; i++; } else if (c === '"') quoted = false; else field += c;
        } else if (c === '"') quoted = true;
        else if (c === ',') { row.push(field); field = ''; } else if (c === '\n' || c === '\r') {
            if (c === '\r' && s[i + 1] === '\n') i++;
            row.push(field); field = '';
            if (row.some((x) => x !== '')) out.push(row);
            row = [];
        } else field += c;
    }
    if (field !== '' || row.length) { row.push(field); if (row.some((x) => x !== '')) out.push(row); }
    const [keys = [], ...data] = out;
    return data.map((r) => Object.fromEntries(keys.map((k, i) => [k, r[i] ?? ''])));
}
