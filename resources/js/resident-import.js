// Constituents -> Import from a file (owner, 2026-10-09): read the LGU's own
// resident lists in the browser and turn them into rows the API takes.
//
// Loaded only when the import window opens. The file never leaves the
// officer's computer: it is read here, the officer matches its columns, and
// only the mapped rows are sent (POST /admin/resident-imports/...).
//
// Reads every spreadsheet SheetJS does (Excel old and new, OpenDocument,
// Numbers, CSV/TSV/TXT, HTML and XML tables, dBASE), JSON, tables in Word
// (.docx) and OpenDocument text (.odt), and the text of a PDF that has a
// table in it. A scanned page or a photo has no text to read; the window says
// so and offers the template instead.

const XLSX_TYPES = ['xlsx', 'xlsm', 'xlsb', 'xls', 'xlt', 'xltx', 'xltm', 'ods', 'fods', 'numbers', 'csv', 'tsv', 'txt', 'prn', 'tab', 'dif', 'slk', 'sylk', 'dbf', 'html', 'htm', 'xml', 'wk1', 'wk3', 'wks', 'qpw', 'eth', 'et'];
const TEXT_TYPES = ['csv', 'tsv', 'txt', 'prn', 'tab'];
const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'bmp', 'tif', 'tiff'];

export const ACCEPT = '.xlsx,.xlsm,.xlsb,.xls,.xlt,.xltx,.ods,.fods,.numbers,.csv,.tsv,.txt,.prn,.tab,.dif,.slk,.dbf,.html,.htm,.xml,.json,.docx,.odt,.pdf,.doc,.rtf,.jpg,.jpeg,.png,.heic';

let xlsxPromise = null;
export const loadXlsx = () => (xlsxPromise ??= import('xlsx'));

export class ReadError extends Error {}

// ------------------------------------------------------------- Reading

/**
 * Every table in the file: [{ name, rows: string[][] }]. A spreadsheet gives
 * one per sheet, a Word file one per table, a PDF one for its text.
 */
export async function readFile(file) {
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (IMAGE_TYPES.includes(ext)) throw new ReadError('This is a picture. A photo or scan of a paper list has no text the portal can read: type the list into the template (Download the template) and choose that instead.');
    if (ext === 'doc' || ext === 'rtf') throw new ReadError('Open this file in Word and save it as a Word Document (.docx), then choose the .docx here.');
    if (file.size > 40 * 1024 * 1024) throw new ReadError('This file is larger than 40 MB. Split it into smaller files, one barangay each, and import them one by one.');

    const buffer = await file.arrayBuffer();
    let tables;
    if (ext === 'json') tables = readJson(decodeText(buffer));
    else if (ext === 'docx') tables = await readDocx(buffer);
    else if (ext === 'odt') tables = await readOdt(buffer);
    else if (ext === 'pdf') tables = await readPdf(buffer);
    else if (XLSX_TYPES.includes(ext) || ext === '') tables = await readSpreadsheet(buffer, ext);
    else throw new ReadError('The portal cannot read .' + ext + ' files. Save the list as Excel (.xlsx) or CSV and choose that.');

    tables = tables
        .map((t) => ({ name: t.name, ...trimRows(t.rows, t.lines) }))
        .filter((t) => t.rows.length > 0);
    if (!tables.length) throw new ReadError('No rows were found in this file. If it is a scanned document, type the list into the template instead.');
    return { format: ext || 'txt', tables };
}

function decodeText(buffer) {
    // Excel's "Unicode Text" (.txt) is UTF-16, with or without the mark that
    // says so; read as UTF-8 it was letters between nulls (live test
    // 2026-10-12). Every other character of a Latin-script list is a null.
    const head = new Uint8Array(buffer, 0, Math.min(buffer.byteLength, 4096));
    if (head[0] === 0xff && head[1] === 0xfe) return new TextDecoder('utf-16le').decode(buffer);
    if (head[0] === 0xfe && head[1] === 0xff) return new TextDecoder('utf-16be').decode(buffer);
    let odd = 0;
    let even = 0;
    for (let i = 0; i < head.length; i++) {
        if (head[i] !== 0) continue;
        if (i % 2) odd++; else even++;
    }
    if (odd > head.length / 4) return new TextDecoder('utf-16le').decode(buffer);
    if (even > head.length / 4) return new TextDecoder('utf-16be').decode(buffer);
    try {
        return new TextDecoder('utf-8', { fatal: true }).decode(buffer).replace(/^﻿/, '');
    } catch {
        // Saved by an older Excel: the Windows code page (ñ, é).
        return new TextDecoder('windows-1252').decode(buffer);
    }
}

async function readSpreadsheet(buffer, ext) {
    const XLSX = await loadXlsx();
    let wb;
    try {
        if (TEXT_TYPES.includes(ext)) {
            const text = decodeText(buffer);
            wb = XLSX.read(text, { type: 'string', raw: true, FS: delimiterOf(text), dense: true });
        } else {
            // A workbook that is a zip inside (xlsx, ods, numbers) and is not
            // one: SheetJS would read the bytes as text and show nonsense
            // (live test 2026-10-12, a damaged .xlsx).
            if (['xlsx', 'xlsm', 'xlsb', 'xltx', 'xltm', 'ods', 'numbers'].includes(ext)) {
                try { XLSX.CFB.read(new Uint8Array(buffer), { type: 'array' }); } catch {
                    throw new ReadError('This file is damaged or is not really a spreadsheet. Open it in Excel and save it again, or save it as CSV, and choose that.');
                }
            }
            wb = XLSX.read(new Uint8Array(buffer), { type: 'array', cellDates: false, cellNF: true, dense: true });
        }
    } catch (e) {
        if (e instanceof ReadError) throw e;
        if (/password|encrypt/i.test(String(e?.message))) throw new ReadError('This file is protected with a password. Open it, remove the password (or save an unprotected copy), and choose it again.');
        throw new ReadError('The portal could not read this file (' + (e?.message || 'unknown format') + '). Save it as Excel (.xlsx) or CSV and try again.');
    }
    return wb.SheetNames
        .filter((name) => !wb.Workbook?.Sheets?.find((s) => s.name === name && s.Hidden))
        .map((name) => ({ name, ...sheetRows(XLSX, wb.Sheets[name]) }));
}

