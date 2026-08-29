import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { Chart, ArcElement, DoughnutController, LineElement, LineController, PointElement, BarElement, BarController, LinearScale, CategoryScale, Filler, Tooltip, Legend } from 'chart.js';
import html2pdf from 'html2pdf.js';
import * as DokyuEditor from './dokyu-editor';

Chart.register(ArcElement, DoughnutController, LineElement, LineController, PointElement, BarElement, BarController, LinearScale, CategoryScale, Filler, Tooltip, Legend);
Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#64748b';

window.Alpine = Alpine;
window.Chart = Chart;
// Exposed globally so any page's Alpine markup can call html2pdf() directly
// (e.g. the citizen Dokyu "Download PDF" button) without a per-page import.
window.html2pdf = html2pdf;
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

document.addEventListener('alpine:init', () => {
    Alpine.store('lang', {
        current: localStorage.getItem('esperanza_lang') || 'fil',
        set(lang) {
            this.current = lang;
            localStorage.setItem('esperanza_lang', lang);
        },
    });

    // Frontend-only "logged in as this role" simulation for the Administrative
    // Portal (see config/esperanza_rbac.php). No real auth — the account object
    // just persists to localStorage so nav/pages can gate content by module
    // access and Municipal/Barangay scope.
    //
    // No session -> fail CLOSED (TAB 08). This used to fail open so the design
    // could be previewed without logging in. That is wrong the moment a real API
    // exists: it trains every screen to treat "no account" as "allowed", and the
    // preview is indistinguishable from a genuine unrestricted session.
    //
    // What this gating is for: hiding what the user cannot use. It is NOT the
    // security boundary. The server enforces every permission triple, and a
    // check here must never be presented as protection.
    Alpine.store('session', {
        // Re-derive moduleKeys/permissions from the current RBAC config on every
        // load instead of trusting whatever was frozen into localStorage at
        // login time — otherwise a newly added module (e.g. Papeles) stays
        // invisible to already-logged-in sessions until they manually log out
        // and back in.
        account: (() => {
            const stored = JSON.parse(localStorage.getItem('esperanza_session') || 'null');
            if (stored && window.__ESPERANZA_RBAC_ACCOUNTS__) {
                const fresh = window.__ESPERANZA_RBAC_ACCOUNTS__.find((a) => a.id === stored.id);
                if (fresh) {
                    stored.moduleKeys = fresh.moduleKeys;
                    stored.permissions = fresh.permissions;
                }
            }
            return stored;
        })(),
        login(account) {
            this.account = account;
            localStorage.setItem('esperanza_session', JSON.stringify(account));
        },
        logout() {
            this.account = null;
            localStorage.removeItem('esperanza_session');
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

    // Frontend-only "logged in as this resident" simulation for the Citizen
    // Portal (see config/esperanza_citizens.php). Mirrors $store.session above
    // but simpler — citizens don't have module permissions, just a barangay
    // identity that Dokyu (and later other pages) can read to show the right
    // barangay-specific content.
    Alpine.store('citizenSession', {
        account: JSON.parse(localStorage.getItem('esperanza_citizen_session') || 'null'),
        login(account) {
            this.account = account;
            localStorage.setItem('esperanza_citizen_session', JSON.stringify(account));
        },
        logout() {
            this.account = null;
            localStorage.removeItem('esperanza_citizen_session');
        },
        initials() {
            if (!this.account) return 'CI';
            return ((this.account.firstName?.[0] || '') + (this.account.lastName?.[0] || '')).toUpperCase();
        },
    });

    // Citizen-facing notifications, persisted to localStorage so a citizen
    // still sees them after navigating away or reloading (same persistence
    // approach as $store.citizenSession above — this project has no real
    // backend, per CLAUDE.md). Dokyu/Tulong submission pushes from
    // citizen/document-requests.blade.php and citizen/assistance-requests.blade.php;
    // admin status-change actions (reject, needs more docs, approve, ready
    // for release, release) push from the matching admin process modals so
    // the citizen sees each processing step. citizen/notifications.blade.php
    // reads and displays this list above its static seed notifications.
    Alpine.store('citizenNotifications', {
        items: JSON.parse(localStorage.getItem('esperanza_notifications') || '[]'),
        persist() {
            localStorage.setItem('esperanza_notifications', JSON.stringify(this.items));
        },
        push({ icon, color, title, body, ref, portal }) {
            this.items.unshift({
                id: Date.now() + Math.random(),
                icon, color, title, body, ref, portal,
                createdAt: new Date().toISOString(),
                unread: true,
            });
            this.items = this.items.slice(0, 50);
            this.persist();
        },
        // Backfills one notification per existing request reflecting its
        // CURRENT status — without this, a citizen only ever sees a
        // notification for something that changed status live in their own
        // browser session; the 50+ pre-seeded historical Dokyu/Tulong mock
        // requests would otherwise never appear here at all. Idempotent
        // (keyed by ref, called from citizen/document-requests.blade.php
        // and citizen/assistance-requests.blade.php's own init()) — safe to
        // call on every page load, never creates duplicates. Backfilled
        // items start read (unread: false) since they aren't a new event.
        seedFromRequests(requests, portal) {
            const existingRefs = new Set(this.items.map((n) => n.ref));
            const statusCopy = {
                'Submitted': ['file-plus', 'brand', (t) => 'Your ' + t + ' request has been submitted and is now Pending Review.'],
                'Pending Review': ['clock', 'amber', (t) => 'Your ' + t + ' request is pending review.'],
                'Under Verification': ['search-check', 'indigo', (t) => 'Your ' + t + ' request is now being verified.'],
                'Under Review': ['file-plus-2', 'amber', (t) => 'Your ' + t + ' request needs additional documents.'],
                'Assigned': ['send', 'indigo', (t) => 'Your ' + t + ' request has been assigned for processing.'],
                'Processing': ['loader', 'indigo', (t) => 'Your ' + t + ' request is being processed.'],
                'Approved': ['package-check', 'emerald', (t) => 'Your ' + t + ' request has been approved.'],
                'Mark to Release': ['circle-check', 'teal', (t) => 'Your ' + t + ' request is ready for release!'],
                'Released': ['check-check', 'cyan', (t) => 'Your ' + t + ' request has been released.'],
                'Completed': ['circle-check', 'emerald', (t) => 'Your ' + t + ' request is complete.'],
                'Rejected': ['circle-x', 'rose', (t) => 'Your ' + t + ' request was rejected.'],
                'Cancelled': ['circle-x', 'rose', (t) => 'Your ' + t + ' request was cancelled.'],
            };
            const toAdd = [];
            requests.forEach((r) => {
                if (!r.ref || existingRefs.has(r.ref)) return;
                const copy = statusCopy[r.status];
                if (!copy) return;
                const [icon, color, bodyFn] = copy;
                toAdd.push({
                    id: r.ref, icon, color, portal, ref: r.ref,
                    title: r.status + ': ' + r.type,
                    body: bodyFn(r.type) + (r.remarks ? ' ' + r.remarks : ''),
                    createdAt: new Date().toISOString(),
                    unread: false,
                });
            });
            if (!toAdd.length) return;
            this.items = [...this.items, ...toAdd];
            this.persist();
        },
        markRead(id) {
            const n = this.items.find((i) => i.id === id);
            if (!n) return;
            n.unread = false;
            this.persist();
        },
        markAllRead() {
            this.items.forEach((i) => (i.unread = false));
            this.persist();
        },
        remove(id) {
            this.items = this.items.filter((i) => i.id !== id);
            this.persist();
        },
        get unreadCount() {
            return this.items.filter((i) => i.unread).length;
        },
    });

    // Admin-facing counterpart to citizenNotifications above — same shape,
    // same localStorage persistence, but for events an LGU staff member
    // needs to know about (a citizen submitted something, a citizen
    // resubmitted a corrected document) rather than events a citizen needs
    // to know about. Since this project has no real backend, this only
    // actually notifies the admin when viewed in the same browser as the
    // citizen action that triggered it (shared localStorage) — see
    // components/admin/topbar.blade.php for where this renders, and
    // citizen/document-requests.blade.php's submitRequest() /
    // admin/document-requests.blade.php's markRequirementResubmitted() for
    // where it's pushed to.
    Alpine.store('adminNotifications', {
        items: JSON.parse(localStorage.getItem('esperanza_admin_notifications') || '[]'),
        persist() {
            localStorage.setItem('esperanza_admin_notifications', JSON.stringify(this.items));
        },
        push({ icon, color, title, body, ref, portal }) {
            this.items.unshift({
                id: Date.now() + Math.random(),
                icon, color, title, body, ref, portal,
                createdAt: new Date().toISOString(),
                unread: true,
            });
            this.items = this.items.slice(0, 50);
            this.persist();
        },
        // One-time seed so the flagship demo scenario (Cristy's Barangay
        // Clearance) has a real notification to click through on a fresh
        // browser, without needing to actually submit it live first.
        // Idempotent — keyed by ref, safe to call on every page load.
        seedDefaults() {
            if (this.items.some((n) => n.ref === 'DR-2026-2255')) return;
            this.items.push({
                id: 'seed-DR-2026-2255',
                icon: 'file-text', color: 'brand', portal: 'dokyu', ref: 'DR-2026-2255',
                title: 'Cristy Submitted Barangay Clearance',
                body: 'Cristy Pareja Bonghanoy submitted a Barangay Clearance request (DR-2026-2255).',
                createdAt: new Date().toISOString(),
                unread: true,
            });
            this.persist();
        },
        markRead(id) {
            const n = this.items.find((i) => i.id === id);
            if (!n) return;
            n.unread = false;
            this.persist();
        },
        markAllRead() {
            this.items.forEach((i) => (i.unread = false));
            this.persist();
        },
        get unreadCount() {
            return this.items.filter((i) => i.unread).length;
        },
    });

    // Per-barangay directory (see components/layouts/citizen.blade.php, which
    // injects window.__barangayCatalog from config/esperanza_barangay_documents.php).
    // `.mine` lets any Citizen Portal page show the signed-in resident's own
    // barangay seal/officials/contact without re-declaring the catalog per view.
    Alpine.store('barangay', {
        catalog: window.__barangayCatalog || {},
        get mine() {
            const b = Alpine.store('citizenSession').account?.barangay;
            return b ? this.catalog[b] || null : null;
        },
    });

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
        init() {
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
        init() {
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
        init() {
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

    Alpine.data('authForm', (redirectUrl = null) => ({
        loading: false,
        done: false,
        submit() {
            if (this.loading) return;
            this.loading = true;
            setTimeout(() => {
                this.loading = false;
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    this.done = true;
                    this.$dispatch('toast', { message: 'This portal is a frontend preview — sign-in is mocked for now.', variant: 'info' });
                    setTimeout(() => (this.done = false), 2600);
                }
            }, 900);
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
