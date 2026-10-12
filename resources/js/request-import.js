// Dokyu / Tulong -> Import records (owner, 2026-10-12): reading an office's
// own list of documents issued or assistance given, matching its columns and
// its "type" values to the portal's services, and cleaning each row. The file
// itself is read with the resident import's reader (any spreadsheet, CSV,
// JSON, a Word or PDF table); loaded when the import window first opens.

import {
    ACCEPT, ReadError, readFile, parseDate, dateOrderOf, normSex, titleCase, splitName, normContact, matchBarangay,
    fieldForHeader as personFieldFor, downloadCsv, blank, cleanSuffix, SUFFIX_RE, headerKey, loadXlsx,
} from './resident-import.js';

export { ACCEPT, ReadError, readFile, parseDate, dateOrderOf, matchBarangay, downloadCsv };

const PERSON = ['full_name', 'last_name', 'first_name', 'middle_name', 'suffix', 'sex', 'dob', 'barangay', 'sitio', 'contact'];

export const fieldsFor = (type) => [
    { key: 'full_name', label: 'Full name', hint: 'Any order: DELA CRUZ, JUAN P. or Juan P. Dela Cruz' },
    { key: 'last_name', label: 'Last name' },
    { key: 'first_name', label: 'First name' },
    { key: 'middle_name', label: 'Middle name or initial' },
    { key: 'suffix', label: 'Suffix (Jr., III)' },
    { key: 'sex', label: 'Sex' },
    { key: 'dob', label: 'Birth date' },
    { key: 'barangay', label: 'Barangay' },
    { key: 'sitio', label: 'Purok / Sitio' },
    { key: 'contact', label: 'Contact number' },
    { key: 'service', label: type === 'tulong' ? 'Type of assistance' : 'Document' },
    { key: 'date', label: type === 'tulong' ? 'Date given' : 'Date issued' },
    { key: 'date_applied', label: 'Date applied' },
    { key: 'amount', label: type === 'tulong' ? 'Amount given' : 'Fee paid' },
    { key: 'status', label: 'Status' },
    { key: 'source_ref', label: type === 'tulong' ? 'Control / voucher no.' : 'OR / control no.' },
    { key: 'purpose', label: 'Purpose' },
    { key: 'remarks', label: 'Remarks' },
    { key: 'released_to', label: 'Received by' },
];

const SYNONYMS = {
    service: ['type of assistance', 'assistance', 'kind of assistance', 'assistance type', 'type of assistance provided', 'assistance provided', 'uri ng tulong', 'tulong', 'program', 'category',
        'nature of assistance', 'service', 'type of service', 'services', 'document', 'document type', 'type of document', 'documents', 'certificate', 'certificate type', 'document requested',
        'uri ng dokumento', 'kind of document', 'type', 'particulars', 'transaction', 'transaction type', 'dokumento', 'dokumentong hiniling', 'sertipiko', 'uri ng sertipiko'],
    date: ['date', 'date released', 'release date', 'date given', 'date issued', 'issued on', 'date of release', 'date of issuance', 'petsa', 'petsa ng pagbigay', 'date received',
        'date claimed', 'date approved', 'date granted', 'date paid', 'petsa ibinigay', 'date served'],
    date_applied: ['date applied', 'date of application', 'date filed', 'date requested', 'application date', 'date of request', 'petsa ng aplikasyon', 'date submitted'],
    amount: ['amount', 'amount given', 'amount released', 'amount granted', 'amount of assistance', 'halaga', 'cash assistance', 'amount php', 'amount in php', 'amount peso', 'fee', 'fees',
        'amount paid', 'or amount', 'fee paid', 'total', 'total amount', 'bayad', 'halagang ibinayad', 'halagang ibinigay'],
    status: ['status', 'action', 'result', 'outcome', 'action taken', 'approved disapproved', 'remarks status', 'decision'],
    source_ref: ['control no', 'control number', 'reference no', 'reference number', 'ref no', 'voucher no', 'voucher number', 'check no', 'cheque no', 'or no', 'or number', 'o r no',
        'official receipt no', 'official receipt number', 'receipt no', 'transaction no', 'case no', 'record no', 'series no', 'serial no', 'dv no', 'control'],
    purpose: ['purpose', 'reason', 'layunin', 'purpose of request', 'purpose of assistance', 'for', 'diagnosis', 'case'],
    remarks: ['remarks', 'notes', 'note', 'comment', 'comments', 'puna', 'other remarks'],
    released_to: ['released to', 'received by', 'claimant', 'claimed by', 'representative', 'tinanggap ni', 'name of claimant', 'recipient'],
};

