// The Summary Report's PDF pages (loaded on the first PDF click only, with
// html2pdf). Two things html2pdf does not do on its own (2026-11-21):
//
// 1. Text where the browser puts it. html2canvas measures each font's
//    baseline with a 1x1 probe image in the LIVE document, not in its copy.
//    Tailwind's preflight makes every img display:block, so the probe drops
//    to its own line and the baseline comes out a whole line too low: every
//    table cell's text sat on its bottom border, and the paper's last line
//    (the "Generated from ..." footer) fell half off the last page. A style
//    on the live page, for the length of the render, keeps the probe inline.
//
// 2. Page breaks a reader can follow. A table that runs onto the next page is
//    split there and the part on the new page starts with the header row
//    again; a row, a paragraph, a graph with its heading, and the signatures
//    never break across two pages.

const PROBE = 'img[src^="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP"]';

/** Runs `render` with html2canvas's baseline probe kept inline on the live page. */
export async function withTrueBaseline(render) {
    const style = document.createElement('style');
    style.textContent = PROBE + ' { display: inline !important; }';
    document.head.appendChild(style);
    try {
        return await render();
    } finally {
        style.remove();
    }
}

const KEEP = '.rep-keep, .rep-end, .rep-summary > *, .rep-note, .rep-ident, .rep-title, .rep-period, .rep-filters, .rep-filters-note';

/**
 * Lays the copy html2pdf is about to draw (`root`, one column of pages each
 * `pageHeight` CSS px tall) out page by page. Measured live after each change,
 * top to bottom, so every move is seen by what follows.
 */
export function paginate(root, pageHeight) {
    const H = pageHeight;
    const top = root.getBoundingClientRect().top;
    const box = (el) => el.getBoundingClientRect();
    const page = (y) => Math.floor((y - top) / H);
    const crosses = (el) => {
        const r = box(el);
        return r.height > 0 && page(r.top) !== page(r.bottom - 0.5);
    };
    // A spacer before `el` that starts it on the next page (not when it is
    // already at a page's top, nor a block taller than a page; a table may be).
    const toNextPage = (el, long = false) => {
        const r = box(el);
        const room = H - ((r.top - top) % H);
        if (room >= H - 1 || (!long && r.height > H)) return false;
        const pad = document.createElement('div');
        pad.style.height = room + 'px';
        el.before(pad);
        return true;
    };

    const splitTable = (table) => {
        let t = table;
        for (let guard = 0; guard < 500; guard += 1) {
            // The first row that ends on a later page than the table starts on:
            // one cut by the page's edge, or one that just starts the next page.
            const rows = Array.from(t.querySelectorAll(':scope > tbody > tr')).filter((r) => box(r).height > 0);
            const first = page(box(t).top);
            const i = rows.findIndex((r) => page(box(r).bottom - 0.5) !== first);
            if (i < 0) return;
            if (i === 0) {
                // Not even one row fits: the table starts on the next page,
                // with its heading and graph when they fit there together.
                const head = t === table ? t.closest('.rep-section')?.querySelector(':scope > .rep-keep') : null;
                const target = head && box(rows[0]).bottom - box(head).top < H ? head : t;
                if (!toNextPage(target, target === t)) return;
                continue;
            }
            const row = rows[i];
            const next = t.cloneNode(false);
            const thead = t.querySelector(':scope > thead');
            if (thead) next.appendChild(thead.cloneNode(true));
            const body = row.parentNode;
            const rest = body.cloneNode(false);
            for (let r = row; r;) { const n = r.nextSibling; rest.appendChild(r); r = n; }
            next.appendChild(rest);
            for (let s = body.nextElementSibling; s;) { const n = s.nextElementSibling; next.appendChild(s); s = n; }
            t.after(next);
            toNextPage(next, true);
            t = next;
        }
    };

    root.querySelectorAll('table, ' + KEEP).forEach((el) => {
        if (el.tagName === 'TABLE') splitTable(el);
        else if (crosses(el)) toNextPage(el);
    });
}