function delimiterOf(text) {
    const sample = text.split(/\r?\n/).slice(0, 20).join('\n');
    const counts = { '\t': 0, ',': 0, ';': 0, '|': 0 };
    for (const ch of sample) if (ch in counts) counts[ch]++;
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][1] > 0 ? Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0] : ',';
}

// Cells as text, with a date cell as YYYY-MM-DD read from its serial number,
// so no time zone can move a birthday by a day.
function sheetRows(XLSX, sheet) {
    if (!sheet || !sheet['!ref']) return { rows: [], lines: [] };
    const range = XLSX.utils.decode_range(sheet['!ref']);
    const dense = sheet['!data'];
    const rows = [];
    const lines = [];
    const lastRow = Math.min(range.e.r, range.s.r + 60000);
    for (let r = range.s.r; r <= lastRow; r++) {
        const row = [];
        for (let c = range.s.c; c <= Math.min(range.e.c, range.s.c + 200); c++) {
            const cell = dense ? dense[r]?.[c] : sheet[XLSX.utils.encode_cell({ r, c })];
            row.push(cellText(XLSX, cell));
        }
        rows.push(row);
        lines.push(r + 1);
    }
    // A merged cell (a household number down its members' rows, a heading
    // across its sub-columns) holds its value in its first cell only.
    for (const m of sheet['!merges'] || []) {
        const value = rows[m.s.r - range.s.r]?.[m.s.c - range.s.c] ?? '';
        if (!value || (m.e.r - m.s.r) * (m.e.c - m.s.c + 1) > 5000) continue;
        for (let r = m.s.r; r <= m.e.r; r++) {
            for (let c = m.s.c; c <= m.e.c; c++) {
                const row = rows[r - range.s.r];
                if (row && c - range.s.c < row.length && !row[c - range.s.c]) row[c - range.s.c] = value;
            }
        }
    }
    return { rows, lines };
}

function cellText(XLSX, cell) {
    if (!cell || cell.v === undefined || cell.v === null) return '';
    if (cell.t === 'd' && cell.v instanceof Date) return isoOf(cell.v.getFullYear(), cell.v.getMonth() + 1, cell.v.getDate());
    if (cell.t === 'n' && cell.z && XLSX.SSF.is_date(cell.z)) {
        const d = XLSX.SSF.parse_date_code(cell.v);
        if (d && d.y > 1800) return isoOf(d.y, d.m, d.d);
    }
    if (cell.t === 'n') {
        // A mobile number stored as a number lost its 0; an integer keeps no ".0".
        return Number.isInteger(cell.v) ? String(cell.v) : (cell.w ?? String(cell.v));
    }
    if (cell.t === 'b') return cell.v ? 'TRUE' : 'FALSE';
    return String(cell.w ?? cell.v);
}

function readJson(text) {
    let data;
    try { data = JSON.parse(text); } catch { throw new ReadError('This JSON file is not valid JSON.'); }
    if (!Array.isArray(data) && data && typeof data === 'object') {
        data = data.data || data.rows || data.records || data.residents || data.items || Object.values(data).find(Array.isArray) || [];
    }
    if (!Array.isArray(data) || !data.length) throw new ReadError('No list of residents was found in this JSON file.');
    if (Array.isArray(data[0])) return [{ name: 'JSON', rows: data.map((r) => r.map(plain)) }];
    const headers = [...new Set(data.flatMap((o) => (o && typeof o === 'object' ? Object.keys(o) : [])))];
    return [{ name: 'JSON', rows: [headers, ...data.map((o) => headers.map((h) => plain(o?.[h])))] }];
}

const plain = (v) => (v === null || v === undefined ? '' : typeof v === 'object' ? Object.values(v).filter((x) => typeof x !== 'object').join(' ') : String(v));

async function unzip(buffer) {
    const XLSX = await loadXlsx();
    try {
        return XLSX.CFB.read(new Uint8Array(buffer), { type: 'array' });
    } catch {
        throw new ReadError('The portal could not open this document. Save it again from Word (or as Excel/CSV) and try again.');
    }
}

function zipText(XLSX, zip, path) {
    // CFB.find wants a part in a folder as "/word/document.xml".
    const entry = XLSX.CFB.find(zip, '/' + path);
    return entry ? new TextDecoder('utf-8').decode(entry.content) : null;
}