/** The field a column holds, from its name. */
export function fieldForHeader(text, type) {
    const h = headerKey(text);
    if (!h) return null;
    for (const [field, words] of Object.entries(SYNONYMS)) if (words.includes(h)) return field;
    const person = personFieldFor(text);
    if (person && PERSON.includes(person)) return person;
    if (/\b(birth|kapanganakan|kaarawan)\b/.test(h)) return 'dob';
    if (/\bdate\b.*\b(appl|filed|request)/.test(h)) return 'date_applied';
    if (/\b(date|petsa)\b/.test(h)) return 'date';
    if (/\b(amount|halaga|php|peso|bayad|fee)\b/.test(h)) return 'amount';
    if (/\b(control|voucher|reference|receipt|or no|serial)\b/.test(h)) return 'source_ref';
    if (/\b(assistance|tulong|document|dokumento|certificate|sertipiko|clearance|program)\b/.test(h)) return 'service';
    if (/\bpurpose\b/.test(h)) return 'purpose';
    if (/\b(beneficiary|claimant|client|applicant|requestor|requester)\b/.test(h) && !/\b(relation|address)\b/.test(h)) return 'full_name';
    return null;
}

/** The row that holds the column names: the one that names the most fields. */
export function guessHeaderRow(rows, type) {
    let best = 0;
    let at = 0;
    rows.slice(0, 25).forEach((row, i) => {
        const hits = new Set(row.map((c) => fieldForHeader(c, type)).filter(Boolean)).size;
        if (hits > best) { best = hits; at = i; }
    });
    return best >= 1 ? at : 0;
}

/** Each column's field, guessed from its name and then from its values. */
export function guessMapping(headers, sampleRows, barangays, services, type) {
    const used = new Set();
    const map = headers.map((h) => {
        const f = fieldForHeader(h, type);
        if (f && !used.has(f)) { used.add(f); return f; }
        return '';
    });
    map.forEach((f, i) => {
        if (f) return;
        const values = sampleRows.map((r) => r[i]).filter(Boolean).slice(0, 40);
        if (values.length < 3) return;
        const share = (test) => values.filter(test).length / values.length;
        const guess =
            !used.has('service') && share((v) => matchService(v, services, type)) > 0.8 ? 'service'
                : !used.has('barangay') && share((v) => matchBarangay(v, barangays)) > 0.8 ? 'barangay'
                    : !used.has('amount') && share((v) => parseAmount(v).amount !== null) > 0.9 && share((v) => /[\d]/.test(v)) > 0.9 ? 'amount'
                        : !used.has('date') && share((v) => parseDate(v, 'mdy').date) > 0.8 ? 'date'
                            : '';
        if (guess) { used.add(guess); map[i] = guess; }
    });
    if (map.includes('first_name') && map.includes('last_name')) {
        const at = map.indexOf('full_name');
        if (at >= 0) map[at] = '';
    }
    if (map.includes('last_name') && !map.includes('first_name') && map.includes('full_name')) {
        map[map.indexOf('full_name')] = 'first_name';
    }
    return map;
}

// ------------------------------------------------------------- Values

