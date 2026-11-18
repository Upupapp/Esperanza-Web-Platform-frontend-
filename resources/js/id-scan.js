// Scan ID, from the admin top bar (welfare round 2026-10-29): an officer
// points the camera at a presented ID or document - a lost child's PWD ID, a
// Digital ID on a phone, a certificate's QR - or types the code printed on it,
// and sees who it belongs to, whether it is still good, and for a minor or a
// PWD, how to reach their guardian (asked for with a reason; see
// resources/js/guardian-scan.js).
//
// A chunk of its own, fetched the first time the button is pressed
// (window.openIdScan in app.js): every admin page carries the button, and
// none should carry a camera loop and a QR reader it may never use. The
// window's markup waits in a <template> (components/admin/id-scan.blade.php)
// until then.
//
// The camera follows openCamera() in app.js: getUserMedia, the rear camera,
// stopped the moment the window closes or a code is read. The browser's own
// BarcodeDetector reads the QR where it has one (Chrome and Edge on Android,
// Safari 17+); elsewhere jsQR, loaded only then. Nothing leaves the device but
// the code that was read.

import { scanId, telHref, sectorName, sectorState, manilaToday, isPwd, revealProblem, REVEAL_REASONS, GUARDIAN_SOURCES } from './guardian-scan';

const DOC_STATES = {
    valid: { label: 'Valid', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    expired: { label: 'Expired', tone: 'bg-amber-50 text-amber-800 ring-amber-200' },
    revoked: { label: 'Revoked', tone: 'bg-rose-50 text-rose-700 ring-rose-200' },
};
const MATCHES = { issued_document: 'Issued document', digital_id: 'Digital ID', sector_id: 'ID number' };

// The camera and the reader live outside Alpine's reactive state: a
// MediaStream or a decoder wrapped in a proxy misbehaves, and nothing on
// screen depends on them directly.
let stream = null;
let reader = null;
let timer = null;
let run = 0;
// The look-up on screen: reset() and each new look-up move it on, so a slow
// answer for an earlier scan (closed, reopened, another ID scanned) is
// dropped instead of putting that person over the new one (2026-11-18).
let ask = 0;

function stopCamera() {
    run += 1;
    clearTimeout(timer);
    timer = null;
    stream?.getTracks().forEach((t) => t.stop());
    stream = null;
    const video = document.getElementById('id-scan-video');
    if (video) video.srcObject = null;
}

/** The QR reader: the browser's own where it reads QR codes, else jsQR. */
async function makeReader() {
    if ('BarcodeDetector' in window) {
        try {
            const formats = (await window.BarcodeDetector.getSupportedFormats?.()) || [];
            if (formats.includes('qr_code')) {
                const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                return async (video) => (await detector.detect(video))[0]?.rawValue || null;
            }
        } catch {
            // Fall through to jsQR.
        }
    }
    const jsQR = (await import('jsqr')).default;
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    return async (video) => {
        const w = video.videoWidth;
        const h = video.videoHeight;
        if (!w || !h) return null;
        // A smaller frame reads as well and much faster on a slow phone.
        const scale = Math.min(1, 720 / Math.max(w, h));
        canvas.width = Math.round(w * scale);
        canvas.height = Math.round(h * scale);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        return jsQR(image.data, image.width, image.height, { inversionAttempts: 'attemptBoth' })?.data || null;
    };
}

function cameraProblem(err) {
    const name = err?.name || '';
    if (name === 'NotAllowedError' || name === 'SecurityError') return 'The camera is blocked for this site. Allow it in the browser (the camera icon in the address bar), or type the code below.';
    if (name === 'NotFoundError' || name === 'OverconstrainedError') return 'No camera was found on this device. Type or paste the code below.';
    if (name === 'NotReadableError') return 'The camera is in use by another app. Close it and try again, or type the code below.';
    return 'The camera could not be opened. Type or paste the code below.';
}

function idScan() {
    return {
        open: false,
        // idle | starting | live | off (no camera here) | blocked
        camera: 'idle',
        cameraNote: '',
        code: '',
        // What was last looked up, for the reveal.
        asked: '',
        // idle | loading | found | notfound | toomany | error
        state: 'idle',
        result: null,
        failure: '',
        reasons: REVEAL_REASONS,
        rvOpen: false,
        rvReason: '',
        rvNote: '',
        rvBusy: false,
        rvError: '',
        copied: false,

        init() {
            // Escape, the backdrop and the X all close the window: the camera stops with it.
            this.$watch('open', (v) => { if (!v) stopCamera(); });
            window.addEventListener('pagehide', stopCamera);
        },
        show() {
            this.open = true;
            this.reset();
            this.$nextTick(() => this.startCamera());
            // The code box takes the focus: a keyboard user, or a handheld QR
            // scanner (which types what it reads and presses Enter), is ready
            // at once. After the window's own opening focus has settled.
            setTimeout(() => { if (this.open) document.getElementById('id-scan-code')?.focus({ preventScroll: true }); }, 320);
        },
        reset() {
            ask += 1;
            this.state = 'idle';
            this.result = null;
            this.failure = '';
            this.code = '';
            this.asked = '';
            this.closeReveal();
        },
        /** Scan another: back to the camera. */
        again() {
            this.reset();
            this.startCamera();
            this.$nextTick(() => document.getElementById('id-scan-code')?.focus({ preventScroll: true }));
        },

        // -- Camera
        get cameraLine() {
            if (this.camera === 'starting') return 'Opening the camera…';
            if (this.camera === 'live') return 'Hold the QR code inside the frame. It is read by itself.';
            return this.cameraNote;
        },
        async startCamera() {
            stopCamera();
            const mine = run;
            if (!navigator.mediaDevices?.getUserMedia) {
                this.camera = 'off';
                this.cameraNote = 'This browser cannot open a camera here. Type or paste the code below.';
                return;
            }
            this.camera = 'starting';
            this.cameraNote = '';
            let media;
            try {
                media = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            } catch (err) {
                if (mine !== run) return;
                this.camera = 'blocked';
                this.cameraNote = cameraProblem(err);
                return;
            }
            // Closed, or a code typed and checked, while the browser asked.
            if (mine !== run || !this.open || this.state !== 'idle') {
                media.getTracks().forEach((t) => t.stop());
                return;
            }
            stream = media;
            const video = document.getElementById('id-scan-video');
            if (!video) { stopCamera(); return; }
            video.srcObject = media;
            try { await video.play(); } catch { /* autoplay of a muted stream is allowed; keep going */ }
            this.camera = 'live';
            try {
                reader ??= await makeReader();
            } catch {
                if (mine !== run) return;
                stopCamera();
                this.camera = 'blocked';
                this.cameraNote = 'The QR reader could not load. Type or paste the code below.';
                return;
            }
            const tick = async () => {
                if (mine !== run) return;
                let text = null;
                try { text = await reader(video); } catch { /* a frame that could not be read */ }
                if (mine !== run) return;
                if (text) {
                    navigator.vibrate?.(60);
                    this.code = text.length > 120 ? '' : text;
                    this.lookup(text);
                    return;
                }
                timer = setTimeout(tick, 200);
            };
            tick();
        },

        // -- Looking it up
        submitTyped() {
            const value = this.code.trim();
            if (value) this.lookup(value);
        },
        async lookup(value) {
            if (this.state === 'loading') return;
            const mine = ++ask;
            stopCamera();
            this.camera = 'idle';
            this.state = 'loading';
            this.result = null;
            this.failure = '';
            this.asked = value;
            this.closeReveal();
            try {
                const res = await scanId(value);
                if (mine !== ask) return;
                this.result = res.data || null;
                this.state = this.result ? 'found' : 'notfound';
            } catch (err) {
                if (mine !== ask) return;
                if (err?.status === 404) this.state = 'notfound';
                else if (err?.status === 429) this.state = 'toomany';
                else {
                    this.state = 'error';
                    this.failure = err?.status === 403 ? 'Your role cannot look up IDs.' : (err?.explain ? err.explain('en') : 'Could not check this code right now.');
                }
            }
            window.Alpine?.nextTick(() => window.renderIcons?.());
        },

        // -- The result
        get holder() { return this.result?.holder || null; },
        get doc() { return this.result?.document || null; },
        get docState() { return DOC_STATES[this.doc?.status] || DOC_STATES.valid; },
        get matchLabel() { return MATCHES[this.result?.match] || 'Match'; },
        get pwd() { return isPwd(this.holder); },
        get sectors() {
            const today = manilaToday();
            return (this.holder?.sectors || []).map((s) => {
                const st = sectorState(s, today);
                return {
                    key: (s.sector || '') + (s.id_number || ''),
                    name: sectorName(s),
                    number: s.id_number || '',
                    category: s.category || '',
                    state: st,
                    tone: DOC_STATES[st].tone,
                    text: st === 'revoked' ? 'Revoked' : (st === 'expired' ? 'Expired ' + (s.valid_until || '') : (s.valid_until ? 'Valid until ' + s.valid_until : 'Valid, no expiry')),
                };
            });
        },
        get facts() {
            const h = this.holder || {};
            return [h.age != null ? h.age + ' years old' : null, h.sex, h.barangay ? 'Brgy. ' + h.barangay : null].filter(Boolean).join(' · ');
        },
        recordHref(ref) { return '/admin/constituents?ref=' + encodeURIComponent(ref || ''); },
        get guardian() { return this.result?.guardian || null; },
        tel: telHref,
        source(key) { return GUARDIAN_SOURCES[key]?.en || ''; },
        initials(name) { return String(name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('').toUpperCase(); },

        // -- The guardian's contact, asked for with a reason
        openReveal() {
            this.rvOpen = true;
            this.rvError = '';
            this.$nextTick(() => document.getElementById('id-scan-reason')?.focus());
        },
        closeReveal() {
            this.rvOpen = false;
            this.rvReason = '';
            this.rvNote = '';
            this.rvError = '';
            this.rvBusy = false;
        },
        get rvProblem() { return revealProblem(this.rvReason, this.rvNote); },
        async reveal() {
            if (this.rvBusy || this.rvProblem || !this.asked) return;
            const mine = ask;
            this.rvBusy = true;
            this.rvError = '';
            try {
                const res = await scanId(this.asked, { reveal: { reason: this.rvReason, note: this.rvNote } });
                if (mine !== ask) return;
                this.result = res.data || this.result;
                if (this.result?.guardian) this.rvOpen = false;
                else if (!this.result?.guardian_withheld) this.rvError = 'No guardian contact came back for this person.';
            } catch (err) {
                if (mine !== ask) return;
                if (err?.status === 429) this.rvError = 'You have reached the limit of 30 guardian look-ups an hour. Try again later, or contact the MSWDO.';
                else if (err?.status !== 403) this.rvError = err?.explain ? err.explain('en') : 'Could not get the guardian’s contact right now.';
            } finally {
                if (mine === ask) this.rvBusy = false;
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        async copyContact() {
            const text = this.guardian?.contact;
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch {
                // Selecting the number by hand still works.
            }
        },
    };
}

let mounted = false;

/** Registers the window (once) and opens it. */
export function open(Alpine) {
    if (!mounted) {
        const tpl = document.getElementById('id-scan-template');
        if (!tpl) return;
        Alpine.data('idScan', idScan);
        const host = tpl.content.firstElementChild.cloneNode(true);
        document.body.appendChild(host);
        Alpine.initTree(host);
        mounted = true;
    }
    window.dispatchEvent(new CustomEvent('id-scan-open'));
}
