// What the Sakuna situation report says before its tables (owner, 2026-11-03):
// an executive summary, the key figures and the charts. Every word and bar is
// read from the export the server returned (POST /admin/sakuna/reports/export)
// under the filters chosen, so the PDF and the XLSX say the same thing, and a
// chart with nothing to show is left out rather than drawn empty.

const statusWords = { Submitted: 'Reported', 'Under Verification': 'Validated', Assigned: 'Assigned', Processing: 'Dispatched', Completed: 'Resolved', Archived: 'Closed', Rejected: 'Rejected' };
const typeColors = { Fire: '#EA580C', Flood: '#2563EB', Landslide: '#92400E', Earthquake: '#7C3AED', 'Vehicular Accident': '#475569',
    'Medical Emergency': '#DB2777', 'Missing Person': '#C2410C', 'Rescue Request': '#0E7490', 'Drowning or Water Rescue': '#0369A1',
    'Structural Damage': '#78716C', 'Agricultural Damage': '#15803D' };
const NAVY = '#1E3A8A';
const BLUE = '#2563EB';
const TEAL = '#0E7490';
const AMBER = '#B45309';
const SLATE = '#94A3B8';

const num = (n) => Number(n || 0).toLocaleString('en-PH');
const peso = (n) => 'PHP ' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
const plural = (n, one, many = one + 's') => num(n) + ' ' + (Number(n) === 1 ? one : many);
const pct = (part, whole) => (whole > 0 ? Math.round((part / whole) * 100) : 0);
const day = (iso, year = false) => new Date(iso + 'T00:00:00').toLocaleDateString('en-US', year ? { month: 'short', day: 'numeric', year: 'numeric' } : { month: 'short', day: 'numeric' });
const entries = (obj) => Object.entries(obj || {}).filter(([, v]) => Number(v) > 0).sort((a, b) => b[1] - a[1]);
/** "a, b and c" */
const list = (items) => (items.length <= 1 ? items.join('') : items.slice(0, -1).join(', ') + ' and ' + items[items.length - 1]);

/** The filters in words, for the summary's first sentence. */
function scope(d) {
    const f = d.filters || {};
    const s = d.summary;
    const parts = [f.barangay ? 'Barangay ' + f.barangay : 'all barangays'];
    if (f.type) parts.push(f.type + ' incidents only');
    if (f.status) parts.push('incidents marked ' + (statusWords[f.status] || f.status));
    if (f.livelihood_code || f.livelihood_group) parts.push('evacuees in the chosen livelihood');
    const from = s.period.from, to = s.period.to;
    const span = from && to ? 'From ' + day(from, from.slice(0, 4) !== to.slice(0, 4)) + ' to ' + day(to, true) : 'Across all recorded dates';
    return span + ' (' + list(parts) + ')';
}

/**
 * Two paragraphs: what the period held, then what stands out and needs action.
 * Only statements the figures support; nothing is estimated.
 */
