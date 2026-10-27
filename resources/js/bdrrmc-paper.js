// The six BDRRMC capture forms as Barangay Labangtaytay's paper files lay them
// out (public/documents/email documents/LABANGTATAY BDRRMC), owner decision
// 2026-10-27: entry, printing and reprinting all draw the paper form from this
// one description, so the three cannot drift apart.
//
// Each form: its page (A4, the file's orientation), the rows one sheet holds,
// the table's column groups top to bottom with the paper's wording, the
// column widths in points as measured on the files, its TOTAL row, and who
// signs; row heights are the files' row pitch less the ruling line, which the
// border adds back; the portrait files print larger type (`type`). Wording is
// the paper's, except two spelling corrections the owner
// approved ("ASSESMENT" -> "ASSESSMENT", "Assign Kagawad" -> "Assigned
// Kagawad"); "No. of Individual", "Others Parts" and the committee's two
// spellings stay as printed.
//
// A leaf column's `key` is the row field the server stores; `type` is how a
// cell is entered: text, int, num, flood (the 1-5 flood level), no (the row
// number), sum-m / sum-f / sum-all (the evacuee profile's computed totals),
// sign (left blank for a signature on paper).

const leaf = (label, key, o = {}) => ({ label, key, type: 'text', ...o });
const int = (label, key, o = {}) => leaf(label, key, { type: 'int', ...o });
const group = (label, children, o = {}) => ({ label, children, ...o });
const mf = (prefix, aria, w) => [int('M', prefix + 'm', { aria: aria + ' M', w: w[0] }), int('F', prefix + 'f', { aria: aria + ' F', w: w[1] })];