async function readDocx(buffer) {
    const XLSX = await loadXlsx();
    const xml = zipText(XLSX, await unzip(buffer), 'word/document.xml');
    if (!xml) throw new ReadError('This Word file has no document inside it.');
    const doc = new DOMParser().parseFromString(xml, 'application/xml');
    const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    const kids = (el, name) => [...el.childNodes].filter((n) => n.localName === name && n.namespaceURI === W);
    const nested = (el) => { for (let p = el.parentNode; p; p = p.parentNode) if (p.localName === 'tbl') return true; return false; };
    const tables = [...doc.getElementsByTagNameNS(W, 'tbl')].filter((t) => !nested(t));
    if (!tables.length) throw new ReadError('There is no table in this Word file. The portal reads a list laid out as a table (one resident per row); put the list in a table, or in the template, and try again.');
    return tables.map((tbl, i) => {
        const rows = [];
        for (const tr of kids(tbl, 'tr')) {
            const row = [];
            for (const tc of kids(tr, 'tc')) {
                const props = kids(tc, 'tcPr')[0];
                const span = Number(props?.getElementsByTagNameNS(W, 'gridSpan')[0]?.getAttributeNS(W, 'val') || 1);
                const vmerge = props?.getElementsByTagNameNS(W, 'vMerge')[0];
                let text = kids(tc, 'p').map((p) => [...p.getElementsByTagNameNS(W, 't')].map((t) => t.textContent).join('')).join(' ').trim();
                // A cell merged down from the row above repeats its value.
                if (vmerge && vmerge.getAttributeNS(W, 'val') !== 'restart' && !text) text = rows[rows.length - 1]?.[row.length] ?? '';
                row.push(text, ...Array(Math.max(0, span - 1)).fill(''));
            }
            rows.push(row);
        }
        return { name: 'Table ' + (i + 1), rows };
    });
}

async function readOdt(buffer) {
    const XLSX = await loadXlsx();
    const xml = zipText(XLSX, await unzip(buffer), 'content.xml');
    if (!xml) throw new ReadError('This document has no content inside it.');
    const doc = new DOMParser().parseFromString(xml, 'application/xml');
    const T = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    const tables = [...doc.getElementsByTagNameNS(T, 'table')];
    if (!tables.length) throw new ReadError('There is no table in this document. Put the list in a table, or in the template, and try again.');
    return tables.map((tbl, i) => ({
        name: tbl.getAttributeNS(T, 'name') || 'Table ' + (i + 1),
        rows: [...tbl.getElementsByTagNameNS(T, 'table-row')].map((tr) => [...tr.childNodes]
            .filter((c) => c.namespaceURI === T && /table-cell$/.test(c.localName))
            .flatMap((c) => Array(Math.min(50, Number(c.getAttributeNS(T, 'number-columns-repeated') || 1))).fill(c.textContent.trim()))),
    }));
}

// A PDF has no table, only words placed on a page. Lines are the words that
// share a height; columns start where the header line's words start.
async function readPdf(buffer) {
    const pdfjs = await window.loadPdfJs();
    let pdf;
    try {
        pdf = await pdfjs.getDocument({ data: new Uint8Array(buffer), isEvalSupported: false, enableXfa: false }).promise;
    } catch (e) {
        if (/password/i.test(String(e?.name) + String(e?.message))) throw new ReadError('This PDF is protected with a password. Save an unprotected copy and try again.');
        throw new ReadError('The portal could not open this PDF.');
    }
    const lines = [];
    for (let n = 1; n <= Math.min(pdf.numPages, 300); n++) {
        const page = await pdf.getPage(n);
        const content = await page.getTextContent();
        const items = content.items.filter((it) => it.str && it.str.trim()).map((it) => ({ x: it.transform[4], y: it.transform[5], w: it.width, s: it.str.trim() }));
        items.sort((a, b) => b.y - a.y || a.x - b.x);
        for (const it of items) {
            const line = lines.length && lines[lines.length - 1].page === n && Math.abs(lines[lines.length - 1].y - it.y) < 3 ? lines[lines.length - 1] : null;
            if (line) line.items.push(it);
            else lines.push({ page: n, y: it.y, items: [it] });
        }
    }
    if (!lines.length) throw new ReadError('This PDF is a scanned picture: it has no text the portal can read. Type the list into the template (Download the template), or use the spreadsheet the PDF was made from.');
    lines.forEach((l) => l.items.sort((a, b) => a.x - b.x));

    // The header: the line that names the most known columns.
    let header = null;
    let best = 1;
    for (const line of lines.slice(0, 80)) {
        const hits = line.items.filter((it) => fieldForHeader(it.s)).length;
        if (hits > best) { best = hits; header = line; }
    }
    if (!header) {
        return [{ name: 'PDF text', rows: lines.map((l) => mergeClose(l.items).map((it) => it.s)) }];
    }
    const starts = mergeClose(header.items).map((it) => it.x);
    const rows = [];
    for (const line of lines) {
        const cells = Array(starts.length).fill('');
        for (const it of line.items) {
            let col = 0;
            for (let c = 0; c < starts.length; c++) if (it.x >= starts[c] - 6) col = c;
            cells[col] = (cells[col] ? cells[col] + ' ' : '') + it.s;
        }
        // A name that wrapped onto a second line: only one or two cells, under
        // a full row. It belongs to the row above.
        const filled = cells.filter(Boolean).length;
        if (rows.length > 1 && filled <= 2 && filled < starts.length / 3 && line !== header) {
            const prev = rows[rows.length - 1];
            cells.forEach((v, c) => { if (v) prev[c] = (prev[c] ? prev[c] + ' ' : '') + v; });
            continue;
        }
        rows.push(cells);
    }
    return [{ name: 'PDF table', rows }];
}

// Words of one heading ("Date of" "Birth") that sit close together are one cell.
function mergeClose(items) {
    const out = [];
    for (const it of items) {
        const last = out[out.length - 1];
        if (last && it.x - (last.x + last.w) < 4) {
            last.s += ' ' + it.s;
            last.w = it.x + it.w - last.x;
        } else out.push({ ...it });
    }
    return out;
}

