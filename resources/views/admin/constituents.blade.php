@php
    // All four Constituents tabs now load from the real API in loadRegistry()
    // below -- nothing in this file reads config('esperanza_constituents')
    // any more.
    $barangayOptions = config('esperanza.barangays');
    $tab  = $tab  ?? 'registry';
    $view = $view ?? 'individuals';
@endphp

<x-layouts.admin title="Constituents" subtitle="LGU constituent registry — residents, families, households." active="constituents">
<div
    x-data="{
        tab:  '{{ $tab }}',
        view: '{{ $view }}',
        loading: true,
        failed: false,
        // Set when a registry list has more rows than one load fetches (getAll's
        // bound), so the screen says it is a partial view instead of implying
        // these are every resident.
        registryTruncated: false,
        registryTotals: { residents: 0, households: 0, families: 0 },
        // The resident detail colours profile completeness by how far along
        // it is; it called this helper, which did not exist, and threw.
        completionTextColor(pct) {
            const p = Number(pct) || 0;
            return p >= 80 ? 'text-emerald-700' : (p >= 50 ? 'text-amber-700' : 'text-rose-700');
        },
        individuals:  [],
        families:     [],
        households:   [],
        citizens:     [],
        qualityIssues: [],
        encoders:     [],
        dqScanning:   false,
        barangayCoverage: [],
        coverageMunicipal: null,
        barangayOptions: @js($barangayOptions),

        async init() {
            await this.loadRegistry();
            // Landed here via the Back to Profile link from Dokyu/Tulong — reopen
            // exactly the resident that navigation started from, then forget the flag.
            const stored = sessionStorage.getItem('reopenResident');
            if (!stored) return;
            sessionStorage.removeItem('reopenResident');
            try {
                const target = JSON.parse(stored);
                const person = this.individuals.find(i => i.ref === target.ref);
                if (person) this.$nextTick(() => this.openIndividual(person));
            } catch (e) { /* malformed flag — ignore */ }
        },

        async loadRegistry() {
            this.loading = true;
            this.failed = false;
            try {
                const [residents, households, families, citizens, issues, encoders, coverage] = await Promise.all([
                    $api.getAll('/admin/residents'),
                    $api.getAll('/admin/households'),
                    $api.getAll('/admin/families'),
                    // A citizen-verification queue's 'Not Registered' bucket needs
                    // every resident, so this joins onto individuals below rather
                    // than driving its own separate list.
                    $api.getAll('/admin/citizens').catch(() => ({ data: [] })),
                    // Data Quality tab. Caught separately so a permission gap on
                    // this one queue doesn't take down the whole Registry.
                    $api.getAll('/admin/data-quality').catch(() => ({ data: [] })),
                    // Encoder list for the Assign action -- not everyone who can
                    // see the queue can see Personnel, so this degrades to an
                    // empty picker rather than failing the page.
                    $api.getAll('/admin/users').catch(() => ({ data: [] })),
                    // Profiling Coverage tab. `meta.municipal` is only present for
                    // a municipality-scoped actor -- null for a barangay-scoped one.
                    $api.get('/admin/coverage').catch(() => ({ data: [], meta: null })),
                ]);
                const citizensByRef = {};
                (citizens.data || []).forEach(c => { if (c.resident_profile?.ref) citizensByRef[c.resident_profile.ref] = c; });
                this.individuals = (residents.data || []).map(i => ({ ...i, citizen_account: citizensByRef[i.ref] || null }));
                this.households = households.data || [];
                this.families = families.data || [];
                this.citizens = citizens.data || [];
                this.qualityIssues = issues.data || [];
                this.encoders = encoders.data || [];
                this.barangayCoverage = coverage.data || [];
                this.coverageMunicipal = coverage.meta?.municipal || null;
                this.registryTruncated = [residents, households, families].some(r => r.meta?.truncated);
                this.registryTotals = {
                    residents: residents.meta?.total ?? this.individuals.length,
                    households: households.meta?.total ?? this.households.length,
                    families: families.meta?.total ?? this.families.length,
                };
            } catch { this.failed = true; } finally { this.loading = false; }
        },

        search:             '',
        barangayFilter:     '',
        verificationFilter: '',
        statusFilter:       '',
        tagFilter:          '',
        dqTypeFilter:       '',
        dqStatusFilter:     '',
        showIndividualDetail: false,
        showAddIndividual:    false,
        showFamilyDetail:     false,
        showAddFamily:        false,
        showHouseholdDetail:  false,
        showAddHousehold:     false,
        showLifecycle:        false,
        showMerge:            false,
        showDQDetail:         false,
        showAddMenu:          false,
        selectedIndividual:   null,
        selectedFamily:       null,
        selectedHousehold:    null,
        selectedDQIssue:      null,
        lifecycleAction:      '',
        addIndividualStep: 1,
        addFamilyStep:     1,
        addHouseholdStep:  1,
        individualTab: 'Overview',
        familyTab:     'overview',
        householdTab:  'overview',

        get isMunicipalScope() {
            const s = this.$store.session.account?.scope;
            return !s || s === 'Municipal';
        },
        get activeStats() {
            return {
                residents: this.individuals.length,
                households: this.households.length,
                families: this.families.length,
                verified: this.individuals.filter(i => i.verification === 'Verified').length,
                pending: this.individuals.filter(i => i.verification === 'For Validation').length,
                issues: this.qualityIssues.length,
            };
        },

        cvType: 'Individual',
        cvSearch: '',
        cvStatusFilter: '',
        cvBarangayFilter: '',
        cvTagFilter: '',
        rejectAccountRemarks: '',
        processingCitizen: false,
        // Every resident in scope belongs here, not just the ones who signed
        // up on the Citizen Portal — Individual/Family/Household already show
        // everyone, so Citizen Verification's Individual list matches that
        // same total instead of silently dropping non-portal residents.
        // Residents with no citizen_account render as Not Registered further
        // down, since there's nothing to approve/reject for them yet.
        get citizenAccountList() {
            return this.individuals.filter(i => this.$store.session.inScope(i.barangay));
        },
        get pendingCitizenVerificationCount() {
            const indiv = this.citizenAccountList.filter(i => i.citizen_account && ['Pending Review', 'Under Verification'].includes(i.citizen_account.status)).length;
            const fam = this.cvFamilyList.filter(f => f.verification !== 'Verified').length;
            const hh = this.cvHouseholdList.filter(h => h.verification !== 'Verified').length;
            return indiv + fam + hh;
        },
        get cvCounts() {
            const list = this.citizenAccountList;
            return {
                pending: list.filter(i => i.citizen_account?.status === 'Pending Review').length,
                underVerification: list.filter(i => i.citizen_account?.status === 'Under Verification').length,
                verified: list.filter(i => i.citizen_account?.status === 'Approved').length,
                rejected: list.filter(i => i.citizen_account?.status === 'Rejected').length,
                notRegistered: list.filter(i => !i.citizen_account).length,
            };
        },
        get filteredCitizenAccounts() {
            let list = this.citizenAccountList.filter(i => {
                if (this.cvStatusFilter === 'Not Registered') {
                    if (i.citizen_account) return false;
                } else if (this.cvStatusFilter && i.citizen_account?.status !== this.cvStatusFilter) {
                    return false;
                }
                if (this.cvBarangayFilter && i.barangay !== this.cvBarangayFilter) return false;
                if (this.cvTagFilter && !(i.tags || []).includes(this.cvTagFilter)) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (i.name || '').toLowerCase().includes(q) || (i.ref || '').toLowerCase().includes(q) || (i.barangay || '').toLowerCase().includes(q) || (i.citizen_account?.email || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = i => {
                if (!i.citizen_account) return 2;
                return ['Pending Review', 'Under Verification'].includes(i.citizen_account.status) ? 0 : 1;
            };
            return [...list].sort((a, b) => priority(a) - priority(b));
        },
        async verifyCitizenAccount(i) {
            if (this.processingCitizen || !i.citizen_account) return;
            this.processingCitizen = true;
            try {
                const res = await $api.post('/admin/citizens/' + i.citizen_account.account_no + '/verify', {});
                i.citizen_account = { ...i.citizen_account, ...res.data };
                this.$dispatch('toast', { message: i.name + '\'s citizen account was verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this account.', variant: 'error' });
            } finally { this.processingCitizen = false; }
        },
        async rejectCitizenAccount(i) {
            if (this.processingCitizen || !i.citizen_account || this.rejectAccountRemarks.trim().length < 5) return;
            this.processingCitizen = true;
            try {
                const res = await $api.post('/admin/citizens/' + i.citizen_account.account_no + '/reject', { remarks: this.rejectAccountRemarks.trim() });
                i.citizen_account = { ...i.citizen_account, ...res.data };
                this.rejectAccountRemarks = '';
                this.$dispatch('toast', { message: i.name + '\'s citizen account was rejected.', variant: 'error' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not reject this account.', variant: 'error' });
            } finally { this.processingCitizen = false; }
        },
        get cvFamilyList() {
            return this.families.filter(f => this.$store.session.inScope(f.barangay));
        },
        get cvHouseholdList() {
            return this.households.filter(h => this.$store.session.inScope(h.barangay));
        },
        get cvFamilyCounts() {
            const list = this.cvFamilyList;
            return {
                unverified: list.filter(f => f.verification === 'Unverified').length,
                partial: list.filter(f => ['Partially Verified', 'For Validation', 'Missing Documents'].includes(f.verification)).length,
                verified: list.filter(f => f.verification === 'Verified').length,
                rejected: list.filter(f => f.verification === 'Rejected').length,
            };
        },
        get cvHouseholdCounts() {
            const list = this.cvHouseholdList;
            return {
                unverified: list.filter(h => h.verification === 'Unverified').length,
                partial: list.filter(h => ['Partially Verified', 'For Validation', 'Missing Documents'].includes(h.verification)).length,
                verified: list.filter(h => h.verification === 'Verified').length,
                rejected: list.filter(h => h.verification === 'Rejected').length,
            };
        },
        get cvVerificationOptions() {
            return ['Unverified', 'Partially Verified', 'For Validation', 'Missing Documents', 'Verified', 'Rejected'];
        },
        get filteredCvFamilies() {
            let list = this.cvFamilyList.filter(f => {
                if (this.cvStatusFilter && f.verification !== this.cvStatusFilter) return false;
                if (this.cvBarangayFilter && f.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (f.label || '').toLowerCase().includes(q) || (f.ref || '').toLowerCase().includes(q) || (f.barangay || '').toLowerCase().includes(q) || (f.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        get filteredCvHouseholds() {
            let list = this.cvHouseholdList.filter(h => {
                if (this.cvStatusFilter && h.verification !== this.cvStatusFilter) return false;
                if (this.cvBarangayFilter && h.barangay !== this.cvBarangayFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (h.label || '').toLowerCase().includes(q) || (h.ref || '').toLowerCase().includes(q) || (h.barangay || '').toLowerCase().includes(q) || (h.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        async verifyFamily(f) {
            try {
                await $api.post('/admin/families/' + f.ref + '/verify', { level: 'Verified' });
                f.verification = 'Verified';
                this.$dispatch('toast', { message: f.label + ' has been marked as verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this family.', variant: 'error' });
            }
        },
        async verifyHousehold(h) {
            try {
                await $api.post('/admin/households/' + h.ref + '/verify', { level: 'Verified' });
                h.verification = 'Verified';
                this.$dispatch('toast', { message: h.label + ' has been marked as verified.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not verify this household.', variant: 'error' });
            }
        },

        get filteredIndividuals() {
            return this.individuals.filter(r => {
                if (!this.$store.session.inScope(r.barangay)) return false;
                if (this.barangayFilter && r.barangay !== this.barangayFilter) return false;
                if (this.verificationFilter && r.verification !== this.verificationFilter) return false;
                if (this.tagFilter && !(r.tags||[]).includes(this.tagFilter)) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (r.name||'').toLowerCase().includes(q)||(r.ref||'').toLowerCase().includes(q)||(r.barangay||'').toLowerCase().includes(q)||(r.occupation||'').toLowerCase().includes(q);
                }
                return true;
            });
        },
        get filteredFamilies() {
            return this.families.filter(f => {
                if (!this.$store.session.inScope(f.barangay)) return false;
                if (this.barangayFilter && f.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (f.label||'').toLowerCase().includes(q)||(f.head_name||'').toLowerCase().includes(q)||(f.ref||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredHouseholds() {
            return this.households.filter(h => {
                if (!this.$store.session.inScope(h.barangay)) return false;
                if (this.barangayFilter && h.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (h.label||'').toLowerCase().includes(q)||(h.head_name||'').toLowerCase().includes(q)||(h.ref||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredQualityIssues() {
            return this.qualityIssues.filter(i => {
                if (!this.$store.session.inScope(i.barangay)) return false;
                if (this.dqTypeFilter && i.type !== this.dqTypeFilter) return false;
                if (this.dqStatusFilter && i.status !== this.dqStatusFilter) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (this.dqRecordName(i.record)||'').toLowerCase().includes(q) || (i.description||'').toLowerCase().includes(q) || (i.barangay||'').toLowerCase().includes(q) || (i.ref||'').toLowerCase().includes(q);
                }
                return true;
            });
        },
        // The data-quality index only returns a `record` ref (e.g. RES-2026-0042),
        // never a name -- resolved here against what Registry already loaded
        // instead of a second fetch per row.
        dqRecordName(ref) {
            if (!ref) return null;
            const person = this.individuals.find(x => x.ref === ref);
            if (person) return person.name;
            const hh = this.households.find(x => x.ref === ref);
            if (hh) return hh.label || hh.ref;
            return ref;
        },
        dqRecord(ref) {
            return this.individuals.find(x => x.ref === ref) || null;
        },
        async runDataQualityDetection() {
            if (this.dqScanning) return;
            this.dqScanning = true;
            try {
                const res = await $api.post('/admin/data-quality/detect', {});
                const { created = 0, skipped = 0 } = res.data || {};
                this.$dispatch('toast', {
                    message: created
                        ? `Scan complete: ${created} new issue(s) found, ${skipped} already open.`
                        : `Scan complete: no new issues. ${skipped} already open.`,
                    variant: created ? 'warning' : 'info',
                });
                if (created) {
                    const refreshed = await $api.getAll('/admin/data-quality');
                    this.qualityIssues = refreshed.data || [];
                }
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not run the detection scan.', variant: 'error' });
            } finally { this.dqScanning = false; }
        },
        async assignDQIssue(issue, encoderId) {
            if (!issue || !encoderId) return;
            try {
                const res = await $api.post('/admin/data-quality/' + issue.ref + '/assign', { encoder_id: encoderId });
                Object.assign(issue, res.data);
                this.$dispatch('toast', { message: 'Issue assigned.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not assign this issue.', variant: 'error' });
            }
        },
        async resolveDQIssue(issue, resolution) {
            if (!issue || !resolution || resolution.trim().length < 3) return;
            try {
                const res = await $api.post('/admin/data-quality/' + issue.ref + '/resolve', { resolution: resolution.trim() });
                Object.assign(issue, res.data);
                this.$dispatch('toast', { message: 'Issue marked as resolved.', variant: 'success' });
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not resolve this issue.', variant: 'error' });
            }
        },
        // Merging IS the resolution of the issue that raised it -- the backend
        // resolves issueRef itself when given, this just mirrors that locally.
        get barangayStatusBreakdown() {
            const colors = { 'Complete': '#10b981', 'In Progress': '#3b82f6', 'Needs Follow-up': '#f59e0b', 'Not Started (Re-visit Required)': '#f43f5e' };
            const counts = {};
            this.barangayCoverage.forEach(b => { const s = b.status || 'Not Started (Re-visit Required)'; counts[s] = (counts[s] || 0) + 1; });
            return Object.keys(colors).filter(s => counts[s]).map(s => ({ label: s, value: counts[s], color: colors[s] }));
        },
        goToBarangayResidents(name) {
            this.tab = 'registry'; this.view = 'individuals'; this.clearFilters(); this.barangayFilter = name;
        },
        async confirmDQMerge(keepRef, mergeRef, issueRef, note) {
            const res = await $api.post('/admin/merges', { keep: keepRef, merge: mergeRef, issue_ref: issueRef || null, note: note || null });
            this.individuals = this.individuals.filter(i => i.ref !== mergeRef);
            if (issueRef) {
                const issue = this.qualityIssues.find(i => i.ref === issueRef);
                if (issue) issue.status = 'Resolved';
            }
            return res.data;
        },

        async openIndividual(r) {
            this.selectedIndividual = r;
            this.individualTab = 'Overview';
            this.rejectAccountRemarks = '';
            this.showIndividualDetail = true;
            this.$nextTick(() => window.renderIcons?.());
            // The list row is intentionally lean (no tags/household/family/
            // sensitive fields) -- the detail endpoint has the rest.
            try {
                const res = await $api.get('/admin/residents/' + r.ref);
                const merged = { ...res.data, citizen_account: r.citizen_account };
                this.selectedIndividual = merged;
                const idx = this.individuals.findIndex(x => x.ref === r.ref);
                if (idx !== -1) this.individuals[idx] = { ...this.individuals[idx], ...merged };
            } catch { /* keep the list row's fields if the detail fetch fails */ }
        },
        // Real cross-module navigation, not just a sub-tab switch: leaves the
        // Constituents page entirely, lands on this resident's requests in the
        // Dokyu/Tulong module, and remembers who to reopen when they come back.
        goToDokyu(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ ref: r.ref, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/document-requests?' + q;
        },
        goToTulong(r, ref) {
            sessionStorage.setItem('returnToProfile', JSON.stringify({ ref: r.ref, name: r.name }));
            const q = ref ? 'ref=' + encodeURIComponent(ref) : 'citizen=' + encodeURIComponent(r.name);
            window.location.href = '/admin/assistance-requests?' + q;
        },
        openFamily(f)     { this.selectedFamily=f;     this.familyTab='overview';     this.showFamilyDetail=true;     this.$nextTick(()=>window.renderIcons?.()); },
        openHousehold(h)  { this.selectedHousehold=h;  this.householdTab='overview';  this.showHouseholdDetail=true;  this.$nextTick(()=>window.renderIcons?.()); },
        openLifecycle(r, action) { this.selectedIndividual=r; this.lifecycleAction=action; this.showLifecycle=true; this.$nextTick(()=>window.renderIcons?.()); },
        openDQIssue(issue)       { this.selectedDQIssue=issue; this.showDQDetail=true; this.$nextTick(()=>window.renderIcons?.()); },
        verificationBadge(v) {
            const m={'Verified':'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200','Partially Verified':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','For Validation':'text-indigo-700 bg-indigo-50 ring-1 ring-indigo-200','Missing Documents':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Unverified':'text-slate-500 bg-slate-100 ring-1 ring-slate-200','Rejected':'text-rose-700 bg-rose-50 ring-1 ring-rose-200'};
            return { class: m[v]||m['Unverified'], label: v || 'Unverified' };
        },
        statusBadge(s) {
            const m={'Active':'text-emerald-700 bg-emerald-50','New Resident':'text-brand-700 bg-brand-50','Transferred':'text-indigo-700 bg-indigo-50','Temporarily Away':'text-amber-700 bg-amber-50','Overseas':'text-teal-700 bg-teal-50','Deceased':'text-slate-500 bg-slate-100','Archived':'text-slate-400 bg-slate-50','Under Review':'text-purple-700 bg-purple-50'};
            return m[s]||m['Active'];
        },
        severityBadge(s) {
            const m={'Critical':'text-rose-700 bg-rose-50 ring-1 ring-rose-200','High':'text-orange-700 bg-orange-50 ring-1 ring-orange-200','Medium':'text-amber-700 bg-amber-50 ring-1 ring-amber-200','Low':'text-slate-600 bg-slate-100 ring-1 ring-slate-200'};
            return m[s]||m['Low'];
        },
        exportCSV(rows, filename) {
            if (!rows.length) { this.$dispatch('toast',{message:'No records to export.',variant:'warning'}); return; }
            const keys=Object.keys(rows[0]).filter(k=>!['services','emergency','member_ids','family_ids','citizen_account','household','family','tags'].includes(k));
            const csv=[keys.join(','),...rows.map(r=>keys.map(k=>JSON.stringify(r[k]??'')).join(','))].join('\n');
            const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(new Blob([csv],{type:'text/csv'})),download:filename});
            a.click(); this.$dispatch('toast',{message:'Exported successfully.',variant:'success'});
        },
        can(sub,perm) { return this.$store.session.canPerm?this.$store.session.canPerm(sub,perm):true; },
        clearFilters() { this.search='';this.barangayFilter='';this.verificationFilter='';this.statusFilter='';this.tagFilter='';this.dqTypeFilter='';this.dqStatusFilter='';this.cvSearch='';this.cvStatusFilter='';this.cvBarangayFilter='';this.cvTagFilter=''; },
    }"
    x-init="init()"
    class="animate-fade-up"
>

    <div x-show="!$store.session.can('constituents')" x-cloak>
        <x-admin.access-restricted module="Constituent Management" icon="users" />
    </div>

    <div x-show="$store.session.can('constituents')" x-cloak>

        {{-- Scope banner --}}
        <div x-show="$store.session.account && $store.session.account.scope !== 'Municipal'" x-cloak
             class="flex items-center gap-2.5 rounded-xl bg-purple-50 border border-purple-100 px-3.5 py-2.5 mb-4">
            <i data-lucide="map-pin" class="w-4 h-4 text-purple-500 shrink-0"></i>
            <p class="text-xs text-purple-700">Barangay-scoped access — data outside <strong x-text="'Brgy. '+$store.session.account?.scope"></strong> is hidden.</p>
        </div>

        {{-- Page header --}}
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-bold text-navy-900">Constituent Registry</h2>
                <p class="text-xs text-slate-400 mt-0.5">Municipality of Esperanza · 20 barangays · <span x-text="isMunicipalScope?'Municipality-wide view':'Brgy. '+$store.session.account?.scope+' view'"></span></p>
            </div>
            <div class="flex items-center gap-2 relative" x-show="can('const_resident','create') || can('const_family','create') || can('const_household','create')">
                <button @click="showAddMenu=!showAddMenu" @click.outside="showAddMenu=false"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-700 shadow-sm shadow-brand-200 transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>Add Record
                    <i data-lucide="chevron-down" class="w-3 h-3 opacity-70 transition-transform" :class="showAddMenu&&'rotate-180'"></i>
                </button>
                <div x-show="showAddMenu" x-transition.origin.top.right x-cloak
                     class="absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl border border-slate-200 shadow-lg z-30 overflow-hidden">
                    <button x-show="can('const_resident','create')" @click="addIndividualStep=1;showAddIndividual=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-brand-500"></i>New Resident
                    </button>
                    <button x-show="can('const_family','create')" @click="addFamilyStep=1;showAddFamily=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-purple-500"></i>New Family
                    </button>
                    <button x-show="can('const_household','create')" @click="addHouseholdStep=1;showAddHousehold=true;showAddMenu=false;$nextTick(()=>window.renderIcons?.())"
                            class="w-full text-left px-4 py-2.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i data-lucide="house" class="w-3.5 h-3.5 text-emerald-500"></i>New Household
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters()" class="cursor-pointer" label="Registered Residents" valueBind="activeStats.residents"  icon="users"          color="brand"  :delay="0"   />
            <x-ui.stat-card @click="tab='registry';view='households';clearFilters()" class="cursor-pointer" label="Active Households"    valueBind="activeStats.households" icon="house"           color="green"  :delay="40"  />
            <x-ui.stat-card @click="tab='registry';view='families';clearFilters()" class="cursor-pointer" label="Recorded Families"    valueBind="activeStats.families"   icon="users-round"    color="purple" :delay="80"  />
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters();verificationFilter='Verified'" class="cursor-pointer" label="ID Verified"          valueBind="activeStats.verified"   icon="badge-check"    color="gold"   :delay="120" />
            <x-ui.stat-card @click="tab='registry';view='individuals';clearFilters();verificationFilter='For Validation'" class="cursor-pointer" label="Pending Validation"   valueBind="activeStats.pending"    icon="clock"          color="orange" :delay="160" />
            <x-ui.stat-card @click="tab='data-quality';clearFilters()" class="cursor-pointer" label="Data Quality Issues"  valueBind="activeStats.issues"     icon="triangle-alert" color="red"    :delay="200" sublabel="Needs attention" />
        </div>

        {{-- Primary tab bar --}}
        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-5">
            <button @click="tab='registry';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                    :class="tab==='registry'     ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>Registry
            </button>
            <button @click="tab='profiling';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                    :class="tab==='profiling'    ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="user-round-search" class="w-3.5 h-3.5"></i>Profiling Coverage
            </button>
            <button @click="tab='data-quality';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 relative"
                    :class="tab==='data-quality' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="triangle-alert" class="w-3.5 h-3.5"></i>Data Quality
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                      :class="tab==='data-quality' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-600'" x-text="qualityIssues.length"></span>
            </button>
            <button @click="tab='citizen-verification';clearFilters()"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 relative"
                    :class="tab==='citizen-verification' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>Citizen Verification
                <span
                    x-show="pendingCitizenVerificationCount > 0"
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                    :class="tab==='citizen-verification' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-600'"
                    x-text="pendingCitizenVerificationCount"
                ></span>
            </button>
        </div>

        <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-40 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the constituent registry.'" retryExpr="loadRegistry()" :retryable="true" /></div>
        <div x-show="!loading && !failed && registryTruncated" x-cloak role="status"
             class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span>
                Showing the first <strong x-text="individuals.length.toLocaleString()"></strong> of
                <strong x-text="registryTotals.residents.toLocaleString()"></strong> residents
                (<span x-text="households.length.toLocaleString() + ' of ' + registryTotals.households.toLocaleString()"></span> households).
                Counts and filters on this page cover the records shown.
            </span>
        </div>

        <div x-show="!loading && !failed">
        {{-- ── Registry tab ────────────────────────────────────────── --}}
        <div x-show="tab === 'registry'">
            {{-- Unified toolbar: view switcher on top, search/filters/actions below --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    <button @click="view='individuals';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='individuals' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>Individuals
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredIndividuals.length"></span>
                    </button>
                    <button @click="view='families';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='families'    ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>Families
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredFamilies.length"></span>
                    </button>
                    <button @click="view='households';clearFilters()"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap"
                            :class="view==='households'  ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="house" class="w-3.5 h-3.5"></i>Households
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600" x-text="filteredHouseholds.length"></span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search" x-model="search" type="text"
                               :placeholder="view==='individuals'?'Search residents...':view==='families'?'Search families...':'Search households...'"
                               class="w-52 sm:w-64 pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                    </div>
                    <select aria-label="Barangay filter" x-show="isMunicipalScope" x-model="barangayFilter"
                            class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                        <option value="">All Barangays</option>
                        @foreach($barangayOptions as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                    <span x-show="!isMunicipalScope" x-cloak
                          class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2">
                        <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
                    </span>
                    <div x-show="view==='individuals'" class="flex items-center gap-2">
                        <select aria-label="Verification filter" x-model="verificationFilter"
                                class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            <option value="">All Verification</option>
                            <option>Verified</option>
                            <option>Partially Verified</option>
                            <option>For Validation</option>
                            <option>Unverified</option>
                        </select>
                        <select aria-label="Tag filter" x-model="tagFilter"
                                class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            <option value="">All Tags</option>
                            <option value="senior">Senior Citizen</option>
                            <option value="osca">OSCA Member</option>
                            <option value="pwd">PWD</option>
                            <option value="4ps">4Ps Beneficiary</option>
                            <option value="solo_parent">Solo Parent</option>
                            <option value="youth">Youth</option>
                            <option value="child">Child</option>
                            <option value="infant">Infant</option>
                            <option value="osy">Out-of-School Youth</option>
                            <option value="farmer">Farmer</option>
                            <option value="high_risk">High Risk</option>
                        </select>
                    </div>
                    <button x-show="search||barangayFilter||verificationFilter||tagFilter" @click="clearFilters()"
                            class="text-xs text-slate-500 hover:text-rose-600 flex items-center gap-1 transition-colors">
                        <i data-lucide="circle-x" class="w-3.5 h-3.5"></i>Clear
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="exportCSV(view==='individuals'?filteredIndividuals:view==='families'?filteredFamilies:filteredHouseholds,view+'-export.csv')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>Export CSV
                    </button>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-white hover:border-slate-300 transition-all">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>Print
                    </button>
                </div>
                </div>
            </div>

            <div x-show="view==='individuals'">@include('admin.constituents.view-individuals')</div>
            <div x-show="view==='families'"    x-cloak>@include('admin.constituents.view-families')</div>
            <div x-show="view==='households'"  x-cloak>@include('admin.constituents.view-households')</div>
        </div>

        {{-- ── Profiling Coverage tab ──────────────────────────────── --}}
        <div x-show="tab === 'profiling'" x-cloak>
            @include('admin.constituents.tab-profiling')
        </div>

        {{-- ── Citizen Verification tab ────────────────────────────── --}}
        <div x-show="tab === 'citizen-verification'" x-cloak>
            @include('admin.constituents.tab-citizen-verification')
        </div>

        {{-- ── Data Quality tab ────────────────────────────────────── --}}
        <div x-show="tab === 'data-quality'" x-cloak>
            @include('admin.constituents.tab-data-quality')
        </div>

        </div>
    </div>

    {{-- ── Modals ──────────────────────────────────────────────────── --}}
    <div x-show="showIndividualDetail" x-cloak>@include('admin.constituents.modal-individual-detail')</div>
    <div x-show="showAddIndividual"    x-cloak>@include('admin.constituents.modal-add-individual')</div>
    <div x-show="showFamilyDetail"     x-cloak>@include('admin.constituents.modal-family-detail')</div>
    <div x-show="showAddFamily"        x-cloak>@include('admin.constituents.modal-add-family')</div>
    <div x-show="showHouseholdDetail"  x-cloak>@include('admin.constituents.modal-household-detail')</div>
    <div x-show="showAddHousehold"     x-cloak>@include('admin.constituents.modal-add-household')</div>
    <div x-show="showLifecycle"        x-cloak>@include('admin.constituents.modal-lifecycle')</div>
    <div x-show="showMerge"            x-cloak>@include('admin.constituents.modal-merge')</div>
    <div x-show="showDQDetail"         x-cloak>@include('admin.constituents.modal-dq-detail')</div>

</div>
</x-layouts.admin>
