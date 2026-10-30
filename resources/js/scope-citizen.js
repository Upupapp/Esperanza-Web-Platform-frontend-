// Scope round 2026-10-15 - Citizen Web screens: the service list, appointments, the public Verify page, support conversations, request tracking extras.
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.

import api from './api/client';
import { registerBalitaMedia } from './balita-media';
import { scanId, telHref, isPwd, revealProblem, REVEAL_REASONS, GUARDIAN_SOURCES } from './guardian-scan';

// ------------------------------------------------------------------ Shared

const TZ = 'Asia/Manila';
const isEn = () => window.Alpine?.store('lang')?.current === 'en';
const tr = (en, fil) => (isEn() ? en : fil);
const locale = () => (isEn() ? 'en-US' : 'fil-PH');
const icons = () => window.Alpine?.nextTick(() => window.renderIcons?.());

// On window, not $dispatch: the button that asked may be gone by the time the
// answer comes back (an x-if the action itself closed).
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

function errorText(err, en, fil) {
    return err && typeof err.localised === 'function' ? err.localised(isEn() ? 'en' : 'fil') : tr(en, fil);
}

// A viewer's own conveniences (an FAQ vote, a survey answered). Storage can be
// off in a private window; the page works without it, the server still counts.
const memory = {
    read(key) {
        try {
            return JSON.parse(localStorage.getItem(key) || '{}') || {};
        } catch {
            return {};
        }
    },
    put(key, name, value) {
        const all = memory.read(key);
        all[name] = value;
        try {
            localStorage.setItem(key, JSON.stringify(all));
        } catch {
            // Remembered for this visit only.
        }
    },
};

/** Today in Esperanza (UTC+8 all year), YYYY-MM-DD; `days` later when given. */
function manilaDay(days = 0) {
    return new Date(Date.now() + 8 * 3600000 + days * 86400000).toISOString().slice(0, 10);
}

/** 'Oct 15, 2026'. A bare YYYY-MM-DD is that day in Esperanza, not UTC midnight. */
function fmtDate(value, extra = {}) {
    if (!value) return '';
    const bare = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value));
    const d = bare ? new Date(Date.UTC(+bare[1], +bare[2] - 1, +bare[3], 12)) : new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString(locale(), { month: 'short', day: 'numeric', year: 'numeric', ...extra, timeZone: bare ? 'UTC' : TZ });
}

