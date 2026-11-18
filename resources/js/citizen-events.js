// Registering for an event, on the resident's Events page (Esperanza round
// 2, 2026-10-30; owner: "have also buttons for users to register to the
// posted events so that the municipality will have record of those who
// attended", and the e-certificates go to those who attended).
//
// On an upcoming event the office opened for registration: Magparehistro
// (with the places left when there is a limit), then "Nakarehistro ka" with
// the registration code in big letters and as a QR - what the officer at the
// door types or scans - and a way to cancel, confirmed on the page. A full
// event or a closed registration says so and why. After the event: "Dumalo
// ka" once the office marked them, and "Tingnan ang sertipiko" once a
// certificate was issued (the shared certificate window). The page's own
// "Aking mga sertipiko mula sa mga kaganapan" lists them all, so one from
// an event months ago is not lost in the calendar.
//
// Routes (backend, round 2): GET /citizen/event-registrations -> [{event_key,
// code, registered_at, cancelled_at, attended, certificate | null}], POST and
// DELETE /citizen/events/{key}/registration, GET /citizen/certificates (rows
// with source session|event and event_key). Errors: 409 EVENT_FULL, 422
// REGISTRATION_CLOSED, 422 EVENT_NOT_UPCOMING.
//
// Loaded only on /citizen/events (the area loader at the end of app.js);
// register() runs before Alpine starts. The cards are drawn again with every
// month the calendar shows, so what the resident registered for is kept in a
// store, read once per visit.
import api from './api/client';
import { registerCertificate, openWelfareCertificate } from './welfare-certificate';

const TZ = 'Asia/Manila';
const isEn = () => window.Alpine?.store('lang')?.current === 'en';
const tr = (en, fil) => (isEn() ? en : fil);
const signedIn = () => !!window.Alpine?.store('citizenSession')?.account;
const icons = () => window.Alpine?.nextTick(() => window.renderIcons?.());

// On window, not $dispatch: the button that asked may be gone by the time the
// answer comes back.
function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

/** The registration code as printed: XXXX-XXXX. */
export function showCode(code) {
    const c = String(code || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    return c.length > 4 ? c.slice(0, 4) + '-' + c.slice(4) : c;
}

/** 'Nob 12, 2026, 8:00 AM' in Esperanza time. */
function when(value) {
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString(isEn() ? 'en-US' : 'fil-PH', { timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

/** 'Nob 12, 2026' for a Y-m-d day. */
function dayText(value) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''));
    if (!m) return value || '';
    return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], 12)).toLocaleDateString(isEn() ? 'en-US' : 'fil-PH', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
}

/**
 * When an event starts, as an instant: its day at its time in Esperanza
 * (UTC+8 all year). A time the office typed as text ('8:00 AM') is read;
 * one that cannot be read counts as the start of the day.
 */
function startOf(e) {
    const d = /^(\d{4}-\d{2}-\d{2})/.exec(String(e?.date || ''));
    if (!d) return null;
    const m = /^(\d{1,2}):(\d{2})(?::\d{2})?\s*([AaPp][Mm])?/.exec(String(e.time || '').trim());
    let hm = '00:00';
    if (m) {
        let h = Number(m[1]);
        const ap = (m[3] || '').toLowerCase();
        if (ap === 'pm' && h < 12) h += 12;
        if (ap === 'am' && h === 12) h = 0;
        if (h <= 23) hm = String(h).padStart(2, '0') + ':' + m[2];
    }
    return new Date(d[1] + 'T' + hm + ':00+08:00');
}

const today = () => new Date().toLocaleDateString('en-CA', { timeZone: TZ });

// The server's wording for these is the same idea; the page says it the way
// the card around it does, and acts on it (a full event shows as full).
const SIGNUP_ERRORS = {
    EVENT_FULL: ['This event is full: every place has been taken. Someone may still cancel, so look again later.', 'Puno na ang kaganapang ito: nakuha na ang lahat ng puwesto. Maaaring may magkansela pa, kaya tumingin muli mamaya.'],
    REGISTRATION_CLOSED: ['Registration for this event has closed.', 'Sarado na ang pagpaparehistro para sa kaganapang ito.'],
    EVENT_NOT_UPCOMING: ['This event has ended or is no longer on the calendar.', 'Tapos na o wala na sa kalendaryo ang kaganapang ito.'],
};

