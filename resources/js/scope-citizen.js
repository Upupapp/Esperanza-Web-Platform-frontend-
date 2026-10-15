// Scope round 2026-10-15 - Citizen Web screens: the service list, appointments, the public Verify page, support conversations, request tracking extras.
//
// Loaded only on the pages that use it (see the area loader at the end of
// app.js), so the shared bundle stays within its budget. register() runs
// before Alpine starts.

import api from './api/client';

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
function applyServiceCatalog(entries, services) {
    const byKey = {};
    (services || []).forEach((sv) => { if (sv && sv.key) byKey[sv.key] = sv; });
    (entries || []).forEach((entry) => {
        const sv = entry && entry.key ? byKey[entry.key] : null;
        if (!sv) return;
        if (sv.fee && sv.fee.display) entry.fee = sv.fee.display;
        if (sv.days) entry.days = sv.days;
        if (Array.isArray(sv.requirements) && sv.version !== null && sv.version !== undefined) {
            entry.requirements = sv.requirements.map(itemText).filter(Boolean);
        }
        const steps = (sv.process || []).map(itemText).filter(Boolean);
        if (steps.length) entry.process = steps;
        if (sv.form_spec && sv.form_spec.entitlement && 'amount' in entry) entry.amount = sv.form_spec.entitlement;
    });
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
};
const SLATE = ['bg-slate-100 text-slate-600 ring-slate-200', 'bg-slate-400'];
function tone(status) {
    const hit = Object.values(TONES).find(([names]) => names.includes(status));
    return hit ? [hit[1], hit[2]] : SLATE;
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

        async check() {
            const value = this.code.trim().replace(/\s+/g, '');
            if (!value || this.state === 'loading') return;
            this.state = 'loading';
            this.result = null;
            this.failure = null;
            this.typeName = '';
            this.checked = value;
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
        another() {
            this.code = '';
            this.checked = '';
            this.state = 'idle';
            this.result = null;
            this.$nextTick(() => document.getElementById('verify-code')?.focus());
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

/** Remind me: a calendar file with a reminder the day before, or Google Calendar. */
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
        toggle(id) {
            this.menu = this.menu === id ? null : id;
            icons();
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

// What a resident can report (the app's MockCatalog.incidentTypes). The
// server takes `type` as free text, not a key into a catalogue, so the name
// is what is sent.
const INCIDENT_TYPES = [
    { key: 'incident_flood', name: 'Flooding', fil: 'Baha', icon: 'waves', office: 'MDRRMO' },
    { key: 'incident_fire', name: 'Fire', fil: 'Sunog', icon: 'flame', office: 'BFP / MDRRMO' },
    { key: 'incident_landslide', name: 'Landslide', fil: 'Pagguho ng lupa', icon: 'mountain', office: 'MDRRMO' },
    { key: 'incident_medical', name: 'Medical Emergency', fil: 'Medikal na emergency', icon: 'siren', office: 'Municipal Health Office / MDRRMO' },
    { key: 'incident_road', name: 'Road Accident', fil: 'Aksidente sa daan', icon: 'car-front', office: 'MDRRMO / PNP' },
    { key: 'incident_other', name: 'Other Concern', fil: 'Iba pang alalahanin', icon: 'flag', office: 'MDRRMO' },
];
// The server's severity values, sent as they are; the label follows the language.
const SEVERITIES = [['Low', 'Mababa'], ['Moderate', 'Katamtaman'], ['High', 'Mataas'], ['Critical', 'Kritikal']];
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
        form: { type: '', severity: '', barangay: '', sitio: '', description: '' },
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
        occupancyTone(c) {
            if (c.pct >= 90) return 'bg-rose-500';
            if (c.pct >= 70) return 'bg-amber-500';
            return 'bg-emerald-500';
        },
        // Directions: to the point when the centre has coordinates, otherwise a
        // Google Maps search for its name and address (the API carries none
        // today, as the app notes).
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
        typeOf(name) { return INCIDENT_TYPES.find((x) => x.name === name) || null; },
        typeLabel(name) {
            const hit = this.typeOf(name);
            return hit ? this.t(hit.name, hit.fil) : (name || '');
        },
        typeIcon(name) { return this.typeOf(name)?.icon || 'triangle-alert'; },
        severityLabel(value) {
            const hit = SEVERITIES.find(([v]) => v === value);
            return hit ? this.t(hit[0], hit[1]) : (value || '');
        },
        severityClass(value) {
            return { Critical: 'bg-rose-50 text-rose-700 ring-rose-200', High: 'bg-orange-50 text-orange-700 ring-orange-200', Moderate: 'bg-amber-50 text-amber-700 ring-amber-200' }[value] || 'bg-slate-100 text-slate-600 ring-slate-200';
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
            if (d.reported) list.push({ key: 'reported', label: this.t(...INCIDENT_STEPS.report), status: 'Submitted', at: d.reported });
            (d.timeline || []).forEach((e, i) => {
                const words = INCIDENT_STEPS[e.verb];
                list.push({ key: 't' + i, label: words ? this.t(words[0], words[1]) : (e.status || e.verb || ''), status: e.status || '', at: e.at });
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
            const fields = {
                title: f.type + ' — ' + (sitio ? sitio + ', ' : '') + f.barangay,
                type: f.type,
                severity: f.severity,
                barangay: f.barangay,
                sitio: sitio || null,
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
            this.form = { type: '', severity: '', barangay: this.$store.citizenSession.account?.barangay || '', sitio: '', description: '' };
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

// =============================================== Balita: write a post

// The six categories the app offers (compose_post_screen.dart).
const POST_CATEGORIES = [
    ['Community', 'Komunidad'], ['Health', 'Kalusugan'], ['Public Service', 'Serbisyo Publiko'],
    ['Advisory', 'Abiso'], ['Livelihood', 'Kabuhayan'], ['Governance', 'Pamamahala'],
];

/**
 * Write a community post (POST /citizen/community-posts, JSON or multipart
 * with `image`). Posts are held for moderation: the server answers with the
 * post as its author sees it (mine, not yet visible), and the page puts it at
 * the top of the feed with its "Pending review" label. Open to an unverified
 * account (CitizenCapabilities::COMMUNITY_POST).
 */
function balitaComposer() {
    return {
        categories: POST_CATEGORIES,
        composeOpen: false,
        category: 'Community',
        body: '',
        image: null,
        imagePreview: '',
        posting: false,
        failure: null,

        t(en, fil) { return this.$store.lang.current === 'en' ? en : fil; },

        openComposer() {
            this.failure = null;
            this.composeOpen = true;
            icons();
        },
        pickImage(file) {
            this.failure = null;
            if (!file) return;
            if (!PHOTO_TYPES.includes(file.type)) {
                this.failure = this.t('Choose a JPG, PNG or WebP photo.', 'Pumili ng larawang JPG, PNG o WebP.');
                return;
            }
            if (file.size > MAX_PHOTO_BYTES) {
                this.failure = this.t('The photo must be 10 MB or smaller.', 'Dapat 10 MB o mas maliit ang larawan.');
                return;
            }
            this.clearImage();
            this.image = file;
            this.imagePreview = URL.createObjectURL(file);
        },
        clearImage() {
            if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
            this.image = null;
            this.imagePreview = '';
        },
        get error() {
            const err = this.failure;
            if (!err) return '';
            if (typeof err === 'string') return err;
            const lang = this.$store.lang.current === 'en' ? 'en' : 'fil';
            return typeof err.explain === 'function' ? err.explain(lang) : errorText(err, 'Your post could not be sent.', 'Hindi naipadala ang iyong post.');
        },
        async sendPost(onPosted) {
            if (this.posting) return;
            const text = this.body.trim();
            if (!text) {
                this.failure = this.t('Please write something to post.', 'Pakisulat ang nais mong i-post.');
                return;
            }
            let payload = { body: text, category: this.category };
            if (this.image) {
                payload = new FormData();
                payload.append('body', text);
                payload.append('category', this.category);
                payload.append('image', this.image);
            }
            this.posting = true;
            this.failure = null;
            try {
                const res = await api.post('/citizen/community-posts', payload);
                if (typeof onPosted === 'function') onPosted(res.data || null);
                this.body = '';
                this.category = 'Community';
                this.clearImage();
                this.composeOpen = false;
                toast(this.t('Posted. It shows to others once Esperanza LGU reviews it.', 'Na-post. Makikita ito ng iba kapag nasuri na ng Esperanza LGU.'));
            } catch (err) {
                this.failure = err;
            } finally {
                this.posting = false;
            }
        },
    };
}

export function register(Alpine) {
    window.applyServiceCatalog = applyServiceCatalog;
    window.dialHref = dialHref;
    Alpine.data('citizenEmergency', citizenEmergency);
    Alpine.data('citizenUploads', citizenUploads);
    Alpine.data('balitaComposer', balitaComposer);
    Alpine.data('citizenAppointments', citizenAppointments);
    Alpine.data('verifyPage', verifyPage);
    Alpine.data('supportDesk', supportDesk);
    Alpine.data('helpFeedback', helpFeedback);
    Alpine.data('eventCalendar', eventCalendar);
    Alpine.data('requestExtras', requestExtras);
    Alpine.data('requestTimeline', requestTimeline);
}
