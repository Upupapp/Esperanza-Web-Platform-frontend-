// A list as a PDF (owner, 2026-10-15: "let them also download in pdf, not just
// in excel"). Every staff list that downloads a CSV or Excel copy downloads the
// same rows as a PDF too: landscape A4 with the LGU header, the title, when it
// was made and how many rows, then the table, repeated column heads on every
// page and "Page n of N". Drawn as text by jsPDF, loaded only when asked, so a
// long list (the whole registry) stays small and quick, unlike a picture of
// the page. reportPdf puts several such tables in one document.

let jsPdfPromise = null;
const loadJsPdf = () => (jsPdfPromise ??= import('jspdf').then((m) => m.jsPDF || m.default));

// The standard PDF fonts know Latin-1 (n with tilde, accents, dashes) but not
// the peso sign.
const clean = (s) => String(s).replace(/₱\s?/g, 'PHP ').replace(/[^\x09\x0a\x0d\x20-\x7e\xa0-\xff–—‘’“”•…‹›]/g, '');

/** What a cell shows: bilingual {fil, en} reads English, lists are joined, objects named. */
function text(value) {
    if (value === null || value === undefined) return '';
    if (Array.isArray(value)) return value.map(text).filter(Boolean).join(', ');
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return text(value.en ?? value.name ?? value.label ?? value.ref ?? '');
    return clean(value);
}

const heading = (key) => String(key).replace(/_/g, ' ').replace(/\s+/g, ' ').trim().replace(/^\w/, (c) => c.toUpperCase());

/** The columns a list shows: options.columns in order, else every plain key minus options.skip. */
function columnsOf(rows, options) {
    const skip = options.skip || [];
    return options.columns
        ? options.columns.map((c) => (typeof c === 'string' ? { key: c, label: heading(c) } : { key: c.key, label: c.label || heading(c.key) }))
        : Object.keys(rows[0])
            .filter((k) => !skip.includes(k) && rows.some((r) => r[k] !== null && r[k] !== undefined && r[k] !== '') && rows.every((r) => r[k] === null || r[k] === undefined || typeof r[k] !== 'object' || Array.isArray(r[k]) || 'en' in r[k]))
            .map((k) => ({ key: k, label: heading(k) }));
}

// Column widths: by the longer of the heading and the typical cell (a
// sample of rows), within limits. To fit the page the wide columns give
// way first, never below the heading's longest word, so a heading wraps
// between words ("Verification" was cut to "Verificati / on").
function layout(doc, columns, body, width) {
    const size = columns.length > 14 ? 6.5 : columns.length > 9 ? 7.5 : 8.5;
    const pad = 1.4;
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
    return { widths, size, line: size * 0.42, pad };
}

const madeNow = () => new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' });

