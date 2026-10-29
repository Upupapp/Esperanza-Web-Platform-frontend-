// Sakuna's Reports tab (owner, 2026-10-28/30). Every figure, chart and table
// row comes from the server's one calculation (GET /admin/sakuna/reports/
// summary and /incidents) under the same filters, so the cards, the charts,
// the table and the exports agree. Export is its own permission, checked and
// logged on the server (POST /admin/sakuna/reports/export); the file is built
// here from what that returns, as the other exports are.
import api from './api/client';
import { loadXlsx } from './resident-import';

const blankFilters = () => ({ barangay: '', from: '', to: '', type: '', status: '', livelihood_group: '', livelihood_code: '' });
const toast = (message, variant = 'success') => window.dispatchEvent(new CustomEvent('toast', { detail: { message, variant } }));

export function sakunaReport() {
    return {
        f: blankFilters(),
        summary: null,
        rows: [],
        page: 1,
        lastPage: 1,
        total: 0,
        loading: false,
        failed: false,
        exporting: '',

        init() {
            this.$store.livelihood?.load();
            this.load();
        },
        params(extra = {}) {
            return Object.fromEntries(Object.entries({ ...this.f, ...extra }).filter(([, v]) => v !== '' && v !== null));
        },
        get rangeError() { return this.f.from && this.f.to && this.f.to < this.f.from ? 'The end date is before the start date.' : ''; },
        async load(page = 1) {
            if (this.rangeError) return;
            this.loading = true;
            this.failed = false;
            this.page = page;
            try {
                const [s, r] = await Promise.all([
                    api.get('/admin/sakuna/reports/summary', { params: this.params() }),
                    api.get('/admin/sakuna/reports/incidents', { params: this.params({ page, per_page: 25 }) }),
                ]);
                this.summary = s.data;
                this.rows = r.data || [];
                this.total = r.meta?.total ?? this.rows.length;
                this.lastPage = r.meta?.last_page || 1;
            } catch {
                this.failed = true;
            } finally {
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            }
        },
        clear() { this.f = blankFilters(); this.load(1); },
        // A group change keeps the item only if it belongs to the group.
        groupChanged() { if (this.f.livelihood_code && !this.f.livelihood_code.startsWith(this.f.livelihood_group)) this.f.livelihood_code = ''; this.load(1); },

        // Charts, in the site's one look (clay-donut, clay-trend), from the
        // server's figures: nothing is counted in the browser.
        reportTypeBreakdown() { return Object.entries(this.summary?.incidents.by_type || {}); },
        reportTrendLabels() { return (this.summary?.incidents.trend || []).map((t) => new Date(t.label + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })); },
        reportTrendData() { return (this.summary?.incidents.trend || []).map((t) => t.count); },
        statusRows() { return Object.entries(this.summary?.incidents.by_status || {}).map(([label, value]) => ({ label, value })); },
        livelihoodRows() { return (this.summary?.livelihood.groups || []).filter((g) => g.people).map((g) => ({ label: g.code + ' · ' + g.label, value: g.people })); },
        barangayRows() {
            const rows = Object.entries(this.summary?.incidents.by_barangay || {});
            const max = Math.max(1, ...rows.map((r) => r[1]));
            return rows.map(([label, count]) => ({ label, count, pct: Math.round((count / max) * 100) }));
        },

        async exportAs(format) {
            if (this.exporting || this.rangeError) return;
            this.exporting = format;
            try {
                const res = await api.post('/admin/sakuna/reports/export', { ...this.params(), format });
                const d = res.data;
                if (format === 'XLSX') await this.writeXlsx(d); else await this.writePdf(d);
                toast(format + ' downloaded.');
            } catch (err) {
                toast(err?.localised ? err.localised('en') : 'Could not export this report.', 'error');
            } finally {
                this.exporting = '';
            }
        },
        filterLines(d) {
            const f = d.filters || {};
            return [
                ['Report', d.title],
                ['Generated', new Date(d.generated_at).toLocaleString('en-PH') + ' by ' + d.generated_by],
                ['Period', d.summary.period.from + ' to ' + d.summary.period.to],
                ['Barangay', f.barangay || 'All barangays'],
                ['Disaster type', f.type || 'All types'],
                ['Incident status', f.status || 'All statuses'],
                ['Livelihood', f.livelihood_code ? this.$store.livelihood.label(f.livelihood_code) : (f.livelihood_group || 'All')],
            ];
        },
        summaryLines(s) {
            return [
                ['Incidents', s.incidents.total],
                ['Evacuated individuals', s.evacuated.individuals],
                ['Evacuated families', s.evacuated.families],
                ['Missing persons', s.persons.missing],
                ['Found', s.persons.found],
                ['Deceased', s.persons.deceased],
                ['Unidentified', s.persons.unidentified],
                ['Damage reports', s.damage.reports],
                ['Units affected', s.damage.units_affected],
            ];
        },
        columns() {
            return [
                { key: 'ref', label: 'Incident' }, { key: 'reported', label: 'Reported' }, { key: 'type', label: 'Disaster type' },
                { key: 'barangay', label: 'Barangay' }, { key: 'title', label: 'Summary' }, { key: 'status', label: 'Status' },
                { key: 'missing', label: 'Missing' }, { key: 'found', label: 'Found' }, { key: 'deceased', label: 'Deceased' },
                { key: 'damage_reports', label: 'Damage reports' }, { key: 'units_affected', label: 'Units affected' },
            ];
        },
        async writeXlsx(d) {
            const XLSX = await loadXlsx();
            const wb = XLSX.utils.book_new();
            const cover = [...this.filterLines(d), [], ['Summary'], ...this.summaryLines(d.summary), [], ['Notes'], ...d.summary.notes.map((n) => [n])];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(cover), 'Summary');
            const cols = this.columns();
            const rows = d.rows.map((r) => cols.map((c) => (c.key === 'reported' ? new Date(r.reported).toLocaleString('en-PH') : r[c.key])));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([cols.map((c) => c.label), ...rows]), 'Incidents');
            const lv = d.summary.livelihood;
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet([
                ['Code', 'Livelihood', 'Evacuated people'],
                ...lv.items.map((i) => [i.code, i.label, i.people]),
                ['', 'Not yet classified', lv.unclassified],
            ]), 'Livelihood');
            XLSX.writeFile(wb, 'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.xlsx');
        },
        async writePdf(d) {
            const rows = d.rows.map((r) => ({ ...r, reported: new Date(r.reported).toLocaleDateString('en-PH') }));
            const subtitle = this.filterLines(d).slice(2).map(([k, v]) => k + ': ' + v).join(' · ')
                + ' · ' + this.summaryLines(d.summary).map(([k, v]) => k + ' ' + v).join(', ');
            await window.tablePdf(rows, { filename: 'sakuna-report_' + d.summary.period.from + '_' + d.summary.period.to + '.pdf', title: d.title, subtitle, columns: this.columns() });
        },
    };
}