function signupError(err, en, fil) {
    const known = SIGNUP_ERRORS[err?.code];
    if (known) return tr(known[0], known[1]);
    return err && typeof err.localised === 'function' ? err.localised(isEn() ? 'en' : 'fil') : tr(en, fil);
}

/**
 * What the resident registered for, and the certificates their events gave
 * them. Read once per visit; a registration or a cancellation updates it.
 * Always used through the store (regs()): Alpine watches the store's copy,
 * not this object.
 */
const eventRegs = {
    byKey: {},
    // Read once: until then a card offers nothing, rather than Register to a
    // resident who registered already, or "full" / "closed" in place of
    // their code and QR. A failed read does not count as read: it is tried
    // again a little later, and `failed` says so (2026-11-18).
    ready: false,
    failed: false,
    tries: 0,
    certs: [],
    certsLoaded: false,
    loading: null,
    certsLoading: null,
    load() {
        if (!signedIn()) {
            this.ready = true;
            return Promise.resolve();
        }
        this.loading ??= api.get('/citizen/event-registrations')
            .then((res) => {
                const by = {};
                (Array.isArray(res?.data) ? res.data : []).forEach((r) => { if (r && r.event_key) by[r.event_key] = r; });
                this.byKey = by;
                this.failed = false;
                this.tries = 0;
                this.ready = true;
            })
            .catch(() => {
                this.loading = null;
                this.failed = true;
                this.tries += 1;
                if (this.tries < 4) setTimeout(() => { if (!this.ready) this.load(); }, 5000 * this.tries);
            });
        return this.loading;
    },
    /** Read again now (a Try again button). */
    retry() {
        this.loading = null;
        this.tries = 0;
        return this.load();
    },
    loadCerts() {
        if (!signedIn()) return Promise.resolve();
        this.certsLoading ??= api.get('/citizen/certificates')
            .then((res) => {
                this.certs = (Array.isArray(res?.data) ? res.data : []).filter((c) => c && (c.source === 'event' || c.event_key));
                this.certsLoaded = true;
                icons();
            })
            .catch(() => { this.certsLoading = null; });
        return this.certsLoading;
    },
    put(row) {
        if (row && row.event_key) this.byKey = { ...this.byKey, [row.event_key]: row };
    },
};

const regs = () => window.Alpine.store('eventRegs');

/** A certificate row's document and what the window needs besides. */
function certParts(c, fallbackName = '') {
    const doc = c.document || { control_number: c.control_number, verify_url: c.verify_url, serial: c.verification_code, issued_on: c.issued_on, document_type: 'event_certificate', field_values: c.field_values || {} };
    const fv = doc.field_values || {};
    const ev = c.event || {};
    return {
        doc,
        meta: {
            source: 'event',
            title: c.title || fv.cert_title,
            event_title: ev.title || fv.cert_subtitle || '',
            holder_names: c.holder_names && c.holder_names.length ? c.holder_names : [fv.applicant || fallbackName].filter(Boolean),
            session: { date: ev.date || fv.event_date || null, venue: ev.venue || fv.venue || null },
            verify_url: c.verify_url,
            verification_code: c.verification_code,
            issued_on: c.issued_on,
        },
    };
}

