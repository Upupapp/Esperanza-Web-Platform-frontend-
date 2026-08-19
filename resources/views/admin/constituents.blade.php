@php
    $data            = config('esperanza_constituents');
    $citizenAccounts = collect(config('esperanza_citizens')['accounts'])->keyBy('id');
    $individuals     = collect($data['individuals'])->map(function ($ind) use ($citizenAccounts) {
        if (!empty($ind['citizen_account_id']) && $citizenAccounts->has($ind['citizen_account_id'])) {
            $acct = $citizenAccounts->get($ind['citizen_account_id']);
            // Access tier mirrors Esperanza-Mobile's 3-tier model: only an
            // 'Approved' status grants Verified (full Dokyu/Tulong access);
            // every other status (Pending Review, Under Verification,
            // Rejected, Draft) is Unverified.
            $ind['citizen_account'] = [
                'id'                   => $acct['id'],
                'email'                => $acct['email'],
                'mobile'               => $acct['mobile'],
                'status'               => $acct['status'],
                'access_level'         => $acct['status'] === 'Approved' ? 'Verified' : 'Unverified',
                'resident_since'       => $acct['residentSince'],
                'registered_at'        => $acct['registeredAt'],
                'id_uploaded'          => $acct['idUploaded'],
                'face_verified'        => $acct['faceVerified'],
                'terms_accepted'       => $acct['termsAccepted'],
                'profile_completeness' => $acct['profileCompleteness'],
            ];
        }
        return $ind;
    })->all();
    $families        = $data['families'];
    $households      = $data['households'];
    $barangayCoverage= $data['barangayCoverage'];
    $qualityIssues   = $data['dataQualityIssues'];
    $municipalStats  = $data['municipalStats'];
    $barangayStats   = $data['barangayStats'];
    $barangayOptions = config('esperanza.barangays');
    $tab  = $tab  ?? 'registry';
    $view = $view ?? 'individuals';
@endphp

