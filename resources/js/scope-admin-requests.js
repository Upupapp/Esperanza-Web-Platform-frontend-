// Scope round 2026-10-15 - Dokyu and Tulong queues and the Dashboard: assignment, overdue work, issued documents, appointments.
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.
//
// requestAssign and issuedDocuments sit inside a Dokyu or Tulong detail and
// read the page's own selected, requests, processing, terminalStatuses and
// apiBase() through Alpine's scope chain; they never redefine those names.

import api from './api/client';

const TZ = 'Asia/Manila';

/** A toast on window: after an await, the button that asked may be gone. */
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

/** The server's own words for a failure (English: the staff site), else the fallback. */
const says = (err, fallback) => (err && err.localised ? err.localised('en') : fallback);

/** Today in Esperanza, as Y-m-d. */
const manilaToday = () => new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());

/** When a Y-m-d day (at an HH:MM time) begins in Esperanza: UTC+8, no daylight saving. */
const manilaInstant = (ymd, hm = '00:00') => new Date(String(ymd).slice(0, 10) + 'T' + hm + ':00+08:00');

/** 'Oct 15, 2026' for a Y-m-d date or a timestamp, as the day it is in Esperanza. */
function day(value, opts = { month: 'short', day: 'numeric', year: 'numeric' }) {
    if (!value) return '';
    const s = String(value);
    const d = /^\d{4}-\d{2}-\d{2}$/.test(s) ? manilaInstant(s) : new Date(s);
    return Number.isNaN(d.getTime()) ? s : d.toLocaleDateString('en-US', { ...opts, timeZone: TZ });
}

/** '13:30' -> '1:30 PM'. */
function time(hm) {
    const [h, m] = String(hm || '').split(':').map(Number);
    if (Number.isNaN(h) || Number.isNaN(m) || m === undefined) return hm || '';
    return (h % 12 || 12) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' AM' : ' PM');
}

/** 'department_staff' -> 'Department Staff'. */
const roleLabel = (key) => String(key || '').split('_').filter(Boolean).map((w) => w[0].toUpperCase() + w.slice(1)).join(' ');

// ------------------------------------------------------------ Assignment

/**
 * Who handles a request. "Assign to me" and "Unassign me" stay the page's own
 * setAssignment(); an officer who may hand requests on (the service's assign,
 * or approve where the service has no assign - RequestController::handOnAction)
 * also gets Assign to... (GET .../assignees, POST .../assign {employee_id}) and
 * Unassign for someone else's request (DELETE .../assign).
 */