/** One event card's Register / Registered / Attended block. */
export function eventSignup(ev) {
    return {
        suBusy: false,
        suAsk: false,
        suQr: '',
        suQrFor: '',
        // A refusal the card learned of just now (closed meanwhile).
        suClosedNow: false,
        init() {
            regs().load();
        },
        suShowCode: showCode,
        /** The resident's registrations could not be read (yet): say so, with Try again. */
        // Only on a card that takes registrations: elsewhere there is nothing
        // the read would change. Shown by event-signup.blade.php (#117).
        suFailed() {
            const info = this.suInfo();
            return signedIn() && !!(info.enabled ?? info.open) && this.$store.eventRegs.failed && !this.$store.eventRegs.ready;
        },
        /** A read is under way (the automatic retry, or Try again). */
        suRetrying() { return !!this.$store.eventRegs.loading && !this.$store.eventRegs.ready; },
        suRetry() { return regs().retry(); },
        suReg() { return this.$store.eventRegs.byKey[ev.ref] || null; },
        suActive() {
            const r = this.suReg();
            return r && !r.cancelled_at ? r : null;
        },
        suInfo() { return ev.registration || {}; },
        /** The day has passed (Esperanza's). */
        suPast() { return !!ev.date && String(ev.date).slice(0, 10) < today(); },
        suStarted() {
            const s = startOf(ev);
            return !!s && Date.now() >= s.getTime();
        },
        suClosesAt() {
            const at = this.suInfo().closes_at ? new Date(this.suInfo().closes_at) : startOf(ev);
            return at && !Number.isNaN(at.getTime()) ? at : null;
        },
        suClosed() {
            const at = this.suClosesAt();
            return this.suClosedNow || (!!at && Date.now() >= at.getTime());
        },
        /**
         * none | guest | open | full | closed | registered | attended |
         * missed (marked absent) | unrecorded (the day passed, not marked yet).
         */
        suState() {
            const mine = this.suActive();
            if (mine) {
                if (mine.attended === true) return 'attended';
                if (this.suPast()) return mine.attended === false ? 'missed' : 'unrecorded';
                return 'registered';
            }
            // enabled is the setting; open turns false once full or closed,
            // when this still says which (round 2 review, 2026-10-30).
            if (!(this.suInfo().enabled ?? this.suInfo().open) || this.suPast()) return 'none';
            if (!signedIn()) return 'guest';
            if (!this.$store.eventRegs.ready) return 'none';
            if (this.suInfo().full) return 'full';
            return this.suClosed() || this.suStarted() ? 'closed' : 'open';
        },
        suOpenLine() {
            const info = this.suInfo();
            const parts = [];
            if (info.capacity) {
                const left = Math.max(0, info.capacity - (info.count || 0));
                parts.push(tr(left === 1 ? '1 place left of ' + info.capacity : left + ' places left of ' + info.capacity, left + ' na puwesto na lang ang natitira sa ' + info.capacity));
            } else if (info.count) {
                parts.push(tr(info.count + ' registered so far', info.count + ' na ang nakarehistro'));
            }
            const at = this.suClosesAt();
            if (at) parts.push(tr('Registration until ', 'Hanggang ') + when(at));
            return parts.join(' · ');
        },
        suClosedLine() {
            const at = this.suClosesAt();
            return at ? tr('It closed on ', 'Nagsara ito noong ') + when(at) + '.' : '';
        },
        suFullLine() {
            const cap = this.suInfo().capacity;
            return cap ? tr('All ' + cap + ' places have been taken.', 'Nakuha na ang lahat ng ' + cap + ' na puwesto.') : '';
        },
        /** The QR of the code, made once per code (x-effect). */
        suMakeQr() {
            const code = this.suActive()?.code;
            if (!code || this.suQrFor === code || !window.qrSvg) return;
            this.suQrFor = code;
            window.qrSvg(showCode(code), 4).then((svg) => { if (this.suQrFor === code) this.suQr = svg; }).catch(() => { this.suQr = ''; });
        },
        async suRegister() {
            if (this.suBusy || !ev.ref) return;
            this.suBusy = true;
            try {
                const res = await api.post('/citizen/events/' + encodeURIComponent(ev.ref) + '/registration');
                const row = { ...(res?.data || {}) };
                row.event_key ||= ev.ref;
                if (!row.cancelled_at) row.cancelled_at = null;
                if (row.code) regs().put(row);
                else {
                    // The answer did not carry the code: read the registrations again.
                    regs().loading = null;
                    await regs().load();
                }
                // The counts the card shows: the server's when it sends them back.
                const info = row.registration || res?.data?.event?.registration;
                if (info) ev.registration = info;
                else if (ev.registration) {
                    ev.registration.count = (ev.registration.count || 0) + 1;
                    if (ev.registration.capacity && ev.registration.count >= ev.registration.capacity) ev.registration.full = true;
                }
                // Registering sets the day-before reminder too (the server's rule).
                window.dispatchEvent(new CustomEvent('event-registered', { detail: ev.ref }));
                toast(tr('You are registered. Show your code when you arrive; we will remind you the day before.', 'Nakarehistro ka na. Ipakita ang iyong code pagdating mo; ipapaalala namin ito isang araw bago.'));
                icons();
            } catch (err) {
                if (err?.code === 'EVENT_FULL' && ev.registration) ev.registration.full = true;
                if (err?.code === 'REGISTRATION_CLOSED') this.suClosedNow = true;
                // Registered already (on the phone, or by the office): show it.
                if (err?.status === 409 && err?.code !== 'EVENT_FULL') {
                    regs().loading = null;
                    regs().load();
                }
                toast(signupError(err, 'Could not register you right now. Try again.', 'Hindi ka maiparehistro ngayon. Subukang muli.'), 'error');
            } finally {
                this.suBusy = false;
            }
        },
        async suCancel() {
            const mine = this.suActive();
            if (this.suBusy || !mine) return;
            this.suBusy = true;
            try {
                const res = await api.delete('/citizen/events/' + encodeURIComponent(ev.ref) + '/registration');
                const row = res?.data && res.data.event_key ? res.data : { ...mine, cancelled_at: new Date().toISOString() };
                regs().put({ ...row, cancelled_at: row.cancelled_at || new Date().toISOString() });
                if (row.registration) ev.registration = row.registration;
                else if (ev.registration) {
                    ev.registration.count = Math.max(0, (ev.registration.count || 0) - 1);
                    ev.registration.full = false;
                }
                this.suAsk = false;
                this.suQr = '';
                this.suQrFor = '';
                toast(tr('Your registration is cancelled. The reminder stays on; turn it off under Remind Me if you no longer need it.', 'Kinansela ang iyong pagpaparehistro. Naka-on pa rin ang paalala; patayin ito sa Paalalahanan Ako kung hindi na kailangan.'));
                icons();
            } catch (err) {
                toast(signupError(err, 'Could not cancel your registration. Try again.', 'Hindi makansela ang iyong pagpaparehistro. Subukang muli.'), 'error');
            } finally {
                this.suBusy = false;
            }
        },
        /** The certificate, from the resident's certificates (the document as issued). */
        async suCert() {
            const mine = this.suActive();
            const own = mine?.certificate || {};
            await regs().loadCerts();
            const row = regs().certs.find((c) => (own.control_number && c.control_number === own.control_number) || c.event_key === ev.ref)
                || { ...own, title: own.title, event_key: ev.ref, event: { title: ev.title, date: ev.date, venue: ev.venue } };
            const name = window.Alpine?.store('citizenSession')?.account?.fullName || '';
            const { doc, meta } = certParts(row, name);
            if (!meta.event_title) meta.event_title = ev.title || '';
            if (!meta.session.date) meta.session = { date: ev.date, venue: ev.venue };
            openWelfareCertificate(doc, meta);
        },
    };
}

/** "Aking mga sertipiko mula sa mga kaganapan": every event certificate, whatever month it was. */
export function eventCertificates() {
    return {
        init() { regs().loadCerts(); },
        ecList() { return this.$store.eventRegs.certs; },
        ecTitle(c) { return c.title || c.document?.field_values?.cert_title || tr('Certificate', 'Sertipiko'); },
        ecLine(c) {
            const fv = c.document?.field_values || {};
            return [c.event?.title || fv.cert_subtitle, dayText(c.event?.date || fv.event_date), c.control_number].filter(Boolean).join(' · ');
        },
        ecOpen(c) {
            const { doc, meta } = certParts(c, window.Alpine?.store('citizenSession')?.account?.fullName || '');
            openWelfareCertificate(doc, meta);
        },
        ecHref(c) { return c.event_key ? '/citizen/events?event=' + encodeURIComponent(c.event_key) : null; },
    };
}

export function register(Alpine) {
    registerCertificate(Alpine);
    Alpine.store('eventRegs', eventRegs);
    Alpine.data('eventSignup', eventSignup);
    Alpine.data('eventCertificates', eventCertificates);
}
