// The e-certificate of a group session (welfare round, 2026-10-29): the
// Certificate of Compliance of the Pre-Marriage Orientation, the Certificate
// of Marriage Counseling (Family Code art. 16) and the Parent Effectiveness
// Service's Certificate of Completion, drawn from the IssuedDocument the
// server made when the session was finished.
//
// One component for both portals (components/welfare/certificate.blade.php):
// the resident opens it from the case and from "My certificates", an officer
// from the case and from a session's participant list. Anything opens it with
//
//     window.openWelfareCertificate(doc, meta)
//
// doc  - an issued_documents[] row (control_number, serial, issued_on,
//        verify_url, revoked, document_type?, field_values?)
// meta - what the opener knows besides (service_key, title, holder_names,
//        session {date, venue}, verification_code)
//
// The wording comes from the document's own field_values first (what was
// true when it was issued); what they do not carry falls back to the
// catalog's certificate (config/esperanza_tulong_types.php), with :names,
// :date and :venue filled in the same way the server fills them.
//
// An event's certificate (round 2, 2026-10-30) is drawn by the same window:
// document_type event_certificate, issued by the municipality to those who
// attended an event (field_values: cert_title, cert_subtitle = the event,
// body, applicant, event_key, event_date, venue, signatories). Its office is
// the municipality itself, already in the letterhead, so no MSWDO line.
import { qrSvg } from './shared-docs';

const TZ = 'Asia/Manila';
const OFFICE = 'Municipal Social Welfare and Development Office';

/** '2026-11-12' -> '12 November 2026', the date as an LGU certificate writes it. */
export function longDate(value) {
    if (!value) return '';
    const s = String(value);
    const bare = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s.slice(0, 10));
    const d = bare ? new Date(Date.UTC(+bare[1], +bare[2] - 1, +bare[3], 12)) : new Date(s);
    if (Number.isNaN(d.getTime())) return s;
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: bare ? 'UTC' : TZ });
}

/** "12th day of November 2026", for the Given line. */
function givenDate(value) {
    const long = longDate(value);
    const m = /^(\d{1,2}) (.+)$/.exec(long);
    if (!m) return long;
    const n = Number(m[1]);
    const suffix = n % 100 >= 11 && n % 100 <= 13 ? 'th' : ({ 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] || 'th');
    return n + suffix + ' day of ' + m[2];
}

const list = (v) => (Array.isArray(v) ? v : (v ? [v] : [])).map((x) => (x && typeof x === 'object' ? (x.name || '') : String(x))).map((x) => x.trim()).filter(Boolean);

/**
 * The catalog's certificate body with the session's details put in; an
 * event's wording says :name (one holder) and :event as well.
 */
export function fillCertificateBody(template, { names, date, venue, event }) {
    return String(template || '')
        .replace(/:names?\b/g, names || '')
        .replace(/:event\b/g, event || 'the event')
        .replace(/:date/g, date || '')
        .replace(/:venue/g, venue || 'the Municipal Social Welfare and Development Office');
}

const EVENT_BODY = 'This is to certify that :name participated in :event held on :date at :venue.';

/**
 * Who signs. The document's own signatories when the server froze them;
 * otherwise its facilitators by the catalog's rule: 'counselor' - the
 * accredited counselor(s) only; 'team' - every facilitator.
 */
function signatoriesOf(fv, rule) {
    const own = Array.isArray(fv.signatories) ? fv.signatories : [];
    let rows = own.length ? own : (Array.isArray(fv.facilitators) ? fv.facilitators : []);
    if (!own.length && rule === 'counselor') {
        const counselors = rows.filter((f) => f && f.role === 'counselor');
        if (counselors.length) rows = counselors;
    }
    if (!rows.length && fv.office_head) rows = [{ name: fv.office_head, position: fv.office_head_position || 'Municipal Social Welfare and Development Officer' }];
    return rows.filter((f) => f && f.name).map((f) => ({
        name: String(f.name),
        position: f.position || '',
        accreditation_no: f.accreditation_no || null,
        accreditation_valid_until: f.accreditation_valid_until || null,
    }));
}

