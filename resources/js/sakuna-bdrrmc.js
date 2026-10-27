// Two BDRRMC forms filled from records, and saved forms reopened (owner,
// 2026-10-27, Phase 1). Merged into the BDRRMC Forms tab's own component
// (tab-bdrrmc-forms.blade.php: x-data="{ ...bdrrmcRecords(), ... }"), so
// `this` is that component: its rows, barangay, header and save().
//
//   Profile of Evacuees   GET /admin/sakuna/bdrrmc-forms/evacuees/draft?center&as_of
//   Relief Distribution   GET /admin/sakuna/bdrrmc-forms/rds/draft?operation&barangay&date&center
//   A saved form          GET /admin/sakuna/bdrrmc-forms/submissions/{ref}
//
// Drafting is read-only on the server: nothing is saved until the officer
// chooses Save Form. Each drafted row carries `_src`, its index in the draft's
// sources; Save sends those references and the server checks them against a
// fresh draft and writes where every row came from. Values the records do not
// hold arrive blank with a warning and are typed by hand, or left blank.

import api from './api/client';
import { PAPER, captionRow, headerRows, leavesOf, storedKeys, totalCells } from './bdrrmc-paper';

const TZ = 'Asia/Manila';
const BRACKETS = ['pre03', 'pre46', 'ch712', 'yth1317', 'ad1859', 'sc60'];

/** Now, as a datetime-local value in Esperanza's time. */
function manilaNow() {
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
        timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(new Date()).map((p) => [p.type, p.value]));
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
}

/** An ISO instant as Esperanza reads it, e.g. "Oct 27, 2026, 9:00 AM". */
function manilaText(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(iso);
    return d.toLocaleString('en-US', { timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

/** A date as the paper form is filled in by hand, e.g. "October 27, 2026". */
function longDate(value) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''));
    if (!m) return value ? String(value) : '';
    return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], 12)).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric', year: 'numeric' });
}

/** A time of day, e.g. "2:30 PM". */
function clockTime(value) {
    const m = /^(\d{2}):(\d{2})/.exec(String(value || ''));
    if (!m) return value ? String(value) : '';
    const h = +m[1];
    return `${((h + 11) % 12) + 1}:${m[2]} ${h < 12 ? 'AM' : 'PM'}`;
}

/** An instant's date and time in Esperanza, for the paper's "As of Date / Time". */
function manilaParts(iso) {
    if (!iso) return { date: '', time: '' };
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return { date: String(iso), time: '' };
    return {
        date: d.toLocaleDateString('en-US', { timeZone: TZ, month: 'long', day: 'numeric', year: 'numeric' }),
        time: d.toLocaleTimeString('en-US', { timeZone: TZ, hour: 'numeric', minute: '2-digit' }),
    };
}

/** A cell as the server compares it: blanks alike, numbers by value. */
function cell(v) {
    if (typeof v === 'boolean') return v ? '1' : '0';
    const s = v === null || v === undefined ? '' : String(v).trim();
    return s !== '' && !Number.isNaN(Number(s)) ? String(Number(s)) : s;
}

/**
 * Print one form window on its own. The windows sit fixed inside the page's
 * content panel, so printed in place they came out as a narrow column and a
 * long table was cut at the first page. A copy goes into a plain section at
 * the top of the page instead, and only that prints: full width, landscape,
 * flowing over as many pages as it needs. What was typed is carried over (a
 * copy keeps attributes, not live values); x-ignore keeps Alpine off it.
 */