<x-layouts.admin title="Constituents" subtitle="LGU constituent registry — residents, families, households." active="constituents">
<div
    x-data="{
        tab:  '{{ $tab }}',
        view: '{{ $view }}',
        individuals:    @js($individuals),
        families:       @js($families),
        households:     @js($households),
        qualityIssues:  @js($qualityIssues),
        municipalStats: @js($municipalStats),
        barangayStats:  @js($barangayStats),
        search:             '',
        barangayFilter:     '',
        verificationFilter: '',
        statusFilter:       '',
        tagFilter:          '',
        dqTypeFilter:       '',
        dqStatusFilter:     '',
        sortField: 'name',
        sortDir:   'asc',
        showIndividualDetail: false,
        showAddIndividual:    false,
        showFamilyDetail:     false,
        showAddFamily:        false,
        showHouseholdDetail:  false,
        showAddHousehold:     false,
        showLifecycle:        false,
        showMerge:            false,
        showAdvancedFilters:  false,
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
        get individualTimeline() {
            const r = this.selectedIndividual; if (!r) return [];
            const items = [];
            if (r.created)      items.push({ date: r.created,      label: 'Record Created', desc: 'Resident profile encoded into the system.', icon: 'user-plus',       color: 'blue'   });
            if (r.last_updated) items.push({ date: r.last_updated, label: 'Last Updated',   desc: 'Profile data last modified.',                icon: 'pencil',           color: 'amber'  });
            (r.document_requests   || []).forEach(d => items.push({ date: d.submitted, label: 'Dokyu · ' + d.type,  desc: d.status + ' — ' + d.office,  icon: 'file-text',        color: 'indigo' }));
            (r.assistance_requests || []).forEach(a => items.push({ date: a.submitted, label: 'Tulong · ' + a.type, desc: a.status + ' — ' + a.office,  icon: 'hand-heart',       color: 'rose'   }));
            (r.incident_reports    || []).forEach(i => items.push({ date: i.date,      label: 'Sakuna · ' + i.type, desc: i.status + ' — ' + i.location, icon: 'triangle-alert',  color: 'orange' }));
            return items.sort((a, b) => new Date(b.date) - new Date(a.date));
        },
        get activeStats() {
            const s = this.$store.session.account?.scope;
            return (!s || s === 'Municipal') ? this.municipalStats : (this.barangayStats[s] ?? this.municipalStats);
        },
        get isMunicipalScope() {
            const s = this.$store.session.account?.scope;
            return !s || s === 'Municipal';
        },
        cvType: 'Individual',
        cvSearch: '',
        cvStatusFilter: '',
        rejectAccountRemarks: '',
        get citizenAccountList() {
            return this.individuals.filter(i => this.$store.session.inScope(i.barangay) && i.citizen_account);
        },
        get pendingCitizenVerificationCount() {
            const indiv = this.citizenAccountList.filter(i => ['Pending Review', 'Under Verification'].includes(i.citizen_account.status)).length;
            const fam = this.cvFamilyList.filter(f => f.verification !== 'Verified').length;
            const hh = this.cvHouseholdList.filter(h => h.verification !== 'Verified').length;
            return indiv + fam + hh;
        },
        get cvCounts() {
            const list = this.citizenAccountList;
            return {
                pending: list.filter(i => i.citizen_account.status === 'Pending Review').length,
                underVerification: list.filter(i => i.citizen_account.status === 'Under Verification').length,
                approved: list.filter(i => i.citizen_account.status === 'Approved').length,
                rejected: list.filter(i => i.citizen_account.status === 'Rejected').length,
            };
        },
        get filteredCitizenAccounts() {
            let list = this.citizenAccountList.filter(i => {
                if (this.cvStatusFilter && i.citizen_account.status !== this.cvStatusFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (i.name || '').toLowerCase().includes(q) || (i.id || '').toLowerCase().includes(q) || (i.barangay || '').toLowerCase().includes(q) || (i.citizen_account.email || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = s => ['Pending Review', 'Under Verification'].includes(s) ? 0 : 1;
            return [...list].sort((a, b) => priority(a.citizen_account.status) - priority(b.citizen_account.status));
        },
        verifyCitizenAccount(i) {
            i.citizen_account.status = 'Approved';
            i.citizen_account.access_level = 'Verified';
            delete i.citizen_account.reject_remarks;
            this.$dispatch('toast', { message: i.name + '\'s citizen account was approved.', variant: 'success' });
        },
        rejectCitizenAccount(i) {
            if (!this.rejectAccountRemarks.trim()) return;
            i.citizen_account.status = 'Rejected';
            i.citizen_account.access_level = 'Unverified';
            i.citizen_account.reject_remarks = this.rejectAccountRemarks.trim();
            this.rejectAccountRemarks = '';
            this.$dispatch('toast', { message: i.name + '\'s citizen account was rejected.', variant: 'error' });
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
                partial: list.filter(f => f.verification === 'Partially Verified' || f.verification === 'For Validation' || f.verification === 'Missing Documents').length,
                verified: list.filter(f => f.verification === 'Verified').length,
                rejected: list.filter(f => f.verification === 'Rejected').length,
            };
        },
        get cvHouseholdCounts() {
            const list = this.cvHouseholdList;
            return {
                unverified: list.filter(h => h.verification === 'Unverified').length,
                partial: list.filter(h => h.verification === 'Partially Verified' || h.verification === 'For Validation' || h.verification === 'Missing Documents').length,
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
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (f.label || '').toLowerCase().includes(q) || (f.id || '').toLowerCase().includes(q) || (f.barangay || '').toLowerCase().includes(q) || (f.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        get filteredCvHouseholds() {
            let list = this.cvHouseholdList.filter(h => {
                if (this.cvStatusFilter && h.verification !== this.cvStatusFilter) return false;
                if (this.cvSearch) {
                    const q = this.cvSearch.toLowerCase();
                    return (h.label || '').toLowerCase().includes(q) || (h.id || '').toLowerCase().includes(q) || (h.barangay || '').toLowerCase().includes(q) || (h.head_name || '').toLowerCase().includes(q);
                }
                return true;
            });
            const priority = v => v === 'Verified' ? 1 : 0;
            return [...list].sort((a, b) => priority(a.verification) - priority(b.verification));
        },
        verifyFamily(f) {
            f.verification = 'Verified';
            this.$dispatch('toast', { message: f.label + ' has been marked as verified.', variant: 'success' });
        },
        verifyHousehold(h) {
            h.verification = 'Verified';
            this.$dispatch('toast', { message: h.label + ' has been marked as verified.', variant: 'success' });
        },
        get filteredIndividuals() {
            let list = this.individuals.filter(r => {
                if (!this.$store.session.inScope(r.barangay)) return false;
                if (this.barangayFilter && r.barangay !== this.barangayFilter) return false;
                if (this.verificationFilter && r.verification !== this.verificationFilter) return false;
                if (this.tagFilter && !(r.tags||[]).includes(this.tagFilter)) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (r.name||'').toLowerCase().includes(q)||(r.id||'').toLowerCase().includes(q)||(r.barangay||'').toLowerCase().includes(q)||(r.occupation||'').toLowerCase().includes(q)||(r.contact||'').includes(q);
                }
                return true;
            });
            if (this.sortField) {
                list = [...list].sort((a,b) => this.sortDir==='asc' ? String(a[this.sortField]??'').localeCompare(String(b[this.sortField]??'')) : String(b[this.sortField]??'').localeCompare(String(a[this.sortField]??'')));
            }
            return list;
        },
        get filteredFamilies() {
            return this.families.filter(f => {
                if (!this.$store.session.inScope(f.barangay)) return false;
                if (this.barangayFilter && f.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (f.label||'').toLowerCase().includes(q)||(f.head_name||'').toLowerCase().includes(q)||(f.id||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredHouseholds() {
            return this.households.filter(h => {
                if (!this.$store.session.inScope(h.barangay)) return false;
                if (this.barangayFilter && h.barangay !== this.barangayFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (h.label||'').toLowerCase().includes(q)||(h.head_name||'').toLowerCase().includes(q)||(h.id||'').toLowerCase().includes(q); }
                return true;
            });
        },
        get filteredQualityIssues() {
            return this.qualityIssues.filter(i => {
                if (!this.$store.session.inScope(i.barangay)) return false;
                if (this.dqTypeFilter && i.type !== this.dqTypeFilter) return false;
                if (this.dqStatusFilter && i.status !== this.dqStatusFilter) return false;
                if (this.search) { const q=this.search.toLowerCase(); return (i.record_name||'').toLowerCase().includes(q)||(i.description||'').toLowerCase().includes(q)||(i.barangay||'').toLowerCase().includes(q); }
                return true;
            });
        },
        openIndividual(r) { this.selectedIndividual=r; this.individualTab='Overview'; this.rejectAccountRemarks=''; this.showIndividualDetail=true; this.$nextTick(()=>window.renderIcons?.()); },
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
        completionColor(pct) { return pct>=90?'bg-emerald-500':pct>=70?'bg-amber-500':'bg-rose-500'; },
        completionTextColor(pct) { return pct>=90?'text-emerald-700':pct>=70?'text-amber-700':'text-rose-700'; },
        onboardingStatus(acct) {
            if (!acct) return 'Draft';
            const wizardDone = !!(acct.terms_accepted && acct.id_uploaded && acct.face_verified);
            if (!wizardDone) return 'Draft';
            if (acct.status === 'Approved') return 'Completed';
            if (acct.status === 'Rejected') return 'Rejected';
            return 'Submitted';
        },
        onboardingSteps(acct) {
            if (!acct) return [];
            const wizardDone = !!(acct.terms_accepted && acct.id_uploaded && acct.face_verified);
            const isApproved = acct.status === 'Approved';
            const isRejected = acct.status === 'Rejected';
            return [
                { label: 'Account Created', done: true },
                { label: 'Personal Information Completed', done: true },
                { label: 'Terms & Conditions Accepted', done: !!acct.terms_accepted },
                { label: 'Valid ID Submitted', done: !!acct.id_uploaded },
                { label: 'Face Verification Completed', done: !!acct.face_verified },
                { label: 'Information Reviewed', done: wizardDone },
                { label: 'Submitted for Verification', done: wizardDone },
                { label: acct.status || 'Pending Review', done: isApproved, rejected: isRejected, current: wizardDone && !isApproved && !isRejected },
            ];
        },
        exportCSV(rows, filename) {
            if (!rows.length) { this.$dispatch('toast',{message:'No records to export.',variant:'warning'}); return; }
            const keys=Object.keys(rows[0]).filter(k=>!['services','emergency','member_ids','family_ids'].includes(k));
            const csv=[keys.join(','),...rows.map(r=>keys.map(k=>JSON.stringify(r[k]??'')).join(','))].join('\n');
            const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(new Blob([csv],{type:'text/csv'})),download:filename});
            a.click(); this.$dispatch('toast',{message:'Exported successfully.',variant:'success'});
        },
        can(sub,perm) { return this.$store.session.canPerm?this.$store.session.canPerm(sub,perm):true; },
        clearFilters() { this.search='';this.barangayFilter='';this.verificationFilter='';this.statusFilter='';this.tagFilter='';this.dqTypeFilter='';this.dqStatusFilter='';this.cvSearch='';this.cvStatusFilter=''; },
    }"
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
                <h1 class="text-lg font-bold text-navy-900">Constituent Registry</h1>
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
                      :class="tab==='data-quality' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-600'">{{ count($qualityIssues) }}</span>
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
                        <input x-model="search" type="text"
                               :placeholder="view==='individuals'?'Search residents...':view==='families'?'Search families...':'Search households...'"
                               class="w-52 sm:w-64 pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                    </div>
                    <select x-show="isMunicipalScope" x-model="barangayFilter"
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
                        <select x-model="verificationFilter"
                                class="text-xs rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all">
                            <option value="">All Verification</option>
                            <option>Verified</option>
                            <option>Partially Verified</option>
                            <option>For Validation</option>
                            <option>Unverified</option>
                        </select>
                        <select x-model="tagFilter"
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