export function requestAssign() {
    return {
        picking: false,
        pickRef: null,
        loadingOfficers: false,
        officersFailed: false,
        officersError: '',
        officers: [],
        choice: '',
        assigning: false,

        // Mine by employee id when the request carries it; by name otherwise, as before.
        get mine() {
            const r = this.selected;
            const me = this.$store.session.account;
            if (!r || !r.assigned || !me) return false;
            return r.assigned_employee_id ? r.assigned_employee_id === me.id : r.assigned === me.name;
        },
        // "Assign to me": the same officers as before.
        get mayTake() {
            const r = this.selected;
            const me = this.$store.session.account;
            return !!r && !r.assigned && !this.terminalStatuses.includes(r.status)
                && (!me || ['department_head', 'municipal_admin', 'super_admin'].includes(me.roleKey));
        },
        // The server says so (permitted.hand_on: review, then assign - or
        // approve where the service has no assign); the older guess stays for
        // a detail without it.
        get mayHandOn() {
            const r = this.selected;
            const p = r && r.permitted;
            const may = p && ('hand_on' in p ? p.hand_on : (p.review && (p.assign || p.approve)));
            return !!(may && !this.terminalStatuses.includes(r.status));
        },
        officerLabel(o) {
            return o.name + ' · ' + roleLabel(o.role) + (o.dept ? ', ' + o.dept : '') + (o.current ? ' (has it now)' : '');
        },
        async openPicker() {
            if (this.picking && this.pickRef === this.selected?.ref) {
                this.picking = false;
                return;
            }
            this.picking = true;
            await this.loadOfficers();
        },
        async loadOfficers() {
            const ref = this.selected?.ref;
            if (!ref) return;
            this.pickRef = ref;
            this.choice = '';
            this.loadingOfficers = true;
            this.officersFailed = false;
            try {
                const res = await api.get(this.apiBase(ref) + '/assignees');
                if (this.pickRef === ref) this.officers = res.data || [];
            } catch (err) {
                if (this.pickRef !== ref) return;
                this.officersFailed = true;
                this.officersError = says(err, 'Could not load the officers.');
            } finally {
                if (this.pickRef === ref) this.loadingOfficers = false;
            }
        },
        async assignTo() {
            const r = this.selected;
            const o = this.officers.find((x) => x.employee_id === this.choice);
            if (!r || !o || this.assigning) return;
            this.assigning = true;
            try {
                const res = await api.post(this.apiBase(r.ref) + '/assign', { employee_id: o.employee_id });
                this.takeAssignee(r, res.data);
                this.picking = false;
                toast(r.ref + ' is now assigned to ' + (res.data?.assigned || o.name) + '.');
            } catch (err) {
                toast(says(err, 'Could not assign ' + r.ref + '.'), 'error');
                // That officer can no longer take it (NOT_AN_ASSIGNEE): read the list again.
                if (err && err.code === 'NOT_AN_ASSIGNEE') this.loadOfficers();
            } finally {
                this.assigning = false;
            }
        },
        async unassign() {
            const r = this.selected;
            if (!r || this.assigning) return;
            const was = r.assigned;
            this.assigning = true;
            try {
                const res = await api.delete(this.apiBase(r.ref) + '/assign');
                this.takeAssignee(r, res.data);
                this.picking = false;
                toast(r.ref + (was ? ' is no longer assigned to ' + was + '.' : ' is unassigned.'));
            } catch (err) {
                toast(says(err, 'Could not unassign ' + r.ref + '.'), 'error');
            } finally {
                this.assigning = false;
            }
        },
        // The server's answer, on the open request and on its row in the queue.
        takeAssignee(r, d) {
            const row = (this.requests || []).find((x) => x.ref === r.ref);
            [r, row].forEach((x) => {
                if (!x) return;
                x.assigned = d?.assigned ?? null;
                x.assigned_employee_id = d?.assigned_employee_id ?? null;
            });
        },
    };
}

// ------------------------------------------------------ Issued documents

/**
 * The documents issued for a request (detail.issued_documents): reprint the
 * original (a new, numbered copy), revoke a copy with the reason the public
 * Verify page then shows, open the Verify page. Issuing and reprinting are
 * releasing, so the page passes its release gate (ModuleController::guardRelease).
 */
