// The graphs on a printed report (2026-10-28: "a report with graphs"). The
// backend says what to draw (a section's `chart`: bars, columns or pie, the
// column that labels each and the columns drawn); this draws it as SVG over
// the same table.
//
// Every colour, size and font is written on the element itself, not in the
// stylesheet: the PDF is made by html2canvas, which draws an inline SVG as an
// image without the page's CSS, so the screen, the printout and the PDF show
// the same picture. Each bar carries its number, so a copy printed in black
// and white still reads.

const FONT = 'Arial, Helvetica, sans-serif';
const INK = '#111111';
const MUTED = '#4b5563';
const GRID = '#dfe4ec';
const SERIES = ['#2457d6', '#d4a106', '#0f8a6c', '#c2410c'];
const PIE = { Female: '#d6457f', Male: '#2457d6', 'Not recorded': '#9aa3b2' };
const W = 680;

const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
const num = (v) => Number(v) || 0;
const fmt = (v, money) => (money ? '₱' : '') + num(v).toLocaleString('en-PH', { maximumFractionDigits: money ? 0 : 1 });
const isMoney = (section, key) => (section.columns || []).some((c) => c.key === key && c.format === 'money');

function text(x, y, value, attrs = '') {
    return `<text x="${x.toFixed(1)}" y="${y.toFixed(1)}" ${attrs}>${esc(value)}</text>`;
}

/** A round top for the axis and the step to it: 1, 2 or 5 times a power of ten, whole numbers for counts. */
function scale(max) {
    if (max <= 5) return { top: Math.max(1, Math.ceil(max)), step: 1 };
    const raw = max / 4;
    const pow = 10 ** Math.floor(Math.log10(raw));
    const step = [1, 2, 5, 10].map((m) => m * pow).find((s) => s >= raw);
    return { top: Math.ceil(max / step) * step, step };
}

/** A label in up to two lines of about `width` characters, the rest cut with an ellipsis. */
function wrap(label, width) {
    const words = String(label ?? '').split(/\s+/).filter(Boolean);
    const lines = [];
    let line = '';
    words.forEach((w) => {
        if (line && (line + ' ' + w).length > width) {
            lines.push(line);
            line = w;
        } else {
            line = line ? line + ' ' + w : w;
        }
    });
    if (line) lines.push(line);
    if (lines.length > 2) lines.splice(1, lines.length, lines.slice(1).join(' '));
    return lines.map((l) => (l.length > width ? l.slice(0, width - 1) + '…' : l));
}

function legend(series, x, y) {
    let out = '';
    let at = x;
    series.forEach((s, i) => {
        out += `<rect x="${at}" y="${y - 9}" width="11" height="11" rx="2" fill="${SERIES[i % SERIES.length]}"/>`;
        out += text(at + 16, y, s.label, `font-size="11" fill="${INK}"`);
        at += 16 + s.label.length * 6.2 + 18;
    });
    return out;
}

function svg(height, body, title) {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${Math.ceil(height)}" width="100%" font-family="${FONT}" role="img" aria-label="${esc(title)}"><title>${esc(title)}</title>${body}</svg>`;
}

