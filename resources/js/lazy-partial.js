// Lazy partials (page budget, 2026-11-25): a window most visits never open
// (an import window, the Prepared Document editor, an Apply form) leaves the
// page's own HTML and comes as its own file, /partials/<name>.html, written by
// the export beside the pages. The page keeps a placeholder in the window's
// place:
//
//   <div class="contents" data-lazy-partial="/partials/x.html?v=..." x-lazy-partial="open">
//
// (x-lazy-partial in resources/views/components/lazy-partial.blade.php). The
// window's markup is put back into that placeholder once the page has loaded
// and the browser is idle, so the page ends up with the same elements as
// before, in the same place, under the same Alpine scope: same buttons, same
// live updates, same transitions. If the window is asked for before that (its
// expression turns true), it is fetched and put in straight away.
//
// Same origin (connect-src 'self'), no script in the file: CSP unchanged.

const fetched = new Map();

function fetchPartial(src) {
    if (!fetched.has(src)) {
        fetched.set(src, fetch(src, { credentials: 'same-origin' })
            .then((res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .catch((err) => {
                fetched.delete(src);
                throw err;
            }));
    }
    return fetched.get(src);
}

function whenIdle(fn) {
    const go = () => (window.requestIdleCallback ? window.requestIdleCallback(fn, { timeout: 2000 }) : setTimeout(fn, 200));
    if (document.readyState === 'complete') go();
    else window.addEventListener('load', go, { once: true });
}

export function registerLazyPartials(Alpine) {
    Alpine.directive('lazy-partial', (el, { expression }, { effect, evaluateLater, evaluate }) => {
        const src = el.dataset.lazyPartial;
        if (!src) return;
        let state = 'empty';

        const fill = (asked) => {
            if (state !== 'empty') return;
            state = 'loading';
            fetchPartial(src)
                .then((html) => {
                    if (!el.isConnected) { state = 'empty'; return; }
                    Alpine.mutateDom(() => { el.innerHTML = html; });
                    [...el.children].forEach((child) => Alpine.initTree(child));
                    state = 'ready';
                })
                .catch(() => {
                    state = 'empty';
                    if (!asked) return;
                    // The window was asked for and could not come: say so, and
                    // let the same button ask again.
                    if (/^[A-Za-z_$][\w$]*(\.[A-Za-z_$][\w$]*)*$/.test(expression)) {
                        try { evaluate(expression + ' = false'); } catch { /* read-only: nothing to reset */ }
                    }
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'This window could not load. Check the connection, then try again.', variant: 'error' } }));
                });
        };

        const asked = evaluateLater(expression || 'false');
        effect(() => asked((value) => { if (value) fill(true); }));
        whenIdle(() => fill(false));
    });
}