export function issuedDocuments() {
    return {
        acting: null, // the control number whose reprint or revoke form is open
        kind: null, // 'reprint' | 'revoke'
        reason: '',
        docBusy: false,
        day,

        get docs() {
            return (this.selected && this.selected.issued_documents) || [];
        },
        get reasonOk() {
            return this.reason.trim().length >= (this.kind === 'revoke' ? 5 : 3);
        },
        expired(d) {
            return !d.revoked && !!d.valid_until && d.valid_until < manilaToday();
        },
        start(d, kind) {
            if (this.acting === d.control_number && this.kind === kind) {
                this.stop();
                return;
            }
            this.acting = d.control_number;
            this.kind = kind;
            this.reason = '';
        },
        stop() {
            this.acting = null;
            this.kind = null;
            this.reason = '';
        },
        async confirmAction() {
            const r = this.selected;
            const cn = this.acting;
            const kind = this.kind;
            if (!r || !cn || !this.reasonOk || this.docBusy) return;
            this.docBusy = true;
            try {
                const res = await api.post('/admin/documents/' + encodeURIComponent(cn) + '/' + kind, { reason: this.reason.trim() });
                if (kind === 'revoke') {
                    r.issued_documents = res.data?.documents || r.issued_documents;
                    toast(cn + ' is revoked. The Verify page now says it is not valid, and why.', 'warning');
                } else {
                    await this.reloadDocs(r);
                    toast('Reprinted as ' + (res.data?.control_number || 'a new copy') + '. The printed copy now carries its number and QR code.');
                }
                this.stop();
            } catch (err) {
                if (err && err.code === 'ALREADY_REVOKED') {
                    toast(cn + ' was already revoked.', 'warning');
                    this.stop();
                    this.reloadDocs(r);
                } else {
                    toast(says(err, 'Could not ' + kind + ' ' + cn + '.'), 'error');
                }
            } finally {
                this.docBusy = false;
            }
        },
        // When issuing at Mark to Release failed, the officer can issue from here.
        async issueNow() {
            const r = this.selected;
            if (!r || this.docBusy) return;
            this.docBusy = true;
            try {
                // One may have been issued meanwhile (another window): never a second original.
                await this.reloadDocs(r);
                if ((r.issued_documents || []).length) return;
                const res = await api.post('/admin/requests/' + encodeURIComponent(r.ref) + '/issue');
                await this.reloadDocs(r);
                toast('Issued ' + (res.data?.control_number || 'the document') + '. The printed copy now carries its number and QR code.');
            } catch (err) {
                toast(says(err, 'Could not issue the document for ' + r.ref + '.'), 'error');
            } finally {
                this.docBusy = false;
            }
        },
        async reloadDocs(r) {
            try {
                const res = await api.get(this.apiBase(r.ref));
                r.issued_documents = res.data?.issued_documents || [];
            } catch {
                // The list stays as it was; the next open reads it again.
            }
        },
    };
}

// ----------------------------------------------------------- Appointments

const STATUSES = ['Booked', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];
const ACTIVE = ['Booked', 'Confirmed'];
const WEEKDAYS = [[1, 'Mon'], [2, 'Tue'], [3, 'Wed'], [4, 'Thu'], [5, 'Fri'], [6, 'Sat'], [7, 'Sun']];
// Offices::BARANGAY on the server: a barangay hall's schedule.
const BARANGAY_OFFICE = 'Barangay Office';

// AppointmentBook::act's refusals, in words for the counter.
const APPT_ERRORS = {
    REASON_REQUIRED: 'Say why the appointment is cancelled, in at least 5 characters. The resident is told.',
    NOT_YET: 'Too early: an appointment is marked completed or a no-show from an hour before its time.',
    INVALID_APPOINTMENT_ACTION: 'This appointment was already changed. The list now shows where it stands.',
};

// Colour, icon and the word itself: status is never colour alone.
const STATUS_STYLE = {
    Booked: ['bg-blue-50 text-blue-700 ring-blue-200', 'calendar-clock'],
    Confirmed: ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'calendar-check'],
    Completed: ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'circle-check'],
    Cancelled: ['bg-slate-100 text-slate-600 ring-slate-200', 'circle-x'],
    'No Show': ['bg-amber-50 text-amber-700 ring-amber-200', 'user-x'],
};

/** A new schedule's form; the office and the barangay chosen when there is only one. */
function blankForm(choices = {}) {
    const offices = choices.offices || [];
    const barangays = choices.barangays || [];
    return {
        office: offices.length === 1 ? offices[0] : '',
        barangay: barangays.length === 1 ? barangays[0] : '',
        service_key: '',
        label: '',
        weekdays: [1, 2, 3, 4, 5],
        start_time: '08:00',
        end_time: '12:00',
        slot_minutes: 30,
        capacity: 1,
        book_days_ahead: 30,
        location: '',
        notes: '',
        active: true,
    };
}

