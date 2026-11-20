import api from './api/client';

// Dokyu / Tulong -> Import records (owner, 2026-10-12): the window's state and
// steps. Reading the file and cleaning its values is in request-import.js,
// loaded when the window first opens.
//
//   1 Choose a file   2 Match columns   3 Review (who each record is for)   4 Import
//
// x-data="requestImport('tulong', keys)": `keys` are the type's service keys,
// so the button shows only to an officer who may release one of them (asking
// the server would pop "no permission" at everyone else). Opened with the
// window event `open-request-import` ({ type }); tells the page
// `requests-imported` ({ type, ref }) when records were added.

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

const emit = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));

const letter = (i) => {
    let s = '';
    for (let n = i + 1; n > 0; n = Math.floor((n - 1) / 26)) s = String.fromCharCode(65 + ((n - 1) % 26)) + s;
    return s;
};

export function requestImport(type, keys = [], barangayList = []) {
    return {
        type,
        keys,
        barangayList,
        open: false,
        panel: 'import',
        step: 1,
        lib: null,
        busy: false,
        busyText: '',
        error: '',
        dragging: false,

        services: [],
        servicesLoaded: false,

        fileName: '',
        format: '',
        tables: [],
        tableIndex: 0,
        headerRow: 0,
        headers: [],
        body: [],
        mapping: [],
        fields: [],
        fixedBarangay: '',
        barangayValues: [],
        fixedService: '',
        serviceValues: [],
        dateOrder: 'mdy',
        dateAmbiguous: false,
        surnameFirst: false,

        rows: [],
        filter: 'all',
        reviewSearch: '',
        page: 1,
        perPage: 50,

        importRef: null,
        progress: { done: 0, total: 0 },
        stopRequested: false,
        running: false,
        stoppedAt: null,
        // Every record is in, but closing the import (POST .../finish) failed.
        closeFailed: false,
        summary: null,

        history: [],
        historyLoading: false,
        historyFailed: false,
        undoTarget: null,
        undoReason: '',
        undoing: false,

        letter,
        reasonText: (codes) => (codes || []).map((c) => REASONS[c] || c).join(' · '),

        get label() { return this.type === 'tulong' ? 'Tulong' : 'Dokyu'; },
        get what() { return this.type === 'tulong' ? 'assistance records' : 'document records'; },
        get serviceWord() { return this.type === 'tulong' ? 'Type of assistance' : 'Document'; },
        get allowed() {
            const s = this.$store.session;
            return !!s?.canPerm && this.keys.some((k) => s.canPerm(k, 'release'));
        },
        get scope() {
            const s = this.$store.session.account?.scope;
            return s && s !== 'Municipal' ? s : null;
        },
        get barangays() { return this.barangayList; },

        // ---------------------------------------------------- Opening

        async openImport(panel = 'import') {
            this.open = true;
            this.panel = panel;
            this.error = '';
            if (!this.lib) {
                try {
                    this.lib = await import('./request-import.js');
                    this.fields = this.lib.fieldsFor(this.type);
                } catch {
                    this.error = 'The import could not load. Check the connection and open it again.';
                }
            }
            if (!this.servicesLoaded || panel === 'history') await this.loadHistory();
            this.$nextTick(() => window.renderIcons?.());
        },

        async loadHistory() {
            this.historyLoading = true;
            this.historyFailed = false;
            try {
                const res = await api.get('/admin/' + this.type + '/imports', { params: { per_page: 50 } });
                this.history = res.data || [];
                this.services = res.meta?.services || [];
                this.servicesLoaded = true;
            } catch {
                this.historyFailed = true;
            } finally {
                this.historyLoading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },

        startOver() {
            if (this.running) return;
            Object.assign(this, {
                step: 1, error: '', fileName: '', format: '', tables: [], tableIndex: 0, headerRow: 0, headers: [], body: [], mapping: [],
                fixedBarangay: '', barangayValues: [], fixedService: '', serviceValues: [], rows: [], filter: 'all', reviewSearch: '', page: 1,
                importRef: null, summary: null, stoppedAt: null, closeFailed: false, progress: { done: 0, total: 0 }, surnameFirst: false,
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
                // The services first: the column guess reads the file's values
                // against them, and a file dropped while they were still loading
                // left its document column unmatched (live test 2026-10-12).
                if (!this.servicesLoaded) await this.loadHistory();
                const read = await this.lib.readFile(file);
                this.fileName = file.name;
                this.format = read.format;
                this.tables = read.tables;
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

        // ---------------------------------------------------- 2 Match columns

        useTable(i) {
            this.tableIndex = Number(i);
            this.headerRow = this.lib.guessHeaderRow(this.tables[this.tableIndex].rows, this.type);
            this.applyHeader(true);
        },

        applyHeader(guess = true) {
            const all = this.tables[this.tableIndex].rows;
            const h = Number(this.headerRow);
            const width = Math.max(...all.map((r) => r.length));
            this.headers = Array.from({ length: width }, (_, c) => (h >= 0 ? all[h]?.[c] : '') || 'Column ' + letter(c));
            const headerLine = h >= 0 ? all[h].join('|') : null;
            const lines = this.tables[this.tableIndex].lines || all.map((_, i) => i + 1);
            this.body = all.slice(h + 1).map((cells, k) => ({ cells, line: lines[h + 1 + k] })).filter((r) => r.cells.join('|') !== headerLine && r.cells.some(Boolean));
            if (guess) this.mapping = this.lib.guessMapping(this.headers, this.body.slice(0, 300).map((r) => r.cells), this.barangays, this.services, this.type);
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

        distinct(col) {
            const counts = {};
            this.body.forEach((r) => { const v = (r.cells[col] || '').trim(); if (v) counts[v] = (counts[v] || 0) + 1; });
            return Object.entries(counts).sort((x, y) => y[1] - x[1]).slice(0, 200);
        },

        refreshValues() {
            const b = this.column('barangay');
            const beforeB = Object.fromEntries(this.barangayValues.map((x) => [x.raw, x.match]));
            this.barangayValues = b < 0 ? [] : this.distinct(b).map(([raw, count]) => ({ raw, count, match: beforeB[raw] ?? this.lib.matchBarangay(raw, this.barangays) ?? '' }));
            if (this.scope && b < 0) this.fixedBarangay = this.scope;

            const s = this.column('service');
            const beforeS = Object.fromEntries(this.serviceValues.map((x) => [x.raw, x.match]));
            this.serviceValues = s < 0 ? [] : this.distinct(s).map(([raw, count]) => ({ raw, count, match: beforeS[raw] ?? this.lib.matchService(raw, this.services, this.type) ?? '' }));

            const dateCols = ['date', 'dob', 'date_applied'].map((f) => this.column(f)).filter((c) => c >= 0);
            if (dateCols.length) {
                const order = this.lib.dateOrderOf(this.body.flatMap((r) => dateCols.map((c) => r.cells[c])));
                this.dateOrder = order.dmy > order.mdy ? 'dmy' : 'mdy';
                this.dateAmbiguous = order.ambiguous || (order.mdy > 0 && order.dmy > 0);
            } else this.dateAmbiguous = false;
            this.$nextTick(() => window.renderIcons?.());
        },

        get hasName() { return this.column('full_name') >= 0 || (this.column('first_name') >= 0 && this.column('last_name') >= 0); },
        get hasBarangay() { return this.column('barangay') >= 0 || !!this.fixedBarangay; },
        get hasService() { return this.column('service') >= 0 || !!this.fixedService; },
        get hasDate() { return this.column('date') >= 0; },
        get unmatchedBarangays() { return this.barangayValues.filter((v) => !v.match).length; },
        get unmatchedServices() { return this.serviceValues.filter((v) => !v.match).length; },
        get fullNameHasCommas() {
            const f = this.column('full_name');
            return f >= 0 && this.body.slice(0, 50).some((r) => (r.cells[f] || '').includes(','));
        },
        get mappedCount() { return this.mapping.filter(Boolean).length; },
        get canCheck() { return this.hasName && this.hasBarangay && this.hasService && this.hasDate && this.body.length > 0 && !this.busy; },
        get missingForCheck() {
            const m = [];
            if (!this.hasName) m.push('the name');
            if (!this.hasBarangay) m.push('the barangay');
            if (!this.hasService) m.push(this.serviceWord.toLowerCase());
            if (!this.hasDate) m.push('the date');
            return m;
        },
        serviceName(key) { return this.services.find((s) => s.key === key)?.name || key || ''; },

        // ---------------------------------------------------- 3 Review

        async toReview() {
            if (!this.canCheck) return;
            this.busy = true;
            this.error = '';
            this.busyText = 'Checking ' + this.body.length.toLocaleString() + ' records…';
            const opts = {
                type: this.type,
                mapping: this.mapping,
                barangayMap: Object.fromEntries(this.barangayValues.filter((v) => v.match).map((v) => [v.raw, v.match])),
                serviceMap: Object.fromEntries(this.serviceValues.filter((v) => v.match).map((v) => [v.raw, v.match])),
                fixedBarangay: this.column('barangay') >= 0 ? '' : this.fixedBarangay,
                fixedService: this.column('service') >= 0 ? '' : this.fixedService,
                dateOrder: this.dateOrder,
                surnameFirst: this.surnameFirst,
            };
            const rows = this.body.map((r) => {
                const { status: recordStatus, ...row } = this.lib.buildRow(r.cells, r.line, opts);
                const outside = !!(this.scope && row.barangay && row.barangay !== this.scope && !row.issues.length);
                return {
                    ...row, recordStatus, cells: r.cells, status: outside ? 'outside' : row.issues.length ? 'error' : 'ready', errors: [...row.issues],
                    resident: null, candidates: [], duplicateOf: null, choice: 'auto', action: 'auto', outcome: null, outcomeRef: null, outcomeResident: null, outcomeMessage: null,
                };
            });
            try {
                const toCheck = rows.filter((r) => r.status === 'ready');
                for (let i = 0; i < toCheck.length; i += CHECK_BATCH) {
                    const chunk = toCheck.slice(i, i + CHECK_BATCH);
                    this.busyText = 'Matching records to residents… ' + Math.min(i + CHECK_BATCH, toCheck.length).toLocaleString() + ' of ' + toCheck.length.toLocaleString();
                    const res = await api.post('/admin/' + this.type + '/imports/check', { rows: chunk.map((r) => this.payload(r)) });
                    (res.data?.rows || []).forEach((result, k) => {
                        const r = chunk[k];
                        r.candidates = result.candidates || [];
                        r.resident = result.resident || null;
                        r.duplicateOf = result.duplicate_of || null;
                        if (result.status === 'error') { r.status = 'error'; r.errors = (result.errors || []).map((e) => e.message); } else r.status = result.status;
                        // A record with one sure resident goes to them; with candidates
                        // it waits for the officer's choice; with none, it keeps its name.
                        r.choice = r.status === 'linked' ? r.resident.ref : r.status === 'possible' ? '' : 'none';
                    });
                }
            } catch (e) {
                this.busy = false;
                this.error = e?.localised ? e.localised('en') : 'Could not check the records. Check the connection and try again.';
                return;
            }
            this.rows = rows;
            this.filter = this.counts.possible ? 'possible' : this.counts.error ? 'error' : 'all';
            this.page = 1;
            this.step = 3;
            this.busy = false;
            this.$nextTick(() => window.renderIcons?.());
        },

        payload(r) {
            return {
                row: r.row, first_name: r.first_name, middle_name: r.middle_name || null, last_name: r.last_name, suffix: r.suffix || null,
                sex: r.sex, dob: r.dob, barangay: r.barangay, sitio: r.sitio, contact: r.contact,
                service_key: r.service_key, status: r.recordStatus, date: r.date, date_applied: r.date_applied,
                amount: r.amount, source_ref: r.source_ref, purpose: r.purpose, remarks: r.remarks, released_to: r.released_to,
                resident: r.choice === '' ? 'none' : r.choice || 'auto', action: r.action,
            };
        },

        get pending() { return this.rows.filter((r) => !r.outcome); },
        get counts() {
            const c = { all: 0, linked: 0, possible: 0, none: 0, duplicate: 0, error: 0, outside: 0 };
            this.pending.forEach((r) => { c.all++; c[r.status] = (c[r.status] || 0) + 1; });
            return c;
        },
        get unchosen() { return this.pending.filter((r) => r.status === 'possible' && r.choice === '' && r.action !== 'skip').length; },
        get filtered() {
            let list = this.filter === 'all' ? this.pending : this.pending.filter((r) => r.status === this.filter);
            const q = this.reviewSearch.trim().toLowerCase();
            if (q) list = list.filter((r) => (this.displayName(r) + ' ' + r.row + ' ' + (r.source_ref || '') + ' ' + this.serviceName(r.service_key)).toLowerCase().includes(q));
            return list;
        },
        get pageRows() { return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage); },
        get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },
        setFilter(f) { this.filter = f; this.page = 1; this.$nextTick(() => window.renderIcons?.()); },
        setReviewSearch(v) { this.reviewSearch = v; this.page = 1; this.$nextTick(() => window.renderIcons?.()); },
        displayName(r) { return [r.first_name, r.middle_name, r.last_name, r.suffix].filter(Boolean).join(' '); },
        willImport(r) { return ['linked', 'possible', 'none'].includes(r.status) && r.action !== 'skip'; },
        get plan() {
            let add = 0;
            let linked = 0;
            let skip = 0;
            let fix = 0;
            // Already imported is its own count, not "left out": the finished
            // import's Left out is what the officer chose to leave out
            // (flows review, 2026-11-20: the footer said 1 left out, the
            // summary 0).
            let already = 0;
            this.pending.forEach((r) => {
                if (r.status === 'error') fix++;
                else if (r.status === 'duplicate') already++;
                else if (this.willImport(r)) { add++; if (r.choice && r.choice !== 'none') linked++; } else if (r.status !== 'outside') skip++;
            });
            return { add, linked, skip, fix, already };
        },
        candidateLabel(c) { return c.name + (c.age != null ? ', ' + c.age : '') + ' (' + c.ref + ')'; },

        // ---------------------------------------------------- 4 Import

        batches() {
            const send = this.pending.filter((r) => this.willImport(r));
            const out = [];
            for (let i = 0; i < send.length; i += BATCH) out.push(send.slice(i, i + BATCH));
            return out;
        },

        async runImport() {
            if (this.running) return;
            const batches = this.batches();
            if (!batches.length) {
                // Every record is in and only closing the import failed: Try
                // again closes it, as resident-import-ui.js (QA 2026-11-18 #126).
                // It used to return here and do nothing.
                if (this.importRef && !this.summary) {
                    this.running = true;
                    try { await this.finish(); } finally {
                        this.running = false;
                        this.$nextTick(() => window.renderIcons?.());
                    }
                }
                return;
            }
            this.running = true;
            this.step = 4;
            this.error = '';
            this.stopRequested = false;
            try {
                if (!this.importRef) {
                    const columns = {};
                    this.mapping.forEach((f, i) => { if (f) columns[f] = this.headers[i]; });
                    const res = await api.post('/admin/' + this.type + '/imports', {
                        filename: this.fileName.slice(0, 255), format: this.format, total_rows: Math.max(1, this.pending.length), columns,
                    });
                    this.importRef = res.data.ref;
                    this.progress = { done: 0, total: batches.reduce((n, b) => n + b.length, 0) };
                }
                // batches() holds only the records not yet answered, so a resumed
                // import starts at the first of them.
                this.stoppedAt = null;
                for (let i = 0; i < batches.length; i++) {
                    if (this.stopRequested) { this.stoppedAt = i; break; }
                    try {
                        const res = await api.post('/admin/' + this.type + '/imports/' + encodeURIComponent(this.importRef) + '/rows', { rows: batches[i].map((r) => this.payload(r)) });
                        const byRow = Object.fromEntries((res.data?.results || []).map((x) => [x.row, x]));
                        batches[i].forEach((r) => {
                            const x = byRow[r.row];
                            if (!x || x.outcome === 'failed') { r.status = 'error'; r.errors = [x?.message || 'Not imported']; r.outcome = null; return; }
                            r.outcome = x.outcome;
                            r.outcomeRef = x.ref || null;
                            r.outcomeResident = x.resident_ref || null;
                            r.outcomeMessage = x.message || null;
                        });
                        this.progress.done += batches[i].length;
                    } catch (e) {
                        this.stoppedAt = i;
                        const at = batches[i][0]?.row ?? '';
                        this.error = (e?.localised ? e.localised('en') : 'The connection dropped.') + ' The import paused at row ' + at + '. Try again to carry on from there; nothing is added twice.';
                        if (!this.open) emit('toast', { message: 'Import ' + this.importRef + ' paused at row ' + at + ': the connection dropped. Open Import records to carry on.', variant: 'error' });
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
                const res = await api.post('/admin/' + this.type + '/imports/' + encodeURIComponent(this.importRef) + '/finish');
                const d = res.data;
                this.summary = {
                    ref: d.ref, created: d.created, linked: d.linked, unlinked: d.created - d.linked,
                    skipped: d.skipped, failed: this.rows.filter((r) => r.status === 'error' && !r.outcome).length,
                    already: this.rows.filter((r) => r.status === 'duplicate').length,
                    // Stopped, or finished with what is in after a drop (not when
                    // every record was sent and only the close was retried).
                    stopped: this.stoppedAt !== null && !this.closeFailed,
                };
                this.closeFailed = false;
                this.error = '';
                emit('requests-imported', { type: this.type, ref: d.ref });
                if (!this.open) emit('toast', { message: 'Import ' + d.ref + ' finished: ' + d.created + ' ' + this.what + ' added.', variant: 'success' });
                this.loadHistory();
            } catch (e) {
                this.error = e?.localised ? e.localised('en') : 'The records are in, but the import could not be closed. Try again to close it.';
                // The import stays In Progress on the server until it is closed.
                // The paused panel's Try again and Finish with what is in show
                // while stoppedAt is set: it showed "Import paused" with no
                // button at all (QA 2026-11-18 #126).
                if (this.stoppedAt === null) {
                    this.closeFailed = true;
                    this.stoppedAt = 0;
                }
            }
        },

        stop() { this.stopRequested = true; },
        guardLeave(e) {
            if (!this.running) return;
            e.preventDefault();
            e.returnValue = '';
        },

        get notAdded() { return this.rows.filter((r) => r.outcome !== 'created'); },
        rowReason(r) {
            if (r.status === 'outside') return 'Brgy. ' + r.barangay + ': not your barangay (' + this.scope + ')';
            if (r.status === 'error') return r.errors.join('; ');
            if (r.outcome === 'skipped') return r.outcomeMessage || 'Left out';
            if (r.status === 'duplicate') return 'Already imported as ' + r.duplicateOf;
            if (r.action === 'skip') return 'Left out in the review';
            return 'Not sent';
        },
        downloadNotAdded() {
            const base = this.fileName.replace(/\.[^.]+$/, '');
            this.lib.downloadCsv(base + ' - not added.csv', [['Row', 'Why it was not added', ...this.headers], ...this.notAdded.map((r) => [r.row, this.rowReason(r), ...r.cells])]);
        },
        downloadTemplate() {
            const go = () => this.lib.downloadTemplate(this.type, this.barangays, this.services);
            if (this.lib && this.servicesLoaded) go(); else this.openImport().then(go);
        },
        showImported(ref) {
            this.open = false;
            emit('requests-show-import', { type: this.type, ref });
        },

        // ---------------------------------------------------- Past imports

        askUndo(imp) { this.undoTarget = imp; this.undoReason = ''; this.$nextTick(() => window.renderIcons?.()); },
        async confirmUndo() {
            if (!this.undoTarget || this.undoReason.trim().length < 5 || this.undoing) return;
            this.undoing = true;
            try {
                const res = await api.post('/admin/' + this.type + '/imports/' + encodeURIComponent(this.undoTarget.ref) + '/undo', { reason: this.undoReason.trim() });
                const imp = res.data;
                emit('toast', { message: 'Import ' + imp.ref + ' undone: ' + (imp.undo_summary?.removed || 0) + ' records archived.', variant: 'success' });
                emit('requests-imported', { type: this.type, ref: imp.ref });
                if (this.summary?.ref === imp.ref) this.summary = { ...this.summary, undone: true };
                this.undoTarget = null;
                await this.loadHistory();
            } catch (e) {
                emit('toast', { message: e?.localised ? e.localised('en') : 'The import could not be undone.', variant: 'error' });
            } finally {
                this.undoing = false;
            }
        },
        when(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            return isNaN(d) ? iso : d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        },
    };
}