function printArea(el) {
    if (!el) { window.print(); return; }
    document.getElementById('bdrrmc-print-root')?.remove();
    const copy = el.cloneNode(true);
    const from = el.querySelectorAll('input, select, textarea');
    const to = copy.querySelectorAll('input, select, textarea');
    from.forEach((s, i) => {
        const d = to[i];
        if (!d) return;
        if (s.type === 'checkbox' || s.type === 'radio') {
            if (s.checked) d.setAttribute('checked', ''); else d.removeAttribute('checked');
        } else if (s.tagName === 'SELECT') {
            // As the paper is written on: the choice as text, nothing for no
            // choice (it printed "Choose a barangay"), the flood level as its
            // number where the column asks for one.
            const text = document.createElement('span');
            text.className = s.className;
            text.textContent = s.value === '' ? '' : (s.dataset.print === 'value' ? s.value : (s.options[s.selectedIndex]?.text || ''));
            d.replaceWith(text);
        } else if (s.tagName === 'TEXTAREA' || ['text', 'number', 'date', 'time', 'search', ''].includes(s.type)) {
            // As plain text: a box prints only as much as fits its width.
            const text = document.createElement('span');
            text.className = s.className;
            text.textContent = s.type === 'date' ? longDate(s.value) : (s.type === 'time' ? clockTime(s.value) : s.value);
            text.style.whiteSpace = 'pre-wrap';
            d.replaceWith(text);
        } else {
            d.setAttribute('value', s.value);
        }
    });
    const root = document.createElement('div');
    root.id = 'bdrrmc-print-root';
    // Fishing and the relief sheet are portrait pages on the barangay's files.
    if (el.dataset.printOrientation === 'portrait') root.dataset.orientation = 'portrait';
    root.setAttribute('x-ignore', '');
    root.appendChild(copy);
    document.body.appendChild(root);
    document.documentElement.classList.add('bdrrmc-printing');
    const done = () => {
        root.remove();
        document.documentElement.classList.remove('bdrrmc-printing');
        window.removeEventListener('afterprint', done);
    };
    window.addEventListener('afterprint', done);
    window.print();
}

