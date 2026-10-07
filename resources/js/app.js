import './bootstrap';
import api from './api/client';
import { resource } from './api/resource';
import { specForm } from './api/form';
import { residentProfileForm } from './resident-profile-form';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { createIcons, icons } from 'lucide';
import * as DokyuEditor from './dokyu-editor';

// x-trap gives modals a real focus trap and, on close, restores focus to the
// element that opened them. Both are required for a keyboard-only user to get
// back out of a dialog (TAB 10), and doing it in the plugin rather than by hand
// keeps Tab/Shift-Tab correct across x-teleport.
Alpine.plugin(focus);

window.Alpine = Alpine;
// The one HTTP client (resources/js/api/client.js). Exposed globally for the
// same reason Chart and html2pdf are: inline Alpine expressions call it
// directly. Do NOT add a per-page fetch() -- base URL, auth header, request id,
// timeout, cancellation and the 401/403/422/5xx mapping all live in there.
window.EsperanzaApi = api;
// TAB 11: chart.js and html2pdf.js are loaded ON DEMAND, not on every page.
// They were static imports, so the login screen shipped a charting library and
// a PDF engine (html2pdf bundles jsPDF and html2canvas) it can never use.
// Each loader caches its promise, so concurrent callers share one download.
let chartModulePromise = null;

window.loadChart = () => {
    if (!chartModulePromise) {
        chartModulePromise = import('chart.js').then((m) => {
            m.Chart.register(
                m.ArcElement, m.DoughnutController, m.LineElement, m.LineController,
                m.PointElement, m.BarElement, m.BarController, m.LinearScale,
                m.CategoryScale, m.Filler, m.Tooltip, m.Legend,
            );
            m.Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
            m.Chart.defaults.color = '#64748b';
            window.Chart = m.Chart;

            return m.Chart;
        });
    }

    return chartModulePromise;
};

let pdfModulePromise = null;

window.loadHtml2Pdf = () => {
    if (!pdfModulePromise) {
        pdfModulePromise = import('html2pdf.js').then((m) => {
            window.html2pdf = m.default;

            return m.default;
        });
    }

    return pdfModulePromise;
};

// Reading an uploaded PDF inside the portal (Esperanza bugs QA, 2026-10-06:
// staff had to download every document to read it). The backend serves
// uploads as downloads only ("never serve an uploaded file inline from the
// application origin", the backend master command's file-upload section), and
// that still holds: the file is fetched as data and PDF.js draws its pages as
// pictures. It is never opened as a document on the portal's own origin, and
// none of its scripts run (no evaluation, no XFA, no scripting layer).
// Loaded only when a PDF is first shown, like Chart.js above.
let pdfjsPromise = null;
window.loadPdfJs = () => {
    if (!pdfjsPromise) {
        pdfjsPromise = Promise.all([import('pdfjs-dist'), import('pdfjs-dist/build/pdf.worker.min.mjs?url')]).then(([pdfjs, worker]) => {
            pdfjs.GlobalWorkerOptions.workerSrc = worker.default;

            return pdfjs;
        });
    }

    return pdfjsPromise;
};

// What the viewer can show for an uploaded file: 'pdf', an image type
// (raster only: png, jpeg, gif, webp), or null for anything else. `mime` may
// also carry the file name, since a stored file's URL has no extension.
window.previewKind = (url, mime) => {
    if (!url) return null;
    const s = String(mime || '');
    const image = (ext) => 'image/' + (ext.toLowerCase() === 'jpg' ? 'jpeg' : ext.toLowerCase());
    if (/application\/pdf/i.test(s)) return 'pdf';
    let m = s.match(/image\/(png|jpe?g|gif|webp)\b/i);
    if (m) return image(m[1]);
    if (/\.pdf\b/i.test(s)) return 'pdf';
    m = s.match(/\.(png|jpe?g|gif|webp)\b/i);
    return m ? image(m[1]) : null;
};

// An uploaded photo, shown in the portal (Esperanza bugs QA follow-up,
// 2026-10-06: the eye icon showed "cannot be shown here" for every image).
// The API sends each file as a download (octet-stream, nosniff) from its own
// origin, so the page reads the bytes and shows them as the raster type the
// upload declared, the way PDF.js draws a PDF. Nothing in a raster image runs.
window.imageBlobUrl = async (url, type) => {
    const res = await fetch(url);
    if (!res.ok) throw new Error('file ' + res.status);
    return URL.createObjectURL(new Blob([await res.arrayBuffer()], { type }));
};

// An <img> source for a photo that may be the API's file (a download, which a
// plain <img> will not draw) or one just taken here (data: or blob:, drawn as
// is). The reopened proof of release was blank for exactly that reason
// (Esperanza QA, 2026-10-07).
window.viewableImage = async (url, mime) => {
    if (!url) return '';
    if (/^(data|blob):/.test(url)) return url;
    return window.imageBlobUrl(url, window.previewKind(url, mime) || 'image/jpeg');
};

// `canvasClass` styles each page; a thumbnail draws page 1 only, edge to edge.
window.renderPdfInto = async (el, url, { maxPages = 15, canvasClass = 'block w-full h-auto rounded-lg border border-slate-200 bg-white mb-2' } = {}) => {
    const pdfjs = await window.loadPdfJs();
    const res = await fetch(url);
    if (!res.ok) throw new Error('file ' + res.status);
    const doc = await pdfjs.getDocument({ data: await res.arrayBuffer(), isEvalSupported: false, enableXfa: false }).promise;
    el.replaceChildren();
    const width = el.clientWidth || 600;
    for (let n = 1; n <= Math.min(doc.numPages, maxPages); n++) {
        const page = await doc.getPage(n);
        const scale = (width / page.getViewport({ scale: 1 }).width) * Math.min(window.devicePixelRatio || 1, 2);
        const viewport = page.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        canvas.className = canvasClass;
        canvas.setAttribute('aria-label', 'Page ' + n + ' of ' + doc.numPages);
        el.appendChild(canvas);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    }

    return doc.numPages;
};

// Saving an uploaded file under a readable name (resident portal, 2026-10-07:
// "Aking mga Dokumento" had no download). The API's signed link is a download
// from its own origin, and a browser ignores a link's download name across
// origins, so the page reads the bytes and saves them itself.
window.downloadFile = async (url, name) => {
    const res = await fetch(url);
    if (!res.ok) throw new Error('file ' + res.status);
    const blobUrl = URL.createObjectURL(await res.blob());
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = name || 'document';
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
};