/** 'Oct 15, 2026, 9:30 AM', Esperanza time. */
function fmtDateTime(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString(locale(), { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', timeZone: TZ });
}

/** '13:30' -> '1:30 PM'. */
function clock(hhmm) {
    const m = /^(\d{1,2}):(\d{2})/.exec(String(hhmm || ''));
    if (!m) return hhmm || '';
    const h = Number(m[1]);
    return (h % 12 || 12) + ':' + m[2] + (h < 12 ? ' AM' : ' PM');
}

function peso(value) {
    if (value === null || value === undefined || value === '') return '';
    const n = Number(value);
    return Number.isNaN(n) ? String(value) : '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** The text of a catalog list item, which staff may have saved as a string or an object. */
const itemText = (v) => (typeof v === 'string' ? v : (v && (v.label || v.name || v.title || v.text || v.step)) || '');

/**
 * A tel: link for a contact as staff type it - '(056) 123-4567',
 * '0917 123 4567 / 0998 765 4321', 'Globe: 0917-123-4567', '911'. Only the
 * first number is dialled, as digits and a leading +; null when the text holds
 * no number, so the page hides the call button rather than show a dead one
 * (the app's phone_dial.dart).
 */
function dialHref(contact) {
    if (!contact) return null;
    const first = String(contact).split(/[/,;]|\bor\b/).map((s) => s.trim()).find((s) => /\d/.test(s)) || '';
    const digits = first.replace(/[^\d+]/g, '');
    const number = digits.startsWith('+') ? '+' + digits.slice(1).replace(/\+/g, '') : digits.replace(/\+/g, '');
    return number.replace('+', '').length < 3 ? null : 'tel:' + number;
}

/**
 * The fee, processing time, requirements and steps the server publishes
 * (GET /services, CatalogController::serialize) laid over the page's own
 * catalogue entries, matched by service key. The page keeps what the server
 * does not send (form fields, certificate wording). Requirements follow the
 * server's list because that list is what files the request: the server
 * makes one row per entry, keyed req_1, req_2... in that order.
 */
function applyServiceCatalog(entries, services, addEntry = null) {
    const byKey = {};
    (services || []).forEach((sv) => { if (sv && sv.key) byKey[sv.key] = sv; });
    (entries || []).forEach((entry) => {
        const sv = entry && entry.key ? byKey[entry.key] : null;
        if (!sv) return;
        if (sv.fee && sv.fee.display) entry.fee = sv.fee.display;
        if (sv.days) entry.days = sv.days;
        if (Array.isArray(sv.requirements) && sv.version !== null && sv.version !== undefined) {
            // requirement_rules carries each requirement's condition (showIf);
            // requirements stays plain text for older clients (2026-10-26).
            entry.requirementRules = window.requirementRules(Array.isArray(sv.requirement_rules) ? sv.requirement_rules : sv.requirements);
            entry.requirements = entry.requirementRules.map((r) => r.label);
        }
        const steps = (sv.process || []).map(itemText).filter(Boolean);
        if (steps.length) entry.process = steps;
        if (sv.form_spec && sv.form_spec.entitlement && 'amount' in entry) entry.amount = sv.form_spec.entitlement;
        // Since 2026-10-17 staff edit the form's questions, the name and the
        // category in the Service Catalog: the server's are the ones asked.
        if (Array.isArray(sv.fields) && sv.version !== null && sv.version !== undefined) entry.fields = JSON.parse(JSON.stringify(sv.fields));
        if (sv.name) entry.name = sv.name;
        if (sv.category) entry.category = sv.category;
        if (Array.isArray(sv.workflow)) entry.workflow = sv.workflow;
    });
    // A service added in the catalog after this site was built: listed from
    // the server alone (addEntry shapes it for the page; null skips it).
    if (typeof addEntry === 'function') {
        const known = new Set((entries || []).map((e) => e && e.key));
        Object.values(byKey).forEach((sv) => {
            if (known.has(sv.key)) return;
            const entry = addEntry(sv);
            if (!entry) return;
            entry.fields = JSON.parse(JSON.stringify(sv.fields || []));
            entry.requirementRules = window.requirementRules(Array.isArray(sv.requirement_rules) ? sv.requirement_rules : (sv.requirements || []));
            entry.requirements = entry.requirementRules.map((r) => r.label);
            entry.process = (sv.process || []).map(itemText).filter(Boolean);
            entry.workflow = Array.isArray(sv.workflow) ? sv.workflow : null;
            entries.push(entry);
        });
    }
    return byKey;
}

// Status chips, by name, for both request lifecycles, tickets and
// appointments. Never colour alone: every chip also prints the name.
const TONES = {
    blue: [['Submitted', 'Initial Screening', 'Booked'], 'bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
    indigo: [['Under Verification', 'Assessment'], 'bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
    orange: [['Under Review', 'Returned for Reassessment'], 'bg-orange-50 text-orange-700 ring-orange-200', 'bg-orange-500'],
    amber: [['Needs Requirements', 'For Approval', 'Pending Review'], 'bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
    sky: [['Resubmitted'], 'bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
    emerald: [['Approved', 'Confirmed'], 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
    purple: [['Assistance Processing', 'Processing'], 'bg-purple-50 text-purple-700 ring-purple-200', 'bg-purple-500'],
    teal: [['Mark to Release', 'Ready for Release'], 'bg-teal-50 text-teal-700 ring-teal-200', 'bg-teal-500'],
    cyan: [['Released'], 'bg-cyan-50 text-cyan-700 ring-cyan-200', 'bg-cyan-500'],
    green: [['Completed'], 'bg-green-50 text-green-700 ring-green-200', 'bg-green-500'],
    rose: [['Rejected', 'No Show'], 'bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
    slate: [['Draft', 'Cancelled', 'Archived', 'Closed', 'Withdrawn'], 'bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'],
};
// A status no list here knows is flagged, not greyed as if it were Draft
// (2026-10-17): a dashed amber chip.
const UNKNOWN_TONE = ['bg-amber-50 text-amber-800 ring-amber-400 border border-dashed border-amber-400', 'bg-amber-500'];
function tone(status) {
    const hit = Object.values(TONES).find(([names]) => names.includes(status));
    return hit ? [hit[1], hit[2]] : (status ? UNKNOWN_TONE : TONES.slate.slice(1));
}

// The resident's own requests, read once per page for the pickers.
let requestsPromise = null;
function myRequests() {
    if (!requestsPromise) {
        requestsPromise = api.getAll('/citizen/requests', { params: { sort: '-submitted_at' } })
            .then((res) => res.data || [])
            .catch((err) => {
                requestsPromise = null;
                throw err;
            });
    }
    return requestsPromise;
}
const requestLabel = (r) => [r.ref, r.service, r.status].filter(Boolean).join(' · ');

// ------------------------------------------------------- Calendar files

const pad = (n) => String(n).padStart(2, '0');
const utcStamp = (d) => d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
const icsText = (s) => String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\r?\n/g, '\\n');

// RFC 5545 3.1: a line longer than 75 octets continues on the next line after
// one space. Counted in UTF-8 bytes, never splitting a character.
function foldIcs(line) {
    const enc = new TextEncoder();
    if (enc.encode(line).length <= 75) return line;
    const out = [];
    let cur = '';
    let bytes = 0;
    for (const ch of line) {
        const b = enc.encode(ch).length;
        if (bytes + b > (out.length ? 74 : 75)) {
            out.push(cur);
            cur = '';
            bytes = 0;
        }
        cur += ch;
        bytes += b;
    }
    out.push(cur);
    return out.join('\r\n ');
}

/**
 * The start and end, in minutes after midnight, of a free-text time:
 * '08:00', '8:00 AM - 5:00 PM', '9 am', '13:00-14:30'. Null when it has none
 * (the event is then all day). One hour when only a start is given.
 */
function timeSpan(text) {
    const re = /(\d{1,2})(?::(\d{2}))?\s*([ap])\.?\s*m\b\.?|(\d{1,2}):(\d{2})/gi;
    const found = [];
    let m;
    while ((m = re.exec(String(text || ''))) && found.length < 2) {
        found.push(m[3] ? { h: +m[1], min: +(m[2] || 0), mer: m[3].toLowerCase() } : { h: +m[4], min: +m[5], mer: null });
    }
    if (!found.length) return null;
    const [a, b] = found;
    const minutes = (t) => {
        let h = t.h % 24;
        if (t.mer === 'p' && h < 12) h += 12;
        if (t.mer === 'a' && h === 12) h = 0;
        return h * 60 + Math.min(t.min, 59);
    };
    // '8:00 - 11:00 AM': the first time takes the second's AM/PM, unless that
    // puts it after the end ('11:00 - 1:00 PM').
    if (b && !a.mer && b.mer) {
        a.mer = b.mer;
        if (minutes(a) > minutes(b)) a.mer = 'a';
    }
    const start = minutes(a);
    let end = b ? minutes(b) : start + 60;
    if (end <= start) end = b ? end + 1440 : start + 60;
    return { start, end };
}

/** Start and end as iCalendar values: UTC 'Z' times, or dates for an all-day event. */
function calendarWindow(date, time) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(date || ''));
    if (!m) return null;
    const [y, mo, d] = [+m[1], +m[2], +m[3]];
    const span = timeSpan(time);
    if (!span) {
        const next = new Date(Date.UTC(y, mo - 1, d + 1));
        return { allDay: true, start: '' + y + pad(mo) + pad(d), end: next.toISOString().slice(0, 10).replace(/-/g, '') };
    }
    // Esperanza is UTC+8 with no daylight saving: 8:00 there is 00:00Z.
    const at = (mins) => utcStamp(new Date(Date.UTC(y, mo - 1, d, 0, mins - 480)));
    return { allDay: false, start: at(span.start), end: at(span.end) };
}

/** A one-event calendar file with a reminder the day before. */
function buildIcs(ev) {
    const when = calendarWindow(ev.date, ev.time);
    if (!when) return null;
    const lines = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//LGU Esperanza//Citizen Portal//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' + ev.uid,
        'DTSTAMP:' + utcStamp(new Date()),
        (when.allDay ? 'DTSTART;VALUE=DATE:' : 'DTSTART:') + when.start,
        (when.allDay ? 'DTEND;VALUE=DATE:' : 'DTEND:') + when.end,
        'SUMMARY:' + icsText(ev.title),
    ];
    if (ev.place) lines.push('LOCATION:' + icsText(ev.place));
    if (ev.description) lines.push('DESCRIPTION:' + icsText(ev.description));
    lines.push('BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' + icsText(ev.title), 'TRIGGER:-P1D', 'END:VALARM', 'END:VEVENT', 'END:VCALENDAR');
    return lines.map(foldIcs).join('\r\n') + '\r\n';
}

function googleCalendarUrl(ev) {
    const when = calendarWindow(ev.date, ev.time);
    if (!when) return '#';
    const params = new URLSearchParams({ action: 'TEMPLATE', text: ev.title || '', dates: when.start + '/' + when.end, ctz: TZ });
    if (ev.description) params.set('details', ev.description);
    if (ev.place) params.set('location', ev.place);
    return 'https://calendar.google.com/calendar/render?' + params.toString();
}

const slug = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60) || 'event';

function saveText(text, name, type) {
    const url = URL.createObjectURL(new Blob([text], { type }));
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 30000);
}

function downloadIcs(ev, name) {
    const ics = buildIcs(ev);
    if (!ics) {
        toast(tr('This date could not be read, so no calendar file was made.', 'Hindi mabasa ang petsa, kaya walang nagawang calendar file.'), 'error');
        return false;
    }
    saveText(ics, name, 'text/calendar;charset=utf-8');
    return true;
}

// ------------------------------------------------------------ Printing

// The site's print isolation (body.dp-printing and #dp-print-area in app.css)
// shows exactly one #dp-print-area. A Dokyu page keeps its certificate's area
// in the page while a request is open, so that one steps aside while a slip
// or notice prints, and comes back after.
function takePrintArea(sheet) {
    const others = [...document.querySelectorAll('#dp-print-area')].filter((n) => n !== sheet);
    others.forEach((n) => { n.id = 'dp-print-area-parked'; });
    const own = sheet.id;
    sheet.id = 'dp-print-area';
    document.body.classList.add('dp-printing');
    return () => {
        document.body.classList.remove('dp-printing');
        sheet.id = own;
        others.forEach((n) => { n.id = 'dp-print-area'; });
    };
}

function printSheet(sheet) {
    if (!sheet) return;
    const restore = takePrintArea(sheet);
    const done = () => {
        window.removeEventListener('afterprint', done);
        restore();
    };
    window.addEventListener('afterprint', done);
    window.print();
}

// Download PDF, as the Dokyu document's own download does it (html2pdf on a
// copy without Alpine, the oklch() colours swapped for rgb() meanwhile).
async function downloadSheet(sheet, filename) {
    if (!sheet) return;
    let html2pdf;
    try {
        html2pdf = await window.loadHtml2Pdf();
    } catch {
        toast(tr('Could not load the PDF generator. Check your connection and try again.', 'Hindi ma-load ang gumagawa ng PDF. Suriin ang koneksyon at subukang muli.'), 'error');
        return;
    }
    const editor = window.DokyuEditor || {};
    const restore = takePrintArea(sheet);
    let restoreColors = () => {};
    try {
        await new Promise((resolve) => requestAnimationFrame(() => resolve()));
        if (editor.waitForImages) await editor.waitForImages(sheet);
        if (editor.sanitizeExportColors) restoreColors = editor.sanitizeExportColors(document.body);
        await html2pdf().set({
            margin: 0,
            filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'], avoid: ['.doc-paper'] },
        }).from(editor.exportClone ? editor.exportClone(sheet) : sheet).save();
    } catch {
        toast(tr('Could not download the PDF. Use Print and choose Save as PDF.', 'Hindi ma-download ang PDF. Gamitin ang I-print at piliin ang Save as PDF.'), 'error');
    } finally {
        restoreColors();
        restore();
    }
}

// =========================================================== Appointments

const APPOINTMENT_ERRORS = {
    ACCOUNT_NOT_VERIFIED: ['Your account must be verified before you book an appointment.', 'Kailangang beripikado ang iyong account bago mag-book ng appointment.'],
    SLOT_FULL: ['That time was just taken. Please choose another time.', 'Kakakuha lang ng iba ang oras na iyan. Pumili ng ibang oras.'],
    ALREADY_BOOKED: ['You already have this time booked.', 'May booking ka na sa oras na ito.'],
    TOO_MANY_APPOINTMENTS: ['You can hold up to three upcoming appointments. Cancel one first.', 'Hanggang tatlong paparating na appointment lang ang maaari. Kanselahin muna ang isa.'],
    NOT_YOUR_REQUEST: ['That request reference is not one of yours.', 'Hindi mo kahilingan ang reference number na iyan.'],
    SLOT_NOT_OFFERED: ['That time is no longer open for booking. Please choose another.', 'Hindi na bukas ang oras na iyan. Pumili ng iba.'],
    NOT_CANCELLABLE: ['This appointment can no longer be cancelled.', 'Hindi na maaaring kanselahin ang appointment na ito.'],
    ALREADY_STARTED: ['The appointment time has passed, so it can no longer be cancelled.', 'Lumipas na ang oras ng appointment, kaya hindi na ito makakansela.'],
};
function appointmentError(err, en, fil) {
    const known = err && APPOINTMENT_ERRORS[err.code];
    return known ? tr(known[0], known[1]) : errorText(err, en, fil);
}

const WEEKDAYS = { 1: ['Mon', 'Lun'], 2: ['Tue', 'Mar'], 3: ['Wed', 'Miy'], 4: ['Thu', 'Huw'], 5: ['Fri', 'Biy'], 6: ['Sat', 'Sab'], 7: ['Sun', 'Lin'] };

/** [1,2,3,4,5] -> 'Mon–Fri'; a run of three or more days reads as a range. */
function weekdaysLabel(days) {
    const list = [...new Set((Array.isArray(days) ? days : []).map(Number).filter((d) => d >= 1 && d <= 7))].sort((a, b) => a - b);
    if (!list.length) return '';
    const name = (d) => WEEKDAYS[d][isEn() ? 0 : 1];
    const runs = [];
    let start = list[0];
    let prev = list[0];
    for (const d of [...list.slice(1), null]) {
        if (d === prev + 1) {
            prev = d;
            continue;
        }
        if (prev - start >= 2) runs.push(name(start) + '–' + name(prev));
        else for (let x = start; x <= prev; x++) runs.push(name(x));
        start = d;
        prev = d;
    }
    return runs.join(', ');
}

const ACTIVE_APPOINTMENT = ['Booked', 'Confirmed'];

function citizenAppointments(links = {}) {
    return {
        links,
        schedules: [],
        schedulesLoading: true,
        schedulesFailure: null,
        mine: [],
        mineLoading: true,
        mineFailure: null,
        requests: [],
        scheduleQuery: '',
        scheduleId: null,
        days: [],
        daysLoading: false,
        daysFailure: null,
        date: null,
        start: null,
        purpose: '',
        requestRef: '',
        booking: false,
        bookFailure: null,
        booked: null,
        listTab: 'upcoming',
        cancelRef: null,
        cancelReason: '',
        cancelling: false,
        cancelFailure: null,
        // Why a request in the link (?request=) did not pick a schedule.
        hint: null,

        async init() {
            const wanted = (new URLSearchParams(window.location.search).get('request') || '').trim().toUpperCase();
            this.loadMine();
            await Promise.all([this.loadSchedules(), this.loadRequests()]);
            if (wanted) this.preselect(wanted);
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        tone,
        fmtDate,
        clock,

        // Booking takes an account the LGU has approved (AppointmentsController::store).
        get verified() {
            const a = this.$store.citizenSession.account;
            return !a || !a.status || a.status === 'Approved';
        },

        async loadSchedules() {
            this.schedulesLoading = true;
            this.schedulesFailure = null;
            try {
                const res = await api.get('/citizen/appointments/schedules');
                this.schedules = res.data || [];
            } catch (err) {
                this.schedulesFailure = err;
            } finally {
                this.schedulesLoading = false;
                icons();
            }
        },
        async loadMine(quiet = false) {
            if (!quiet) this.mineLoading = true;
            this.mineFailure = null;
            try {
                const res = await api.get('/citizen/appointments');
                this.mine = res.data || [];
            } catch (err) {
                if (!quiet) this.mineFailure = err;
            } finally {
                this.mineLoading = false;
                icons();
            }
        },
        async loadRequests() {
            try {
                this.requests = await myRequests();
            } catch {
                this.requests = [];
            }
        },
        get schedulesError() { return errorText(this.schedulesFailure, 'Could not load the offices taking appointments.', 'Hindi ma-load ang mga opisinang tumatanggap ng appointment.'); },
        get mineError() { return errorText(this.mineFailure, 'Could not load your appointments.', 'Hindi ma-load ang iyong mga appointment.'); },
        get daysError() { return errorText(this.daysFailure, 'Could not load the open days.', 'Hindi ma-load ang mga bukas na araw.'); },
        get bookError() { return this.bookFailure ? appointmentError(this.bookFailure, 'Could not book the appointment.', 'Hindi ma-book ang appointment.') : ''; },
        get cancelError() { return this.cancelFailure ? appointmentError(this.cancelFailure, 'Could not cancel the appointment.', 'Hindi makansela ang appointment.') : ''; },
        get hintText() {
            if (!this.hint) return '';
            return this.hint.kind === 'not-mine'
                ? this.t('Request ' + this.hint.ref + ' is not among your requests.', 'Wala sa iyong mga kahilingan ang ' + this.hint.ref + '.')
                : this.t('No office schedule is open for request ' + this.hint.ref + ' yet. You can choose another office below.', 'Wala pang bukas na iskedyul para sa kahilingang ' + this.hint.ref + '. Maaari kang pumili ng ibang opisina sa ibaba.');
        },

        // -- Step 1: the office's schedule
        place(s) { return s ? (s.place || s.office_short || s.office || '') : ''; },
        hours(s) { return s ? clock(s.start_time) + ' – ' + clock(s.end_time) : ''; },
        weekdays(s) { return s ? weekdaysLabel(s.weekdays) : ''; },
        get filteredSchedules() {
            const words = this.scheduleQuery.trim().toLowerCase().split(/\s+/).filter(Boolean);
            return this.schedules.filter((s) => {
                const hay = [s.place, s.office, s.office_short, s.label, s.service, s.location, s.barangay].join(' ').toLowerCase();
                return words.every((w) => hay.includes(w));
            });
        },
        get schedule() { return this.schedules.find((s) => s.id === this.scheduleId) || null; },
        selectSchedule(s) {
            if (!s) return;
            this.booked = null;
            this.bookFailure = null;
            if (this.scheduleId === s.id && this.days.length) return;
            this.scheduleId = s.id;
            this.date = null;
            this.start = null;
            this.days = [];
            this.loadDays();
        },

        // -- Step 2: a day and a free slot
        async loadDays(keepDay = false) {
            const s = this.schedule;
            if (!s) return;
            const id = s.id;
            this.daysLoading = true;
            this.daysFailure = null;
            try {
                const ahead = Math.max(1, Math.min(Number(s.book_days_ahead) || 14, 62));
                const res = await api.get('/citizen/appointments/schedules/' + id + '/availability', { params: { from: manilaDay(0), to: manilaDay(ahead) } });
                if (this.scheduleId !== id) return;
                this.days = res.data?.days || [];
                const slotStillOpen = this.day?.slots?.some((sl) => sl.start === this.start && sl.left > 0);
                if (!slotStillOpen) this.start = null;
                if (!keepDay || !this.day || this.dayState(this.day) !== 'open') {
                    const first = this.days.find((d) => this.dayState(d) === 'open');
                    this.date = first ? first.date : null;
                }
            } catch (err) {
                if (this.scheduleId !== id) return;
                this.days = [];
                this.daysFailure = err;
            } finally {
                if (this.scheduleId === id) this.daysLoading = false;
                icons();
            }
        },
        get day() { return this.days.find((d) => d.date === this.date) || null; },
        openSlots(d) { return d && !d.closed ? (d.slots || []).filter((sl) => sl.left > 0).length : 0; },
        dayState(d) {
            if (!d) return 'none';
            if (d.closed) return 'closed';
            if (!(d.slots || []).length) return 'none';
            return this.openSlots(d) ? 'open' : 'full';
        },
        dayNote(d) {
            const state = this.dayState(d);
            if (state === 'closed') return this.t('Closed', 'Sarado');
            if (state === 'open') return this.openSlots(d) + ' ' + this.t('open', 'bakante');
            // Today's slots that have already started are not offered.
            return state === 'none' ? this.t('None left', 'Wala na') : this.t('Full', 'Puno');
        },
        weekdayShort(d) { return fmtDate(d.date, { weekday: 'short', year: undefined, month: undefined, day: undefined }); },
        dayShort(d) { return fmtDate(d.date, { year: undefined }); },
        pickDay(d) {
            if (this.dayState(d) !== 'open') return;
            this.date = d.date;
            this.start = null;
            this.bookFailure = null;
        },
        pickSlot(sl) {
            if (!sl || sl.left < 1) return;
            this.start = sl.start;
            this.bookFailure = null;
        },
        slotNote(sl) { return sl.left > 0 ? sl.left + ' ' + this.t('left', 'natitira') : this.t('Full', 'Puno'); },
        get slot() { return this.day?.slots?.find((sl) => sl.start === this.start) || null; },

        // -- Step 3: the details, then confirm
        get purposeLength() { return this.purpose.trim().length; },
        get linkedRequest() { return this.requests.find((r) => r.ref === this.requestRef) || null; },
        // A request that was turned down or withdrawn has nothing left to visit for.
        get requestOptions() {
            return this.requests.filter((r) => !['Rejected', 'Cancelled', 'Archived'].includes(r.status) || r.ref === this.requestRef);
        },
        requestLabel,
        get ready() { return !!(this.schedule && this.date && this.slot && this.slot.left > 0 && this.purposeLength >= 3 && this.purposeLength <= 500 && this.verified); },
        get whenChosen() {
            if (!this.day || !this.slot) return '';
            return fmtDate(this.day.date, { weekday: 'long' }) + ', ' + clock(this.slot.start) + ' – ' + clock(this.slot.end);
        },
        async book() {
            if (!this.ready || this.booking) return;
            this.booking = true;
            this.bookFailure = null;
            const s = this.schedule;
            try {
                const res = await api.post('/citizen/appointments', {
                    schedule_id: s.id,
                    date: this.date,
                    start_time: this.start,
                    purpose: this.purpose.trim(),
                    request_ref: this.requestRef || null,
                });
                const row = res.data;
                this.booked = row;
                this.mine = [row, ...this.mine.filter((a) => a.ref !== row.ref)];
                this.listTab = 'upcoming';
                this.purpose = '';
                this.requestRef = '';
                this.start = null;
                toast(this.t('Appointment ' + row.ref + ' booked.', 'Naka-book ang appointment ' + row.ref + '.'));
                this.loadDays(true);
            } catch (err) {
                this.bookFailure = err;
                // A 403 already has its red toast (the API client's own).
                if (err?.status !== 403) toast(this.bookError, 'error');
                if (['SLOT_FULL', 'SLOT_NOT_OFFERED'].includes(err?.code)) {
                    this.start = null;
                    this.loadDays(true);
                }
            } finally {
                this.booking = false;
                icons();
            }
        },
        bookAnother() {
            this.booked = null;
            this.$nextTick(() => document.getElementById('appointment-booking')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },

        // A request in the link: link it, and pick its office's schedule when there is one.
        preselect(ref) {
            const req = this.requests.find((r) => String(r.ref).toUpperCase() === ref);
            if (!req) {
                this.hint = { kind: 'not-mine', ref };
                return;
            }
            this.requestRef = req.ref;
            if (!this.purpose) this.purpose = this.t('About my request ', 'Tungkol sa aking kahilingan ') + req.ref + (req.service ? ' (' + req.service + ')' : '');
            const match = this.schedules.find((s) => s.service_key && s.service_key === req.service_key)
                || this.schedules.find((s) => s.office && s.office === req.office);
            if (match) this.selectSchedule(match);
            else this.hint = { kind: 'no-schedule', ref: req.ref };
            this.$nextTick(() => document.getElementById('appointment-booking')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },

        // -- The resident's appointments
        isUpcoming(a) { return ACTIVE_APPOINTMENT.includes(a.status) && String(a.date) >= manilaDay(0); },
        get upcoming() {
            return this.mine.filter((a) => this.isUpcoming(a)).sort((x, y) => (x.date + x.start_time).localeCompare(y.date + y.start_time));
        },
        get past() {
            return this.mine.filter((a) => !this.isUpcoming(a)).sort((x, y) => (y.date + y.start_time).localeCompare(x.date + x.start_time));
        },
        get shown() { return this.listTab === 'upcoming' ? this.upcoming : this.past; },
        when(a) { return fmtDate(a.date, { weekday: 'short' }) + ' · ' + clock(a.start_time) + (a.end_time ? ' – ' + clock(a.end_time) : ''); },
        requestHref(ref) { return (String(ref).toUpperCase().startsWith('TUL') ? this.links.tulong : this.links.dokyu) + '?ref=' + encodeURIComponent(ref); },
        cancelNote(a) {
            if (a.status !== 'Cancelled') return '';
            const by = a.cancelled_by === 'staff' ? this.t('Cancelled by the office', 'Kinansela ng opisina') : this.t('You cancelled it', 'Kinansela mo');
            return by + (a.cancel_reason ? ': ' + a.cancel_reason : '.');
        },
        statusNote(a) {
            if (a.status === 'Booked') return this.t('Waiting for the office to confirm.', 'Hinihintay ang kumpirmasyon ng opisina.');
            if (a.status === 'Confirmed') return this.t('Confirmed. Please come on time and bring your requirements.', 'Kumpirmado. Dumating sa oras at dalhin ang mga requirement.');
            if (a.status === 'No Show') return this.t('Marked as not attended.', 'Hindi nakadalo.');
            return '';
        },
        askCancel(a) {
            this.cancelRef = a.ref;
            this.cancelReason = '';
            this.cancelFailure = null;
        },
        async confirmCancel(a) {
            if (this.cancelling) return;
            this.cancelling = true;
            this.cancelFailure = null;
            try {
                const res = await api.post('/citizen/appointments/' + encodeURIComponent(a.ref) + '/cancel', { reason: this.cancelReason.trim() || null });
                this.mine = this.mine.map((x) => (x.ref === a.ref ? res.data : x));
                this.cancelRef = null;
                if (this.booked?.ref === a.ref) this.booked = null;
                toast(this.t('Appointment ' + a.ref + ' cancelled.', 'Nakansela ang appointment ' + a.ref + '.'));
                if (this.schedule) this.loadDays(true);
            } catch (err) {
                this.cancelFailure = err;
                if (err?.status !== 403) toast(this.cancelError, 'error');
                if (['NOT_CANCELLABLE', 'ALREADY_STARTED'].includes(err?.code)) this.loadMine(true);
            } finally {
                this.cancelling = false;
                icons();
            }
        },
        saveToCalendar(a) {
            const ok = downloadIcs({
                uid: a.ref + '@esperanza.gov.ph',
                title: 'Appointment ' + a.ref + ' – ' + (a.place || a.office || ''),
                date: a.date,
                time: a.start_time + (a.end_time ? '-' + a.end_time : ''),
                place: [a.location, a.place].filter(Boolean).join(', '),
                description: [a.purpose, a.schedule, a.request_ref ? 'Request ' + a.request_ref : null].filter(Boolean).join('\n'),
            }, 'appointment-' + a.ref + '.ics');
            if (ok) toast(this.t('Calendar file saved. Open it to add the appointment.', 'Na-save ang calendar file. Buksan ito para maidagdag ang appointment.'));
        },
    };
}

// ============================================================ Verify page

function verifyPage() {
    return {
        code: '',
        checked: '',
        state: 'idle',
        result: null,
        failure: null,
        typeName: '',
        // For an officer who is signed in on this browser: the officers'
        // scan of the same code (GET /admin/id-scan), with the holder and, for
        // a minor or a PWD, the guardian behind a stated reason (welfare
        // round 2026-10-29). Anyone else - a resident's token, no token, no
        // match - sees nothing of it, and nothing is said.
        officer: null,
        reasons: REVEAL_REASONS,
        ofRvOpen: false,
        ofRvReason: '',
        ofRvNote: '',
        ofRvBusy: false,
        ofRvError: '',

        init() {
            const params = new URLSearchParams(window.location.search);
            const code = (params.get('code') || params.get('token') || '').trim();
            if (code) {
                this.code = code;
                this.check();
            }
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        fmtDate,

        // A QR carries a 64-character token, not something to show back.
        get fromQr() { return /^[a-f0-9]{64}$/i.test(this.checked); },
        get found() { return ['valid', 'expired', 'revoked'].includes(this.state); },
        get documentName() {
            const r = this.result || {};
            return r.title || this.typeName || String(r.document_type || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        },
        get failureText() { return errorText(this.failure, 'Could not check the document right now.', 'Hindi masuri ang dokumento ngayon.'); },
        // The office and its barangay: "Barangay Hall · Brgy. Poblacion".
        get issuedBy() {
            const r = this.result || {};
            return [r.office, r.barangay ? 'Brgy. ' + r.barangay : null].filter(Boolean).join(' · ');
        },

        async check() {
            const value = this.code.trim().replace(/\s+/g, '');
            if (!value || this.state === 'loading') return;
            this.state = 'loading';
            this.result = null;
            this.failure = null;
            this.typeName = '';
            this.checked = value;
            this.officerCheck(value);
            try {
                const res = await api.get('/verify/' + encodeURIComponent(value));
                const r = res.data || {};
                this.result = r;
                // The server says revoked or not; expiry is the date on the document.
                this.state = r.revoked || r.authentic === false ? 'revoked' : (r.valid_until && String(r.valid_until) < manilaDay(0) ? 'expired' : 'valid');
                if (!r.title && r.document_type) {
                    api.get('/services/' + encodeURIComponent(r.document_type))
                        .then((s) => { if (this.result === r) this.typeName = s.data?.name || ''; })
                        .catch(() => {});
                }
            } catch (err) {
                this.failure = err;
                this.state = err?.status === 404 ? 'notfound' : (err?.status === 429 ? 'toomany' : 'error');
            }
            // The address keeps what was checked, so a refresh checks it again.
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('code', value);
                url.searchParams.delete('token');
                window.history.replaceState(null, '', url);
            } catch {
                // The check itself is done either way.
            }
            icons();
        },
        // -- For officers
        async officerCheck(value) {
            this.officer = null;
            this.ofReset();
            if (!api.getToken()) return;
            try {
                const res = await scanId(value, { silent: true });
                if (this.checked === value) this.officer = res.data || null;
            } catch {
                // 401, 403, 404, a limit: not an officer's question to answer here.
            }
            icons();
        },
        get ofHolder() { return this.officer?.holder || null; },
        get ofGuardian() { return this.officer?.guardian || null; },
        get ofPwd() { return isPwd(this.ofHolder); },
        get ofFacts() {
            const h = this.ofHolder || {};
            return [h.age != null ? h.age + this.t(' years old', ' taong gulang') : null, h.sex, h.barangay ? 'Brgy. ' + h.barangay : null].filter(Boolean).join(' · ');
        },
        ofTel: telHref,
        ofSource(key) { const s = GUARDIAN_SOURCES[key]; return s ? this.t(s.en, s.fil) : ''; },
        ofRecordHref(ref) { return '/admin/constituents?ref=' + encodeURIComponent(ref || ''); },
        ofReset() {
            this.ofRvOpen = false;
            this.ofRvReason = '';
            this.ofRvNote = '';
            this.ofRvError = '';
            this.ofRvBusy = false;
        },
        ofOpenReveal() {
            this.ofRvOpen = true;
            this.ofRvError = '';
            this.$nextTick(() => document.getElementById('verify-officer-reason')?.focus());
        },
        get ofRvProblem() { return revealProblem(this.ofRvReason, this.ofRvNote); },
        async ofReveal() {
            const value = this.checked;
            if (this.ofRvBusy || this.ofRvProblem || !value) return;
            this.ofRvBusy = true;
            this.ofRvError = '';
            try {
                const res = await scanId(value, { reveal: { reason: this.ofRvReason, note: this.ofRvNote }, silent: true });
                if (this.checked !== value) return;
                this.officer = res.data || this.officer;
                if (this.officer?.guardian) this.ofRvOpen = false;
                else if (!this.officer?.guardian_withheld) this.ofRvError = this.t('No guardian contact came back for this person.', 'Walang bumalik na contact ng guardian para sa taong ito.');
            } catch (err) {
                this.ofRvError = err?.status === 429
                    ? this.t('You have reached the limit of 30 guardian look-ups an hour. Try again later, or contact the MSWDO.', 'Naabot mo na ang limitasyong 30 pagtingin ng guardian sa isang oras. Subukan muli mamaya, o makipag-ugnayan sa MSWDO.')
                    : this.t('Could not get the guardian’s contact right now.', 'Hindi makuha ngayon ang contact ng guardian.');
            } finally {
                this.ofRvBusy = false;
                icons();
            }
        },
        another() {
            this.code = '';
            this.checked = '';
            this.state = 'idle';
            this.result = null;
            this.officer = null;
            this.ofReset();
            this.$nextTick(() => document.getElementById('verify-code')?.focus());
        },
    };
}

// =================================================== A change of name

// Why a verified resident's name changes, and the proof the barangay checks
// (the PSA's own list for updating a PhilSys record). Owner, 2026-10-16.
const NAME_REASONS = [
    { key: 'marriage', en: 'Marriage (taking my spouse\u2019s surname)', fil: 'Ikinasal (gagamitin ang apelyido ng asawa)', proofEn: 'PSA marriage certificate', proofFil: 'PSA marriage certificate' },
    { key: 'annulment', en: 'Annulment or nullity of marriage (back to my former surname)', fil: 'Annulment o pagpapawalang-bisa ng kasal (babalik sa dating apelyido)', proofEn: 'PSA marriage certificate with the annotation, or the court decision', proofFil: 'PSA marriage certificate na may annotation, o ang desisyon ng korte' },
    { key: 'court_order', en: 'Court order (a legal change of name)', fil: 'Utos ng korte (legal na pagpapalit ng pangalan)', proofEn: 'Certified copy of the court order or decision', proofFil: 'Sertipikadong kopya ng utos o desisyon ng korte' },
    { key: 'clerical', en: 'My name is misspelled (typo)', fil: 'Mali ang pagkakasulat ng pangalan ko (typo)', proofEn: 'PSA birth certificate, or a valid ID showing the right spelling', proofFil: 'PSA birth certificate, o valid ID na may tamang spelling' },
];
const OPEN_REQUEST = ['Submitted', 'Under Review'];

/**
 * A verified resident asks to change their name, with the reason and proof;
 * the barangay checks it and applies it (Constituents > To Verify > Data
 * requests). One request open at a time, no waiting period between changes,
 * and the old name stays in the history. POST /citizen/data-requests
 * (kind name_change, multipart).
 */
function nameChangeRequest() {
    return {
        ncOpen: false,
        ncReasons: NAME_REASONS,
        ncForm: { reason: '', firstName: '', middleName: '', lastName: '', note: '' },
        ncFile: null,
        ncSending: false,
        ncError: '',
        ncPending: null,

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        fmtDate,
        get ncReason() { return NAME_REASONS.find((r) => r.key === this.ncForm.reason) || null; },
        get ncProofHint() { return this.ncReason ? this.t(this.ncReason.proofEn, this.ncReason.proofFil) : this.t('Choose the reason first.', 'Piliin muna ang dahilan.'); },

        init() { this.ncLoad(); },

        // The request still waiting, if there is one: then no second.
        async ncLoad() {
            try {
                const res = await api.get('/citizen/data-requests');
                this.ncPending = (res.data || []).find((r) => r.kind === 'name_change' && OPEN_REQUEST.includes(r.status)) || null;
            } catch {
                // The form still works; the server refuses a second request.
            }
            icons();
        },

        ncStart() {
            const a = this.$store.citizenSession.account || {};
            this.ncForm = { reason: '', firstName: a.firstName || '', middleName: a.middleName || '', lastName: a.lastName || '', note: '' };
            this.ncFile = null;
            this.ncError = '';
            this.ncOpen = true;
            icons();
        },

        ncPick(file) {
            this.ncError = '';
            if (!file) return;
            if (!/^image\/|^application\/pdf$/.test(file.type || '')) {
                this.ncError = this.t('Attach a photo or a PDF of the document.', 'Mag-attach ng litrato o PDF ng dokumento.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.ncError = this.t('The file is larger than 10 MB. Take a smaller photo or scan.', 'Mas malaki sa 10 MB ang file. Kumuha ng mas maliit na litrato o scan.');
                return;
            }
            this.ncFile = file;
        },

        async ncSend() {
            if (this.ncSending) return;
            const f = this.ncForm;
            const a = this.$store.citizenSession.account || {};
            const first = f.firstName.trim();
            const middle = f.middleName.trim();
            const last = f.lastName.trim();
            if (!f.reason) { this.ncError = this.t('Choose why your name is changing.', 'Piliin kung bakit magbabago ang iyong pangalan.'); return; }
            if (!first || !last) { this.ncError = this.t('Enter your new first and last name.', 'Ilagay ang bagong pangalan at apelyido.'); return; }
            const now = [a.firstName, a.middleName, a.lastName].filter(Boolean).join(' ').toLowerCase();
            if ([first, middle, last].filter(Boolean).join(' ').toLowerCase() === now) { this.ncError = this.t('That is the name already on record.', 'Iyan na ang pangalang nasa record.'); return; }
            if (!this.ncFile) { this.ncError = this.t('Attach your proof: ', 'Mag-attach ng patunay: ') + this.ncProofHint; return; }
            this.ncSending = true;
            this.ncError = '';
            try {
                const body = new FormData();
                body.append('kind', 'name_change');
                body.append('reason', f.reason);
                body.append('first_name', first);
                if (middle) body.append('middle_name', middle);
                body.append('last_name', last);
                if (f.note.trim()) body.append('detail', f.note.trim());
                body.append('proof', this.ncFile);
                const res = await api.post('/citizen/data-requests', body);
                this.ncPending = res.data;
                this.ncOpen = false;
                toast(this.t('Name change request ' + res.data.ref + ' sent. Your barangay office will check your proof and let you know.', 'Naipadala ang kahilingan ' + res.data.ref + '. Susuriin ng iyong barangay ang iyong patunay at ipapaalam sa iyo.'));
            } catch (err) {
                // The form keeps what was typed.
                if (err?.code === 'NAME_CHANGE_PENDING') this.ncLoad();
                this.ncError = errorText(err, 'Could not send the request.', 'Hindi maipadala ang kahilingan.');
            } finally {
                this.ncSending = false;
                icons();
            }
        },
    };
}

// ======================================================== Help & feedback

const TICKET_CATEGORIES = {
    'Account & Login': 'Account at Pag-login',
    Dokyu: 'Dokyu',
    Tulong: 'Tulong',
    'Technical Issue': 'Teknikal na Problema',
    'App Problem': 'Problema sa App',
    Other: 'Iba pa',
};

/** The Support tab: a new ticket (optionally about a request), the list, and each ticket's conversation. */
function supportDesk() {
    return {
        categories: Object.keys(TICKET_CATEGORIES),
        form: { category: 'Account & Login', subject: '', message: '', requestRef: '' },
        file: null,
        attachOpen: false,
        submitting: false,
        requests: [],
        threadOpen: false,
        thread: null,
        threadLoading: false,
        threadFailure: null,
        reply: '',
        replying: false,
        replyFailure: null,
        closed: false,

        init() {
            myRequests().then((rows) => { this.requests = rows; }).catch(() => {});
            const ref = new URLSearchParams(window.location.search).get('ticket');
            if (ref) this.openThread(ref);
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        tone,
        fmtDateTime,
        requestLabel,
        categoryLabel(c) { return this.$store.lang.current === 'en' ? c : (TICKET_CATEGORIES[c] || c); },
        statusNote(status) {
            if (status === 'Completed') return this.t('Resolved by the office', 'Nalutas na ng opisina');
            if (status === 'Cancelled') return this.t('Closed', 'Sarado na');
            if (status === 'Under Review') return this.t('The office is looking into it', 'Sinusuri na ng opisina');
            return this.t('Waiting for the office', 'Hinihintay ang opisina');
        },

        pickFile(file) {
            this.file = file || null;
            this.attachOpen = false;
        },
        async submit() {
            const f = this.form;
            if (this.submitting || !f.subject.trim() || !f.message.trim()) return;
            this.submitting = true;
            try {
                const fields = { category: f.category, subject: f.subject.trim(), body: f.message.trim() };
                // A ticket about a request goes to that request's office.
                if (f.requestRef) fields.request_ref = f.requestRef;
                let payload = fields;
                if (this.file) {
                    payload = new FormData();
                    Object.entries(fields).forEach(([k, v]) => payload.append(k, v));
                    payload.append('attachment', this.file);
                }
                const res = await api.post('/citizen/support/tickets', payload);
                this.tickets.unshift(res.data);
                this.form = { category: this.categories[0], subject: '', message: '', requestRef: '' };
                this.file = null;
                toast(this.t('Support ticket submitted — our team will respond within 24 hours.', 'Naisumite ang support ticket — sasagutin ng aming team sa loob ng 24 oras.'));
            } catch (err) {
                toast(errorText(err, 'Could not submit ticket.', 'Hindi maisumite ang ticket.'), 'error');
            } finally {
                this.submitting = false;
            }
        },

        async openThread(target) {
            const ref = typeof target === 'string' ? target : target?.ref;
            if (!ref) return;
            if (this.thread?.ref !== ref) this.reply = '';
            this.thread = typeof target === 'string' ? (this.tickets.find((x) => x.ref === ref) || { ref }) : target;
            this.threadOpen = true;
            this.threadLoading = true;
            this.threadFailure = null;
            this.replyFailure = null;
            this.closed = this.thread.status === 'Cancelled';
            try {
                const res = await api.get('/citizen/support/tickets/' + encodeURIComponent(ref));
                if (this.thread?.ref !== ref) return;
                this.thread = res.data;
                this.closed = res.data.status === 'Cancelled';
                this.syncRow(res.data);
            } catch (err) {
                if (this.thread?.ref === ref) this.threadFailure = err;
            } finally {
                this.threadLoading = false;
                this.$nextTick(() => {
                    window.renderIcons?.();
                    this.scrollThread();
                });
            }
        },
        get threadError() { return errorText(this.threadFailure, 'Could not load the conversation.', 'Hindi ma-load ang usapan.'); },
        get replyError() { return this.replyFailure ? errorText(this.replyFailure, 'Could not send your reply.', 'Hindi maipadala ang iyong tugon.') : ''; },

        // The resident's first message is the ticket itself; staff appear as their office.
        get messages() {
            const t = this.thread;
            if (!t || !t.subject) return [];
            const you = this.t('You', 'Ikaw');
            const list = [{ key: 'first', from: 'citizen', name: you, body: t.body, at: t.created_at }];
            const replies = Array.isArray(t.messages) ? t.messages : [];
            replies.forEach((m, i) => list.push({ key: 'm' + i, from: m.from, name: m.from === 'staff' ? (m.name || 'LGU Esperanza') : you, body: m.body, at: m.at }));
            // A ticket answered before conversations existed (2026-10-15) has its one reply here.
            if (t.response && !replies.some((m) => m.from === 'staff')) {
                list.push({ key: 'response', from: 'staff', name: 'LGU Esperanza', body: t.response, at: t.responded_at });
            }
            return list;
        },
        scrollThread() {
            const box = document.getElementById('ticket-thread-messages');
            if (box) box.scrollTop = box.scrollHeight;
        },
        syncRow(t) {
            const i = this.tickets.findIndex((x) => x.ref === t.ref);
            if (i >= 0) this.tickets[i] = { ...this.tickets[i], ...t };
        },
        async sendReply() {
            const t = this.thread;
            const text = this.reply.trim();
            if (!t || !text || this.replying || this.closed) return;
            this.replying = true;
            this.replyFailure = null;
            try {
                const res = await api.post('/citizen/support/tickets/' + encodeURIComponent(t.ref) + '/reply', { message: text });
                this.thread = res.data;
                this.syncRow(res.data);
                this.reply = '';
                toast(this.t('Reply sent.', 'Naipadala ang tugon.'));
            } catch (err) {
                if (err?.code === 'TICKET_CLOSED' || err?.status === 409) {
                    this.closed = true;
                    if (this.thread) this.thread.status = 'Cancelled';
                    this.syncRow({ ref: t.ref, status: 'Cancelled' });
                } else {
                    this.replyFailure = err;
                }
            } finally {
                this.replying = false;
                this.$nextTick(() => {
                    window.renderIcons?.();
                    this.scrollThread();
                });
            }
        },
        // A closed ticket takes no replies: a new one, starting from this one.
        startNew() {
            const t = this.thread;
            this.threadOpen = false;
            if (t) {
                this.form.subject = ('Re: ' + (t.subject || '')).slice(0, 160);
                if (this.categories.includes(t.category)) this.form.category = t.category;
                this.form.requestRef = t.request_ref || '';
            }
            setTimeout(() => {
                const el = document.getElementById('subject');
                el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el?.focus({ preventScroll: true });
            }, 250);
        },
    };
}

/** "Was this helpful?" on one FAQ: posted, and remembered on this device. */
function helpFeedback(docKey, item) {
    const key = String(item || '').trim().slice(0, 160);
    const STORE = 'esperanza_help_votes';
    return {
        vote: null,
        sending: false,
        init() {
            const saved = memory.read(STORE)[docKey + '|' + key];
            this.vote = typeof saved === 'boolean' ? saved : null;
        },
        async send(helpful) {
            if (this.sending || !key || this.vote === helpful) return;
            const before = this.vote;
            this.vote = helpful;
            this.sending = true;
            try {
                await api.post('/citizen/help/feedback', { doc_key: docKey, item: key, helpful });
                memory.put(STORE, docKey + '|' + key, helpful);
                toast(tr('Thanks for your feedback!', 'Salamat sa iyong feedback!'));
            } catch (err) {
                this.vote = before;
                toast(errorText(err, 'Could not send your feedback.', 'Hindi maipadala ang iyong feedback.'), 'error');
            } finally {
                this.sending = false;
            }
        },
    };
}

// ================================================================= Events

// The upcoming events this resident asked to be reminded of: fetched once a
// page, shared by every month the calendar shows.
let reminderKeys = null;

/**
 * Remind me (2026-10-21): in the app - the resident is reminded the day
 * before in their notifications and on their phone, and told if the event
 * moves or is cancelled - or in their own calendar: a calendar file with a
 * reminder the day before, or Google Calendar.
 */
function eventCalendar() {
    // The page keys its events by date (event.key), so the date and title
    // make the UID: two events on one day must not replace each other.
    const details = (e) => ({
        uid: e.date + '-' + slug(e.title || e.name) + '@esperanza.gov.ph',
        title: e.title || e.name || 'Event',
        date: e.date,
        time: e.time,
        place: [e.venue, e.barangay ? 'Brgy. ' + e.barangay : null, 'Esperanza, Masbate'].filter(Boolean).join(', '),
        description: [e.description, e.time ? tr('Time: ', 'Oras: ') + e.time : null].filter(Boolean).join('\n'),
    });
    return {
        menu: null,
        reminded: {},
        saving: null,
        async init() {
            if (!window.Alpine?.store('citizenSession')?.account) return;
            try {
                reminderKeys ??= (await api.get('/citizen/event-reminders')).data || [];
            } catch {
                reminderKeys = null;
                return;
            }
            this.reminded = Object.fromEntries(reminderKeys.map((k) => [k, true]));
        },
        toggle(id) {
            this.menu = this.menu === id ? null : id;
            icons();
        },
        isReminded(e) { return !!this.reminded[e.ref]; },
        // Registering for an event sets its reminder on the server (round 2,
        // 2026-10-30, resources/js/citizen-events.js): the button follows.
        markReminded(key) {
            if (!key || this.reminded[key]) return;
            this.reminded = { ...this.reminded, [key]: true };
            reminderKeys = Object.keys(this.reminded).filter((k) => this.reminded[k]);
            icons();
        },
        async toggleReminder(e) {
            if (this.saving || !e.ref) return;
            this.menu = null;
            this.saving = e.ref;
            const on = !this.isReminded(e);
            try {
                const path = '/citizen/events/' + encodeURIComponent(e.ref) + '/reminder';
                if (on) await api.post(path);
                else await api.delete(path);
                this.reminded = { ...this.reminded, [e.ref]: on };
                reminderKeys = Object.keys(this.reminded).filter((k) => this.reminded[k]);
                toast(on
                    ? tr('We will remind you the day before, here and on your phone. You will also be told if it moves or is cancelled.', 'Ipapaalala namin ito isang araw bago, dito at sa iyong telepono. Sasabihan ka rin kung malilipat o makakansela ito.')
                    : tr('Reminder turned off.', 'Pinatay ang paalala.'));
                icons();
            } catch (err) {
                toast(errorText(err, 'Could not change the reminder. Try again.', 'Hindi mabago ang paalala. Subukang muli.'), 'error');
            } finally {
                this.saving = null;
            }
        },
        save(e) {
            this.menu = null;
            const d = details(e);
            if (downloadIcs(d, slug(d.title) + '.ics')) {
                toast(tr('Calendar file saved. Open it to add the event, with a reminder one day before.', 'Na-save ang calendar file. Buksan ito para maidagdag ang kaganapan, may paalala isang araw bago nito.'));
            }
        },
        google(e) { return googleCalendarUrl(details(e)); },
        // A reminder is for what is still to come: an event whose day has
        // passed (Esperanza's day) offers none (2026-10-15).
        isPast(e) {
            const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
            return !!e.date && String(e.date).slice(0, 10) < today;
        },
    };
}

// ======================================================= Request tracking

const SURVEY_STORE = 'esperanza_surveys';
const RATINGS = { 1: ['Very poor', 'Napakahina'], 2: ['Poor', 'Mahina'], 3: ['Fair', 'Katamtaman'], 4: ['Good', 'Maganda'], 5: ['Excellent', 'Napakahusay'] };

/**
 * Track Request, both pages: the issued documents with their QR codes, the
 * request slip (and Tulong's approval notice) to print, booking an
 * appointment for it, and the satisfaction survey once it is done. Reads the
 * page's selectedRequest; the page's applyDetail() puts the detail on it.
 */
function requestExtras(kind, links = {}) {
    return {
        kind,
        links,
        ref: null,
        sheetOpen: false,
        sheetKind: 'slip',
        refQr: '',
        docQrs: {},
        entitlement: '',
        printedAt: '',
        downloading: false,
        rating: 0,
        comment: '',
        surveySending: false,
        surveyFailure: null,
        surveyState: '',

        init() {
            this.sync();
            this.$watch('selectedRequest && selectedRequest.ref', () => this.sync());
            this.$watch('selectedRequest && selectedRequest.issuedDocuments', () => this.drawQrs());
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        fmtDate,
        fmtDateTime,
        peso,
        tone,

        // The service's steps on this request (2026-10-17).
        get steps() { return this.selectedRequest?.workflow?.steps || []; },
        get stepsSummary() {
            const wf = this.selectedRequest?.workflow;
            if (!wf) return '';
            const done = this.steps.filter((st) => st.done).length;
            if (wf.stopped) return this.t('This request was stopped; the steps after it will not happen.', 'Nahinto ang kahilingang ito; hindi na matutuloy ang mga susunod na hakbang.');
            const cur = this.steps.find((st) => st.index === wf.current);
            return done + this.t(' of ', ' sa ') + this.steps.length + this.t(' done', ' ang tapos na') + (cur ? this.t('. Now: ', '. Ngayon: ') + cur.name : '');
        },
        stepMeta(st) {
            const bits = [];
            if (st.responsible) bits.push(st.responsible);
            if (st.days !== null && st.days !== undefined && st.days !== '') bits.push(st.days + this.t(Number(st.days) === 1 ? ' working day' : ' working days', ' araw ng trabaho'));
            if (st.done && st.done_at) bits.push(this.t('done ', 'natapos ') + fmtDate(st.done_at));
            return bits.join(' · ');
        },

        // Another request opened in the same window starts clean.
        sync() {
            const ref = this.selectedRequest?.ref || null;
            if (ref !== this.ref) {
                this.ref = ref;
                this.refQr = '';
                this.entitlement = '';
                this.rating = 0;
                this.comment = '';
                this.surveyFailure = null;
                this.sheetOpen = false;
                this.surveyState = ref ? (memory.read(SURVEY_STORE)[ref] || '') : '';
            }
            this.drawQrs();
        },
        drawQrs() {
            (this.selectedRequest?.issuedDocuments || []).forEach((d) => {
                const url = d.verify_url;
                if (!url || url in this.docQrs || !window.qrSvg) return;
                this.docQrs[url] = '';
                window.qrSvg(url).then((svg) => { this.docQrs[url] = svg; }).catch(() => {});
            });
        },

        get r() { return this.selectedRequest || null; },
        get status() { return this.r?.status || ''; },
        get canBook() { return !!this.r && !['Rejected', 'Cancelled', 'Released', 'Completed', 'Archived'].includes(this.status); },
        get bookHref() { return this.links.appointments + '?request=' + encodeURIComponent(this.r?.ref || ''); },
        get issued() { return this.r?.issuedDocuments || []; },
        get noticeReady() { return this.kind === 'tulong' && ['Assistance Processing', 'Ready for Release', 'Completed'].includes(this.status); },
        docState(d) {
            if (d.revoked) return 'revoked';
            return d.valid_until && String(d.valid_until) < manilaDay(0) ? 'expired' : 'valid';
        },
        docStateLabel(d) {
            const state = this.docState(d);
            if (state === 'revoked') return this.t('Revoked', 'Binawi');
            if (state === 'expired') return this.t('Expired', 'Lipas na');
            return this.t('Valid', 'May bisa');
        },
        verifyHref(d) { return this.links.verify + '?code=' + encodeURIComponent(d.serial || d.control_number || ''); },

        // -- The printed slip and notice
        get applicant() { return this.$store.citizenSession.account?.fullName || ''; },
        get barangay() { return this.r?.barangay || this.$store.citizenSession.account?.barangay || ''; },
        get filed() { return fmtDate(this.r?.submitted_at) || this.r?.submitted || ''; },
        get bring() {
            const r = this.r;
            if (!r) return [];
            const list = [this.t('A valid ID with your photo', 'Isang valid ID na may larawan')];
            list.push(this.sheetKind === 'notice'
                ? this.t('This notice, or your reference number', 'Ang abisong ito, o ang iyong reference number')
                : this.t('This slip, or your reference number', 'Ang slip na ito, o ang iyong reference number'));
            const reqs = r.requirements || [];
            reqs.filter((q) => q.status === 'Not submitted').forEach((q) => list.push(q.name));
            if (reqs.some((q) => q.file)) list.push(this.t('The originals of the documents you uploaded, in case the office asks to see them', 'Ang orihinal ng mga in-upload mong dokumento, kung sakaling hingin ng opisina'));
            const pay = r.payment;
            if (pay && Number(pay.amount) > 0 && pay.status !== 'Approved') list.push(this.t('The fee of ' + peso(pay.amount) + ', paid at the office', 'Ang bayaring ' + peso(pay.amount) + ', babayaran sa opisina'));
            return list;
        },
        get approvedAmount() { return peso(this.r?.assistance?.approved_amount); },
        get releasedAmount() { return peso(this.r?.assistance?.released_amount); },
        get noticeBody() {
            const r = this.r;
            if (!r) return '';
            const office = r.office || this.t('the Municipality', 'ang Munisipyo');
            return r.status === 'Completed'
                ? this.t('This is to certify that the ' + r.type + ' applied for under reference ' + r.ref + ' was approved by the ' + office + ' and has been released to the applicant.', 'Pinatutunayan nito na ang ' + r.type + ' na in-apply sa reference ' + r.ref + ' ay inaprubahan ng ' + office + ' at naibigay na sa aplikante.')
                : this.t('This is to inform you that your application for ' + r.type + ' under reference ' + r.ref + ' has been approved by the ' + office + '.', 'Ipinababatid sa iyo na naaprubahan ng ' + office + ' ang iyong aplikasyon para sa ' + r.type + ' sa reference ' + r.ref + '.');
        },
        get pickup() {
            const s = this.claim;
            if (s) return [s.office, s.office_contact].filter(Boolean).join(' · ');
            const p = this.pickupInfo;
            return p ? [p.address, p.contact].filter(Boolean).join(' · ') : '';
        },

        // -- Ready to claim: the server's own pickup details (GET
        //    /citizen/requests/{ref} -> pickup, CitizenRequestController::pickup),
        //    present only while the request waits to be claimed (Mark to
        //    Release, or Tulong's Ready for Release). The app's PickupCard.
        get claim() { return this.r?.pickup || null; },
        get claimHours() {
            const s = this.claim;
            if (!s) return '';
            return this.$store.lang.current === 'en' ? (s.office_hours || s.office_hours_fil || '') : (s.office_hours_fil || s.office_hours || '');
        },
        get claimTel() { return dialHref(this.claim?.office_contact); },
        get claimSince() { return this.claim?.ready_since ? fmtDate(this.claim.ready_since) : ''; },
        get claimBring() {
            const s = this.claim;
            if (!s) return [];
            const ref = this.r?.ref || '';
            return (s.bring || []).map((key) => {
                if (key === 'valid_id') return this.t('A valid ID', 'Isang valid ID');
                if (key === 'reference_number') return this.t('Your reference number, ' + ref, 'Ang iyong reference number, ' + ref);
                if (key === 'fee') {
                    return s.fee_due !== null && s.fee_due !== undefined
                        ? this.t('The fee of ' + peso(s.fee_due) + ', paid at the office before release', 'Ang bayaring ' + peso(s.fee_due) + ', babayaran sa opisina bago i-release')
                        : this.t('The fee, paid at the office before release', 'Ang bayarin, babayaran sa opisina bago i-release');
                }
                return String(key);
            });
        },

        // -- The signed copy of an issued document (issued_documents[]:
        //    has_scan, scan_uploaded_at, scan_url). A revoked one has none.
        scanState(d) {
            if (d.revoked) return 'revoked';
            if (d.scan_url) return 'ready';
            return d.has_scan ? 'checking' : 'none';
        },
        openSheet(which) {
            const r = this.r;
            if (!r) return;
            this.sheetKind = which;
            this.printedAt = fmtDateTime(new Date().toISOString());
            this.sheetOpen = true;
            if (!this.refQr && window.qrSvg) {
                window.qrSvg(r.ref).then((svg) => { if (this.r?.ref === r.ref) this.refQr = svg; }).catch(() => {});
            }
            // A program with no amount (relief goods) says what it gives instead.
            if (which === 'notice' && !this.entitlement && r.serviceKey && !this.approvedAmount && !this.releasedAmount) {
                api.get('/services/' + encodeURIComponent(r.serviceKey))
                    .then((res) => { if (this.r?.ref === r.ref) this.entitlement = res.data?.form_spec?.entitlement || ''; })
                    .catch(() => {});
            }
            icons();
        },
        printSheet() { printSheet(document.getElementById('scope-print-sheet')); },
        async downloadSheet() {
            if (this.downloading) return;
            this.downloading = true;
            try {
                await downloadSheet(document.getElementById('scope-print-sheet'), (this.sheetKind === 'notice' ? 'Esperanza-Approval-Notice-' : 'Esperanza-Request-Slip-') + (this.r?.ref || '') + '.pdf');
            } finally {
                this.downloading = false;
            }
        },

        // -- The satisfaction survey, once per request
        get surveyDue() { return ['Released', 'Completed'].includes(this.status) && !this.surveyState; },
        ratingLabel(n) {
            const hit = RATINGS[n];
            return hit ? this.t(hit[0], hit[1]) : '';
        },
        get surveyError() { return this.surveyFailure ? errorText(this.surveyFailure, 'Could not send your rating.', 'Hindi maipadala ang iyong rating.') : ''; },
        remember(ref, state) {
            if (state !== 'closed') memory.put(SURVEY_STORE, ref, state);
            if (this.ref === ref) this.surveyState = state;
        },
        async sendSurvey() {
            const r = this.r;
            if (!r || this.surveySending || !this.rating) return;
            const ref = r.ref;
            this.surveySending = true;
            this.surveyFailure = null;
            try {
                await api.post('/citizen/surveys', { ref, rating: this.rating, comment: this.comment.trim() || null });
                this.remember(ref, 'sent');
                toast(this.t('Thank you for rating the service!', 'Salamat sa pag-rate sa serbisyo!'));
            } catch (err) {
                // The server keeps one answer per request; it decides.
                if (err?.code === 'ALREADY_SUBMITTED' || err?.status === 409) this.remember(ref, 'already');
                else if (err?.code === 'REQUEST_NOT_FINISHED') this.remember(ref, 'closed');
                else this.surveyFailure = err;
            } finally {
                this.surveySending = false;
            }
        },
        skipSurvey() {
            if (this.r) this.remember(this.r.ref, 'skipped');
        },
    };
}

/** Track Request's history: every status change, newest first, by "You" or the office. */
function requestTimeline() {
    return {
        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        tone,
        fmtDateTime,
        get entries() {
            const r = this.selectedRequest;
            if (!r) return [];
            const you = this.t('You', 'Ikaw');
            const office = r.office || this.t('The office', 'Ang opisina');
            const list = [];
            if (r.submitted_at) list.push({ key: 'filed', status: 'Submitted', at: r.submitted_at, by: you, mine: true, note: this.t('Request filed.', 'Naisumite ang kahilingan.') });
            (r.history || []).forEach((h, i) => {
                const trigger = String(h.trigger || '');
                const mine = trigger.startsWith('citizen:');
                let note = '';
                if (trigger === 'citizen:resubmit') note = this.t('You sent the corrected documents.', 'Ipinadala mo ang mga naayos na dokumento.');
                else if (trigger === 'citizen:withdraw') note = this.t('You withdrew the request.', 'Binawi mo ang kahilingan.');
                else if (trigger === 'auto:requirement-flagged') note = this.t('A document needs correction.', 'May dokumentong kailangang ayusin.');
                else if (trigger === 'import') note = this.t("Recorded from the office's files.", 'Itinala mula sa talaan ng opisina.');
                if (['Rejected', 'Cancelled'].includes(h.to) && r.remarks && !mine) note = r.remarks;
                list.push({ key: 'h' + i, status: h.to, at: h.at, by: mine ? you : office, mine, note });
            });
            return list.reverse();
        },
    };
}

// ============================================================== Emergency

// What a resident can report: the same names the staff Incidents tab files
// and filters by (SakunaVocabulary on the server, 2026-10-17), so a resident's
// flood is found under Flood. Six quick picks, the rest in "More kinds".
const INCIDENT_TYPES = [
    { key: 'incident_flood', name: 'Flood', fil: 'Baha', icon: 'waves', office: 'MDRRMO' },
    { key: 'incident_fire', name: 'Fire', fil: 'Sunog', icon: 'flame', office: 'BFP / MDRRMO' },
    { key: 'incident_landslide', name: 'Landslide', fil: 'Pagguho ng lupa', icon: 'mountain', office: 'MDRRMO' },
    { key: 'incident_medical', name: 'Medical Emergency', fil: 'Medikal na emergency', icon: 'siren', office: 'Municipal Health Office / MDRRMO' },
    { key: 'incident_road', name: 'Vehicular Accident', fil: 'Aksidente sa sasakyan', icon: 'car-front', office: 'MDRRMO / PNP' },
    { key: 'incident_other', name: 'Other', fil: 'Iba pa', icon: 'flag', office: 'MDRRMO' },
];
const MORE_INCIDENT_TYPES = [
    { key: 'incident_rescue', name: 'Rescue Request', fil: 'Humihingi ng saklolo', icon: 'life-buoy' },
    { key: 'incident_quake', name: 'Earthquake', fil: 'Lindol', icon: 'activity' },
    { key: 'incident_missing', name: 'Missing Person', fil: 'Nawawalang tao', icon: 'search' },
    { key: 'incident_stranded', name: 'Stranded Resident', fil: 'Residenteng na-stranded', icon: 'anchor' },
    { key: 'incident_drowning', name: 'Drowning or Water Rescue', fil: 'Nalulunod o saklolo sa tubig', icon: 'anchor' },
    { key: 'incident_tree', name: 'Fallen Tree', fil: 'Natumbang puno', icon: 'tree-pine' },
    { key: 'incident_powerline', name: 'Downed Power Line', fil: 'Bumagsak na kawad ng kuryente', icon: 'zap' },
    { key: 'incident_obstruction', name: 'Road Obstruction', fil: 'Harang sa daan', icon: 'construction' },
    { key: 'incident_bridge', name: 'Damaged Bridge', fil: 'Sirang tulay', icon: 'construction' },
    { key: 'incident_structure', name: 'Structural Damage', fil: 'Pinsala sa gusali o bahay', icon: 'building-2' },
    { key: 'incident_farm', name: 'Agricultural Damage', fil: 'Pinsala sa pananim o hayop', icon: 'wheat' },
    { key: 'incident_water', name: 'Water Interruption', fil: 'Walang tubig', icon: 'droplets' },
    { key: 'incident_power', name: 'Power Interruption', fil: 'Walang kuryente', icon: 'zap-off' },
    { key: 'incident_health', name: 'Public Health Emergency', fil: 'Emergency sa kalusugan ng publiko', icon: 'shield-alert' },
];
const ALL_INCIDENT_TYPES = [...INCIDENT_TYPES, ...MORE_INCIDENT_TYPES];
// Reports filed before the names were shared, read under the shared name.
const OLD_INCIDENT_TYPES = { Flooding: 'Flood', 'Road Accident': 'Vehicular Accident', 'Other Concern': 'Other' };
// The shared severities, sent as they are; the label follows the language.
const SEVERITIES = [['Low', 'Mababa'], ['Medium', 'Katamtaman'], ['High', 'Mataas'], ['Critical', 'Kritikal']];
// A report's status in the words the staff Incidents tab shows for it
// (incidentStatusLabel there), so both sides name the same moment the same way.
const INCIDENT_STATUS = {
    Submitted: ['Reported', 'Naiulat', 'bg-slate-100 text-slate-700 ring-slate-200'],
    'Under Verification': ['Validated', 'Nakumpirma', 'bg-sky-50 text-sky-800 ring-sky-200'],
    Assigned: ['Assigned', 'May naitalagang tutugon', 'bg-indigo-50 text-indigo-800 ring-indigo-200'],
    Processing: ['Dispatched', 'Papunta na ang tutugon', 'bg-violet-50 text-violet-800 ring-violet-200'],
    Completed: ['Resolved', 'Naresolba', 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
    Archived: ['Closed', 'Isinara', 'bg-slate-100 text-slate-700 ring-slate-200'],
};
// The staff alert levels (Alerts tab), in the resident's language.
const ALERT_LEVELS = { Information: 'Impormasyon', Advisory: 'Abiso', Watch: 'Pagbabantay', Warning: 'Babala', Critical: 'Kritikal' };
// What happened to a report, in words: the verbs of the staff side
// (IncidentLifecycle), never the responder's own note.
const INCIDENT_STEPS = {
    report: ['You reported it', 'Iniulat mo'],
    validate: ['Report confirmed by the office', 'Nakumpirma ng opisina ang ulat'],
    assign: ['Responders assigned', 'May naitalagang tutugon'],
    dispatch: ['Responders on the way', 'Papunta na ang mga tutugon'],
    escalate: ['Marked more serious', 'Itinaas ang antas ng panganib'],
    resolve: ['Resolved', 'Naresolba na'],
    close: ['Closed', 'Isinara na'],
};
const MAX_INCIDENT_PHOTOS = 3;
const MAX_PHOTO_BYTES = 10 * 1024 * 1024;
const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

function newUuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
    const b = new Uint8Array(16);
    if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(b);
    else for (let i = 0; i < b.length; i++) b[i] = Math.floor(Math.random() * 256);
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
    return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
}

/**
 * Emergency (Sakuna, the citizen slice): the published alerts, the hotlines
 * and evacuation centres (public GET /alerts, /hotlines, /sakuna/centers),
 * and reporting an incident and following it (GET/POST /citizen/incidents,
 * GET /citizen/incidents/{ref}). Reporting is open to an unverified account
 * on purpose (CitizenCapabilities::SAKUNA_INCIDENT). Mirrors the app's
 * sakuna_screen.dart, evacuation_center_detail_screen.dart and
 * report_incident_screen.dart. Each part loads on its own, so a failing one
 * never hides the hotlines.
 */
function citizenEmergency() {
    return {
        types: INCIDENT_TYPES,
        moreTypes: MORE_INCIDENT_TYPES,
        severities: SEVERITIES,
        maxPhotos: MAX_INCIDENT_PHOTOS,

        alerts: [],
        alertsState: 'loading',
        hotlines: [],
        hotlinesState: 'loading',
        centres: [],
        centresState: 'loading',
        incidents: [],
        incidentsState: 'loading',

        centre: null,
        centreOpen: false,

        detail: null,
        detailOpen: false,
        detailLoading: false,
        detailFailure: null,

        reportOpen: false,
        // lat/lng: where the emergency is, pinned on the map or from the
        // device (2026-10-26); optional, both or neither.
        form: { type: '', severity: '', barangay: '', sitio: '', description: '', lat: null, lng: null },
        photos: [],
        photoError: '',
        // One per report, kept across retries: the server answers a repeated
        // client_uuid with the report it already has, so a retry after a lost
        // answer never files the same emergency twice.
        clientUuid: '',
        submitting: false,
        reportFailure: null,
        reported: null,

        init() {
            this.loadAlerts();
            this.loadDirectory();
            this.loadIncidents();
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('incident');
            if (ref) this.openIncident({ ref });
            if (params.get('report')) this.openReport();
        },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        fmtDate,
        fmtDateTime,
        dialHref,
        get en() { return this.$store.lang.current === 'en'; },

        // -- Alerts: the resident's barangay and the municipality-wide ones.
        async loadAlerts() {
            this.alertsState = 'loading';
            try {
                const barangay = this.$store.citizenSession.account?.barangay;
                const res = await api.get('/alerts', { params: barangay ? { barangay } : {} });
                this.alerts = res.data || [];
                this.alertsState = 'loaded';
            } catch {
                this.alertsState = 'error';
            }
            icons();
        },
        alertText(field) {
            if (!field) return '';
            if (typeof field === 'string') return field;
            return this.en ? (field.en || field.fil || '') : (field.fil || field.en || '');
        },
        alertWhere(a) {
            return a.barangays && a.barangays.length ? a.barangays.map((b) => 'Brgy. ' + b).join(', ') : this.t('All barangays', 'Lahat ng barangay');
        },
        alertKind(a) {
            const level = a.level ? (this.en ? a.level : (ALERT_LEVELS[a.level] || a.level)) : '';
            return [a.type, level].filter(Boolean).join(' · ');
        },
        // When the office set it to end by itself (2026-10-17).
        alertUntil(a) {
            return a.expires_at ? this.t('Until ', 'Hanggang ') + fmtDateTime(a.expires_at) : '';
        },

        // -- Hotlines and evacuation centres
        loadDirectory() {
            this.hotlinesState = 'loading';
            this.centresState = 'loading';
            api.get('/hotlines')
                .then((res) => {
                    this.hotlines = (res.data || []).map((h) => ({
                        office: String(h.office || h.name || '').trim() || 'Hotline',
                        position: h.position || '',
                        contact: String(h.contact || h.number || '').trim(),
                    })).filter((h) => h.contact);
                    this.hotlinesState = 'loaded';
                })
                .catch(() => { this.hotlinesState = 'error'; })
                .finally(icons);
            api.getAll('/sakuna/centers')
                .then((res) => {
                    const mine = this.$store.citizenSession.account?.barangay || null;
                    this.centres = (res.data || []).filter((c) => c && c.name).map((c) => ({
                        ref: c.ref || c.name,
                        name: c.name,
                        barangay: c.barangay || '',
                        address: c.address || '',
                        // A centre is taking evacuees while it is open (Processing).
                        open: c.status === 'Processing',
                        services: (c.services || []).map(itemText).filter(Boolean),
                        capacity: Number(c.capacity) || 0,
                        individuals: Number.isFinite(Number(c.individuals)) && c.individuals !== null ? Number(c.individuals) : null,
                        families: Number.isFinite(Number(c.families)) && c.families !== null ? Number(c.families) : null,
                        pct: Number(c.pct) || 0,
                        lat: c.lat ?? null,
                        lng: c.lng ?? null,
                        mine: !!mine && c.barangay === mine,
                    })).sort((a, b) => (b.mine - a.mine) || (b.open - a.open) || a.barangay.localeCompare(b.barangay) || a.name.localeCompare(b.name));
                    this.centresState = 'loaded';
                })
                .catch(() => { this.centresState = 'error'; })
                .finally(icons);
        },
        occupancyText(c) {
            if (c.individuals !== null && c.capacity > 0) return this.t(c.individuals + ' of ' + c.capacity + ' people', c.individuals + ' sa ' + c.capacity + ' katao');
            return c.capacity > 0 ? this.t('Capacity: ' + c.capacity + ' people', 'Kapasidad: ' + c.capacity + ' katao') : this.t('Capacity not listed', 'Walang nakalistang kapasidad');
        },
        // The staff Centres tab's thresholds: near capacity from 75%.
        occupancyTone(c) {
            if (c.pct >= 90) return 'bg-rose-500';
            if (c.pct >= 75) return 'bg-amber-500';
            return 'bg-emerald-500';
        },
        occupancyWarning(c) {
            if (!c.open || c.individuals === null || c.capacity <= 0) return '';
            if (c.pct >= 100) return this.t('Full: ask the MDRRMO for another centre', 'Puno na: magtanong sa MDRRMO ng ibang center');
            if (c.pct >= 75) return this.t('Almost full', 'Halos puno na');
            return '';
        },
        // Directions: to the point when the centre has coordinates (staff pin
        // each centre since 2026-10-26), otherwise a Google Maps search for its
        // name and address, for a centre registered before the map.
        directionsUrl(c) {
            if (c && c.lat !== null && c.lng !== null && c.lat !== undefined && c.lng !== undefined) {
                return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(c.lat + ',' + c.lng);
            }
            const query = [c?.name, c?.address, c?.barangay ? 'Brgy. ' + c.barangay : null, 'Esperanza, Masbate'].filter(Boolean).join(', ');
            return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
        },
        openCentre(c) {
            this.centre = c;
            this.centreOpen = true;
            icons();
        },

        // -- My reports
        async loadIncidents() {
            this.incidentsState = 'loading';
            try {
                const res = await api.getAll('/citizen/incidents', { params: { sort: '-created_at' } });
                this.incidents = res.data || [];
                this.incidentsState = 'loaded';
            } catch {
                this.incidentsState = 'error';
            }
            icons();
        },
        typeOf(name) {
            const kept = OLD_INCIDENT_TYPES[name] || name;
            return ALL_INCIDENT_TYPES.find((x) => x.name === kept) || null;
        },
        isMoreType(name) { return MORE_INCIDENT_TYPES.some((x) => x.name === name); },
        statusLabel(st) {
            const hit = INCIDENT_STATUS[st];
            return hit ? this.t(hit[0], hit[1]) : (st || '');
        },
        statusTone(st) { return INCIDENT_STATUS[st]?.[2] || 'bg-slate-100 text-slate-700 ring-slate-200'; },
        typeLabel(name) {
            const hit = this.typeOf(name);
            return hit ? this.t(hit.name, hit.fil) : (name || '');
        },
        typeIcon(name) { return this.typeOf(name)?.icon || 'triangle-alert'; },
        severityLabel(value) {
            const hit = SEVERITIES.find(([v]) => v === (value === 'Moderate' ? 'Medium' : value));
            return hit ? this.t(hit[0], hit[1]) : (value || '');
        },
        severityClass(value) {
            const kept = value === 'Moderate' ? 'Medium' : value;
            return { Critical: 'bg-rose-50 text-rose-700 ring-rose-200', High: 'bg-orange-50 text-orange-700 ring-orange-200', Medium: 'bg-amber-50 text-amber-700 ring-amber-200' }[kept] || 'bg-slate-100 text-slate-600 ring-slate-200';
        },
        async openIncident(row) {
            this.detail = { ...row, photos: [], timeline: [] };
            this.detailOpen = true;
            this.detailLoading = true;
            this.detailFailure = null;
            try {
                const res = await api.get('/citizen/incidents/' + encodeURIComponent(row.ref));
                const d = res.data || {};
                this.detail = {
                    ...d,
                    photos: (d.photos || []).filter(Boolean).map((p) => ({ name: p.name || '', url: p.url || null, mime: p.mime || '', scan: p.scan_status || '', src: '', failed: false })),
                    timeline: d.timeline || [],
                };
                this.showPhotos(this.detail);
            } catch (err) {
                this.detailFailure = err;
            } finally {
                this.detailLoading = false;
                icons();
            }
        },
        // The API sends each photo as a download, so it is read and shown as
        // the image type it is (window.viewableImage), never run.
        showPhotos(detail) {
            detail.photos.forEach((p) => {
                if (!p.url || !window.viewableImage) return;
                window.viewableImage(p.url, p.mime || p.name)
                    .then((src) => { if (this.detail === detail) p.src = src; })
                    .catch(() => { p.failed = true; });
            });
        },
        photoNote(p) {
            if (p.url) return p.failed ? this.t('Could not show it. Open it instead.', 'Hindi maipakita. Buksan na lang.') : '';
            if (p.scan === 'infected' || p.scan === 'error') return this.t('This photo could not be accepted.', 'Hindi matanggap ang larawang ito.');
            return this.t('Being checked', 'Sinusuri pa');
        },
        get steps() {
            const d = this.detail;
            if (!d) return [];
            const list = [];
            if (d.reported) list.push({ key: 'reported', label: this.t(...INCIDENT_STEPS.report), status: this.statusLabel('Submitted'), at: d.reported });
            (d.timeline || []).forEach((e, i) => {
                const words = INCIDENT_STEPS[e.verb];
                list.push({ key: 't' + i, label: words ? this.t(words[0], words[1]) : this.statusLabel(e.status || e.verb || ''), status: this.statusLabel(e.status || ''), at: e.at });
            });
            return list.reverse();
        },
        get detailError() { return this.detailFailure ? errorText(this.detailFailure, 'Could not load this report.', 'Hindi ma-load ang ulat na ito.') : ''; },

        // -- Report an incident
        get barangays() {
            return Object.keys(this.$store.barangay.catalog || {}).sort((a, b) => a.localeCompare(b));
        },
        openReport(type = null) {
            if (!this.clientUuid) this.clientUuid = newUuid();
            if (type) this.form.type = type;
            if (!this.form.barangay) this.form.barangay = this.$store.citizenSession.account?.barangay || '';
            this.reported = null;
            this.reportFailure = null;
            this.reportOpen = true;
            icons();
        },
        addPhotos(fileList) {
            this.photoError = '';
            for (const file of [...(fileList || [])]) {
                if (this.photos.length >= MAX_INCIDENT_PHOTOS) {
                    this.photoError = this.t('Up to 3 photos.', 'Hanggang 3 larawan lang.');
                    break;
                }
                if (!PHOTO_TYPES.includes(file.type)) {
                    this.photoError = this.t('Choose a JPG, PNG or WebP photo.', 'Pumili ng larawang JPG, PNG o WebP.');
                    continue;
                }
                if (file.size > MAX_PHOTO_BYTES) {
                    this.photoError = this.t('Each photo must be 10 MB or smaller.', 'Dapat 10 MB o mas maliit ang bawat larawan.');
                    continue;
                }
                this.photos.push({ file, preview: URL.createObjectURL(file) });
            }
            icons();
        },
        removePhoto(i) {
            const [gone] = this.photos.splice(i, 1);
            if (gone) URL.revokeObjectURL(gone.preview);
        },
        get formProblem() {
            if (!this.form.type) return this.t('Choose what is happening.', 'Piliin kung ano ang nangyayari.');
            if (!this.form.severity || !this.form.barangay) return this.t('Please choose how serious it is and the barangay where it is happening.', 'Piliin kung gaano kalala at ang barangay kung saan ito nangyayari.');
            return '';
        },
        get reportError() {
            const err = this.reportFailure;
            if (!err) return '';
            if (typeof err === 'string') return err;
            const lang = this.en ? 'en' : 'fil';
            return typeof err.explain === 'function' ? err.explain(lang) : errorText(err, 'Your report could not be sent. Please try again.', 'Hindi naipadala ang iyong ulat. Pakisubukang muli.');
        },
        async submitReport() {
            if (this.submitting) return;
            const problem = this.formProblem;
            if (problem) { this.reportFailure = problem; return; }
            if (!this.clientUuid) this.clientUuid = newUuid();
            const f = this.form;
            const sitio = f.sitio.trim();
            const pinned = f.lat !== null && f.lng !== null && Number.isFinite(Number(f.lat)) && Number.isFinite(Number(f.lng));
            const fields = {
                title: f.type + ' — ' + (sitio ? sitio + ', ' : '') + f.barangay,
                type: f.type,
                severity: f.severity,
                barangay: f.barangay,
                sitio: sitio || null,
                // Sent together, or not at all (the API refuses half a pin).
                lat: pinned ? Number(f.lat) : null,
                lng: pinned ? Number(f.lng) : null,
                description: f.description.trim() || null,
                client_uuid: this.clientUuid,
            };
            let body = fields;
            if (this.photos.length) {
                body = new FormData();
                Object.entries(fields).forEach(([k, v]) => { if (v !== null) body.append(k, v); });
                this.photos.forEach((p, i) => body.append('photos[' + i + ']', p.file));
            }
            this.submitting = true;
            this.reportFailure = null;
            try {
                const res = await api.post('/citizen/incidents', body);
                const created = res.data || {};
                this.reported = created;
                this.incidents = [created, ...this.incidents.filter((x) => x.ref !== created.ref)];
                this.incidentsState = 'loaded';
                this.resetForm();
                toast(this.t('Report sent: ' + (created.ref || ''), 'Naipadala ang ulat: ' + (created.ref || '')));
            } catch (err) {
                // The form and its client_uuid stay as they were, for a retry.
                this.reportFailure = err;
            } finally {
                this.submitting = false;
                icons();
            }
        },
        resetForm() {
            this.photos.forEach((p) => URL.revokeObjectURL(p.preview));
            this.photos = [];
            this.photoError = '';
            this.form = { type: '', severity: '', barangay: this.$store.citizenSession.account?.barangay || '', sitio: '', description: '', lat: null, lng: null };
            this.clientUuid = '';
        },
        closeReport() {
            this.reportOpen = false;
            this.reportFailure = null;
        },
    };
}

// ================================================== Documents Uploaded

/**
 * Every file the resident attached to their Dokyu and Tulong requests, from
 * the app or the website (GET /citizen/uploads), with the ones a later file
 * replaced marked. A file opens by its signed link once the virus check has
 * cleared it; until then the row says why it cannot open yet. The app's
 * documents_uploaded_screen.dart.
 */
function citizenUploads() {
    return {
        rows: [],
        state: 'loading',
        failure: null,
        filter: 'all',

        init() { this.load(); },

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        fmtDate,

        async load() {
            this.state = 'loading';
            this.failure = null;
            try {
                const res = await api.get('/citizen/uploads');
                this.rows = (res.data || []).filter((row) => row && row.file).map((row, i) => ({
                    id: row.ref + '-' + i,
                    ref: row.ref,
                    type: row.type === 'tulong' ? 'tulong' : 'dokyu',
                    service: row.service || '',
                    requirement: row.requirement || '',
                    current: row.current !== false,
                    name: row.file.name || '',
                    url: row.file.url || null,
                    scan: row.file.scan_status || '',
                    uploadedAt: row.file.uploaded_at || null,
                }));
                this.state = 'loaded';
            } catch (err) {
                this.failure = err;
                this.state = 'error';
            }
            icons();
        },
        get list() { return this.filter === 'all' ? this.rows : this.rows.filter((r) => r.type === this.filter); },
        count(type) { return type === 'all' ? this.rows.length : this.rows.filter((r) => r.type === type).length; },
        get errorMessage() { return errorText(this.failure, 'Could not load your uploaded documents.', 'Hindi ma-load ang iyong mga in-upload na dokumento.'); },
        // Why a file cannot be opened yet, in the resident's words.
        why(row) {
            if (row.url) return '';
            switch (row.scan) {
                case 'pending': return this.t('Being checked for viruses. Try again in a minute.', 'Sinusuri pa para sa virus. Subukan ulit pagkalipas ng isang minuto.');
                case 'infected': return this.t('Blocked: this file failed the virus check. Upload a new copy.', 'Hinarang: hindi pumasa sa virus check ang file na ito. Mag-upload ng bagong kopya.');
                case 'error': return this.t('The virus check could not run yet, so it cannot open for now.', 'Hindi pa natapos ang virus check, kaya hindi pa ito mabubuksan.');
                default: return this.t('This file cannot be opened right now. Try again later.', 'Hindi mabuksan ang file na ito sa ngayon. Subukan ulit mamaya.');
            }
        },
    };
}

// =============================================== Balita: the feed

// The categories the staff form offers: the English value is what is stored.
const POST_CATEGORIES = [
    ['Community', 'Komunidad'], ['Health', 'Kalusugan'], ['Public Service', 'Serbisyo Publiko'],
    ['Advisory', 'Abiso'], ['Livelihood', 'Kabuhayan'], ['Governance', 'Pamamahala'],
];
const FEED_PAGE = 20;
const COMMENT_PAGE = 20;

function categoryLabel(value) {
    const hit = POST_CATEGORIES.find(([v]) => v === value);
    return hit ? tr(hit[0], hit[1]) : (value || '');
}

/**
 * Balita, the resident's side: the municipality's posts, newest first, with
 * their photos and videos, which residents like, comment on and share. Only
 * the LGU posts to Balita (owner, 2026-10-20: residents "can only see and
 * browse"). GET /announcements is already limited by the server to the
 * resident's barangay and municipal-wide posts. A notification opens its
 * post: ?post=ID.
 */
function balitaFeed() {
    return {
        tab: 'all',
        category: '',
        loading: true,
        failed: false,
        loadingMore: false,
        posts: [],
        events: [],
        more: { page: 0, last: 1 },
        focusKey: null,
        categories: POST_CATEGORIES,
        tabs: [
            ['all', 'All', 'Lahat', 'layout-list'],
            ['barangay', 'Barangay', 'Barangay', 'map-pinned'],
        ],

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },
        get en() { return this.$store.lang.current === 'en'; },
        categoryLabel,

        async init() {
            await this.loadFirst();
            await this.openLinked();
        },

        fromAnnouncement(a) {
            return {
                key: 'announcement-' + a.id, id: a.id, author: a.author || 'Esperanza LGU', barangay: a.barangay,
                category: a.category, title: a.title, body: a.body || '', media: a.media || [],
                likes: a.likes || 0, comments_count: a.comments_count || 0, liked_by_me: a.liked_by_me === true,
                at: a.published_at,
                // The event it was posted from: { key, date, time, venue, link } (2026-10-21).
                event: a.event || null,
                expanded: false, showComments: false, comments: null, commentsPage: 0, commentsMore: false, liking: false,
                // The comment being written, on the post itself: a map keyed by
                // post did not re-run the Send button's binding when it changed.
                draft: '', sending: false,
            };
        },

        async fetchPage(page) {
            const res = await api.get('/announcements', { params: { per_page: FEED_PAGE, page } });
            this.more = { page, last: res.meta?.last_page || page };
            return (res.data || []).map((row) => this.fromAnnouncement(row));
        },
        merge(list) {
            const byKey = new Map(this.posts.map((p) => [p.key, p]));
            list.forEach((p) => { if (!byKey.has(p.key)) byKey.set(p.key, p); });
            this.posts = [...byKey.values()].sort((a, b) => new Date(b.at || 0) - new Date(a.at || 0));
        },
        async loadFirst() {
            this.loading = true;
            this.failed = false;
            this.posts = [];
            try {
                const [ann, ev] = await Promise.all([
                    this.fetchPage(1),
                    api.get('/events', { params: { per_page: 4, from: new Date().toLocaleDateString('en-CA', { timeZone: TZ }) } }).catch(() => ({ data: [] })),
                ]);
                this.merge(ann);
                this.events = ev.data || [];
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
                icons();
            }
        },
        get hasMore() { return this.more.page < this.more.last; },
        async loadMore() {
            if (this.loadingMore || !this.hasMore) return;
            this.loadingMore = true;
            try {
                this.merge(await this.fetchPage(this.more.page + 1));
            } catch (err) {
                toast(errorText(err, 'Could not load more posts.', 'Hindi ma-load ang iba pang post.'), 'error');
            } finally {
                this.loadingMore = false;
                icons();
            }
        },

        // -- Filters
        matches(post) {
            const mine = this.$store.citizenSession.account?.barangay || null;
            const byTab = { all: true, barangay: !!mine && post.barangay === mine }[this.tab];
            return byTab && (!this.category || post.category === this.category);
        },
        get shown() { return this.posts.filter((p) => this.matches(p)); },
        get recentPosts() { return this.posts.slice(0, 4); },
        get trendingPosts() { return this.posts.filter((p) => p.likes > 0).sort((a, b) => b.likes - a.likes).slice(0, 3); },
        emptyText() { return this.t('No Balita posts here yet.', 'Wala pang balita rito.'); },

        // -- A post
        isLong(post) { return post.body.length > 420 || post.body.split('\n').length > 7; },
        whereLine(post) {
            const where = post.barangay ? 'Brgy. ' + post.barangay : this.t('All barangays', 'Lahat ng barangay');
            return [where, this.fmtTime(post.at)].filter(Boolean).join(' · ');
        },
        mediaLabel(post) { return post.title || post.author || ''; },
        engagementPath(post, action) { return '/citizen/announcements/' + post.id + '/' + action; },

        async toggleLike(post) {
            if (post.liking) return;
            post.liking = true;
            try {
                const res = await api.post(this.engagementPath(post, 'like'));
                post.liked_by_me = res.data.liked;
                post.likes = res.data.likes;
            } catch (err) {
                toast(errorText(err, 'Could not like this.', 'Hindi ito ma-like.'), 'error');
            } finally {
                post.liking = false;
            }
        },
        async toggleComments(post) {
            post.showComments = !post.showComments;
            if (post.showComments && post.comments === null) await this.loadComments(post);
            if (post.showComments) this.$nextTick(() => document.getElementById('comment-' + post.key)?.focus());
        },
        async loadComments(post, more = false) {
            const page = more ? post.commentsPage + 1 : 1;
            try {
                const res = await api.get(this.engagementPath(post, 'comments'), { params: { per_page: COMMENT_PAGE, page } });
                post.comments = more ? [...(post.comments || []), ...(res.data || [])] : (res.data || []);
                post.commentsPage = page;
                post.commentsMore = page < (res.meta?.last_page || page);
            } catch (err) {
                if (!more) post.comments = [];
                toast(errorText(err, 'Could not load the comments.', 'Hindi ma-load ang mga komento.'), 'error');
            } finally {
                icons();
            }
        },
        async addComment(post) {
            const text = (post.draft || '').trim();
            if (!text || post.sending) return;
            post.sending = true;
            try {
                const res = await api.post(this.engagementPath(post, 'comments'), { body: text });
                post.comments = [...(post.comments || []), res.data];
                post.comments_count += 1;
                post.draft = '';
            } catch (err) {
                toast(errorText(err, 'Could not post your comment.', 'Hindi naipadala ang iyong komento.'), 'error');
            } finally {
                post.sending = false;
                icons();
            }
        },
        async deleteComment(post, comment) {
            try {
                await api.delete(this.engagementPath(post, 'comments') + '/' + comment.id);
                post.comments = (post.comments || []).filter((c) => c.id !== comment.id);
                post.comments_count = Math.max(0, post.comments_count - 1);
                toast(this.t('Comment deleted.', 'Nabura ang komento.'));
            } catch (err) {
                toast(errorText(err, 'Could not delete the comment.', 'Hindi mabura ang komento.'), 'error');
            }
        },
        async share(post) {
            const url = window.location.origin + window.location.pathname + '?post=' + post.id;
            const text = (post.title ? post.title + '\n\n' : '') + post.body.slice(0, 200);
            try {
                if (navigator.share) {
                    await navigator.share({ title: post.title || 'Balita — Esperanza', text, url });
                    return;
                }
                await navigator.clipboard.writeText(url);
                toast(this.t('Link copied.', 'Nakopya ang link.'));
            } catch (err) {
                if (err?.name !== 'AbortError') toast(this.t('Could not share. Copy the address from your browser instead.', 'Hindi maibahagi. Kopyahin na lang ang address sa browser.'), 'error');
            }
        },

        // ?post=ID: that post is shown even when older than the first page,
        // and the page scrolls to it.
        async openLinked() {
            const id = Number(new URLSearchParams(window.location.search).get('post'));
            if (!id) return;
            let key = 'announcement-' + id;
            if (!this.posts.some((p) => p.key === key)) {
                try {
                    const one = await api.get('/announcements/' + id);
                    if (one.data) this.posts.unshift(this.fromAnnouncement(one.data));
                    else key = null;
                } catch { key = null; }
            }
            if (!key) return;
            this.tab = 'all';
            this.category = '';
            this.focusKey = key;
            this.$nextTick(() => document.getElementById('post-' + key)?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        },

        initials(name) { return (name || '').split(/\s+/).filter(Boolean).map((w) => w[0]).slice(0, 2).join('').toUpperCase(); },
        fmtTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const mins = Math.round((Date.now() - d.getTime()) / 60000);
            if (mins < 1) return this.t('Just now', 'Ngayon lang');
            if (mins < 60) return this.t(mins + (mins === 1 ? ' min ago' : ' mins ago'), mins + ' minuto ang nakalipas');
            const hrs = Math.round(mins / 60);
            if (hrs < 24) return this.t(hrs + (hrs === 1 ? ' hr ago' : ' hrs ago'), hrs + ' oras ang nakalipas');
            return fmtDate(iso, { year: 'numeric' });
        },
        fmtDate,
    };
}

export function register(Alpine) {
    window.applyServiceCatalog = applyServiceCatalog;
    window.dialHref = dialHref;
    Alpine.data('citizenEmergency', citizenEmergency);
    Alpine.data('citizenUploads', citizenUploads);
    Alpine.data('balitaFeed', balitaFeed);
    registerBalitaMedia(Alpine);
    Alpine.data('citizenAppointments', citizenAppointments);
    Alpine.data('verifyPage', verifyPage);
    Alpine.data('nameChangeRequest', nameChangeRequest);
    Alpine.data('supportDesk', supportDesk);
    Alpine.data('helpFeedback', helpFeedback);
    Alpine.data('eventCalendar', eventCalendar);
    Alpine.data('requestExtras', requestExtras);
    Alpine.data('requestTimeline', requestTimeline);
}
