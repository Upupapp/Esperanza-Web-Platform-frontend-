import api from './api/client';

// The Dokyu and Tulong on the resident's record (GET /citizen/records, owner
// 2026-10-13): what the offices recorded for them - imported from an office's
// own files, or encoded at the counter - and what they filed here. My Profile
// shows them all ("Dokyu & Tulong Received", the staff profile's card); the
// Dokyu and Tulong pages show that type's office records under "Past records
// from the office". Read only: an office's record has no actions, and while
// the barangay has not yet confirmed the record is theirs nothing of it shows;
// nor do the offices' records before the account is verified (2026-11-19:
// `pending` then says some wait, and what they filed themselves still shows).
export function citizenRecords(type = null, pages = {}) {
    return {
        type,
        pages,
        rows: [],
        loading: true,
        failed: false,
        pending: false,

        init() { this.load(); },

        get en() { return this.$store.lang.current === 'en'; },
        t(en, fil) { return this.en ? en : fil; },

        async load() {
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.get('/citizen/records');
                this.rows = res.data || [];
                this.pending = !!res.meta?.pending;
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        // A Dokyu or Tulong page lists its own requests above; this adds the
        // office's records of that type. My Profile lists everything.
        get list() {
            return this.type ? this.rows.filter((q) => q.type === this.type && q.from_office) : this.rows;
        },
        get total() {
            return this.rows.filter((q) => q.type === 'tulong' && q.status === 'Completed' && q.amount).reduce((n, q) => n + Number(q.amount), 0);
        },

        peso(n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 }); },
        day(q) {
            return q.date ? new Date(q.date + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : null;
        },
        line(q) {
            return [q.type === 'tulong' ? 'Tulong' : 'Dokyu', this.day(q), q.amount ? this.peso(q.amount) : null, q.ref].filter(Boolean).join(' · ');
        },
        source(q) {
            if (!q.from_office) return this.t('Filed here on the portal', 'In-file mo rito sa portal');
            return q.office ? this.t('Recorded by ' + q.office, 'Naitala ng ' + q.office) : this.t('Recorded by the office', 'Naitala ng office');
        },
        // A request filed here opens on its page; an office's record is all here.
        link(q) {
            return q.from_office || !this.pages[q.type] ? null : this.pages[q.type] + '?ref=' + encodeURIComponent(q.ref);
        },
        tone(status) {
            if (['Completed', 'Released'].includes(status)) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            if (['Rejected', 'Cancelled'].includes(status)) return 'bg-rose-50 text-rose-700 ring-rose-200';
            return 'bg-amber-50 text-amber-700 ring-amber-200';
        },
    };
}