// A file name from a document's title and stored extension: no path or
// reserved characters, at most 80 characters, the extension once.
window.downloadName = (name, ext) => {
    let base = String(name || '').replace(/[\\/:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) || 'document';
    const e = String(ext || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    if (e && !base.toLowerCase().endsWith('.' + e)) base += '.' + e;
    return base;
};

// Windows stacked on one another: the Prepared Document over its request, or a
// document viewer or the camera opened inside one. Every <x-ui.modal> listens
// for Escape on the whole window, so one key closed the viewer AND the request
// behind it, and the officer was back on the list (live check, 2026-10-06).
// Escape now closes only the window opened last; a viewer or the camera
// ([data-modal-overlay]) closes itself first. The page behind stays locked
// while any window is still open.
const openLayers = [];
window.modalLayer = (layer, open) => {
    const i = openLayers.indexOf(layer);
    if (open && i === -1) openLayers.push(layer);
    if (!open && i !== -1) openLayers.splice(i, 1);
    document.body.classList.toggle('overflow-hidden', openLayers.length > 0);
    document.querySelector('main')?.classList.toggle('overflow-hidden', openLayers.length > 0);
};
window.closesOnEscape = (layer) => openLayers[openLayers.length - 1] === layer
    && ![...document.querySelectorAll('[data-modal-overlay]')].some((o) => o.getClientRects().length > 0);

// "Take Photo" for Proof of Release. A desktop browser ignores <input capture>,
// so the button opened the file picker like "Upload Photo" (Esperanza bugs QA,
// 2026-10-06). On a computer this opens the webcam in a dialog and resolves
// with the captured JPEG, or null when cancelled. It resolves undefined when
// the caller should fall back to the file input instead: on a phone (whose
// own camera input is better), with no camera, or with access refused.
window.takePhoto = async () => {
    if (window.matchMedia?.('(pointer: coarse)').matches || !navigator.mediaDevices?.getUserMedia) return undefined;
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
    } catch (e) {
        return undefined;
    }

    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center p-4 bg-navy-950/70';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Take a photo');
        overlay.setAttribute('data-modal-overlay', '');
        overlay.innerHTML = '<div class="bg-white rounded-2xl shadow-float w-full max-w-lg p-4 space-y-3">'
            + '<p class="text-sm font-semibold text-navy-900">Take a photo</p>'
            + '<video autoplay playsinline muted class="w-full rounded-xl bg-black aspect-video object-contain"></video>'
            + '<div class="flex justify-end gap-2">'
            + '<button type="button" data-act="cancel" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Cancel</button>'
            + '<button type="button" data-act="shoot" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl">Capture</button>'
            + '</div></div>';
        document.body.appendChild(overlay);
        const video = overlay.querySelector('video');
        video.srcObject = stream;
        // Escape cancels the camera only, not the request window under it.
        const onKey = (e) => { if (e.key === 'Escape') { e.stopPropagation(); done(null); } };
        const done = (file) => {
            stream.getTracks().forEach((t) => t.stop());
            overlay.remove();
            document.removeEventListener('keydown', onKey);
            resolve(file);
        };
        document.addEventListener('keydown', onKey);
        overlay.querySelector('[data-act=cancel]').addEventListener('click', () => done(null));
        overlay.querySelector('[data-act=shoot]').addEventListener('click', () => {
            if (!video.videoWidth) return;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            canvas.toBlob((blob) => done(blob ? new File([blob], 'proof-of-release-' + Date.now() + '.jpg', { type: 'image/jpeg' }) : null), 'image/jpeg', 0.9);
        });
        overlay.querySelector('[data-act=shoot]').focus();
    });
};

// Exposed globally for the same reason — the admin Dokyu "Edit Document"
// rich-text editor (document-requests.blade.php + document-editor-toolbar/
// document-editor-background-panel.blade.php) calls these directly from
// inline Alpine expressions.
window.DokyuEditor = DokyuEditor;
// Pairs with the body.dp-printing print-isolation rules in app.css — any
// page can toggle this class on before window.print() to show only its own
// #dp-print-area element; this listener is the one place that clears it
// back off afterward, regardless of which page's button triggered print.
window.addEventListener('afterprint', () => document.body.classList.remove('dp-printing'));

/**
 * 'super_admin' -> 'Super Admin'. The exact display name (with icon/color)
 * lives in the roles table and comes back from GET /admin/roles, but that
 * route is itself gated by perm:um_roles,view -- not every signed-in role can
 * call it. This title-cases the key so every account always has SOMETHING to
 * show immediately after login, independent of what that account can see.
 */