export const PAPER = {
    evacuees: {
        orientation: 'landscape',
        rows: 20,
        rowHeight: 14.15,
        noAlign: ['left', 'right'],
        caption: { text: 'Number of Persons', from: 'pre03m', to: 'sc60f' },
        columns: [
            leaf('No.', 'no', { type: 'no', w: 22.5 }),
            leaf('Purok', 'purok', { w: 63 }),
            leaf('HH Code', 'hh', { hl: 'yellow', w: 72 }),
            leaf('Name of Family Head or Representative', 'head', { aria: 'Family Head or Representative', notes: true, w: 130.5 }),
            group('Pre-Schooler (0-6)', [group('0 to 3', mf('pre03', 'Pre-Schooler 0-3', [27, 22.6])), group('4 to 6', mf('pre46', 'Pre-Schooler 4-6', [27, 27]))]),
            group('Older Children', [group('7 to 12', mf('ch712', 'Children 7-12', [27, 28.6]))]),
            group('Youth', [group('13 to 17', mf('yth1317', 'Youth 13-17', [25.6, 26.8]))]),
            group('Adult', [group('18 to 59', mf('ad1859', 'Adult 18-59', [27, 27]))]),
            group('Senior Citizen', [group('60 above', mf('sc60', 'Senior 60+', [27, 27]))]),
            group('Total', [leaf('M', 'tm', { type: 'sum-m', w: 27 }), leaf('F', 'tf', { type: 'sum-f', w: 22.5 })], { span: 2 }),
            leaf('Grand Total', 'gt', { type: 'sum-all', w: 47.5 }),
            leaf('Assistance Extended', 'assist', { w: 85.4 }),
            leaf('No.', 'no2', { type: 'no', w: 22.6 }),
        ],
        totals: { label: 'Grand Total', from: 'head', to: 'head', align: 'center', sums: ['pre03m', 'pre03f', 'pre46m', 'pre46f', 'ch712m', 'ch712f', 'yth1317m', 'yth1317f', 'ad1859m', 'ad1859f', 'sc60m', 'sc60f', 'tm', 'tf', 'gt'] },
        sign: { prepared: 'Assigned Barangay Official (Evacuation Center)', consolidated: 'Barangay Secretary', noted: 'Punong Barangay' },
    },
    'human-housing': {
        orientation: 'landscape',
        rows: 20,
        rowHeight: 15.55,
        noAlign: ['left', 'right'],
        columns: [
            leaf('No.', 'no', { type: 'no', w: 27.8 }),
            leaf('HH Code', 'hh', { hl: 'yellow', w: 74.9 }),
            leaf('Name of Head of the Family', 'head', { w: 111.6 }),
            int('No. of Depen­dents', 'deps', { aria: 'No. of Dependents', w: 41.7 }),
            group('Human', [group('No. of Individual', [
                group('Casualty', mf('cas_', 'Casualty', [25.8, 24.4])),
                group('Missing', mf('miss_', 'Missing', [23, 22.5])),
                group('Rescued', mf('resc_', 'Rescued', [24.9, 25.2])),
            ])]),
            group('Housing Damages', [
                group('Roof', [
                    group('Slightly', [int('Nipa', 'roof_slight_nipa', { small: true, aria: 'Roof slightly damaged, Nipa', w: 26.2 }), int('GI Sheets', 'roof_slight_gi', { small: true, aria: 'Roof slightly damaged, GI Sheets', w: 31.3 })], { hl: 'yellow' }),
                    group('Partially', [int('Nipa', 'roof_partial_nipa', { small: true, aria: 'Roof partially damaged, Nipa', w: 21.7 }), int('GI Sheets', 'roof_partial_gi', { small: true, aria: 'Roof partially damaged, GI Sheets', w: 30.5 })]),
                    group('Totally', [int('Nipa', 'roof_total_nipa', { small: true, aria: 'Roof totally damaged, Nipa', w: 21.7 }), int('GI Sheets', 'roof_total_gi', { small: true, aria: 'Roof totally damaged, GI Sheets', w: 31.2 })]),
                ]),
                group('Outer Wall', [int('Partial', 'wall_partial', { aria: 'Outer wall, partial', w: 31.9 }), int('Total', 'wall_total', { aria: 'Outer wall, total', w: 25.9 })]),
                group('Others Parts', [int('Partial', 'other_partial', { aria: 'Other parts, partial', w: 31.8 }), int('Total', 'other_total', { aria: 'Other parts, total', w: 32.5 })]),
                leaf('Flood Height', 'flood_height', { type: 'flood', star: true, hl: 'yellow', w: 35.8 }),
            ]),
            leaf('Estimated Damage (PhP)', 'est_damage', { type: 'num', w: 69.9 }),
            leaf('No.', 'no2', { type: 'no', w: 22 }),
        ],
        totals: { label: 'TOTAL', from: 'no', to: 'head', align: 'center', sums: ['deps', 'cas_m', 'cas_f', 'miss_m', 'miss_f', 'resc_m', 'resc_f', 'roof_slight_nipa', 'roof_slight_gi', 'roof_partial_nipa', 'roof_partial_gi', 'roof_total_nipa', 'roof_total_gi', 'wall_partial', 'wall_total', 'other_partial', 'other_total', 'est_damage'] },
        footnote: 'flood',
        sign: { prepared: 'Assigned Kagawad (Purok)', consolidated: 'Barangay Secretary', noted: 'Punong Barangay' },
    },
    agriculture: {
        orientation: 'landscape',
        rows: 20,
        rowHeight: 14.15,
        noAlign: ['left', 'left'],
        noDot: true,
        columns: [
            leaf('No.', 'no', { type: 'no', w: 26.7 }),
            leaf('HH Code', 'hh', { hl: 'yellow', w: 63 }),
            leaf('HH Name of Affected Owner/Farmer', 'owner', { w: 121.5 }),
            group('LIVESTOCK', [leaf('Type of Livestock', 'livestock_type', { w: 63.4 }), int('No. of Heads', 'heads', { w: 40.4 }), leaf('Age', 'age', { aria: 'Age of livestock', w: 37.2 })]),
            group('CROPS', [
                leaf('Type of Crop Planted', 'crop', { w: 66.4 }),
                group('Stage of the Crop', [leaf('Vegetative / Productive', 'stage', { small: true, aria: 'Stage of the crop', w: 76.4 })]),
                group('Area Affected', [leaf('w/ chance of recovery', 'area_recoverable', { small: true, aria: 'Area affected, with chance of recovery', w: 54 }), leaf('NO chance of recovery', 'area_unrecoverable', { small: true, aria: 'Area affected, no chance of recovery', w: 54.1 })]),
            ]),
            group('PRODUCTION', [leaf('Type of Product', 'product', { w: 76.5 }), group('Production Loss', [leaf('In metric tons', 'metric_tons', { type: 'num', small: true, aria: 'Production loss in metric tons', w: 72 })])]),
            leaf('No.', 'no2', { type: 'no', w: 31.5 }),
        ],
        totals: { label: 'TOTAL', from: 'owner', to: 'owner', align: 'right', sums: ['heads', 'metric_tons'] },
        sign: { prepared: 'Assigned Kagawad (Purok)', consolidated: 'Barangay Secretary', noted: 'Punong Barangay/BDRRMC Chairperson' },
    },
    fishing: {
        orientation: 'portrait',
        type: 'mid',
        rows: 20,
        rowHeight: 25.15,
        noAlign: ['left'],
        noDot: true,
        columns: [
            leaf('No.', 'no', { type: 'no', w: 28.5 }),
            leaf('HH Code', 'hh', { hl: 'yellow', w: 70.7 }),
            leaf('HH Name of Owner', 'owner', { w: 120.2 }),
            int('Qty.', 'qty', { aria: 'Quantity', w: 45.7 }),
            leaf('Description of Fishing Gear/ Equipment', 'gear', { w: 135.5 }),
            leaf('Description of Damage', 'damage', { sub: '(Partial/ Total/Missing)', w: 71.3 }),
            leaf('Estimated Damage (PhP)', 'est_damage', { type: 'num', w: 82.2 }),
        ],
        totals: { label: 'TOTAL', from: 'damage', to: 'damage', align: 'right', sums: ['est_damage'] },
        sign: { prepared: 'Assigned Kagawad (Purok)', consolidated: 'Barangay Secretary', noted: 'Punong Barangay' },
    },
    business: {
        orientation: 'landscape',
        rows: 20,
        rowHeight: 17.85,
        noAlign: ['left', 'right'],
        noDot: true,
        columns: [
            leaf('No.', 'no', { type: 'no', w: 28.6 }),
            leaf('Business Code', 'code', { hl: 'green', w: 77.6 }),
            leaf('Full Name of Owner', 'owner', { w: 104.9 }),
            leaf('Name of Establishment/ Store', 'establishment', { w: 98.4 }),
            leaf('Business Type', 'type', { sub: '(store, products , food, rental)', w: 92.4 }),
            int('No. of Workers', 'workers', { w: 54.6 }),
            group('Description, Type & Qty. of Damage', [
                leaf('Merchandise', 'merch', { small: true, sub: '(Qty,Desc’n) (Total/Partial)', w: 59.8 }),
                leaf('Equipment', 'equip', { small: true, sub: '(Qty,Desc’n) (Total/Partial)', w: 55.1 }),
                leaf('Structures', 'struct', { small: true, sub: '(Qty,Desc’n) (Total/Partial)', w: 55.1 }),
                leaf('Flood Height', 'flood_height', { type: 'flood', star: true, hl: 'yellow', w: 57.2 }),
            ]),
            leaf('Estimated Damage (PhP)', 'est_damage', { type: 'num', w: 93.4 }),
            leaf('No.', 'no2', { type: 'no', w: 28.5 }),
        ],
        totals: { label: 'TOTAL', from: 'code', to: 'code', align: 'left', sums: ['workers', 'est_damage'] },
        footnote: 'flood',
        sign: { prepared: 'Assigned Kagawad (Purok)', consolidated: 'Barangay Secretary', noted: 'Punong Barangay/BDRRMC Chairperson' },
    },
    rds: {
        orientation: 'portrait',
        type: 'large',
        rows: 25,
        rowHeight: 19.15,
        noAlign: ['center'],
        columns: [
            leaf('No.', 'no', { type: 'no', w: 31.6 }),
            leaf('HH Code', 'hh', { hl: 'yellow', w: 72 }),
            leaf('Name of Beneficiary', 'beneficiary', { notes: true, w: 126 }),
            int('No. of Depen­dents', 'deps', { aria: 'No. of Dependents', w: 56.4 }),
            group('Assistance Received', [leaf('Kind/ Type', 'kind', { aria: 'Kind/Type', w: 101.9 }), leaf('Qty/ Unit', 'qty', { aria: 'Qty/Unit', w: 43.4 })]),
            leaf('Signature/', 'sign', { type: 'sign', sub: 'Thumbmark Received Family Food Packs in Good Condition', subBold: true, w: 117.9 }),
        ],
        totals: null,
        sign: { prepared: 'Barangay Official', noted: 'Punong Barangay', preparedRole: 'Distributed by:', notedRole: 'Certified True and Correct:', twoColumn: true },
    },
};