const RULES = {
    tulong: [
        [/\btupad\b/, 'tulong_tupad'],
        [/\berpat\b/, 'tulong_erpat'],
        [/\btesda\b|skills? training/, 'tulong_tesda'],
        [/business plan/, 'tulong_business_plan'],
        [/livelihood (business )?mapping/, 'tulong_livelihood_mapping'],
        [/kabuhayan|livelihood/, 'tulong_kabuhayan'],
        [/solo parent/, 'tulong_solo_parent'],
        [/(social )?pension|indigent senior/, 'tulong_pension'],
        [/burial|funeral|libing|pampalibing|death|patay/, 'tulong_burial'],
        [/medical|medicine|hospital|gamot|ospital|laborator|dialysis|chemo|check ?up|operation|therapy/, 'tulong_medical'],
        [/educ|school|tuition|scholar|pag ?aaral|eskwela|estudyante/, 'tulong_educational'],
        [/food|relief|bigas|rice|pagkain|grocer|family food pack|ffp/, 'tulong_food'],
        [/financ|cash|aics|pinansyal|transport|emergency/, 'tulong_financial'],
    ],
    dokyu: [
        [/first ?time ?job|ftjs|11261/, 'brgy_first_time_jobseeker'],
        [/business clearance/, 'brgy_business_clearance'],
        [/barangay business permit|brgy business permit/, 'brgy_business_permit'],
        [/business permit.*renew|renew.*business/, 'dokyu_business_renewal'],
        [/business permit|mayor'?s permit/, 'dokyu_business_new'],
        [/event|special permit/, 'brgy_event_permit'],
        [/cedula|community tax|ctc/, 'dokyu_cedula'],
        [/marriage licen/, 'mcro_marriage_license'],
        [/marriage|kasal/, 'mcro_marriage'],
        [/delayed registration|late registration of birth/, 'mcro_delayed_registration'],
        [/live birth|birth cert|kapanganakan|psa birth/, 'mcro_live_birth'],
        [/certification.*death|registration of death/, 'brgy_cert_death_registration'],
        [/death cert|certificate of death/, 'mcro_death'],
        [/pwd/, 'msw_pwd_id'],
        [/senior citizen|osca/, 'osca_senior_citizen'],
        [/real property|rpt|amilyar/, 'dokyu_rpt'],
        [/locational/, 'locational_clearance'],
        [/pet|dog|aso/, 'municipal_pet_registration'],
        [/national id|psa purpose/, 'brgy_cert_national_id'],
        [/land|property|lupa/, 'brgy_cert_land_property'],
        [/late registration/, 'brgy_cert_late_registration'],
        [/(municipal|mswdo|certificate of) indigen/, 'dokyu_indigency'],
        [/indigen/, 'brgy_indigency'],
        [/(municipal|mcro|certificate of) residen/, 'dokyu_residency'],
        [/residen/, 'brgy_residency'],
        [/certification|cert/, 'brgy_cert_general'],
        [/clearance|brgy clr|bc\b/, 'brgy_clearance'],
    ],
};

const svcKey = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9']+/g, ' ').trim();

/** The service a file's value names, among those this officer may record, or null. */
export function matchService(value, services, type) {
    const v = svcKey(value);
    if (!v) return null;
    const allowed = new Set(services.map((s) => s.key));
    const byName = services.find((s) => svcKey(s.name) === v || s.key === v.replace(/\s+/g, '_'));
    if (byName) return byName.key;
    for (const [re, key] of RULES[type] || []) if (re.test(v) && allowed.has(key)) return key;
    return null;
}

/** "completed" (Released / Completed), "rejected" or "cancelled"; null when not understood. */
export function normStatus(value, type) {
    const v = svcKey(value);
    if (!v) return 'completed';
    if (/\b(cancel|withdr|void|binawi)/.test(v)) return type === 'tulong' ? 'cancelled' : 'rejected';
    if (/\b(reject|denied|deny|disapprov|declin|not approved|hindi|ineligible|not eligible|failed)/.test(v)) return 'rejected';
    if (/\b(releas|issued|complet|given|claimed|paid|approv|grant|done|ok|served|naibigay|natanggap|received|yes|success)/.test(v)) return 'completed';
    return null;
}

/** "₱5,000.00", "PHP 3,000", "1500" -> { amount } or { error }. */
export function parseAmount(value) {
    const s = String(value ?? '').trim();
    if (!s) return { amount: null };
    // A document given free of charge: no fee, not an unreadable amount.
    if (/^(free|libre|gratis|no fee|waived)$/i.test(s)) return { amount: 0 };
    const n = s.replace(/(php|₱|p\b|pesos?)/gi, '').replace(/[,\s]/g, '');
    if (!/^\d+(\.\d{1,2})?$/.test(n)) return { amount: null, error: 'is not an amount' };
    return { amount: Number(n) };
}

/**
 * One row of the file as the API takes it, with what is wrong with it.
 * `opts`: { type, mapping, barangayMap, serviceMap, fixedBarangay, fixedService, dateOrder, surnameFirst }
 */
export function buildRow(cells, index, opts) {
    const get = (field) => {
        const at = opts.mapping.indexOf(field);
        return at < 0 ? '' : blank(cells[at]);
    };
    const issues = [];
    let name = { first_name: get('first_name'), middle_name: get('middle_name'), last_name: get('last_name'), suffix: get('suffix') };
    const full = get('full_name');
    if (full && (!name.first_name || !name.last_name)) {
        const parts = splitName(full, opts.surnameFirst);
        name = {
            first_name: name.first_name || parts.first_name,
            middle_name: name.middle_name || parts.middle_name,
            last_name: name.last_name || parts.last_name,
            suffix: name.suffix || parts.suffix,
        };
    }
    for (const part of ['first_name', 'last_name']) {
        const w = name[part].split(' ');
        if (w.length > 1 && SUFFIX_RE.test(w[w.length - 1])) {
            name.suffix = name.suffix || cleanSuffix(w.pop());
            name[part] = w.join(' ');
        }
    }
    name = { first_name: titleCase(name.first_name), middle_name: titleCase(name.middle_name), last_name: titleCase(name.last_name), suffix: cleanSuffix(name.suffix) };
    if (!name.first_name) issues.push('First name is missing');
    if (!name.last_name) issues.push('Last name is missing');

    let dob = null;
    const rawDob = get('dob');
    if (rawDob) {
        const p = parseDate(rawDob, opts.dateOrder);
        if (!p.error && p.date < new Date().toISOString().slice(0, 10)) dob = p.date;
    }

    let barangay = opts.fixedBarangay || '';
    if (!barangay) {
        const raw = get('barangay');
        barangay = raw ? (opts.barangayMap[raw] ?? '') : '';
        if (!raw) issues.push('Barangay is missing');
        else if (!barangay) issues.push('Barangay "' + raw.slice(0, 40) + '" is not matched to a barangay');
    }

    let service = opts.fixedService || '';
    const rawService = get('service');
    if (!service) {
        service = rawService ? (opts.serviceMap[rawService] ?? '') : '';
        const what = opts.type === 'tulong' ? 'Type of assistance' : 'Document';
        if (!rawService) issues.push(what + ' is missing');
        else if (!service) issues.push(what + ' "' + rawService.slice(0, 40) + '" is not matched to a service');
    }

    let date = null;
    const rawDate = get('date');
    if (!rawDate) issues.push((opts.type === 'tulong' ? 'Date given' : 'Date issued') + ' is missing');
    else {
        const p = parseDate(rawDate, opts.dateOrder);
        if (p.error) issues.push('Date "' + rawDate.slice(0, 30) + '" ' + p.error);
        else if (p.date > new Date().toISOString().slice(0, 10) || p.date < '1990-01-01') issues.push('Date ' + p.date + ' is outside 1990 to today');
        else date = p.date;
    }
    let dateApplied = null;
    const rawApplied = get('date_applied');
    if (rawApplied) {
        const p = parseDate(rawApplied, opts.dateOrder);
        if (!p.error && (!date || p.date <= date)) dateApplied = p.date;
    }

    const amount = parseAmount(get('amount'));
    if (amount.error) issues.push('Amount "' + get('amount').slice(0, 20) + '" ' + amount.error);
    const status = normStatus(get('status'), opts.type);
    if (status === null) issues.push('Status "' + get('status').slice(0, 30) + '" is not understood (Released, Rejected' + (opts.type === 'tulong' ? ', Cancelled' : '') + ')');

    return {
        row: index,
        ...name,
        sex: normSex(get('sex')),
        dob,
        barangay,
        sitio: titleCase(get('sitio')) || null,
        contact: normContact(get('contact')),
        service_key: service || null,
        service_raw: rawService,
        status: status || 'completed',
        date,
        date_applied: dateApplied,
        amount: amount.amount,
        source_ref: get('source_ref') || null,
        purpose: get('purpose') || null,
        remarks: get('remarks') || null,
        released_to: titleCase(get('released_to')) || null,
        issues,
    };
}

/** The Excel template for this type: its columns, two example rows, and how to fill it. */
export async function downloadTemplate(type, barangays, services) {
    const XLSX = await loadXlsx();
    const tulong = type === 'tulong';
    const headers = ['Last Name', 'First Name', 'Middle Name', 'Suffix', 'Birth Date', 'Barangay', tulong ? 'Type of Assistance' : 'Document', tulong ? 'Date Given' : 'Date Issued',
        tulong ? 'Amount' : 'Fee Paid', tulong ? 'Control No.' : 'OR No.', 'Purpose', 'Status'];
    const brgy = barangays[0] || 'Poblacion';
    const svc = services[0]?.name || (tulong ? 'Medical Assistance (AICS)' : 'Barangay Clearance');
    const example = [
        ['Dela Cruz', 'Juan', 'Santos', '', '1980-03-14', brgy, svc, '2025-03-10', tulong ? '5000' : '50', tulong ? 'AICS-2025-0001' : 'OR 1234567', tulong ? 'Hospital bill' : 'Employment', tulong ? 'Released' : 'Issued'],
        ['Dela Cruz', 'Maria', 'Reyes', '', '1982-07-02', brgy, svc, '2025-04-02', tulong ? '3000' : '50', tulong ? 'AICS-2025-0002' : 'OR 1234568', tulong ? 'Medicines' : 'Bank requirement', tulong ? 'Released' : 'Issued'],
    ];
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([headers, ...example]);
    ws['!cols'] = headers.map((h) => ({ wch: Math.max(12, h.length + 2) }));
    XLSX.utils.book_append_sheet(wb, ws, tulong ? 'Tulong records' : 'Dokyu records');
    const notes = [
        ['How to fill in this template'],
        ['One ' + (tulong ? 'assistance given' : 'document issued') + ' per row. Delete the two example rows before importing.'],
        ['Needed: Last Name, First Name, Barangay, ' + (tulong ? 'Type of Assistance and Date Given' : 'Document and Date Issued') + '. Fill in what else you have.'],
        ['Birth Date helps match the record to the right resident when two people share a name.'],
        ['Status: ' + (tulong ? 'Released (or Completed), Rejected or Cancelled' : 'Issued (or Released) or Rejected') + '. Blank means ' + (tulong ? 'Released' : 'Issued') + '.'],
        [(tulong ? 'Type of Assistance' : 'Document') + ' should be one of:'],
        ...services.map((s) => ['   ' + s.name]),
        ['Barangay must be one of:'],
        ...barangays.map((b) => ['   ' + b]),
    ];
    const ns = XLSX.utils.aoa_to_sheet(notes);
    ns['!cols'] = [{ wch: 110 }];
    XLSX.utils.book_append_sheet(wb, ns, 'Instructions');
    XLSX.writeFile(wb, 'Esperanza ' + (tulong ? 'Tulong' : 'Dokyu') + ' records import template.xlsx', { compression: true });
}