function formatRoleLabel(roleKey) {
    if (!roleKey) return '';

    return roleKey
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// GET /auth/me sends the role's grants as a list of {module, submodule,
// action} triples; canPerm() and canExportReports() look them up by
// submodule. Read as a map, the list matched nothing, so every officer,
// the municipal admin included, lost every action button behind canPerm()
// (Verify Account, Sakuna's create buttons...). Accepts either shape, so an
// account cached before this fix is read correctly too.
function permissionMap(raw) {
    if (!raw) return {};
    if (!Array.isArray(raw)) return raw;
    const map = {};
    for (const p of raw) {
        if (!p || !p.submodule || !p.action) continue;
        (map[p.submodule] ||= []).push(p.action);
    }
    return map;
}

function withPermissionMap(account) {
    return account ? { ...account, permissions: permissionMap(account.permissions) } : account;
}

// The resident's own details for the fields a request form asks again
// (their name, age, purok...), so they are not typed twice. Only fields the
// form left blank are filled, and they stay editable: a request can be for
// someone else.
function profileDefaults(fields, account) {
    if (!account) return {};
    const age = (() => {
        const dob = account.birthdate ? new Date(account.birthdate) : null;
        if (!dob || isNaN(dob)) return '';
        const today = new Date();
        let years = today.getFullYear() - dob.getFullYear();
        const months = today.getMonth() - dob.getMonth();
        if (months < 0 || (months === 0 && today.getDate() < dob.getDate())) years--;
        return years;
    })();
    const byKey = {
        requester_name: account.fullName,
        applicant_name: account.fullName,
        full_name: account.fullName,
        subject_name: account.fullName,
        age,
        purok: account.purok,
        address: account.address,
        date_of_birth: account.birthdate,
        civil_status: account.civilStatus,
        sex: account.sex,
        occupation: account.occupation,
    };
    const out = {};
    for (const field of fields || []) {
        const value = byKey[field.key];
        if (value === undefined || value === null || value === '') continue;
        if (!['text', 'number', 'date', 'select'].includes(field.type)) continue;
        if (field.type === 'select' && !(field.options || []).includes(value)) continue;
        out[field.key] = field.type === 'date' ? String(value).slice(0, 10) : value;
    }
    return out;
}

window.profileDefaults = profileDefaults;

// Why an uploaded file cannot be opened. The server links a file only once
// the malware scan has cleared it; every other case used to read "Still
// being scanned", including a scan that had failed and never would finish.
function fileNotice(file) {
    if (!file || file.url) return '';
    switch (file.scan_status) {
        case 'pending': return 'Being checked for viruses. Reopen this in a minute.';
        case 'infected': return 'Blocked: this file failed the virus check. Ask the resident for a new copy.';
        case 'error': return 'The virus check could not run, so the file cannot be opened yet. Ask ICT to rescan the uploads.';
        default: return 'This file cannot be opened right now. Try again later.';
    }
}

window.fileNotice = fileNotice;

// How a requirement line reads: the server's RequestRequirement::needsFile
// and the phone app's requirement_document_type.dart use the same rule.
// 'office': a step the office or the hall does (an interview, a thumbmark
// taken at the hall, 'None'); 'optional': the line says it is conditional;
// otherwise 'required'. The resident could not tell which they owed
// (Esperanza bugs QA, 2026-10-06).
function reqKind(label) {
    const l = String(label || '');
    if (/interview|case study|case assessment|details of the record being requested|thumbmark|to be taken at|^\s*none\b/i.test(l)) return 'office';
    if (/if applicable|if available|if any|required if|\bonly\s*$/i.test(l)) return 'optional';
    return 'required';
}
window.reqKind = reqKind;
window.reqKindLabel = (label) => {
    const en = window.Alpine?.store('lang')?.current === 'en';
    return { required: en ? 'Required' : 'Kailangan', optional: en ? 'If it applies' : 'Kung kailangan', office: en ? 'Done at the office' : 'Gagawin sa opisina' }[reqKind(label)];
};
window.reqKindClass = (label) => ({ required: 'bg-rose-50 text-rose-700', optional: 'bg-slate-100 text-slate-600', office: 'bg-sky-50 text-sky-700' }[reqKind(label)]);

// The status history's trigger codes, in words. "admin:action" says
// nothing the officer's name beside it does not already say.
const TRIGGER_NOTES = {
    'auto:first-open': 'automatically, when the request was first opened',
    'auto:requirement-flagged': 'automatically, when a document was flagged',
    'citizen:resubmit': 'resubmitted by the resident',
};

window.historyNote = (trigger) => TRIGGER_NOTES[trigger] || '';

// A path the API hands back unprefixed (`images/esperanza/...`), because the
// file it names is one of THIS site's own bundled static assets (public/...),
// not something the backend hosts. An <img> given that raw string resolves it
// against the current page instead of the site root and 404s — only visible
// once a row actually carries a path, which is why it went unnoticed: of the
// 20 barangays only Labangtaytay has ever had a seal file (B-finding
// 2026-10-05). Already-absolute values (http(s):// or a leading /) pass through.
// A Philippine mobile number, as every citizen form takes it: 11 digits,
// 09XXXXXXXXX, numbers only (Esperanza QA, 2026-10-07: contact fields took
// letters and any length). The API checks the same rule (PhilippineMobile).
window.phMobile = {
    // What may stay in the box as it is typed: digits, at most 11.
    clean(value) {
        return String(value ?? '').replace(/\D+/g, '').slice(0, 11);
    },
    // The problem with a number, or '' when it is fine. Empty is fine unless required.
    error(value, required = false, lang = 'en') {
        const v = String(value ?? '').trim();
        if (!v) return required ? (lang === 'en' ? 'Please enter a contact number.' : 'Pakilagay ang contact number.') : '';
        return /^09\d{9}$/.test(v) ? '' : (lang === 'en'
            ? 'Contact number must contain 11 digits and numbers only.'
            : 'Ang contact number ay dapat 11 digit at numero lamang.');
    },
    // On an <input>: keep only digits, and let the browser's own validity carry
    // the message, so a step that checks checkValidity() stops on it.
    bind(el, required = false) {
        el.value = this.clean(el.value);
        el.setCustomValidity(this.error(el.value, required, window.Alpine?.store('lang')?.current || 'en'));
        return el.value;
    },
};

window.staticAssetUrl = (path) => {
    if (!path) return null;
    return /^https?:\/\//.test(path) || path.startsWith('/') ? path : `/${path}`;
};

function readJson(key) {
    try {
        return JSON.parse(localStorage.getItem(key) || 'null');
    } catch {
        return null;
    }
}

function writeJson(key, value) {
    try {
        if (value === null) {
            localStorage.removeItem(key);
        } else {
            localStorage.setItem(key, JSON.stringify(value));
        }
    } catch {
        // Private mode with storage disabled -- the session still works for
        // this page load, it just will not survive a reload.
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.magic('api', () => api);

    // TAB 06: the four states (loading / empty / error / loaded) for any data
    // surface, in one place. See resources/js/api/resource.js for the three
    // rules it encodes -- chiefly that "empty" is never true until a load has
    // actually resolved, and that a failure never degrades to an empty list.
    Alpine.data('resource', resource);

    // TAB 07: one submission path for every write -- in-flight state, a
    // double-submit guard, 422 field errors mapped onto the same keys the spec
    // uses, input preserved on failure, and an unsaved-changes warning.
    Alpine.data('specForm', specForm);

    // The Resident Profile form on the website (live test 2026-10-07, UX-8).
    Alpine.data('residentProfileForm', residentProfileForm);

    // The staff site is English-only by owner ruling (TAB 09). The document
    // editor it shares with the resident site follows this store, so staff saw
    // "Kanselahin" and "I-save ang Pagbabago" beside the English buttons.
    const staffSite = window.location.pathname.startsWith('/admin');
    // The page's own lang follows the language shown, so a screen reader reads
    // Filipino with a Filipino voice (live test 2026-10-07, UX-6).
    const applyDocumentLang = (lang) => { document.documentElement.lang = lang === 'en' ? 'en' : 'fil'; };
    Alpine.store('lang', {
        current: staffSite ? 'en' : (localStorage.getItem('esperanza_lang') || 'fil'),
        set(lang) {
            this.current = lang;
            localStorage.setItem('esperanza_lang', lang);
            applyDocumentLang(lang);
        },
    });
    applyDocumentLang(Alpine.store('lang').current);
    // Settings that reach the portals before anyone signs in (GET /portal-settings):
    // the Default Language, until the resident picks one (not stored, so a later
    // change by the LGU still applies), and the Branding in force.
    api.get('/portal-settings')
        .then((res) => {
            const lang = res?.data?.default_language;
            if (!staffSite && (lang === 'en' || lang === 'fil') && !localStorage.getItem('esperanza_lang')) { Alpine.store('lang').current = lang; applyDocumentLang(lang); }
            applyBranding(res?.data?.branding ?? null, true);
        })
        .catch(() => {});

    // Real personnel session for the Administrative Portal, backed by
    // Sanctum bearer tokens (production-readiness programme, 2026-09-25).
    // The account object is cached to localStorage for a warm reload, but it
    // is never the source of truth: refresh() always re-fetches GET /auth/me
    // and overwrites it, so a newly granted module or edited role bundle
    // (see UserManagementTest) is visible on the very next request rather
    // than only after a manual logout/login.
    //
    // No session -> fail CLOSED (TAB 08). What this gating is for: hiding
    // what the user cannot use. It is NOT the security boundary. The server
    // enforces every permission triple, and a check here must never be
    // presented as protection.
    Alpine.store('session', {
        account: withPermissionMap(readJson('esperanza_session')),
        booting: false,

        setAccount(raw) {
            this.account = {
                id: raw.employee_id,
                name: raw.name,
                roleKey: raw.role,
                role: formatRoleLabel(raw.role),
                dept: raw.dept,
                scope: raw.scope,
                status: raw.status,
                moduleKeys: raw.moduleKeys || [],
                permissions: permissionMap(raw.permissions),
                // A password an administrator set: My Profile opens by itself and
                // asks for the officer's own before anything else (PR-8).
                mustChangePassword: !!raw.must_change_password,
            };
            writeJson('esperanza_session', this.account);
        },

        /** Step 1 (and, if MFA is required, step 2) of personnel sign-in. */
        async login(identifier, password, mfaCode = null) {
            const result = await api.post('/auth/personnel/login', { identifier, password, mfa_code: mfaCode });
            const data = result.data;

            // Even an MFA-pending login issues a token — one that can do
            // nothing but answer the challenge (see AuthController).
            api.setToken(data.token);

            // The account's real scope, so the sign-in page can tell an
            // officer who chose a different one (the choice is not a filter).
            const scope = data.account?.scope ?? null;

            if (data.mfa?.required && !data.mfa.satisfied) {
                return { mfaRequired: true, scope };
            }

            this.setAccount(data.account);

            return { mfaRequired: false, scope };
        },

        /** Re-derive the account from the server. Never trust a cached copy for a decision. */
        async refresh() {
            this.booting = true;
            this.pending = api.get('/auth/me')
                .then((result) => this.setAccount(result.data))
                .finally(() => { this.booting = false; });
            return this.pending;
        },

        /** True once the account carries its permissions (sign-in sends none; /auth/me does). */
        get permsReady() {
            return Object.keys(this.account?.permissions || {}).length > 0;
        },

        /**
         * Resolves when canPerm() can be trusted: at once with the cached copy,
         * otherwise after the /auth/me every page load runs. A page asks this
         * before calling an endpoint the role may not hold, because every 403
         * pops up "Walang pahintulot" (QA run 2026-10-05).
         */
        whenReady() {
            if (this.permsReady || !this.pending) return Promise.resolve(this.account);
            return this.pending.then(() => this.account, () => this.account);
        },

        clear() {
            this.account = null;
            writeJson('esperanza_session', null);
        },

        async logout() {
            try {
                await api.post('/auth/logout');
            } catch {
                // Token already invalid or the request never landed -- the
                // session is cleared locally either way.
            }
            api.setToken(null);
            this.clear();
        },

        can(moduleKey) {
            if (!this.account) return false;
            if (this.account.roleKey === 'super_admin') return true;
            return (this.account.moduleKeys || []).includes(moduleKey);
        },
        canPerm(submoduleKey, permission) {
            if (!this.account) return false;
            // The Super Administrator can do everything, even actions no role
            // carries (Reject on a barangay certificate): the server agrees.
            if (this.account.roleKey === 'super_admin') return true;
            const perms = (this.account.permissions || {})[submoduleKey];
            if (!perms) return false;
            return Array.isArray(perms) ? perms.includes(permission) : perms === '*';
        },
        inScope(barangay) {
            if (!this.account) return false;
            if (this.account.roleKey === 'super_admin') return true;
            if (this.account.scope === 'Municipal') return true;
            return this.account.scope === barangay;
        },
        // Department-based row visibility for Dokyu/Tulong, mirroring inScope()
        // above. Only Department Head / Department Staff are office-restricted —
        // Front Desk, Records Officer, Municipal/Super Admin see across offices
        // (per the Dokyu & Tulong routing plan's role x scope matrix).
        inDeptScope(office) {
            if (!this.account) return false;
            if (!['department_head', 'department_staff'].includes(this.account.roleKey)) return true;
            return this.account.dept === office;
        },
        // Report-download gate: true if the account has 'export' on any
        // Reports & Analytics submodule (rep_population/assistance/document/
        // financial). Matches config/esperanza_rbac.php's 'reports' grant —
        // Super Admin, Municipal Admin, Department Head, and Records Officer
        // get it via the 'full'/'view_export' preset; Information Officer
        // (view-only) and everyone else without the reports module do not.
        canExportReports() {
            if (!this.account) return false;
            if (this.account.roleKey === 'super_admin') return true;
            const perms = this.account.permissions || {};
            return ['rep_population', 'rep_assistance', 'rep_document', 'rep_financial'].some((k) => (perms[k] || []).includes('export'));
        },
        initials() {
            if (!this.account) return 'GP';
            return this.account.name
                .split(/\s+/)
                .filter(Boolean)
                .map((part) => part[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();
        },
    });

    // Real citizen session for the Citizen Portal, backed by the same
    // Sanctum bearer tokens as $store.session above (production-readiness
    // programme, 2026-09-25). refresh() re-reads GET /citizen/profile, the
    // one endpoint with every field the portal's views already read this
    // account object for (name parts, contact, barangay, verification tier).
    Alpine.store('citizenSession', {
        account: readJson('esperanza_citizen_session'),

        setAccount(p) {
            this.account = {
                id: p.account_no,
                firstName: p.first_name,
                middleName: p.middle_name,
                lastName: p.last_name,
                suffix: p.suffix,
                fullName: p.name,
                email: p.email,
                mobile: p.mobile,
                barangay: p.barangay,
                purok: p.purok,
                address: p.address,
                birthdate: p.birthdate,
                sex: p.sex,
                civilStatus: p.civil_status,
                occupation: p.occupation,
                residentSince: p.resident_since,
                status: p.status,
                accessLevel: p.access_level,
                fullServiceAccess: p.full_service_access,
                capabilities: p.capabilities,
                profileCompleteness: p.profile_completeness,
                identityLocked: p.identity_locked,
            };
            writeJson('esperanza_citizen_session', this.account);
        },

        async login(identifier, password) {
            const result = await api.post('/auth/citizen/login', { identifier, password });
            api.setToken(result.data.token);
            await this.refresh();
        },

        async refresh() {
            const result = await api.get('/citizen/profile');
            this.setAccount(result.data);
        },

        clear() {
            this.account = null;
            writeJson('esperanza_citizen_session', null);
        },

        async logout() {
            try {
                await api.post('/auth/logout');
            } catch {
                // Same reasoning as $store.session.logout() above.
            }
            api.setToken(null);
            this.clear();
        },

        initials() {
            if (!this.account) return 'CI';
            return ((this.account.firstName?.[0] || '') + (this.account.lastName?.[0] || '')).toUpperCase();
        },
    });

    // Notification centers, real for both portals (production-readiness
    // programme, 2026-09-25). GET {citizen|admin}/notifications and
    // POST .../notifications/{id}/read are the same ModuleController on the
    // backend; category IS the request type ('dokyu' | 'tulong') for a
    // Dokyu/Tulong lifecycle notification, so it doubles as the existing
    // 'portal' badge with no extra mapping. `dismiss` below is
    // session-only — there is no backend endpoint to delete a notification,
    // only to mark one read, so hiding one from view does not survive a
    // reload; that is a real, intentional gap (see PRODUCTION_READINESS.md).
    // Settings → Branding, applied to both portals. Tailwind 4 compiles the
    // brand colours to --color-brand-* variables, so a theme is a set of those.
    // The last branding seen is kept so the next page opens in it at once.
    const BRAND_THEMES = {
        'Brand Blue': null,
        Navy: ['#eef2f9', '#dbe3f2', '#b9c7e4', '#8ea3cf', '#5f78b2', '#3d5795', '#2c437a', '#243766', '#1d2c52', '#172340'],
        Gold: ['#fdf9e7', '#fbf0c2', '#f7e28a', '#f2d24f', '#ecc02a', '#c99716', '#a57a12', '#8a6412', '#6f4f10', '#5a400e'],
        Emerald: ['#ecfdf5', '#d1fae5', '#a7f3d0', '#6ee7b7', '#34d399', '#10b981', '#059669', '#047857', '#065f46', '#064e3b'],
    };
    function applyBranding(branding, remember = false) {
        const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
        const root = document.documentElement;
        const palette = branding ? (BRAND_THEMES[branding.theme] ?? null) : null;
        shades.forEach((shade, i) => {
            if (palette) root.style.setProperty('--color-brand-' + shade, palette[i]);
            else root.style.removeProperty('--color-brand-' + shade);
        });
        const name = branding?.municipality_name;
        if (name) {
            document.querySelectorAll('[data-brand-name]').forEach((el) => { el.textContent = name + ' LGU'; });
            document.title = document.title.replace(/Esperanza LGU/g, name + ' LGU');
        }
        if (remember) {
            try { localStorage.setItem('esperanza_branding', JSON.stringify(branding)); } catch { /* applied for this page only */ }
        }
    }
    try { applyBranding(JSON.parse(localStorage.getItem('esperanza_branding') || 'null')); } catch { /* nothing remembered */ }
    document.addEventListener('DOMContentLoaded', () => {
        try { applyBranding(JSON.parse(localStorage.getItem('esperanza_branding') || 'null')); } catch { /* nothing remembered */ }
    });

    const notificationStyle = {
        dokyu: ['file-text', 'brand'], tulong: ['hand-heart', 'purple'],
        account: ['user-round', 'indigo'], community: ['megaphone', 'teal'],
        events: ['calendar', 'amber'], sakuna: ['shield-alert', 'rose'],
        summary: ['calendar-range', 'indigo'],
    };

    function makeNotificationStore(path) {
        return {
            items: [],
            state: 'idle',
            error: null,
            dismissed: [],
            // The server's count of every unread notification (meta.unread),
            // not just the page loaded here: the number on the bell.
            serverUnread: null,

            get loading() { return this.state === 'loading'; },
            get failed() { return this.state === 'error'; },
            get visible() { return this.items.filter((n) => !this.dismissed.includes(n.id)); },
            get unreadCount() { return this.serverUnread ?? this.visible.filter((n) => n.unread).length; },

            // quiet: a background refresh keeps what is on screen until the answer comes.
            async load(quiet = false) {
                if (!quiet) this.state = 'loading';
                this.error = null;
                try {
                    const result = await api.get(path);
                    this.serverUnread = Number.isInteger(result.meta?.unread) ? result.meta.unread : null;
                    this.items = (result.data || []).map((n) => {
                        const [icon, color] = notificationStyle[n.category] || ['bell', 'brand'];
                        const lang = window.Alpine?.store('lang')?.current || 'fil';

                        return {
                            id: n.id,
                            portal: n.category,
                            icon, color,
                            title: n.title?.[lang] || n.title?.en || '',
                            titleFil: n.title?.fil, titleEn: n.title?.en,
                            body: n.body?.[lang] || n.body?.en || '',
                            bodyFil: n.body?.fil, bodyEn: n.body?.en,
                            pill: n.pill, ref: n.ref, link: n.link,
                            pinned: n.pinned, unread: n.unread,
                            popup: n.popup === true,
                            createdAt: n.time,
                        };
                    });
                    this.state = 'loaded';
                    // The rows' <i data-lucide> placeholders only become icons
                    // when Lucide runs after Alpine has drawn them.
                    window.Alpine?.nextTick(() => window.renderIcons?.());
                } catch (err) {
                    this.error = err;
                    this.state = 'error';
                }
            },

            async markRead(id) {
                const n = this.items.find((i) => i.id === id);
                if (!n || !n.unread) return;
                n.unread = false;
                if (this.serverUnread) this.serverUnread--;
                try {
                    await api.post(`${path}/${id}/read`);
                } catch {
                    n.unread = true; // the server did not accept it — undo the optimistic flip
                    if (this.serverUnread !== null) this.serverUnread++;
                }
            },

            // One call clears every unread notification, including ones on
            // pages not loaded here, and it holds after a refresh.
            async markAllRead() {
                const before = this.items.filter((n) => n.unread).map((n) => n.id);
                const count = this.serverUnread;
                this.items.forEach((n) => { n.unread = false; });
                this.serverUnread = 0;
                try {
                    await api.post(`${path}/read-all`);
                } catch {
                    this.items.forEach((n) => { if (before.includes(n.id)) n.unread = true; });
                    this.serverUnread = count;
                }
            },

            // New notifications arrive without a reload: every minute while the
            // tab is in view. The bell only loaded once per page before.
            poll(ms = 60000) {
                if (this.timer) return;
                this.timer = setInterval(() => {
                    if (document.visibilityState === 'visible' && this.state !== 'loading') this.load(true);
                }, ms);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && this.state !== 'loading') this.load(true);
                });
            },

            // A Sakuna alert the resident has not seen yet opens as a pop-up once.
            shownAlerts: (() => { try { return JSON.parse(localStorage.getItem('esperanza_shown_alerts') || '[]'); } catch { return []; } })(),
            get pendingAlert() {
                return this.visible.find((n) => n.portal === 'sakuna' && n.popup && n.unread && !this.shownAlerts.includes(n.id)) || null;
            },
            // The notice that the account is verified opens once as a welcome
            // (live test 2026-10-07, UX-9): it was only a red dot on the bell
            // while Dokyu and Tulong still said to wait for approval.
            get pendingWelcome() {
                return this.visible.find((n) => n.portal === 'account' && n.pill === 'Verified' && n.unread && !this.shownAlerts.includes(n.id)) || null;
            },
            alertShown(id) {
                this.shownAlerts = [...this.shownAlerts, id].slice(-50);
                try { localStorage.setItem('esperanza_shown_alerts', JSON.stringify(this.shownAlerts)); } catch { /* the pop-up just may show again */ }
                this.markRead(id);
            },

            dismiss(id) {
                this.dismissed = [...this.dismissed, id];
            },
        };
    }

    Alpine.store('citizenNotifications', makeNotificationStore('/citizen/notifications'));
    Alpine.store('adminNotifications', makeNotificationStore('/admin/notifications'));

    // The sidebar's Dokyu/Tulong badges. They were fixed numbers in the markup
    // (18 and 9 for staff, 2 and 1 for residents) whatever the queues held.
    // Staff see new requests waiting to be opened (Submitted or Resubmitted)
    // in their own scope; a resident sees their own requests still in
    // progress. Each is one per_page=1 list read for meta.total, once per
    // page; a count that cannot be read stays null and the badge stays hidden.
    Alpine.store('queueCounts', {
        dokyu: null,
        tulong: null,
        loaded: false,
        async load(side) {
            if (this.loaded) return;
            this.loaded = true;
            const total = (result) => result?.meta?.total ?? null;
            if (side === 'admin') {
                const session = Alpine.store('session');
                if (!session.account) return;
                // Waiting on a first look, in each type's own vocabulary: a
                // resident's corrections make a Dokyu request Resubmitted and
                // return a Tulong case to Initial Screening.
                const waiting = { dokyu: 'Submitted,Resubmitted', tulong: 'Submitted,Initial Screening' };
                const count = (type) => (session.can(type)
                    ? api.get('/admin/' + type + '/requests', { params: { status: waiting[type], per_page: 1 } }).then(total).catch(() => null)
                    : Promise.resolve(null));
                [this.dokyu, this.tulong] = await Promise.all([count('dokyu'), count('tulong')]);
            } else {
                if (!Alpine.store('citizenSession').account) return;
                // Still open, in each type's own vocabulary (Tulong's is
                // config/esperanza_tulong_lifecycle.php, not the fifteen).
                const open = {
                    dokyu: 'Submitted,Under Verification,Under Review,Resubmitted,Approved,Mark to Release',
                    tulong: 'Submitted,Initial Screening,Needs Requirements,Assessment,For Approval,Returned for Reassessment,Assistance Processing,Ready for Release',
                };
                const count = (type) => api.get('/citizen/requests', { params: { type, status: open[type], per_page: 1 } }).then(total).catch(() => null);
                [this.dokyu, this.tulong] = await Promise.all([count('dokyu'), count('tulong')]);
            }
        },
    });

    // Per-barangay directory (see components/layouts/citizen.blade.php, which
    // injects window.__barangayCatalog from config/esperanza_barangay_documents.php).
    // `.mine` lets any Citizen Portal page show the signed-in resident's own
    // barangay seal/officials/contact without re-declaring the catalog per view.
    // The per-barangay directory (seal, officials, contact, history), read
    // from GET /barangays instead of a window.__barangayCatalog the layout
    // used to inject from config/esperanza_barangay_documents.php
    // (production-readiness programme, 2026-09-25). Public and unauthenticated
    // — a resident chooses their barangay before they have an account (see
    // auth/citizen-register.blade.php). `officials` here is the two-name
    // captain/secretary pair the barangays table actually stores; the fuller
    // per-official roster with photos/committees that citizen/directory's
    // barangay profile panel used to show from a fixture is NOT backed by
    // any column yet (see PRODUCTION_READINESS.md).
    Alpine.store('barangay', {
        catalog: {},
        state: 'idle',
        async load() {
            if (this.state === 'loading' || this.state === 'loaded') return;
            this.state = 'loading';
            try {
                const result = await api.get('/barangays');
                const map = {};
                (result.data || []).forEach((b) => {
                    map[b.name] = {
                        label: b.name,
                        // Deliberately raw: every citizen-facing template that
                        // reads $store.barangay.mine?.seal (sidebar, dashboard,
                        // profile, directory, document-requests) already wraps
                        // it with `{{ rtrim(asset(''), '/') }}/` + seal itself.
                        // Prefixing it here too would double it. Only the admin
                        // Balita page skipped that wrapping — fixed there
                        // instead, in its own component (see staticAssetUrl).
                        seal: b.seal_path,
                        officials: b.officials || {},
                        contact: b.contact,
                        history: b.history,
                    };
                });
                this.catalog = map;
                this.state = 'loaded';
            } catch {
                this.state = 'error';
            }
        },
        get mine() {
            const b = Alpine.store('citizenSession').account?.barangay;
            return b ? this.catalog[b] || null : null;
        },
    });
    Alpine.store('barangay').load();

    // On any full page load, if a bearer token exists, re-validate the
    // session against the server rather than trusting what localStorage
    // cached at login -- a revoked token, an edited role bundle (see
    // UserManagementTest), or an account archived from another tab must all
    // take effect on the very next request, not only after a manual
    // logout/login.
    if (api.getToken()) {
        const bootStore = window.location.pathname.startsWith('/admin')
            ? Alpine.store('session')
            : Alpine.store('citizenSession');
        bootStore.refresh().catch(() => {
            // A 401 already dispatches api:unauthorized and redirects; any
            // other failure (offline, 5xx) just leaves the cached account in
            // place for this page load.
        });
    }

    // <div x-data="pdfPreview" x-effect="show(url, mime)"><div x-ref="pages"></div></div>
    // Draws a PDF in place (window.renderPdfInto); anything else is left to
    // the page's own image preview or download link.
    // Also shows an uploaded photo: <img x-show="image" :src="image"> inside.
    Alpine.data('pdfPreview', () => ({
        loading: false,
        failed: false,
        pages: 0,
        shown: null,
        image: null,
        async show(url, mime) {
            const kind = window.previewKind(url, mime);
            if (!kind) { this.reset(); return; }
            if (this.shown === url) return;
            this.reset();
            this.shown = url;
            this.loading = true;
            try {
                if (kind === 'pdf') {
                    this.pages = await window.renderPdfInto(this.$refs.pages, url);
                } else {
                    const blobUrl = await window.imageBlobUrl(url, kind);
                    // Another file was opened meanwhile: drop this one.
                    if (this.shown !== url) { URL.revokeObjectURL(blobUrl); return; }
                    this.image = blobUrl;
                    this.pages = 1;
                }
            } catch (e) {
                if (this.shown === url) this.failed = true;
            } finally {
                if (this.shown === url) this.loading = false;
            }
        },
        reset() {
            if (this.image) URL.revokeObjectURL(this.image);
            this.image = null;
            this.shown = null;
            this.pages = 0;
            this.failed = false;
            this.loading = false;
            this.$refs.pages?.replaceChildren();
        },
        destroy() {
            if (this.image) URL.revokeObjectURL(this.image);
        },
    }));

    Alpine.data('dropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));

    Alpine.data('sidebar', () => ({
        collapsed: false,
        mobileOpen: false,
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
        },
    }));

    // A doughnut with its total in the centre (dashboard, Reports, Constituents).
    // The tooltip landed on the centre total and was clipped by the small canvas,
    // so neither could be read (2026-10-07). No tooltip now: the centre shows the
    // slice under the pointer (its number and name) and the total otherwise.
    //   <div x-data="centerDonut()" x-effect="draw(rows)" @mouseleave="hover = null">
    //     <canvas x-ref="canvas"></canvas> ... x-text="hover ? hover.value : total"
    // The chart object stays out of Alpine's reactive state.
    Alpine.data('centerDonut', (opts = {}) => {
        let chart = null;
        return {
            hover: null,
            async draw(rows) {
                const list = (rows || []).map((r) => ({ label: r.label, value: Number(r.value) || 0, color: r.color }));
                this.hover = null;
                const Chart = await window.loadChart();
                const canvas = this.$refs.canvas;
                if (!canvas || !canvas.isConnected) return;
                if (chart) { chart.destroy(); chart = null; }
                Chart.getChart?.(canvas)?.destroy();
                if (!list.length) return;
                chart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: list.map((r) => r.label),
                        datasets: [{
                            data: list.map((r) => r.value),
                            backgroundColor: list.map((r) => r.color),
                            borderColor: '#ffffff',
                            borderWidth: opts.borderWidth ?? 0,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        cutout: opts.cutout || '72%',
                        layout: { padding: 6 },
                        maintainAspectRatio: false,
                        animation: { duration: 600, easing: 'easeOutQuart' },
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        onHover: (event, active) => {
                            this.hover = active.length ? list[active[0].index] : null;
                        },
                    },
                });
            },
            destroy() {
                if (chart) chart.destroy();
                chart = null;
            },
        };
    });

    // ---- Clay charts (design_handoff_dashboard_clay README "Donut cards",
    // "Requests trend"; STYLE_GUIDE §3.13). SVG built here, so the markup
    // stays a <g x-ref> and the charts need no charting library.
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const svgEl = (name, attrs = {}) => {
        const el = document.createElementNS(SVG_NS, name);
        Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
        return el;
    };
    // Chart palette, largest slice first (§3.13).
    const CLAY_PALETTE = ['#1d47d6', '#5687ff', '#ffe11a', '#0b1730', '#b9c7e8', '#2f62f5', '#e8c200', '#24396b', '#8ab0ff', '#7a5e00'];

    // <x-ui.clay-donut rows="..."> -- rounded arcs with even ~10px gaps, a 16°
    // minimum slice (the legend keeps the exact share), the hovered slice pops
    // out 7px and the rest dim; the centre and the legend follow the hover.
    Alpine.data('clayDonut', (opts = {}) => {
        // `last`: x-effect can run before the <g x-ref> is registered; init()
        // draws it again once the refs exist.
        let paths = [], last = null;
        return {
            hover: null,
            rows: [],
            get total() { return this.rows.reduce((n, r) => n + r.value, 0); },
            pct(row) { return this.total ? Math.round((row.value / this.total) * 100) : 0; },
            draw(rows) {
                let list = (rows || []).map((r) => ({ label: r.label, value: Number(r.value) || 0, color: r.color })).filter((r) => r.value > 0);
                if (!opts.keep) list = list.sort((a, b) => b.value - a.value).map((r, i) => ({ ...r, color: CLAY_PALETTE[i % CLAY_PALETTE.length] }));
                this.rows = list;
                this.hover = null;
                last = list;
                this.build(list);
            },
            // Reads only `list`, never this.hover or this.rows: draw() runs in an
            // x-effect, and reading the hover here would redraw on every hover.
            build(list) {
                const g = this.$refs.segs;
                if (!g) return;
                g.replaceChildren();
                paths = [];
                const total = list.reduce((n, r) => n + r.value, 0);
                if (!total) return;
                const cx = 100, cy = 100, R = 94, r = 68, TAU = 2 * Math.PI;
                const pt = (rad, a) => `${(cx + rad * Math.cos(a)).toFixed(2)} ${(cy + rad * Math.sin(a)).toFixed(2)}`;
                const add = (d, row, i, mid) => {
                    const el = svgEl('path', { d, fill: row.color, stroke: row.color, 'stroke-width': 5, 'stroke-linejoin': 'round', 'fill-rule': 'evenodd' });
                    el.style.cssText = 'cursor:pointer;transition:transform .3s cubic-bezier(.34,1.56,.64,1),opacity .2s,filter .2s;filter:drop-shadow(0 2px 3px rgba(11,23,48,.16));';
                    el.addEventListener('mouseenter', () => { this.hover = i; });
                    g.appendChild(el);
                    paths.push({ el, mid });
                };
                if (list.length === 1) {
                    // A single slice is the whole ring.
                    add(`M${cx + R} ${cy}A${R} ${R} 0 1 1 ${cx - R} ${cy}A${R} ${R} 0 1 1 ${cx + R} ${cy}Z M${cx + r} ${cy}A${r} ${r} 0 1 0 ${cx - r} ${cy}A${r} ${r} 0 1 0 ${cx + r} ${cy}Z`, list[0], 0, -Math.PI / 2);
                    return;
                }
                const gap = 10 / ((R + r) / 2), minA = (16 * Math.PI) / 180;
                let spans = list.map((s) => (s.value / total) * TAU);
                const small = spans.map((a) => a < minA);
                const nSmall = small.filter(Boolean).length;
                const bigSum = spans.reduce((a, s, i) => a + (small[i] ? 0 : s), 0);
                const k = bigSum ? (TAU - nSmall * minA) / bigSum : 1;
                spans = spans.map((a, i) => (small[i] ? minA : a * k));
                let cur = -Math.PI / 2;
                list.forEach((row, i) => {
                    const a0 = cur + gap / 2, a1 = cur + spans[i] - gap / 2;
                    cur += spans[i];
                    const L = a1 - a0 > Math.PI ? 1 : 0;
                    add(`M${pt(R, a0)}A${R} ${R} 0 ${L} 1 ${pt(R, a1)}L${pt(r, a1)}A${r} ${r} 0 ${L} 0 ${pt(r, a0)}Z`, row, i, (a0 + a1) / 2);
                });
            },
            paint() {
                paths.forEach((p, i) => {
                    const on = this.hover === i, dim = this.hover !== null && !on;
                    p.el.style.transform = on ? `translate(${(Math.cos(p.mid) * 7).toFixed(1)}px,${(Math.sin(p.mid) * 7).toFixed(1)}px)` : 'none';
                    p.el.style.opacity = dim ? '.35' : '1';
                    p.el.style.filter = on ? 'drop-shadow(0 8px 12px rgba(11,23,48,.32))' : 'drop-shadow(0 2px 3px rgba(11,23,48,.16))';
                });
            },
            init() {
                this.$watch('hover', () => this.paint());
                this.$nextTick(() => { if (last && !paths.length) this.build(last); });
            },
        };
    });

    // <x-ui.clay-trend data="{ labels: [...], series: [{ label, values, color, fill }] }">
    // -- smooth curves with horizontal tangents, area fades, dashed grid; a hover
    // column shows a guide, a dot on each line and a glass tooltip.
    let trendSeq = 0;
    // '2026-05' from the API reads 'May' on the axis and 'May 2026' in the tooltip.
    const trendMonth = (m, year) => {
        const k = /^(\d{4})-(\d{2})$/.exec(String(m));
        return k ? new Date(+k[1], +k[2] - 1, 1).toLocaleDateString('en-US', year ? { month: 'short', year: 'numeric' } : { month: 'short' }) : String(m);
    };
    Alpine.data('clayTrend', () => {
        const id = 'trend' + (++trendSeq);
        let guide = null, dots = [], data = null, geo = null, last = null;
        return {
            tip: null,
            // Drawn at the card's own width, so the 11px labels stay 11px in a
            // narrow card (a fixed 600-wide viewBox shrank them to ~5px).
            init() {
                this.$nextTick(() => { if (last && !this.$refs.svg?.childNodes.length) this.draw(last); });
                let width = 0, frame = 0;
                new ResizeObserver(() => {
                    const w = Math.round(this.$refs.svg?.clientWidth || 0);
                    if (!w || w === width) return;
                    width = w;
                    cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(() => { if (last) this.draw(last); });
                }).observe(this.$el);
            },
            draw(input) {
                last = input;
                const svg = this.$refs.svg;
                if (!svg) return;
                data = { labels: [...(input?.labels || [])], series: (input?.series || []).map((s) => ({ ...s, values: [...(s.values || [])].map((v) => Number(v) || 0) })) };
                svg.replaceChildren();
                dots = [];
                this.tip = null;
                const n = data.labels.length;
                if (!n) return;
                const W = Math.max(280, Math.round(svg.clientWidth) || 600), H = 250, left = 40, right = 16, top = 16, bottom = 34;
                svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
                const max = Math.max(1, ...data.series.flatMap((s) => s.values));
                const step = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000].find((s) => s * 4 >= max) || Math.ceil(max / 4);
                const yMax = step * 4;
                const x = (i) => left + (n === 1 ? (W - left - right) / 2 : (i * (W - left - right)) / (n - 1));
                const y = (v) => +(top + (1 - v / yMax) * (H - top - bottom)).toFixed(2);
                geo = { W, x, y, n };
                const line = (vals) => vals.map((v, i) => {
                    if (!i) return `M${x(0)} ${y(v)}`;
                    const x0 = x(i - 1), x1 = x(i), c = (x1 - x0) / 2;
                    return `C${x0 + c} ${y(vals[i - 1])} ${x1 - c} ${y(v)} ${x1} ${y(v)}`;
                }).join('');
                const defs = svgEl('defs');
                data.series.forEach((s, k) => {
                    const lg = svgEl('linearGradient', { id: `${id}-${k}`, x1: 0, y1: 0, x2: 0, y2: 1 });
                    lg.append(svgEl('stop', { offset: '0%', 'stop-color': s.fill || s.color, 'stop-opacity': s.fillOpacity ?? 0.38 }));
                    lg.append(svgEl('stop', { offset: '100%', 'stop-color': s.fill || s.color, 'stop-opacity': 0 }));
                    defs.append(lg);
                });
                svg.append(defs);
                for (let t = 0; t <= 4; t++) {
                    const v = step * t, yy = y(v);
                    svg.append(svgEl('line', { x1: left, x2: W - right, y1: yy, y2: yy, stroke: 'rgba(29,71,214,.12)', 'stroke-dasharray': '4 6' }));
                    const label = svgEl('text', { x: left - 10, y: yy + 4, 'text-anchor': 'end', 'font-size': 11, fill: '#7d89a8' });
                    label.textContent = String(v);
                    svg.append(label);
                }
                data.labels.forEach((m, i) => {
                    const label = svgEl('text', { x: x(i), y: H - 10, 'text-anchor': 'middle', 'font-size': 11, fill: '#7d89a8', 'font-family': 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace' });
                    label.textContent = trendMonth(m);
                    svg.append(label);
                });
                data.series.forEach((s, k) => {
                    svg.append(svgEl('path', { d: line(s.values) + `L${x(n - 1)} ${y(0)}L${x(0)} ${y(0)}Z`, fill: `url(#${id}-${k})` }));
                });
                data.series.forEach((s) => {
                    svg.append(svgEl('path', { d: line(s.values), fill: 'none', stroke: s.color, 'stroke-width': 3.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
                });
                guide = svgEl('line', { y1: top, y2: H - bottom, stroke: 'rgba(11,23,48,.28)', 'stroke-dasharray': '4 4', visibility: 'hidden' });
                svg.append(guide);
                data.series.forEach((s) => {
                    const dot = svgEl('circle', { r: 6.5, fill: s.color, stroke: '#fff', 'stroke-width': 3, visibility: 'hidden' });
                    svg.append(dot);
                    dots.push(dot);
                });
                const colW = n === 1 ? W - left - right : (W - left - right) / (n - 1);
                data.labels.forEach((m, i) => {
                    const hit = svgEl('rect', { x: x(i) - colW / 2, y: 0, width: colW, height: H, fill: 'transparent' });
                    hit.addEventListener('mouseenter', () => this.show(i));
                    svg.append(hit);
                });
            },
            show(i) {
                if (!data || !geo) return;
                const xx = geo.x(i);
                guide.setAttribute('x1', xx); guide.setAttribute('x2', xx); guide.setAttribute('visibility', 'visible');
                data.series.forEach((s, k) => { dots[k].setAttribute('cx', xx); dots[k].setAttribute('cy', geo.y(s.values[i])); dots[k].setAttribute('visibility', 'visible'); });
                this.tip = {
                    label: trendMonth(data.labels[i], true),
                    text: data.series.map((s) => `${s.label} ${s.values[i].toLocaleString()}`).join(' · '),
                    left: Math.min(84, Math.max(16, (xx / geo.W) * 100)).toFixed(1) + '%',
                };
            },
            hide() {
                this.tip = null;
                guide?.setAttribute('visibility', 'hidden');
                dots.forEach((d) => d.setAttribute('visibility', 'hidden'));
            },
        };
    });

    Alpine.data('donutChart', (config) => ({
        chart: null,
        async init() {
            const Chart = await window.loadChart();

            this.chart = new Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: config.labels,
                    datasets: [{
                        data: config.values,
                        backgroundColor: config.colors,
                        borderWidth: 0,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    cutout: '72%',
                    plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } },
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    maintainAspectRatio: false,
                },
            });
        },
    }));

    Alpine.data('lineChart', (config) => ({
        chart: null,
        async init() {
            const Chart = await window.loadChart();

            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: config.labels,
                    datasets: config.datasets.map((set) => ({
                        label: set.label,
                        data: set.data,
                        borderColor: set.color,
                        backgroundColor: set.fill ?? 'transparent',
                        tension: 0.4,
                        fill: !!set.fill,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: set.color,
                        borderWidth: 2.5,
                    })),
                },
                options: {
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v) => config.yPrefix ? config.yPrefix + v : v } },
                    },
                },
            });
        },
    }));

    Alpine.data('barChart', (config) => ({
        chart: null,
        async init() {
            const Chart = await window.loadChart();

            this.chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: (config.datasets || [{ label: config.label, data: config.values, backgroundColor: config.colors || config.color }]).map((ds) => ({
                        label: ds.label,
                        data: ds.data,
                        backgroundColor: ds.color || ds.backgroundColor,
                        borderRadius: 6,
                        borderSkipped: false,
                    })),
                },
                options: {
                    indexAxis: config.horizontal ? 'y' : 'x',
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: !!config.showLegend },
                        tooltip: { padding: 10, cornerRadius: 8 },
                    },
                    scales: {
                        x: { grid: { display: config.horizontal ? false : true, color: '#f1f5f9' }, border: { display: false } },
                        y: { grid: { display: config.horizontal ? true : false, color: '#f1f5f9' }, border: { display: false } },
                    },
                },
            });
        },
    }));

    Alpine.data('passwordField', () => ({
        visible: false,
        toggle() {
            this.visible = !this.visible;
            window.renderIcons?.();
        },
    }));

    // Progressive-enhancement haptics: fires only for meaningful, user-triggered
    // confirmations (never on load, scroll, hover, typing, or negative/rejection
    // states). Feature-detected, reduced-motion-gated, and never throws — every
    // call site should also carry a visible/textual signal, never rely on this
    // alone (iOS Safari has no navigator.vibrate at all).
    Alpine.store('haptics', {
        enabled: true,
        canVibrate() {
            return typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function';
        },
        prefersReducedMotion() {
            return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },
        fire(pattern) {
            if (!this.enabled || !this.canVibrate() || this.prefersReducedMotion()) return;
            try { navigator.vibrate(pattern); } catch { /* never throw */ }
        },
        selection() { this.fire([8]); },
        press() { this.fire([6]); },
        success() { this.fire([12]); },
        warning() { this.fire([20]); },
        actionComplete() { this.fire([8, 30, 8]); },
        uploadComplete() { this.fire([10, 20, 10]); },
        submitted() { this.fire([12, 30, 12]); },
    });

    // Toasts are a status channel, not an alert — auto-dismiss timing gives
    // the user enough time to actually read before it disappears, and errors
    // never self-dismiss since the user may still need to act on them.
    // Never more than 2 on screen at once (oldest drops first).
    Alpine.data('toast', () => ({
        items: [],
        push(message, variant = 'success') {
            // One failure can be reported twice (the client's api:forbidden
            // toast and the page's own catch). The same words twice read as
            // two problems, so a toast already on screen is not repeated.
            if (this.items.some((item) => item.message === message && item.variant === variant)) return;
            const id = Date.now() + Math.random();
            while (this.items.length >= 2) this.dismiss(this.items[0].id);
            this.items.push({ id, message, variant });
            this.$nextTick(() => window.renderIcons?.());

            if (variant === 'success') Alpine.store('haptics').success();
            if (variant === 'warning') Alpine.store('haptics').warning();

            if (variant === 'error') return; // stays until the user dismisses it
            const dismissAfter = variant === 'warning' ? 8000 : 5000;
            setTimeout(() => this.dismiss(id), dismissAfter);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    }));
});

