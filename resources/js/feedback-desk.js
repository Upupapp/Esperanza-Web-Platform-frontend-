// The staff Feedback desk: residents' tickets within the officer's reach
// (FeedbackDesk::visibleTo on the server), the conversation, replies with a
// status, routing to an office and assigning to an officer, and how the help
// content was rated. A tab of Settings since 2026-11-18 (owner: "this is not
// really in the balita part in the citizen side, and some account types dont
// have balita and they need to access the feedback screen"); it was Balita's
// Feedback tab, in scope-admin-records.js, from the scope round of 2026-10-15.
//
// Loaded only under /admin/settings (the area loader at the end of app.js),
// so neither the shared bundle nor Balita's chunk carries it. register() runs
// before Alpine starts. The component sits inside the Settings page's own
// component and reads its `tab`, and sets its `feedbackOpen` (the tab's count).

import api from './api/client';

// The ticket statuses, in x-ui.badge's colours.
const TONES = {
    Submitted: ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
    'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
    Completed: ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
    Cancelled: ['bg-slate-50 text-slate-500 ring-slate-200', 'bg-slate-400'],
};
const tone = (status) => (TONES[status] || ['bg-white text-slate-500 ring-slate-300', 'bg-slate-300'])[0];
const dot = (status) => (TONES[status] || ['', 'bg-slate-300'])[1];