/** One bar per row (as the table lists them), a group of bars when two columns are drawn. */
function bars(section, chart, title) {
    const series = chart.series || [];
    const rows = (section.rows || []).slice(0, 24);
    const max = Math.max(0, ...rows.flatMap((r) => series.map((s) => num(r[s.key]))));
    if (!rows.length || max === 0) return '';
    const money = series.map((s) => isMoney(section, s.key));
    const L = 220;
    const R = 64;
    const top = series.length > 1 ? 26 : 6;
    const barH = series.length > 1 ? 11 : 15;
    const gap = 3;
    const stack = series.length * (barH + gap) - gap;
    const rowH = Math.max(stack + 12, 30);
    const plotW = W - L - R;
    const { top: maxV, step } = scale(max);
    const x = (v) => L + (num(v) / maxV) * plotW;
    const bottom = top + rows.length * rowH;
    let out = '';
    for (let t = 0; t <= maxV + 1e-9; t += step) {
        out += `<line x1="${x(t).toFixed(1)}" y1="${top - 2}" x2="${x(t).toFixed(1)}" y2="${bottom}" stroke="${GRID}" stroke-width="1"/>`;
        out += text(x(t), bottom + 14, fmt(t, money[0] && series.length === 1), `font-size="10" fill="${MUTED}" text-anchor="middle"`);
    }
    rows.forEach((r, i) => {
        const y0 = top + i * rowH;
        const lines = wrap(r[chart.label], 36);
        lines.forEach((l, li) => {
            out += text(L - 8, y0 + rowH / 2 + (li - (lines.length - 1) / 2) * 13 + 4, l, `font-size="11" fill="${INK}" text-anchor="end"`);
        });
        series.forEach((s, si) => {
            const v = num(r[s.key]);
            const by = y0 + (rowH - stack) / 2 + si * (barH + gap);
            const w = v > 0 ? Math.max(1.5, x(v) - L) : 0;
            out += `<rect x="${L}" y="${by.toFixed(1)}" width="${w.toFixed(1)}" height="${barH}" rx="2" fill="${SERIES[si % SERIES.length]}"/>`;
            out += text(L + w + 5, by + barH / 2 + 4, fmt(v, money[si]), `font-size="10.5" fill="${INK}"`);
        });
    });
    out += `<line x1="${L}" y1="${top - 2}" x2="${L}" y2="${bottom}" stroke="${MUTED}" stroke-width="1"/>`;
    if (series.length > 1) out += legend(series, L, 14);
    return svg(bottom + 20, out, title);
}

/** Groups left to right (the months), each with a column per column drawn. */
function columns(section, chart, title) {
    const series = chart.series || [];
    const rows = section.rows || [];
    const max = Math.max(0, ...rows.flatMap((r) => series.map((s) => num(r[s.key]))));
    if (!rows.length || max === 0) return '';
    const money = series.map((s) => isMoney(section, s.key));
    const L = 46;
    const R = 8;
    const top = 30;
    const plotH = 170;
    const plotW = W - L - R;
    const { top: maxV, step } = scale(max);
    const y = (v) => top + plotH - (num(v) / maxV) * plotH;
    const gw = plotW / rows.length;
    const bw = Math.min(22, (gw * 0.74) / series.length);
    let out = '';
    for (let t = 0; t <= maxV + 1e-9; t += step) {
        out += `<line x1="${L}" y1="${y(t).toFixed(1)}" x2="${W - R}" y2="${y(t).toFixed(1)}" stroke="${GRID}" stroke-width="1"/>`;
        out += text(L - 6, y(t) + 3.5, fmt(t, money[0] && series.length === 1), `font-size="10" fill="${MUTED}" text-anchor="end"`);
    }
    rows.forEach((r, i) => {
        const gx = L + i * gw + (gw - bw * series.length) / 2;
        series.forEach((s, si) => {
            const v = num(r[s.key]);
            const h = v > 0 ? Math.max(1.5, top + plotH - y(v)) : 0;
            const bx = gx + si * bw;
            out += `<rect x="${bx.toFixed(1)}" y="${(top + plotH - h).toFixed(1)}" width="${(bw - 1.5).toFixed(1)}" height="${h.toFixed(1)}" rx="1.5" fill="${SERIES[si % SERIES.length]}"/>`;
            if (v > 0) out += text(bx + (bw - 1.5) / 2, top + plotH - h - 3, fmt(v, money[si]), `font-size="9" fill="${INK}" text-anchor="middle"`);
        });
        const [month, year] = String(r[chart.label] ?? '').split(' ');
        const cx = L + i * gw + gw / 2;
        out += text(cx, top + plotH + 14, month, `font-size="10.5" fill="${INK}" text-anchor="middle"`);
        if (year && (i === 0 || month === 'Jan')) out += text(cx, top + plotH + 26, year, `font-size="9.5" fill="${MUTED}" text-anchor="middle"`);
    });
    out += `<line x1="${L}" y1="${top + plotH}" x2="${W - R}" y2="${top + plotH}" stroke="${MUTED}" stroke-width="1"/>`;
    out += legend(series, L, 14);
    return svg(top + plotH + 32, out, title);
}