// Empty rows go, but every row keeps the number it has in the file, so "row
// 6" in the review is row 6 in Excel (live test 2026-10-12: a blank line
// under the title moved every number up by one).
function trimRows(rows, lines) {
    const kept = [];
    rows.forEach((r, i) => {
        const clean = r.map((v) => String(v ?? '').replace(/\s+/g, ' ').trim());
        if (clean.some(Boolean)) kept.push([clean, lines ? lines[i] : i + 1]);
    });
    const width = Math.max(0, ...kept.map(([r]) => r.reduce((w, v, i) => (v ? i + 1 : w), 0)));
    return {
        rows: kept.map(([r]) => r.slice(0, width).concat(Array(Math.max(0, width - r.length)).fill(''))),
        lines: kept.map(([, line]) => line),
    };
}

// ------------------------------------------------------------- Columns

export const FIELDS = [
    { key: 'full_name', label: 'Full name', hint: 'Any order: DELA CRUZ, JUAN P. or Juan P. Dela Cruz' },
    { key: 'last_name', label: 'Last name' },
    { key: 'first_name', label: 'First name' },
    { key: 'middle_name', label: 'Middle name or initial' },
    { key: 'suffix', label: 'Suffix (Jr., III)' },
    { key: 'sex', label: 'Sex' },
    { key: 'dob', label: 'Birth date' },
    { key: 'civil_status', label: 'Civil status' },
    { key: 'barangay', label: 'Barangay' },
    { key: 'sitio', label: 'Purok / Sitio' },
    { key: 'address', label: 'House no. / Street' },
    { key: 'contact', label: 'Contact number' },
    { key: 'occupation', label: 'Occupation' },
    { key: 'education', label: 'Education' },
    { key: 'income_range', label: 'Monthly income' },
    { key: 'relation', label: 'Relationship to head' },
    { key: 'household_key', label: 'Household number' },
];

const SYNONYMS = {
    full_name: ['name', 'full name', 'fullname', 'complete name', 'pangalan', 'buong pangalan', 'resident', 'resident name', 'name of resident', 'name of residents', 'residents name', 'name of household member', 'household member', 'member name', 'name of member', 'beneficiary', 'beneficiary name', 'name of beneficiary', 'pangalan ng residente', 'inhabitant', 'name of inhabitant', 'name surname first name middle name', 'last name first name middle name', 'surname first name middle name'],
    last_name: ['last name', 'lastname', 'surname', 'family name', 'apelyido', 'apellido', 'lname', 'last', 'apelido'],
    first_name: ['first name', 'firstname', 'given name', 'givenname', 'fname', 'first', 'unang pangalan', 'christian name'],
    middle_name: ['middle name', 'middlename', 'mname', 'mi', 'm i', 'middle initial', 'middle', 'gitnang pangalan', 'middle name initial'],
    suffix: ['suffix', 'ext', 'extension', 'name extension', 'ext name', 'extension name', 'jr sr', 'qualifier', 'name ext'],
    sex: ['sex', 'gender', 'kasarian', 'm f', 'male female', 'sex m f'],
    dob: ['birth date', 'birthdate', 'date of birth', 'dob', 'birthday', 'kaarawan', 'petsa ng kapanganakan', 'bday', 'b day', 'date born', 'born', 'birth', 'date of birth mm dd yyyy', 'birthdate mm dd yyyy', 'kapanganakan'],
    civil_status: ['civil status', 'marital status', 'katayuang sibil', 'cs', 'civil', 'katayuan'],
    barangay: ['barangay', 'brgy', 'bgy', 'barangay name', 'brgy name', 'barrio'],
    sitio: ['purok', 'sitio', 'zone', 'purok sitio', 'sitio purok', 'purok no', 'purok name', 'zone purok'],
    address: ['address', 'house no', 'house number', 'street', 'house no street', 'tirahan', 'lot blk', 'block lot', 'complete address', 'residence address', 'home address', 'house no and street', 'house no street name', 'street name'],
    contact: ['contact', 'contact no', 'contact number', 'mobile', 'mobile no', 'mobile number', 'cellphone', 'cellphone no', 'cp no', 'cp', 'cell no', 'phone', 'phone no', 'telephone', 'tel no', 'numero'],
    occupation: ['occupation', 'trabaho', 'hanapbuhay', 'work', 'job', 'profession', 'employment', 'source of livelihood', 'livelihood'],
    education: ['education', 'educational attainment', 'highest educational attainment', 'edukasyon', 'highest grade completed', 'educ attainment', 'educ', 'highest education', 'natapos na pag aaral', 'pinakamataas na natapos', 'antas ng pinag aralan', 'pinag aralan', 'natapos'],
    income_range: ['income', 'monthly income', 'average monthly income', 'kita', 'buwanang kita', 'income range', 'monthly family income'],
    relation: ['relationship', 'relation', 'relationship to head', 'relationship to household head', 'rel to head', 'relasyon', 'relation to head', 'relationship to the head', 'role in household', 'position in household', 'relasyon sa puno', 'relasyon sa puno ng pamilya', 'relasyon sa puno ng sambahayan', 'kaugnayan sa puno', 'kaugnayan'],
    household_key: ['household no', 'household number', 'hh no', 'hh number', 'hh id', 'household id', 'hh', 'household', 'hh code', 'household code', 'household serial no', 'family no', 'family number', 'hh num', 'household num', 'household ref'],
};

export const headerKey = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();