export function executiveSummary(d) {
    const s = d.summary;
    const inc = s.incidents;
    const ev = s.evacuation;
    const rel = s.relief.totals;
    const dmg = s.damage;
    const per = s.persons;
    const p1 = [];
    const p2 = [];

    if (inc.total > 0) {
        const types = entries(inc.by_type).map(([t, n]) => t + ' (' + num(n) + ')');
        const brgys = entries(inc.by_barangay);
        let line = scope(d) + ', ' + plural(inc.total, 'incident was', 'incidents were') + ' recorded';
        line += types.length ? ': ' + list(types.slice(0, 4)) + (types.length > 4 ? ' and ' + (types.length - 4) + ' other types' : '') + '.' : '.';
        p1.push(line);
        if (brgys.length) {
            const [top, n] = brgys[0];
            p1.push(brgys.length === 1 ? 'All of them were in ' + top + '.' : top + ' had the most (' + num(n) + ' of ' + num(inc.total) + ', ' + pct(n, inc.total) + '%), across ' + plural(brgys.length, 'barangay') + ' in all.');
        }
    } else {
        p1.push(scope(d) + ', no incidents were recorded.');
    }
    if (s.evacuated.individuals > 0) {
        p1.push(plural(s.evacuated.individuals, 'person was', 'people were') + ' evacuated from ' + plural(s.evacuated.families, 'family', 'families') + '.');
    }
    if (ev.now.open_centres > 0 && ev.now.persons_inside > 0) {
        p1.push('At the time of this report, ' + plural(ev.now.persons_inside, 'person is', 'people are') + ' inside ' + plural(ev.now.open_centres, 'open evacuation centre') +
            (ev.now.capacity > 0 ? ', ' + pct(ev.now.persons_inside, ev.now.capacity) + '% of their combined capacity of ' + num(ev.now.capacity) : '') + '.');
    } else if (ev.now.open_centres > 0) {
        p1.push('At the time of this report, ' + plural(ev.now.open_centres, 'evacuation centre is', 'evacuation centres are') + ' open with no one inside' + (ev.now.capacity > 0 ? ' (room for ' + num(ev.now.capacity) + ')' : '') + '.');
    }
    // Evacuation, relief and resources are not tied to one incident, so a type
    // or status filter does not narrow them; the reader is told so.
    const f = d.filters || {};
    if ((f.type || f.status) && (s.evacuated.individuals > 0 || rel.records > 0)) {
        p1.push('Evacuation and relief figures are not linked to a single incident, so they cover the whole period' + (f.barangay ? ' in ' + f.barangay : '') + ', whatever the disaster type or status chosen.');
    }
    if (rel.records > 0) {
        p1.push('Relief: ' + num(rel.released) + ' of ' + plural(rel.records, 'recorded item') + ' released to ' + plural(rel.families, 'family', 'families') +
            (rel.cost > 0 ? ', at a recorded cost of ' + peso(rel.cost) : '') + '.');
    }
    if (dmg.reports > 0) {
        const sector = [...(dmg.by_sector || [])].sort((a, b) => b.estimated_cost - a.estimated_cost)[0];
        p1.push(plural(dmg.reports, 'damage report') + ' cover ' + plural(dmg.units_affected, 'affected unit') +
            (dmg.estimated_cost > 0 ? ', with estimated damage of ' + peso(dmg.estimated_cost) + (sector && sector.estimated_cost > 0 ? ', the largest share in ' + sector.sector + ' (' + pct(sector.estimated_cost, dmg.estimated_cost) + '%)' : '') : '') + '.');
    }
    if (inc.total === 0 && !s.evacuated.individuals && !rel.records && !dmg.reports) {
        p1.push('No evacuation, relief or damage was recorded under these filters either, so this report has no figures to analyse beyond the current state of centres and resources.');
    }

    // What stands out.
    const waiting = (inc.by_status?.Submitted || 0);
    const open = Object.entries(inc.by_status || {}).filter(([k]) => !['Completed', 'Archived', 'Rejected'].includes(k)).reduce((a, [, n]) => a + n, 0);
    if (waiting > 0) p2.push(plural(waiting, 'incident is', 'incidents are') + ' still Reported and waiting to be validated' + (open > waiting ? ', and ' + plural(open - waiting, 'more is', 'more are').replace(/^(\d[\d,]*) /, '$1 ') + ' validated or in progress' : '') + '.');
    else if (open > 0) p2.push(plural(open, 'incident remains', 'incidents remain') + ' open (validated or in progress).');
    else if (inc.total > 0) p2.push('Every incident in the period has been resolved or closed.');
    if (per.missing > 0 || per.found > 0 || per.deceased > 0 || per.unidentified > 0) {
        const bits = [];
        if (per.missing) bits.push(plural(per.missing, 'person is', 'people are') + ' still missing');
        if (per.found) bits.push(num(per.found) + ' found');
        if (per.deceased) bits.push(num(per.deceased) + ' recorded deceased');
        if (per.unidentified) bits.push(num(per.unidentified) + ' unidentified');
        p2.push('Missing persons: ' + list(bits) + '.');
    }
    const trend = inc.trend || [];
    if (inc.total >= 3 && trend.length) {
        const peak = trend.reduce((a, b) => (b.count > a.count ? b : a));
        const share = peak.count / inc.total;
        if (peak.count >= 2) p2.push('The busiest day was ' + day(peak.label, true) + ' with ' + plural(peak.count, 'incident') + (share === 1 ? ', every incident in the period' : share >= 0.5 ? ', ' + pct(peak.count, inc.total) + '% of all incidents in the period' : '') + '.');
        if (trend.length >= 6 && share < 0.5) {
            const half = Math.floor(trend.length / 2);
            const a = trend.slice(0, half).reduce((x, t) => x + t.count, 0);
            const b = trend.slice(half).reduce((x, t) => x + t.count, 0);
            if (a + b >= 4 && Math.abs(b - a) >= 2) p2.push('Reports ' + (b > a ? 'rose' : 'fell') + ' in the second half of the period (' + num(a) + ' then ' + num(b) + ').');
        }
    }
    const isOpen = (c) => c.status === 'Open' || c.status === 'Processing';
    const full = (ev.centres || []).filter((c) => isOpen(c) && c.percent_full >= 75);
    if (full.length) p2.push(list(full.map((c) => c.name + ' (' + c.percent_full + '% full)')) + (full.length === 1 ? ' is' : ' are') + ' near or over capacity.');
    if (rel.awaiting > 0) p2.push(plural(rel.awaiting, 'relief item is', 'relief items are') + ' recorded but not yet released.');
    if (rel.not_received > 0) p2.push(plural(rel.not_received, 'family says', 'families say') + ' in the app that relief was not received.');
    const deployed = (s.resources.items || []).filter((r) => r.deployed > 0);
    if (deployed.length) p2.push('Deployed now: ' + list(deployed.map((r) => r.name + ' (' + num(r.deployed) + ' of ' + num(r.quantity) + (r.unit ? ' ' + r.unit : '') + ')')) + '.');
    const out = (s.resources.items || []).filter((r) => r.quantity > 0 && r.available === 0);
    if (out.length) p2.push(list(out.map((r) => r.name)) + (out.length === 1 ? ' has' : ' have') + ' none left to deploy.');
    if (s.alerts.issued > 0) p2.push(plural(s.alerts.issued, 'public alert was', 'public alerts were') + ' issued' + (s.alerts.cancelled ? ' (' + num(s.alerts.cancelled) + ' later cancelled)' : '') + '.');
    if (!p2.length) p2.push('Nothing in these records calls for follow-up: no incident is waiting, no one is recorded missing and no relief is pending.');
    else p2.unshift('What needs attention:');

    return [p1.join(' '), p2.join(' ')];
}