/** A landscape A4 document under the LGU header; st.y is where content starts. */
async function startDoc(title, subtitleText) {
    const JsPdf = await loadJsPdf();
    const doc = new JsPdf({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const page = { w: doc.internal.pageSize.getWidth(), h: doc.internal.pageSize.getHeight() };
    const margin = 10;
    const st = { doc, page, margin, width: page.w - margin * 2, y: margin };
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(90);
    doc.text('Republic of the Philippines  •  Province of Masbate  •  Municipality of Esperanza', page.w / 2, st.y + 2, { align: 'center' });
    st.y += 7;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(20);
    doc.text(clean(title), page.w / 2, st.y + 2, { align: 'center' });
    st.y += 6;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(90);
    // Wrapped to the page: a long subtitle (filters, totals) ran off both edges.
    const sub = doc.splitTextToSize(clean(subtitleText), st.width);
    doc.text(sub, page.w / 2, st.y + 2, { align: 'center' });
    st.y += 6 + (sub.length - 1) * 3.4;
    return st;
}

/** The table, its column heads repeated on every page it runs onto. */
function drawTable(st, columns, body) {
    const { doc, page, margin, width } = st;
    const { widths, size, line, pad } = layout(doc, columns, body, width);
    const head = () => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(size);
        const wrapped = columns.map((c, i) => doc.splitTextToSize(c.label, widths[i] - pad * 2));
        const h = Math.max(...wrapped.map((w) => w.length)) * line + pad * 2;
        doc.setFillColor(30, 58, 138);
        doc.rect(margin, st.y, width, h, 'F');
        doc.setTextColor(255);
        let x = margin;
        wrapped.forEach((w, i) => { doc.text(w, x + pad, st.y + pad + line * 0.8); x += widths[i]; });
        st.y += h;
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(30);
    };
    head();
    body.forEach((r, n) => {
        const wrapped = r.map((t, i) => doc.splitTextToSize(t, widths[i] - pad * 2));
        const h = Math.max(1, ...wrapped.map((w) => w.length)) * line + pad * 2;
        if (st.y + h > page.h - margin - 6) {
            doc.addPage();
            st.y = margin;
            head();
        }
        if (n % 2) {
            doc.setFillColor(241, 245, 249);
            doc.rect(margin, st.y, width, h, 'F');
        }
        let x = margin;
        wrapped.forEach((w, i) => { if (w.length) doc.text(w, x + pad, st.y + pad + line * 0.8); x += widths[i]; });
        doc.setDrawColor(226, 232, 240);
        doc.line(margin, st.y + h, margin + width, st.y + h);
        st.y += h;
    });
}

/** "Page n of N" on every page, then the download. */
function finish(st, title, filename) {
    const { doc, page, margin } = st;
    const pages = doc.getNumberOfPages();
    for (let p = 1; p <= pages; p++) {
        doc.setPage(p);
        doc.setFontSize(7);
        doc.setTextColor(120);
        doc.text(clean(title) + '  •  Page ' + p + ' of ' + pages, page.w - margin, page.h - 5, { align: 'right' });
    }
    doc.save(filename.replace(/\.(csv|xlsx?)$/i, '') + (/\.pdf$/i.test(filename) ? '' : '.pdf'));
}

/**
 * rows: the same objects the CSV is made from.
 * options.columns: [{key, label}] or keys, in order; otherwise every key whose
 *   values are plain (not objects) in the rows, minus options.skip.
 */
export async function tablePdf(rows, options = {}) {
    const { filename = 'export.pdf', title = 'Export', subtitle = '' } = options;
    if (!rows || !rows.length) throw new Error('No records to export.');
    const columns = columnsOf(rows, options);
    const body = rows.map((r) => columns.map((c) => text(r[c.key])));
    const st = await startDoc(title, (subtitle ? subtitle + '  •  ' : '') + 'Made ' + madeNow() + '  •  ' + rows.length.toLocaleString('en-PH') + (rows.length === 1 ? ' row' : ' rows'));
    drawTable(st, columns, body);
    finish(st, title, filename);
}

/**
 * Several tables in one document (the Sakuna situation report, 2026-10-30):
 * the header once, then each section's heading, its note and its table.
 * sections: [{ heading, note?, columns: [{key, label}], rows: [...] }]; a
 * section with no rows says "None recorded." instead of an empty table.
 */
export async function reportPdf(sections, options = {}) {
    const { filename = 'report.pdf', title = 'Report', subtitle = '' } = options;
    const st = await startDoc(title, (subtitle ? subtitle + '  •  ' : '') + 'Made ' + madeNow());
    const { doc, page, margin, width } = st;
    sections.forEach((s) => {
        if (st.y > page.h - margin - 32) {
            doc.addPage();
            st.y = margin;
        }
        st.y += 3;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10.5);
        doc.setTextColor(11, 23, 48);
        doc.text(clean(s.heading), margin, st.y + 3);
        st.y += 6;
        if (s.note) {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor(100);
            const lines = doc.splitTextToSize(clean(s.note), width);
            doc.text(lines, margin, st.y + 2);
            st.y += 1.5 + lines.length * 3.3;
        }
        if (!s.rows || !s.rows.length) {
            doc.setFont('helvetica', 'italic');
            doc.setFontSize(8);
            doc.setTextColor(110);
            doc.text('None recorded.', margin, st.y + 3);
            st.y += 7;
            return;
        }
        const columns = columnsOf(s.rows, { columns: s.columns });
        drawTable(st, columns, s.rows.map((r) => columns.map((c) => text(r[c.key]))));
        st.y += 4;
    });
    finish(st, title, filename);
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