export function fieldForHeader(text) {
    const h = headerKey(text);
    if (!h) return null;
    for (const [field, words] of Object.entries(SYNONYMS)) if (words.includes(h)) return field;
    // "Name (Surname, First, Middle)" and the like
    if (/^(name|pangalan)\b/.test(h) && !/barangay|purok|street|household|mother|father|spouse/.test(h)) return 'full_name';
    if (/\b(birth|kapanganakan|kaarawan)\b/.test(h) && !/place|lugar/.test(h)) return 'dob';
    if (/\bsurname\b|\blast name\b/.test(h)) return 'last_name';
    if (/\bfirst name\b|\bgiven name\b/.test(h)) return 'first_name';
    if (/\bmiddle\b/.test(h)) return 'middle_name';
    if (/\bhousehold (no|number|id|code)\b|\bhh (no|number|id|code)\b/.test(h)) return 'household_key';
    if (/\bcontact|mobile|cellphone/.test(h)) return 'contact';
    if (/\beducation|attainment|pag aaral|pinag aralan|\bnatapos\b/.test(h)) return 'education';
    if (/\btirahan\b|\bkalye\b|\baddress\b|\bhouse (no|number)\b|\bstreet\b|\bbahay blg\b/.test(h)) return 'address';
    if (/\bincome\b/.test(h)) return 'income_range';
    if (/\brelation|\brelasyon\b|\bkaugnayan\b/.test(h)) return 'relation';
    if (/\bcivil\b|\bmarital\b/.test(h)) return 'civil_status';
    return null;
}

/** The row that holds the column names: the one that names the most fields. */
export function guessHeaderRow(rows) {
    let best = 0;
    let at = 0;
    rows.slice(0, 25).forEach((row, i) => {
        const hits = new Set(row.map(fieldForHeader).filter(Boolean)).size;
        if (hits > best) { best = hits; at = i; }
    });
    return best >= 1 ? at : 0;
}

/** Each column's field, guessed from its name and then from its values. */
export function guessMapping(headers, sampleRows, barangays) {
    const used = new Set();
    const map = headers.map((h) => {
        const f = fieldForHeader(h);
        if (f && !used.has(f)) { used.add(f); return f; }
        return '';
    });
    map.forEach((f, i) => {
        if (f) return;
        const values = sampleRows.map((r) => r[i]).filter(Boolean).slice(0, 40);
        if (values.length < 3) return;
        const share = (test) => values.filter(test).length / values.length;
        const guess =
            !used.has('dob') && share((v) => parseDate(v, 'mdy').date) > 0.8 ? 'dob'
                : !used.has('sex') && share((v) => normSex(v)) > 0.9 && values.every((v) => v.length <= 7) ? 'sex'
                    : !used.has('barangay') && share((v) => matchBarangay(v, barangays)) > 0.8 ? 'barangay'
                        : !used.has('civil_status') && share((v) => CIVIL[headerKey(v)]) > 0.8 ? 'civil_status'
                            : !used.has('contact') && share((v) => /^(\+?63|0)?9\d{9}$/.test(v.replace(/[\s-]/g, ''))) > 0.8 ? 'contact'
                                : '';
        if (guess) { used.add(guess); map[i] = guess; }
    });
    // A full name AND its parts: the parts win, they need no guessing.
    if (map.includes('first_name') && map.includes('last_name')) {
        const at = map.indexOf('full_name');
        if (at >= 0) map[at] = '';
    }
    // The DILG RBI form: Apelyido | Pangalan | Gitnang Pangalan. Beside a
    // surname column, "Pangalan" (or "Name") is the given name, not the whole
    // name: read whole, "MA. LUISA" lost its "Luisa".
    if (map.includes('last_name') && !map.includes('first_name') && map.includes('full_name')) {
        map[map.indexOf('full_name')] = 'first_name';
    }
    return map;
}

// ------------------------------------------------------------- Values

const isoOf = (y, m, d) => String(y).padStart(4, '0') + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');

const MONTHS = {
    jan: 1, january: 1, enero: 1, feb: 2, february: 2, pebrero: 2, mar: 3, march: 3, marso: 3, apr: 4, april: 4, abril: 4,
    may: 5, mayo: 5, jun: 6, june: 6, hunyo: 6, jul: 7, july: 7, hulyo: 7, aug: 8, august: 8, agosto: 8, sep: 9, sept: 9, september: 9, setyembre: 9, septiyembre: 9,
    oct: 10, october: 10, oktubre: 10, nov: 11, november: 11, nobyembre: 11, dec: 12, december: 12, disyembre: 12,
};

function validDate(y, m, d) {
    if (y < 100) y += y > (new Date().getFullYear() % 100) ? 1900 : 2000;
    const dt = new Date(Date.UTC(y, m - 1, d));
    if (dt.getUTCFullYear() !== y || dt.getUTCMonth() !== m - 1 || dt.getUTCDate() !== d) return null;
    return isoOf(y, m, d);
}

/**
 * A birth date as YYYY-MM-DD. `order` says how a date with slashes is
 * written: 'mdy' (03/14/1985, the Philippine and US way) or 'dmy'.
 * Returns { date } or { error }.
 */
