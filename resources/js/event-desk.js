// An event's Registrations window, on Balita > Events (Esperanza round 2,
// 2026-10-30; owner: "the e-certificates will be sent to residents who
// attended, have also buttons for users to register to the posted events so
// that the municipality will have record of those who attended").
//
// Before the day it is the list of who registered (online in the portal or
// the app, or added by staff). On the day it is the door: an officer, often
// on a phone, types the code a resident shows (XXXX-XXXX, with or without the
// dash, any case; a handheld QR scanner types it too) and the person is
// checked in; a walk-in is found in the records or written down by name and
// barangay. After the event, attendance is put right with the checkboxes,
// and an officer who approves events issues the certificates - confirmed on
// the page, never with confirm() - to everyone marked as attended who has
// none yet. The list downloads as CSV for the office's own files.
//
// Routes (backend, round 2): GET/POST /admin/events/{key}/registrations,
// POST .../check-in {code}, POST .../attendance {registrations: [{id,
// attended}]}, POST .../certificates -> {issued, total_attended}. comm_events
// edit for the list, check-in and attendance; approve for certificates.
//
// A chunk of its own, loaded only on the Balita page (the area loader at the
// end of app.js). The component sits inside adminEvents and reads its
// barangayNames through Alpine's scope chain; every name it defines starts
// with ed, so none shadows the page's. It opens on the window event
// event-desk (the card's Registrations button) and asks for the event's
// form back the same way (events-edit): a method of the page that sets
// `open` would set this window's instead.
import api from './api/client';
import { registerCertificate, openWelfareCertificate, longDate } from './welfare-certificate';

const TZ = 'Asia/Manila';

/** A toast on window: after an await, the button that asked may be gone. */
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

/** The server's own words for a failure (English: the staff site), else the fallback. */
function says(err, fallback) {
    if (err?.explain) return err.explain('en');
    return err?.localised ? err.localised('en') : fallback;
}

const can = (action) => !!window.Alpine?.store('session')?.canPerm('comm_events', action);
const icons = () => window.Alpine?.nextTick(() => window.renderIcons?.());

/** Today in Esperanza, as Y-m-d. */
const manilaToday = () => new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());

/** 'Thu, Nov 12, 2026' for a Y-m-d day. */
function day(value) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''));
    if (!m) return value || '';
    return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], 12)).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
}

/** 'Nov 12, 9:02 AM' in Esperanza time; with the year for the file. */
function stamp(iso, withYear = false) {
    if (!iso) return '';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('en-US', { timeZone: TZ, month: 'short', day: 'numeric', ...(withYear ? { year: 'numeric' } : {}), hour: 'numeric', minute: '2-digit' });
}

/** A registration code as typed or scanned: letters and digits only, upper case. */
export function normCode(text) {
    return String(text || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
}

/** As printed for residents: XXXX-XXXX. */
export function showCode(text) {
    const c = normCode(text);
    return c.length > 4 ? c.slice(0, 4) + '-' + c.slice(4) : c;
}

const SOURCES = { resident: 'Registered online', staff: 'Added by staff', walk_in: 'Walk-in' };

// A cell that starts like a formula is opened as text, not run.
function csvCell(v) {
    const s = String(v ?? '');
    const safe = /^[=+\-@\t\r]/.test(s) ? "'" + s : s;
    return /[",\n\r]/.test(safe) ? '"' + safe.replace(/"/g, '""') + '"' : safe;
}

function downloadCsv(filename, rows) {
    // The byte-order mark lets Excel read the names' ñ and accents.
    const text = '﻿' + rows.map((r) => r.map(csvCell).join(',')).join('\r\n');
    const url = URL.createObjectURL(new Blob([text], { type: 'text/csv;charset=utf-8' }));
    const a = Object.assign(document.createElement('a'), { href: url, download: filename, rel: 'noopener' });
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 30000);
}

const slug = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 50) || 'event';
const cancelled = (r) => !!r.cancelled_at;