/** Shares of a whole (persons by sex, cases by how they came in): a ring with the total inside, and each part's number and share beside it. */
function pie(section, chart, title) {
    const key = chart.series?.[0]?.key;
    const rows = (section.rows || []).filter((r) => num(r[key]) > 0);
    const total = rows.reduce((s, r) => s + num(r[key]), 0);
    if (!key || total === 0) return '';
    const cx = 96;
    const cy = 84;
    const ro = 72;
    const ri = 44;
    const colorOf = (r, i) => PIE[r[chart.label]] || SERIES[i % SERIES.length];
    const point = (a, rad) => [cx + rad * Math.cos(a), cy + rad * Math.sin(a)];
    let out = '';
    let a0 = -Math.PI / 2;
    rows.forEach((r, i) => {
        const v = num(r[key]);
        if (v === total) {
            out += `<circle cx="${cx}" cy="${cy}" r="${(ro + ri) / 2}" fill="none" stroke="${colorOf(r, i)}" stroke-width="${ro - ri}"/>`;
            return;
        }
        const a1 = a0 + (v / total) * Math.PI * 2;
        const large = a1 - a0 > Math.PI ? 1 : 0;
        const [x0, y0] = point(a0, ro);
        const [x1, y1] = point(a1, ro);
        const [x2, y2] = point(a1, ri);
        const [x3, y3] = point(a0, ri);
        out += `<path d="M${x0.toFixed(2)} ${y0.toFixed(2)} A${ro} ${ro} 0 ${large} 1 ${x1.toFixed(2)} ${y1.toFixed(2)} L${x2.toFixed(2)} ${y2.toFixed(2)} A${ri} ${ri} 0 ${large} 0 ${x3.toFixed(2)} ${y3.toFixed(2)} Z" fill="${colorOf(r, i)}" stroke="#ffffff" stroke-width="1.5"/>`;
        a0 = a1;
    });
    out += text(cx, cy + 4, fmt(total), `font-size="20" font-weight="700" fill="${INK}" text-anchor="middle"`);
    // The ring counts what its column counts: persons (who was served) or
    // cases ("How the cases came in", flows review 2026-11-20).
    const [one, many] = key === 'people' ? ['person', 'persons'] : ['case', 'cases'];
    out += text(cx, cy + 20, total === 1 ? one : many, `font-size="10" fill="${MUTED}" text-anchor="middle"`);
    const lx = 220;
    const ly = cy - ((rows.length - 1) * 26) / 2;
    rows.forEach((r, i) => {
        const v = num(r[key]);
        const yy = ly + i * 26;
        out += `<rect x="${lx}" y="${yy - 10}" width="13" height="13" rx="3" fill="${colorOf(r, i)}"/>`;
        out += text(lx + 22, yy + 1, r[chart.label], `font-size="12.5" fill="${INK}"`);
        out += text(lx + 230, yy + 1, fmt(v), `font-size="12.5" font-weight="700" fill="${INK}" text-anchor="end"`);
        out += text(lx + 240, yy + 1, (Math.round((v * 1000) / total) / 10) + '%', `font-size="12" fill="${MUTED}"`);
    });
    return svg(cy + ro + 8, out, title);
}

/** The SVG for a report section's chart, or '' when it has none or nothing to draw. */
export function chartSvg(section) {
    const chart = section?.chart;
    if (!chart) return '';
    const title = String(section.title || '').replace(/^[A-Z]\.\s*/, '');
    if (chart.type === 'columns') return columns(section, chart, title);
    if (chart.type === 'pie') return pie(section, chart, title);
    return bars(section, chart, title);
}