export function parseDate(value, order = 'mdy') {
    const v = String(value || '').trim();
    if (!v) return { date: null };
    let m;
    if ((m = v.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})(?:[ T].*)?$/))) {
        const d = validDate(+m[1], +m[2], +m[3]);
        return d ? { date: d } : { error: 'is not a date' };
    }
    if ((m = v.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{2}|\d{4})$/))) {
        // A number over 12 can only be the day, whatever the column's order:
        // a day-first list with one month-first row (01/13/1990) read it as
        // month 13 (live test 2026-10-12). The order decides the rest.
        const [a, b] = [+m[1], +m[2]];
        const dayFirst = a > 12 && b <= 12 ? true : b > 12 && a <= 12 ? false : order === 'dmy';
        const d = dayFirst ? validDate(+m[3], b, a) : validDate(+m[3], a, b);
        return d ? { date: d } : { error: 'is not a date' };
    }
    if ((m = v.match(/^([A-Za-z]+)\.?\s+(\d{1,2}),?\s+(\d{2,4})$/))) { // March 14, 1985
        const mo = MONTHS[m[1].toLowerCase()];
        const d = mo && validDate(+m[3], mo, +m[2]);
        return d ? { date: d } : { error: 'is not a date' };
    }
    if ((m = v.match(/^(\d{1,2})[\s-]+([A-Za-z]+)\.?[\s,-]+(\d{2,4})$/))) { // 14 March 1985, 14-Mar-85
        const mo = MONTHS[m[2].toLowerCase()];
        const d = mo && validDate(+m[3], mo, +m[1]);
        return d ? { date: d } : { error: 'is not a date' };
    }
    if (/^\d{5}(\.\d+)?$/.test(v)) { // an Excel serial that lost its date format
        const n = Math.floor(+v);
        if (n > 300 && n < 80000) {
            const dt = new Date(Date.UTC(1899, 11, 30) + n * 86400000);
            return { date: isoOf(dt.getUTCFullYear(), dt.getUTCMonth() + 1, dt.getUTCDate()) };
        }
    }
    if (/^\d{8}$/.test(v)) { // 19850314
        const d = validDate(+v.slice(0, 4), +v.slice(4, 6), +v.slice(6, 8));
        if (d) return { date: d };
    }
    return { error: 'is not a date' };
}

/** Whether slash dates in a column only make sense one way round. */
export function dateOrderOf(values) {
    let mdy = 0;
    let dmy = 0;
    for (const v of values) {
        const m = String(v || '').trim().match(/^(\d{1,2})[-/.](\d{1,2})[-/.]\d{2,4}$/);
        if (!m) continue;
        if (+m[1] > 12) dmy++;
        if (+m[2] > 12) mdy++;
    }
    return { mdy, dmy, ambiguous: mdy === 0 && dmy === 0 ? values.some((v) => /^\d{1,2}[-/.]\d{1,2}[-/.]\d{2,4}$/.test(String(v || '').trim())) : false };
}

export function normSex(value) {
    const s = headerKey(value);
    if (['m', 'male', 'lalaki', 'l', 'boy', 'man'].includes(s)) return 'Male';
    if (['f', 'female', 'babae', 'b', 'girl', 'woman'].includes(s)) return 'Female';
    return null;
}

const CIVIL = {
    single: 'Single', s: 'Single', 'walang asawa': 'Single', binata: 'Single', dalaga: 'Single', 'never married': 'Single',
    married: 'Married', m: 'Married', kasal: 'Married', 'may asawa': 'Married',
    widowed: 'Widowed', widow: 'Widowed', widower: 'Widowed', w: 'Widowed', balo: 'Widowed', biyudo: 'Widowed', biyuda: 'Widowed', byuda: 'Widowed', byudo: 'Widowed',
    separated: 'Separated', hiwalay: 'Separated', 'legally separated': 'Separated',
    annulled: 'Annulled', annul: 'Annulled', divorced: 'Annulled',
    'live in': 'Live-in', livein: 'Live-in', 'common law': 'Live-in', cohabiting: 'Live-in', 'li': 'Live-in',
};

const RELATIONS = [
    [/^(head|puno|household head|hh head|head of (the )?(household|family)|1)$/, 'Head'],
    [/^(spouse|wife|husband|asawa|partner|live ?in partner|common law (wife|husband|partner))$/, 'Spouse'],
    [/^(son|anak na lalaki)$/, 'Son'],
    [/^(daughter|anak na babae)$/, 'Daughter'],
    [/^(child|anak|children|step ?(son|daughter|child)|adopted (son|daughter|child))$/, 'Child'],
    [/^(grand ?(son|daughter|child)|apo|apo sa tuhod)$/, 'Grandchild'],
    [/^(father|ama|tatay)$/, 'Father'],
    [/^(mother|ina|nanay)$/, 'Mother'],
    [/^(parent|parents|magulang)$/, 'Parent'],
    [/^(sibling|brother|sister|kapatid)$/, 'Sibling'],
    [/^(in law|.* in law|biyenan|manugang|bayaw|hipag|pamangkin|niece|nephew|tiyo|tiya|tito|tita|uncle|aunt|pinsan|cousin|lolo|lola|grand ?(father|mother|parent)|relative|other relative|kamag anak)$/, 'Other Relative'],
    [/^(boarder|kasambahay|katulong|helper|maid|non relative|nonrelative|border|caretaker)$/, 'Non-Relative'],
];

export function normRelation(value) {
    const s = headerKey(value);
    if (!s) return null;
    for (const [re, rel] of RELATIONS) if (re.test(s)) return rel;
    return titleCase(String(value).trim());
}

const ACRONYMS = new Set(['OFW', 'BPO', 'PNP', 'AFP', 'BFP', 'BJMP', 'LGU', 'BHW', 'BNS', 'TESDA', 'CHED', 'DEPED', 'PWD', 'IT', 'HR', 'OJT', 'ALS', 'SHS', 'HS', 'BS', 'BSED', 'BEED', 'BSBA', 'BSN', 'BSIT', 'RN', 'LPT', 'CPA', 'MD', 'TODA', 'NGO', 'DSWD', 'DOH', 'SK', 'GSIS', 'SSS', 'AB', 'MA', 'PHD']);

/**
 * "MARIA" -> "Maria", "dela cruz" -> "Dela Cruz"; mixed case is left alone.
 * With `acronyms`, OFW and TESDA stay as written (an occupation, not a name:
 * "LUZ" is a name, "OFW" is not).
 */