/** 'Oct 15, 2026, 9:30 AM' in Manila time; '' when there is no date. */
function when(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString('en-US', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

/** The error's own words, the field's for a 422 (staff site: English). */
function errText(err, fallback) {
    if (err?.explain) return err.explain('en');
    return err?.localised ? err.localised('en') : fallback;
}

// On window: a toast raised after an await may come from a button its own
// action removed, and that dispatch would never arrive.
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

// A 401 or 403 already raised its own toast (app.js), so it is not doubled.
function fail(err, fallback) {
    if (err?.status === 401 || err?.status === 403) return;
    toast(errText(err, fallback), 'error');
}

const FB_CHIPS = [
    { key: 'open', label: 'Open', params: { open: 1 } },
    { key: 'new', label: 'Submitted', params: { status: 'Submitted' } },
    { key: 'review', label: 'Under Review', params: { status: 'Under Review' } },
    { key: 'resolved', label: 'Completed', params: { status: 'Completed' } },
    { key: 'closed', label: 'Cancelled', params: { status: 'Cancelled' } },
    { key: 'all', label: 'All', params: {} },
];

export function register(Alpine) {
    Alpine.data('feedbackDesk', () => ({
        chips: FB_CHIPS,
        status: 'open',
        category: '',
        office: '',
        search: '',
        mine: false,
        rows: [],
        counts: { open: 0, new: 0, resolved: 0, closed: 0 },
        categories: ['Account & Login', 'Dokyu', 'Tulong', 'Technical Issue', 'App Problem', 'Other'],
        loading: false,
        failed: false,
        loaded: false,
        started: false,
        page: 1,
        lastPage: 1,
        total: 0,
        more: false,
        seq: 0,
        timer: null,
        sel: null,
        selRef: null,
        selLoading: false,
        selFailed: false,
        reply: '',
        replyStatus: 'Completed',
        sending: false,
        replyError: '',
        routing: false,
        routeOffice: '',
        routeTo: '',
        assignees: [],
        assigneesLoading: false,
        assigning: false,
        routeError: '',
        offices: [],
        officesLoading: null,
        help: [],
        helpLoading: false,
        helpFailed: false,
        helpLoaded: false,
        helpAll: false,

        get may() { return this.$store.session.canPerm('comm_feedback', 'view'); },
        get mayReply() { return this.$store.session.canPerm('comm_feedback', 'edit'); },
        get mayAssign() { return this.$store.session.canPerm('comm_feedback', 'assign'); },

        async init() {
            this.$watch('search', () => {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.reload(), 350);
            });
            this.$watch('category', () => this.reload());
            this.$watch('office', () => this.reload());
            this.$watch('mine', () => this.reload());
            this.$watch('tab', (t) => { if (t === 'feedback' && this.may) this.start(); });
            await this.$store.session.whenReady();
            if (!this.may) return;
            if (this.tab === 'feedback') this.start();
            else this.loadCounts();
            // Live updates (2026-11-15): tickets count as other changes on the
            // server, ratings as Balita. The queue reloads quietly once open
            // (the tab's count otherwise), not while a reply or a routing is
            // being sent, nor while a new filter's queue is coming: a quiet
            // reload then would replace that load and merge the new filter's
            // tickets with the old one's (sweep 2026-11-18).
            this.$store.live.on(null, ['other', 'balita'], () => (this.started ? this.load(true) : this.loadCounts()), { busy: () => this.sending || this.assigning || this.routing || this.loading });
        },

        // The first time the tab is shown: the queue, the offices, the help
        // ratings, and a ticket named in the address (?ref=, from the
        // notification an assignee gets).
        start() {
            if (this.started) return;
            this.started = true;
            this.load();
            this.loadOffices();
            this.loadHelp();
            const ref = new URLSearchParams(window.location.search).get('ref');
            if (ref) this.openTicket(ref);
        },
        reload() { if (this.started) this.load(); },

        when,
        tone,
        dot,
        chipCount(key) {
            const c = this.counts;
            return { open: c.open, new: c.new, review: Math.max(0, c.open - c.new), resolved: c.resolved, closed: c.closed }[key];
        },
        setStatus(key) {
            if (this.status === key) return;
            this.status = key;
            this.reload();
        },
        applyMeta(meta) {
            if (meta?.counts) {
                this.counts = { open: 0, new: 0, resolved: 0, closed: 0, ...meta.counts };
                // The page's tab button shows how many are open.
                this.feedbackOpen = this.counts.open;
            }
            if (Array.isArray(meta?.categories) && meta.categories.length) this.categories = meta.categories;
        },

        async loadCounts() {
            try {
                const res = await api.get('/admin/support/tickets', { params: { open: 1, per_page: 1 } });
                this.applyMeta(res.meta);
            } catch {
                // The tab then shows no count.
            }
        },

        params(page) {
            const chip = this.chips.find((c) => c.key === this.status) || this.chips[0];
            const p = { ...chip.params, page, per_page: 25 };
            if (this.category) p.category = this.category;
            if (this.office) p.office = this.office;
            if (this.mine) p.mine = 1;
            const q = this.search.trim();
            if (q) p.q = q;
            return p;
        },

        // quiet (a live update): the queue on screen stays until the new first
        // page comes, and the tickets Load more added stay below it.
        async load(quiet = false) {
            const seq = ++this.seq;
            if (!quiet) {
                this.loading = true;
                this.failed = false;
                // The pages Load more added were the old filter's.
                this.page = 1;
            }
            try {
                const res = await api.get('/admin/support/tickets', { params: this.params(1) });
                if (seq !== this.seq) return;
                const fresh = res.data || [];
                if (quiet && this.page > 1) {
                    const refs = new Set(fresh.map((t) => t.ref));
                    this.rows = [...fresh, ...this.rows.filter((t) => !refs.has(t.ref))];
                } else {
                    this.rows = fresh;
                    this.page = 1;
                }
                this.lastPage = Math.max(this.page, res.meta?.last_page || 1);
                this.total = res.meta?.total ?? this.rows.length;
                this.applyMeta(res.meta);
                this.loaded = true;
                this.failed = false;
            } catch {
                if (seq === this.seq && !quiet) this.failed = true;
            } finally {
                if (seq === this.seq) this.loading = false;
            }
        },

        async loadMore() {
            if (this.more || this.page >= this.lastPage) return;
            const seq = this.seq;
            this.more = true;
            try {
                const res = await api.get('/admin/support/tickets', { params: this.params(this.page + 1) });
                if (seq !== this.seq) return;
                const seen = new Set(this.rows.map((t) => t.ref));
                this.rows = [...this.rows, ...(res.data || []).filter((t) => !seen.has(t.ref))];
                this.page += 1;
                this.lastPage = res.meta?.last_page || this.lastPage;
            } catch (err) {
                fail(err, 'Could not load more tickets.');
            } finally {
                this.more = false;
            }
        },

        // Offices to route to: every office that runs a service (the public
        // catalog, which any officer can read), and the barangay halls.
        loadOffices() {
            if (this.officesLoading) return this.officesLoading;
            this.officesLoading = api.get('/services').then((res) => {
                const seen = new Map();
                (res.data || []).forEach((s) => { if (s.office && !seen.has(s.office)) seen.set(s.office, s.office_short || null); });
                if (!seen.has('Barangay Office')) seen.set('Barangay Office', null);
                this.offices = [...seen].map(([name, short]) => ({ name, short })).sort((a, b) => a.name.localeCompare(b.name));
            }).catch(() => { this.officesLoading = null; });
            return this.officesLoading;
        },
        officeName(o) {
            if (o.name === 'Barangay Office') return 'Barangay hall of the resident';
            return o.short && o.short !== o.name ? o.name + ' (' + o.short + ')' : o.name;
        },
        officeLabel(t) {
            if (!t?.office) return 'Not routed yet';
            if (t.office === 'Barangay Office') return t.barangay ? 'Brgy. ' + t.barangay + ' hall' : 'Barangay hall';
            return t.office_short || t.office;
        },
        requestLink(ref) {
            return (/^TUL-/i.test(String(ref)) ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(ref);
        },
        size(bytes) {
            const n = Number(bytes) || 0;
            return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB';
        },

        async openTicket(ref) {
            if (!ref) return;
            this.selRef = ref;
            const row = this.rows.find((t) => t.ref === ref);
            // What the list knows shows at once; the conversation follows.
            this.sel = row ? { ...row } : null;
            this.selLoading = true;
            this.selFailed = false;
            this.reply = '';
            this.replyError = '';
            this.routing = false;
            this.routeError = '';
            this.replyStatus = row?.status === 'Cancelled' ? 'Under Review' : 'Completed';
            // One column below the xl width (the ticket under the queue): bring it into view.
            if (window.matchMedia?.('(max-width: 1279px)').matches) this.$nextTick(() => this.$refs.ticket?.scrollIntoView({ block: 'start', behavior: 'smooth' }));
            try {
                const res = await api.get('/admin/support/tickets/' + encodeURIComponent(ref));
                if (this.selRef !== ref) return;
                this.sel = res.data;
                this.replyStatus = res.data?.status === 'Cancelled' ? 'Under Review' : 'Completed';
            } catch (err) {
                if (this.selRef !== ref) return;
                this.selFailed = true;
                if (!this.sel) this.sel = { ref, subject: err?.status === 404 ? 'Ticket not found' : 'Could not open this ticket', notFound: err?.status === 404 };
            } finally {
                if (this.selRef === ref) this.selLoading = false;
            }
        },
        closeTicket() {
            this.sel = null;
            this.selRef = null;
            this.routing = false;
        },

        // The resident's first message is the ticket itself; replies follow in
        // order. A reply sent before conversations existed (2026-10-15) is
        // kept only as `response`, and is shown last.
        get thread() {
            const t = this.sel;
            if (!t || t.notFound) return [];
            const list = [{ from: 'citizen', name: t.citizen?.name || 'Resident', body: t.body, at: t.created_at }];
            const replies = Array.isArray(t.messages) ? t.messages : [];
            list.push(...replies);
            if (Array.isArray(t.messages) && t.response && !replies.some((m) => m.from === 'staff')) {
                list.push({ from: 'staff', name: 'LGU staff', body: t.response, at: t.responded_at });
            }
            return list.filter((m) => m.body);
        },

        get replyOptions() {
            if (this.sel?.status === 'Cancelled') return [['Under Review', 'Under Review: reopen the ticket']];
            return [
                ['Completed', 'Completed: resolved'],
                ['Under Review', 'Under Review: still working on it'],
                ['Cancelled', 'Cancelled: closed without action'],
            ];
        },

        patchRow(t) {
            if (!t?.ref) return;
            const { messages, ...row } = t;
            this.rows = this.rows.map((x) => (x.ref === row.ref ? { ...x, ...row } : x));
        },

        async sendReply() {
            const t = this.sel;
            const message = this.reply.trim();
            if (!t?.ref || this.sending || !message) return;
            this.sending = true;
            this.replyError = '';
            try {
                const res = await api.post('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/reply', { message, status: this.replyStatus || null });
                if (this.selRef === t.ref) {
                    this.sel = res.data;
                    this.reply = '';
                    this.replyStatus = res.data?.status === 'Cancelled' ? 'Under Review' : 'Completed';
                }
                this.patchRow(res.data);
                toast('Reply sent. The resident is notified.');
                this.loadCounts();
            } catch (err) {
                // The reply stays in the box, to send again.
                if (err?.code === 'TICKET_CLOSED') {
                    this.replyStatus = 'Under Review';
                    this.replyError = 'This ticket is closed. Send the reply with Under Review to reopen it.';
                } else {
                    this.replyError = errText(err, 'The reply could not be sent.');
                }
            } finally {
                this.sending = false;
            }
        },

        async startRouting() {
            const t = this.sel;
            if (!t?.ref) return;
            this.routing = true;
            this.routeError = '';
            this.routeOffice = t.office || '';
            this.routeTo = t.assigned_to?.employee_id || '';
            await this.loadOffices();
            if (t.office && !this.offices.some((o) => o.name === t.office)) this.offices = [...this.offices, { name: t.office, short: t.office_short || null }];
            await this.loadAssignees();
        },

        // Officers who may take this ticket for the chosen office.
        async loadAssignees() {
            const t = this.sel;
            if (!t?.ref) return;
            const office = this.routeOffice;
            this.assigneesLoading = true;
            this.assignees = [];
            try {
                const res = await api.get('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/assignees', { params: office ? { office } : {} });
                if (this.routeOffice !== office || this.sel?.ref !== t.ref) return;
                this.assignees = res.data || [];
                if (this.routeTo && !this.assignees.some((a) => a.employee_id === this.routeTo)) this.routeTo = '';
            } catch (err) {
                if (this.routeOffice === office) this.routeError = errText(err, 'Could not load the officers for this office.');
            } finally {
                if (this.routeOffice === office) this.assigneesLoading = false;
            }
        },

        async saveRouting() {
            const t = this.sel;
            if (!t?.ref || this.assigning) return;
            this.assigning = true;
            this.routeError = '';
            const body = {};
            if (this.routeOffice) body.office = this.routeOffice;
            if (this.routeTo) body.employee_id = this.routeTo;
            try {
                const res = await api.post('/admin/support/tickets/' + encodeURIComponent(t.ref) + '/assign', body);
                if (this.selRef === t.ref) this.sel = res.data;
                this.patchRow(res.data);
                this.routing = false;
                toast(res.data?.assigned_to ? 'Assigned to ' + res.data.assigned_to.name + '. They are notified.' : 'Routed to ' + this.officeLabel(res.data) + '.');
                this.loadCounts();
            } catch (err) {
                if (err?.code === 'NOT_AN_ASSIGNEE') {
                    this.routeError = 'That officer cannot take tickets for this office. Choose someone from the list.';
                    this.loadAssignees();
                } else {
                    this.routeError = errText(err, 'Could not assign this ticket.');
                }
            } finally {
                this.assigning = false;
            }
        },

        async loadHelp() {
            this.helpLoading = true;
            this.helpFailed = false;
            try {
                const res = await api.get('/admin/support/helpfulness');
                // What residents found unhelpful first: that is what needs work.
                this.help = (res.data || []).slice().sort((a, b) => (b.not_helpful - a.not_helpful) || (b.helpful - a.helpful));
                this.helpLoaded = true;
            } catch {
                this.helpFailed = true;
            } finally {
                this.helpLoading = false;
            }
        },
        get helpShown() { return this.helpAll ? this.help : this.help.slice(0, 6); },
    }));
}
