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

    // ── Operational Context ─────────────────────────────────────────────────
    $periods = $__fixtures['periods'];

    // ── Barangays ────────────────────────────────────────────────────────────
    $barangays = config('esperanza.barangays');

    // ── Vulnerability profiles ────────────────────────────────────────────────
    $vulnerabilityProfiles = $__fixtures['vulnerabilityProfiles'];

    // ── Incidents ────────────────────────────────────────────────────────────
    $incidents = $__fixtures['incidents'];

    // ── Evacuation Centers ───────────────────────────────────────────────────
    $centers = $__fixtures['centers'];

    // ── Evacuees ─────────────────────────────────────────────────────────────
    $evacuees = $__fixtures['evacuees'];

    // ── Resources ─────────────────────────────────────────────────────────────
    $resources = $__fixtures['resources'];

    // ── Relief Inventory ──────────────────────────────────────────────────────
    $inventory = $__fixtures['inventory'];

    // ── Damage Assessments ───────────────────────────────────────────────────
    $assessments = $__fixtures['assessments'];

    // ── Alerts ────────────────────────────────────────────────────────────────
    $alerts = $__fixtures['alerts'];
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
        tabUrls: @json($tabUrls),
        opStatus: 'Red Alert',
        selectedPeriod: 'SWM-2026-07',
        selectedBarangay: '',
        ready: false,
        returnToProfile: null,
        // Arrived from a resident's profile via Open in Sakuna — land on the
        // Incidents tab pre-filtered to their exact report, and offer a way
        // straight back to the profile that started the trip.
        init() {
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
        selectedIncident: null,
        showIncidentDetail: false,
        showCreateIncident: false,
        showDispatch: false,
        dispatchTarget: null,

        // Vulnerability tab state
        vulSearch: '',
        vulRiskFilter: '',
        vulHazardFilter: '',
        vulReadinessFilter: '',
        selectedBarangayVul: null,
        showVulDetail: false,

        // Centers tab state
        centerSearch: '',
        centerStatusFilter: '',
        selectedCenter: null,
        showCenterDetail: false,
        showCreateCenter: false,
        showOpenCenterConfirm: false,
        showCloseCenterConfirm: false,
        pendingCenterAction: null,

        // Evacuees tab state
        evacueeSearch: '',
        evCenterFilter: '',
        evStatusFilter: '',
        evHealthFilter: '',
        selectedEvacuee: null,
        showEvacueeDetail: false,
        showEvacueeReg: false,
        evacueeRegStep: 1,

        // Resources tab state
        resourceSearch: '',
        resourceCategoryFilter: '',
        resourceStatusFilter: '',
        selectedResource: null,
        showResourceDetail: false,
        showDeployModal: false,
        deployTarget: null,

        // Relief tab state
        reliefSubtab: 'inventory',
        inventorySearch: '',
        inventoryCategoryFilter: '',
        showStockInModal: false,
        showStockOutModal: false,
        selectedInventoryItem: null,
        showRequestModal: false,

        // Damage tab state
        damageSearch: '',
        damageTypeFilter: '',
        damageStatusFilter: '',
        damageBarangayFilter: '',
        selectedAssessment: null,
        showAssessmentDetail: false,
        showCreateAssessment: false,

        // Alerts tab state
        alertSearch: '',
        alertTypeFilter: '',
        alertLevelFilter: '',
        alertStatusFilter: '',
        selectedAlert: null,
        showAlertDetail: false,
        showCreateAlert: false,
        alertComposerStep: 1,

        // Command center state
        showEOCConfirm: false,
        showDecisionModal: false,
        selectedDecision: null,

        // Reports tab
        reportBarangayFilter: '',
        reportPeriodFilter: 'SWM-2026-07',

        // Only this tab's partial exists in the document now, so switching tabs
        // is a navigation. The query string is preserved deliberately: arriving
        // from a resident profile carries ?ref=, and init() re-enters here.
        setTab(tab) {
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
                'Closed': 'bg-slate-100 text-slate-500',
                'Referred': 'bg-pink-100 text-pink-700',
                'Open': 'bg-emerald-100 text-emerald-700',
                'Standby': 'bg-blue-100 text-blue-700',
                'Preparing': 'bg-amber-100 text-amber-700',
                'Near Capacity': 'bg-orange-100 text-orange-700',
                'Full': 'bg-rose-100 text-rose-700',
                'Temporarily Unavailable': 'bg-slate-100 text-slate-500',
                'Under Inspection': 'bg-indigo-100 text-indigo-700',
                'Available': 'bg-emerald-100 text-emerald-700',
                'Deployed': 'bg-orange-100 text-orange-700',
                'Returning': 'bg-teal-100 text-teal-700',
                'Under Maintenance': 'bg-slate-100 text-slate-500',
                'Out of Service': 'bg-rose-100 text-rose-700',
                'Checked In': 'bg-emerald-100 text-emerald-700',
                'Temporarily Out': 'bg-amber-100 text-amber-700',
                'Transferred': 'bg-blue-100 text-blue-700',
                'Returned Home': 'bg-slate-100 text-slate-500',
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
                'Expired': 'bg-slate-100 text-slate-500',
                'Cancelled': 'bg-rose-100 text-rose-700',
                'Archived': 'bg-slate-100 text-slate-500',
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
    }"
    x-init="
        setTimeout(() => { ready = true; $nextTick(() => window.renderIcons?.()); }, 600);
    "
    class="space-y-4"
>
    {{-- ── Operational Status Bar ──────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open" :class="opStatusColor()" class="inline-flex items-center gap-2 text-sm font-medium border rounded-xl px-3 py-1.5 transition-all shadow-card">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                <span x-text="opStatus"></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-60" :class="open && 'rotate-180'" style="transition: transform 0.2s"></i>
            </button>
            <div x-show="open" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" class="absolute left-0 mt-2 w-64 bg-white rounded-2xl shadow-float border border-slate-100 overflow-hidden z-30 py-1.5">
                @foreach(['Normal', 'Monitoring', 'Blue Alert', 'Red Alert', 'Emergency Operations Activated', 'Recovery Operations'] as $s)
                    <button @click="opStatus = '{{ $s }}'; open = false; toast('Operational status set to {{ $s }}.', 'info')" class="w-full text-left px-4 py-2 text-sm transition-colors hover:bg-slate-50" :class="opStatus === '{{ $s }}' ? 'text-brand-600 font-medium bg-brand-50/60' : 'text-slate-600'">{{ $s }}</button>
                @endforeach
            </div>
        </div>

        <div class="relative" x-data="{ open: false, label: 'Southwest Monsoon & Flooding — July 2026' }" @click.outside="open = false">
            <button @click="open = !open" class="inline-flex items-center gap-2 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-card hover:border-slate-300 transition-colors">
                <i data-lucide="radio-tower" class="w-4 h-4 text-slate-400"></i>
                <span x-text="label" class="max-w-48 truncate"></span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
            </button>
            <div x-show="open" x-cloak x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute left-0 mt-2 w-80 bg-white rounded-2xl shadow-float border border-slate-100 z-30 py-1.5">
                @foreach($periods as $p)
                    <button @click="label = '{{ $p['label'] }}'; selectedPeriod = '{{ $p['id'] }}'; open = false" class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 transition-colors {{ $p['active'] ? 'text-brand-600 font-medium' : 'text-slate-600' }}">
                        <div class="flex items-center gap-2">
                            @if($p['active'])<span class="w-1.5 h-1.5 rounded-full bg-brand-500 inline-block"></span>@endif
                            {{ $p['label'] }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <span class="text-xs text-slate-400 flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i>Updated Jul 15, 2026 — 08:26 AM</span>

        <div class="ml-auto flex items-center gap-2">
            <button @click="toast('Situation report exported.', 'success')" class="inline-flex items-center gap-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-card hover:border-slate-300 transition-colors">
                <i data-lucide="download" class="w-4 h-4 text-slate-400"></i> Export SitRep
            </button>
            <button @click="showEOCConfirm = true; $nextTick(() => window.renderIcons?.())" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl px-3 py-1.5 shadow-card transition-colors">
                <i data-lucide="siren" class="w-4 h-4"></i> Activate EOC
            </button>
        </div>
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <nav class="flex border-b border-slate-100 min-w-max">
                @php
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
                @endphp
                @foreach($tabs as $t)
                    <a
                        href="{{ $tabUrls[$t['key']] }}"
                        @if($activeTab === $t['key']) aria-current="page" @endif
                        class="inline-flex items-center gap-2 px-4 py-3.5 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === $t['key'] ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50/60' }}"
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

            {{-- Command Center --}}
            @if($activeTab === 'command-center')
            <div>
                @include('admin.partials.sakuna.tab-command', ['incidents' => $incidents, 'centers' => $centers, 'resources' => $resources])
            </div>
            @endif

            {{-- Vulnerability --}}
            @if($activeTab === 'vulnerability')
            <div>
                @include('admin.partials.sakuna.tab-vulnerability', ['profiles' => $vulnerabilityProfiles, 'barangays' => $barangays])
            </div>
            @endif

            {{-- Incidents --}}
            @if($activeTab === 'incidents')
            <div>
                @include('admin.partials.sakuna.tab-incidents', ['incidents' => $incidents, 'barangays' => $barangays])
            </div>
            @endif

            {{-- Evacuation Centers --}}
            @if($activeTab === 'evacuation-centers')
            <div>
                @include('admin.partials.sakuna.tab-centers', ['centers' => $centers, 'barangays' => $barangays])
            </div>
            @endif

            {{-- Evacuees --}}
            @if($activeTab === 'evacuees')
            <div>
                @include('admin.partials.sakuna.tab-evacuees', ['evacuees' => $evacuees, 'centers' => $centers, 'barangays' => $barangays])
            </div>
            @endif

            {{-- Resources --}}
            @if($activeTab === 'resources')
            <div>
                @include('admin.partials.sakuna.tab-resources', ['resources' => $resources, 'incidents' => $incidents, 'centers' => $centers])
            </div>
            @endif

            {{-- Relief & Logistics --}}
            @if($activeTab === 'relief')
            <div>
                @include('admin.partials.sakuna.tab-relief', ['inventory' => $inventory, 'barangays' => $barangays, 'centers' => $centers])
            </div>
            @endif

            {{-- Damage Assessment --}}
            @if($activeTab === 'damage-assessment')
            <div>
                @include('admin.partials.sakuna.tab-damage', ['assessments' => $assessments, 'barangays' => $barangays, 'incidents' => $incidents])
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
                @include('admin.partials.sakuna.tab-alerts', ['alerts' => $alerts, 'barangays' => $barangays])
            </div>
            @endif

            {{-- Reports --}}
            @if($activeTab === 'reports')
            <div>
                @include('admin.partials.sakuna.tab-reports', ['barangays' => $barangays, 'incidents' => $incidents, 'centers' => $centers])
            </div>
            @endif

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
                    <h3 class="text-base font-semibold text-slate-800">Activate Emergency Operations Center</h3>
                    <p class="text-sm text-slate-500">This is a high-impact action. Please confirm.</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 mb-5">Activating the EOC will notify all department heads, MDRRMO personnel, and partner agencies. An EOC activation log will be recorded with your credentials and timestamp.</p>
            <div class="flex gap-3">
                <button @click="showEOCConfirm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button @click="showEOCConfirm = false; opStatus = 'Emergency Operations Activated'; toast('EOC activated. All department heads notified.', 'success'); $nextTick(() => window.renderIcons?.())" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors">Confirm Activation</button>
            </div>
        </div>
    </div>

</div>

</x-layouts.admin>
