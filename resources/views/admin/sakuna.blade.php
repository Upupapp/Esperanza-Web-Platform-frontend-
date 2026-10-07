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
    subtitle="Disaster risk, emergency operations, evacuation, relief, and recovery management."
    active="sakuna"
>

<div x-show="!$store.session.can('sakuna')" x-cloak>
    <x-admin.access-restricted module="Sakuna (Disaster Risk & Emergency Ops)" icon="shield-alert" />
</div>

<div
    x-show="$store.session.can('sakuna')"
    x-cloak
    x-data="{
        activeTab: '{{ $activeTab }}',
        tabUrls: @js($tabUrls),
        // The status bar reads the live board (GET /admin/sakuna/command-center):
        // the alerts in effect and the incidents still open. It used to show a
        // fixed Red Alert and a July 2026 monsoon that never happened.
        // Activate EOC still sets a display-only status on this screen.
        opStatusOverride: '',
        headerBoard: null,
        headerFailed: false,
        get liveStatus() {
            const c = this.headerBoard?.counts;
            if (!c) return 'Normal';
            if (c.alerts_live > 0) return 'Alert in effect';
            if (c.incidents_open > 0) return 'Monitoring';
            return 'Normal';
        },
        get opStatus() { return this.opStatusOverride || this.liveStatus; },
        get activeEventLabel() {
            if (this.headerFailed) return 'Live board not available';
            const b = this.headerBoard;
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
            this.headerFailed = false;
            try {
                this.headerBoard = (await $api.get('/admin/sakuna/command-center')).data || {};
            } catch { this.headerFailed = true; }
        },
        selectedBarangay: '',
        ready: false,
        returnToProfile: null,
        // Arrived from a resident's profile via Open in Sakuna — land on the
        // Incidents tab pre-filtered to their exact report, and offer a way
        // straight back to the profile that started the trip.
        init() {
            this.landOnAllowedTab();
            this.loadHeader();
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref) {
                this.setTab('incidents');
                this.incidentSearch = ref;
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
        selectedIncident: null,
        showIncidentDetail: false,
        showCreateIncident: false,
        incidentsList: [],
        incidentsLoading: true,
        incidentsFailed: false,
        incidentSaving: false,
        incidentForm: { title: '', type: '', barangay: '', sitio: '', severity: '', description: '', source: '' },
        // GET /sakuna/incidents (full list for this tab, distinct from Command
        // Center's own severity-ranked slice of the same endpoint).
        async loadIncidentsList() {
            this.incidentsLoading = true;
            this.incidentsFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/incidents');
                this.incidentsList = res.data || [];
            } catch { this.incidentsFailed = true; } finally { this.incidentsLoading = false; }
        },
        resetIncidentForm() {
            this.incidentForm = { title: '', type: '', barangay: '', sitio: '', severity: '', description: '', source: '' };
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
        centerForm: { ref: null, name: '', barangay: '', address: '', capacity: '', services: [] },
        centerEvacuees: [],
        // GET /sakuna/centers (real, derived occupancy -- SakunaCenter has no
        // lat/lng, no manager/contact column, no inspection/room/personnel
        // sub-model; see tab-centers.blade.php's header comment).
        async loadCenters() {
            this.centersLoading = true;
            this.centersFailed = false;
            try {
                const res = await $api.getAll('/admin/sakuna/centers');
                this.centersList = res.data || [];
            } catch { this.centersFailed = true; } finally { this.centersLoading = false; }
        },
        resetCenterForm() {
            this.centerForm = { ref: null, name: '', barangay: '', address: '', capacity: '', services: [] };
        },
        openCreateCenter() {
            this.resetCenterForm();
            this.centerFormMode = 'create';
            this.showCreateCenter = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openEditCenter(c) {
            this.centerForm = { ref: c.ref, name: c.name, barangay: c.barangay, address: c.address || '', capacity: c.capacity, services: [...(c.services || [])] };
            this.centerFormMode = 'edit';
            this.showCreateCenter = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        toggleCenterFormService(s) {
            this.centerForm.services = this.centerForm.services.includes(s)
                ? this.centerForm.services.filter(x => x !== s)
                : [...this.centerForm.services, s];
        },
        async saveCenter() {
            if (this.centerSaving || !this.centerForm.name || !this.centerForm.barangay || this.centerForm.capacity === '') return;
            this.centerSaving = true;
            const payload = { name: this.centerForm.name, barangay: this.centerForm.barangay, address: this.centerForm.address || null, capacity: Number(this.centerForm.capacity), services: this.centerForm.services };
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
        checkInForm: { center: '', head_name: '', barangay: '', sitio: '', members: [] },
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
            } catch { this.evacueesFailed = true; } finally { this.evacueesLoading = false; }
        },
        resetCheckInForm() {
            this.checkInForm = { center: '', head_name: '', barangay: '', sitio: '', members: [] };
        },
        openEvacueeReg() {
            this.resetCheckInForm();
            this.showEvacueeReg = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        addCheckInMember() { this.checkInForm.members.push({ name: '', relation: '', age: '', sex: '', needs: '' }); },
        removeCheckInMember(i) { this.checkInForm.members.splice(i, 1); },
        // members[] is optional on the real endpoint (a bare member_count is
        // enough), but sending it is what makes the detail view's real
        // per-person needs/relation breakdown possible later.
        async submitCheckIn() {
            if (this.checkInSaving || !this.checkInForm.center || !this.checkInForm.head_name || !this.checkInForm.barangay) return;
            this.checkInSaving = true;
            const members = this.checkInForm.members
                .filter(m => m.name.trim())
                .map(m => ({
                    name: m.name.trim(),
                    relation: m.relation || null,
                    age: m.age === '' ? null : Number(m.age),
                    sex: m.sex || null,
                    needs: m.needs ? m.needs.split(',').map(s => s.trim()).filter(Boolean) : [],
                }));
            const payload = { head_name: this.checkInForm.head_name, barangay: this.checkInForm.barangay, sitio: this.checkInForm.sitio || null, members };
            try {
                const res = await $api.post('/admin/sakuna/centers/' + this.checkInForm.center + '/check-in', payload);
                this.evacueesList.unshift(res.data);
                this.showEvacueeReg = false;
                this.toast(res.data.ref + ' checked in.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not check in this household.', 'error');
            } finally { this.checkInSaving = false; }
        },
        async openEvacueeDetail(row) {
            this.selectedEvacuee = row;
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
            } catch { this.resourcesFailed = true; } finally { this.resourcesLoading = false; }
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
        reliefLoading: true,
        reliefFailed: false,
        showCreateDistribution: false,
        distributionSaving: false,
        distributionForm: { operation: '', barangay: '', beneficiary_name: '', item: '', quantity: '' },
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
            } catch { this.reliefFailed = true; } finally { this.reliefLoading = false; }
        },
        reliefOperations() {
            return [...new Set(this.reliefList.map(d => d.operation).filter(Boolean))].sort();
        },
        resetDistributionForm() {
            this.distributionForm = { operation: this.reliefOperationFilter || '', barangay: '', beneficiary_name: '', item: '', quantity: '' };
        },
        openCreateDistribution() {
            this.resetDistributionForm();
            this.showCreateDistribution = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        // DUPLICATE_DISTRIBUTION (409) is a real, enforced guard -- the same
        // beneficiary cannot receive twice from the same operation. Surfaced
        // via the ordinary error toast rather than re-implemented client-side.
        async submitDistribution() {
            if (this.distributionSaving || !this.distributionForm.operation || !this.distributionForm.barangay || !this.distributionForm.beneficiary_name || !this.distributionForm.item) return;
            this.distributionSaving = true;
            const payload = { ...this.distributionForm, quantity: this.distributionForm.quantity === '' ? undefined : Number(this.distributionForm.quantity) };
            try {
                const res = await $api.post('/admin/sakuna/relief', payload);
                this.reliefList.unshift(res.data);
                this.showCreateDistribution = false;
                this.toast(res.data.ref + ' recorded.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not record this distribution.', 'error');
            } finally { this.distributionSaving = false; }
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
        alertForm: { ref: null, type: '', level: 'Advisory', title_fil: '', title_en: '', body_fil: '', body_en: '', target_barangays: [], channels: ['in_app'] },
        cancelReason: '',
        cancelSaving: false,
        // GET /sakuna/alerts -- the real channel vocabulary is exactly
        // in_app/sms/email (AlertService::publish()'s own channel match);
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
            } catch { this.alertsFailed = true; } finally { this.alertsLoading = false; }
        },
        // type has no fixed vocabulary on the backend (a plain string column)
        // -- offered as suggestions from what is actually in use, the same
        // treatment Resources' category and Damage's sector already get.
        alertTypes() {
            return [...new Set(this.alertsList.map(a => a.type).filter(Boolean))].sort();
        },
        resetAlertForm() {
            this.alertForm = { ref: null, type: '', level: 'Advisory', title_fil: '', title_en: '', body_fil: '', body_en: '', target_barangays: [], channels: ['in_app'] };
        },
        openCreateAlert() {
            this.resetAlertForm();
            this.alertFormMode = 'create';
            this.showCreateAlert = true;
            this.$nextTick(() => window.renderIcons?.());
        },
        openEditAlert(a) {
            this.alertForm = { ref: a.ref, type: a.type, level: a.level, title_fil: a.title?.fil || '', title_en: a.title?.en || '', body_fil: a.body?.fil || '', body_en: a.body?.en || '', target_barangays: [...(a.barangays || [])], channels: [...(a.channels || ['in_app'])] };
            this.alertFormMode = 'edit';
            this.showCreateAlert = true;
            this.$nextTick(() => window.renderIcons?.());
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
            if (this.alertSaving || !this.alertForm.type || !this.alertForm.level || !this.alertForm.title_fil || !this.alertForm.title_en || !this.alertForm.body_fil || !this.alertForm.body_en) return;
            this.alertSaving = true;
            const payload = { type: this.alertForm.type, level: this.alertForm.level, title_fil: this.alertForm.title_fil, title_en: this.alertForm.title_en, body_fil: this.alertForm.body_fil, body_en: this.alertForm.body_en, target_barangays: this.alertForm.target_barangays, channels: this.alertForm.channels };
            try {
                const res = this.alertFormMode === 'create'
                    ? await $api.post('/admin/sakuna/alerts', payload)
                    : await $api.put('/admin/sakuna/alerts/' + this.alertForm.ref, payload);
                const idx = this.alertsList.findIndex(a => a.ref === res.data.ref);
                if (idx !== -1) this.alertsList[idx] = { ...this.alertsList[idx], ...res.data }; else this.alertsList.unshift(res.data);
                if (this.selectedAlert?.ref === res.data.ref) this.selectedAlert = { ...this.selectedAlert, ...res.data };
                this.showCreateAlert = false;
                this.toast(this.alertFormMode === 'create' ? res.data.ref + ' drafted.' : 'Alert updated.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not save this alert.', 'error');
            } finally { this.alertSaving = false; }
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
        async publishAlert(a) {
            try {
                const res = await $api.post('/admin/sakuna/alerts/' + a.ref + '/publish', {});
                const idx = this.alertsList.findIndex(x => x.ref === res.data.ref);
                if (idx !== -1) this.alertsList[idx] = { ...this.alertsList[idx], ...res.data };
                if (this.selectedAlert?.ref === res.data.ref) this.selectedAlert = { ...this.selectedAlert, ...res.data };
                this.toast(a.ref + ' published to ' + res.data.recipients + ' recipient(s).', 'success');
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
                this.toast('Cancellation reached ' + res.data.cancellation_reached + ' recipient(s).', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not cancel this alert.', 'error');
            } finally { this.cancelSaving = false; }
        },

        // Command center state
        showEOCConfirm: false,
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
        personnel: [],
        showEscalateForm: false,
        showAssignForm: false,
        escalateSeverity: 'High',
        escalateNote: '',
        assignTo: '',

        // GET /sakuna/command-center (CommandCenter::board -- a read-only
        // projection over everything else) plus the real incidents list for
        // a true severity-ranked priority queue, which the projection itself
        // doesn't provide (its own `markers` are only incidents with lat/lng).
        async loadCommandCenter() {
            this.commandLoading = true;
            this.commandFailed = false;
            try {
                // The personnel list feeds the Assign picker; only a role that
                // may see Personnel asks for it (every 403 pops up).
                await this.$store.session.whenReady();
                const [board, incidents, personnel] = await Promise.all([
                    $api.get('/admin/sakuna/command-center'),
                    this.allowed('sakuna_incidents') ? $api.getAll('/admin/sakuna/incidents') : this.nothing(),
                    this.$store.session.canPerm('um_personnel', 'view') ? $api.getAll('/admin/users').catch(() => ({ data: [] })) : this.nothing(),
                ]);
                this.commandBoard = board.data;
                const rank = { Critical: 0, High: 1, Medium: 2, Low: 3 };
                this.priorityIncidents = (incidents.data || [])
                    .filter(i => i.status !== 'Archived')
                    .sort((a, b) => (rank[a.severity] ?? 9) - (rank[b.severity] ?? 9))
                    .slice(0, 6);
                this.personnel = personnel.data || [];
            } catch { this.commandFailed = true; } finally { this.commandLoading = false; }
        },
        async openIncidentDetail(row) {
            this.selectedIncident = row;
            this.showIncidentDetail = true;
            this.showEscalateForm = false;
            this.showAssignForm = false;
            this.$nextTick(() => window.renderIcons?.());
            // The list row is lean; the detail fetch adds description/timeline.
            try {
                const res = await $api.get('/admin/sakuna/incidents/' + row.ref);
                this.selectedIncident = { ...row, ...res.data };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },
        // One method for every verb in the chain (validate/assign/dispatch/
        // escalate/resolve/close) -- the verb IS the permission on the backend
        // too, so this mirrors that exactly rather than having six near-copies.
        async actIncidentVerb(inc, verb, payload = {}) {
            try {
                const res = await $api.post('/admin/sakuna/incidents/' + inc.ref + '/' + verb, payload);
                Object.assign(inc, res.data);
                if (this.selectedIncident?.ref === inc.ref) this.selectedIncident = { ...this.selectedIncident, ...res.data };
                this.showEscalateForm = false;
                this.showAssignForm = false;
                this.toast(inc.ref + ' ' + verb + 'd.', 'success');
            } catch (err) {
                this.toast(err.localised ? err.localised('en') : 'Could not ' + verb + ' this incident.', 'error');
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
        setTab(tab) {
            if (!this.tabAllowed(tab)) return;
            if (tab === this.activeTab) {
                this.$nextTick(() => window.renderIcons?.());
                return;
            }
            const url = this.tabUrls[tab];
            if (url) {
                window.location.href = url + window.location.search;
                return;
            }
            this.activeTab = tab;
            this.$nextTick(() => window.renderIcons?.());
        },

        opStatusColor() {
            const map = {
                'Normal': 'text-emerald-700 bg-emerald-50 border-emerald-200',
                'Alert in effect': 'text-rose-700 bg-rose-50 border-rose-200',
                'Monitoring': 'text-blue-700 bg-blue-50 border-blue-200',
                'Blue Alert': 'text-indigo-700 bg-indigo-50 border-indigo-200',
                'Red Alert': 'text-rose-700 bg-rose-50 border-rose-200',
                'Emergency Operations Activated': 'text-red-700 bg-red-100 border-red-300 font-semibold',
                'Recovery Operations': 'text-amber-700 bg-amber-50 border-amber-200',
            };
            return map[this.opStatus] || 'text-slate-600 bg-slate-50 border-slate-200';
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
            const map = {
                'New': 'bg-sky-100 text-sky-700',
                'For Validation': 'bg-indigo-100 text-indigo-700',
                'Validated': 'bg-blue-100 text-blue-700',
                'Assigned': 'bg-violet-100 text-violet-700',
                'Dispatched': 'bg-purple-100 text-purple-700',
                'En Route': 'bg-fuchsia-100 text-fuchsia-700',
                'On Scene': 'bg-orange-100 text-orange-700',
                'Contained': 'bg-teal-100 text-teal-700',
                'Resolved': 'bg-emerald-100 text-emerald-700',
                'Closed': 'bg-slate-100 text-ink-muted',
                'Referred': 'bg-pink-100 text-pink-700',
                'Open': 'bg-emerald-100 text-emerald-700',
                'Standby': 'bg-blue-100 text-blue-700',
                'Preparing': 'bg-amber-100 text-amber-700',
                'Near Capacity': 'bg-orange-100 text-orange-700',
                'Full': 'bg-rose-100 text-rose-700',
                'Temporarily Unavailable': 'bg-slate-100 text-ink-muted',
                'Under Inspection': 'bg-indigo-100 text-indigo-700',
                'Available': 'bg-emerald-100 text-emerald-700',
                'Deployed': 'bg-orange-100 text-orange-700',
                'Returning': 'bg-teal-100 text-teal-700',
                'Under Maintenance': 'bg-slate-100 text-ink-muted',
                'Out of Service': 'bg-rose-100 text-rose-700',
                'Checked In': 'bg-emerald-100 text-emerald-700',
                'Temporarily Out': 'bg-amber-100 text-amber-700',
                'Transferred': 'bg-blue-100 text-blue-700',
                'Returned Home': 'bg-slate-100 text-ink-muted',
                'Referred to Hospital': 'bg-pink-100 text-pink-700',
                'Draft': 'bg-slate-100 text-slate-600',
                'Submitted': 'bg-blue-100 text-blue-700',
                'For Validation': 'bg-indigo-100 text-indigo-700',
                'For Approval': 'bg-violet-100 text-violet-700',
                'Approved': 'bg-emerald-100 text-emerald-700',
                'Returned for Revision': 'bg-amber-100 text-amber-700',
                'Consolidated': 'bg-teal-100 text-teal-700',
                'Published': 'bg-emerald-100 text-emerald-700',
                'Scheduled': 'bg-blue-100 text-blue-700',
                'Expired': 'bg-slate-100 text-ink-muted',
                'Cancelled': 'bg-rose-100 text-rose-700',
                'Archived': 'bg-slate-100 text-ink-muted',
                // Real values reachable on SakunaIncident/SakunaCenter/
                // SakunaEvacueeFamily that the map above never carried --
                // added when Command Center/Incidents/Centers were wired to
                // the real API rather than the fake vocabularies above.
                'Under Verification': 'bg-indigo-100 text-indigo-700',
                'Processing': 'bg-orange-100 text-orange-700',
                'Completed': 'bg-emerald-100 text-emerald-700',
                // The real value AlertService::publish() sets -- distinct
                // from the fake vocabulary's own unrelated 'Published' above.
                'Released': 'bg-emerald-100 text-emerald-700',
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
            'incidents': 'sakuna_incidents', 'evacuation-centers': 'sakuna_centers',
            'evacuees': 'sakuna_evacuees', 'resources': 'sakuna_resources',
            'relief': 'sakuna_relief', 'damage-assessment': 'sakuna_damage',
            'bdrrmc-forms': 'sakuna_reports', 'alerts': 'sakuna_alerts', 'reports': 'sakuna_reports',
        },
        allowed(submodule) { return this.can(submodule, 'view'); },
        tabAllowed(tab) { return this.allowed(this.tabSubs[tab]); },
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
            })[st] || st;
        },
    }"
    x-init="
        setTimeout(() => { ready = true; $nextTick(() => window.renderIcons?.()); }, 600);
    "
    class="space-y-4"
>
    {{-- ── Operational Status Bar ──────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-2">
        <span :class="opStatusColor()" class="inline-flex items-center gap-2 text-sm font-medium border rounded-xl px-3 py-1.5 shadow-card" :title="opStatusOverride ? 'Set on this screen only; nobody was notified' : 'From the public alerts in effect and the incidents still open'">
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span x-text="opStatus"></span>
        </span>

        <button type="button" x-show="tabAllowed('alerts') || tabAllowed('incidents')" @click="setTab(headerBoard?.alerts?.length && tabAllowed('alerts') ? 'alerts' : 'incidents')" class="inline-flex items-center gap-2 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-1.5 shadow-card hover:border-slate-300 transition-colors" title="Open the alerts or incidents behind this">
            <i data-lucide="radio-tower" class="w-4 h-4 text-ink-muted"></i>
            <span x-text="activeEventLabel" class="max-w-48 truncate"></span>
        </button>

        <button type="button" @click="loadHeader()" class="text-xs text-ink-muted hover:text-slate-700 flex items-center gap-1 transition-colors" title="Refresh">
            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
            <span x-text="headerBoard?.generated_at ? 'Updated ' + new Date(headerBoard.generated_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Not updated yet'"></span>
        </button>

        <div class="ml-auto flex items-center gap-2">
            <button x-show="tabAllowed('reports')" @click="setTab('reports')" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-2xl px-3 py-1.5 shadow-card hover:border-slate-300 transition-colors">
                <i data-lucide="file-bar-chart" class="w-4 h-4 text-ink-muted"></i> SitRep &amp; Reports
            </button>
            {{-- Warning the public must never be past the edge of the tab row
                 (live test 2026-10-07, NAV-4). --}}
            <button x-show="permsReady && can('sakuna_alerts', 'create')" x-cloak
                @click="activeTab === 'alerts' ? openCreateAlert() : (window.location.href = tabUrls['alerts'] + '?compose=1')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100 rounded-2xl px-3 py-1.5 shadow-card transition-colors">
                <i data-lucide="bell-ring" class="w-4 h-4"></i> Send alert
            </button>
            <button x-show="can('sakuna_command', 'escalate')" @click="showEOCConfirm = true; $nextTick(() => window.renderIcons?.())" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl px-3 py-1.5 shadow-card transition-colors">
                <i data-lucide="siren" class="w-4 h-4"></i> Activate EOC
            </button>
        </div>
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────────────────── --}}
    <div class="surface-glass rounded-[26px] overflow-hidden">
        <div>
            {{-- Clay design (STYLE_GUIDE §3.6): a pressed track, never underline tabs.
                 It wraps, so no tab is ever off screen (live test NAV-4). --}}
            <nav class="flex flex-wrap gap-1 m-4 mb-1 p-1 rounded-2xl surface-pressed-sm">
                @foreach($tabs as $t)
                    <a
                        href="{{ $tabUrls[$t['key']] }}"
                        x-show="permsReady && tabAllowed('{{ $t['key'] }}')" x-cloak
                        @if($activeTab === $t['key']) aria-current="page" @endif
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-[13px] font-semibold whitespace-nowrap transition-all duration-200 {{ $activeTab === $t['key'] ? 'bg-white/85 text-brand-600 font-bold shadow-[3px_4px_8px_rgb(29_71_214/.16),-2px_-2px_5px_rgb(255_255_255/.9),inset_0_1px_0_#fff]' : 'text-ink-muted hover:text-ink' }}"
                    >
                        <i data-lucide="{{ $t['icon'] }}" class="w-4 h-4"></i>
                        {{ $t['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Arrived from a resident's profile — offer a way straight back to it. --}}
        <div
            x-show="returnToProfile" x-cloak
            class="flex items-center justify-between gap-3 mx-5 mt-4 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100"
        >
            <p class="text-xs text-brand-700">
                Showing the Sakuna report for <span class="font-semibold" x-text="returnToProfile?.name"></span>.
            </p>
            <button
                type="button"
                @click="backToProfile()"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline shrink-0"
            >
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Profile
            </button>
        </div>

        {{-- ── Tab Panels ───────────────────────────────────────────────────── --}}
        <div class="p-5">
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

    {{-- ── EOC Activation Confirmation Modal ──────────────────────────────── --}}
    <div x-show="showEOCConfirm" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm" @click.self="showEOCConfirm = false"
        x-trap="showEOCConfirm"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="showEOCConfirm = false">
        <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="bg-white rounded-2xl shadow-float w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="siren" class="w-6 h-6 text-rose-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-ink-body">Set Operational Status</h3>
                    <p class="text-sm text-ink-muted">This changes the banner shown in your own browser only.</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-5">There is no backend notion of an "operational status" yet — no notification, log entry, or record is created anywhere by this. It changes what this banner reads, for you, until you reload the page.</p>
            <div class="flex gap-3">
                <button @click="showEOCConfirm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button @click="showEOCConfirm = false; opStatusOverride = 'Emergency Operations Activated'; toast('Operational status set. This is a local display setting only — nobody was notified.', 'warning'); $nextTick(() => window.renderIcons?.())" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors">Set Status</button>
            </div>
        </div>
    </div>

</div>

</x-layouts.admin>
