@php
    use Illuminate\Support\Arr;
    $activeTab = $tab ?? 'command-center';

    // TAB 11: every tab is already its own route and therefore its own HTML
    // file in the static export. Only the requested tab's partial is rendered
    // (see the @if guards below) -- previously all 11 shipped on all 11 URLs,
    // 1.59 MB each. Tab switching is therefore navigation, not Alpine state.
    // TAB 04: fixtures live in resources/data/admin/sakuna.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/admin/sakuna.php');
    $tabRoutes = $__fixtures['tabRoutes'];
    $tabUrls = array_map(fn ($name) => route($name), $tabRoutes);

    // The tab bar, and the names used when a tab is not this role's (NAV-1).
    $tabs = [
        ['key' => 'command-center',    'label' => 'Command Center',    'icon' => 'monitor-check'],
        ['key' => 'vulnerability',      'label' => 'Vulnerability',     'icon' => 'map-pin'],
        ['key' => 'incidents',          'label' => 'Incidents',         'icon' => 'flame'],
        // Missing Persons grown into the casualty record: dead, injured, missing (2026-11-14).
        ['key' => 'missing-persons',    'label' => 'Casualties',        'icon' => 'user-search'],
        ['key' => 'evacuation-centers', 'label' => 'Evac. Centers',    'icon' => 'house'],
        ['key' => 'evacuees',           'label' => 'Evacuees',          'icon' => 'users'],
        ['key' => 'resources',          'label' => 'Resources',         'icon' => 'truck'],
        ['key' => 'relief',             'label' => 'Relief & Logistics','icon' => 'package'],
        ['key' => 'damage-assessment',  'label' => 'Damage',            'icon' => 'clipboard-list'],
        ['key' => 'bdrrmc-forms',       'label' => 'BDRRMC Forms',      'icon' => 'file-check'],
        ['key' => 'alerts',             'label' => 'Alerts',            'icon' => 'bell-ring'],
        ['key' => 'reports',            'label' => 'Reports',           'icon' => 'chart-bar'],
    ];

    // ── Operational Context ─────────────────────────────────────────────────

    // ── Barangays ────────────────────────────────────────────────────────────
    $barangays = config('esperanza.barangays');

    // Vulnerability now loads from GET /sakuna/vulnerability -- the
    // vulnerabilityProfiles fixture key is no longer read by anything.

    // Incidents now loads from GET /sakuna/incidents -- the fixture key is no
    // longer read by anything.

    // Evacuation Centers now loads from GET /sakuna/centers -- the fixture
    // key is no longer read by anything.

    // Evacuees now loads from GET /sakuna/evacuees -- the fixture key is no
    // longer read by anything.

    // Resources now loads from GET /sakuna/resources -- the fixture key is no
    // longer read by anything.

    // Relief now loads from GET /sakuna/relief -- the fixture key is no
    // longer read by anything. There never was a real inventory/requests/
    // donations model behind those parts of the mock (see tab-relief.blade.php).

    // Damage now loads from GET /sakuna/damage -- the fixture key is no
    // longer read by anything.

    // Alerts now loads from GET /sakuna/alerts -- the fixture key is no
    // longer read by anything.

    // Reports now loads incidents/centers/vulnerability directly from their
    // own real endpoints -- the fixture keys are no longer read by anything.
@endphp

<x-layouts.admin
    title="Sakuna"
    subtitle="Disaster risk, response and relief."
    active="sakuna"
>

<div x-show="!$store.session.can('sakuna')" x-cloak>
    <x-admin.access-restricted module="Sakuna (Disaster Risk & Emergency Ops)" icon="shield-alert" />
</div>