export function titleCase(s, acronyms = false) {
    const v = String(s || '').trim();
    if (!v || (v !== v.toUpperCase() && v !== v.toLowerCase())) return v;
    const t = v.toLowerCase().replace(/(^|[\s\-'.(/])([a-zñ])/g, (_, p, c) => p + c.toUpperCase())
        .replace(/\b(Ii|Iii|Iv|Vi)\b/g, (r) => r.toUpperCase());
    return acronyms ? t.replace(/[A-Za-zÑñ]+/g, (w) => (ACRONYMS.has(w.toUpperCase()) ? (w.toUpperCase() === 'DEPED' ? 'DepEd' : w.toUpperCase() === 'PHD' ? 'PhD' : w.toUpperCase()) : w)) : t;
}

// No lone "V": in a written name it is a middle initial ("VILLANUEVA, SHIELA
// V." became "Shiela Villanueva V", live test 2026-10-12). A fifth comes only
// from a suffix column, which cleanSuffix reads.
export const SUFFIX_RE = /^(jr|sr|ii|iii|iv|vi|2nd|3rd|4th)\.?$/i;
const PARTICLES = ['de', 'del', 'dela', 'delas', 'delos', 'la', 'las', 'los', 'san', 'santa', 'sta', 'sta.', 'santo', 'sto', 'sto.', 'van', 'von', 'di', 'da', 'dos', 'das', 'y'];
const isInitial = (w) => /^[A-Za-zÑñ]\.?$/.test(w);

export function cleanSuffix(s) {
    const v = String(s || '').trim().replace(/\.$/, '');
    if (!v) return '';
    const low = v.toLowerCase();
    if (low === 'jr') return 'Jr.';
    if (low === 'sr') return 'Sr.';
    if (/^(ii|iii|iv|v|vi)$/i.test(v)) return v.toUpperCase();
    return { '2nd': 'II', '3rd': 'III', '4th': 'IV' }[low] || v;
}

/**
 * A full name in parts. "DELA CRUZ, JUAN P. JR." and "Juan P. Dela Cruz Jr."
 * give the same parts; `surnameFirst` reads "DELA CRUZ JUAN P" (no comma).
 */
export function splitName(full, surnameFirst = false) {
    let s = String(full || '').replace(/\s+/g, ' ').trim();
    if (!s) return { first_name: '', middle_name: '', last_name: '', suffix: '' };
    let suffix = '';
    const take = (words) => {
        while (words.length > 1 && SUFFIX_RE.test(words[words.length - 1].replace(/,$/, ''))) suffix = cleanSuffix(words.pop().replace(/,$/, ''));
        return words;
    };
    let last;
    let rest;
    if (s.includes(',')) {
        const [a, ...b] = s.split(',');
        let after = b.join(' ').trim().split(' ').filter(Boolean);
        // "Dela Cruz Jr., Juan" or "Dela Cruz, Juan, Jr."
        const lastWords = take(a.trim().split(' '));
        after = after.filter((w) => (SUFFIX_RE.test(w) ? ((suffix = cleanSuffix(w)), false) : true));
        last = lastWords.join(' ');
        rest = after;
    } else {
        const words = take(s.split(' '));
        if (words.length === 1) return { first_name: words[0], middle_name: '', last_name: '', suffix };
        if (surnameFirst) {
            let i = 0;
            while (i < words.length - 2 && PARTICLES.includes(words[i].toLowerCase())) i++;
            last = words.slice(0, i + 1).join(' ');
            rest = words.slice(i + 1);
        } else {
            let i = words.length - 1;
            while (i > 1 && PARTICLES.includes(words[i - 1].toLowerCase())) i--;
            last = words.slice(i).join(' ');
            rest = words.slice(0, i);
        }
    }
    // The middle name is the last word when it is an initial, or when there
    // are three or more; two words are one given name ("Maria Teresa").
    let middle = '';
    if (rest.length >= 2 && (isInitial(rest[rest.length - 1]) || rest.length >= 3)) middle = rest.pop();
    return { first_name: rest.join(' '), middle_name: middle, last_name: last, suffix };
}

export function normContact(value) {
    const raw = String(value || '').trim();
    if (!raw) return null;
    const digits = raw.replace(/[^\d+]/g, '');
    const d = digits.replace(/\D/g, '');
    if (/^9\d{9}$/.test(d)) return '0' + d;
    if (/^639\d{9}$/.test(d)) return '0' + d.slice(2);
    if (/^09\d{9}$/.test(d)) return d;
    return raw.slice(0, 40);
}

const brgyKey = (s) => headerKey(s).replace(/^(brgy|bgy|barangay|barrio|bo)\s+/, '').replace(/\s+(brgy|barangay)$/, '').replace(/\bpob\b/, 'poblacion').replace(/\bsto\b/, 'santo').replace(/\bsta\b/, 'santa').replace(/\s+/g, ' ');

/** The official barangay a value names, or null. */
export function matchBarangay(value, barangays) {
    const k = brgyKey(value);
    if (!k) return null;
    const exact = barangays.find((b) => brgyKey(b) === k);
    if (exact) return exact;
    const loose = barangays.filter((b) => brgyKey(b).replace(/\s/g, '') === k.replace(/\s/g, '') || (k.length >= 5 && (brgyKey(b).startsWith(k) || k.startsWith(brgyKey(b)))));
    return loose.length === 1 ? loose[0] : null;
}

export const blank = (v) => {
    const s = String(v ?? '').trim();
    return !s || /^(n\/?a|none|null|-+|—|\.|wala|not applicable)$/i.test(s) ? '' : s;
};

/**
 * One row of the file as the API takes it, with what is wrong with it.
 * `opts`: { mapping, barangayMap, fixedBarangay, dateOrder, surnameFirst }
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
    // A suffix typed after the first or last name ("Juan Jr.")
    for (const part of ['first_name', 'last_name']) {
        const w = name[part].split(' ');
        if (w.length > 1 && SUFFIX_RE.test(w[w.length - 1])) {
            name.suffix = name.suffix || cleanSuffix(w.pop());
            name[part] = w.join(' ');
        }
    }
    name = {
        first_name: titleCase(name.first_name),
        middle_name: titleCase(name.middle_name),
        last_name: titleCase(name.last_name),
        suffix: cleanSuffix(name.suffix),
    };
    if (!name.first_name) issues.push('First name is missing');
    if (!name.last_name) issues.push('Last name is missing');

    let dob = null;
    const rawDob = get('dob');
    if (rawDob) {
        const p = parseDate(rawDob, opts.dateOrder);
        if (p.error) issues.push('Birth date "' + rawDob.slice(0, 30) + '" ' + p.error);
        else if (p.date >= new Date().toISOString().slice(0, 10) || p.date <= '1900-01-01') issues.push('Birth date ' + p.date + ' is outside 1900 to today');
        else dob = p.date;
    }

    let barangay = opts.fixedBarangay || '';
    if (!barangay) {
        const raw = get('barangay');
        barangay = raw ? (opts.barangayMap[raw] ?? '') : '';
        if (!raw) issues.push('Barangay is missing');
        else if (!barangay) issues.push('Barangay "' + raw.slice(0, 40) + '" is not matched to a barangay');
    }

    const sex = normSex(get('sex'));
    let relation = normRelation(get('relation'));
    // "Anak" is a son or a daughter, as the registry records it.
    if (relation === 'Child' && sex) relation = sex === 'Male' ? 'Son' : 'Daughter';
    const civilRaw = get('civil_status');
    const household = get('household_key');
    return {
        row: index,
        ...name,
        sex,
        dob,
        civil_status: civilRaw ? (CIVIL[headerKey(civilRaw)] || titleCase(civilRaw)) : null,
        barangay,
        sitio: titleCase(get('sitio')) || null,
        address: get('address') || null,
        contact: normContact(get('contact')),
        occupation: titleCase(get('occupation'), true) || null,
        education: titleCase(get('education'), true) || null,
        income_range: get('income_range') || null,
        relation,
        household_key: household ? household.replace(/\.0+$/, '') : null,
        is_head: relation === 'Head',
        issues,
    };
}

// ------------------------------------------------------------- Files out

const csvCell = (v) => {
    const s = String(v ?? '');
    // A cell that starts like a formula is opened as text, not run.
    const safe = /^[=+\-@\t\r]/.test(s) ? "'" + s : s;
    return /[",\n\r]/.test(safe) ? '"' + safe.replace(/"/g, '""') + '"' : safe;
};

export function downloadCsv(filename, rows) {
    const text = '﻿' + rows.map((r) => r.map(csvCell).join(',')).join('\r\n');
    const url = URL.createObjectURL(new Blob([text], { type: 'text/csv;charset=utf-8' }));
    const a = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
}

/** The Excel template: the columns the import knows, two example rows, and how to fill it. */
export async function downloadTemplate(barangays) {
    const XLSX = await loadXlsx();
    const headers = ['Household No.', 'Relationship to Head', 'Last Name', 'First Name', 'Middle Name', 'Suffix', 'Sex', 'Birth Date', 'Civil Status', 'Barangay', 'Purok / Sitio', 'House No. / Street', 'Contact No.', 'Occupation', 'Highest Education', 'Monthly Income'];
    const brgy = barangays[0] || 'Poblacion';
    const example = [
        ['1', 'Head', 'Dela Cruz', 'Juan', 'Santos', '', 'Male', '1980-03-14', 'Married', brgy, 'Purok 1', '12 Rizal St.', '09171234567', 'Farmer', 'High School Graduate', '₱5,000 - ₱10,000'],
        ['1', 'Spouse', 'Dela Cruz', 'Maria', 'Reyes', '', 'Female', '1982-07-02', 'Married', brgy, 'Purok 1', '12 Rizal St.', '', 'Vendor', 'College Level', ''],
    ];
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([headers, ...example]);
    ws['!cols'] = headers.map((h) => ({ wch: Math.max(12, h.length + 2) }));
    XLSX.utils.book_append_sheet(wb, ws, 'Residents');
    const notes = [
        ['How to fill in this template'],
        ['One resident per row. Delete the two example rows (the Dela Cruz family) before importing.'],
        ['Only Last Name, First Name and Barangay are needed. Fill in what you have; leave the rest blank.'],
        ['Birth Date: YYYY-MM-DD (1980-03-14) or MM/DD/YYYY (03/14/1980). With a birth date, the resident is recognised when they register.'],
        ['Household No.: give everyone in one household the same number. The person whose Relationship is Head becomes its head.'],
        ['Sex: Male or Female. Civil Status: Single, Married, Widowed, Separated, Annulled or Live-in.'],
        ['Barangay must be one of:'],
        ...barangays.map((b) => ['   ' + b]),
    ];
    const ns = XLSX.utils.aoa_to_sheet(notes);
    ns['!cols'] = [{ wch: 110 }];
    XLSX.utils.book_append_sheet(wb, ns, 'Instructions');
    XLSX.writeFile(wb, 'Esperanza resident import template.xlsx', { compression: true });
}