/**
 * The Appointments page: the bookings of the schedules this officer runs,
 * one day at a time (GET /admin/appointments), with Confirm, Complete,
 * No-show and Cancel; and the schedules themselves with their closed days
 * (GET /admin/appointment-schedules, meta.choices for the form).
 */
export function appointmentsDesk() {
    return {
        ready: false,
        mayBookings: false,
        maySchedules: false,
        section: 'bookings',
        today: manilaToday(),
        statuses: STATUSES,
        weekdayChoices: WEEKDAYS,
        barangayOffice: BARANGAY_OFFICE,
        day,
        time,

        // Bookings
        date: manilaToday(),
        statusFilter: '',
        bookings: [],
        counts: {},
        bLoading: false,
        bFailed: false,
        bError: '',
        bSeq: 0,
        actingRef: null,
        cancelOpen: false,
        cancelTarget: null,
        cancelReason: '',
        cancelError: '',

        // Schedules
        schedules: [],
        choices: { offices: [], barangays: [], services: [] },
        sLoading: false,
        sFailed: false,
        sError: '',
        formOpen: false,
        editingId: null,
        form: blankForm(),
        formErrors: {},
        formError: '',
        saving: false,
        delOpen: false,
        delTarget: null,
        deleting: false,

        async init() {
            const session = this.$store.session;
            await session.whenReady?.();
            this.mayBookings = session.canPerm('appt_bookings', 'view');
            this.maySchedules = session.canPerm('appt_schedules', 'view');
            this.section = this.mayBookings ? 'bookings' : 'schedules';
            this.ready = true;
            if (this.mayBookings) this.loadBookings();
            if (this.maySchedules) this.loadSchedules();
            this.$nextTick(() => window.renderIcons?.());
        },
        show(section) {
            this.section = section;
            this.$nextTick(() => window.renderIcons?.());
        },
        can(action) {
            return this.$store.session.canPerm('appt_bookings', action);
        },
        canS(action) {
            return this.$store.session.canPerm('appt_schedules', action);
        },

        // ---- Bookings ------------------------------------------------------

        dateLabel(ymd) {
            return day(ymd, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        },
        shiftDate(n) {
            const d = new Date((this.date || this.today) + 'T00:00:00Z');
            if (Number.isNaN(d.getTime())) return;
            d.setUTCDate(d.getUTCDate() + n);
            this.date = d.toISOString().slice(0, 10);
            this.loadBookings();
        },
        goToday() {
            this.date = manilaToday();
            this.loadBookings();
        },
        // quiet: after an action, the counts and the list are read again without the skeleton.
        async loadBookings(quiet = false) {
            if (!this.date) return;
            const seq = ++this.bSeq;
            this.today = manilaToday();
            if (!quiet) {
                this.bLoading = true;
                this.bFailed = false;
            }
            try {
                const params = { date: this.date };
                if (this.statusFilter) params.status = this.statusFilter;
                const res = await api.getAll('/admin/appointments', { params });
                if (seq !== this.bSeq) return;
                this.bookings = res.data || [];
                this.counts = res.meta?.today?.counts || {};
                if (Array.isArray(res.meta?.statuses) && res.meta.statuses.length) this.statuses = res.meta.statuses;
                this.bFailed = false;
            } catch (err) {
                if (seq !== this.bSeq || quiet) return;
                this.bFailed = true;
                this.bError = says(err, 'Could not load the bookings.');
            } finally {
                if (seq === this.bSeq) {
                    this.bLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            }
        },
        get todaySummary() {
            const c = this.counts || {};
            const parts = STATUSES.filter((s) => c[s]).map((s) => c[s] + ' ' + s.toLowerCase());
            return parts.length ? 'Today: ' + parts.join(' · ') : '';
        },
        statusClass(s) {
            return (STATUS_STYLE[s] || STATUS_STYLE.Cancelled)[0];
        },
        statusIcon(s) {
            return (STATUS_STYLE[s] || STATUS_STYLE.Cancelled)[1];
        },
        isActive(b) {
            return ACTIVE.includes(b.status);
        },
        // Completed and No-show open an hour before the time, as the server allows.
        tooEarly(b) {
            return Date.now() < manilaInstant(b.date, b.start_time).getTime() - 3600000;
        },
        requestLink(ref) {
            return (/^TUL-/i.test(ref) ? '/admin/assistance-requests' : '/admin/document-requests') + '?ref=' + encodeURIComponent(ref);
        },
        async act(b, action, note = null) {
            if (this.actingRef) return false;
            this.actingRef = b.ref;
            try {
                const res = await api.post('/admin/appointments/' + encodeURIComponent(b.ref) + '/' + action, note ? { note } : {});
                Object.assign(b, res.data || {});
                const done = {
                    confirm: [b.ref + ' is confirmed. The resident is told.', 'success'],
                    complete: [b.ref + ' is marked completed.', 'success'],
                    'no-show': [b.ref + ' is marked a no-show.', 'warning'],
                    cancel: [b.ref + ' is cancelled. The resident is told why.', 'warning'],
                }[action];
                toast(done[0], done[1]);
                this.loadBookings(true);
                return true;
            } catch (err) {
                const msg = APPT_ERRORS[err?.code] || says(err, 'Could not update ' + b.ref + '.');
                if (action === 'cancel') this.cancelError = msg;
                else toast(msg, 'error');
                if (err?.code === 'INVALID_APPOINTMENT_ACTION') this.loadBookings(true);
                return false;
            } finally {
                this.actingRef = null;
            }
        },
        askCancel(b) {
            this.cancelTarget = b;
            this.cancelReason = '';
            this.cancelError = '';
            this.cancelOpen = true;
        },
        async confirmCancel() {
            const b = this.cancelTarget;
            if (!b || this.cancelReason.trim().length < 5) return;
            if (await this.act(b, 'cancel', this.cancelReason.trim())) this.cancelOpen = false;
        },

        // ---- Schedules -----------------------------------------------------

        async loadSchedules() {
            this.sLoading = true;
            this.sFailed = false;
            try {
                const res = await api.get('/admin/appointment-schedules');
                this.schedules = (res.data || []).map((s) => this.withDraft(s));
                const c = res.meta?.choices || {};
                this.choices = { offices: (c.offices || []).filter(Boolean), barangays: c.barangays || [], services: c.services || [] };
            } catch (err) {
                this.sFailed = true;
                this.sError = says(err, 'Could not load the schedules.');
            } finally {
                this.sLoading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        // A row with the closed-day form's draft. The answers to a save or a
        // closed day count no upcoming bookings (no withCount there), so the
        // count read with the list is kept.
        withDraft(s, old = null) {
            return {
                ...s,
                closures: s.closures || [],
                upcoming: old ? old.upcoming : (s.upcoming ?? 0),
                _closeDate: old ? old._closeDate : '',
                _closeReason: old ? old._closeReason : '',
                _busy: false,
            };
        },
        replaceSchedule(row, extra = {}) {
            if (!row || row.id === undefined) return;
            const i = this.schedules.findIndex((s) => s.id === row.id);
            const next = { ...this.withDraft(row, i >= 0 ? this.schedules[i] : null), ...extra };
            if (i >= 0) this.schedules.splice(i, 1, next);
            else this.schedules.unshift(next);
            this.$nextTick(() => window.renderIcons?.());
        },
        weekdaysLabel(list) {
            const days = [...new Set((list || []).map(Number))].filter((n) => n >= 1 && n <= 7).sort((a, b) => a - b);
            const name = (n) => WEEKDAYS[n - 1][1];
            const runs = [];
            days.forEach((n) => {
                const last = runs[runs.length - 1];
                if (last && n === last[1] + 1) last[1] = n;
                else runs.push([n, n]);
            });
            return runs.map(([a, b]) => (a === b ? name(a) : b === a + 1 ? name(a) + ', ' + name(b) : name(a) + '–' + name(b))).join(', ') || '—';
        },
        officeName(o) {
            return o === BARANGAY_OFFICE ? 'Barangay hall' : o;
        },
        get officeOptions() {
            const list = this.choices.offices || [];
            return this.form.office && !list.includes(this.form.office) ? [this.form.office, ...list] : list;
        },
        servicesFor(office) {
            return (this.choices.services || []).filter((sv) => sv.office === office);
        },
        officeChanged() {
            if (!this.servicesFor(this.form.office).some((sv) => sv.key === this.form.service_key)) this.form.service_key = '';
        },
        newSchedule() {
            this.editingId = null;
            this.form = blankForm(this.choices);
            this.formErrors = {};
            this.formError = '';
            this.formOpen = true;
        },
        editSchedule(s) {
            this.editingId = s.id;
            this.form = {
                office: s.office || '',
                barangay: s.barangay || '',
                service_key: s.service_key || '',
                label: s.label || '',
                weekdays: (s.weekdays || []).map(Number),
                start_time: s.start_time || '',
                end_time: s.end_time || '',
                slot_minutes: s.slot_minutes,
                capacity: s.capacity,
                book_days_ahead: s.book_days_ahead,
                location: s.location || '',
                notes: s.notes || '',
                active: !!s.active,
            };
            this.formErrors = {};
            this.formError = '';
            this.formOpen = true;
        },
        fieldError(key) {
            const e = this.formErrors[key] || this.formErrors[key + '.0'];
            return Array.isArray(e) ? e[0] : (e || '');
        },
        get slotPreview() {
            const f = this.form;
            const [sh, sm] = String(f.start_time || '').split(':').map(Number);
            const [eh, em] = String(f.end_time || '').split(':').map(Number);
            const len = Number(f.slot_minutes);
            if ([sh, sm, eh, em].some((n) => n === undefined || Number.isNaN(n)) || !(len >= 5)) return '';
            const minutes = (eh * 60 + em) - (sh * 60 + sm);
            if (minutes <= 0) return 'The closing time must be after the opening time.';
            const slots = Math.floor(minutes / len);
            if (!slots) return 'No slot of that length fits between these times.';
            const people = slots * Math.max(1, Number(f.capacity) || 1);
            return slots + (slots === 1 ? ' slot' : ' slots') + ' a day, for up to ' + people + (people === 1 ? ' resident.' : ' residents.');
        },
        async saveSchedule() {
            if (this.saving) return;
            const f = this.form;
            const body = {
                office: f.office,
                barangay: f.office === BARANGAY_OFFICE ? (f.barangay || null) : null,
                service_key: f.service_key || null,
                label: String(f.label || '').trim(),
                weekdays: [...(f.weekdays || [])].map(Number).sort((a, b) => a - b),
                start_time: f.start_time,
                end_time: f.end_time,
                slot_minutes: Number(f.slot_minutes),
                capacity: Number(f.capacity),
                book_days_ahead: Number(f.book_days_ahead),
                location: String(f.location || '').trim() || null,
                notes: String(f.notes || '').trim() || null,
                active: !!f.active,
            };
            // What the server checks too (validateSchedule), said before the round trip.
            const e = {};
            if (!body.office) e.office = ['Choose the office.'];
            if (body.office === BARANGAY_OFFICE && !body.barangay) e.barangay = ['Choose the barangay.'];
            if (body.label.length < 3) e.label = ['Give it a name of at least 3 characters.'];
            if (!body.weekdays.length) e.weekdays = ['Choose at least one day.'];
            if (!body.start_time) e.start_time = ['Set the opening time.'];
            if (!body.end_time) e.end_time = ['Set the closing time.'];
            else if (body.start_time && body.end_time <= body.start_time) e.end_time = ['The closing time must be after the opening time.'];
            if (!(body.slot_minutes >= 5 && body.slot_minutes <= 240)) e.slot_minutes = ['From 5 to 240 minutes.'];
            if (!(body.capacity >= 1 && body.capacity <= 200)) e.capacity = ['From 1 to 200 residents.'];
            if (!(body.book_days_ahead >= 1 && body.book_days_ahead <= 120)) e.book_days_ahead = ['From 1 to 120 days.'];
            this.formErrors = e;
            this.formError = Object.keys(e).length ? 'Check the fields marked below.' : '';
            if (this.formError) return;
            const editing = this.editingId;
            this.saving = true;
            try {
                const res = editing
                    ? await api.put('/admin/appointment-schedules/' + editing, body)
                    : await api.post('/admin/appointment-schedules', body);
                this.replaceSchedule(res.data);
                this.formOpen = false;
                toast(editing
                    ? 'Schedule saved. Bookings already made keep their time.'
                    : (body.active ? 'Schedule created. Residents can book it now.' : 'Schedule created, switched off for now.'));
            } catch (err) {
                // The typed form stays as it was.
                this.formErrors = err?.fields || {};
                this.formError = says(err, 'Could not save the schedule.');
            } finally {
                this.saving = false;
            }
        },
        askDelete(s) {
            this.delTarget = s;
            this.delOpen = true;
        },
        async confirmDelete() {
            const s = this.delTarget;
            if (!s || this.deleting) return;
            this.deleting = true;
            try {
                const res = await api.delete('/admin/appointment-schedules/' + s.id);
                if (res.data?.deleted) {
                    this.schedules = this.schedules.filter((x) => x.id !== s.id);
                    toast('Schedule deleted.');
                } else {
                    s.active = false;
                    toast('It has bookings, so it was switched off instead: residents can no longer book it, and its bookings keep their history.', 'warning');
                }
                this.delOpen = false;
            } catch (err) {
                toast(says(err, 'Could not delete the schedule.'), 'error');
            } finally {
                this.deleting = false;
            }
        },
        async addClosure(s) {
            const date = s._closeDate;
            const reason = String(s._closeReason || '').trim();
            if (!date || reason.length < 3 || s._busy) return;
            s._busy = true;
            try {
                const res = await api.post('/admin/appointment-schedules/' + s.id + '/closures', { date, reason });
                const n = Number(res.data?.bookings_cancelled || 0);
                this.replaceSchedule(res.data, { _closeDate: '', _closeReason: '' });
                // Closing a day cancels the bookings already on it, with the
                // reason, and tells each resident (AppointmentsController::addClosure).
                toast(n
                    ? day(date) + ' is closed. ' + n + (n === 1 ? ' booking on it was' : ' bookings on it were') + ' cancelled and the resident' + (n === 1 ? ' was' : 's were') + ' told why.'
                    : day(date) + ' is closed.', 'success');
                if (n && this.mayBookings) this.loadBookings(true);
            } catch (err) {
                toast(says(err, 'Could not close that day.'), 'error');
            } finally {
                s._busy = false;
            }
        },
        async removeClosure(s, c) {
            if (s._busy) return;
            s._busy = true;
            try {
                const res = await api.delete('/admin/appointment-schedules/' + s.id + '/closures/' + c.id);
                this.replaceSchedule(res.data);
                toast(day(c.date) + ' is open for booking again.');
            } catch (err) {
                toast(says(err, 'Could not open that day again.'), 'error');
            } finally {
                s._busy = false;
            }
        },
    };
}

export function register(Alpine) {
    Alpine.data('requestAssign', requestAssign);
    Alpine.data('issuedDocuments', issuedDocuments);
    Alpine.data('appointmentsDesk', appointmentsDesk);
}
