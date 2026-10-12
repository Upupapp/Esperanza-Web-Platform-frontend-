import api from './api/client';

// Constituents -> Import from a file (owner, 2026-10-09). The window's state
// and steps; reading the file and cleaning its values is in
// resident-import.js, loaded when the window first opens.
//
//   1 Choose a file   2 Match columns   3 Review   4 Import
//
// Opened with the window event `open-resident-import`; tells the page
// `residents-imported` when records changed and `registry-show-import` to
// list one import's records.

const BATCH = 250;
const CHECK_BATCH = 500;

const REASONS = {
    name: 'same name',
    name_typo: 'nearly the same name',
    barangay: 'same barangay',
    middle_name: 'same middle name',
    middle_initial: 'middle initial agrees',
    sex: 'same sex',
    birth_date: 'same birth date',
    near_birth_date: 'nearly the same birth date',
    no_birth_date: 'no birth date to compare',
    mobile: 'same mobile number',
    different_sex: 'sex differs',
    different_suffix: 'suffix differs (Jr., Sr.)',
    different_middle_name: 'middle name differs',
};

// Told to the page on window, not with $dispatch: a button that starts a step
// is often gone by the time it finishes (the footer changes with the step),
// and an event from a removed element reaches no one.
const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));

export const reasonText = (codes) => (codes || []).map((c) => REASONS[c] || c).join(' · ');

const letter = (i) => {
    let s = '';
    for (let n = i + 1; n > 0; n = Math.floor((n - 1) / 26)) s = String.fromCharCode(65 + ((n - 1) % 26)) + s;
    return s;
};

