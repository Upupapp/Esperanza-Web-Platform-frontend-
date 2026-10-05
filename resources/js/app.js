import './bootstrap';
import api from './api/client';
import { resource } from './api/resource';
import { specForm } from './api/form';
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

// The status history's trigger codes, in words. "admin:action" says
// nothing the officer's name beside it does not already say.
const TRIGGER_NOTES = {
    'auto:first-open': 'automatically, when the request was first opened',
    'auto:requirement-flagged': 'automatically, when a document was flagged',
    'citizen:resubmit': 'resubmitted by the resident',
};

window.historyNote = (trigger) => TRIGGER_NOTES[trigger] || '';

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

    Alpine.store('lang', {
        current: localStorage.getItem('esperanza_lang') || 'fil',
        set(lang) {
            this.current = lang;
            localStorage.setItem('esperanza_lang', lang);
        },
    });
    // Until the resident picks a language, follow Settings, General, Default
    // Language (it was saved and never read). Not stored, so a later change
    // by the LGU still applies.
    if (!localStorage.getItem('esperanza_lang')) {
        api.get('/portal-settings')
            .then((res) => {
                const lang = res?.data?.default_language;
                if ((lang === 'en' || lang === 'fil') && !localStorage.getItem('esperanza_lang')) Alpine.store('lang').current = lang;
            })
            .catch(() => {});
    }

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
            try {
                const result = await api.get('/auth/me');
                this.setAccount(result.data);
            } finally {
                this.booting = false;
            }
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
            return (this.account.moduleKeys || []).includes(moduleKey);
        },
        canPerm(submoduleKey, permission) {
            if (!this.account) return false;
            const perms = (this.account.permissions || {})[submoduleKey];
            if (!perms) return false;
            return Array.isArray(perms) ? perms.includes(permission) : perms === '*';
        },
        inScope(barangay) {
            if (!this.account) return false;
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
    const notificationStyle = {
        dokyu: ['file-text', 'brand'], tulong: ['hand-heart', 'purple'],
        account: ['user-round', 'indigo'], community: ['megaphone', 'teal'],
        events: ['calendar', 'amber'], sakuna: ['shield-alert', 'rose'],
    };

    function makeNotificationStore(path) {
        return {
            items: [],
            state: 'idle',
            error: null,
            dismissed: [],

            get loading() { return this.state === 'loading'; },
            get failed() { return this.state === 'error'; },
            get visible() { return this.items.filter((n) => !this.dismissed.includes(n.id)); },
            get unreadCount() { return this.visible.filter((n) => n.unread).length; },

            async load() {
                this.state = 'loading';
                this.error = null;
                try {
                    const result = await api.get(path);
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
                try {
                    await api.post(`${path}/${id}/read`);
                } catch {
                    n.unread = true; // the server did not accept it — undo the optimistic flip
                }
            },

            async markAllRead() {
                await Promise.all(this.visible.filter((n) => n.unread).map((n) => this.markRead(n.id)));
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
    toast(event.detail.error.localised(lang));
});