/** Eight headline figures for the KPI row; "now" figures say so. */
export function kpis(d) {
    const s = d.summary;
    const ev = s.evacuation.now;
    const rel = s.relief.totals;
    return [
        { label: 'Incidents', value: num(s.incidents.total), note: 'in the period' },
        { label: 'People evacuated', value: num(s.evacuated.individuals), note: plural(s.evacuated.families, 'family', 'families') },
        { label: 'Inside centres now', value: num(ev.persons_inside), note: ev.capacity > 0 ? pct(ev.persons_inside, ev.capacity) + '% of ' + num(ev.capacity) : 'no centre open' },
        { label: 'Still missing', value: num(s.persons.missing), note: num(s.persons.found) + ' found' },
        { label: 'Relief released', value: num(rel.released), note: 'of ' + num(rel.records) + ' recorded' },
        { label: 'Families served', value: num(rel.families), note: rel.cost > 0 ? peso(rel.cost) : 'relief' },
        { label: 'Estimated damage', value: peso(s.damage.estimated_cost), note: plural(s.damage.reports, 'report') },
        { label: 'Alerts issued', value: num(s.alerts.issued), note: s.alerts.cancelled ? num(s.alerts.cancelled) + ' cancelled' : 'public alerts' },
    ];
}

/** Daily counts, or weekly sums once the period is longer than a month. */
function trendPoints(trend) {
    if (trend.length <= 31) return trend.map((t) => [day(t.label), t.count]);
    const out = [];
    for (let i = 0; i < trend.length; i += 7) {
        const wk = trend.slice(i, i + 7);
        out.push(['Wk of ' + day(wk[0].label), wk.reduce((a, t) => a + t.count, 0)]);
    }
    return out;
}

/**
 * The charts the data supports, in report order. Each: { key, title, note,
 * kind: 'column' | 'bar', categories, series: [{ name, values, color, colors? }], format }.
 */