export function eventDesk() {
    return {
        edOpen: false,
        edEvent: null,
        edRows: [],
        edLoading: false,
        edFailed: false,
        edSeq: 0,
        edBusy: false,
        edError: '',
        edResult: '',
        edQuery: '',
        edFilter: 'all',
        // null | 'add' | 'issue'
        edPanel: null,
        // Something changed that the event cards count: they reload when the window closes.
        edChanged: false,
        // Check in by code
        edCode: '',
        edChecking: false,
        edCheck: null,
        // Add attendee: a person found in the records, or a name and barangay
        edMode: 'search',
        edPq: '',
        edPResults: [],
        edPLoading: false,
        edPFailed: false,
        edPSearched: false,
        edPSeq: 0,
        edPBlocked: false,
        edPerson: null,
        edNew: { name: '', barangay: '', contact: '' },
        edMark: true,

        init() {
            this.$watch('edOpen', (open) => {
                if (open || !this.edChanged) return;
                this.edChanged = false;
                // The cards' counts (adminEvents.load, through the scope chain).
                if (typeof this.load === 'function') this.load();
            });
        },

        edShowCode: showCode,
        edDay: day,
        edCanEdit() { return !!this.edEvent && can('edit') && this.edEvent.status === 'Released'; },
        edCanIssue() { return !!this.edEvent && can('approve') && ['Released', 'Archived'].includes(this.edEvent.status); },
        /** Check-in and attendance open on the event's day (Esperanza's). */
        edDayCome() { return !!this.edEvent?.date && manilaToday() >= String(this.edEvent.date).slice(0, 10); },

        edShow(e) {
            if (!e || !e.key) return;
            this.edEvent = e;
            this.edRows = [];
            this.edError = '';
            this.edResult = '';
            this.edQuery = '';
            this.edFilter = 'all';
            this.edPanel = null;
            this.edCode = '';
            this.edCheck = null;
            this.edOpen = true;
            this.edLoad();
        },
        async edLoad() {
            const seq = ++this.edSeq;
            this.edLoading = true;
            this.edFailed = false;
            try {
                const res = await api.getAll('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/registrations');
                if (seq !== this.edSeq) return;
                // A tick not saved yet survives the reload (a walk-in added meanwhile).
                const unsaved = new Map(this.edDirty().map((r) => [r.id, r._attended]));
                this.edRows = (res.data || []).map((r) => ({ ...r, _attended: unsaved.has(r.id) && !r.certificate ? unsaved.get(r.id) : r.attended === true }));
            } catch {
                if (seq === this.edSeq) this.edFailed = true;
            } finally {
                if (seq === this.edSeq) this.edLoading = false;
                icons();
            }
        },

        // ---- Counts and the list (methods, not getters: this object is spread)
        edTotals() {
            const live = this.edRows.filter((r) => !cancelled(r));
            const here = live.filter((r) => r._attended).length;
            return {
                registered: live.length,
                attended: here,
                waiting: live.length - here,
                cancelled: this.edRows.length - live.length,
                certificates: live.filter((r) => r.certificate).length,
            };
        },
        edCapacityText() {
            const cap = this.edEvent?.registration?.capacity;
            return cap ? 'of ' + cap + ' places' : 'no limit';
        },
        edList() {
            const q = this.edQuery.trim().toLowerCase();
            const code = normCode(q);
            const f = this.edFilter;
            return this.edRows
                .filter((r) => (f === 'all' ? !cancelled(r)
                    : f === 'here' ? !cancelled(r) && r._attended
                        : f === 'waiting' ? !cancelled(r) && !r._attended
                            : cancelled(r)))
                .filter((r) => !q
                    || (r.name || '').toLowerCase().includes(q)
                    || (r.barangay || '').toLowerCase().includes(q)
                    || (r.account_no || '').toLowerCase().includes(q)
                    || (code.length >= 3 && normCode(r.code).includes(code)))
                .sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        },
        edLine(r) {
            return [
                r.barangay ? 'Brgy. ' + r.barangay : '',
                r.account_no,
                r.contact,
                SOURCES[r.source] || '',
                r.checked_in_at ? 'checked in ' + stamp(r.checked_in_at) : '',
                cancelled(r) ? 'cancelled ' + stamp(r.cancelled_at) : '',
            ].filter(Boolean).join(' · ');
        },
        /** What the row's right side says when there is no certificate to open. */
        edState(r) {
            if (cancelled(r)) return 'Cancelled';
            if (r._attended !== (r.attended === true)) return 'Not saved yet';
            if (r.attended === true) return 'Attended';
            if (r.attended === false) return 'Did not attend';
            return this.edDayCome() ? 'Not here yet' : 'Registered';
        },
        edStateTone(r) {
            if (cancelled(r)) return 'text-ink-faint';
            if (r._attended !== (r.attended === true)) return 'text-amber-700 font-semibold';
            if (r.attended === true) return 'text-emerald-700 font-semibold';
            return r.attended === false ? 'text-rose-700' : 'text-ink-muted';
        },
        /** Attendance is marked on the day and after; a certificate already issued keeps it. */
        edCanMark(r) { return this.edCanEdit() && this.edDayCome() && !cancelled(r) && !r.certificate; },
        edDirty() { return this.edRows.filter((r) => !cancelled(r) && r._attended !== (r.attended === true)); },

        async edSaveAttendance(quiet = false) {
            const dirty = this.edDirty();
            if (!dirty.length) return true;
            this.edBusy = true;
            this.edError = '';
            try {
                await api.post('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/attendance', {
                    registrations: dirty.map((r) => ({ id: r.id, attended: !!r._attended })),
                });
                dirty.forEach((r) => { r.attended = !!r._attended; });
                this.edChanged = true;
                if (!quiet) toast('Attendance saved.');
                return true;
            } catch (err) {
                this.edError = says(err, 'Could not save the attendance. Try again.');
                return false;
            } finally {
                this.edBusy = false;
            }
        },

        // ---- Check in by code
        edTypeCode() { this.edCode = showCode(this.edCode); },
        async edCheckIn() {
            if (this.edChecking || !this.edCanEdit()) return;
            const code = normCode(this.edCode);
            if (code.length !== 8) {
                this.edCheck = { tone: 'error', title: 'Check the code', text: 'A code has 8 letters and numbers, like ABCD-2345.' };
                return;
            }
            this.edChecking = true;
            const before = this.edRows.find((r) => normCode(r.code) === code);
            try {
                const res = await api.post('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/check-in', { code });
                const row = res?.data || {};
                // The server says so itself (already_checked_in) when another
                // officer checked the person in since this list was read.
                const already = row.already_checked_in === true || (before && before.attended === true);
                const since = row.checked_in_at || before?.checked_in_at;
                let r = this.edRows.find((x) => x.id === row.id);
                if (!r && row.id !== undefined) {
                    r = { ...row };
                    this.edRows.push(r);
                }
                if (r) {
                    Object.assign(r, row, { attended: true });
                    r._attended = true;
                }
                const who = row.name || r?.name || 'The resident';
                this.edCheck = already
                    ? { tone: 'again', title: who + ' is already checked in', text: (since ? 'Since ' + stamp(since) + '. ' : '') + showCode(code) + (row.barangay ? ' · Brgy. ' + row.barangay : '') }
                    : { tone: 'ok', title: who + ' is checked in', text: showCode(code) + (row.barangay ? ' · Brgy. ' + row.barangay : '') };
                this.edCode = '';
                this.edChanged = true;
            } catch (err) {
                const again = err?.status === 409 && /CHECKED_IN|ATTENDED/.test(String(err.code || ''));
                this.edCheck = again
                    ? { tone: 'again', title: (before?.name || 'This person') + ' is already checked in', text: says(err, showCode(code)) }
                    : {
                        tone: 'error',
                        title: err?.status === 404 ? 'No registration has this code' : 'Not checked in',
                        text: err?.status === 404 ? showCode(code) + ' is not registered for this event. Check the code, or add the person as a walk-in.' : says(err, 'Could not check in. Try again.'),
                    };
            } finally {
                this.edChecking = false;
                icons();
                window.Alpine?.nextTick(() => document.getElementById('ed-code')?.focus());
            }
        },

        // ---- Add attendee
        edAdd() {
            this.edPanel = this.edPanel === 'add' ? null : 'add';
            this.edMode = this.edPBlocked ? 'new' : 'search';
            this.edPq = '';
            this.edPResults = [];
            this.edPSearched = false;
            this.edPFailed = false;
            this.edPerson = null;
            this.edNew = { name: '', barangay: this.edEvent?.barangay || '', contact: '' };
            this.edMark = this.edDayCome();
            icons();
            if (this.edPanel) window.Alpine?.nextTick(() => document.getElementById(this.edMode === 'search' ? 'ed-person-q' : 'ed-new-name')?.focus());
        },
        // The same search as Apply for New Tulong (GET /admin/people/search):
        // residents of the registry and citizen accounts, merged. It needs a
        // resident or Tulong permission the Balita team may not hold; asked
        // quietly, a refusal leaves name and barangay.
        async edPSearch() {
            const q = this.edPq.trim();
            const seq = ++this.edPSeq;
            if (q.length < 2) {
                this.edPResults = [];
                this.edPSearched = false;
                this.edPLoading = false;
                return;
            }
            this.edPLoading = true;
            this.edPFailed = false;
            try {
                const res = await api.get('/admin/people/search', { params: { q }, silent: true });
                if (seq !== this.edPSeq) return;
                this.edPResults = (Array.isArray(res?.data) ? res.data : []).slice(0, 20);
                this.edPSearched = true;
            } catch (err) {
                if (seq !== this.edPSeq) return;
                this.edPResults = [];
                if (err?.status === 403) {
                    this.edPBlocked = true;
                    this.edMode = 'new';
                } else {
                    this.edPFailed = true;
                }
            } finally {
                if (seq === this.edPSeq) this.edPLoading = false;
                icons();
            }
        },
        edPLine(p) {
            if (!p) return '';
            return [p.sex, p.age !== null && p.age !== undefined ? p.age + ' yrs' : '', p.barangay ? 'Brgy. ' + p.barangay : '', p.account_no || p.individual_ref].filter(Boolean).join(' · ');
        },
        /** Someone already on the list: the officer is told rather than adding them twice. */
        edListed(p) {
            return this.edRows.find((r) => !cancelled(r) && ((p.account_no && r.account_no === p.account_no) || (p.individual_ref && r.individual_ref === p.individual_ref))) || null;
        },
        edAddProblem() {
            if (this.edMode === 'new') {
                if (this.edNew.name.trim().length < 3) return 'Write the person’s full name.';
                if (!this.edNew.barangay) return 'Choose the person’s barangay.';
                return '';
            }
            if (!this.edPerson) return 'Find the person, or choose Not in the records.';
            return this.edListed(this.edPerson) ? 'Already on the list.' : '';
        },
        async edAddSave() {
            if (this.edBusy || this.edAddProblem()) return;
            const p = this.edPerson;
            const body = this.edMode === 'new'
                ? { name: this.edNew.name.trim(), barangay: this.edNew.barangay, contact: this.edNew.contact.trim() || null }
                : (p.account_no ? { account_no: p.account_no } : { individual_ref: p.individual_ref });
            const mark = this.edMark && this.edDayCome();
            this.edBusy = true;
            this.edError = '';
            try {
                const res = await api.post('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/registrations', body);
                const row = res?.data || {};
                if (mark && row.id !== undefined && row.attended !== true) {
                    await api.post('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/attendance', { registrations: [{ id: row.id, attended: true }] });
                }
                const who = row.name || body.name || p?.name || 'The person';
                toast(who + (mark ? ' is added and checked in.' : ' is added to the list.') + (row.code ? ' Code ' + showCode(row.code) + '.' : ''));
                this.edPanel = null;
                this.edChanged = true;
                await this.edLoad();
            } catch (err) {
                this.edError = err?.code === 'EVENT_FULL'
                    ? 'The event is full. Raise its capacity in Edit to add more people.'
                    : says(err, 'Could not add this person. Try again.');
            } finally {
                this.edBusy = false;
            }
        },

        // ---- Certificates
        edCertOn() { return !!this.edEvent?.certificate?.enabled; },
        edIssueCount() { return this.edRows.filter((r) => !cancelled(r) && r._attended && !r.certificate).length; },
        /** '' when certificates can be issued now, else why not. */
        edIssueBlock() {
            const e = this.edEvent;
            if (!e) return '';
            if (!this.edCertOn()) return 'Certificates are off for this event. Turn them on in its form (Edit, then Certificate).';
            if (!this.edDayCome()) return 'Certificates can be issued from the day of the event, ' + day(e.date) + '.';
            if (!this.edTotals().attended) return 'Mark who attended first: check them in by code or tick them in the list.';
            if (!this.edIssueCount()) return 'Everyone marked as attended has a certificate.';
            return '';
        },
        edAskIssue() {
            if (this.edIssueBlock()) return;
            this.edPanel = 'issue';
            this.edResult = '';
            icons();
        },
        async edIssue() {
            if (this.edBusy || this.edIssueBlock()) return;
            if (!(await this.edSaveAttendance(true))) return;
            this.edBusy = true;
            this.edError = '';
            try {
                const res = await api.post('/admin/events/' + encodeURIComponent(this.edEvent.key) + '/certificates');
                const n = res?.data?.issued ?? 0;
                const m = res?.data?.total_attended;
                this.edResult = (n === 1 ? '1 certificate' : n + ' certificates') + ' issued' + (m !== undefined ? ' (' + m + ' attended in all)' : '') + '. Residents with an account are told; open a certificate here to print it for the others.';
                this.edEvent.certificates_issued_at = this.edEvent.certificates_issued_at || new Date().toISOString();
                this.edPanel = null;
                this.edChanged = true;
                toast(n === 1 ? '1 certificate issued.' : n + ' certificates issued.');
                await this.edLoad();
            } catch (err) {
                this.edError = says(err, 'Could not issue the certificates. Try again.');
            } finally {
                this.edBusy = false;
            }
        },
        /**
         * An attendee's certificate in the shared certificate window. The
         * document's own field_values when the row carries them (what was
         * true when it was issued); otherwise drawn from the event's
         * certificate settings and the person's name, with the real control
         * number and Verify link.
         */
        edCert(r) {
            const c = r.certificate || {};
            const e = this.edEvent;
            const cfg = e.certificate || {};
            const title = e.title || e.name || '';
            const fields = {
                cert_title: cfg.title || 'Certificate of Participation',
                cert_subtitle: title,
                applicant: r.name,
                event_key: e.key,
                event_date: e.date,
                venue: e.venue || '',
                signatories: cfg.signatories || [],
            };
            if (cfg.body) {
                fields.body = cfg.body
                    .replace(/:names?\b/g, r.name || '')
                    .replace(/:event\b/g, title)
                    .replace(/:date\b/g, longDate(e.date))
                    .replace(/:venue\b/g, e.venue || 'Esperanza, Masbate');
            }
            const doc = c.document || {
                control_number: c.control_number,
                verify_url: c.verify_url,
                serial: c.verification_code || c.serial,
                issued_on: c.issued_on,
                revoked: !!c.revoked,
                document_type: 'event_certificate',
                field_values: c.field_values || fields,
            };
            openWelfareCertificate(doc, { source: 'event', title: fields.cert_title, event_title: title, holder_names: [r.name], session: { date: e.date, venue: e.venue } });
        },

        // ---- The list as a file
        edCsv() {
            const e = this.edEvent;
            const withContact = this.edRows.some((r) => r.contact);
            const head = ['Code', 'Name', 'Barangay', 'Account No.', ...(withContact ? ['Contact'] : []), 'How', 'Registered', 'Status', 'Checked in', 'Certificate No.'];
            const rows = [...this.edRows].sort((a, b) => (a.name || '').localeCompare(b.name || '')).map((r) => [
                showCode(r.code),
                r.name,
                r.barangay || '',
                r.account_no || '',
                ...(withContact ? [r.contact || ''] : []),
                SOURCES[r.source] || r.source || '',
                stamp(r.registered_at, true),
                cancelled(r) ? 'Cancelled' : (r.attended === true ? 'Attended' : (r.attended === false ? 'Did not attend' : 'Registered')),
                stamp(r.checked_in_at, true),
                r.certificate?.control_number || '',
            ]);
            downloadCsv(slug(e.title || e.name) + '-' + String(e.date || '').slice(0, 10) + '-registrations.csv', [head, ...rows]);
        },

        /** The event's own form, for registration or certificate settings. */
        edEdit() {
            const key = this.edEvent?.key;
            this.edOpen = false;
            window.dispatchEvent(new CustomEvent('events-edit', { detail: key }));
        },
    };
}

export function register(Alpine) {
    registerCertificate(Alpine);
    Alpine.data('eventDesk', eventDesk);
}