// Lucide swaps each <i data-lucide> for a brand new <svg> node. Any Alpine
// directive on that icon (x-show, :class) only binds if Alpine walks the
// DOM *after* the swap — so icons must render before Alpine.start().
function renderIcons() {
    createIcons({ icons });
}

window.renderIcons = renderIcons;

renderIcons();

// Modals use x-teleport to escape transformed ancestors (see modal.blade.php).
// Teleported content lives inside an inert <template> until Alpine relocates
// it during its initial walk, so icons inside modals aren't in the live DOM
// yet when renderIcons() runs above — catch them once Alpine finishes init.
document.addEventListener('alpine:initialized', () => renderIcons());

// Rows and cards Alpine draws later (a list that loads from the API, a tab
// opened later) bring new <i data-lucide> placeholders, which stayed blank
// unless that page remembered to call renderIcons(): on staging the personnel
// list's Edit and Archive buttons were empty and invisible (QA run
// 2026-10-05). One watcher draws them on the next frame. Lucide's <svg>
// replacements do not match, so it never feeds itself.
let iconFrame = null;
new MutationObserver((mutations) => {
    if (iconFrame) return;
    const placeholders = mutations.some((m) => [...m.addedNodes].some((n) => n.nodeType === 1 && (n.matches('i[data-lucide]') || n.querySelector('i[data-lucide]'))));
    if (placeholders) iconFrame = requestAnimationFrame(() => { iconFrame = null; renderIcons(); });
}).observe(document.documentElement, { childList: true, subtree: true });