export function charts(d) {
    const s = d.summary;
    const out = [];
    const add = (c) => { if (c.series.some((x) => x.values.some((v) => Number(v) > 0))) out.push(c); };

    const tp = trendPoints(s.incidents.trend || []);
    add({ key: 'trend', title: 'Incidents over time', note: tp.length > 31 ? 'Weekly totals' : 'Per day', kind: 'column', categories: tp.map((t) => t[0]),
        series: [{ name: 'Incidents', values: tp.map((t) => t[1]), color: BLUE }], format: 'count' });
    const types = entries(s.incidents.by_type);
    add({ key: 'type', title: 'Incidents by disaster type', kind: 'bar', categories: types.map((t) => t[0]),
        series: [{ name: 'Incidents', values: types.map((t) => t[1]), color: BLUE, colors: types.map((t) => typeColors[t[0]] || '#64748B') }], format: 'count' });
    const status = entries(s.incidents.by_status);
    add({ key: 'status', title: 'Incidents by status', kind: 'bar', categories: status.map((t) => statusWords[t[0]] || t[0]),
        series: [{ name: 'Incidents', values: status.map((t) => t[1]), color: NAVY }], format: 'count' });
    const brgy = entries(s.incidents.by_barangay).slice(0, 12);
    if (brgy.length > 1) add({ key: 'barangay', title: 'Incidents by barangay', note: brgy.length === 12 ? 'The 12 with the most' : '', kind: 'bar', categories: brgy.map((t) => t[0]),
        series: [{ name: 'Incidents', values: brgy.map((t) => t[1]), color: NAVY }], format: 'count' });

    const centres = (s.evacuation.centres || []).filter((c) => c.status === 'Open' || c.status === 'Processing');
    add({ key: 'centres', title: 'Evacuation centre occupancy (now)', note: 'People inside against capacity', kind: 'bar', categories: centres.map((c) => c.name),
        series: [{ name: 'People inside', values: centres.map((c) => c.persons), color: TEAL }, { name: 'Capacity', values: centres.map((c) => c.capacity), color: SLATE }], format: 'count' });
    if (s.evacuees.persons > 0) {
        add({ key: 'age', title: 'Evacuated people by age group', kind: 'column', categories: Object.keys(s.evacuees.by_age),
            series: [{ name: 'People', values: Object.values(s.evacuees.by_age), color: TEAL }], format: 'count' });
        add({ key: 'sex', title: 'Evacuated people by sex', kind: 'column', categories: Object.keys(s.evacuees.by_sex),
            series: [{ name: 'People', values: Object.values(s.evacuees.by_sex), color: TEAL }], format: 'count' });
    }
    const items = (s.relief.by_item || []).slice(0, 12);
    add({ key: 'relief', title: 'Relief by item', note: 'Records released and awaiting release', kind: 'bar', categories: items.map((r) => r.item + (r.unit ? ' (' + r.unit + ')' : '')),
        series: [{ name: 'Released', values: items.map((r) => r.released), color: '#059669' }, { name: 'Awaiting', values: items.map((r) => r.awaiting), color: AMBER }], format: 'count' });
    const rbrgy = (s.relief.by_barangay || []).slice(0, 12);
    if (rbrgy.length > 1) add({ key: 'relief-brgy', title: 'Families served by barangay', kind: 'bar', categories: rbrgy.map((r) => r.barangay),
        series: [{ name: 'Families', values: rbrgy.map((r) => r.families), color: '#059669' }], format: 'count' });
    const resItems = (s.resources.items || []).slice(0, 12);
    if (s.resources.now.deployed > 0) add({ key: 'resources', title: 'Resources now: deployed and available', kind: 'bar', categories: resItems.map((r) => r.name),
        series: [{ name: 'Deployed', values: resItems.map((r) => r.deployed), color: AMBER }, { name: 'Available', values: resItems.map((r) => r.available), color: '#059669' }], format: 'count' });
    const sectors = [...(s.damage.by_sector || [])].sort((a, b) => b.estimated_cost - a.estimated_cost);
    add({ key: 'damage', title: 'Estimated damage by sector', note: 'PHP', kind: 'bar', categories: sectors.map((r) => r.sector),
        series: [{ name: 'Estimated cost (PHP)', values: sectors.map((r) => r.estimated_cost), color: '#7C2D12' }], format: 'money' });
    const per = s.persons;
    add({ key: 'persons', title: 'Missing, found, deceased and unidentified', kind: 'column', categories: ['Still missing', 'Found', 'Deceased', 'Unidentified'],
        series: [{ name: 'People', values: [per.missing, per.found, per.deceased, per.unidentified], color: NAVY, colors: [AMBER, '#059669', '#475569', '#7C3AED'] }], format: 'count' });
    return out;
}
