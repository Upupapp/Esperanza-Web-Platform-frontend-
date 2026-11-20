// Staff access (owner decision, 2026-11-20): the Department Head's switches
// for what the office's Department Staff do on its Dokyu and Tulong requests
// (screen, assess, release, import). Final approval and reject are never a
// switch: the server refuses them (NOT_A_STAFF_STEP). The server also keeps a
// Head to their own office and refuses a step their own role does not hold
// (STEP_NOT_AVAILABLE); the Super Administrator picks the office.
//
// Loaded only on /admin/document-requests/staff-access and
// /admin/assistance-requests/staff-access (the area loader in app.js).

import api from './api/client';

const TZ = 'Asia/Manila';

function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}
const errText = (err, fallback) => (err && typeof err.localised === 'function' ? err.localised('en') : fallback);

export function staffAccess() {
    return {
        data: { office: '', short: '', steps: [], head_only: [], staff: [], offices: null },
        office: '',
        loading: false,
        loaded: false,
        failed: false,
        denied: false,
        saving: null,

        async start() {
            await Promise.resolve(window.Alpine?.store('session')?.whenReady?.()).catch(() => null);
            await this.load();
        },

        async load() {
            this.loading = true; this.failed = false;
            try {
                const res = await api.get('/admin/staff-access', this.office ? { params: { office: this.office } } : undefined);
                this.apply(res.data);
                this.loaded = true;
            } catch (err) {
                if (err?.status === 403) this.denied = true; else this.failed = true;
            } finally { this.loading = false; }
        },

        apply(d) {
            this.data = { ...this.data, ...(d || {}) };
            this.office = this.data.office || '';
        },

        async toggle(step) {
            if (this.saving) return;
            const enabled = !step.enabled;
            this.saving = step.step;
            try {
                const res = await api.put('/admin/staff-access', { office: this.office || null, step: step.step, enabled });
                this.apply(res.data);
                toast(step.label + (enabled ? ' is on: the Staff can do it now.' : ' is off: it stays with the Head.'));
            } catch (err) {
                toast(errText(err, 'Could not save the change.'), 'error');
            } finally { this.saving = null; }
        },

        when(value) {
            const d = new Date(String(value).replace(' ', 'T') + (/[zZ]|[+-]\d\d:?\d\d$/.test(String(value)) ? '' : 'Z'));
            return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('en-US', { timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        },
    };
}

export function register(Alpine) {
    Alpine.data('staffAccess', staffAccess);
}
