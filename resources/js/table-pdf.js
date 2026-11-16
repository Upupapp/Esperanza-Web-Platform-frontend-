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
async function startDoc(title, subtitleText, office = '') {
    const JsPdf = await loadJsPdf();
    const doc = new JsPdf({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const page = { w: doc.internal.pageSize.getWidth(), h: doc.internal.pageSize.getHeight() };
    const margin = 10;
    const st = { doc, page, margin, width: page.w - margin * 2, y: margin };
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(90);
    doc.text('Republic of the Philippines  •  Province of Masbate  •  Municipality of Esperanza', page.w / 2, st.y + 2, { align: 'center' });
    if (office) {
        st.y += 4;
        doc.setFont('helvetica', 'bold');
        doc.text(clean(office), page.w / 2, st.y + 2, { align: 'center' });
        doc.setFont('helvetica', 'normal');
    }
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

/** An executive summary under the header (2026-11-03): its heading, then each paragraph wrapped to the page. */
function drawSummary(st, paragraphs) {
    if (!paragraphs || !paragraphs.length) return;
    const { doc, margin, width } = st;
    st.y += 2;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(11, 23, 48);
    doc.text('Executive Summary', margin, st.y + 3);
    st.y += 6.5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.setTextColor(30);
    paragraphs.forEach((p) => {
        const lines = doc.splitTextToSize(clean(p), width);
        doc.text(lines, margin, st.y + 2.5);
        st.y += lines.length * 3.9 + 2.2;
    });
    st.y += 3;
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
 * A titled part after the main table, in the same columns (the casualty list's
 * residents hurt or killed outside Esperanza, 2026-11-16): heading, note, rows.
 */
function drawPart(st, columns, part) {
    const { doc, page, margin, width } = st;
    doc.setFontSize(8.5);
    const note = part.note ? doc.splitTextToSize(clean(part.note), width) : [];
    if (st.y + 20 + note.length * 3.6 > page.h - margin - 6) {
        doc.addPage();
        st.y = margin;
    }
    st.y += 5;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(11, 23, 48);
    doc.text(clean(part.title), margin, st.y + 3);
    st.y += 6;
    if (note.length) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(60);
        doc.text(note, margin, st.y + 2.5);
        st.y += note.length * 3.6 + 2;
    }
    drawTable(st, columns, part.rows.map((r) => columns.map((c) => text(r[c.key]))));
}

/** Signature lines at the end, side by side: who prepares, countersigns and approves. */
function drawSignatures(st, signatures) {
    if (!signatures || !signatures.length) return;
    const { doc, page, margin, width } = st;
    if (st.y + 30 > page.h - margin - 6) {
        doc.addPage();
        st.y = margin;
    }
    st.y += 10;
    const w = width / signatures.length;
    signatures.forEach((s, i) => {
        const x = margin + i * w;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(60);
        doc.text(clean(s.label), x + 4, st.y);
        doc.setDrawColor(120);
        doc.line(x + 4, st.y + 12, x + w - 8, st.y + 12);
        doc.setTextColor(30);
        doc.text(clean(s.position || ''), x + 4, st.y + 16);
    });
    st.y += 20;
}

/**
 * rows: the same objects the CSV is made from.
 * options.columns: [{key, label}] or keys, in order; otherwise every key whose
 *   values are plain (not objects) in the rows, minus options.skip.
 * options.summary: paragraphs printed under the header, before the table.
 * options.parts: [{title, note, rows}] printed after the table, same columns.
 * options.signatures: [{label, position}] signature lines at the end.
 */
export async function tablePdf(rows, options = {}) {
    const { filename = 'export.pdf', title = 'Export', subtitle = '', summary = [], parts = [], signatures = [] } = options;
    if (!rows || !rows.length) throw new Error('No records to export.');
    const columns = columnsOf(rows, options);
    const body = rows.map((r) => columns.map((c) => text(r[c.key])));
    const total = rows.length + parts.reduce((n, p) => n + p.rows.length, 0);
    const st = await startDoc(title, (subtitle ? subtitle + '  •  ' : '') + 'Made ' + madeNow() + '  •  ' + total.toLocaleString('en-PH') + (total === 1 ? ' row' : ' rows'));
    drawSummary(st, summary);
    drawTable(st, columns, body);
    parts.filter((p) => p.rows && p.rows.length).forEach((p) => drawPart(st, columns, p));
    drawSignatures(st, signatures);
    finish(st, title, filename);
}

// ------------------------------------------------- The management report
// (Sakuna situation report, owner 2026-11-03): the period and filters, the
// executive summary, the key figures and the charts come before the tables.
// Charts are drawn as vector shapes, so they print sharp at any size.

const rgb = (hex) => [1, 3, 5].map((i) => parseInt(String(hex).slice(i, i + 2), 16));
const short = (n, money) => {
    const v = Number(n || 0);
    const a = Math.abs(v);
    const s = a >= 1e9 ? (v / 1e9).toFixed(a >= 1e10 ? 0 : 1) + 'B' : a >= 1e6 ? (v / 1e6).toFixed(a >= 1e7 ? 0 : 1) + 'M' : a >= 1e4 ? (v / 1e3).toFixed(0) + 'K' : v.toLocaleString('en-PH');
    return (money ? 'PHP ' : '') + s.replace(/\.0([KMB])$/, '$1');
};
/** A round axis top and step: 4 or 5 gridlines at 1, 2, 2.5 or 5 times a power of ten. */
function niceScale(max) {
    if (max <= 0) return { top: 1, step: 1 };
    if (max <= 5) return { top: Math.max(1, Math.ceil(max)), step: 1 };
    const raw = max / 4;
    const pow = Math.pow(10, Math.floor(Math.log10(raw)));
    const step = [1, 2, 2.5, 5, 10].map((m) => m * pow).find((x) => x >= raw);
    return { top: Math.ceil(max / step) * step, step };
}

function newPageIf(st, need) {
    if (st.y + need > st.page.h - st.margin - 6) {
        st.doc.addPage();
        st.y = st.margin;
        return true;
    }
    return false;
}

function sectionHeading(st, textValue) {
    const { doc, margin } = st;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(11, 23, 48);
    doc.text(clean(textValue), margin, st.y + 3);
    st.y += 6.5;
}

/** The reporting period and every filter, in a light box. */
function drawFilters(st, lines) {
    if (!lines || !lines.length) return;
    const { doc, margin, width } = st;
    const cols = 3;
    const rows = Math.ceil(lines.length / cols);
    const h = rows * 4.6 + 4;
    doc.setFillColor(241, 245, 251);
    doc.setDrawColor(214, 223, 238);
    doc.roundedRect(margin, st.y, width, h, 1.5, 1.5, 'FD');
    const cw = width / cols;
    lines.forEach(([k, v], i) => {
        const x = margin + 3 + (i % cols) * cw;
        const y = st.y + 4.6 + Math.floor(i / cols) * 4.6;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(70, 80, 100);
        const label = clean(k) + ':';
        doc.text(label, x, y);
        const lw = doc.getTextWidth(label) + 1.5;
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(20);
        doc.text(doc.splitTextToSize(clean(v), cw - 6 - lw)[0] || '', x + lw, y);
    });
    st.y += h + 3;
}

/** The headline figures as tiles, four to a row. */
function drawKpis(st, kpis) {
    if (!kpis || !kpis.length) return;
    const { doc, margin, width } = st;
    newPageIf(st, 46);
    sectionHeading(st, 'Key figures');
    const per = 4;
    const gap = 3;
    const w = (width - gap * (per - 1)) / per;
    const h = 17;
    kpis.forEach((k, i) => {
        const x = margin + (i % per) * (w + gap);
        const y = st.y + Math.floor(i / per) * (h + gap);
        doc.setFillColor(255);
        doc.setDrawColor(214, 223, 238);
        doc.roundedRect(x, y, w, h, 1.5, 1.5, 'FD');
        doc.setFillColor(30, 58, 138);
        doc.rect(x, y + 2, 1.1, h - 4, 'F');
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(80, 90, 110);
        doc.text(clean(k.label), x + 4, y + 4.8);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(11, 23, 48);
        doc.text(clean(k.value), x + 4, y + 11.2);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(100, 110, 128);
        doc.text(clean(k.note || ''), x + 4, y + 15);
    });
    st.y += Math.ceil(kpis.length / per) * (h + gap) + 2;
}

/** One chart in the box (x, y, w, h). */
function drawChart(doc, x, y, w, h, c) {
    const money = c.format === 'money';
    doc.setDrawColor(214, 223, 238);
    doc.setFillColor(255);
    doc.roundedRect(x, y, w, h, 1.5, 1.5, 'FD');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(11, 23, 48);
    doc.text(clean(c.title), x + 3, y + 5);
    let top = y + 8;
    if (c.note) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(110);
        doc.text(clean(c.note), x + 3, top + 1);
        top += 3;
    }
    // The legend, when there is more than one series.
    if (c.series.length > 1) {
        let lx = x + w - 3;
        doc.setFontSize(7);
        [...c.series].reverse().forEach((sr) => {
            const tw = doc.getTextWidth(clean(sr.name));
            lx -= tw;
            doc.setTextColor(60);
            doc.text(clean(sr.name), lx, y + 5);
            lx -= 3.5;
            doc.setFillColor(...rgb(sr.color));
            doc.rect(lx, y + 2.9, 2.5, 2.5, 'F');
            lx -= 3;
        });
    }
    const max = Math.max(...c.series.flatMap((sr) => sr.values.map(Number)));
    const { top: axisTop, step } = niceScale(max);
    const n = c.categories.length;
    const ns = c.series.length;
    doc.setFont('helvetica', 'normal');
    if (c.kind === 'bar') {
        // Horizontal bars: names on the left, values along the bottom.
        doc.setFontSize(7);
        const labelW = Math.min(w * 0.36, Math.max(...c.categories.map((t) => doc.getTextWidth(clean(t)))) + 2);
        const px = x + 3 + labelW;
        const pw = w - labelW - 14;
        const py = top + 2;
        const ph = y + h - 8 - py;
        for (let v = 0; v <= axisTop + 1e-9; v += step) {
            const gx = px + (v / axisTop) * pw;
            doc.setDrawColor(232, 237, 245);
            doc.line(gx, py, gx, py + ph);
            doc.setTextColor(120);
            doc.text(short(v, money), gx, py + ph + 3.5, { align: 'center' });
        }
        const band = ph / n;
        const bh = Math.min(5.5, (band * 0.72) / ns);
        c.categories.forEach((cat, i) => {
            const cy = py + i * band + (band - bh * ns) / 2;
            doc.setTextColor(40);
            const name = doc.splitTextToSize(clean(cat), labelW - 1)[0];
            doc.text(name, px - 1.5, cy + (bh * ns) / 2 + 1.1, { align: 'right' });
            c.series.forEach((sr, k) => {
                const v = Number(sr.values[i] || 0);
                const bw = (v / axisTop) * pw;
                doc.setFillColor(...rgb((sr.colors && sr.colors[i]) || sr.color));
                if (bw > 0) doc.rect(px, cy + k * bh, bw, bh * 0.9, 'F');
                doc.setTextColor(50);
                doc.text(short(v, money), px + bw + 1, cy + k * bh + bh * 0.62);
            });
        });
    } else {
        // Columns: values up the left, names along the bottom.
        doc.setFontSize(7);
        const labelW = Math.max(...Array.from({ length: Math.floor(axisTop / step) + 1 }, (_, i) => doc.getTextWidth(short(i * step, money))));
        const px = x + 4 + labelW;
        const pw = w - labelW - 8;
        const py = top + 3;
        const ph = y + h - 9 - py;
        for (let v = 0; v <= axisTop + 1e-9; v += step) {
            const gy = py + ph - (v / axisTop) * ph;
            doc.setDrawColor(232, 237, 245);
            doc.line(px, gy, px + pw, gy);
            doc.setTextColor(120);
            doc.text(short(v, money), px - 1.5, gy + 1, { align: 'right' });
        }
        const band = pw / n;
        const bw = Math.min(9, (band * 0.7) / ns);
        const every = Math.max(1, Math.ceil(n / Math.max(1, Math.floor(pw / 12))));
        // Every bar's value when there are few; on a long daily run, the bars that have one.
        const labelled = c.series.flatMap((sr) => sr.values).filter((v) => Number(v) > 0).length <= 16;
        c.categories.forEach((cat, i) => {
            const cx = px + i * band + (band - bw * ns) / 2;
            c.series.forEach((sr, k) => {
                const v = Number(sr.values[i] || 0);
                const bh = (v / axisTop) * ph;
                doc.setFillColor(...rgb((sr.colors && sr.colors[i]) || sr.color));
                if (bh > 0) doc.rect(cx + k * bw, py + ph - bh, bw * 0.92, bh, 'F');
                if (v > 0 && labelled) {
                    doc.setTextColor(50);
                    doc.text(short(v, money), cx + k * bw + bw / 2, py + ph - bh - 1, { align: 'center' });
                }
            });
            if ((n - 1 - i) % every === 0) {
                doc.setTextColor(80);
                doc.text(doc.splitTextToSize(clean(cat), Math.max(band * every, 10))[0], px + i * band + band / 2, py + ph + 3.5, { align: 'center' });
            }
        });
    }
}

/** The charts, two to a row; a row keeps together on one page. */
function drawCharts(st, charts) {
    if (!charts || !charts.length) return;
    const { margin, width } = st;
    const gap = 5;
    const w = (width - gap) / 2;
    const heightOf = (c) => (c.kind === 'bar' ? Math.min(92, Math.max(58, 22 + c.categories.length * c.series.length * 6.2)) : 62);
    newPageIf(st, 70);
    sectionHeading(st, 'Charts');
    for (let i = 0; i < charts.length; i += 2) {
        const pair = charts.slice(i, i + 2);
        const h = Math.max(...pair.map(heightOf));
        newPageIf(st, h + 2);
        pair.forEach((c, k) => drawChart(st.doc, margin + k * (w + gap), st.y, w, h, c));
        st.y += h + gap;
    }
}

/**
 * Several tables in one document (the Sakuna situation report, 2026-10-30):
 * the header once, then each section's heading, its note and its table.
 * sections: [{ heading, note?, columns: [{key, label}], rows: [...] }]; a
 * section with no rows says "None recorded." instead of an empty table.
 */
export async function reportPdf(sections, options = {}) {
    const { filename = 'report.pdf', title = 'Report', subtitle = '', office = '', filters = [], summary = [], kpis = [], charts = [] } = options;
    const st = await startDoc(title, (subtitle ? subtitle + '  •  ' : '') + 'Made ' + madeNow(), office);
    const { doc, page, margin, width } = st;
    // The management report's front matter, then the tables on a fresh page.
    drawFilters(st, filters);
    drawSummary(st, summary);
    drawKpis(st, kpis);
    drawCharts(st, charts);
    if (summary.length || kpis.length || charts.length) {
        doc.addPage();
        st.y = margin;
        sectionHeading(st, 'Detailed statistics and supporting tables');
    }
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