function bdrrmcRecords() {
    return {
        // The draft each form was filled from, with the rows left out of it.
        drafted: { evacuees: null, rds: null },
        fillOpen: false,
        fill: { center: '', asOf: '', operation: '', date: '', rdsCenter: '', loading: false, error: '' },
        centers: [],
        operations: [],
        fillSourcesLoaded: false,
        address: '',
        // The relief sheet has two dates on paper: when the disaster happened
        // (the shared Date of Occurrence) and when relief was given.
        distributedOn: '',
        // The evacuee profile's "as of", for a form typed by hand: the paper
        // asks for a date and a time apart.
        asOfTyped: '',
        asOfDate: '',
        asOfTime: '',
        // Bumped whenever a form's rows are replaced, added or removed (not while
        // typing): the sheets are keyed by it, so they are drawn again. A sheet
        // kept by position kept showing its old blank rows after Fill from
        // records replaced them.
        paperVersion: 0,
        // A saved form being read or reprinted.
        viewing: null,
        viewLoading: false,
        definitionsByKey: null,

        // -- Filling from records

        /** Filing the form, and reading the records it is drafted from. */
        canFill(key) {
            const s = this.$store.session;
            return s.canPerm('sakuna_damage', 'create') && s.canPerm(key === 'rds' ? 'sakuna_relief' : 'sakuna_evacuees', 'view');
        },
        async openFill() {
            this.fillOpen = !this.fillOpen;
            this.fill.error = '';
            if (!this.fill.asOf) this.fill.asOf = manilaNow();
            if (this.fillSourcesLoaded) return;
            this.fillSourcesLoaded = true;
            const [centers, relief] = await Promise.allSettled([
                api.getAll('/admin/sakuna/centers'),
                this.$store.session.canPerm('sakuna_relief', 'view') ? api.getAll('/admin/sakuna/relief') : Promise.resolve({ data: [] }),
            ]);
            this.centers = centers.status === 'fulfilled' ? (centers.value.data || []) : [];
            const rows = relief.status === 'fulfilled' ? (relief.value.data || []) : [];
            this.operations = [...new Set(rows.map((d) => d.operation).filter(Boolean))].sort();
        },
        /**
         * Draft the open form from the records and put it in the table. Rows
         * already typed are replaced, after asking.
         */
        async fillFromRecords(key) {
            const f = this.fill;
            f.error = '';
            let path;
            if (key === 'evacuees') {
                if (!f.center) { f.error = 'Choose the evacuation centre.'; return; }
                if (!f.asOf) { f.error = 'Choose the date and time of the report.'; return; }
                path = '/admin/sakuna/bdrrmc-forms/evacuees/draft?' + new URLSearchParams({ center: f.center, as_of: f.asOf });
            } else {
                if (!f.operation.trim()) { f.error = 'Choose the relief operation.'; return; }
                if (!this.barangay) { f.error = 'Choose the barangay first.'; return; }
                const q = { operation: f.operation.trim(), barangay: this.barangay };
                if (f.date) q.date = f.date;
                if (f.rdsCenter) q.center = f.rdsCenter;
                path = '/admin/sakuna/bdrrmc-forms/rds/draft?' + new URLSearchParams(q);
            }
            const rows = this[this.rowsOf[key]] || [];
            if (rows.some((r) => !this.isBlankRow(r)) && !window.confirm('Replace the rows already on this form with the ones from the records?')) return;

            f.loading = true;
            try {
                const res = await api.get(path);
                this.applyDraft(key, res.data);
                this.fillOpen = false;
                const n = res.data.rows.length;
                this.$dispatch('toast', { message: n ? `${n} row${n === 1 ? '' : 's'} filled from the records. Check the flagged rows, then save.` : 'Nothing in the records matches; no rows were filled.', variant: n ? 'success' : 'info' });
            } catch (err) {
                f.error = err && err.explain ? err.explain('en') : 'Could not fill the form from the records.';
            } finally {
                f.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        applyDraft(key, draft) {
            this.drafted[key] = { ...draft, removed: [] };
            const h = draft.header || {};
            if (h.barangay) {
                this.barangay = h.barangay;
                this.fillOfficials();
            }
            if (key === 'evacuees') {
                this.center = h.evacuation_center || '';
                this.address = h.address || '';
            } else {
                // The forms share these fields: what another form left there is
                // not this sheet's. Blank when the rows do not agree on one.
                this.center = h.evacuation_center || '';
                this.distributedOn = h.distribution_date || '';
                if (h.distributed_by) this.signoff.prepared = h.distributed_by;
            }
            this[this.rowsOf[key]] = draft.rows.map((r, i) => ({ ...r, _src: i }));
            this.padRows(key);
            this.paperVersion++;
        },
        /**
         * A row's x-for key. Keyed by position, the empty starting row's
         * element was reused for the first drafted row and kept showing it as
         * typed by hand; a drafted row is keyed by its draft and source.
         */
        rowKey(key, r, ri) {
            const d = this.drafted[key];
            return d && r._src !== undefined && r._src !== null ? `${d.generated_at}:${r._src}` : `typed:${ri}`;
        },
        /** The record a drafted row came from, or null for a row typed by hand. */
        rowSource(key, r) {
            const d = this.drafted[key];
            return d && r._src !== undefined && r._src !== null ? d.sources[r._src] : null;
        },
        rowWarnings(key, r) {
            return (this.rowSource(key, r)?.warnings) || [];
        },
        rowAlert(key, r) {
            const w = this.rowWarnings(key, r);
            if (w.some((x) => x.level === 'conflict')) return 'conflict';
            if (w.some((x) => x.level === 'warning')) return 'warning';
            return '';
        },
        /** A drafted cell the officer has changed: marked on the screen. */
        isEdited(key, r, column) {
            const d = this.drafted[key];
            if (!d || r._src === undefined || r._src === null) return false;
            return cell(d.rows[r._src][column]) !== cell(r[column]);
        },
        editedClass(key, r, column) {
            return this.isEdited(key, r, column) ? 'ring-2 ring-amber-300 bg-amber-50' : '';
        },
        rowClass(key, r, ri) {
            const alert = this.rowAlert(key, r);
            if (alert === 'conflict') return 'bg-rose-50/70';
            if (alert === 'warning') return 'bg-amber-50/60';
            return ri % 2 === 1 ? 'bg-slate-50/50' : '';
        },
        /** Leaving out a drafted row needs a reason, saved with the form. */
        removeRow(key, ri) {
            const rows = this[this.rowsOf[key]];
            const src = this.rowSource(key, rows[ri]);
            if (src) {
                const reason = (window.prompt(`Why is ${src.ref} left out of the form? (saved with it)`) || '').trim();
                if (reason.length < 3) return;
                this.drafted[key].removed.push({ ref: src.ref, reason });
            }
            rows.splice(ri, 1);
            this.padRows(key);
            this.paperVersion++;
        },
        /** Drop a form's draft: its rows become ordinary typed rows. */
        forgetDraft(key) {
            this.drafted[key] = null;
            (this[this.rowsOf[key]] || []).forEach((r) => { delete r._src; });
        },
        /** What Save tells the server about where the rows came from. */
        sourcePayload(key, rows) {
            const d = this.drafted[key];
            if (!d) return { kind: 'manual' };
            return {
                kind: 'records',
                params: d.params,
                row_refs: rows.map((r) => (r._src === undefined || r._src === null ? null : d.sources[r._src].ref)),
                removed: d.removed,
                generated_at: d.generated_at,
            };
        },
        /** A row as the server takes it: the form's own columns only. */
        cleanRow(r) {
            return Object.fromEntries(Object.entries(r).filter(([k]) => !k.startsWith('_')));
        },

        /** A column's TOTAL, as the damage forms' last row has it. */
        formTotal(key, column) {
            return (this[this.rowsOf[key]] || []).reduce((s, r) => s + (Number(r[column]) || 0), 0);
        },

        // -- The evacuee profile's totals

        sexTotal(r, sex) {
            return BRACKETS.reduce((s, b) => s + (Number(r[b + sex]) || 0), 0);
        },
        columnTotal(column) {
            return (this.evacueeRows || []).reduce((s, r) => s + (Number(r[column]) || 0), 0);
        },
        sexColumnTotal(sex) {
            return (this.evacueeRows || []).reduce((s, r) => s + this.sexTotal(r, sex), 0);
        },
        /** The family's recorded headcount, or the people in its age columns for a typed row. */
        rowHeadcount(r) {
            const src = this.rowSource('evacuees', r);
            return src ? src.headcount : this.sexTotal(r, 'm') + this.sexTotal(r, 'f');
        },
        familySizes() {
            const out = { lt5: 0, eq5: 0, gt5: 0 };
            (this.evacueeRows || []).filter((r) => !this.isBlankRow(r)).forEach((r) => {
                const n = this.rowHeadcount(r);
                out[n < 5 ? 'lt5' : n === 5 ? 'eq5' : 'gt5'] += 1;
            });
            return out;
        },
        /** A family is listed on the evacuee profile (its count lines stay blank until one is). */
        hasFamilies() {
            return (this.evacueeRows || []).some((r) => !this.isBlankRow(r));
        },
        fiveRule() {
            return this.drafted.evacuees?.summary?.five_member_rule || 'unresolved';
        },
        recordedHeadcount() {
            return (this.evacueeRows || []).filter((r) => !this.isBlankRow(r)).reduce((s, r) => s + this.rowHeadcount(r), 0);
        },

        // -- The paper form (resources/js/bdrrmc-paper.js)

        /** The form on screen: the one being filled in, or the saved one being read. */
        paperKey(mode) {
            return mode === 'view' ? this.viewing?.form : this.openForm;
        },
        paperSpec(mode) {
            return PAPER[this.paperKey(mode)] || { orientation: 'landscape', rows: 20, rowHeight: 15, columns: [], sign: {} };
        },
        paperLeaves(mode) { return leavesOf(this.paperSpec(mode).columns); },
        paperHead(mode) { return headerRows(this.paperSpec(mode).columns); },
        paperCaption(mode) { return captionRow(this.paperSpec(mode)); },
        paperTotals(mode) { return totalCells(this.paperSpec(mode)); },
        paperRowsOf(mode) {
            return mode === 'view' ? (this.viewing?.rows || []) : (this[this.rowsOf[this.openForm]] || []);
        },
        /**
         * Whole sheets, as on paper: 20 numbered rows a sheet (25 on the relief
         * sheet), a further sheet when they are full, numbering carried on. A
         * saved form's rows fill its sheets and the rest stay blank.
         */
        paperPages(mode) {
            const spec = PAPER[this.paperKey(mode)];
            if (!spec) return [];
            const all = this.paperRowsOf(mode);
            const n = spec.rows;
            const count = Math.max(1, Math.ceil(all.length / n));
            const v = this.paperVersion;
            return Array.from({ length: count }, (_, p) => ({
                p,
                key: p + ':' + v,
                last: p === count - 1,
                slots: Array.from({ length: n }, (__, k) => ({ i: p * n + k, r: all[p * n + k] || null })),
            }));
        },
        /** Fill the form's last sheet with blank rows (never fewer than one sheet). */
        padRows(key) {
            const n = PAPER[key]?.rows || 1;
            const rows = this[this.rowsOf[key]];
            const target = Math.max(n, Math.ceil(rows.length / n) * n);
            if (rows.length >= target) return;
            while (rows.length < target) this.blankRow(key);
            this.paperVersion++;
        },
        /** Another sheet: as many blank rows as the paper holds. */
        addPage(key) {
            const n = PAPER[key]?.rows || 1;
            for (let k = 0; k < n; k++) this.blankRow(key);
            this.paperVersion++;
        },
        paperNo(mode, i, side) {
            const spec = this.paperSpec(mode);
            return String(i + 1) + (spec.noDot ? '.' : '');
        },
        paperNoAlign(mode, key) {
            const a = this.paperSpec(mode).noAlign || ['left'];
            return key === 'no2' ? (a[1] || a[0]) : a[0];
        },
        paperHeadClass(h) {
            return [h.leaf ? 'pp-leaf' : 'pp-group', h.hl ? 'pp-hl-' + h.hl : '', h.small ? 'pp-small' : ''].join(' ');
        },
        /** The evacuee profile's computed cells; blank on a blank row. */
        paperComputed(r, type) {
            if (!r || this.isBlankRow(r)) return '';
            if (type === 'sum-m') return this.sexTotal(r, 'm');
            if (type === 'sum-f') return this.sexTotal(r, 'f');
            return this.sexTotal(r, 'm') + this.sexTotal(r, 'f');
        },
        /** A saved cell as written on the form. */
        paperValue(r, c) {
            if (!r) return '';
            if (['sum-m', 'sum-f', 'sum-all'].includes(c.type)) return this.paperComputed(r, c.type);
            const v = r[c.key];
            return v === null || v === undefined ? '' : (typeof v === 'boolean' ? (v ? 'Yes' : '') : v);
        },
        /** The TOTAL row's figure for a column: the rows written on, summed. */
        paperTotal(mode, t) {
            if (!t.sum) return '';
            const rows = this.paperRowsOf(mode).filter((r) => r && !this.isBlankRow(r));
            if (!rows.length) return '';
            const value = (r) => (['sum-m', 'sum-f', 'sum-all'].includes(t.type) ? this.paperComputed(r, t.type) : Number(r[t.key]) || 0);
            const sum = rows.reduce((s, r) => s + value(r), 0);
            return t.type === 'num' ? sum.toLocaleString('en-PH', { maximumFractionDigits: 2 }) : sum;
        },
        /** Who signs, under the paper's titles, with the names on this form. */
        paperSigners(mode) {
            const s = this.paperSpec(mode).sign || {};
            const m = this.viewing?.meta || {};
            const name = (k) => (mode === 'view' ? (m[k + '_by'] || '') : '');
            const out = [{ role: s.preparedRole || 'Prepared by:', title: s.prepared, model: 'prepared', name: name('prepared') }];
            if (!s.twoColumn) out.push({ role: 'Consolidated by:', title: s.consolidated, model: 'consolidated', name: name('consolidated') });
            out.push({ role: s.notedRole || 'Noted by:', title: s.noted, model: 'noted', name: name('noted') });
            return out;
        },
        /**
         * A saved form is drawn as the paper form when it was filed against
         * the paper's columns; one saved under older columns (before
         * 2026-10-27, or the housing form's two estimated-damage columns) keeps
         * the plain table it was saved with, so nothing it holds is hidden.
         */
        paperFits() {
            const v = this.viewing;
            const spec = v && PAPER[v.form];
            if (!spec) return false;
            const filed = (v.meta?.definition?.columns || this.definitionsByKey?.[v.form]?.columns || []).map((c) => c.key);
            const keys = storedKeys(spec);
            if (filed.length && (filed.length !== keys.length || !keys.every((k) => filed.includes(k)))) return false;
            return (v.rows || []).every((r) => Object.keys(r).every((k) => keys.includes(k)));
        },
        /**
         * A saved evacuee profile's people: the headcount recorded at check-in
         * and those its age and sex columns hold, as filed (the summary the
         * server kept), else counted from its rows.
         */
        viewHeadcount() {
            const rows = (this.viewing?.rows || []).filter((r) => r && !this.isBlankRow(r));
            const counted = rows.reduce((s, r) => s + this.sexTotal(r, 'm') + this.sexTotal(r, 'f'), 0);
            const sum = this.viewing?.meta?.summary || {};
            return { recorded: sum.headcount ?? counted, classified: sum.grand_total ?? counted };
        },
        paperDate(value) { return longDate(value); },
        paperAsOf(iso) { return manilaParts(iso); },

        // -- A saved form, reopened

        async viewSubmission(sub) {
            this.viewLoading = true;
            try {
                const [res] = await Promise.all([
                    api.get('/admin/sakuna/bdrrmc-forms/submissions/' + encodeURIComponent(sub.ref)),
                    this.definitionsByKey ? Promise.resolve() : api.get('/admin/sakuna/bdrrmc-forms').then((d) => {
                        this.definitionsByKey = Object.fromEntries((d.data || []).map((x) => [x.key, x]));
                    }).catch(() => { this.definitionsByKey = {}; }),
                ]);
                this.viewing = res.data;
                this.$nextTick(() => window.renderIcons?.());
            } catch (err) {
                this.$dispatch('toast', { message: err && err.explain ? err.explain('en') : 'Could not open the saved form.', variant: 'error' });
            } finally {
                this.viewLoading = false;
            }
        },
        /**
         * The saved form's own columns: as filed, else the current template;
         * plus any column its rows carry that the template no longer has (a
         * form saved before the 2026-10-27 layouts), so nothing it holds is
         * hidden.
         */
        viewColumns() {
            const v = this.viewing;
            if (!v) return [];
            const cols = [...(v.meta?.definition?.columns || this.definitionsByKey?.[v.form]?.columns || [])];
            const known = new Set(cols.map((c) => c.key));
            (v.rows || []).forEach((r) => Object.keys(r).forEach((k) => {
                if (!known.has(k)) { known.add(k); cols.push({ key: k, label: k.replace(/_/g, ' ') }); }
            }));
            return cols;
        },
        /** The heading the barangay's paper form prints above the title. */
        viewHeading() {
            const v = this.viewing;
            if (!v) return [];
            const place = (v.barangay ? 'Barangay ' + v.barangay + ', ' : '') + 'Esperanza, Masbate';
            if (v.form === 'rds') return ['Republic of the Philippines', place];
            if (v.form === 'evacuees') return [];
            return ['Barangay Disaster Risk Reduction and Management Committee', place];
        },
        /** Who signs, under the titles the barangay's paper form prints. */
        viewSignoff() {
            return {
                evacuees: { prepared: 'Assigned Barangay Official (Evacuation Center)', noted: 'Punong Barangay' },
                'human-housing': { prepared: 'Assigned Kagawad (Purok)', noted: 'Punong Barangay' },
                agriculture: { prepared: 'Assigned Kagawad (Purok)', noted: 'Punong Barangay/BDRRMC Chairperson' },
                fishing: { prepared: 'Assigned Kagawad (Purok)', noted: 'Punong Barangay' },
                business: { prepared: 'Assigned Kagawad (Purok)', noted: 'Punong Barangay/BDRRMC Chairperson' },
                rds: { prepared: 'Barangay Official', noted: 'Punong Barangay' },
            }[this.viewing?.form] || { prepared: 'Assigned Barangay Official', noted: 'Punong Barangay' };
        },
        viewPortrait() {
            return (PAPER[this.viewing?.form]?.orientation) === 'portrait';
        },
        /** A numeric column of a damage form gets a TOTAL; the evacuee profile has its own. */
        viewTotals(column) {
            return ['integer', 'number'].includes(column.type);
        },
        viewName() {
            const v = this.viewing;
            return v ? (v.meta?.definition?.name || this.formNames[v.form] || v.form) : '';
        },
        viewCode() {
            const v = this.viewing;
            return v ? (v.meta?.definition?.code || this.definitionsByKey?.[v.form]?.code || '') : '';
        },
        viewRowSource(i) {
            return (this.viewing?.meta?.source?.rows || []).find((r) => r.index === i) || null;
        },
        viewRowTotals(row) {
            return { m: this.sexTotal(row, 'm'), f: this.sexTotal(row, 'f') };
        },
        viewColumnTotal(column) {
            return (this.viewing?.rows || []).reduce((s, r) => s + (Number(r[column]) || 0), 0);
        },
        /** The header the form was saved with, in reading order. */
        viewHeader() {
            const m = this.viewing?.meta || {};
            const v = this.viewing || {};
            const fields = [
                ['Region', m.region || 'Region V'],
                ['Province', m.province],
                ['Municipality', m.municipality],
                ['Barangay', v.barangay],
                ['Type of Disaster', m.disaster || v.period],
                ['Date of Occurrence', m.occurred_on],
                ['Name of Evacuation Center', m.evacuation_center],
                ['Address', m.address],
                ['As of', m.as_of ? manilaText(m.as_of) : null],
                ['Date of Relief Distribution', m.distribution_date],
                ['Purok', v.form === 'evacuees' ? null : m.purok],
            ];
            return fields.filter(([, value]) => value !== null && value !== undefined && value !== '');
        },
        viewWhen(iso) { return manilaText(iso); },
        printSaved(event) { printArea(event?.target?.closest('[data-print-area]')); },
    };
}

export function register() {
    window.bdrrmcRecords = bdrrmcRecords;
    window.bdrrmcPrint = printArea;
}
