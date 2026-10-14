import api from './api/client';

// Shared document pieces (scope round 2026-10-15).
//
// - docTemplates: the wording staff edit in Settings > Templates (title,
//   subtitle, a note printed on the document, validity), read once from the
//   public GET /document-templates and applied wherever a document is drawn.
// - qrSvg: a QR code as inline SVG, the library loaded only when one is drawn.
// - verifyStrip: the control number, verification code and QR printed on an
//   issued document, leading to the website's Verify page.

let qrLib = null;
const loadQr = () => (qrLib ??= import('qrcode-generator').then((m) => m.default || m));

/** A QR code for `text` as an SVG string (black on white, quiet zone included). */
export async function qrSvg(text, cell = 3) {
    const qrcode = await loadQr();
    const qr = qrcode(0, 'M');
    qr.addData(String(text));
    qr.make();
    return qr.createSvgTag({ cellSize: cell, margin: 2, scalable: true });
}

export const docTemplatesStore = {
    loaded: false,
    loading: null,
    map: {},
    load() {
        if (this.loaded || this.loading) return this.loading;
        this.loading = api.get('/document-templates')
            .then((res) => {
                const map = {};
                (res.data || []).forEach((t) => { map[t.doc_key] = t; });
                this.map = map;
                this.loaded = true;
            })
            .catch(() => { this.loaded = true; })
            .finally(() => { this.loading = null; });
        return this.loading;
    },
    /** The edited title for a document type, else the one the page already has. */
    title(docKey, fallback) {
        this.load();
        return (docKey && this.map[docKey]?.cert_title) || fallback || '';
    },
    subtitle(docKey, fallback) {
        this.load();
        const t = docKey ? this.map[docKey] : null;
        return t && t.version > 1 ? (t.cert_subtitle || '') : (fallback || '');
    },
    note(docKey) {
        this.load();
        return (docKey && this.map[docKey]?.note) || '';
    },
};

/**
 * The strip at the foot of a document: the template's note, and - once the
 * document is issued - its control number, verification code and a QR code
 * that opens the Verify page.
 */
export function verifyStrip() {
    return {
        issued: null,
        note: '',
        svg: '',
        lastUrl: '',
        render(docs, docKey) {
            const list = Array.isArray(docs) ? docs : (docs ? [docs] : []);
            // The newest copy still valid (a reprint replaces the original on paper).
            const current = [...list].reverse().find((d) => d && !d.revoked) || null;
            this.issued = current;
            this.note = window.Alpine?.store('docTemplates')?.note(docKey) || '';
            const url = current?.verify_url || '';
            if (url && url !== this.lastUrl) {
                this.lastUrl = url;
                qrSvg(url).then((svg) => { if (this.lastUrl === url) this.svg = svg; }).catch(() => { this.svg = ''; });
            }
            if (!url) { this.svg = ''; this.lastUrl = ''; }
        },
        get verifyHost() {
            try { return new URL(this.issued?.verify_url || '').host + '/verify'; } catch { return ''; }
        },
    };
}
