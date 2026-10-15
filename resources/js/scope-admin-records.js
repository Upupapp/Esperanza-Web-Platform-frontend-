// Scope round 2026-10-15 - Constituents and Communications: data requests, corrections, record history, message history, merges, the feedback desk, editing posts and events.
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.
//
// Every component here sits inside its page's own component, and Alpine
// resolves `this` over the whole scope chain: a method may use the page's
// state and helpers (openAccount, accountDetail, citizenAccountList,
// loadRegistry, dqRecordName, tab ...). So the Constituents components'
// own fields carry a prefix (dr, ic, cm, rh, mh) and none can shadow a
// name the page's markup around them uses.

import api from './api/client';

const OPEN = ['Submitted', 'Under Review'];

// The statuses these screens show, in x-ui.badge's colours. The badge's
// statusBind form prints its whole colour table into the page at every use,
// and the Constituents page is near its size budget.
const TONES = {
    Submitted: ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
    'Under Review': ['bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
    Completed: ['bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
    Rejected: ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
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

// ------------------------------------------------------------------ Names

const IDENTITY_FIELDS = { first_name: 'First name', middle_name: 'Middle name', last_name: 'Last name', suffix: 'Suffix', birthdate: 'Birth date', sex: 'Sex', barangay: 'Barangay' };

/**
 * The middle name and suffix of an account, read back from its full name:
 * the account detail sends name, first_name and last_name only, and the
 * name is those four joined by spaces (CitizenAccount::getNameAttribute).
 * Where the last name fits more than once the last place wins, so a middle
 * name equal to the last name stays the middle name. `known` is false when
 * the name does not split that way, and the officer is told to check.
 */
function splitName(full, first, last) {
    const f = String(full || '').trim();
    const a = String(first || '').trim();
    const z = String(last || '').trim();
    if (!f || !a || !z) return { middle: '', suffix: '', known: false };
    if (f === a + ' ' + z) return { middle: '', suffix: '', known: true };
    if (!f.startsWith(a + ' ')) return { middle: '', suffix: '', known: false };
    const rest = f.slice(a.length + 1);
    for (let p = rest.length - z.length; p >= 0; p -= 1) {
        if (rest.slice(p, p + z.length) !== z) continue;
        if (p > 0 && rest[p - 1] !== ' ') continue;
        const after = rest.slice(p + z.length);
        if (after && after[0] !== ' ') continue;
        const suffix = after.trim();
        if (suffix.includes(' ')) continue;
        return { middle: rest.slice(0, Math.max(0, p - 1)).trim(), suffix, known: true };
    }
    return { middle: '', suffix: '', known: false };
}

function normSex(v) {
    const s = String(v || '').trim().toLowerCase();
    if (s === 'male' || s === 'm') return 'Male';
    if (s === 'female' || s === 'f') return 'Female';
    return '';
}

function shown(v) {
    if (v === null || v === undefined || v === '') return '(empty)';
    if (typeof v === 'boolean') return v ? 'Yes' : 'No';
    let s;
    if (Array.isArray(v)) s = v.length ? v.map((x) => (x && typeof x === 'object' ? JSON.stringify(x) : String(x))).join(', ') : '(none)';
    else if (typeof v === 'object') s = JSON.stringify(v);
    else s = String(v);
    return s.length > 200 ? s.slice(0, 199) + '…' : s;
}

function humanize(key) {
    const s = String(key || '').replace(/_/g, ' ').trim();
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

// ---------------------------------------------------------- Record history

const HISTORY_ACTIONS = {
    'constituent.individual.created': 'Record created',
    'constituent.individual.updated': 'Record edited',
    'constituent.individual.deceased': 'Marked as deceased',
    'constituent.individual.transfer': 'Transferred',
    'constituent.individual.archive': 'Archived',
    'constituents.merged': 'Merged with another record',
    'constituents.merge_reverted': 'Merge undone',
    'constituents.verified': 'Verified',
    'constituents.returned': 'Sent back to the resident for changes',
    'constituent.registry_match.confirmed': 'Confirmed as the resident’s record',
    'constituent.registry_match.linked': 'Linked to the resident’s account',
    'constituent.registry_match.rejected': 'Found to be someone else',
    'resident_profile.created': 'Profile sent by the resident',
    'resident_profile.matched': 'Matched to the resident’s profile',
};

const HISTORY_FIELDS = {
    dob: 'Birth date', sitio: 'Sitio / Purok', contact: 'Contact number', income_range: 'Income range',
    civil_status: 'Civil status', relation: 'Relation in household', household_id: 'Household', family_id: 'Family',
    tags: 'Sector tags', verification: 'Verification', review_note: 'Note to the resident', merged_into: 'Merged into',
};

// ------------------------------------------------------------- Data requests

const DR_KINDS = { access: 'See their data', correction: 'Correct their data', erasure: 'Erase their data', portability: 'A copy of their data', name_change: 'Change their name' };

// Why a verified resident's name changes, and the proof each needs (2026-10-16).
const NAME_REASONS = {
    marriage: ['Marriage', 'PSA marriage certificate'],
    annulment: ['Annulment or nullity of marriage', 'Annotated PSA marriage certificate, or the court decision'],
    court_order: ['Court order', 'Certified copy of the court order or decision'],
    clerical: ['Misspelling', 'PSA birth certificate, or a valid ID with the right spelling'],
};
const fullName = (n) => [n?.first_name, n?.middle_name, n?.last_name, n?.suffix].filter(Boolean).join(' ');

// --------------------------------------------------------------- Feedback

const FB_CHIPS = [
    { key: 'open', label: 'Open', params: { open: 1 } },
    { key: 'new', label: 'Submitted', params: { status: 'Submitted' } },
    { key: 'review', label: 'Under Review', params: { status: 'Under Review' } },
    { key: 'resolved', label: 'Completed', params: { status: 'Completed' } },
    { key: 'closed', label: 'Cancelled', params: { status: 'Cancelled' } },
    { key: 'all', label: 'All', params: {} },
];

export function register(Alpine) {
    // A correction request opened from the Data requests list: the account's
    // "Correct details" opens by itself, with that request to close.
    Alpine.store('recordsDesk', { pendingCorrection: null });

    // ---------------------------------------------------------------------
    // Residents' data requests (to see, correct, erase or get a copy of
    // their data), the second view of the To Verify tab. Wraps the whole tab
    // so the switch can show either view; the existing markup inside keeps
    // reading the page's state.
    Alpine.data('dataRequests', () => ({
        drView: 'accounts',
        drOpenCount: null,
        drRows: [],
        drLoading: false,
        drFailed: false,
        drLoaded: false,
        drFilter: 'open',
        drKind: '',
        drSearch: '',
        drPage: 1,
        drLastPage: 1,
        drMoreLoading: false,
        drSeq: 0,
        drTimer: null,
        drSel: null,
        drOutcome: 'Completed',
        drNote: '',
        drBusy: false,
        drError: '',
        drKinds: DR_KINDS,
        drOutcomes: [
            ['Completed', 'Done. Say what was done.'],
            ['Rejected', 'Declined. Say why.'],
            ['Under Review', 'Still checking. Say what happens next.'],
        ],

        get drMay() { return this.$store.session.canPerm('const_citizen_verification', 'view'); },
        get drMayResolve() { return this.$store.session.canPerm('const_citizen_verification', 'verify'); },

        async init() {
            this.$watch('drSearch', () => {
                clearTimeout(this.drTimer);
                this.drTimer = setTimeout(() => this.drReload(), 350);
            });
            this.$watch('drKind', () => this.drReload());
            await this.$store.session.whenReady();
            if (this.drMay) this.drCount();
        },

        when,
        drTone: tone,
        drDot: dot,
        drKindLabel(kind) { return DR_KINDS[kind] || humanize(kind); },
        drReasonLabel(r) { return (NAME_REASONS[r] || [humanize(r)])[0]; },
        drProofHint(r) { return (NAME_REASONS[r] || [])[1] || ''; },
        drNewName(r) { return fullName(r?.requested); },
        drIsOpen(r) { return OPEN.includes(r?.status); },

        drShow(view) {
            this.drView = view;
            if (view === 'requests' && !this.drLoaded && !this.drLoading) this.drLoad();
        },
        drSetFilter(f) {
            if (this.drFilter === f) return;
            this.drFilter = f;
            this.drReload();
        },
        // Filters reload the list once it has been looked at.
        drReload() { if (this.drLoaded || this.drView === 'requests') this.drLoad(); },

        async drCount() {
            try {
                const res = await api.get('/admin/data-requests', { params: { open: 1, per_page: 1 } });
                this.drOpenCount = res.meta?.total ?? 0;
            } catch {
                // The switch then shows no count.
            }
        },

        drParams(page) {
            const p = { page, per_page: 25 };
            if (this.drFilter === 'open') p.open = 1;
            else if (this.drFilter === 'closed') p.status = 'Completed,Rejected';
            if (this.drKind) p.kind = this.drKind;
            const q = this.drSearch.trim();
            if (q) p.q = q;
            return p;
        },

        async drLoad() {
            const seq = ++this.drSeq;
            this.drLoading = true;
            this.drFailed = false;
            try {
                const res = await api.get('/admin/data-requests', { params: this.drParams(1) });
                if (seq !== this.drSeq) return;
                this.drRows = res.data || [];
                this.drPage = 1;
                this.drLastPage = res.meta?.last_page || 1;
                if (this.drFilter === 'open' && !this.drKind && !this.drSearch.trim()) this.drOpenCount = res.meta?.total ?? this.drRows.length;
                this.drLoaded = true;
            } catch {
                if (seq === this.drSeq) this.drFailed = true;
            } finally {
                if (seq === this.drSeq) this.drLoading = false;
            }
        },

        async drMore() {
            if (this.drMoreLoading || this.drPage >= this.drLastPage) return;
            const seq = this.drSeq;
            this.drMoreLoading = true;
            try {
                const res = await api.get('/admin/data-requests', { params: this.drParams(this.drPage + 1) });
                if (seq !== this.drSeq) return;
                const seen = new Set(this.drRows.map((r) => r.ref));
                this.drRows = [...this.drRows, ...(res.data || []).filter((r) => !seen.has(r.ref))];
                this.drPage += 1;
                this.drLastPage = res.meta?.last_page || this.drLastPage;
            } catch (err) {
                fail(err, 'Could not load more data requests.');
            } finally {
                this.drMoreLoading = false;
            }
        },

        drRefresh() {
            this.drCount();
            if (this.drLoaded) this.drLoad();
        },

        drPick(r) {
            this.drSel = r;
            this.drOutcome = 'Completed';
            this.drNote = '';
            this.drError = '';
        },

        async drResolve() {
            const r = this.drSel;
            const note = this.drNote.trim();
            if (!r || this.drBusy || note.length < 5) return;
            this.drBusy = true;
            this.drError = '';
            const outcome = this.drOutcome;
            try {
                const res = await api.post('/admin/data-requests/' + encodeURIComponent(r.ref) + '/resolve', { status: outcome, resolution: note });
                const row = { ...r, ...(res.data || {}), handled_by: this.$store.session.account?.name || r.handled_by };
                this.drRows = this.drRows.map((x) => (x.ref === r.ref ? row : x));
                this.drSel = null;
                toast({
                    Completed: 'Marked done. The resident was told.',
                    Rejected: 'Declined. The resident was told why.',
                    'Under Review': 'Marked as being checked. The resident was told.',
                }[outcome]);
                this.drRefresh();
            } catch (err) {
                if (err?.code === 'ALREADY_RESOLVED') {
                    // Someone else closed it meanwhile: show the list as it is now.
                    this.drSel = null;
                    toast('This request was already resolved by someone else. The list is up to date now.', 'warning');
                    this.drRefresh();
                } else {
                    // The note stays, to send again.
                    this.drError = errText(err, 'Could not save this.');
                }
            } finally {
                this.drBusy = false;
            }
        },

        // The account the request is about, in the account review. An open
        // correction request opens the account's "Correct details" with it; an
        // open change of name, with the new name and its reason filled in.
        drOpenAccount(r) {
            const no = r?.citizen?.account_no;
            if (!no) return;
            const row = (this.citizenAccountList || []).find((i) => i.citizen_account?.account_no === no)
                || { ref: no, name: r.citizen.name, barangay: r.citizen.barangay, tags: [], citizen_account: { account_no: no }, accountOnly: true };
            let hint = null;
            if (this.drIsOpen(r) && r.kind === 'correction') hint = { account: no, ref: r.ref };
            if (this.drIsOpen(r) && r.kind === 'name_change' && r.requested) {
                hint = {
                    account: no,
                    ref: r.ref,
                    prefill: { first_name: r.requested.first_name || '', middle_name: r.requested.middle_name || '', last_name: r.requested.last_name || '', suffix: r.requested.suffix || '' },
                    reason: 'Name change (' + this.drReasonLabel(r.reason).toLowerCase() + '): ' + (r.current_name || r.citizen?.name || '') + ' to ' + this.drNewName(r) + '. Proof checked.',
                };
            }
            this.$store.recordsDesk.pendingCorrection = hint;
            this.drSel = null;
            this.openAccount(row);
        },
    }));

    // ---------------------------------------------------------------------
    // Correcting a verified account's locked details (name, birth date, sex,
    // barangay) with a reason, in the account review. The resident cannot
    // change them once the account is approved.
    Alpine.data('identityCorrection', () => ({
        icFor: null,
        icOpen: false,
        icForm: { first_name: '', middle_name: '', last_name: '', suffix: '', birthdate: '', sex: '', barangay: '', reason: '', data_request_ref: '' },
        icNameKnown: true,
        icSexFrom: '',
        icBusy: false,
        icError: '',
        icDone: null,
        icRequests: [],
        when,

        get icMay() { return this.$store.session.canPerm('const_citizen_verification', 'verify'); },
        get icLocked() { return this.accountDetail?.status === 'Approved'; },
        // The latest identity check found the ID saying something else than
        // the account (2026-10-16): the account can be corrected to match it,
        // approved or not.
        get icCheck() { return this.accountDetail?.identity?.checks?.[0] || null; },
        // Read against the account as it is now: a corrected account no longer differs.
        get icMismatch() { return (this.icCheck?.comparison || []).filter((r) => r.matches === false).map((r) => r.field); },
        get icHistory() { return this.accountDetail?.name_history || []; },
        get icToday() { return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' }); },
        get icSexHint() {
            if (this.icSexFrom === 'record') return 'The account does not show its sex here: this is the registry record’s. Check it against the ID.';
            if (!this.icForm.sex) return 'Choose the sex shown on the ID.';
            return '';
        },

        init() {
            this.$watch('accountDetail?.account_no', (no) => this.icSync(no));
            this.icSync(this.accountDetail?.account_no);
        },

        icSync(no) {
            if ((no || null) === this.icFor) return;
            this.icFor = no || null;
            this.icOpen = false;
            this.icError = '';
            this.icDone = null;
            this.icRequests = [];
            // The hint is for the next account to open: used there, or dropped
            // (that account failed to open and another was opened instead).
            const hint = this.$store.recordsDesk.pendingCorrection;
            if (no && hint) {
                this.$store.recordsDesk.pendingCorrection = null;
                if (hint.account === no && (this.icLocked || this.icMismatch.length) && this.icMay) this.icStart(hint.ref, hint.prefill, hint.reason);
            }
        },

        // "Make the account match the ID": the correction form, filled in
        // from what the ID says; the officer checks it and saves.
        icMatchId() {
            const id = this.icCheck?.account_from_id;
            if (!id) return;
            const prefill = {};
            ['first_name', 'middle_name', 'last_name', 'birthdate', 'sex'].forEach((k) => { if (id[k]) prefill[k] = id[k]; });
            this.icStart(null, prefill, 'Matched to the verified ID (' + (this.icCheck.extracted?.document_type || 'ID') + (this.icCheck.extracted?.document_number_last4 ? ' ending ' + this.icCheck.extracted.document_number_last4 : '') + '), checked by the officer.');
            this.$nextTick(() => this.$root?.scrollIntoView?.({ behavior: 'smooth', block: 'start' }));
        },

        // Where the account's own sex can be read: the registry comparison
        // carries the account's value; otherwise the linked registry record's.
        icAccountSex() {
            const m = this.registryMatch;
            const fields = [m?.record?.comparison, ...(m?.candidates || []).map((c) => c.comparison)].filter(Array.isArray).flat();
            const own = fields.find((f) => f?.field === 'sex' && f.account);
            if (own && normSex(own.account)) return { value: normSex(own.account), from: 'account' };
            const row = this.accountRow;
            if (row && !row.accountOnly && normSex(row.sex)) return { value: normSex(row.sex), from: 'record' };
            return { value: '', from: '' };
        },

        icStart(ref = null, prefill = null, reason = '') {
            const d = this.accountDetail || {};
            const parts = splitName(d.name, d.first_name, d.last_name);
            const sex = this.icAccountSex();
            this.icForm = {
                first_name: d.first_name || '',
                middle_name: d.middle_name ?? parts.middle,
                last_name: d.last_name || '',
                suffix: d.suffix ?? parts.suffix,
                birthdate: d.birthdate || '',
                sex: sex.value,
                barangay: d.barangay || '',
                reason: reason || '',
                data_request_ref: ref || '',
                ...(prefill || {}),
            };
            this.icNameKnown = parts.known;
            this.icSexFrom = sex.from;
            this.icError = '';
            this.icDone = null;
            this.icOpen = true;
            this.icLoadRequests(ref);
        },

        // The account's open correction requests, one of which the save can close.
        async icLoadRequests(ref) {
            const no = this.icFor;
            if (!no || !this.$store.session.canPerm('const_citizen_verification', 'view')) return;
            try {
                const res = await api.get('/admin/data-requests', { params: { open: 1, kind: 'correction,name_change', q: no, per_page: 20 } });
                if (this.icFor !== no) return;
                const list = (res.data || []).filter((r) => r.citizen?.account_no === no);
                if (ref && !list.some((r) => r.ref === ref)) list.unshift({ ref, detail: '' });
                this.icRequests = list;
            } catch {
                this.icRequests = ref ? [{ ref, detail: '' }] : [];
            }
        },

        async icSave() {
            const f = this.icForm;
            const no = this.icFor;
            if (!no || this.icBusy) return;
            if (!f.first_name.trim() || !f.last_name.trim() || !f.birthdate || !f.sex || !f.barangay) {
                this.icError = 'Fill in the first and last name, the birth date, the sex and the barangay.';
                return;
            }
            if (f.reason.trim().length < 10) {
                this.icError = 'Give the reason in at least 10 characters.';
                return;
            }
            this.icBusy = true;
            this.icError = '';
            const payload = {
                first_name: f.first_name.trim(),
                middle_name: f.middle_name.trim() || null,
                last_name: f.last_name.trim(),
                suffix: f.suffix.trim() || null,
                birthdate: f.birthdate,
                sex: f.sex,
                barangay: f.barangay,
                reason: f.reason.trim(),
                data_request_ref: f.data_request_ref || null,
            };
            try {
                const res = await api.put('/admin/citizens/' + encodeURIComponent(no) + '/identity', payload);
                const before = res.data?.before || {};
                const after = res.data?.after || {};
                this.icDone = Object.keys(IDENTITY_FIELDS)
                    .filter((k) => String(before[k] ?? '') !== String(after[k] ?? ''))
                    .map((k) => ({ label: IDENTITY_FIELDS[k], from: shown(before[k]), to: shown(after[k]) }));
                this.icOpen = false;
                toast((payload.data_request_ref ? 'Details corrected and ' + payload.data_request_ref + ' closed. The resident was told why.' : 'Details corrected. The resident was told why.')
                    + (res.data?.identity_verified_now ? ' The account now matches the ID, so the identity is verified.' : ''));
                // Both refreshed: the account here, and the data requests list.
                window.dispatchEvent(new CustomEvent('data-requests-changed'));
                await this.icRefresh(no);
            } catch (err) {
                // The form keeps what was typed.
                if (err?.code === 'NO_CHANGE') this.icError = 'Nothing was changed: the account has these details already.';
                else this.icError = errText(err, 'Could not correct the details.');
            } finally {
                this.icBusy = false;
            }
        },

        async icRefresh(no) {
            try {
                const res = await api.get('/admin/citizens/' + encodeURIComponent(no));
                if (this.accountDetail?.account_no !== no) return;
                const d = res.data || {};
                this.accountDetail = d;
                const row = this.accountRow;
                if (row?.citizen_account?.account_no === no) {
                    row.citizen_account = { ...row.citizen_account, name: d.name, first_name: d.first_name, last_name: d.last_name, barangay: d.barangay };
                    if (row.accountOnly) { row.name = d.name; row.barangay = d.barangay; }
                }
            } catch {
                // The review keeps what it showed; reopening it reads it again.
            }
        },
    }));

    // ---------------------------------------------------------------------
    // What a resident was sent: every notification, newest first, with how
    // each email or SMS went (addresses masked by the server).
    Alpine.data('citizenMessages', () => ({
        cmFor: null,
        cmOpen: false,
        cmRows: [],
        cmLoading: false,
        cmFailed: false,
        cmPage: 1,
        cmLastPage: 1,
        cmTotal: 0,
        cmMore: false,

        init() {
            this.$watch('accountDetail?.account_no', (no) => this.cmSync(no));
            this.cmSync(this.accountDetail?.account_no);
        },

        when,
        cmSync(no) {
            if ((no || null) === this.cmFor) return;
            this.cmFor = no || null;
            this.cmOpen = false;
            this.cmRows = [];
            this.cmFailed = false;
            this.cmLoading = false;
            this.cmPage = 1;
            this.cmLastPage = 1;
            this.cmTotal = 0;
        },
        cmToggle() {
            this.cmOpen = !this.cmOpen;
            if (this.cmOpen && !this.cmRows.length && !this.cmLoading) this.cmLoad(1);
        },

        async cmLoad(page = 1) {
            const no = this.cmFor;
            if (!no) return;
            if (page === 1) { this.cmLoading = true; this.cmFailed = false; } else { this.cmMore = true; }
            try {
                const res = await api.get('/admin/citizens/' + encodeURIComponent(no) + '/messages', { params: { page, per_page: 20 } });
                if (this.cmFor !== no) return;
                this.cmRows = page === 1 ? (res.data || []) : [...this.cmRows, ...(res.data || [])];
                this.cmPage = page;
                this.cmLastPage = res.meta?.last_page || 1;
                this.cmTotal = res.meta?.total ?? this.cmRows.length;
            } catch (err) {
                if (this.cmFor !== no) return;
                if (page === 1) this.cmFailed = true; else fail(err, 'Could not load older messages.');
            } finally {
                this.cmLoading = false;
                this.cmMore = false;
            }
        },

        cmChannel(d) { return { in_app: 'Portal and app', email: 'Email', sms: 'SMS' }[d?.channel] || humanize(d?.channel); },
        cmState(d) { return { Released: 'Sent', 'Pending Review': 'Waiting to send', Rejected: 'Failed', Cancelled: 'Not sent' }[d?.status] || d?.status || ''; },
        cmLabel(d) { return this.cmChannel(d) + (d?.to ? ' ' + d.to : '') + ': ' + this.cmState(d); },
        cmTone(d) {
            return {
                Released: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'Pending Review': 'bg-amber-50 text-amber-700 ring-amber-200',
                Rejected: 'bg-rose-50 text-rose-700 ring-rose-200',
            }[d?.status] || 'bg-slate-50 text-slate-600 ring-slate-200';
        },
        cmProblems(m) { return (m?.deliveries || []).filter((d) => d.error && d.status !== 'Released'); },
    }));

    // ---------------------------------------------------------------------
    // A resident record's change history, from the audit log: who, when,
    // what, and each field before and after. Sensitive values come masked
    // from the server for a role without view_sensitive; shown as given.
    Alpine.data('residentHistory', () => ({
        rhFor: null,
        rhOpen: false,
        rhRows: [],
        rhLoading: false,
        rhFailed: false,
        rhSensitive: true,

        init() {
            this.$watch('selectedIndividual?.ref', (ref) => this.rhSync(ref));
            this.$watch('showIndividualDetail', (v) => { if (v) this.rhReset(); });
            // An edit saved in this window is the newest change: show it.
            this.$watch('editing', (v) => { if (!v && this.rhOpen) this.rhLoad(); });
            this.rhSync(this.selectedIndividual?.ref);
        },

        when,
        rhSync(ref) {
            if ((ref || null) === this.rhFor) return;
            this.rhFor = ref || null;
            this.rhReset();
        },
        rhReset() {
            this.rhOpen = false;
            this.rhRows = [];
            this.rhFailed = false;
            this.rhLoading = false;
        },
        rhToggle() {
            this.rhOpen = !this.rhOpen;
            if (this.rhOpen && !this.rhRows.length && !this.rhLoading) this.rhLoad();
        },

        async rhLoad() {
            const ref = this.rhFor;
            if (!ref) return;
            this.rhLoading = true;
            this.rhFailed = false;
            try {
                const res = await api.get('/admin/residents/' + encodeURIComponent(ref) + '/history');
                if (this.rhFor !== ref) return;
                this.rhRows = res.data || [];
                this.rhSensitive = res.meta?.sensitive_shown !== false;
            } catch {
                if (this.rhFor === ref) this.rhFailed = true;
            } finally {
                if (this.rhFor === ref) this.rhLoading = false;
            }
        },

        rhAction(action) {
            if (HISTORY_ACTIONS[action]) return HISTORY_ACTIONS[action];
            return humanize(String(action || '').split('.').pop()) || 'Changed';
        },
        rhField(field) { return HISTORY_FIELDS[field] || humanize(field); },
        rhValue: shown,
    }));

    // ---------------------------------------------------------------------
    // Every merge of two resident records, and undoing one: the merged-away
    // record comes back with exactly the rows that were moved off it.
    Alpine.data('mergeHistory', () => ({
        mhRows: [],
        mhLoading: false,
        mhFailed: false,
        mhLoaded: false,
        mhPage: 1,
        mhLastPage: 1,
        mhMore: false,
        mhSeq: 0,
        mhConfirm: null,
        mhBusy: false,
        mhError: '',

        get mhMay() { return this.$store.session.canPerm('const_data_quality', 'view'); },
        get mhMayUndo() { return this.$store.session.canPerm('const_data_quality', 'merge'); },

        init() {
            // Fresh each time the tab is opened; a merge made elsewhere shows.
            this.$watch('tab', (t) => { if (t === 'data-quality') this.mhLoad(); });
            if (this.tab === 'data-quality') this.mhLoad();
        },

        when,
        // The registry knows the kept record's name; a merged-away record is
        // out of the list until it is restored, so its reference stands alone.
        mhName(ref) {
            if (!ref || typeof this.dqRecordName !== 'function') return '';
            const name = this.dqRecordName(ref);
            return name && name !== ref ? name : '';
        },

        async mhLoad() {
            await this.$store.session.whenReady();
            if (!this.mhMay) return;
            const seq = ++this.mhSeq;
            this.mhLoading = true;
            this.mhFailed = false;
            try {
                const res = await api.get('/admin/merges', { params: { page: 1, per_page: 10, sort: 'created_at', direction: 'desc' } });
                if (seq !== this.mhSeq) return;
                this.mhRows = res.data || [];
                this.mhPage = 1;
                this.mhLastPage = res.meta?.last_page || 1;
                this.mhLoaded = true;
            } catch {
                if (seq === this.mhSeq) this.mhFailed = true;
            } finally {
                if (seq === this.mhSeq) this.mhLoading = false;
            }
        },

        async mhLoadMore() {
            if (this.mhMore || this.mhPage >= this.mhLastPage) return;
            const seq = this.mhSeq;
            this.mhMore = true;
            try {
                const res = await api.get('/admin/merges', { params: { page: this.mhPage + 1, per_page: 10, sort: 'created_at', direction: 'desc' } });
                if (seq !== this.mhSeq) return;
                const seen = new Set(this.mhRows.map((m) => m.id));
                this.mhRows = [...this.mhRows, ...(res.data || []).filter((m) => !seen.has(m.id))];
                this.mhPage += 1;
                this.mhLastPage = res.meta?.last_page || this.mhLastPage;
            } catch (err) {
                fail(err, 'Could not load older merges.');
            } finally {
                this.mhMore = false;
            }
        },

        mhAsk(m) {
            this.mhConfirm = m;
            this.mhError = '';
        },

        async mhUndo() {
            const m = this.mhConfirm;
            if (!m || this.mhBusy) return;
            this.mhBusy = true;
            this.mhError = '';
            try {
                const res = await api.post('/admin/merges/' + encodeURIComponent(m.id) + '/revert', {});
                const at = res.data?.reverted_at || new Date().toISOString();
                this.mhRows = this.mhRows.map((x) => (x.id === m.id ? { ...x, reverted_at: at } : x));
                this.mhConfirm = null;
                toast((res.data?.restored || m.merged || 'The record') + ' is its own record again, with the links it had.');
                // The restored record and its household and family links.
                if (typeof this.loadRegistry === 'function') this.loadRegistry();
            } catch (err) {
                if (err?.code === 'ALREADY_REVERTED') {
                    this.mhConfirm = null;
                    toast('This merge was already undone. The list is up to date now.', 'warning');
                    this.mhLoad();
                } else {
                    this.mhError = errText(err, 'Could not undo this merge.');
                }
            } finally {
                this.mhBusy = false;
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Communications > Feedback: residents' tickets within the officer's
    // reach (FeedbackDesk::visibleTo), the conversation, replies with a
    // status, routing to an office and assigning to an officer, and how the
    // help content was rated.
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

        async load() {
            const seq = ++this.seq;
            this.loading = true;
            this.failed = false;
            try {
                const res = await api.get('/admin/support/tickets', { params: this.params(1) });
                if (seq !== this.seq) return;
                this.rows = res.data || [];
                this.page = 1;
                this.lastPage = res.meta?.last_page || 1;
                this.total = res.meta?.total ?? this.rows.length;
                this.applyMeta(res.meta);
                this.loaded = true;
            } catch {
                if (seq === this.seq) this.failed = true;
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
            if (window.matchMedia?.('(max-width: 1023px)').matches) this.$nextTick(() => this.$refs.ticket?.scrollIntoView({ block: 'start', behavior: 'smooth' }));
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