/** The certificate as drawn, from a document and what the opener knows. */
export function buildCertificate(doc, meta = {}, certByKey = {}) {
    const d = doc || {};
    const fv = d.field_values || d.fields || {};
    const key = d.document_type || fv.service_key || meta.service_key || '';
    const tpl = certByKey[key] || {};
    const isEvent = key === 'event_certificate' || !!fv.event_key || meta.source === 'event';
    const holders = list(fv.holder_names || fv.names || meta.holder_names || fv.applicant || meta.applicant);
    const names = holders.join(' and ');
    const session = meta.session || {};
    const date = longDate(fv.session_date || fv.event_date || session.date);
    const venue = fv.venue || fv.session_venue || session.venue || (isEvent ? 'Esperanza, Masbate' : '');
    const event = fv.cert_subtitle || meta.event_title || '';
    const body = fv.body || fillCertificateBody(tpl.body || (isEvent ? EVENT_BODY : 'This is to certify that :names attended the session on :date at :venue.'), { names, date, venue, event });
    const eventOffice = /^municipality of esperanza$/i.test(String(fv.office || '').trim()) ? '' : (fv.office || '');
    // The names stand out in the body, as they are typed in on paper. Split,
    // never x-html: a name is the resident's own text.
    const parts = [];
    if (holders.length) {
        const pattern = new RegExp('(' + holders.map((h) => h.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')', 'g');
        body.split(pattern).filter((t) => t !== '').forEach((t) => parts.push({ text: t, strong: holders.includes(t) }));
    } else {
        parts.push({ text: body, strong: false });
    }
    let host = '';
    try { host = new URL(d.verify_url || meta.verify_url || '').host; } catch { host = ''; }
    return {
        title: fv.cert_title || meta.title || tpl.title || 'Certificate',
        subtitle: fv.cert_subtitle || tpl.subtitle || '',
        names,
        parts,
        office: fv.office_name || (isEvent ? eventOffice : OFFICE),
        signatories: signatoriesOf(fv, tpl.signedBy),
        control: d.control_number || meta.control_number || '',
        code: d.serial || d.verification_code || meta.verification_code || '',
        issued: d.issued_on || meta.issued_on || '',
        issuedLong: longDate(d.issued_on || meta.issued_on),
        given: givenDate(d.issued_on || meta.issued_on),
        verifyUrl: d.verify_url || meta.verify_url || '',
        verifyHost: host ? host + '/verify' : '',
        revoked: !!d.revoked,
        revokedReason: d.revoked_reason || '',
    };
}

// ------------------------------------------------------------ Printing

// The site's print isolation (body.dp-printing and #dp-print-area in
// app.css) shows exactly one #dp-print-area: any other one on the page steps
// aside while the certificate prints, and comes back after.
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

function toast(message, variant = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));
}

/**
 * The certificate window. certByKey: the catalog's certificate per service
 * key; bilingual: the resident's portal (Filipino first), else English.
 */
export function welfareCertificate(certByKey = {}, bilingual = false) {
    return {
        certOpen: false,
        cert: null,
        certQr: '',
        certBusy: false,
        certT(en, fil) {
            return bilingual && window.Alpine?.store('lang')?.current !== 'en' ? fil : en;
        },
        openCertificate(detail) {
            const { doc, meta } = detail || {};
            if (!doc && !meta) return;
            this.cert = buildCertificate(doc, meta || {}, certByKey);
            this.certQr = '';
            this.certOpen = true;
            const url = this.cert.verifyUrl;
            if (url) qrSvg(url, 4).then((svg) => { if (this.cert && this.cert.verifyUrl === url) this.certQr = svg; }).catch(() => { this.certQr = ''; });
            window.Alpine?.nextTick(() => {
                window.renderIcons?.();
                // The page opens at its top (the seal and the title), not
                // wherever the window was last scrolled to.
                for (let el = document.getElementById('welfare-cert-sheet')?.parentElement; el && el !== document.body; el = el.parentElement) {
                    if (el.scrollTop) el.scrollTop = 0;
                }
            });
        },
        printCertificate() {
            const sheet = document.getElementById('welfare-cert-sheet');
            if (!sheet) return;
            const restore = takePrintArea(sheet);
            const done = () => {
                window.removeEventListener('afterprint', done);
                restore();
            };
            window.addEventListener('afterprint', done);
            window.print();
        },
        // As the Dokyu document and the request slip are downloaded: html2pdf
        // on a copy without Alpine, oklch() colours swapped for rgb() meanwhile.
        async downloadCertificate() {
            const sheet = document.getElementById('welfare-cert-sheet');
            if (!sheet || this.certBusy) return;
            this.certBusy = true;
            let html2pdf;
            let withTrueBaseline;
            try {
                [html2pdf, { withTrueBaseline }] = await Promise.all([window.loadHtml2Pdf(), import('./report-pdf')]);
            } catch {
                this.certBusy = false;
                toast(this.certT('Could not load the PDF maker. Check the connection and try again.', 'Hindi ma-load ang gumagawa ng PDF. Suriin ang koneksyon at subukang muli.'), 'error');
                return;
            }
            const editor = window.DokyuEditor || {};
            const restore = takePrintArea(sheet);
            let restoreColors = () => {};
            try {
                await new Promise((resolve) => requestAnimationFrame(() => resolve()));
                if (editor.waitForImages) await editor.waitForImages(sheet);
                if (editor.sanitizeExportColors) restoreColors = editor.sanitizeExportColors(document.body);
                // Text where the browser puts it, not a line low (resources/js/report-pdf.js, 2026-11-21).
                await withTrueBaseline(() => html2pdf().set({
                    margin: 0,
                    filename: (this.cert?.title || 'Certificate').replace(/[^\w-]+/g, '-') + '-' + (this.cert?.control || '') + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, allowTaint: false, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape', compress: true },
                    pagebreak: { mode: ['avoid-all'] },
                }).from(editor.exportClone ? editor.exportClone(sheet) : sheet).save());
            } catch {
                toast(this.certT('Could not make the PDF. Use Print and choose Save as PDF.', 'Hindi magawa ang PDF. Gamitin ang I-print at piliin ang Save as PDF.'), 'error');
            } finally {
                restoreColors();
                restore();
                this.certBusy = false;
            }
        },
    };
}

/** Opens the certificate window from anywhere on the page (on window: the button may be gone). */
export function openWelfareCertificate(doc, meta = {}) {
    window.dispatchEvent(new CustomEvent('welfare-certificate', { detail: { doc, meta } }));
}

export function registerCertificate(Alpine) {
    window.openWelfareCertificate = openWelfareCertificate;
    Alpine.data('welfareCertificate', welfareCertificate);
}
