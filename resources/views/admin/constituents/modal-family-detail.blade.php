{{-- modal-family-detail.blade.php --}}
{{-- Family detail modal — 4 tabs: Overview | Members | Programs | History --}}

<div x-data="{
    get open() { return showFamilyDetail },
    set open(v) { showFamilyDetail = v }
}">
<x-ui.modal maxWidth="2xl" :bare="true" :fullscreen="true">

    <template x-if="selectedFamily">
        <div class="flex flex-col min-h-0 h-full">

            {{-- ════════════════════════════════════════════════════════
                 MODAL HEADER (dark hero — matches individual/household)
            ════════════════════════════════════════════════════════ --}}
            <div class="relative shrink-0 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-900 text-white px-6 py-5 rounded-t-2xl overflow-hidden">
                <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

                {{-- Compact toggle + Close --}}
                <div class="absolute top-4 right-4 flex items-center gap-1.5 z-20">
                    <button type="button" @click="compact = !compact" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                        <span x-show="compact"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i></span>
                        <span x-show="!compact"><i data-lucide="minimize-2" class="w-3.5 h-3.5"></i></span>
                    </button>
                    <button type="button" @click="showFamilyDetail = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex items-start gap-5 relative z-10">

                    {{-- Profile completion ring + avatar --}}
                    <div class="flex-shrink-0 relative">
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
                                    :stroke-dashoffset="226.2 - (226.2 * (selectedFamily?.profile_pct || 0) / 100)"
                                    class="transition-all duration-700"
                                />
                            </svg>
                            <div class="absolute inset-2 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xl uppercase">
                                <span x-text="selectedFamily?.surname?.substring(0,2).toUpperCase()"></span>
                            </div>
                        </div>
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-white/20 rounded-full px-2 py-0.5 text-[10px] font-bold text-white whitespace-nowrap">
                            <span x-text="(selectedFamily?.profile_pct || 0) + '%'"></span>
                        </div>
                    </div>

                    {{-- Identity block --}}
                    <div class="flex-1 min-w-0 pt-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white" x-text="selectedFamily?.family_type"></span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                                <i data-lucide="shield-check" class="w-3 h-3"></i>
                                <span x-text="verificationBadge(selectedFamily?.verification).label"></span>
                            </span>
                            <span x-show="selectedFamily?.household_id" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/30">
                                <i data-lucide="house" class="w-3 h-3"></i>
                                <span x-text="selectedFamily?.household_id"></span>
                            </span>
                        </div>

                        <h2 class="text-xl font-bold text-white leading-tight truncate" x-text="selectedFamily?.label"></h2>
                        <p class="text-sm text-white/60 mt-0.5">
                            ID: <span class="font-mono font-medium text-white/80" x-text="selectedFamily?.id"></span>
                            &nbsp;·&nbsp;
                            <span x-text="(selectedFamily?.member_count || 0) + ' members'"></span>
                        </p>

                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span x-text="'Brgy. ' + (selectedFamily?.barangay || '—')"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white/10 text-xs font-medium text-white/80">
                                <i data-lucide="user" class="w-3 h-3"></i>
                                <span x-text="selectedFamily?.head_name || 'No head listed'"></span>
                            </span>
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
                        {id:'overview',icon:'layout-list',label:'Overview'},
                        {id:'members',icon:'users',label:'Members',count:selectedFamily?.member_count},
                        {id:'programs',icon:'heart-handshake',label:'Programs'},
                        {id:'history',icon:'clock',label:'History'}
                    ]" :key="tab.id">
                        <button
                            @click="familyTab = tab.id"
                            class="shrink-0 sm:w-full flex items-center gap-2 sm:gap-2.5 px-3 py-2 sm:py-2.5 rounded-lg text-[13px] font-medium transition-colors whitespace-nowrap sm:whitespace-normal text-left"
                            :class="familyTab === tab.id ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'"
                        >
                            <i :data-lucide="tab.icon" class="w-4 h-4 shrink-0" :class="familyTab === tab.id ? 'text-brand-600' : 'text-slate-400'"></i>
                            <span class="flex-1 min-w-0 sm:truncate" x-text="tab.label"></span>
                            <span
                                x-show="tab.count"
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0"
                                :class="familyTab === tab.id ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-500'"
                                x-text="tab.count"
                            ></span>
                        </button>
                    </template>
                </div>

                {{-- ── Content panel ── --}}
                <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-6 py-5 bg-slate-50/50 space-y-4">

                {{-- ── OVERVIEW TAB ── --}}
                <div x-show="familyTab === 'overview'" x-cloak>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Left column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Family Details</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="users-round" class="w-3.5 h-3.5 text-slate-400"></i> Family Type</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedFamily.family_type"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i> Barangay</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedFamily.barangay"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="milestone" class="w-3.5 h-3.5 text-slate-400"></i> Sitio / Purok</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedFamily.sitio || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="house" class="w-3.5 h-3.5 text-slate-400"></i> Household ID</dt>
                                        <dd class="text-xs font-semibold text-blue-600" x-text="selectedFamily.household_id || 'Unlinked'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i> Head of Family</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedFamily.head_name || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500 flex items-center gap-1.5"><i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i> Member Count</dt>
                                        <dd class="text-xs font-semibold text-navy-900" x-text="selectedFamily.member_count"></dd>
                                    </div>
                                </dl>
                            </x-ui.card>
                        </div>

                        {{-- Right column --}}
                        <div class="space-y-3">
                            <x-ui.card class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Status & Verification</h4>
                                <dl class="space-y-2.5">
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-slate-500">Record Status</dt>
                                        <dd>
                                            <x-ui.badge :status="''" x-bind:data-status="selectedFamily.status" />
                                        </dd>
                                    </div>
                                    <div class="flex justify-between items-center gap-2">
                                        <dt class="text-xs text-slate-500">Verification</dt>
                                        <dd>
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="verificationBadge(selectedFamily.verification).class"
                                                x-text="verificationBadge(selectedFamily.verification).label"
                                            ></span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between items-start gap-2">
                                        <dt class="text-xs text-slate-500">Profile Completion</dt>
                                        <dd class="text-xs font-bold" :class="completionTextColor(selectedFamily.profile_pct)" x-text="(selectedFamily.profile_pct||0) + '%'"></dd>
                                    </div>
                                </dl>
                            </x-ui.card>

                            {{-- Programs chips --}}
                            <x-ui.card class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Enrolled Programs</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-if="!selectedFamily.programs || selectedFamily.programs.length === 0">
                                        <span class="text-xs text-slate-400 italic">No enrolled programs</span>
                                    </template>
                                    <template x-for="prog in (selectedFamily.programs || [])" :key="prog">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700" x-text="prog"></span>
                                    </template>
                                </div>
                            </x-ui.card>

                            {{-- Notes --}}
                            <x-ui.card class="p-4">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes / Remarks</h4>
                                <p class="text-xs text-slate-600 leading-relaxed" x-text="selectedFamily.notes || 'No notes recorded.'"></p>
                            </x-ui.card>
                        </div>
                    </div>
                </div>

                {{-- ── MEMBERS TAB ── --}}
                <div x-show="familyTab === 'members'" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-navy-900">
                            All Family Members
                            <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 font-semibold" x-text="selectedFamily.member_count"></span>
                        </h3>
                    </div>

                    <x-ui.card class="overflow-hidden">
                        <template x-if="(individuals.filter(r => selectedFamily?.member_ids?.includes(r.id))).length === 0">
                            <div class="py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="user-x" class="w-8 h-8 text-slate-300"></i>
                                    <p class="text-sm text-slate-400">No members linked to this family yet.</p>
                                </div>
                            </div>
                        </template>

                        <ul class="divide-y divide-slate-100">
                            <template x-for="r in individuals.filter(r => selectedFamily?.member_ids?.includes(r.id))" :key="r.id">
                                <li
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50/40 cursor-pointer transition-colors"
                                    @click="openIndividual(r)"
                                >
                                    <div class="w-8 h-8 text-xs rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0" x-text="(r.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-navy-900 truncate" x-text="r.name"></p>
                                        <p class="text-xs text-slate-400">
                                            <span x-text="r.age ? r.age + ' yrs' : ''"></span>
                                            <template x-if="r.relation"><span> · <span x-text="r.relation"></span></span></template>
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0"
                                        :class="verificationBadge(r.verification).class"
                                        x-text="verificationBadge(r.verification).label"
                                    ></span>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 flex-shrink-0"></i>
                                </li>
                            </template>
                        </ul>
                    </x-ui.card>
                </div>

                {{-- ── PROGRAMS TAB ── --}}
                <div x-show="familyTab === 'programs'" x-cloak>
                    <h3 class="text-sm font-semibold text-navy-900 mb-3">Program Enrollments</h3>

                    <template x-if="!selectedFamily.programs || selectedFamily.programs.length === 0">
                        <x-ui.card class="py-10 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="heart-handshake" class="w-8 h-8 text-slate-300"></i>
                                <p class="text-sm text-slate-400">No programs enrolled.</p>
                            </div>
                        </x-ui.card>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="prog in (selectedFamily.programs || [])" :key="prog">
                            <x-ui.card class="p-4 flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i data-lucide="heart-handshake" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900" x-text="prog"></p>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 mt-0.5">
                                        <i data-lucide="circle-check" class="w-3 h-3"></i>
                                        Active
                                    </span>
                                </div>
                            </x-ui.card>
                        </template>
                    </div>
                </div>

                {{-- ── HISTORY TAB ── --}}
                <div x-show="familyTab === 'history'" x-cloak>
                    <h3 class="text-sm font-semibold text-navy-900 mb-3">Record History</h3>
                    <x-ui.card class="p-4 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i data-lucide="file-plus" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-navy-900">Record Created</p>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="selectedFamily.created || 'Date not recorded'"></p>
                                <p class="text-xs text-slate-400 mt-0.5">Encoded by: <span class="font-medium text-slate-600">System</span></p>
                            </div>
                        </div>
                        <div class="border-t border-slate-100"></div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i data-lucide="pencil" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-navy-900">Last Updated</p>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="selectedFamily.last_updated || 'No updates recorded'"></p>
                                <p class="text-xs text-slate-400 mt-0.5">Updated by: <span class="font-medium text-slate-600">System</span></p>
                            </div>
                        </div>
                        <div class="border-t border-slate-100"></div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i data-lucide="hash" class="w-4 h-4 text-slate-500"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-navy-900">Record ID</p>
                                <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="selectedFamily.id"></p>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                </div>
            </div>

            {{-- ── Modal Footer ── --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3 rounded-b-2xl">
                <x-ui.button size="sm" icon="user-plus" @click="$dispatch('toast', {message: 'Add member feature coming soon.', type: 'info'})">Add Member</x-ui.button>
            </div>

        </div>
    </template>

    {{-- Placeholder when no family selected --}}
    <template x-if="!selectedFamily">
        <div class="flex items-center justify-center h-48 text-slate-400 text-sm">
            <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mr-2"></i>
            Loading family details…
        </div>
    </template>

</x-ui.modal>
</div>