export function residentImport() {
    return {
        open: false,
        panel: 'import',
        step: 1,
        lib: null,
        busy: false,
        busyText: '',
        error: '',
        dragging: false,

        fileName: '',
        format: '',
        tables: [],
        tableIndex: 0,
        headerRow: 0,
        headers: [],
        body: [],
        bodyStart: 0,
        mapping: [],
        fields: [],
        fixedBarangay: '',
        barangayValues: [],
        dateOrder: 'mdy',
        dateAmbiguous: false,
        surnameFirst: false,
        groupHouseholds: true,

        rows: [],
        filter: 'all',
        reviewSearch: '',
        page: 1,
        perPage: 50,
        onMatch: 'skip',
        verification: 'Unverified',

        importRef: null,
        progress: { done: 0, total: 0 },
        stopRequested: false,
        running: false,
        stoppedAt: null,
        summary: null,

        history: [],
        historyLoading: false,
        historyFailed: false,
        // The row being corrected in the review (owner, 2026-10-09: fix what
        // was not accepted here, not in the file).
        editing: null,
        draft: {},
        draftErrors: [],
        checkingRow: false,

        undoTarget: null,
        undoReason: '',
        undoing: false,

        reasonText,
        letter,

        // ---------------------------------------------------- Opening

        async openImport(panel = 'import') {
            this.open = true;
            this.panel = panel;
            if (panel === 'history') this.loadHistory();
            if (!this.lib) {
                try {
                    this.lib = await import('./resident-import.js');
                    this.fields = this.lib.FIELDS;
                } catch {
                    this.error = 'The import could not load. Check the connection and open it again.';
                }
            }
            this.$nextTick(() => window.renderIcons?.());
        },

        get scope() {
            const s = this.$store.session.account?.scope;
            return s && s !== 'Municipal' ? s : null;
        },
        get barangays() { return this.barangayOptions || []; },
        get mayUndo() { return this.$store.session.canPerm ? this.$store.session.canPerm('const_resident', 'delete') : false; },

        startOver() {
            if (this.running) return;
            Object.assign(this, {
                step: 1, error: '', fileName: '', format: '', tables: [], tableIndex: 0, headerRow: 0, headers: [], body: [], mapping: [],
                fixedBarangay: '', barangayValues: [], rows: [], filter: 'all', page: 1, importRef: null, summary: null, stoppedAt: null,
                progress: { done: 0, total: 0 }, onMatch: 'skip', verification: 'Unverified', surnameFirst: false, groupHouseholds: true,
                editing: null, draftErrors: [],
            });
            this.$nextTick(() => window.renderIcons?.());
        },

        // ---------------------------------------------------- 1 Choose a file

        async takeFile(file) {
            if (!file || this.busy) return;
            if (!this.lib) await this.openImport();
            this.error = '';
            this.busy = true;
            this.busyText = 'Reading ' + file.name + '…';
            try {
                const read = await this.lib.readFile(file);
                this.fileName = file.name;
                this.format = read.format;
                this.tables = read.tables;
                // The biggest table is the list; a cover sheet or legend is smaller.
                let biggest = 0;
                read.tables.forEach((t, i) => { if (t.rows.length > read.tables[biggest].rows.length) biggest = i; });
                this.useTable(biggest);
                this.step = 2;
            } catch (e) {
                this.error = e instanceof this.lib.ReadError ? e.message : 'The portal could not read this file. Save it as Excel (.xlsx) or CSV and try again.';
            } finally {
                this.busy = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        useTable(i) {
            this.tableIndex = Number(i);
            this.headerRow = this.lib.guessHeaderRow(this.tables[this.tableIndex].rows);
            this.applyHeader(true);
        },

        applyHeader(guess = true) {
            const all = this.tables[this.tableIndex].rows;
            const h = Number(this.headerRow);
            const width = Math.max(...all.map((r) => r.length));
            this.headers = Array.from({ length: width }, (_, c) => (h >= 0 ? all[h]?.[c] : '') || 'Column ' + letter(c));
            this.bodyStart = h + 1;
            const headerLine = h >= 0 ? all[h].join('|') : null;
            // A PDF repeats its header on every page; those lines are not people.
            const lines = this.tables[this.tableIndex].lines || all.map((_, i) => i + 1);
            this.body = all.slice(h + 1).map((cells, k) => ({ cells, line: lines[h + 1 + k] })).filter((r) => r.cells.join('|') !== headerLine && r.cells.some(Boolean));
            if (guess) this.mapping = this.lib.guessMapping(this.headers, this.body.slice(0, 300).map((r) => r.cells), this.barangays);
            this.refreshValues();
        },

        get headerChoices() {
            const rows = this.tables[this.tableIndex]?.rows || [];
            const lines = this.tables[this.tableIndex]?.lines || [];
            return rows.slice(0, 15).map((r, i) => ({ value: i, label: 'Row ' + (lines[i] || i + 1) + ': ' + r.filter(Boolean).slice(0, 4).join(', ').slice(0, 60) }));
        },

        setField(col, field) {
            if (field) this.mapping = this.mapping.map((f, i) => (i !== col && f === field ? '' : f));
            this.mapping[col] = field;
            this.refreshValues();
        },

        column(field) { return this.mapping.indexOf(field); },
        samples(col) {
            const out = [];
            for (const r of this.body) {
                if (r.cells[col]) out.push(r.cells[col]);
                if (out.length === 3) break;
            }
            return out;
        },

        refreshValues() {
            const b = this.column('barangay');
            if (b >= 0) {
                const counts = {};
                this.body.forEach((r) => { const v = (r.cells[b] || '').trim(); if (v) counts[v] = (counts[v] || 0) + 1; });
                const before = Object.fromEntries(this.barangayValues.map((x) => [x.raw, x.match]));
                this.barangayValues = Object.entries(counts).sort((x, y) => y[1] - x[1]).slice(0, 200)
                    .map(([raw, count]) => ({ raw, count, match: before[raw] ?? this.lib.matchBarangay(raw, this.barangays) ?? '' }));
            } else this.barangayValues = [];
            if (this.scope && b < 0) this.fixedBarangay = this.scope;

            const d = this.column('dob');
            if (d >= 0) {
                const order = this.lib.dateOrderOf(this.body.map((r) => r.cells[d]));
                this.dateOrder = order.dmy > order.mdy ? 'dmy' : 'mdy';
                this.dateAmbiguous = order.ambiguous || (order.mdy > 0 && order.dmy > 0);
            } else this.dateAmbiguous = false;
            this.$nextTick(() => window.renderIcons?.());
        },

        get hasName() { return this.column('full_name') >= 0 || (this.column('first_name') >= 0 && this.column('last_name') >= 0); },
        get hasBarangay() { return this.column('barangay') >= 0 || !!this.fixedBarangay; },
        get unmatchedBarangays() { return this.barangayValues.filter((v) => !v.match).length; },
        get hasHouseholdNo() { return this.column('household_key') >= 0; },
        get hasRelation() { return this.column('relation') >= 0; },
        get fullNameHasCommas() {
            const f = this.column('full_name');
            return f >= 0 && this.body.slice(0, 50).some((r) => (r.cells[f] || '').includes(','));
        },
        get namePreview() {
            const f = this.column('full_name');
            if (f < 0) return [];
            // As it will be saved: "QAIMPORT, RAMON C." reads Last: Qaimport.
            const t = this.lib.titleCase;
            return this.body.slice(0, 3).filter((r) => r.cells[f]).map((r) => {
                const n = this.lib.splitName(r.cells[f], this.surnameFirst);
                return { raw: r.cells[f], first_name: t(n.first_name), middle_name: t(n.middle_name), last_name: t(n.last_name), suffix: n.suffix };
            });
        },
        get mappedCount() { return this.mapping.filter(Boolean).length; },

        // ---------------------------------------------------- 3 Review

        async toReview() {
            if (!this.hasName || !this.hasBarangay || this.busy || !this.body.length) return;
            this.busy = true;
            this.error = '';
            this.busyText = 'Checking ' + this.body.length.toLocaleString() + ' rows…';
            const barangayMap = Object.fromEntries(this.barangayValues.filter((v) => v.match).map((v) => [v.raw, v.match]));
            const opts = {
                mapping: this.mapping,
                barangayMap,
                fixedBarangay: this.column('barangay') >= 0 ? '' : this.fixedBarangay,
                dateOrder: this.dateOrder,
                surnameFirst: this.surnameFirst,
            };
            let head = null;
            let lastKey = null;
            const rows = this.body.map((r) => {
                const row = this.lib.buildRow(r.cells, r.line, opts);
                // A barangay officer imports their barangay's rows; the rest of
                // a municipal list is left out, not something to fix.
                const outside = !!(this.scope && row.barangay && row.barangay !== this.scope && !row.issues.length);
                if (!this.groupHouseholds) row.household_key = null;
                else if (this.hasHouseholdNo) {
                    // Registers write the number on the head's row only: a
                    // blank one is the household of the row above, until a
                    // new Head starts one.
                    if (row.household_key) lastKey = row.household_key;
                    else if (lastKey && !row.is_head) row.household_key = lastKey;
                    else lastKey = null;
                } else if (this.hasRelation) {
                    // Each Head starts a household; the rows under them join it.
                    if (row.is_head) head = 'H' + row.row;
                    row.household_key = head;
                }
                return { ...row, cells: r.cells, status: outside ? 'outside' : row.issues.length ? 'error' : 'ready', errors: [...row.issues], match: null, twinOf: null, action: 'auto', outcome: null, outcomeRef: null, outcomeMessage: null };
            });

            try {
                const toCheck = rows.filter((r) => r.status === 'ready');
                for (let i = 0; i < toCheck.length; i += CHECK_BATCH) {
                    const chunk = toCheck.slice(i, i + CHECK_BATCH);
                    this.busyText = 'Checking rows against the registry… ' + Math.min(i + CHECK_BATCH, toCheck.length).toLocaleString() + ' of ' + toCheck.length.toLocaleString();
                    const res = await api.post('/admin/resident-imports/check', { rows: chunk.map((r) => this.payload(r)) });
                    (res.data?.rows || []).forEach((result, k) => {
                        const r = chunk[k];
                        r.key = result.key;
                        if (result.status === 'error') { r.status = 'error'; r.errors = (result.errors || []).map((e) => e.message); }
                        else if (result.status === 'match') { r.status = 'match'; r.match = result.match; }
                    });
                }
            } catch (e) {
                this.busy = false;
                this.error = e?.localised ? e.localised('en') : 'Could not check the rows. Check the connection and try again.';
                return;
            }

            this.rows = rows;
            this.editing = null;
            this.recountTwins();
            this.filter = this.counts.error ? 'error' : this.counts.match ? 'match' : 'all';
            this.page = 1;
            this.step = 3;
            this.busy = false;
            this.$nextTick(() => window.renderIcons?.());
        },

        payload(r) {
            const action = r.status === 'twice' ? (r.action === 'new' ? 'new' : 'skip') : r.action;
            return {
                row: r.row, first_name: r.first_name, middle_name: r.middle_name || null, last_name: r.last_name, suffix: r.suffix || null,
                sex: r.sex, dob: r.dob, civil_status: r.civil_status, barangay: r.barangay, sitio: r.sitio, address: r.address,
                contact: r.contact, occupation: r.occupation, education: r.education, income_range: r.income_range,
                relation: r.relation, household_key: r.household_key, is_head: r.is_head, action,
            };
        },

        // The same person twice in this file: the first one counts. Run again
        // after a correction, which can make or break a pair.
        // Judged the way the server judges a resident already there: the same
        // name in the same barangay, middle initials that agree (or one
        // missing), and birth dates that agree (or one missing).
        recountTwins() {
            for (const r of this.pending) {
                if (r.status === 'twice') { r.keepNew = r.action === 'new'; r.status = 'ready'; r.twinOf = null; r.action = 'auto'; }
            }
            const groups = {};
            const initial = (r) => (r.middle_name || '').normalize('NFD').replace(/[^A-Za-z]/g, '').charAt(0).toLowerCase();
            for (const r of this.pending) {
                if (r.status !== 'ready' || !r.key) continue;
                const g = (groups[r.key + '|' + r.barangay + '|' + (r.suffix || '')] ||= []);
                const twin = g.find((o) => (!o.dob || !r.dob || o.dob === r.dob) && (!initial(o) || !initial(r) || initial(o) === initial(r)));
                if (twin) { r.status = 'twice'; r.twinOf = twin.row; r.action = r.keepNew ? 'new' : 'skip'; } else g.push(r);
            }
        },

        // Rows not yet sent in an import: all of them, until an import has
        // run; then the ones left to fix.
        get pending() { return this.rows.filter((r) => !r.outcome); },
        get counts() {
            const c = { all: 0, ready: 0, match: 0, twice: 0, error: 0, outside: 0 };
            this.pending.forEach((r) => { c.all++; c[r.status]++; });
            return c;
        },
        get filtered() {
            const rows = this.filter === 'all' ? this.pending.filter((r) => r.status !== 'outside') : this.pending.filter((r) => r.status === this.filter);
            const q = this.reviewSearch.trim().toLowerCase();
            if (!q) return rows;
            // A name, a row number, a household number or a matched ref.
            return rows.filter((r) => String(r.row) === q || this.displayName(r).toLowerCase().includes(q)
                || (r.household_key || '').toLowerCase() === q || (r.match?.ref || '').toLowerCase() === q);
        },

        // ---------------------------------------------------- Correcting a row

        fileValue(r, field) {
            const at = this.mapping.indexOf(field);
            return at >= 0 ? (r.cells[at] || '') : '';
        },

        startEdit(r) {
            this.editing = r.row;
            this.draftErrors = [];
            this.draft = {
                first_name: r.first_name || '', middle_name: r.middle_name || '', last_name: r.last_name || '', suffix: r.suffix || '',
                sex: r.sex || '', dob: r.dob || '', civil_status: r.civil_status || '', barangay: r.barangay || (this.scope || ''),
                sitio: r.sitio || '', relation: r.relation || '', household_key: r.household_key || '', contact: r.contact || '',
            };
            this.$nextTick(() => window.renderIcons?.());
        },

        cancelEdit() {
            this.editing = null;
            this.draftErrors = [];
            this.$nextTick(() => window.renderIcons?.());
        },

        async saveEdit(r) {
            if (this.checkingRow) return;
            const d = Object.fromEntries(Object.entries(this.draft).map(([k, v]) => [k, String(v ?? '').trim()]));
            const errors = [];
            if (!d.first_name) errors.push('First name is missing');
            if (!d.last_name) errors.push('Last name is missing');
            if (d.dob) {
                const today = new Date().toISOString().slice(0, 10);
                if (this.lib.parseDate(d.dob).error) errors.push('Birth date is not a date');
                else if (d.dob >= today || d.dob <= '1900-01-01') errors.push('Birth date must be between 1900 and today');
            }
            if (!d.barangay) errors.push('Choose the barangay');
            else if (this.scope && d.barangay !== this.scope) errors.push('Outside your barangay (' + this.scope + ')');
            if (errors.length) { this.draftErrors = errors; return; }

            const before = r.status;
            const blankToNull = (v) => (v === '' ? null : v);
            Object.assign(r, {
                first_name: d.first_name, middle_name: blankToNull(d.middle_name), last_name: d.last_name, suffix: blankToNull(d.suffix),
                sex: blankToNull(d.sex), dob: blankToNull(d.dob), civil_status: blankToNull(d.civil_status), barangay: d.barangay,
                sitio: blankToNull(d.sitio), relation: blankToNull(d.relation), household_key: blankToNull(d.household_key),
                contact: blankToNull(d.contact), is_head: d.relation === 'Head', edited: true, action: 'auto', match: null, twinOf: null,
            });
            this.checkingRow = true;
            try {
                const res = await api.post('/admin/resident-imports/check', { rows: [this.payload(r)] });
                const result = res.data?.rows?.[0] || {};
                r.key = result.key;
                if (result.status === 'error') {
                    r.status = 'error';
                    r.errors = (result.errors || []).map((e) => e.message);
                    this.draftErrors = r.errors;
                    return;
                }
                r.status = result.status === 'match' ? 'match' : 'ready';
                r.errors = [];
                r.match = result.match || null;
                this.recountTwins();
                this.editing = null;
                const now = { ready: 'it will be added as a new resident', match: 'it is already in the registry as ' + (r.match?.ref || ''), twice: 'it is the same person as row ' + r.twinOf }[r.status];
                emit('toast', { message: 'Row ' + r.row + (before === 'error' ? ' is fixed: ' : ' is updated: ') + now + '.', variant: 'success' });
            } catch (e) {
                this.draftErrors = [e?.localised ? e.localised('en') : 'Could not check the row. Check the connection and try again.'];
            } finally {
                this.checkingRow = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        // After an import: the rows it left out, back in the review to fix
        // and import on their own.
        get leftovers() { return this.rows.filter((r) => r.status === 'error' && !r.outcome).length; },
        fixLeftovers() {
            if (this.running) return;
            Object.assign(this, { importRef: null, summary: null, stoppedAt: null, progress: { done: 0, total: 0 }, error: '', step: 3, filter: 'error', page: 1, editing: null });
            this.$nextTick(() => window.renderIcons?.());
        },
        get pageRows() { return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage); },
        get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },
        setFilter(f) { this.filter = f; this.page = 1; this.$nextTick(() => window.renderIcons?.()); },
        setReviewSearch(v) { this.reviewSearch = v; this.page = 1; this.editing = null; this.$nextTick(() => window.renderIcons?.()); },

        willAdd(r) {
            if (r.status === 'ready') return r.action !== 'skip';
            if (r.status === 'twice') return r.action === 'new';
            if (r.status === 'match') return r.action === 'new';
            return false;
        },
        willFill(r) { return r.status === 'match' && (r.action === 'fill' || (r.action === 'auto' && this.onMatch === 'fill')); },
        get plan() {
            let add = 0;
            let fill = 0;
            let leave = 0;
            let fix = 0;
            this.pending.forEach((r) => {
                if (r.status === 'outside') return;
                if (r.status === 'error') fix++;
                else if (this.willAdd(r)) add++;
                else if (this.willFill(r)) fill++;
                else leave++;
            });
            return { add, fill, leave, fix };
        },
        get households() {
            const keys = new Set(this.pending.filter((r) => r.household_key && this.willAdd(r)).map((r) => r.barangay + '|' + r.household_key));
            return keys.size;
        },
        displayName(r) { return [r.first_name, r.middle_name, r.last_name, r.suffix].filter(Boolean).join(' '); },

        // ---------------------------------------------------- 4 Import

        // Rows the server is told about: what is added or filled, and what is
        // already there (it anchors a household even when left as it is).
        sendable(r) {
            return r.status !== 'error' && r.status !== 'outside' && (this.willAdd(r) || this.willFill(r) || r.status === 'match' || r.status === 'twice');
        },

        // Rows still to send, a household's rows together and its head first,
        // in batches that never split a household. Rows already answered are
        // not in them, so a resumed import starts at the first batch.
        batches() {
            const send = this.pending.filter((r) => this.sendable(r));
            const groups = [];
            const byKey = {};
            for (const r of send) {
                if (!r.household_key) { groups.push([r]); continue; }
                const k = r.barangay + '|' + r.household_key;
                if (!byKey[k]) { byKey[k] = []; groups.push(byKey[k]); }
                byKey[k].push(r);
            }
            groups.forEach((g) => g.sort((a, b) => (b.is_head ? 1 : 0) - (a.is_head ? 1 : 0)));
            const out = [];
            let current = [];
            for (const g of groups) {
                if (current.length && current.length + g.length > BATCH) { out.push(current); current = []; }
                current.push(...g);
            }
            if (current.length) out.push(current);
            return out;
        },

        async runImport() {
            if (this.running) return;
            const batches = this.batches();
            if (!batches.length) return;
            this.running = true;
            this.step = 4;
            this.error = '';
            this.stopRequested = false;
            try {
                if (!this.importRef) {
                    const columns = {};
                    this.mapping.forEach((f, i) => { if (f) columns[f] = this.headers[i]; });
                    const res = await api.post('/admin/resident-imports', {
                        filename: this.fileName.slice(0, 255),
                        format: this.format,
                        total_rows: Math.max(1, this.pending.length),
                        on_match: this.onMatch,
                        verification: this.verification,
                        group_households: this.groupHouseholds && (this.hasHouseholdNo || this.hasRelation),
                        columns,
                    });
                    this.importRef = res.data.ref;
                    this.progress = { done: 0, total: batches.reduce((n, b) => n + b.length, 0) };
                }
                // Not from where it stopped: batches() holds only the rows not
                // yet answered, so counting on from there skipped a batch (live
                // test 2026-10-12: 250 of 600 never sent after Try again).
                this.stoppedAt = null;
                for (let i = 0; i < batches.length; i++) {
                    if (this.stopRequested) { this.stoppedAt = i; break; }
                    try {
                        const res = await api.post('/admin/resident-imports/' + encodeURIComponent(this.importRef) + '/rows', { rows: batches[i].map((r) => this.payload(r)) });
                        const byRow = Object.fromEntries((res.data?.results || []).map((x) => [x.row, x]));
                        batches[i].forEach((r) => {
                            const x = byRow[r.row];
                            if (!x || x.outcome === 'failed') {
                                // Refused by the server: back with the rows to fix.
                                r.status = 'error';
                                r.errors = [x?.message || 'Not imported'];
                                r.outcome = null;
                                return;
                            }
                            r.outcome = x.outcome;
                            r.outcomeRef = x.ref || null;
                            r.outcomeMessage = x.message || null;
                        });
                        this.progress.done += batches[i].length;
                    } catch (e) {
                        this.stoppedAt = i;
                        const at = batches[i][0]?.row ?? '';
                        this.error = (e?.localised ? e.localised('en') : 'The connection dropped.') + ' The import paused at row ' + at + '. Try again to carry on from there; no one is added twice.';
                        if (!this.open) emit('toast', { message: 'Import ' + this.importRef + ' paused at row ' + at + ': the connection dropped. Open Add Record, Import from a file to carry on.', variant: 'error' });
                        break;
                    }
                }
                if (this.stoppedAt === null || this.stopRequested) await this.finish();
            } catch (e) {
                this.error = e?.localised ? e.localised('en') : 'The import could not start.';
                if (!this.importRef) this.step = 3;
            } finally {
                this.running = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        async finish() {
            try {
                const res = await api.post('/admin/resident-imports/' + encodeURIComponent(this.importRef) + '/finish');
                const d = res.data;
                this.summary = {
                    ref: d.ref,
                    created: d.created,
                    filled: d.filled,
                    // Rows left out in the review; rows not sent are not "left
                    // as they were" (live test 2026-10-12).
                    skipped: d.skipped + this.pending.filter((r) => r.status !== 'error' && !this.sendable(r)).length,
                    failed: this.leftovers,
                    households: d.households_created,
                    // Stopped, or finished with what is in after a drop.
                    stopped: this.stoppedAt !== null,
                };
                emit('residents-imported', { ref: d.ref });
                if (!this.open) emit('toast', { message: 'Import ' + d.ref + ' finished: ' + d.created + ' residents added.', variant: 'success' });
            } catch (e) {
                this.error = e?.localised ? e.localised('en') : 'The rows are in, but the import could not be closed. Open Past imports to see it.';
            }
        },

        stop() { this.stopRequested = true; },

        // Leaving the page ends the import part way: the browser asks first.
        guardLeave(e) {
            if (!this.running) return;
            e.preventDefault();
            e.returnValue = '';
        },

        get notAdded() {
            return this.rows.filter((r) => r.status === 'error' || (r.outcome && r.outcome !== 'created') || (!r.outcome && r.status !== 'error'));
        },

        rowReason(r) {
            if (r.status === 'outside') return 'Brgy. ' + r.barangay + ': not your barangay (' + this.scope + ')';
            if (r.status === 'error') return r.errors.join('; ');
            if (r.outcome === 'failed') return r.outcomeMessage || 'Not imported';
            if (r.outcome === 'filled') return 'Already in the registry as ' + r.outcomeRef + '; blank details filled in';
            if (r.outcome === 'skipped') return r.outcomeRef ? 'Already in the registry as ' + r.outcomeRef : (r.twinOf ? 'Same person as row ' + r.twinOf : (r.outcomeMessage || 'Left out'));
            if (r.status === 'match') return 'Already in the registry as ' + r.match.ref;
            if (r.status === 'twice') return 'Same person as row ' + r.twinOf;
            return 'Not sent';
        },

        downloadNotAdded() {
            const rows = this.notAdded;
            const base = this.fileName.replace(/\.[^.]+$/, '');
            this.lib.downloadCsv(base + ' - not added.csv', [['Row', 'Why it was not added', ...this.headers], ...rows.map((r) => [r.row, this.rowReason(r), ...r.cells])]);
        },

        downloadTemplate() {
            const go = () => this.lib.downloadTemplate(this.barangays);
            if (this.lib) go(); else this.openImport().then(go);
        },

        showImported(ref) {
            emit('registry-show-import', { ref });
            this.open = false;
        },

        // ---------------------------------------------------- Past imports

        async loadHistory() {
            this.historyLoading = true;
            this.historyFailed = false;
            try {
                const res = await api.get('/admin/resident-imports', { params: { per_page: 50 } });
                this.history = res.data || [];
            } catch {
                this.historyFailed = true;
            } finally {
                this.historyLoading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        askUndo(imp) {
            this.undoTarget = imp;
            this.undoReason = '';
            this.$nextTick(() => window.renderIcons?.());
        },

        async confirmUndo() {
            const imp = this.undoTarget;
            if (!imp || this.undoing || this.undoReason.trim().length < 5) return;
            this.undoing = true;
            try {
                const res = await api.post('/admin/resident-imports/' + encodeURIComponent(imp.ref) + '/undo', { reason: this.undoReason.trim() });
                this.history = this.history.map((h) => (h.ref === imp.ref ? res.data : h));
                if (this.summary?.ref === imp.ref) this.summary = { ...this.summary, undone: res.data.undo_summary };
                this.undoTarget = null;
                emit('residents-imported', { ref: imp.ref });
                const s = res.data.undo_summary || {};
                emit('toast', { message: 'Import ' + imp.ref + ' undone: ' + (s.removed || 0) + ' records removed' + (s.kept ? ', ' + s.kept + ' kept because they are in use' : '') + '.', variant: 'success' });
            } catch (e) {
                emit('toast', { message: e?.localised ? e.localised('en') : 'Could not undo this import.', variant: 'error' });
            } finally {
                this.undoing = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        undoneText(h) {
            const s = h.undo_summary || {};
            const kept = s.kept ? ' · ' + s.kept + ' kept (in use)' : '';
            return (s.removed || 0) + ' removed' + kept;
        },
    };
}