/** The leaf columns, left to right. */
export function leavesOf(columns) {
    const out = [];
    const walk = (nodes) => nodes.forEach((n) => (n.children ? walk(n.children) : out.push(n)));
    walk(columns);
    return out;
}

/**
 * The header as table rows: each cell with its colspan and rowspan, a group
 * spanning its leaves and a leaf reaching down to the last header row, as the
 * paper's merged cells do. `span` lets a group fill more than one row (the
 * evacuee profile's Total stands over two before its M and F).
 */
export function headerRows(columns) {
    const width = (n) => (n.children ? n.children.reduce((s, c) => s + width(c), 0) : 1);
    const depthOf = (n, level) => (n.children ? Math.max(...n.children.map((c) => depthOf(c, level + (n.span || 1)))) : level + 1);
    const depth = Math.max(...columns.map((c) => depthOf(c, 0)));
    const rows = Array.from({ length: depth }, () => []);
    const walk = (nodes, level) => nodes.forEach((n) => {
        rows[level].push({ ...n, colspan: width(n), rowspan: n.children ? (n.span || 1) : depth - level, leaf: !n.children });
        if (n.children) walk(n.children, level + (n.span || 1));
    });
    walk(columns, 0);
    return rows;
}

/** The paper's caption above some columns (the evacuee profile's "Number of Persons"). */
export function captionRow(spec) {
    if (!spec.caption) return null;
    const keys = leavesOf(spec.columns).map((c) => c.key);
    const a = keys.indexOf(spec.caption.from);
    const b = keys.indexOf(spec.caption.to);
    return [{ blank: true, colspan: a }, { text: spec.caption.text, colspan: b - a + 1 }, { blank: true, colspan: keys.length - b - 1 }].filter((c) => c.colspan > 0);
}

/** The TOTAL row's cells: the label over its columns, a sum, or a blank. */
export function totalCells(spec) {
    if (!spec.totals) return [];
    const t = spec.totals;
    const leaves = leavesOf(spec.columns);
    const a = leaves.findIndex((c) => c.key === t.from);
    const b = leaves.findIndex((c) => c.key === t.to);
    const out = [];
    leaves.forEach((c, i) => {
        if (i === a) out.push({ label: t.label, colspan: b - a + 1, align: t.align });
        else if (i > a && i <= b) return;
        else out.push({ key: c.key, type: c.type, sum: t.sums.includes(c.key), colspan: 1 });
    });
    return out;
}

/** The columns a row of this form stores (the computed and blank ones are not stored). */
export function storedKeys(spec) {
    return leavesOf(spec.columns).filter((c) => !['no', 'sum-m', 'sum-f', 'sum-all', 'sign'].includes(c.type)).map((c) => c.key);
}