<div
    x-show="$store.session.can('sakuna')"
    x-cloak
    x-data="{
        // Phase 2 of the shelter and relief records (2026-10-27): the registry
        // at check-in, the head, corrections at the source, where relief was given.
        ...(window.sakunaShelter ? sakunaShelter() : {}),
        activeTab: '{{ $activeTab }}',
        tabUrls: @js($tabUrls),
        // The status bar reads the live board (GET /admin/sakuna/command-center):
        // the alerts in effect and the incidents still open. It used to show a
        // fixed Red Alert and a July 2026 monsoon that never happened.
        headerBoard: null,
        headerFailed: false,
        // Never a false all-clear (critique 2026-11-03): until the board has
        // loaded the status is unknown, not Normal, and a board that could not
        // be refreshed stays on screen marked with the time it was true.
        headerLoadedAt: 0,
        headerNow: Date.now(),
        headerRefreshing: false,
        get headerState() {
            if (!this.headerBoard) return this.headerFailed ? 'failed' : 'loading';
            if (this.headerFailed || this.headerNow - this.headerLoadedAt > 5 * 60000) return 'stale';
            return 'live';
        },
        get headerUpdatedLabel() {
            const state = this.headerState;
            if (state === 'loading') return 'Loading the live board…';
            if (state === 'failed') return 'Live board not available. Retry';
            const at = new Date(this.headerBoard.generated_at || this.headerLoadedAt).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            if (this.headerFailed) return 'Could not refresh. Last updated ' + at;
            const mins = Math.floor((this.headerNow - this.headerLoadedAt) / 60000);
            if (state === 'stale') return 'Last updated ' + at + ', ' + mins + ' min ago';
            return mins < 1 ? 'Updated just now' : 'Updated ' + mins + ' min ago';
        },
        // A weak connection keeps what was last seen (critique 2026-11-03, second
        // run). Each list is kept in this browser tab only (sessionStorage, per
        // account, gone when the tab closes and cleared at sign-out). Evacuees and
        // missing persons too since the seventh run (owner, 2026-11-03): in a
        // brownout, who is in which centre and who is missing matter most.
        // When a refresh fails, the last copy shows, marked with its time.
        staleSince: {},
        copyKey(name) { return 'sakuna-copy:' + (this.$store.session.account?.employee_id || this.$store.session.account?.id || 'anon') + ':' + name; },
        saveCopy(name, data) {
            try { sessionStorage.setItem(this.copyKey(name), JSON.stringify({ at: Date.now(), data })); } catch { /* storage full or blocked: nothing kept */ }
            if (this.staleSince[name]) this.staleSince = { ...this.staleSince, [name]: 0 };
        },
        lastCopy(name) {
            try { return JSON.parse(sessionStorage.getItem(this.copyKey(name)) || 'null'); } catch { return null; }
        },
        useCopy(name) {
            const copy = this.lastCopy(name);
            if (copy) this.staleSince = { ...this.staleSince, [name]: copy.at };
            return copy;
        },
        get offlineWhy() { return navigator.onLine === false ? 'This computer is offline.' : 'The server did not answer.'; },
        // The tab's own list and how to fetch it again, for the last-copy note.
        get tabCopy() {
            return ({ 'command-center': ['board', () => this.loadCommandCenter()], incidents: ['incidents', () => this.loadIncidentsList()],
                'evacuation-centers': ['centers', () => this.loadCenters()], alerts: ['alerts', () => this.loadAlerts()],
                evacuees: ['evacuees', () => this.loadEvacuees()], resources: ['resources', () => this.loadResources()],
                relief: ['relief', () => this.loadRelief()],
                'missing-persons': ['missing', () => window.dispatchEvent(new CustomEvent('sakuna-missing-reload'))] })[this.activeTab] || null;
        },
        // The Command Center's figures and queue say when they were true once
        // the live board could not be refreshed (critique 2026-11-03, third run).
        get boardAsOf() {
            const at = this.staleSince.board || (this.headerState === 'stale' ? this.headerLoadedAt : 0);
            if (!at) return '';
            const mins = Math.max(0, Math.round((this.headerNow - at) / 60000));
            return new Date(at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) + (mins ? ', ' + mins + ' min ago' : '');
        },
        // Showing a last copy: steps that change a record wait for the connection.
        get onLastCopy() { return !!(this.tabCopy && this.staleSince[this.tabCopy[0]]); },
        // A figure while its list is loading or failed is unknown, not zero.
        fig(value, unknown) { return unknown ? '—' : value; },
        get liveStatus() {
            if (this.headerState === 'loading') return 'Checking…';
            if (this.headerState === 'failed') return 'Status unknown';
            const c = this.headerBoard.counts || {};
            if (c.alerts_live > 0) return 'Alert in effect';
            if (c.incidents_open > 0) return 'Monitoring';
            return 'Normal';
        },
        get activeEventLabel() {
            const b = this.headerBoard;
            if (this.headerFailed && !b) return 'Live board not available';
            if (!b) return 'Loading…';
            const alert = (b.alerts || [])[0];
            if (alert) return alert.title?.en || alert.title?.fil || alert.ref;
            const open = b.counts?.incidents_open || 0;
            if (open) return open + ' open incident' + (open === 1 ? '' : 's');
            return 'No active event';
        },
        headerWaiting: false,
        async loadHeader() {
            // The account cached at sign-in carries no permissions yet; the
            // GET /auth/me every page load runs brings them, so wait for it.
            if (!Object.keys(this.$store.session.account?.permissions || {}).length) {
                if (!this.headerWaiting) {
                    this.headerWaiting = true;
                    this.$watch('$store.session.account', () => {
                        if (!this.headerWaiting) return;
                        this.headerWaiting = false;
                        this.loadHeader();
                    });
                }
                return;
            }
            if (!this.$store.session.canPerm('sakuna_command', 'view')) { this.headerFailed = true; return; }
            if (this.headerRefreshing) return;
            this.headerRefreshing = true;
            if (!this.headerBoard) {
                const copy = this.lastCopy('board');
                if (copy) { this.headerBoard = copy.data; this.headerLoadedAt = copy.at; this.headerNow = Date.now(); }
            }
            try {
                const board = (await $api.get('/admin/sakuna/command-center')).data || {};
                this.headerBoard = board;
                this.headerLoadedAt = this.headerNow = Date.now();
                this.headerFailed = false;
                this.saveCopy('board', board);
                // The Command Center reads the same board: keep its figures and map current too.
                if (this.activeTab === 'command-center' && this.commandBoard && !this.commandLoading) this.commandBoard = board;
            } catch { this.headerFailed = true; } finally { this.headerRefreshing = false; }
        },
        // Refresh on its own (critique 2026-11-03): every minute while the page
        // is in view, when it comes back into view, and when the connection returns.
        startHeaderRefresh() {
            setInterval(() => { this.headerNow = Date.now(); }, 30000);
            setInterval(() => { if (document.visibilityState === 'visible') this.loadHeader(); }, 60000);
            document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') { this.headerNow = Date.now(); this.loadHeader(); } });
            window.addEventListener('online', () => this.loadHeader());
        },
        selectedBarangay: '',
        returnToProfile: null,
        // Arrived from a resident's profile via Open in Sakuna — land on the
        // Incidents tab pre-filtered to their exact report, and offer a way
        // straight back to the profile that started the trip.
        init() {
            this.landOnAllowedTab();
            this.probeMissing();
            this.loadHeader();
            this.startHeaderRefresh();
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref) {
                this.setTab('incidents');
                this.incidentSearch = ref;
            }
            // A Needs action chip opens Incidents showing what it counted.
            if (this.activeTab === 'incidents') {
                if (params.get('filter_status')) this.incidentStatusFilter = params.get('filter_status');
                if (['none', 'someone'].includes(params.get('filter_assigned'))) this.incidentAssignedFilter = params.get('filter_assigned');
            }
            const stored = sessionStorage.getItem('returnToProfile');
            if (stored) {
                try { this.returnToProfile = JSON.parse(stored); } catch (e) { this.returnToProfile = null; }
                sessionStorage.removeItem('returnToProfile');
            }
        },
        backToProfile() {
            if (!this.returnToProfile) return;
            sessionStorage.setItem('reopenResident', JSON.stringify(this.returnToProfile));
            window.location.href = '/admin/constituents';
        },

        // Incident tab state
        incidentSearch: '',
        incidentTypeFilter: '',
        incidentSeverityFilter: '',
        incidentStatusFilter: '',
        incidentBarangayFilter: '',
        incidentSourceFilter: '',
        incidentAssignedFilter: '',
        selectedIncident: null,
        showIncidentDetail: false,
        showCreateIncident: false,
        incidentsList: [],
        incidentsLoading: true,
        incidentsFailed: false,
        incidentSaving: false,
        incidentForm: { title: '', type: '', barangay: '', sitio: '', severity: '', description: '', source: '', lat: null, lng: null },
        // GET /sakuna/incidents (full list for this tab, distinct from Command
        // Center's own severity-ranked slice of the same endpoint).
        async loadIncidentsList() {
            this.incidentsLoading = true;
            this.incidentsFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/incidents');
                this.incidentsList = res.data || [];
                this.saveCopy('incidents', this.incidentsList);
            } catch {
                const copy = this.useCopy('incidents');
                if (copy) this.incidentsList = copy.data; else this.incidentsFailed = true;
            } finally { this.incidentsLoading = false; }
        },
        resetIncidentForm() {
            this.incidentForm = { title: '', type: '', barangay: '', sitio: '', severity: '', description: '', source: '', lat: null, lng: null };
        },
        openCreateIncident() {
            this.resetIncidentForm();
            this.showCreateIncident = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async submitIncident() {
            if (this.incidentSaving || !this.incidentForm.title || !this.incidentForm.type || !this.incidentForm.barangay || !this.incidentForm.severity) return;
            this.incidentSaving = true;
            try {
                const res = await $api.post('/admin/sakuna/incidents', this.incidentForm);
                this.incidentsList.unshift(res.data);
                this.showCreateIncident = false;
                this.toast(res.data.ref + ' reported.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not report this incident.', 'error');
            } finally { this.incidentSaving = false; }
        },

        // Vulnerability tab state
        vulSearch: '',
        vulRiskFilter: '',
        vulHazardFilter: '',
        vulReadinessFilter: '',
        selectedBarangayVul: null,
        showVulDetail: false,
        vulProfiles: [],
        vulLoading: true,
        vulFailed: false,
        showVulForm: false,
        vulFormMode: 'create',
        vulSaving: false,
        vulForm: { ref: null, barangay: '', population: '', households: '', vulnerable: '', hazards: [], exposed: '', evac_cap: '', readiness_score: '', completeness: '', last_assessed: '', focal: '', notes: '' },
        // GET /sakuna/vulnerability (SakunaVulnerabilityAssessment via SakunaFormsController).
        async loadVulnerability() {
            this.vulLoading = true;
            this.vulFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/vulnerability');
                this.vulProfiles = res.data || [];
            } catch { this.vulFailed = true; } finally { this.vulLoading = false; }
        },
        // readiness_score (0-100) is the only real field -- there is no stored
        // categorical label. Derived client-side with the same thresholds the
        // score's own color-coding already uses.
        readinessLabel(score) {
            if (score === null || score === undefined) return 'Not Assessed';
            if (score >= 70) return 'Ready';
            if (score >= 50) return 'Needs Improvement';
            return 'Major Gaps';
        },
        resetVulForm() {
            this.vulForm = { ref: null, barangay: '', population: '', households: '', vulnerable: '', hazards: [], exposed: '', evac_cap: '', readiness_score: '', completeness: '', last_assessed: '', focal: '', notes: '' };
        },
        openVulCreate() {
            this.resetVulForm();
            this.vulFormMode = 'create';
            this.showVulForm = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openVulEdit(p) {
            this.vulForm = { ref: p.ref, barangay: p.barangay, population: p.population ?? '', households: p.households ?? '', vulnerable: p.vulnerable ?? '', hazards: [...(p.hazards || [])], exposed: p.exposed ?? '', evac_cap: p.evac_cap ?? '', readiness_score: p.readiness_score ?? '', completeness: p.completeness ?? '', last_assessed: p.last_assessed || '', focal: p.focal || '', notes: p.notes || '' };
            this.vulFormMode = 'edit';
            this.showVulForm = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        toggleVulFormHazard(h) {
            this.vulForm.hazards = this.vulForm.hazards.includes(h)
                ? this.vulForm.hazards.filter(x => x !== h)
                : [...this.vulForm.hazards, h];
        },
        async saveVulnerability() {
            if (this.vulSaving) return;
            this.vulSaving = true;
            const payload = { ...this.vulForm };
            delete payload.ref;
            ['population', 'households', 'vulnerable', 'exposed', 'evac_cap', 'readiness_score', 'completeness'].forEach(k => {
                payload[k] = payload[k] === '' ? null : Number(payload[k]);
            });
            if (!payload.last_assessed) delete payload.last_assessed;
            try {
                const res = this.vulFormMode === 'create'
                    ? await $api.post('/admin/sakuna/vulnerability', payload)
                    : await $api.put('/admin/sakuna/vulnerability/' + this.vulForm.ref, payload);
                const idx = this.vulProfiles.findIndex(p => p.ref === res.data.ref);
                if (idx !== -1) this.vulProfiles[idx] = res.data; else this.vulProfiles.push(res.data);
                if (this.selectedBarangayVul?.ref === res.data.ref) this.selectedBarangayVul = res.data;
                this.showVulForm = false;
                this.toast(this.vulFormMode === 'create' ? 'Assessment recorded.' : 'Assessment updated.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this assessment.', 'error');
            } finally { this.vulSaving = false; }
        },
        async approveVulnerabilityAssessment(p) {
            try {
                const res = await $api.post('/admin/sakuna/vulnerability/' + p.ref + '/approve', {});
                Object.assign(p, res.data);
                if (this.selectedBarangayVul?.ref === p.ref) this.selectedBarangayVul = { ...this.selectedBarangayVul, ...res.data };
                this.toast(`${p.barangay}'s assessment approved.`, 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not approve this assessment.', 'error');
            }
        },
        // Reused by any tab that wants a plain client-side CSV of already-loaded
        // real rows -- same one-liner already established for Constituents/Papeles.
        exportCSV(rows, filename) {
            if (!rows.length) { this.toast('No records to export.', 'warning'); return; }
            const keys = Object.keys(rows[0]).filter(k => !Array.isArray(rows[0][k]) && typeof rows[0][k] !== 'object');
            const csv = [keys.join(','), ...rows.map(r => keys.map(k => JSON.stringify(r[k] ?? '')).join(','))].join('\n');
            const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv], { type: 'text/csv' })), download: filename });
            a.click();
            this.toast('Exported successfully.', 'success');
        },
        // The same rows as a PDF (owner, 2026-10-15: PDF as well as the spreadsheet).
        async exportPDF(rows, filename) {
            if (!rows.length) { this.toast('No records to export.', 'warning'); return; }
            const name = filename.replace(/(-export|-report)?\.csv$/, '').replace(/-/g, ' ');
            try {
                await window.tablePdf(rows, { filename, title: 'Sakuna \u00b7 ' + name.charAt(0).toUpperCase() + name.slice(1) });
                this.toast('PDF downloaded.', 'success');
            } catch { this.toast('Could not make the PDF.', 'error'); }
        },

        // Centers tab state
        centerSearch: '',
        centerStatusFilter: '',
        selectedCenter: null,
        showCenterDetail: false,
        showCreateCenter: false,
        showOpenCenterConfirm: false,
        showCloseCenterConfirm: false,
        pendingCenterAction: null,
        centersList: [],
        centersLoading: true,
        centersFailed: false,
        centerFormMode: 'create',
        centerSaving: false,
        centerForm: { ref: null, name: '', barangay: '', address: '', capacity: '', services: [], lat: null, lng: null },
        centerEvacuees: [],
        // GET /sakuna/centers (real, derived occupancy; lat/lng since
        // 2026-10-26 -- no manager/contact column, no inspection/room/personnel
        // sub-model; see tab-centers.blade.php's header comment).
        async loadCenters() {
            this.centersLoading = true;
            this.centersFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/centers');
                this.centersList = res.data || [];
                this.saveCopy('centers', this.centersList);
            } catch {
                const copy = this.useCopy('centers');
                if (copy) this.centersList = copy.data; else this.centersFailed = true;
            } finally { this.centersLoading = false; }
            // A center opened from the Command Center's map (?center=REF).
            const ref = new URLSearchParams(window.location.search).get('center');
            const hit = ref && this.centersList.find((c) => c.ref === ref);
            if (hit) this.openCenterDetail(hit);
        },
        hasMapPin(row) { return !!row && window.sakunaGeo ? window.sakunaGeo.isPair(row.lat, row.lng) : (row?.lat != null && row?.lng != null); },
        resetCenterForm() {
            this.centerForm = { ref: null, name: '', barangay: '', address: '', capacity: '', services: [], lat: null, lng: null };
        },
        openCreateCenter() {
            this.resetCenterForm();
            this.centerFormMode = 'create';
            this.showCreateCenter = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openEditCenter(c) {
            this.centerForm = { ref: c.ref, name: c.name, barangay: c.barangay, address: c.address || '', capacity: c.capacity, services: [...(c.services || [])], lat: c.lat ?? null, lng: c.lng ?? null };
            this.centerFormMode = 'edit';
            this.showCreateCenter = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        toggleCenterFormService(s) {
            this.centerForm.services = this.centerForm.services.includes(s)
                ? this.centerForm.services.filter(x => x !== s)
                : [...this.centerForm.services, s];
        },
        // A new center is registered on the map (2026-10-26); one from before
        // the map can be saved without a pin and pinned later.
        get centerNeedsPin() { return this.centerFormMode === 'create' && !this.hasMapPin(this.centerForm); },
        async saveCenter() {
            if (this.centerSaving || !this.centerForm.name || !this.centerForm.barangay || this.centerForm.capacity === '' || this.centerNeedsPin) return;
            this.centerSaving = true;
            const pinned = this.hasMapPin(this.centerForm);
            const payload = { name: this.centerForm.name, barangay: this.centerForm.barangay, address: this.centerForm.address || null, capacity: Number(this.centerForm.capacity), services: this.centerForm.services, lat: pinned ? Number(this.centerForm.lat) : null, lng: pinned ? Number(this.centerForm.lng) : null };
            try {
                const res = this.centerFormMode === 'create'
                    ? await $api.post('/admin/sakuna/centers', payload)
                    : await $api.put('/admin/sakuna/centers/' + this.centerForm.ref, payload);
                const idx = this.centersList.findIndex(c => c.ref === res.data.ref);
                if (idx !== -1) this.centersList[idx] = res.data; else this.centersList.unshift(res.data);
                if (this.selectedCenter?.ref === res.data.ref) this.selectedCenter = res.data;
                this.showCreateCenter = false;
                this.toast(this.centerFormMode === 'create' ? res.data.ref + ' registered.' : 'Center updated.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this center.', 'error');
            } finally { this.centerSaving = false; }
        },
        // The Occupants tab shows real checked-in families for this specific
        // center, fetched on open rather than baked into the centers list.
        async openCenterDetail(c) {
            this.selectedCenter = c;
            this.showCenterDetail = true;
            this.centerEvacuees = [];
            this.$nextTick(() => window.renderIcons?.());
            try {
                const res = await $api.getAll('/admin/sakuna/evacuees', { params: { center: c.ref } });
                this.centerEvacuees = res.data || [];
            } catch { /* the tab just shows no families rather than failing the modal */ }
        },
        async confirmOpenCenter() {
            if (!this.pendingCenterAction) return;
            try {
                const res = await $api.post('/admin/sakuna/centers/' + this.pendingCenterAction.ref + '/open', {});
                const idx = this.centersList.findIndex(c => c.ref === res.data.ref);
                if (idx !== -1) this.centersList[idx] = res.data;
                if (this.selectedCenter?.ref === res.data.ref) this.selectedCenter = res.data;
                this.showOpenCenterConfirm = false;
                this.toast(res.data.name + ' opened.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not open this center.', 'error');
            }
        },
        // The backend itself refuses to close an occupied center (real
        // CENTER_NOT_EMPTY guard) -- the confirmation here is a UX gesture on
        // a consequential action, not a re-implementation of that check.
        async confirmCloseCenter() {
            if (!this.pendingCenterAction) return;
            try {
                const res = await $api.post('/admin/sakuna/centers/' + this.pendingCenterAction.ref + '/close', {});
                const idx = this.centersList.findIndex(c => c.ref === res.data.ref);
                if (idx !== -1) this.centersList[idx] = res.data;
                if (this.selectedCenter?.ref === res.data.ref) this.selectedCenter = res.data;
                this.showCloseCenterConfirm = false;
                this.toast(res.data.name + ' closed.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not close this center.', 'error');
            }
        },

        // Evacuees tab state
        evacueeSearch: '',
        evCenterFilter: '',
        evStatusFilter: '',
        selectedEvacuee: null,
        showEvacueeDetail: false,
        showEvacueeReg: false,
        evacueesList: [],
        evacueesLoading: true,
        evacueesFailed: false,
        evacueeCentersOpen: [],
        checkInForm: { center: '', head_name: '', barangay: '', sitio: '', purok: '', hh_code: '', family_id: null, members: [], faced_serial: '', contact: '', monthly_income: '', house_ownership: '', shelter_damage: '', is_4ps: false, is_ip: false, ip_ethnicity: '' },
        checkInSaving: false,
        // GET /sakuna/evacuees (list) + GET /sakuna/centers filtered to open
        // ones, for the check-in/transfer center pickers.
        async loadEvacuees() {
            this.evacueesLoading = true;
            this.evacueesFailed = false;
            try {
                await this.$store.session.whenReady();
                const [ev, centers] = await Promise.all([
                    $api.getAll('/admin/sakuna/evacuees'),
                    this.mayListCenters() ? $api.getAll('/admin/sakuna/centers').catch(() => ({ data: [] })) : this.nothing(),
                ]);
                this.evacueesList = ev.data || [];
                this.evacueeCentersOpen = (centers.data || []).filter(c => c.status === 'Processing');
                this.saveCopy('evacuees', { list: this.evacueesList, centers: this.evacueeCentersOpen });
            } catch {
                const copy = this.useCopy('evacuees');
                if (copy) { this.evacueesList = copy.data.list || []; this.evacueeCentersOpen = copy.data.centers || []; } else this.evacueesFailed = true;
            } finally { this.evacueesLoading = false; }
        },
        resetCheckInForm() {
            this.checkInForm = { center: '', head_name: '', barangay: '', sitio: '', purok: '', hh_code: '', family_id: null, members: [], faced_serial: '', contact: '', monthly_income: '', house_ownership: '', shelter_damage: '', is_4ps: false, is_ip: false, ip_ethnicity: '' };
            this.registryQuery = '';
            this.registryResults = [];
            this.registrySearched = false;
            this.registryPicked = null;
            this.checkInDuplicate = '';
        },
        openEvacueeReg() {
            this.resetCheckInForm();
            this.showEvacueeReg = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        addCheckInMember() { this.checkInForm.members.push({ name: '', relation: '', age: '', sex: '', needs: '', dob: '', education: '', occupation: '', livelihood_code: '', livelihood_other: '', registry_has_occupation: false, update_registry: false, vulnerability: '', individual_id: null, is_head: false, arrived: true }); },
        // A member's occupation as sent: none for a registered resident whose
        // registry occupation stands (the server takes it from there), else
        // the DRRM choice, or the words when no choice was made.
        checkInOccupation(m) {
            if (m.individual_id && m.registry_has_occupation && !m.update_registry) return {};
            const extra = m.update_registry ? { update_registry: true } : {};
            return m.livelihood_code
                ? { livelihood_code: m.livelihood_code, livelihood_other: m.livelihood_other || null, ...extra }
                : { occupation: m.occupation || null, ...extra };
        },
        removeCheckInMember(i) { this.checkInForm.members.splice(i, 1); },
        // members[] is optional on the real endpoint (a bare member_count is
        // enough), but sending it is what makes the detail view's real
        // per-person needs/relation breakdown possible later.
        // Only the members ticked as arrived are sent. A family already staying
        // somewhere comes back as 409 ALREADY_SHELTERED; the officer may then
        // confirm (confirm = true) that this is a second stay (2026-10-27).
        async submitCheckIn(confirm = false) {
            if (this.checkInSaving || !this.checkInForm.center || !this.checkInForm.head_name || !this.checkInForm.barangay) return;
            this.checkInSaving = true;
            const members = this.checkInArrived()
                .map(m => ({
                    name: m.name.trim(),
                    relation: m.relation || null,
                    age: m.age === '' ? null : Number(m.age),
                    sex: m.sex || null,
                    needs: m.needs ? m.needs.split(',').map(s => s.trim()).filter(Boolean) : [],
                    dob: m.dob || null,
                    education: m.education || null,
                    ...this.checkInOccupation(m),
                    vulnerability: m.vulnerability || null,
                    individual_id: m.individual_id || undefined,
                    is_head: !!m.is_head,
                }));
            const f = this.checkInForm;
            const payload = { head_name: f.head_name, barangay: f.barangay, sitio: f.sitio || null, members, ...this.facedPayload(f), ...this.checkInExtras(confirm) };
            try {
                const res = await $api.post('/admin/sakuna/centers/' + this.checkInForm.center + '/check-in', payload);
                this.evacueesList.unshift(res.data);
                this.showEvacueeReg = false;
                this.checkInDuplicate = '';
                this.toast(res.data.ref + ' checked in.', 'success');
            } catch (err) {
                if (err.code === 'ALREADY_SHELTERED') { this.checkInDuplicate = err.localised('en'); return; }
                this.toast(err.localised ? err.localised('en') : 'Could not check in this household.', 'error');
            } finally { this.checkInSaving = false; }
        },
        // The DSWD Family Assistance Card in Emergencies and Disasters (FACED):
        // the family's registration side is kept on the evacuee family and
        // every relief it receives lands on its Family Assistance Record
        // (MSWDO forms, 2026-10-26).
        houseOwnershipChoices: ['Owner', 'Renter', 'Sharer', 'Informal Settler'],
        shelterDamageChoices: ['None', 'Partially Damaged', 'Totally Damaged'],
        facedEditing: false,
        facedForm: {},
        facedSaving: false,
        facedCard: null,
        facedLoading: false,
        facedPayload(f) {
            return {
                faced_serial: f.faced_serial || null,
                contact: f.contact || null,
                monthly_income: f.monthly_income || null,
                house_ownership: f.house_ownership || null,
                shelter_damage: f.shelter_damage || null,
                is_4ps: !!f.is_4ps,
                is_ip: !!f.is_ip,
                ip_ethnicity: f.is_ip ? (f.ip_ethnicity || null) : null,
            };
        },
        editFaced() {
            const fc = this.selectedEvacuee?.faced || {};
            this.facedForm = {
                faced_serial: fc.serial || '', contact: fc.contact || '', monthly_income: fc.monthly_income || '',
                house_ownership: fc.house_ownership || '', shelter_damage: fc.shelter_damage || '',
                is_4ps: !!fc.is_4ps, is_ip: !!fc.is_ip, ip_ethnicity: fc.ip_ethnicity || '',
            };
            this.facedEditing = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async saveFaced() {
            if (this.facedSaving || !this.selectedEvacuee) return;
            this.facedSaving = true;
            try {
                const res = await $api.put('/admin/sakuna/evacuees/' + this.selectedEvacuee.ref + '/faced', this.facedPayload(this.facedForm));
                this.selectedEvacuee = { ...this.selectedEvacuee, ...res.data };
                this.facedEditing = false;
                this.toast('FACED card details saved.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save the FACED card.', 'error');
            } finally { this.facedSaving = false; }
        },
        async openFacedCard(ev) {
            this.facedLoading = true;
            this.facedCard = null;
            try {
                const res = await $api.get('/admin/sakuna/evacuees/' + ev.ref + '/faced');
                this.facedCard = res.data;
                this.$nextTick(() => window.renderIcons?.());
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not open the FACED card.', 'error');
            } finally { this.facedLoading = false; }
        },
        printFacedCard() {
            document.body.classList.add('dp-printing');
            this.$nextTick(() => window.print());
        },
        async openEvacueeDetail(row) {
            this.selectedEvacuee = row;
            this.facedEditing = false;
            this.recordEditing = false;
            this.memberEditing = null;
            this.showEvacueeDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            // The list row has no members/movements; GET /sakuna/evacuees/{ref}
            // (added because it never existed before) has both.
            try {
                const res = await $api.get('/admin/sakuna/evacuees/' + row.ref);
                this.selectedEvacuee = { ...row, ...res.data };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },
        async transferEvacuee(ev, toCenterRef) {
            if (!toCenterRef) return;
            try {
                const res = await $api.post('/admin/sakuna/evacuees/' + ev.ref + '/transfer', { to_center: toCenterRef });
                const idx = this.evacueesList.findIndex(e => e.ref === res.data.ref);
                if (idx !== -1) this.evacueesList[idx] = { ...this.evacueesList[idx], ...res.data };
                if (this.selectedEvacuee?.ref === res.data.ref) this.selectedEvacuee = { ...this.selectedEvacuee, ...res.data };
                this.toast(ev.head_name + ' transferred.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not transfer this household.', 'error');
            }
        },
        async checkOutEvacuee(ev) {
            try {
                const res = await $api.post('/admin/sakuna/evacuees/' + ev.ref + '/check-out', {});
                const idx = this.evacueesList.findIndex(e => e.ref === res.data.ref);
                if (idx !== -1) this.evacueesList[idx] = { ...this.evacueesList[idx], ...res.data };
                if (this.selectedEvacuee?.ref === res.data.ref) this.selectedEvacuee = { ...this.selectedEvacuee, ...res.data };
                this.toast(ev.head_name + ' checked out.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not check out this household.', 'error');
            }
        },

        // Resources tab state
        resourceSearch: '',
        resourceCategoryFilter: '',
        selectedResource: null,
        showResourceDetail: false,
        showDeployModal: false,
        showCreateResource: false,
        resourcesList: [],
        resourcesLoading: true,
        resourcesFailed: false,
        resourceFormMode: 'create',
        resourceSaving: false,
        resourceForm: { ref: null, name: '', category: '', quantity: '', unit: '', readiness: '', barangay: '' },
        resourceIncidentsOpen: [],
        deployForm: { quantity: '', incident_id: '' },
        deploySaving: false,
        // GET /sakuna/resources -- a bulk quantity/deployed inventory, not a
        // per-unit asset with its own status/condition/fuel/personnel/serial
        // (see tab-resources.blade.php's header for what that means for the UI).
        async loadResources() {
            this.resourcesLoading = true;
            this.resourcesFailed = false;
            try {
                await this.$store.session.whenReady();
                const [res, incidents] = await Promise.all([
                    $api.getAll('/admin/sakuna/resources'),
                    this.allowed('sakuna_incidents') ? $api.getAll('/admin/sakuna/incidents').catch(() => ({ data: [] })) : this.nothing(),
                ]);
                this.resourcesList = res.data || [];
                this.resourceIncidentsOpen = (incidents.data || []).filter(i => !['Completed', 'Archived'].includes(i.status));
                this.saveCopy('resources', { list: this.resourcesList, incidents: this.resourceIncidentsOpen });
            } catch {
                const copy = this.useCopy('resources');
                if (copy) { this.resourcesList = copy.data.list || []; this.resourceIncidentsOpen = copy.data.incidents || []; } else this.resourcesFailed = true;
            } finally { this.resourcesLoading = false; }
        },
        // category has no fixed vocabulary on the backend (a plain string
        // column) -- offered as suggestions from what is actually in use
        // rather than a hardcoded, possibly-wrong enum.
        resourceCategories() {
            return [...new Set(this.resourcesList.map(r => r.category).filter(Boolean))].sort();
        },
        resetResourceForm() {
            this.resourceForm = { ref: null, name: '', category: '', quantity: '', unit: '', readiness: '', barangay: '' };
        },
        openCreateResource() {
            this.resetResourceForm();
            this.resourceFormMode = 'create';
            this.showCreateResource = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openEditResource(r) {
            this.resourceForm = { ref: r.ref, name: r.name, category: r.category, quantity: r.quantity, unit: r.unit || '', readiness: r.readiness || '', barangay: r.barangay || '' };
            this.resourceFormMode = 'edit';
            this.showCreateResource = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async saveResource() {
            if (this.resourceSaving || !this.resourceForm.name || !this.resourceForm.category || this.resourceForm.quantity === '') return;
            this.resourceSaving = true;
            const payload = { name: this.resourceForm.name, category: this.resourceForm.category, quantity: Number(this.resourceForm.quantity), unit: this.resourceForm.unit || null, readiness: this.resourceForm.readiness || null, barangay: this.resourceForm.barangay || null };
            try {
                const res = this.resourceFormMode === 'create'
                    ? await $api.post('/admin/sakuna/resources', payload)
                    : await $api.put('/admin/sakuna/resources/' + this.resourceForm.ref, payload);
                const idx = this.resourcesList.findIndex(r => r.ref === res.data.ref);
                if (idx !== -1) this.resourcesList[idx] = { ...this.resourcesList[idx], ...res.data }; else this.resourcesList.unshift(res.data);
                if (this.selectedResource?.ref === res.data.ref) this.selectedResource = { ...this.selectedResource, ...res.data };
                this.showCreateResource = false;
                this.toast(this.resourceFormMode === 'create' ? res.data.ref + ' registered.' : 'Resource updated.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this resource.', 'error');
            } finally { this.resourceSaving = false; }
        },
        // The list row already has quantity/deployed/available; the detail
        // fetch adds sakuna_resource_movements' real deploy/return history,
        // never read back anywhere before GET /sakuna/resources/{ref} existed.
        async openResourceDetail(row) {
            this.selectedResource = row;
            this.showResourceDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            try {
                const res = await $api.get('/admin/sakuna/resources/' + row.ref);
                this.selectedResource = { ...row, ...res.data };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },
        openDeploy(r) {
            this.selectedResource = r;
            this.deployForm = { quantity: '', incident_id: '' };
            this.showDeployModal = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        // verb is 'deploy' or 'return' -- the only two actions the backend
        // actually implements; incident_id is optional (a return never needs
        // one, and a deploy may be general standby rather than incident-tied).
        async submitDeploy(verb) {
            if (this.deploySaving || !this.selectedResource || !this.deployForm.quantity) return;
            this.deploySaving = true;
            const payload = { quantity: Number(this.deployForm.quantity) };
            if (verb === 'deploy' && this.deployForm.incident_id) payload.incident_id = Number(this.deployForm.incident_id);
            try {
                const res = await $api.post('/admin/sakuna/resources/' + this.selectedResource.ref + '/' + verb, payload);
                const idx = this.resourcesList.findIndex(r => r.ref === res.data.ref);
                if (idx !== -1) this.resourcesList[idx] = { ...this.resourcesList[idx], ...res.data };
                if (this.selectedResource?.ref === res.data.ref) this.selectedResource = { ...this.selectedResource, ...res.data };
                this.showDeployModal = false;
                this.toast((verb === 'deploy' ? 'Deployed ' : 'Returned ') + payload.quantity + ' ' + (this.selectedResource.unit || 'unit(s)') + '.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not record this movement.', 'error');
            } finally { this.deploySaving = false; }
        },

        // Relief tab state
        reliefSearch: '',
        reliefOperationFilter: '',
        reliefStatusFilter: '',
        reliefList: [],
        reliefViewing: null,
        reliefLoading: true,
        reliefFailed: false,
        showCreateDistribution: false,
        distributionSaving: false,
        distributionForm: { operation: '', barangay: '', beneficiary_name: '', item: '', quantity: '', unit: '', cost: '', provider: '', evacuee_ref: '', center: '', location: '' },
        // GET /sakuna/relief -- SakunaReliefDistribution is the ONLY real model
        // behind this submodule (see tab-relief.blade.php's header for what
        // that means for the UI: no inventory, request-approval or donation
        // model exists anywhere in the schema).
        async loadRelief() {
            this.reliefLoading = true;
            this.reliefFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/relief');
                this.reliefList = res.data || [];
                this.saveCopy('relief', this.reliefList);
            } catch {
                const copy = this.useCopy('relief');
                if (copy) this.reliefList = copy.data || []; else this.reliefFailed = true;
            } finally { this.reliefLoading = false; }
        },
        reliefOperations() {
            return [...new Set(this.reliefList.map(d => d.operation).filter(Boolean))].sort();
        },
        resetDistributionForm() {
            this.distributionForm = { operation: this.reliefOperationFilter || '', barangay: '', beneficiary_name: '', item: '', quantity: '', unit: '', cost: '', provider: '', evacuee_ref: '', center: '', location: '' };
        },
        openCreateDistribution() {
            this.resetDistributionForm();
            // The sheltered families, for the FACED card picker, when the
            // Evacuees tab has not loaded them yet.
            if (!this.evacueesList.length && this.can('sakuna_evacuees', 'view')) {
                $api.getAll('/admin/sakuna/evacuees').then(r => { this.evacueesList = r.data || []; }).catch(() => {});
            }
            this.loadReliefCenters();
            this.showCreateDistribution = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        // DUPLICATE_DISTRIBUTION (409) is a real, enforced guard -- the same
        // item cannot go to the same beneficiary twice in one operation; a
        // different item can (2026-10-27). Surfaced via the ordinary error
        // toast rather than re-implemented client-side. another = true keeps
        // the form open on the same family for its next item.
        async submitDistribution(another = false) {
            if (this.distributionSaving || !this.distributionForm.operation || !this.distributionForm.barangay || !this.distributionForm.beneficiary_name || !this.distributionForm.item) return;
            this.distributionSaving = true;
            const f = this.distributionForm;
            const payload = {
                ...f,
                quantity: f.quantity === '' ? undefined : Number(f.quantity),
                cost: f.cost === '' ? undefined : Number(f.cost),
                unit: f.unit || undefined,
                provider: f.provider || undefined,
                evacuee_ref: f.evacuee_ref || undefined,
                center: f.center || undefined,
                location: f.location.trim() || undefined,
            };
            try {
                const res = await $api.post('/admin/sakuna/relief', payload);
                this.reliefList.unshift(res.data);
                if (another) {
                    Object.assign(this.distributionForm, { item: '', quantity: '', unit: '', cost: '' });
                    this.$nextTick(() => document.getElementById('dist-item')?.focus());
                } else {
                    this.showCreateDistribution = false;
                }
                this.toast(res.data.ref + ' recorded.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not record this distribution.', 'error');
            } finally { this.distributionSaving = false; }
        },
        // A release for a sheltered family lands on its FACED card's Family
        // Assistance Record; picking the family fills its name and barangay.
        pickReliefFamily() {
            const ev = this.evacueesList.find(e => e.ref === this.distributionForm.evacuee_ref);
            if (!ev) return;
            this.distributionForm.beneficiary_name = ev.head_name;
            // A barangay officer records the relief their barangay gives, also
            // to a family from elsewhere staying at their center (2026-10-27).
            this.distributionForm.barangay = this.$store.session.inScope(ev.barangay) ? ev.barangay : this.$store.session.account.scope;
            this.distributionForm.center = this.reliefCenterFor(ev);
        },
        async releaseDistribution(d) {
            try {
                const res = await $api.post('/admin/sakuna/relief/' + d.ref + '/release', {});
                const idx = this.reliefList.findIndex(x => x.ref === res.data.ref);
                if (idx !== -1) this.reliefList[idx] = { ...this.reliefList[idx], ...res.data };
                this.toast(d.ref + ' released.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not release this distribution.', 'error');
            }
        },

        // Damage tab state
        damageSearch: '',
        damageBarangayFilter: '',
        damageSectorFilter: '',
        selectedAssessment: null,
        showAssessmentDetail: false,
        showCreateAssessment: false,
        damagesList: [],
        damagesLoading: true,
        damagesFailed: false,
        damageFormMode: 'create',
        damageSaving: false,
        damageForm: { ref: null, barangay: '', sector: '', units_affected: '', estimated_cost: '', notes: '', incident_id: '', photoFile: null },
        damageIncidentsOpen: [],
        // GET /sakuna/damage -- SakunaDamageAssessment has no `type` enum, no
        // assessor name, no priority-needs list and no Population/Housing/
        // Infrastructure/Agriculture/Financial breakdown; see
        // tab-damage.blade.php's header for what that means for the UI.
        async loadDamage() {
            this.damagesLoading = true;
            this.damagesFailed = false;
            try {
                await this.$store.session.whenReady();
                const [res, incidents] = await Promise.all([
                    $api.getAll('/admin/sakuna/damage'),
                    this.allowed('sakuna_incidents') ? $api.getAll('/admin/sakuna/incidents').catch(() => ({ data: [] })) : this.nothing(),
                ]);
                this.damagesList = res.data || [];
                this.damageIncidentsOpen = incidents.data || [];
            } catch { this.damagesFailed = true; } finally { this.damagesLoading = false; }
        },
        damageSectors() {
            return [...new Set(this.damagesList.map(d => d.sector).filter(Boolean))].sort();
        },
        resetDamageForm() {
            this.damageForm = { ref: null, barangay: '', sector: '', units_affected: '', estimated_cost: '', notes: '', incident_id: '', photoFile: null };
        },
        openCreateAssessment() {
            this.resetDamageForm();
            this.damageFormMode = 'create';
            this.showCreateAssessment = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openEditAssessment(d) {
            this.damageForm = { ref: d.ref, barangay: d.barangay, sector: d.sector, units_affected: d.units_affected ?? '', estimated_cost: d.estimated_cost ?? '', notes: d.notes || '', incident_id: '', photoFile: null };
            this.damageFormMode = 'edit';
            this.showCreateAssessment = true;
            this.$nextTick(() => window.renderIcons?.());
            // The list row carries neither (working test 2026-11-03): fetched, and
            // filled only while the form is still this assessment's and untouched.
            $api.get('/admin/sakuna/damage/' + d.ref).then((res) => {
                if (this.damageForm.ref !== d.ref) return;
                if (!this.damageForm.notes && res.data?.notes) this.damageForm.notes = res.data.notes;
                const inc = (this.damageIncidentsOpen || []).find((i) => i.ref === res.data?.incident_ref);
                if (inc && !this.damageForm.incident_id) this.damageForm.incident_id = inc.id;
            }).catch(() => {});
        },
        // PHP never populates a multipart body's fields on a true PUT request
        // (only POST) -- sent as POST with Laravel's own _method override on
        // edit, the same workaround already established for admin banners.
        async saveDamage() {
            if (this.damageSaving || !this.damageForm.barangay || !this.damageForm.sector) return;
            this.damageSaving = true;
            const body = new FormData();
            body.append('barangay', this.damageForm.barangay);
            body.append('sector', this.damageForm.sector);
            if (this.damageForm.units_affected !== '') body.append('units_affected', this.damageForm.units_affected);
            if (this.damageForm.estimated_cost !== '') body.append('estimated_cost', this.damageForm.estimated_cost);
            if (this.damageForm.notes) body.append('notes', this.damageForm.notes);
            if (this.damageForm.incident_id) body.append('incident_id', this.damageForm.incident_id);
            if (this.damageForm.photoFile) body.append('photo', this.damageForm.photoFile);
            try {
                let res;
                if (this.damageFormMode === 'create') {
                    res = await $api.post('/admin/sakuna/damage', body);
                } else {
                    body.append('_method', 'PUT');
                    res = await $api.post('/admin/sakuna/damage/' + this.damageForm.ref, body);
                }
                const idx = this.damagesList.findIndex(d => d.ref === res.data.ref);
                if (idx !== -1) this.damagesList[idx] = { ...this.damagesList[idx], ...res.data }; else this.damagesList.unshift(res.data);
                if (this.selectedAssessment?.ref === res.data.ref) this.selectedAssessment = { ...this.selectedAssessment, ...res.data };
                this.showCreateAssessment = false;
                this.toast(this.damageFormMode === 'create' ? res.data.ref + ' recorded.' : 'Assessment updated.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this assessment.', 'error');
            } finally { this.damageSaving = false; }
        },
        // The list row has only a photo COUNT; the detail fetch adds notes,
        // the linked incident's ref and each photo's real signed URL.
        async openAssessmentDetail(row) {
            this.selectedAssessment = row;
            this.showAssessmentDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            try {
                const res = await $api.get('/admin/sakuna/damage/' + row.ref);
                this.selectedAssessment = { ...row, ...res.data };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },

        // Alerts tab state
        alertSearch: '',
        alertTypeFilter: '',
        alertLevelFilter: '',
        alertStatusFilter: '',
        selectedAlert: null,
        showAlertDetail: false,
        showCreateAlert: false,
        showCancelAlert: false,
        alertsList: [],
        alertsLoading: true,
        alertsFailed: false,
        alertFormMode: 'create',
        alertSaving: false,
        alertForm: { ref: null, type: '', level: 'Advisory', title_fil: '', title_en: '', body_fil: '', body_en: '', target_barangays: [], channels: ['in_app'], expires_at: '' },
        cancelReason: '',
        cancelSaving: false,
        // GET /sakuna/alerts -- the real channel vocabulary is in_app/sms/
        // email; in_app also pushes to residents' phones (2026-10-26);
        // the mock's SMS/Mobile Push/Barangay Notification/Portal Banner/
        // Social Media list was never real. target_barangays is the only
        // real audience axis -- there is no demographic/occupational
        // 'target group' concept (All Residents/Farmers/PWD Households/etc.)
        // anywhere in the schema.
        async loadAlerts() {
            this.alertsLoading = true;
            this.alertsFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/alerts');
                this.alertsList = res.data || [];
                this.saveCopy('alerts', this.alertsList);
            } catch {
                const copy = this.useCopy('alerts');
                if (copy) this.alertsList = copy.data; else this.alertsFailed = true;
            } finally { this.alertsLoading = false; }
        },
        // type has no fixed vocabulary on the backend (a plain string column)
        // -- offered as suggestions from what is actually in use, the same
        // treatment Resources' category and Damage's sector already get.
        alertTypes() {
            return [...new Set(this.alertsList.map(a => a.type).filter(Boolean))].sort();
        },
        resetAlertForm() {
            this.alertForm = { ref: null, type: '', level: 'Advisory', audience: '', title_fil: '', title_en: '', body_fil: '', body_en: '', target_barangays: [], channels: ['in_app'], expires_at: '' };
        },
        // Critique 2026-11-03 (second run): who an alert reaches is chosen, never
        // implied by leaving the barangays empty, and a written draft is never
        // thrown away by Escape or a stray click without asking.
        alertFormSnapshot: '',
        alertDiscardAsk: false,
        get alertFormDirty() { return JSON.stringify(this.alertForm) !== this.alertFormSnapshot; },
        get alertFormReady() {
            const f = this.alertForm;
            return !!(f.type && f.level && f.title_fil && f.title_en && f.body_fil && f.body_en && f.channels.length
                && (f.audience === 'all' || (f.audience === 'barangays' && f.target_barangays.length)));
        },
        get alertFormMissing() {
            const f = this.alertForm, miss = [];
            if (!f.type) miss.push('the type');
            if (!f.title_fil || !f.title_en) miss.push(!f.title_fil && !f.title_en ? 'both titles' : (!f.title_fil ? 'the Filipino title' : 'the English title'));
            if (!f.body_fil || !f.body_en) miss.push(!f.body_fil && !f.body_en ? 'both messages' : (!f.body_fil ? 'the Filipino message' : 'the English message'));
            if (!f.audience) miss.push('who receives it');
            else if (f.audience === 'barangays' && !f.target_barangays.length) miss.push('at least one barangay');
            if (!f.channels.length) miss.push('a channel');
            return miss;
        },
        // A new alert's draft is kept on this computer while it is written, so a
        // dropped connection or a closed tab does not lose both languages.
        alertDraftKey() { return 'sakuna-alert-draft:' + (this.$store.session.account?.id || 'anon'); },
        keepAlertDraft() {
            if (!this.showCreateAlert || this.alertFormMode !== 'create') return;
            try { if (this.alertFormDirty) localStorage.setItem(this.alertDraftKey(), JSON.stringify(this.alertForm)); } catch { /* storage blocked */ }
        },
        dropAlertDraft() { try { localStorage.removeItem(this.alertDraftKey()); } catch { /* storage blocked */ } },
        alertDraftRestored: false,
        get alertOffline() { return navigator.onLine === false || this.headerState === 'failed'; },
        // The form opens with the cursor in its first field, not on Close.
        focusAlertForm() { setTimeout(() => document.getElementById('alert-type')?.focus(), 120); },
        closeAlertForm(force = false) {
            if (!force && this.alertFormDirty && !this.alertSaving) { this.alertDiscardAsk = true; return; }
            if (force && this.alertFormMode === 'create') this.dropAlertDraft();
            this.alertDiscardAsk = false;
            this.showCreateAlert = false;
        },
        alertReachLabel(a) {
            return (a?.barangays && a.barangays.length) ? 'Residents of ' + a.barangays.join(', ') : 'Residents of every barangay (whole municipality)';
        },
        // An alert can end by itself (2026-10-17): the form holds local time
        // for the datetime-local input; the server gets an ISO instant.
        localInput(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d)) return '';
            const p = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
        },
        nowInput() { return this.localInput(new Date().toISOString()); },
        alertEnds(a) {
            if (!a?.expires_at) return '';
            const when = new Date(a.expires_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
            return (a.expired ? 'Ended ' : 'Ends ') + when;
        },
        openCreateAlert() {
            this.resetAlertForm();
            this.alertFormMode = 'create';
            this.alertFormSnapshot = JSON.stringify(this.alertForm);
            this.alertDraftRestored = false;
            try {
                const draft = JSON.parse(localStorage.getItem(this.alertDraftKey()) || 'null');
                if (draft) { this.alertForm = { ...this.alertForm, ...draft, ref: null }; this.alertDraftRestored = true; }
            } catch { /* no draft */ }
            this.alertDiscardAsk = false;
            this.showCreateAlert = true;
            this.$nextTick(() => window.renderIcons?.());
            this.focusAlertForm();
        },
        openEditAlert(a) {
            this.alertForm = { ref: a.ref, type: a.type, level: a.level, audience: (a.barangays || []).length ? 'barangays' : 'all', title_fil: a.title?.fil || '', title_en: a.title?.en || '', body_fil: a.body?.fil || '', body_en: a.body?.en || '', target_barangays: [...(a.barangays || [])], channels: [...(a.channels || ['in_app'])], expires_at: this.localInput(a.expires_at) };
            this.alertFormMode = 'edit';
            this.alertFormSnapshot = JSON.stringify(this.alertForm);
            this.alertDiscardAsk = false;
            this.showCreateAlert = true;
            this.$nextTick(() => window.renderIcons?.());
            this.focusAlertForm();
        },
        toggleAlertFormBarangay(b) {
            this.alertForm.target_barangays = this.alertForm.target_barangays.includes(b)
                ? this.alertForm.target_barangays.filter(x => x !== b)
                : [...this.alertForm.target_barangays, b];
        },
        toggleAlertFormChannel(c) {
            this.alertForm.channels = this.alertForm.channels.includes(c)
                ? this.alertForm.channels.filter(x => x !== c)
                : [...this.alertForm.channels, c];
        },
        async saveAlert() {
            if (this.alertSaving || !this.alertFormReady) return;
            this.alertSaving = true;
            const payload = { type: this.alertForm.type, level: this.alertForm.level, title_fil: this.alertForm.title_fil, title_en: this.alertForm.title_en, body_fil: this.alertForm.body_fil, body_en: this.alertForm.body_en, target_barangays: this.alertForm.audience === 'all' ? [] : this.alertForm.target_barangays, channels: this.alertForm.channels, expires_at: this.alertForm.expires_at ? new Date(this.alertForm.expires_at).toISOString() : null };
            try {
                const res = this.alertFormMode === 'create'
                    ? await $api.post('/admin/sakuna/alerts', payload)
                    : await $api.put('/admin/sakuna/alerts/' + this.alertForm.ref, payload);
                const idx = this.alertsList.findIndex(a => a.ref === res.data.ref);
                if (idx !== -1) this.alertsList[idx] = { ...this.alertsList[idx], ...res.data }; else this.alertsList.unshift(res.data);
                if (this.selectedAlert?.ref === res.data.ref) this.selectedAlert = { ...this.selectedAlert, ...res.data };
                this.closeAlertForm(true);
                this.toast(this.alertFormMode === 'create' ? res.data.ref + ' drafted.' : 'Alert updated.', 'success');
                if (this.alertSendAfterSave) this.askPublishAlert({ ...res.data, body: { fil: payload.body_fil, en: payload.body_en } });
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this alert.', 'error');
            } finally { this.alertSaving = false; this.alertSendAfterSave = false; }
        },
        // The list row has no body text; the detail fetch adds the real
        // bilingual message, never read back by anything before this.
        async openAlertDetail(row) {
            this.selectedAlert = row;
            this.showAlertDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            try {
                const res = await $api.get('/admin/sakuna/alerts/' + row.ref);
                this.selectedAlert = { ...row, ...res.data };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },
        // Recipients are materialised at publish time -- the same real
        // rows a cancellation later has to reach exactly.
        // Sending a public alert is reviewed first (critique 2026-11-03): who it
        // reaches, through which channels, both texts and the end, then one
        // explicit send. The count is known only once it is sent.
        publishTarget: null,
        showPublishConfirm: false,
        publishSaving: false,
        alertSendAfterSave: false,
        channelLabel(c) { return { in_app: 'In-app and phone push', sms: 'SMS', email: 'Email' }[c] || c; },
        // The levels the alerts carry: the standard five, then any other level on record (e.g. Orange).
        alertLevels() {
            const std = ['Information', 'Advisory', 'Watch', 'Warning', 'Critical'];
            return [...std, ...new Set(this.alertsList.map(a => a.level).filter(l => l && !std.includes(l)))];
        },
        async askPublishAlert(a) {
            if (!a || this.publishSaving) return;
            this.showAlertDetail = false;
            this.publishTarget = a;
            this.showPublishConfirm = true;
            this.$nextTick(() => window.renderIcons?.());
            if (a.body?.en) return;
            try {
                const res = await $api.get('/admin/sakuna/alerts/' + a.ref);
                if (this.publishTarget?.ref === a.ref) this.publishTarget = { ...a, ...res.data };
            } catch { /* the review still shows the titles, places and channels */ }
        },
        async confirmPublishAlert() {
            if (!this.publishTarget || this.publishSaving) return;
            this.publishSaving = true;
            try {
                await this.publishAlert(this.publishTarget);
            } finally {
                this.publishSaving = false;
                this.showPublishConfirm = false;
                this.publishTarget = null;
            }
        },
        async publishAlert(a) {
            try {
                const res = await $api.post('/admin/sakuna/alerts/' + a.ref + '/publish', {});
                const idx = this.alertsList.findIndex(x => x.ref === res.data.ref);
                if (idx !== -1) this.alertsList[idx] = { ...this.alertsList[idx], ...res.data };
                if (this.selectedAlert?.ref === res.data.ref) this.selectedAlert = { ...this.selectedAlert, ...res.data };
                // The list's own word and unit: Released, residents (flow guide
                // 2026-11-13, MDRRMO bug 9).
                const n = res.data.recipients;
                this.toast(a.ref + ' released to ' + n + (n === 1 ? ' resident.' : ' residents.'), 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not publish this alert.', 'error');
            }
        },
        openCancelAlert(a) {
            this.selectedAlert = a;
            this.cancelReason = '';
            this.showCancelAlert = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async submitCancelAlert() {
            if (this.cancelSaving || !this.cancelReason.trim()) return;
            this.cancelSaving = true;
            try {
                const res = await $api.post('/admin/sakuna/alerts/' + this.selectedAlert.ref + '/cancel', { reason: this.cancelReason });
                const idx = this.alertsList.findIndex(x => x.ref === res.data.ref);
                if (idx !== -1) this.alertsList[idx] = { ...this.alertsList[idx], ...res.data };
                this.showCancelAlert = false;
                // People, as the list counts them; the server counted rows per
                // channel before (MDRRMO bug 4).
                const n = res.data.cancellation_reached;
                this.toast('Cancellation sent to ' + n + (n === 1 ? ' resident.' : ' residents.'), 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not cancel this alert.', 'error');
            } finally { this.cancelSaving = false; }
        },

        // Command center state
        // Shaped with real empty defaults, not null: the content wrapper below
        // is only display:none while loading, not removed from the DOM, so
        // Alpine still evaluates its bindings against this before the fetch
        // resolves. A null here would throw on every nested field access.
        commandBoard: {
            counts: { incidents_open: 0, incidents_total: 0, centers_open: 0, families_sheltered: 0, individuals_sheltered: 0, relief_released: 0, damage_reports: 0, alerts_live: 0 },
            markers: [], centers: [], feed: [], alerts: [], resources: [],
        },
        commandLoading: true,
        commandFailed: false,
        priorityIncidents: [],
        commandIncidents: [],
        commandOpenCount: 0,
        infoTip: null,
        // A table that scrolls sideways is a Tab stop, so the keyboard can
        // scroll it; one that fits is not (critique 2026-11-03, eighth run).
        watchScrollers() {
            if (!window.ResizeObserver) return;
            const fit = (el) => {
                if (el.dataset.label === undefined) el.dataset.label = (el.getAttribute('aria-label') || '').replace(/ \(scrolls sideways\)$/, '');
                const scrolls = el.scrollWidth > el.clientWidth + 1;
                el.tabIndex = scrolls ? 0 : -1;
                el.setAttribute('aria-label', el.dataset.label + (scrolls ? ' (scrolls sideways)' : ''));
            };
            const ro = new ResizeObserver((entries) => entries.forEach((e) => fit(e.target.closest('.sk-scroll'))));
            document.querySelectorAll('.sk-scroll[role=region]').forEach((el) => {
                ro.observe(el);
                if (el.firstElementChild) ro.observe(el.firstElementChild);
                fit(el);
            });
        },
        // False while offline with no copy of the incidents: the queue and the
        // Needs action counts are then unknown, never nothing waiting.
        commandIncidentsKnown: true,
        missingUnknown: false,
        // People still missing, from the Missing Persons summary; null where the
        // role may not read cases or the feature is off.
        missingSummary: null,
        // What needs someone now (critique 2026-11-03, second run): the open
        // incidents nobody has validated or taken, the one waiting longest, and
        // the centers filling up. Counted from the records, never estimated.
        get needsAction() {
            const open = this.commandIncidents.filter(i => this.$store.session.inScope(i.barangay));
            const waiting = open.filter(i => i.status === 'Submitted').sort((a, b) => new Date(a.reported) - new Date(b.reported));
            return {
                toValidate: waiting.length,
                unassigned: open.filter(i => !i.assigned_name).length,
                // The same incident the queue tags Longest (critique, ninth run).
                oldest: open.find(i => i.ref === this.longestWaitingRef) || null,
                fullCentres: (this.commandBoard?.centers || []).filter(c => c.pct >= 75),
            };
        },
        incidentTypeIcon(type) {
            const map = {
                'Flood': 'waves', 'Landslide': 'triangle-alert', 'Fire': 'flame', 'Earthquake': 'activity',
                'Vehicular Accident': 'car', 'Medical Emergency': 'heart-pulse', 'Missing Person': 'search',
                'Stranded Resident': 'anchor', 'Fallen Tree': 'tree-pine', 'Downed Power Line': 'zap',
                'Road Obstruction': 'cone', 'Damaged Bridge': 'construction', 'Rescue Request': 'life-buoy',
                'Drowning or Water Rescue': 'anchor', 'Agricultural Damage': 'wheat', 'Structural Damage': 'building-2',
                'Water Interruption': 'droplets', 'Power Interruption': 'zap-off', 'Public Health Emergency': 'shield-alert',
            };
            return map[type] || 'alert-circle';
        },
        waitedLabel(iso) {
            if (!iso) return '—';
            const mins = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
            if (mins < 60) return mins + 'm';
            if (mins < 1440) return Math.round(mins / 60) + 'h';
            return Math.round(mins / 1440) + 'd';
        },
        // Red means one thing (critique 2026-11-03, third run): the single open
        // incident that has waited longest for someone to validate or take it.
        // Others waiting more than 4 hours are amber (owner, 2026-11-03: after a
        // few hours of a typhoon every wait passed an hour); one in hand stays neutral.
        isWaiting(inc) {
            return !!inc?.reported && !['Completed', 'Archived', 'Rejected'].includes(inc.status) && (inc.status === 'Submitted' || !inc.assigned_name);
        },
        get longestWaitingRef() {
            const list = (this.commandIncidents.length ? this.commandIncidents : (this.incidentsList || []))
                .filter(i => this.isWaiting(i) && this.$store.session.inScope(i.barangay));
            if (!list.length) return null;
            return list.reduce((a, b) => (new Date(b.reported) < new Date(a.reported) ? b : a)).ref;
        },
        waitTone(inc) {
            if (!inc?.reported || ['Completed', 'Archived', 'Rejected'].includes(inc.status)) return 'sk-muted';
            if (!this.isWaiting(inc)) return '';
            const h = (Date.now() - new Date(inc.reported).getTime()) / 3600000;
            if (h < 4) return '';
            return inc.ref === this.longestWaitingRef ? 'sk-wait-red' : 'sk-wait-amber';
        },
        personnel: [],
        showEscalateForm: false,
        showAssignForm: false,
        escalateSeverity: 'High',
        // Escalate only raises (critique 2026-11-03, ninth run): the severities
        // above the incident's own, nearest first; none for a Critical one.
        escalateChoices() {
            const order = ['Low', 'Medium', 'High', 'Critical'];
            const at = order.indexOf(this.selectedIncident?.severity);
            return order.slice(at + 1);
        },
        escalateNote: '',
        assignTo: '',

        // GET /sakuna/command-center (CommandCenter::board -- a read-only
        // projection over everything else) plus the real incidents list for
        // a true severity-ranked priority queue, which the projection itself
        // doesn't provide (its own `markers` are only incidents with lat/lng).
        // The Assign picker's people outside the Command Center: each tab is
        // its own page, so the Incidents tab asks when Assign is first opened.
        // Sakuna's own list, held to assign rather than Personnel, so an
        // MDRRMO Head assigns without User Management (owner, 2026-10-28).
        async loadPersonnel() {
            if (this.personnel.length) return;
            try {
                const res = await (this.$store.session.canPerm('sakuna_incidents', 'assign') ? $api.get('/admin/sakuna/assignees') : this.nothing());
                this.personnel = res.data || [];
            } catch { /* the picker stays empty */ }
        },
        async loadCommandCenter() {
            this.commandLoading = true;
            this.commandFailed = false;
            try {
                // The Sakuna list feeds the Assign picker; only a role that
                // may assign asks for it (every 403 pops up).
                await this.$store.session.whenReady();
                const [board, incidents, personnel] = await Promise.all([
                    $api.get('/admin/sakuna/command-center'),
                    this.allowed('sakuna_incidents') ? $api.getAll('/admin/sakuna/incidents') : this.nothing(),
                    this.$store.session.canPerm('sakuna_incidents', 'assign') ? $api.get('/admin/sakuna/assignees').catch(() => ({ data: [] })) : this.nothing(),
                ]);
                this.commandBoard = board.data;
                this.saveCopy('board', board.data);
                // The hotlines ride along, so they are on hand if the next load fails.
                if (this.$store.session.canPerm('sakuna_alerts', 'view')) $api.get('/admin/sakuna/hotlines').then((r) => this.saveCopy('hotlines', r.data || [])).catch(() => {});
                this.setCommandIncidents(incidents.data || []);
                if (this.allowed('sakuna_incidents')) this.saveCopy('incidents', incidents.data || []);
                this.commandIncidentsKnown = true;
                this.personnel = personnel.data || [];
            } catch {
                // The last board and incidents, marked with their time, rather than nothing.
                // With no copy of the incidents, the queue and counts are unknown, not zero
                // (critique 2026-11-03, eighth run).
                const copy = this.useCopy('board');
                if (copy) {
                    this.commandBoard = copy.data;
                    const incidents = this.lastCopy('incidents');
                    this.commandIncidentsKnown = !!incidents || !this.allowed('sakuna_incidents');
                    this.setCommandIncidents(incidents?.data || []);
                } else this.commandFailed = true;
            } finally { this.commandLoading = false; }
        },
        // The queue (critique 2026-11-03, seventh run): the same open incidents
        // the Needs action strip counts, most severe first and, within a
        // severity, the one waiting longest first, as on the Incidents tab.
        // The count is taken before the list is cut to six.
        setCommandIncidents(list) {
            const rank = { Critical: 0, High: 1, Medium: 2, Low: 3 };
            const at = (i) => new Date(i.reported || 0).getTime();
            this.commandIncidents = list.filter(i => !['Completed', 'Archived', 'Rejected'].includes(i.status));
            const open = this.commandIncidents.filter(i => this.$store.session.inScope(i.barangay));
            this.commandOpenCount = open.length;
            this.priorityIncidents = [...open]
                .sort((a, b) => ((rank[a.severity] ?? 9) - (rank[b.severity] ?? 9))
                    || (this.isWaiting(b) - this.isWaiting(a))
                    || (at(a) - at(b)))
                .slice(0, 6);
        },
        async openIncidentDetail(row) {
            this.selectedIncident = row;
            this.showIncidentDetail = true;
            this.incidentConfirmVerb = '';
            this.showEscalateForm = false;
            this.showAssignForm = false;
            this.assignSearch = '';
            this.assignShowOthers = false;
            this.incidentLocationEditing = false;
            this.incidentDetailFailed = false;
            this.$nextTick(() => window.renderIcons?.());
            // The list row is lean; the detail fetch adds description/timeline.
            try {
                const res = await $api.get('/admin/sakuna/incidents/' + row.ref);
                this.selectedIncident = { ...row, ...res.data };
            } catch { this.incidentDetailFailed = true; /* the list row's fields stay */ }
        },
        // Pin an incident on the map, move its pin or take it off (2026-10-26):
        // PUT /sakuna/incidents/{ref}/location, sakuna_incidents.edit. For the
        // reports filed without one, and a resident's pin that was off.
        incidentLocationEditing: false,
        incidentLocationDraft: { lat: null, lng: null },
        incidentLocationSaving: false,
        startIncidentLocation() {
            const i = this.selectedIncident || {};
            this.incidentLocationDraft = { lat: i.lat ?? null, lng: i.lng ?? null };
            this.incidentLocationEditing = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        async saveIncidentLocation() {
            const inc = this.selectedIncident;
            if (!inc || this.incidentLocationSaving) return;
            const pinned = this.hasMapPin(this.incidentLocationDraft);
            this.incidentLocationSaving = true;
            try {
                const res = await $api.put('/admin/sakuna/incidents/' + inc.ref + '/location', {
                    lat: pinned ? Number(this.incidentLocationDraft.lat) : null,
                    lng: pinned ? Number(this.incidentLocationDraft.lng) : null,
                });
                this.selectedIncident = { ...inc, ...res.data };
                [this.incidentsList, this.priorityIncidents].forEach((list) => {
                    const row = (list || []).find((r) => r.ref === inc.ref);
                    if (row) Object.assign(row, { lat: res.data.lat, lng: res.data.lng });
                });
                this.incidentLocationEditing = false;
                this.toast(inc.ref + (pinned ? ': map location saved.' : ': map location removed.'), 'success');
                // The Command Center's map and its count of unpinned incidents.
                if (this.activeTab === 'command-center') {
                    $api.get('/admin/sakuna/command-center').then((b) => { this.commandBoard = b.data; }).catch(() => {});
                }
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save the map location.', 'error');
            } finally { this.incidentLocationSaving = false; }
        },
        // One method for every verb in the chain (validate/assign/dispatch/
        // escalate/resolve/close) -- the verb IS the permission on the backend
        // too, so this mirrors that exactly rather than having six near-copies.
        // The incident window's steps (critique 2026-11-03): one next step as
        // the primary button, the rest in a menu, and marking resolved or
        // closing confirmed first because it ends the work on the incident.
        incidentConfirmVerb: '',
        // Not valid (2026-11-13): the reason the reporter reads, at least 5 characters.
        incidentRejectReason: '',
        incidentDetailFailed: false,
        // Assigning (critique 2026-11-03, fourth run): the people who can take
        // the incident, MDRRMO first, grouped by role, searchable, each with the
        // open incidents already on their hands.
        assignSearch: '',
        assignShowOthers: false,
        openLoad(p) {
            const list = this.commandIncidents.length ? this.commandIncidents : (this.incidentsList || []);
            return list.filter(i => i.assigned_name === p.name && !['Completed', 'Archived', 'Rejected'].includes(i.status)).length;
        },
        // list: the people in scope for this incident (the window filters them).
        assignGroups(list) {
            const q = this.assignSearch.trim().toLowerCase();
            const people = (list || []).filter(p => !q || [p.name, p.employee_id, p.role].some(v => String(v || '').toLowerCase().includes(q)));
            const groups = {};
            people.forEach(p => { (groups[p.role || 'Other'] ||= []).push(p); });
            const rank = (r) => (/mdrrmo/i.test(r) ? 0 : 1);
            return Object.keys(groups).sort((a, b) => rank(a) - rank(b) || a.localeCompare(b))
                .map(role => ({ role, people: groups[role].sort((a, b) => this.openLoad(a) - this.openLoad(b) || a.name.localeCompare(b.name)) }));
        },
        incidentActing: false,
        incidentVerbLabel(v) {
            return { validate: 'Validate', dispatch: 'Dispatch', resolve: 'Mark resolved', resolve_close: 'Resolve and close', close: 'Close incident', assign: 'Assign', escalate: 'Escalate', reject: 'Mark not valid' }[v] || v;
        },
        // Sprint 4 (owner's manual test, 2026-11-06): Mark resolved then
        // Close incident were two buttons and two confirmations for one
        // decision. The window offers one Resolve and close (resolve, then
        // close) whenever the role may do both; close alone stays only for an
        // incident already Resolved (Completed), e.g. an older one.
        incidentSteps(inc) {
            const verbs = inc?.available_verbs || [];
            const both = verbs.includes('resolve') && this.can('sakuna_incidents', 'close');
            return ['validate', 'dispatch', 'resolve', 'close']
                .filter(v => verbs.includes(v))
                .map(v => (v === 'resolve' && both ? 'resolve_close' : v));
        },
        nextIncidentVerb(inc) {
            return this.incidentSteps(inc)[0] || '';
        },
        otherIncidentVerbs(inc) {
            return this.incidentSteps(inc).slice(1);
        },
        // From a list row: open the incident with Validate ready, never validate unseen.
        async openIncidentToValidate(inc) {
            await this.openIncidentDetail(inc);
            setTimeout(() => document.getElementById('incident-next-step')?.focus(), 250);
        },
        askIncidentVerb(inc, verb) {
            if (['resolve', 'resolve_close', 'close', 'reject'].includes(verb)) { if (verb === 'reject') this.incidentRejectReason = ''; this.incidentConfirmVerb = verb; return; }
            this.runIncidentVerb(inc, verb);
        },
        async runIncidentVerb(inc, verb) {
            if (this.incidentActing || !inc) return;
            this.incidentActing = true;
            try {
                if (verb === 'reject') { await this.actIncidentVerb(inc, 'reject', { note: this.incidentRejectReason.trim() }); return; }
                if (verb !== 'resolve_close') { await this.actIncidentVerb(inc, verb); return; }
                // One confirmation, two steps. A close that fails after the
                // resolve went through leaves the incident Resolved, and says so.
                if (!(await this.actIncidentVerb(inc, 'resolve', {}, { quiet: true }))) return;
                if (await this.actIncidentVerb(inc, 'close', {}, { quiet: true, failure: inc.ref + ' is marked resolved, but it could not be closed. It stays Resolved; try Close incident again.' })) {
                    this.toast(inc.ref + ' resolved and closed.', 'success');
                }
            } finally { this.incidentActing = false; this.incidentConfirmVerb = ''; }
        },
        // Returns true when the step went through (the window chains resolve and close).
        async actIncidentVerb(inc, verb, payload = {}, opts = {}) {
            try {
                const res = await $api.post('/admin/sakuna/incidents/' + inc.ref + '/' + verb, payload);
                Object.assign(inc, res.data);
                if (this.selectedIncident?.ref === inc.ref) this.selectedIncident = { ...this.selectedIncident, ...res.data };
                // The window works on a copy after its first step: the list rows
                // and the Command Center queue follow every step too (working
                // test 2026-11-03: a row stayed Validated after Close).
                [this.incidentsList, this.commandIncidents].forEach((list) => {
                    const row = (list || []).find((r) => r.ref === inc.ref);
                    if (row && row !== inc) Object.assign(row, res.data);
                });
                if (this.commandIncidents.length) this.setCommandIncidents(this.commandIncidents);
                this.showEscalateForm = false;
                this.showAssignForm = false;
                const done = { validate: 'validated', assign: 'assigned', dispatch: 'dispatched', escalate: 'escalated', resolve: 'marked resolved', close: 'closed', reject: 'marked not valid; the reporter was told why' }[verb] || verb;
                if (!opts.quiet) this.toast(inc.ref + ' ' + done + '.', 'success');
                return true;
            } catch (err) {
                const why = err.localised ? err.localised('en') : 'Could not ' + verb + ' this incident.';
                this.toast(opts.failure ? opts.failure + ' (' + why + ')' : why, 'error');
                return false;
            }
        },

        // Reports tab
        reportBarangayFilter: '',
        reportsLoading: true,
        reportsFailed: false,
        reportIncidents: [],
        reportCenters: [],
        reportVulnerability: [],
        // No operational-period model exists anywhere in Sakuna's schema (the
        // shell's own period selector above is chrome, not a real filter) --
        // barangay is the one real, consistent axis across all three lists.
        async loadReports() {
            this.reportsLoading = true;
            this.reportsFailed = false;
            try {
                // Reports reads three lists; the Records Officer holds no
                // incidents or centers and the Information Officer no
                // vulnerability, so each section shows what the role may see.
                await this.$store.session.whenReady();
                const [inc, centers, vul] = await Promise.all([
                    this.allowed('sakuna_incidents') ? $api.getAll('/admin/sakuna/incidents') : this.nothing(),
                    this.mayListCenters() ? $api.getAll('/admin/sakuna/centers') : this.nothing(),
                    this.allowed('sakuna_vulnerability') ? $api.getAll('/admin/sakuna/vulnerability') : this.nothing(),
                ]);
                this.reportIncidents = inc.data || [];
                this.reportCenters = centers.data || [];
                this.reportVulnerability = vul.data || [];
            } catch { this.reportsFailed = true; } finally { this.reportsLoading = false; }
        },
        get reportScopedIncidents() { return this.reportIncidents.filter(i => !this.reportBarangayFilter || i.barangay === this.reportBarangayFilter); },
        get reportScopedCenters() { return this.reportCenters.filter(c => !this.reportBarangayFilter || c.barangay === this.reportBarangayFilter); },
        get reportScopedVulnerability() { return this.reportVulnerability.filter(v => !this.reportBarangayFilter || v.barangay === this.reportBarangayFilter); },
        // Last 14 days, bucketed from each incident's own real `reported`
        // timestamp -- there is no stored daily-trend aggregate anywhere.
        reportTrendLabels() {
            return Array.from({ length: 14 }, (_, i) => {
                const d = new Date(); d.setDate(d.getDate() - (13 - i));
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
        },
        reportTrendData() {
            const days = this.reportTrendLabels();
            const counts = days.map(() => 0);
            this.reportScopedIncidents.forEach(i => {
                if (!i.reported) return;
                const label = new Date(i.reported).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const idx = days.indexOf(label);
                if (idx !== -1) counts[idx]++;
            });
            return counts;
        },
        reportTypeBreakdown() {
            const counts = {};
            this.reportScopedIncidents.forEach(i => { counts[i.type] = (counts[i.type] || 0) + 1; });
            return Object.entries(counts).sort((a, b) => b[1] - a[1]);
        },
        reportLowestReadiness(n) {
            return [...this.reportScopedVulnerability]
                .filter(v => v.readiness_score !== null && v.readiness_score !== undefined)
                .sort((a, b) => a.readiness_score - b.readiness_score)
                .slice(0, n);
        },

        // Only this tab's partial exists in the document now, so switching tabs
        // is a navigation. The query string is preserved deliberately: arriving
        // from a resident profile carries ?ref=, and init() re-enters here.
        // Incidents with a filter already chosen (critique 2026-11-03, eighth run).
        openIncidentsFiltered(filters) {
            if (!this.tabAllowed('incidents') || !this.tabUrls.incidents) return;
            window.location.href = this.tabUrls.incidents + '?' + new URLSearchParams(filters);
        },
        setTab(tab) {
            if (!this.tabAllowed(tab)) return;
            if (tab === this.activeTab) {
                this.$nextTick(() => window.renderIcons?.());
                return;
            }
            const url = this.tabUrls[tab];
            if (url) {
                // A tab's own settings (a chip's filter, an incident to record a
                // person under, a centre to open) do not follow to other tabs.
                const params = new URLSearchParams(window.location.search);
                const own = { incidents: ['filter_status', 'filter_assigned', 'ref'], alerts: ['compose'], 'missing-persons': ['incident'], 'evacuation-centers': ['center'] };
                Object.entries(own).forEach(([t, keys]) => { if (t !== tab) keys.forEach((k) => params.delete(k)); });
                window.location.href = url + (params.toString() ? '?' + params : '');
                return;
            }
            this.activeTab = tab;
            this.$nextTick(() => window.renderIcons?.());
        },

        // A center's or a family's stay: open / checked in is green, closed /
        // checked out slate, anything else blue (fifth critique run).
        stayPill(status) { return status === 'Processing' ? 'sk-p-green' : (['Archived', 'Completed'].includes(status) ? 'sk-p-slate' : 'sk-p-blue'); },
        // The pill colour a public alert level wears, wherever it appears.
        alertLevelPill(level) {
            return ({ Critical: 'sk-p-red', Red: 'sk-p-red', Warning: 'sk-p-orange', Orange: 'sk-p-orange',
                Watch: 'sk-p-amber', Yellow: 'sk-p-amber', Advisory: 'sk-p-teal', Information: 'sk-p-blue' })[level] || 'sk-p-slate';
        },
        opStatusColor() {
            if (this.liveStatus === 'Alert in effect') return this.alertLevelPill(this.headerBoard?.alerts?.[0]?.level);
            if (this.liveStatus === 'Normal') return 'sk-p-green';
            return 'sk-p-slate';
        },

        severityBadge(sev) {
            const map = {
                'Critical': 'bg-rose-100 text-rose-700 border border-rose-200',
                'High': 'bg-orange-100 text-orange-700 border border-orange-200',
                'Medium': 'bg-amber-100 text-amber-700 border border-amber-200',
                'Low': 'bg-slate-100 text-slate-600 border border-slate-200',
            };
            return map[sev] || 'bg-slate-100 text-slate-600';
        },

        statusBadge(st) {
            // The statuses Sakuna's records carry (critique 2026-11-03, seventh
            // run: the old invented vocabulary is gone). Anything else is slate.
            const map = {
                'Draft': 'bg-slate-100 text-slate-600',
                'Submitted': 'bg-blue-100 text-blue-700',
                'Under Verification': 'bg-indigo-100 text-indigo-700',
                'Assigned': 'bg-violet-100 text-violet-700',
                'Processing': 'bg-orange-100 text-orange-700',
                'Approved': 'bg-emerald-100 text-emerald-700',
                'Released': 'bg-emerald-100 text-emerald-700',
                'Completed': 'bg-emerald-100 text-emerald-700',
                'Cancelled': 'bg-rose-100 text-rose-700',
                'Archived': 'bg-slate-100 text-ink-muted',
                'Open': 'bg-emerald-100 text-emerald-700',
                'Closed': 'bg-slate-100 text-ink-muted',
            };
            return (map[st] || 'bg-slate-100 text-slate-600') + ' px-2.5 py-0.5 rounded-full text-xs font-medium';
        },

        riskBadge(risk) {
            const map = {
                'Critical': 'bg-rose-100 text-rose-700 border border-rose-200',
                'High': 'bg-orange-100 text-orange-700 border border-orange-200',
                'Moderate': 'bg-amber-100 text-amber-700 border border-amber-200',
                'Low': 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            };
            return (map[risk] || 'bg-slate-100 text-slate-600') + ' px-2.5 py-0.5 rounded-full text-xs font-medium';
        },

        alertLevelBadge(level) {
            const map = {
                'Information': 'bg-sky-100 text-sky-700 border border-sky-200',
                'Advisory': 'bg-teal-100 text-teal-700 border border-teal-200',
                'Watch': 'bg-amber-100 text-amber-700 border border-amber-200',
                'Warning': 'bg-orange-100 text-orange-700 border border-orange-200',
                'Critical': 'bg-rose-100 text-rose-700 border border-rose-200',
                // PAGASA-style colour levels, when an alert carries one.
                'Yellow': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                'Orange': 'bg-orange-100 text-orange-800 border border-orange-300',
                'Red': 'bg-rose-100 text-rose-800 border border-rose-300',
            };
            return (map[level] || 'bg-slate-100 text-slate-600') + ' px-2.5 py-0.5 rounded-full text-xs font-medium';
        },

        occupancyBar(pct) {
            if (pct >= 90) return 'bg-rose-500';
            if (pct >= 75) return 'bg-orange-400';
            if (pct >= 50) return 'bg-amber-400';
            return 'bg-emerald-500';
        },

        toast(message, variant = 'success') {
            this.$dispatch('toast', { message, variant });
        },
        // Sakuna's submodule keys vary per tab (sakuna_vulnerability,
        // sakuna_incidents, ...), so this takes both rather than being a
        // single-submodule wrapper like other admin screens' own can().
        can(submodule, action) {
            return this.$store.session.canPerm ? this.$store.session.canPerm(submodule, action) : true;
        },

        // Each tab is one submodule. A role sees only the tabs it may view and
        // lands on the first of them: the Front Desk, Records, Information,
        // Department Head and Staff accounts opened tabs outside their part
        // and got 'Something went wrong' plus a 'Walang pahintulot' pop-up
        // (QA run 2026-10-05).
        tabSubs: {
            'command-center': 'sakuna_command', 'vulnerability': 'sakuna_vulnerability',
            'incidents': 'sakuna_incidents', 'missing-persons': 'sakuna_missing', 'evacuation-centers': 'sakuna_centers',
            'evacuees': 'sakuna_evacuees', 'resources': 'sakuna_resources',
            'relief': 'sakuna_relief', 'damage-assessment': 'sakuna_damage',
            'bdrrmc-forms': 'sakuna_reports', 'alerts': 'sakuna_alerts', 'reports': 'sakuna_reports',
        },
        allowed(submodule) { return this.can(submodule, 'view'); },
        tabAllowed(tab) { return this.allowed(this.tabSubs[tab]); },
        // Missing Persons switched off on this server (owner, 2026-11-05;
        // production has it off): the server answers FEATURE_OFF. Learned once
        // for the page, from the first request that meets it; then its tab, the
        // Command Center's card and chip, and the report's case-kind filter hide.
        missingOff: false,
        // The one-incident report window is open (_incident-report-print).
        incidentReportOpen: false,
        markMissingOff() {
            if (this.missingOff) return;
            this.missingOff = true;
            this.missingSummary = null;
            this.missingUnknown = false;
            window.dispatchEvent(new CustomEvent('sakuna-missing-off'));
        },
        // The tab bar shows a tab the role may view, Missing Persons only while it is on.
        tabShown(tab) { return this.tabAllowed(tab) && !(tab === 'missing-persons' && this.missingOff); },
        // The Command Center and Missing Persons tabs ask Missing Persons
        // themselves; any other tab asks once, quietly.
        async probeMissing() {
            await this.$store.session.whenReady();
            if (['command-center', 'missing-persons'].includes(this.activeTab) || !this.can('sakuna_missing', 'view')) return;
            try { await $api.get('/admin/sakuna/missing/summary', { silent: true }); } catch (e) { if (e?.code === 'FEATURE_OFF') this.markMissingOff(); }
        },
        get permsReady() { return this.$store.session.permsReady; },
        get activeAllowed() { return this.permsReady && this.tabAllowed(this.activeTab); },
        get firstAllowedTab() { return Object.keys(this.tabSubs).find((t) => this.tabAllowed(t)) || null; },
        // Choosing a center to check a family into, or move them to, needs the
        // list without the Evacuation Centers tab (the server agrees).
        mayListCenters() {
            return this.allowed('sakuna_centers') || this.can('sakuna_evacuees', 'check_in') || this.can('sakuna_evacuees', 'transfer');
        },
        nothing() { return Promise.resolve({ data: [] }); },
        // A shared link to a tab this role does not hold lands on the first one
        // it does, and says so (live test 2026-10-07, NAV-1): it used to move
        // the person without a word.
        tabLabels: @js(collect($tabs)->pluck('label', 'key')),
        async landOnAllowedTab() {
            await this.$store.session.whenReady();
            const moved = new URLSearchParams(window.location.search).get('no_access');
            if (moved && this.tabLabels[moved] && this.tabAllowed(this.activeTab)) {
                this.toast('You do not have access to ' + this.tabLabels[moved] + '; showing ' + this.tabLabels[this.activeTab] + '.', 'info');
                const params = new URLSearchParams(window.location.search);
                params.delete('no_access');
                history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params : ''));
            }
            if (this.tabAllowed(this.activeTab)) return;
            const first = this.firstAllowedTab;
            if (first && this.tabUrls[first]) {
                const params = new URLSearchParams(window.location.search);
                params.set('no_access', this.activeTab);
                window.location.replace(this.tabUrls[first] + '?' + params);
            }
        },
        // A Send alert button in the header lands here with ?compose=1.
        composeOnArrival() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('compose') !== '1') return;
            params.delete('compose');
            history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params : ''));
            if (this.can('sakuna_alerts', 'create')) this.openCreateAlert();
        },
        // Incidents keep the shared request statuses on record; the screen says
        // what happened in the words of the buttons that moved them (live test
        // 2026-10-07, UX-17): Dispatch reads Dispatched, not Processing.
        incidentStatusLabel(st) {
            return ({
                'Submitted': 'Reported',
                'Under Verification': 'Validated',
                'Assigned': 'Assigned',
                'Processing': 'Dispatched',
                'Completed': 'Resolved',
                'Archived': 'Closed',
                'Rejected': 'Not valid',
            })[st] || st;
        },
    }"
    x-init="
        setTimeout(() => window.renderIcons?.(), 600);
        $nextTick(() => watchScrollers());
    "
    @click="const b = $event.target.closest('[data-info]'); if (b) { const r = b.getBoundingClientRect(); infoTip = infoTip && infoTip.text === b.title ? null : { text: b.title, top: r.bottom + 6, left: Math.max(16, Math.min(r.left - 140, window.innerWidth - 336)) }; } else if (infoTip) infoTip = null;"
    @keydown.escape.window="infoTip = null" @scroll.window="infoTip = null"
    class="sk space-y-3 max-w-[1480px]"
>
    {{-- What an (i) button says, on a press as well as a hover (critique, eighth run). --}}
    <div x-show="infoTip" x-cloak role="status" class="fixed z-50 w-80 max-w-[calc(100vw-32px)] rounded-xl bg-navy-900 text-white text-[13px] leading-snug px-3 py-2 shadow-float"
        :style="infoTip ? 'top:' + infoTip.top + 'px; left:' + infoTip.left + 'px' : ''" x-text="infoTip?.text || ''"></div>
    {{-- ── Sakuna header (owner's Sakuna Redesign, 2026-11-02) ────────────────
         One white band: the live status and the emergency actions, then the
         tab row. The tabs stay links (each tab is its own page) and wrap
         rather than scroll, so no tab is ever off screen (live test NAV-4). --}}
    <header class="sk-card">
        <div class="flex flex-wrap items-center gap-2 px-4 pt-2.5 pb-1">
            <span x-show="!headerBoard?.alerts?.length" :class="opStatusColor()" class="sk-pill" style="padding: 3px 9px;" title="From the public alerts in effect and the incidents still open">
                <i data-lucide="shield-alert" class="w-3 h-3"></i>
                <span x-text="liveStatus"></span>
            </span>

            <button type="button" x-show="headerBoard && activeTab !== 'command-center' && (tabAllowed('alerts') || tabAllowed('incidents'))" @click="setTab(headerBoard?.alerts?.length && tabAllowed('alerts') ? 'alerts' : 'incidents')" :class="headerBoard?.alerts?.length ? alertLevelPill(headerBoard.alerts[0].level) : 'sk-p-slate'" class="sk-pill hover:brightness-95" style="padding: 3px 9px;" title="Open the alerts or incidents behind this">
                <span class="sk-dot shrink-0"></span>
                <span x-text="headerBoard?.alerts?.length ? headerBoard.alerts[0].level + ' · ' + activeEventLabel : activeEventLabel" class="text-left whitespace-normal"></span>
            </button>

            <button type="button" @click="loadHeader()" :disabled="headerRefreshing"
                class="sk-updated" :class="'sk-updated-' + headerState" title="Refresh now. The board also refreshes every minute.">
                <i data-lucide="refresh-cw" x-show="headerState === 'live' || headerState === 'loading'" class="w-3.5 h-3.5" :class="headerRefreshing ? 'animate-spin' : ''"></i>
                <i data-lucide="cloud-off" x-show="headerState === 'stale' || headerState === 'failed'" x-cloak class="w-3.5 h-3.5"></i>
                <span x-text="headerUpdatedLabel"></span>
            </button>

            <div class="ml-auto flex items-center gap-2">
                {{-- Warning the public must never be past the edge of the tab row
                     (live test 2026-10-07, NAV-4). --}}
                <button x-show="permsReady && can('sakuna_alerts', 'create')" x-cloak
                    @click="activeTab === 'alerts' ? openCreateAlert() : (window.location.href = tabUrls['alerts'] + '?compose=1')"
                    class="sk-btn sk-btn-amber" title="Write a public alert; you review it before it is sent">
                    <i data-lucide="bell-ring"></i> New alert
                </button>
            </div>
        </div>

        {{-- The tabs share the spare width evenly while they fit on one row (owner, 2026-11-02). --}}
        <div class="sk-tabhost">
        <nav aria-label="Sakuna sections" class="flex flex-wrap px-2">
            @foreach($tabs as $t)
                <a
                    href="{{ $tabUrls[$t['key']] }}"
                    x-show="permsReady && tabShown('{{ $t['key'] }}')" x-cloak
                    @if($activeTab === $t['key']) aria-current="page" @endif
                    class="sk-tab {{ $activeTab === $t['key'] ? 'sk-tab-on' : '' }}"
                >{{ $t['label'] }}</a>
            @endforeach
        </nav>
        </div>
    </header>

    <div>
        {{-- A refresh failed: this tab shows its last copy, with its time and why
             (critique 2026-11-03, second run). --}}
        <div x-show="tabCopy && staleSince[tabCopy[0]]" x-cloak role="status"
            class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-3 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900">
            <i data-lucide="cloud-off" class="w-4 h-4 shrink-0"></i>
            <span x-text="tabCopy && staleSince[tabCopy[0]] ? 'Showing the last copy, from ' + new Date(staleSince[tabCopy[0]]).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) + '. ' + offlineWhy : ''"></span>
            <button type="button" @click="tabCopy && tabCopy[1]()" class="font-semibold underline underline-offset-2 hover:text-amber-950">Try again</button>
        </div>
        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mb-3 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing the Sakuna report for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to profile
            </button>
        </div>

        {{-- ── Tab Panels ───────────────────────────────────────────────────── --}}
        <div>
            {{-- A tab renders, and loads, only once the role may view it; a tab
                 outside the role's part sends them to the first one they hold. --}}
            <p x-show="!activeAllowed" class="py-10 text-center text-sm text-ink-muted">Loading…</p>
            <template x-if="activeAllowed">
            <div x-init="$nextTick(() => window.renderIcons?.())">

            {{-- Command Center --}}
            @if($activeTab === 'command-center')
            <div>
                {{-- Loads its own data from GET /sakuna/command-center + /sakuna/incidents; no fixture variables needed. --}}
                @include('admin.partials.sakuna.tab-command')
            </div>
            @endif

            {{-- Vulnerability --}}
            @if($activeTab === 'vulnerability')
            <div>
                {{-- Loads its own data from GET /sakuna/vulnerability; $barangays is real, static reference data. --}}
                @include('admin.partials.sakuna.tab-vulnerability', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Incidents --}}
            @if($activeTab === 'incidents')
            <div>
                {{-- Loads its own data from GET/POST /sakuna/incidents; $barangays is real, static reference data. --}}
                @include('admin.partials.sakuna.tab-incidents', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Missing Persons (owner, 2026-10-30) --}}
            @if($activeTab === 'missing-persons')
            <div>
                {{-- Loads its own data from GET /sakuna/missing; $barangays is real, static reference data. --}}
                @include('admin.partials.sakuna.tab-missing', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Evacuation Centers --}}
            @if($activeTab === 'evacuation-centers')
            <div>
                {{-- Loads its own data from GET/POST/PUT /sakuna/centers; $barangays is real, static reference data. --}}
                @include('admin.partials.sakuna.tab-centers', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Evacuees --}}
            @if($activeTab === 'evacuees')
            <div>
                {{-- Loads its own data from GET /sakuna/evacuees and GET /sakuna/centers; $barangays is real, static reference data. --}}
                @include('admin.partials.sakuna.tab-evacuees', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Resources --}}
            @if($activeTab === 'resources')
            <div>
                @include('admin.partials.sakuna.tab-resources', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Relief & Logistics --}}
            @if($activeTab === 'relief')
            <div>
                @include('admin.partials.sakuna.tab-relief', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Damage Assessment --}}
            @if($activeTab === 'damage-assessment')
            <div>
                @include('admin.partials.sakuna.tab-damage', ['barangays' => $barangays])
            </div>
            @endif

            {{-- BDRRMC Forms --}}
            @if($activeTab === 'bdrrmc-forms')
            <div>
                @include('admin.partials.sakuna.tab-bdrrmc-forms')
            </div>
            @endif

            {{-- Alerts --}}
            @if($activeTab === 'alerts')
            <div>
                @include('admin.partials.sakuna.tab-alerts', ['barangays' => $barangays])
            </div>
            @endif

            {{-- Reports --}}
            @if($activeTab === 'reports')
            <div>
                @include('admin.partials.sakuna.tab-reports', ['barangays' => $barangays])
            </div>
            @endif

            </div>
            </template>
        </div>
    </div>

</div>

</x-layouts.admin>
