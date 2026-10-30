// The resident's side of the welfare round (2026-10-29), on the Tulong page:
// the certificates of the sessions they attended - their own, and a
// partner's copy that names them too. (The services the MSWDO recorded as
// given to them left in round 2, 2026-10-30: their Tulong list shows them.)
// Loaded only on /citizen/assistance-requests (the area loader at the end of
// app.js); register() runs before Alpine starts.
import api from './api/client';
import { registerCertificate, openWelfareCertificate } from './welfare-certificate';

const TZ = 'Asia/Manila';
const isEn = () => window.Alpine?.store('lang')?.current === 'en';
const tr = (en, fil) => (isEn() ? en : fil);

function fmtDate(value) {
    if (!value) return '';
    const bare = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value).slice(0, 10));
    const d = bare ? new Date(Date.UTC(+bare[1], +bare[2] - 1, +bare[3], 12)) : new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString(isEn() ? 'en-US' : 'fil-PH', { month: 'short', day: 'numeric', year: 'numeric', timeZone: bare ? 'UTC' : TZ });
}

/** GET /citizen/certificates: every certificate naming them (applicant or partner), each with the document to draw. */
export function citizenWelfare() {
    return {
        certificates: [],
        fmtDate,
        tr,
        init() {
            this.loadCertificates();
        },
        async loadCertificates() {
            try {
                const res = await api.get('/citizen/certificates');
                this.certificates = Array.isArray(res?.data) ? res.data : [];
            } catch {
                this.certificates = [];
            } finally {
                window.Alpine?.nextTick(() => window.renderIcons?.());
            }
        },
        // GET /citizen/certificates carries the certificates of the events the
        // resident attended too (round 2, 2026-10-30): source event, with the
        // event in its document's field_values (cert_subtitle, event_date, venue).
        isEventCert(c) { return c.source === 'event' || !!c.event_key; },
        holders(c) {
            const names = (Array.isArray(c.holder_names) ? c.holder_names : [c.holder_names]).filter(Boolean);
            return (names.length ? names : [c.document?.field_values?.applicant].filter(Boolean)).join(tr(' and ', ' at '));
        },
        certLine(c) {
            if (!this.isEventCert(c)) return [c.session?.date ? fmtDate(c.session.date) : '', c.session?.venue, c.control_number].filter(Boolean).join(' · ');
            const fv = c.document?.field_values || {};
            const date = c.event?.date || fv.event_date;
            return [c.event?.title || fv.cert_subtitle, date ? fmtDate(date) : '', c.event?.venue || fv.venue, c.control_number].filter(Boolean).join(' · ');
        },
        openCertificate(c) {
            const fv = c.document?.field_values || {};
            const event = this.isEventCert(c);
            openWelfareCertificate(c.document || { control_number: c.control_number, verify_url: c.verify_url, serial: c.verification_code, issued_on: c.issued_on }, {
                title: c.title,
                holder_names: c.holder_names,
                session: event ? { date: c.event?.date || fv.event_date || null, venue: c.event?.venue || fv.venue || null } : (c.session || null),
                source: event ? 'event' : 'session',
                event_title: event ? (c.event?.title || fv.cert_subtitle || '') : '',
                verification_code: c.verification_code,
                verify_url: c.verify_url,
                issued_on: c.issued_on,
            });
        },
    };
}

export function register(Alpine) {
    registerCertificate(Alpine);
    Alpine.data('citizenWelfare', citizenWelfare);
}