Alpine.start();


// -- API failure handling, in one place -------------------------------------
// The client rethrows every failure as an ApiError and emits these two events
// for the cases no individual page should be deciding on its own.

function toast(message, variant = 'error') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

window.addEventListener('api:unauthorized', (event) => {
    const lang = window.Alpine?.store('lang')?.current || 'fil';
    api.setToken(null);

    // Clear locally rather than calling logout() -- the token this event is
    // reporting on is already rejected, so a second round trip to revoke it
    // would just 401 again. clear() is the sync, no-network half of logout().
    window.Alpine?.store('session')?.clear?.();
    window.Alpine?.store('citizenSession')?.clear?.();

    toast(event.detail.error.localised(lang));

    // Send them to the right door rather than a generic one.
    const target = window.location.pathname.startsWith('/admin') ? '/admin/login' : '/citizen/login';
    setTimeout(() => { window.location.href = target; }, 1200);
});

window.addEventListener('api:forbidden', (event) => {
    const lang = window.Alpine?.store('lang')?.current || 'fil';
    // A password an administrator set: not a missing permission. My Profile
    // opens and asks for the officer's own (PR-8).
    if (event.detail.error.code === 'PASSWORD_CHANGE_REQUIRED') {
        window.dispatchEvent(new CustomEvent('password-change-required'));
        return;
    }
    // The server appends the permission it checked, '(um_audit.view)', for
    // the log; people saw it in red toasts (live test 2026-10-07, NAV-3).
    toast(String(event.detail.error.localised(lang)).replace(/\s*\([a-z_]+\.[a-z_]+\)\s*$/, ''));
});
