{{-- ============================================================
     FILE: modal-individual-detail.blade.php
     Partial: Comprehensive individual detail modal
     Alpine scope: parent x-data (showIndividualDetail, selectedIndividual, etc.)
     ============================================================ --}}

<div
    x-data="{
        get open() { return showIndividualDetail; },
        set open(v) { showIndividualDetail = v; }
    }"
>
    <x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <div class="flex flex-col min-h-0 h-full" x-show="selectedIndividual">

        {{-- ════════════════════════════════════════════════════════
             MODAL HEADER (compact — tabs live in the side nav below)
        ════════════════════════════════════════════════════════ --}}
        <div
            class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden"
        >
            {{-- Decorative backdrop ring --}}
            <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

            {{-- Compact toggle + Close --}}
            <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                <button
                    type="button"
                    @click="compact = !compact"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                    :title="compact ? 'Expand to full screen' : 'Shrink to popup'"
                >
                    <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                    <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                </button>
                <button
                    type="button"
                    @click="showIndividualDetail = false"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="flex items-start gap-5 relative z-10">

                {{-- Profile completion ring + avatar --}}
                <div class="flex-shrink-0 relative">
                    {{-- Outer ring (SVG donut) --}}
                    <div class="relative w-20 h-20">
                        <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="36" stroke="rgba(255,255,255,0.15)" stroke-width="5" fill="none"/>
                            <circle
                                cx="40" cy="40" r="36"
                                stroke="rgba(255,255,255,0.85)"
                                stroke-width="5"
                                fill="none"
                                stroke-linecap="round"
                                :stroke-dasharray="'226.2'"
                                :stroke-dashoffset="226.2 - (226.2 * (selectedIndividual?.profile_pct || 0) / 100)"
                                class="transition-all duration-[600ms] ease-standard"
                            />
                        </svg>
                        {{-- Avatar inside ring --}}
                        <div class="absolute inset-2 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xl uppercase">
                            <span x-text="(selectedIndividual?.name || '').split(' ').map(w=>w[0]).slice(0,2).join('')"></span>
                        </div>
                    </div>
                    {{-- Profile % label --}}
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-white/20 rounded-full px-2 py-0.5 text-[10px] font-bold text-white whitespace-nowrap">
                        <span x-text="(selectedIndividual?.profile_pct || 0) + '%'"></span>
                    </div>
                </div>

                {{-- Identity block --}}
                <div class="flex-1 min-w-0 pt-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        {{-- Status badge --}}
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white"
                            x-text="selectedIndividual?.status"
                        ></span>
                        {{-- Verification badge --}}
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80"
                        >
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.verification"></span>
                        </span>
                        {{-- Citizen Portal account linkage indicator --}}
                        <span
                            x-show="selectedIndividual?.citizen_account"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30"
                        >
                            <i data-lucide="smartphone" class="w-3 h-3"></i>
                            Portal Linked
                        </span>
                        <span
                            x-show="!selectedIndividual?.citizen_account"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-white/40"
                        >
                            <i data-lucide="smartphone" class="w-3 h-3"></i>
                            No Portal Account
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedIndividual?.name"></h2>
                    <p class="text-sm text-white/60 mt-0.5">
                        ID: <span class="font-mono font-medium text-white/80" x-text="selectedIndividual?.id"></span>
                        &nbsp;·&nbsp;
                        <span x-text="selectedIndividual?.sex === 'F' ? 'Female' : 'Male'"></span>,
                        <span x-text="selectedIndividual?.age + ' years old'"></span>
                    </p>

                    {{-- Role / relation chip --}}
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="house" class="w-3 h-3"></i>
                            <span x-text="selectedIndividual?.relation || 'Member'"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></span>
                        </span>
                        {{-- Program / sector tag chips --}}
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="'hdrtag-'+tag">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="tag" class="w-3 h-3"></i>
                                <span x-text="
                                    tag === 'solo_parent' ? 'Solo Parent' :
                                    tag === 'high_risk' ? 'High Risk' :
                                    tag === 'osy' ? 'Out-of-School Youth' :
                                    tag === '4ps' ? '4Ps' :
                                    tag === 'osca' ? 'OSCA' :
                                    tag === 'pwd' ? 'PWD' :
                                    tag.charAt(0).toUpperCase() + tag.slice(1)
                                "></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             BODY: side nav + scrollable content panel
        ════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-h-0 flex flex-col sm:flex-row overflow-hidden">

            {{-- ── Side nav ── --}}
            <div class="w-full sm:w-48 shrink-0 flex flex-row sm:flex-col gap-0.5 border-b sm:border-b-0 sm:border-r border-slate-100 bg-slate-50/60 p-2 sm:p-2.5 overflow-x-auto sm:overflow-x-visible sm:overflow-y-auto scrollbar-thin">
                <template x-for="tab in [
                    { name: 'Overview', icon: 'layout-dashboard' },
                    { name: 'Programs & Benefits', icon: 'landmark' },
                    { name: 'Dokyu', icon: 'file-stack', count: (selectedIndividual?.document_requests || []).length },
                    { name: 'Tulong', icon: 'hand-heart', count: (selectedIndividual?.assistance_requests || []).length },
                    { name: 'Services', icon: 'clipboard-list', count: (selectedIndividual?.services || []).length },
                    { name: 'Sakuna', icon: 'triangle-alert', count: (selectedIndividual?.incident_reports || []).length },
                    { name: 'History', icon: 'history' },
                ]" :key="tab.name">
                    <button
                        @click="individualTab = tab.name"
                        class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-lg text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                        :class="individualTab === tab.name ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'"
                    >
                        <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="individualTab === tab.name ? 'text-brand-600' : 'text-slate-400'"></i>
                        <span class="flex-1 min-w-0 sm:truncate" x-text="tab.name"></span>
                        <span
                            x-show="tab.count"
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                            :class="individualTab === tab.name ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-500'"
                            x-text="tab.count"
                        ></span>
                    </button>
                </template>
            </div>

            {{-- ── Content panel ── --}}
            <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50">

            {{-- ── TAB 1: Overview ── --}}
            <div x-show="individualTab === 'Overview'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">

                {{-- Personal info grid --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Personal Information</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Full Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.name || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Date of Birth</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.dob || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Age</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <span x-text="(selectedIndividual?.age || '—') + (selectedIndividual?.age ? ' years old' : '')"></span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Sex</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <span x-text="selectedIndividual?.sex === 'F' ? 'Female' : selectedIndividual?.sex === 'M' ? 'Male' : '—'"></span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Civil Status</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.civil_status || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Contact Number</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <a
                                    :href="'tel:' + selectedIndividual?.contact"
                                    class="text-brand-600 hover:underline"
                                    x-text="selectedIndividual?.contact || '—'"
                                ></a>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Email Address</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.email || selectedIndividual?.citizen_account?.email || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Occupation</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.occupation || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Highest Education</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.education || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Income Range</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.income_range || '—'"></dd>
                        </div>

                    </dl>
                </div>

                {{-- Account & Onboarding (Esperanza Citizen Portal signup) --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-data="{ onboardingExpanded: false }">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-4 h-4 text-brand-400"></i>
                        Account & Onboarding
                    </h3>

                    <template x-if="selectedIndividual?.citizen_account">
                        <div>
                            {{-- Summary strip — who / when / status at a glance --}}
                            <div class="flex flex-wrap items-center gap-2 mb-5 pb-5 border-b border-slate-100">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': selectedIndividual?.citizen_account?.status === 'Approved',
                                        'bg-amber-100 text-amber-700': selectedIndividual?.citizen_account?.status === 'Pending Review',
                                        'bg-indigo-100 text-indigo-700': selectedIndividual?.citizen_account?.status === 'Under Verification',
                                        'bg-rose-100 text-rose-700': selectedIndividual?.citizen_account?.status === 'Rejected',
                                        'bg-slate-100 text-slate-600': !['Approved','Pending Review','Under Verification','Rejected'].includes(selectedIndividual?.citizen_account?.status),
                                    }"
                                >
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5" x-show="selectedIndividual?.citizen_account?.status === 'Approved'"></i>
                                    <span x-text="selectedIndividual?.citizen_account?.status || 'Draft'"></span>
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="selectedIndividual?.citizen_account?.access_level === 'Verified' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'"
                                >
                                    <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                                    <span x-text="(selectedIndividual?.citizen_account?.access_level || 'Unverified') + ' Access'"></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500">
                                    <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                                    <span x-text="'Registered ' + (selectedIndividual?.citizen_account?.registered_at || '—')"></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 ring-1 ring-purple-100">
                                    <i data-lucide="route" class="w-3.5 h-3.5"></i>
                                    <span x-text="'Onboarding: ' + onboardingStatus(selectedIndividual?.citizen_account)"></span>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                                {{-- Account Information --}}
                                <div>
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5">Account Information</h4>
                                    <dl class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Citizen Portal ID</dt>
                                            <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.id || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Email</dt>
                                            <dd class="text-xs font-semibold text-navy-900 truncate" x-text="selectedIndividual?.citizen_account?.email || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Mobile</dt>
                                            <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.mobile || '—'"></dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Citizen Information --}}
                                <div>
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5">Citizen Information</h4>
                                    <dl class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Full Name (as registered)</dt>
                                            <dd class="text-xs font-semibold text-navy-900 truncate" x-text="selectedIndividual?.name || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Resident ID</dt>
                                            <dd class="text-xs font-mono font-semibold text-navy-900" x-text="selectedIndividual?.id || '—'"></dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Residency Information --}}
                                <div>
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5">Residency Information</h4>
                                    <dl class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Barangay</dt>
                                            <dd class="text-xs font-semibold text-navy-900" x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Resident Since</dt>
                                            <dd class="text-xs font-semibold text-navy-900" x-text="selectedIndividual?.citizen_account?.resident_since || '—'"></dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Verification Information --}}
                                <div>
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-2.5">Verification Information</h4>
                                    <dl class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Terms & Conditions</dt>
                                            <dd class="inline-flex items-center gap-1 text-xs font-semibold" :class="selectedIndividual?.citizen_account?.terms_accepted ? 'text-emerald-600' : 'text-slate-400'">
                                                <i :data-lucide="selectedIndividual?.citizen_account?.terms_accepted ? 'circle-check' : 'circle-dashed'" class="w-3.5 h-3.5"></i>
                                                <span x-text="selectedIndividual?.citizen_account?.terms_accepted ? 'Accepted' : 'Not accepted'"></span>
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Valid ID</dt>
                                            <dd class="inline-flex items-center gap-1 text-xs font-semibold" :class="selectedIndividual?.citizen_account?.id_uploaded ? 'text-emerald-600' : 'text-slate-400'">
                                                <i :data-lucide="selectedIndividual?.citizen_account?.id_uploaded ? 'circle-check' : 'circle-dashed'" class="w-3.5 h-3.5"></i>
                                                <span x-text="selectedIndividual?.citizen_account?.id_uploaded ? 'Uploaded' : 'Not uploaded'"></span>
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-xs text-slate-400">Face Verification</dt>
                                            <dd class="inline-flex items-center gap-1 text-xs font-semibold" :class="selectedIndividual?.citizen_account?.face_verified ? 'text-emerald-600' : 'text-slate-400'">
                                                <i :data-lucide="selectedIndividual?.citizen_account?.face_verified ? 'circle-check' : 'circle-dashed'" class="w-3.5 h-3.5"></i>
                                                <span x-text="selectedIndividual?.citizen_account?.face_verified ? 'Completed' : 'Pending'"></span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-slate-400 mb-1">Mobile App Profile Completeness</dt>
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                    <div
                                                        class="h-full rounded-full transition-all duration-[600ms] ease-standard"
                                                        :class="completionColor(selectedIndividual?.citizen_account?.profile_completeness)"
                                                        :style="'width:' + (selectedIndividual?.citizen_account?.profile_completeness || 0) + '%'"
                                                    ></div>
                                                </div>
                                                <span
                                                    class="text-xs font-bold shrink-0"
                                                    :class="completionTextColor(selectedIndividual?.citizen_account?.profile_completeness)"
                                                    x-text="(selectedIndividual?.citizen_account?.profile_completeness || 0) + '%'"
                                                ></span>
                                            </div>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            {{-- Onboarding Timeline — mirrors the Esperanza Mobile 6-step registration wizard --}}
                            <div class="mt-5 pt-5 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="onboardingExpanded = !onboardingExpanded"
                                    class="flex items-center justify-between w-full text-left group/timeline"
                                >
                                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                        <i data-lucide="route" class="w-3.5 h-3.5 text-purple-400"></i>
                                        Onboarding Timeline
                                    </h4>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-[160ms] ease-standard group-hover/timeline:text-slate-600" :class="onboardingExpanded && 'rotate-180'"></i>
                                </button>

                                <div
                                    x-show="onboardingExpanded" x-cloak
                                    x-transition:enter="transition duration-[200ms] ease-emphasized"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="mt-3.5 space-y-0"
                                >
                                    <template x-for="(step, si) in onboardingSteps(selectedIndividual?.citizen_account)" :key="si">
                                        <div class="flex items-start gap-3">
                                            <div class="flex flex-col items-center shrink-0">
                                                <span
                                                    class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                                                    :class="step.rejected ? 'bg-rose-100 text-rose-600' : step.done ? 'bg-emerald-500 text-white' : step.current ? 'bg-amber-100 text-amber-600 animate-pulse' : 'bg-slate-100 text-slate-300'"
                                                >
                                                    <span x-show="step.rejected"><i data-lucide="x" class="w-3 h-3"></i></span>
                                                    <span x-show="step.done && !step.rejected"><i data-lucide="check" class="w-3 h-3"></i></span>
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current" x-show="!step.done && !step.rejected"></span>
                                                </span>
                                                <div class="w-px flex-1 my-0.5" :class="step.done && !step.rejected ? 'bg-emerald-200' : 'bg-slate-100'" x-show="si < onboardingSteps(selectedIndividual?.citizen_account).length - 1" style="min-height: 1.25rem"></div>
                                            </div>
                                            <p
                                                class="text-xs pb-3.5 -mt-0.5"
                                                :class="step.rejected ? 'text-rose-600 font-semibold' : step.done ? 'text-slate-700 font-medium' : step.current ? 'text-amber-600 font-semibold' : 'text-slate-400'"
                                                x-text="step.label"
                                            ></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Rejected / Under Verification callout — what's blocking full access --}}
                            <div
                                x-show="['Pending Review','Under Verification','Rejected','Draft'].includes(selectedIndividual?.citizen_account?.status)"
                                class="mt-5 pt-5 border-t border-slate-100 text-xs"
                            >
                                <div class="flex items-start gap-2.5" :class="selectedIndividual?.citizen_account?.status === 'Rejected' ? 'text-rose-600' : 'text-amber-600'">
                                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                    <span x-text="
                                        selectedIndividual?.citizen_account?.status === 'Rejected'
                                            ? 'Registration was rejected — resident needs to resubmit valid ID / details before Dokyu and Tulong access can be granted.'
                                            : selectedIndividual?.citizen_account?.status === 'Under Verification'
                                            ? 'LGU is actively reviewing this registration. Dokyu and Tulong remain locked until approved.'
                                            : 'Registration submitted and awaiting LGU review. Dokyu and Tulong remain locked until approved.'
                                    "></span>
                                </div>

                                <p class="mt-2 text-rose-600 font-medium" x-show="selectedIndividual?.citizen_account?.status === 'Rejected' && selectedIndividual?.citizen_account?.reject_remarks" x-text="selectedIndividual?.citizen_account?.reject_remarks"></p>

                                <div
                                    class="mt-3.5 flex flex-wrap items-center gap-2"
                                    x-show="['Pending Review','Under Verification'].includes(selectedIndividual?.citizen_account?.status) && (can('const_citizen_verification','verify') || can('const_citizen_verification','reject'))"
                                >
                                    <x-ui.button x-show="can('const_citizen_verification','verify')" size="sm" icon="check" @click="verifyCitizenAccount(selectedIndividual)">Approve Registration</x-ui.button>
                                    <x-ui.button x-show="can('const_citizen_verification','reject')" variant="danger" size="sm" icon="x" x-bind:disabled="!rejectAccountRemarks.trim()" @click="rejectCitizenAccount(selectedIndividual)">Reject</x-ui.button>
                                </div>
                                <div class="mt-2.5" x-show="['Pending Review','Under Verification'].includes(selectedIndividual?.citizen_account?.status) && can('const_citizen_verification','reject')">
                                    <textarea
                                        x-model="rejectAccountRemarks" rows="2" placeholder="Remarks if rejecting — e.g. ID photo unreadable, name doesn't match uploaded ID..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 px-3 text-xs text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!selectedIndividual?.citizen_account">
                        <div class="py-6 text-center">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2">
                                <i data-lucide="smartphone" class="w-5 h-5 text-slate-400"></i>
                            </div>
                            <p class="text-sm font-medium text-slate-600">No linked Citizen Portal account</p>
                            <p class="text-xs text-slate-400 mt-1">This resident has not registered (or is not yet linked to) an Esperanza Citizen Portal account.</p>
                        </div>
                    </template>
                </div>

                {{-- Address & Household info --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Address & Household</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Barangay</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="'Brgy. ' + (selectedIndividual?.barangay || '—')"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Sitio / Purok</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.sitio || '—'"></dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Full Address</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.address || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Household ID</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.household_id || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Family ID</dt>
                            <dd class="text-sm font-mono font-semibold text-navy-900" x-text="selectedIndividual?.family_id || '—'"></dd>
                        </div>

                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Relation in Household</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.relation || '—'"></dd>
                        </div>

                    </dl>
                </div>

                {{-- Vulnerable Sector Tags --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4" x-show="(selectedIndividual?.tags || []).length > 0">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Vulnerable Sector Classification</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="tag">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
                                :class="{
                                    'bg-amber-100 text-amber-800 ring-1 ring-amber-200': tag === 'senior' || tag === 'osca',
                                    'bg-blue-100 text-blue-800 ring-1 ring-blue-200': tag === 'pwd',
                                    'bg-green-100 text-green-800 ring-1 ring-green-200': tag === 'youth',
                                    'bg-violet-100 text-violet-800 ring-1 ring-violet-200': tag === 'child',
                                    'bg-orange-100 text-orange-800 ring-1 ring-orange-200': tag === 'osy',
                                    'bg-cyan-100 text-cyan-800 ring-1 ring-cyan-200': tag === '4ps',
                                    'bg-rose-100 text-rose-800 ring-1 ring-rose-200': tag === 'solo_parent',
                                    'bg-lime-100 text-lime-800 ring-1 ring-lime-200': tag === 'farmer',
                                    'bg-red-100 text-red-800 ring-1 ring-red-200': tag === 'high_risk',
                                }"
                            >
                                <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                <span x-text="
                                    tag === 'solo_parent' ? 'Solo Parent' :
                                    tag === 'high_risk' ? 'High Risk' :
                                    tag === 'osy' ? 'Out-of-School Youth' :
                                    tag === '4ps' ? '4Ps Beneficiary' :
                                    tag === 'osca' ? 'OSCA' :
                                    tag === 'pwd' ? 'PWD' :
                                    tag.charAt(0).toUpperCase() + tag.slice(1)
                                "></span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="circle-alert" class="w-4 h-4 text-rose-400"></i>
                        Emergency Contact
                    </h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Name</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.emergency?.name || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Contact</dt>
                            <dd class="text-sm font-semibold text-navy-900">
                                <a :href="'tel:' + selectedIndividual?.emergency?.contact"
                                   class="text-brand-600 hover:underline"
                                   x-text="selectedIndividual?.emergency?.contact || '—'"></a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Relationship</dt>
                            <dd class="text-sm font-semibold text-navy-900" x-text="selectedIndividual?.emergency?.relation || '—'"></dd>
                        </div>
                    </dl>
                </div>

            </div>{{-- /Overview --}}


            {{-- ── TAB 2: Programs & Benefits ── --}}
            <div x-show="individualTab === 'Programs & Benefits'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-blue-50 border border-blue-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-blue-400 flex-shrink-0"></i>
                    Programs shown are based on vulnerable sector tags encoded on this resident's profile.
                </p>

                {{-- Program list derived from tags --}}
                <template x-if="(selectedIndividual?.tags || []).length === 0">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="package-open" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No program enrollments found</p>
                        <p class="text-xs text-slate-400 mt-1">Add sector tags on the resident's profile to reflect program eligibility.</p>
                    </div>
                </template>

                <template x-if="(selectedIndividual?.tags || []).length > 0">
                    <div class="space-y-3">
                        <template x-for="tag in (selectedIndividual?.tags || [])" :key="'prog-'+tag">
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                        :class="{
                                            'bg-amber-100': tag === 'senior' || tag === 'osca',
                                            'bg-blue-100': tag === 'pwd',
                                            'bg-green-100': tag === 'youth',
                                            'bg-violet-100': tag === 'child',
                                            'bg-orange-100': tag === 'osy',
                                            'bg-cyan-100': tag === '4ps',
                                            'bg-rose-100': tag === 'solo_parent',
                                            'bg-lime-100': tag === 'farmer',
                                            'bg-red-100': tag === 'high_risk',
                                        }"
                                    >
                                        <i data-lucide="landmark" class="w-5 h-5"
                                            :class="{
                                                'text-amber-600': tag === 'senior' || tag === 'osca',
                                                'text-blue-600': tag === 'pwd',
                                                'text-green-600': tag === 'youth',
                                                'text-violet-600': tag === 'child',
                                                'text-orange-600': tag === 'osy',
                                                'text-cyan-600': tag === '4ps',
                                                'text-rose-600': tag === 'solo_parent',
                                                'text-lime-700': tag === 'farmer',
                                                'text-red-600': tag === 'high_risk',
                                            }"
                                        ></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-navy-900" x-text="
                                            tag === '4ps' ? 'Pantawid Pamilyang Pilipino Program (4Ps)' :
                                            tag === 'osca' || tag === 'senior' ? 'OSCA / Senior Citizen Assistance' :
                                            tag === 'pwd' ? 'PWD Assistance & Benefits Program' :
                                            tag === 'solo_parent' ? 'Solo Parent Cash Assistance Program' :
                                            tag === 'youth' ? 'Youth Development Program' :
                                            tag === 'child' ? 'Child Protection & Early Childhood Program' :
                                            tag === 'osy' ? 'Alternative Learning System (ALS / OSY)' :
                                            tag === 'farmer' ? 'Farmers\' Assistance & Livelihood Program' :
                                            tag === 'high_risk' ? 'High-Risk Family Monitoring Program' :
                                            tag.toUpperCase() + ' Program'
                                        "></p>
                                        <p class="text-xs text-slate-500 mt-0.5">Barangay-administered &bull; MSWDO linked</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 flex-shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                                    Active
                                </span>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Programs & Benefits --}}


            {{-- ── TAB 3: Services ── --}}
            <div x-show="individualTab === 'Services'" class="space-y-4">

                <template x-if="!(selectedIndividual?.services?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="clipboard-list" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No service records yet</p>
                        <p class="text-xs text-slate-400 mt-1">Service availed will appear here as transactions are processed.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.services?.length">
                    <div class="relative">
                        {{-- Timeline line --}}
                        <div class="absolute left-[19px] top-3 bottom-3 w-0.5 bg-slate-200 rounded-full"></div>

                        <div class="space-y-4">
                            <template x-for="(svc, idx) in (selectedIndividual?.services || [])" :key="idx">
                                <div class="flex items-start gap-4 relative">
                                    {{-- Timeline dot --}}
                                    <div class="w-9 h-9 rounded-full bg-white border-2 border-slate-200 flex items-center justify-center flex-shrink-0 shadow-sm z-10">
                                        <i data-lucide="file-text" class="w-4 h-4 text-brand-500"></i>
                                    </div>
                                    <div class="flex-1 bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-navy-900" x-text="svc.type"></p>
                                                <p class="text-xs text-slate-400 mt-0.5" x-text="svc.date"></p>
                                            </div>
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                                :class="{
                                                    'bg-green-100 text-green-700': svc.status === 'Completed' || svc.status === 'Released',
                                                    'bg-amber-100 text-amber-700': svc.status === 'Processing' || svc.status === 'Pending',
                                                    'bg-blue-100 text-blue-700': svc.status === 'Approved',
                                                    'bg-red-100 text-red-700': svc.status === 'Rejected' || svc.status === 'Cancelled',
                                                    'bg-slate-100 text-slate-600': !['Completed','Released','Processing','Pending','Approved','Rejected','Cancelled'].includes(svc.status),
                                                }"
                                                x-text="svc.status"
                                            ></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>{{-- /Services --}}


            {{-- ── TAB 4: Dokyu ── --}}
            <div x-show="individualTab === 'Dokyu'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                    Document checklist status is derived from verification records. Upload to update.
                </p>

                {{-- Base required documents --}}
                <template x-for="doc in [
                    { label: 'PSA Birth Certificate', key: 'psa_birth', icon: 'scroll-text', required: true, tag: null },
                    { label: 'Valid Government-Issued ID', key: 'gov_id', icon: 'id-card', required: true, tag: null },
                    { label: 'PhilSys National ID', key: 'philsys', icon: 'credit-card', required: true, tag: null },
                    { label: 'Proof of Residency', key: 'proof_residency', icon: 'home', required: true, tag: null },
                    { label: 'School ID / Enrollment Form', key: 'school_id', icon: 'graduation-cap', required: false, tag: 'youth' },
                    { label: 'School ID / Enrollment Form', key: 'school_id_child', icon: 'graduation-cap', required: false, tag: 'child' },
                    { label: 'PWD Identification Card', key: 'pwd_id', icon: 'accessibility', required: false, tag: 'pwd' },
                    { label: 'OSCA Senior Citizen ID', key: 'osca_id', icon: 'award', required: false, tag: 'senior' },
                    { label: 'Solo Parent ID', key: 'solo_id', icon: 'heart', required: false, tag: 'solo_parent' },
                    { label: '4Ps Beneficiary Card', key: 'fourps_card', icon: 'wallet', required: false, tag: '4ps' },
                ]" :key="doc.key">
                    {{-- Show if no tag OR tag is present in resident's tags --}}
                    <template x-if="!doc.tag || (selectedIndividual?.tags || []).includes(doc.tag)">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="selectedIndividual?.verification === 'Verified' ? 'bg-green-100' : 'bg-slate-100'"
                            >
                                <i
                                    :data-lucide="doc.icon"
                                    class="w-5 h-5"
                                    :class="selectedIndividual?.verification === 'Verified' ? 'text-green-600' : 'text-slate-400'"
                                ></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-navy-900" x-text="doc.label"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="doc.required ? 'Required' : 'Conditional (sector-specific)'"></p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                :class="selectedIndividual?.verification === 'Verified'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-slate-100 text-slate-500'"
                            >
                                <template x-if="selectedIndividual?.verification === 'Verified'">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                </template>
                                <template x-if="selectedIndividual?.verification !== 'Verified'">
                                    <i data-lucide="circle-dashed" class="w-3.5 h-3.5"></i>
                                </template>
                                <span x-text="selectedIndividual?.verification === 'Verified' ? 'Submitted' : 'Missing'"></span>
                            </span>
                        </div>
                    </template>
                </template>

                <button
                    @click="$dispatch('toast', {message: 'Document upload feature — connect to document management module.', variant: 'info'})"
                    class="w-full mt-2 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-dashed border-slate-200 text-sm text-slate-500 hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                >
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Upload Document
                </button>

                {{-- Dokyu request history — visually separate from the identity checklist above --}}
                <div class="pt-2 mt-4 border-t border-dashed border-slate-200">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="file-stack" class="w-4 h-4 text-brand-400"></i>
                        Document Requests (Dokyu)
                    </h3>

                    <template x-if="!(selectedIndividual?.document_requests?.length)">
                        <div class="py-8 text-center bg-white rounded-xl border border-slate-200">
                            <i data-lucide="inbox" class="w-6 h-6 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-sm font-medium text-slate-600">No Dokyu requests on file</p>
                            <p class="text-xs text-slate-400 mt-1">Document requests submitted via Dokyu will appear here.</p>
                        </div>
                    </template>

                    <template x-if="selectedIndividual?.document_requests?.length">
                        <div class="space-y-2">
                            <template x-for="d in (selectedIndividual?.document_requests || [])" :key="d.ref">
                                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-navy-900" x-text="d.type"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="d.office + ' · ' + d.purpose"></p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            <span class="font-mono" x-text="d.ref"></span>
                                            &nbsp;·&nbsp;
                                            <span x-text="'Submitted ' + d.submitted"></span>
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                        :class="{
                                            'bg-green-100 text-green-700': ['Released','Completed','Approved','Ready for Release'].includes(d.status),
                                            'bg-amber-100 text-amber-700': ['Processing','Waiting Requirements','Pending Review','Assigned'].includes(d.status),
                                            'bg-blue-100 text-blue-700': ['Submitted','Under Verification'].includes(d.status),
                                            'bg-red-100 text-red-700': ['Rejected','Cancelled'].includes(d.status),
                                            'bg-slate-100 text-slate-600': !['Released','Completed','Approved','Ready for Release','Processing','Waiting Requirements','Pending Review','Assigned','Submitted','Under Verification','Rejected','Cancelled'].includes(d.status),
                                        }"
                                        x-text="d.status"
                                    ></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

            </div>{{-- /Dokyu --}}


            {{-- ── TAB: Tulong (assistance request history) ── --}}
            <div x-show="individualTab === 'Tulong'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-rose-50 border border-rose-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="hand-heart" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                    Assistance requests submitted via Tulong — distinct from ongoing sector-based Programs &amp; Benefits enrollment.
                </p>

                <template x-if="!(selectedIndividual?.assistance_requests?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="hand-heart" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No Tulong requests on file</p>
                        <p class="text-xs text-slate-400 mt-1">Assistance requests submitted by this resident will appear here.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.assistance_requests?.length">
                    <div class="space-y-2">
                        <template x-for="a in (selectedIndividual?.assistance_requests || [])" :key="a.ref">
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900" x-text="a.type"></p>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="a.office + ' · ' + a.purpose"></p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        <span class="font-mono" x-text="a.ref"></span>
                                        &nbsp;·&nbsp;
                                        <span x-text="'Submitted ' + a.submitted"></span>
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                    :class="{
                                        'bg-green-100 text-green-700': ['Released','Completed','Approved'].includes(a.status),
                                        'bg-amber-100 text-amber-700': ['Processing','Waiting Requirements','Assigned'].includes(a.status),
                                        'bg-blue-100 text-blue-700': ['Submitted','Pending Review'].includes(a.status),
                                        'bg-red-100 text-red-700': ['Rejected','Cancelled'].includes(a.status),
                                        'bg-slate-100 text-slate-600': !['Released','Completed','Approved','Processing','Waiting Requirements','Assigned','Submitted','Pending Review','Rejected','Cancelled'].includes(a.status),
                                    }"
                                    x-text="a.status"
                                ></span>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Tulong --}}


            {{-- ── TAB: Sakuna (incident report history) ── --}}
            <div x-show="individualTab === 'Sakuna'" class="space-y-3">

                <p class="text-xs text-slate-500 bg-orange-50 border border-orange-100 rounded-lg px-4 py-2.5 flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="w-4 h-4 text-orange-400 flex-shrink-0"></i>
                    Disaster / incident reports filed by or concerning this resident.
                </p>

                <template x-if="!(selectedIndividual?.incident_reports?.length)">
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="triangle-alert" class="w-6 h-6 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-600">No incident reports on file</p>
                        <p class="text-xs text-slate-400 mt-1">Sakuna incident reports involving this resident will appear here.</p>
                    </div>
                </template>

                <template x-if="selectedIndividual?.incident_reports?.length">
                    <div class="space-y-2">
                        <template x-for="i in (selectedIndividual?.incident_reports || [])" :key="i.ref">
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900" x-text="i.type"></p>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="i.office + ' · ' + i.location"></p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        <span class="font-mono" x-text="i.ref"></span>
                                        &nbsp;·&nbsp;
                                        <span x-text="i.date"></span>
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0"
                                    :class="{
                                        'bg-green-100 text-green-700': ['Resolved','Closed'].includes(i.status),
                                        'bg-amber-100 text-amber-700': ['Dispatched'].includes(i.status),
                                        'bg-blue-100 text-blue-700': ['Submitted','Validated'].includes(i.status),
                                        'bg-slate-100 text-slate-600': !['Resolved','Closed','Dispatched','Submitted','Validated'].includes(i.status),
                                    }"
                                    x-text="i.status"
                                ></span>
                            </div>
                        </template>
                    </div>
                </template>

            </div>{{-- /Sakuna --}}


            {{-- ── TAB 5: History ── --}}
            <div x-show="individualTab === 'History'" class="space-y-4">

                <div class="bg-white rounded-2xl border border-slate-100 shadow-card divide-y divide-slate-100">

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user-plus" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Record Created</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Resident profile encoded into the system.
                                Encoded by: <span class="font-medium text-slate-700">System</span>
                            </p>
                        </div>
                        <p class="text-xs text-slate-400 flex-shrink-0" x-text="selectedIndividual?.created || '—'"></p>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="pencil" class="w-4 h-4 text-amber-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Last Updated</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Profile data last modified.
                                Updated by: <span class="font-medium text-slate-700">System</span>
                            </p>
                        </div>
                        <p class="text-xs text-slate-400 flex-shrink-0" x-text="selectedIndividual?.last_updated || '—'"></p>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                            :class="selectedIndividual?.verification === 'Verified' ? 'bg-green-100' : 'bg-slate-100'"
                        >
                            <i data-lucide="shield-check" class="w-4 h-4"
                                :class="selectedIndividual?.verification === 'Verified' ? 'text-green-600' : 'text-slate-400'"
                            ></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Verification Status</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current verification level:
                                <span class="font-medium text-slate-700" x-text="selectedIndividual?.verification"></span>
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                            :class="verificationBadge(selectedIndividual?.verification).class"
                            x-text="selectedIndividual?.verification"
                        ></span>
                    </div>

                    <div class="p-4 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="chart-bar" class="w-4 h-4 text-violet-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-navy-900">Profile Completion</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current profile completeness score.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-[600ms] ease-standard"
                                    :class="completionColor(selectedIndividual?.profile_pct)"
                                    :style="'width:' + (selectedIndividual?.profile_pct || 0) + '%'"
                                ></div>
                            </div>
                            <span
                                class="text-xs font-bold"
                                :class="completionTextColor(selectedIndividual?.profile_pct)"
                                x-text="(selectedIndividual?.profile_pct || 0) + '%'"
                            ></span>
                        </div>
                    </div>

                </div>

                {{-- Merged chronological activity timeline (account, Dokyu, Tulong, Sakuna events) --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Activity Timeline</h3>

                    <template x-if="!individualTimeline.length">
                        <div class="bg-slate-50 rounded-xl border border-dashed border-slate-200 p-5 text-center">
                            <i data-lucide="clock-2" class="w-6 h-6 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-xs text-slate-400">No timeline events recorded for this resident yet.</p>
                        </div>
                    </template>

                    <template x-if="individualTimeline.length">
                        <div class="relative">
                            <div class="absolute left-[19px] top-3 bottom-3 w-0.5 bg-slate-200 rounded-full"></div>
                            <div class="space-y-3">
                                <template x-for="(ev, idx) in individualTimeline" :key="idx">
                                    <div class="flex items-start gap-4 relative">
                                        <div
                                            class="w-9 h-9 rounded-full bg-white border-2 flex items-center justify-center flex-shrink-0 shadow-sm z-10"
                                            :class="{
                                                'border-blue-200': ev.color === 'blue',
                                                'border-amber-200': ev.color === 'amber',
                                                'border-indigo-200': ev.color === 'indigo',
                                                'border-rose-200': ev.color === 'rose',
                                                'border-orange-200': ev.color === 'orange',
                                            }"
                                        >
                                            <i
                                                :data-lucide="ev.icon"
                                                class="w-4 h-4"
                                                :class="{
                                                    'text-blue-500': ev.color === 'blue',
                                                    'text-amber-500': ev.color === 'amber',
                                                    'text-indigo-500': ev.color === 'indigo',
                                                    'text-rose-500': ev.color === 'rose',
                                                    'text-orange-500': ev.color === 'orange',
                                                }"
                                            ></i>
                                        </div>
                                        <div class="flex-1 bg-white rounded-2xl border border-slate-100 shadow-card p-3.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-navy-900" x-text="ev.label"></p>
                                                    <p class="text-xs text-slate-500 mt-0.5" x-text="ev.desc"></p>
                                                </div>
                                                <p class="text-xs text-slate-400 flex-shrink-0" x-text="ev.date"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </div>{{-- /History --}}

            </div>{{-- /content panel --}}

        </div>{{-- /body: side nav + content panel --}}


        {{-- ════════════════════════════════════════════════════════
             FOOTER ACTIONS
        ════════════════════════════════════════════════════════ --}}
        <div class="shrink-0 px-6 py-4 border-t border-slate-200 bg-white rounded-b-2xl flex items-center justify-end gap-3 flex-wrap">

            <div class="flex items-center gap-2">

                {{-- Edit button --}}
                <button
                    @click="$dispatch('toast', {message: 'Edit resident — connect to edit modal.', variant: 'info'})"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                    x-show="can('const_resident','edit')"
                >
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                    Edit
                </button>

                {{-- Lifecycle dropdown --}}
                <div class="relative" x-data="{ lopen: false }" x-show="can('const_resident','edit')">
                    <button
                        @click="lopen = !lopen"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 transition-colors"
                    >
                        <i data-lucide="settings-2" class="w-4 h-4"></i>
                        Lifecycle
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 ml-0.5 transition-transform" :class="lopen ? 'rotate-180' : ''"></i>
                    </button>

                    <div
                        x-show="lopen"
                        @click.outside="lopen = false"
                        x-transition:enter="transition duration-[200ms] ease-emphasized"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition duration-[150ms] ease-exit"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                        class="absolute bottom-full right-0 mb-2 w-52 rounded-xl bg-white border border-slate-200 shadow-lg divide-y divide-slate-100 overflow-hidden z-50"
                        style="display:none"
                    >
                        <div class="py-1">
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Transfer'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                            >
                                <i data-lucide="arrow-right-from-line" class="w-4 h-4 text-indigo-400"></i>
                                Transfer Resident
                            </button>
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Deceased'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                            >
                                <i data-lucide="flower" class="w-4 h-4 text-slate-400"></i>
                                Mark as Deceased
                            </button>
                        </div>
                        <div class="py-1">
                            <button
                                @click="lopen=false; openLifecycle(selectedIndividual, 'Archive'); showIndividualDetail=false"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-amber-600 hover:bg-amber-50 transition-colors text-left"
                            >
                                <i data-lucide="archive" class="w-4 h-4"></i>
                                Archive Record
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- /root --}}

    </x-ui.modal>
</div>
