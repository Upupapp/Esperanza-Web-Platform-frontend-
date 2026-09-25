{{-- tab-citizen-verification.blade.php — Citizen, Family & Household Verification Queue --}}
{{-- Rendered inside constituents.blade.php's outer Alpine x-data scope. --}}

<div class="space-y-6">

    {{-- ── ENTITY TYPE SWITCHER — its own bordered/shadowed toolbar card so
         it's anchored the same way the Registry tab's view switcher is,
         instead of a bare pill floating directly on the page background. ── --}}
    <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-1.5 w-fit">
        <div class="inline-flex items-center gap-1 bg-slate-100/70 rounded-xl p-1">
            <template x-for="t in ['Individual', 'Family', 'Household']" :key="t">
                <button
                    type="button"
                    @click="cvType = t; cvStatusFilter = ''; cvSearch = ''; cvBarangayFilter = ''; cvTagFilter = ''"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                    :class="cvType === t ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                >
                    <i :data-lucide="t === 'Individual' ? 'user' : (t === 'Family' ? 'users-round' : 'house')" class="w-3.5 h-3.5"></i>
                    <span x-text="t"></span>
                    <span
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                        :class="cvType === t ? 'bg-brand-100 text-brand-700' : 'bg-slate-200 text-slate-500'"
                        x-text="t === 'Individual' ? citizenAccountList.length : (t === 'Family' ? cvFamilyList.length : cvHouseholdList.length)"
                    ></span>
                </button>
            </template>
        </div>
    </div>

    {{-- ── SUMMARY STAT CARDS — Individual ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4" x-show="cvType === 'Individual'">
        <x-ui.stat-card @click="cvStatusFilter = 'Pending Review'" class="cursor-pointer" label="Pending Review" valueBind="cvCounts.pending" icon="clock" color="orange" :delay="0" />
        <x-ui.stat-card @click="cvStatusFilter = 'Under Verification'" class="cursor-pointer" label="Under Verification" valueBind="cvCounts.underVerification" icon="search" color="brand" :delay="75" />
        <x-ui.stat-card @click="cvStatusFilter = 'Approved'" class="cursor-pointer" label="Approved" valueBind="cvCounts.verified" icon="badge-check" color="green" :delay="150" />
        <x-ui.stat-card @click="cvStatusFilter = 'Rejected'" class="cursor-pointer" label="Rejected" valueBind="cvCounts.rejected" icon="circle-x" color="red" :delay="225" />
        <x-ui.stat-card @click="cvStatusFilter = 'Not Registered'" class="cursor-pointer" label="Not Registered" sublabel="No Citizen Portal account on file" valueBind="cvCounts.notRegistered" icon="user-round-x" color="slate" :delay="300" />
    </div>

    {{-- ── SUMMARY STAT CARDS — Family ─────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" x-show="cvType === 'Family'" x-cloak>
        <x-ui.stat-card @click="cvStatusFilter = 'Unverified'" class="cursor-pointer" label="Unverified" valueBind="cvFamilyCounts.unverified" icon="shield-x" color="red" :delay="0" />
        <x-ui.stat-card @click="cvStatusFilter = 'Partially Verified'" class="cursor-pointer" label="Needs Review" valueBind="cvFamilyCounts.partial" icon="shield-half" color="orange" :delay="75" />
        <x-ui.stat-card @click="cvStatusFilter = 'Verified'" class="cursor-pointer" label="Verified" valueBind="cvFamilyCounts.verified" icon="shield-check" color="green" :delay="150" />
        <x-ui.stat-card @click="cvStatusFilter = 'Rejected'" class="cursor-pointer" label="Rejected" valueBind="cvFamilyCounts.rejected" icon="circle-x" color="red" :delay="225" />
    </div>

    {{-- ── SUMMARY STAT CARDS — Household ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" x-show="cvType === 'Household'" x-cloak>
        <x-ui.stat-card @click="cvStatusFilter = 'Unverified'" class="cursor-pointer" label="Unverified" valueBind="cvHouseholdCounts.unverified" icon="shield-x" color="red" :delay="0" />
        <x-ui.stat-card @click="cvStatusFilter = 'Partially Verified'" class="cursor-pointer" label="Needs Review" valueBind="cvHouseholdCounts.partial" icon="shield-half" color="orange" :delay="75" />
        <x-ui.stat-card @click="cvStatusFilter = 'Verified'" class="cursor-pointer" label="Verified" valueBind="cvHouseholdCounts.verified" icon="shield-check" color="green" :delay="150" />
        <x-ui.stat-card @click="cvStatusFilter = 'Rejected'" class="cursor-pointer" label="Rejected" valueBind="cvHouseholdCounts.rejected" icon="circle-x" color="red" :delay="225" />
    </div>

    {{-- ── FILTER BAR ──────────────────────────────────────────────────────── --}}
    <x-ui.card class="px-5 py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </span>
                <input aria-label="Citizen verification search"
                    type="text"
                    x-model="cvSearch"
                    :placeholder="cvType === 'Individual' ? 'Search by name, resident ID, barangay, or email…' : 'Search by name, ID, barangay, or head…'"
                    class="w-full pl-8 pr-4 py-2 text-sm rounded-lg border border-slate-200 bg-white placeholder-slate-400 text-navy-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition"
                />
            </div>

            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="list-filter" class="w-3.5 h-3.5"></i>
                </span>
                <select aria-label="Citizen verification status filter"
                    x-model="cvStatusFilter"
                    class="pl-8 pr-8 py-2 text-sm rounded-lg border border-slate-200 bg-white text-navy-900 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition cursor-pointer"
                >
                    <option value="">All Statuses</option>
                    <template x-if="cvType === 'Individual'">
                        <template x-for="s in ['Pending Review', 'Under Verification', 'Approved', 'Rejected', 'Not Registered']" :key="s">
                            <option :value="s" x-text="s"></option>
                        </template>
                    </template>
                    <template x-if="cvType !== 'Individual'">
                        <template x-for="s in cvVerificationOptions" :key="s">
                            <option :value="s" x-text="s"></option>
                        </template>
                    </template>
                </select>
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </span>
            </div>

            {{-- ── ADVANCED FILTERS — barangay + vulnerable-sector tags, same
                 filters the Registry sub-tabs already offer, so Citizen
                 Verification isn't the one queue admins can't narrow down. ── --}}
            <div class="relative" x-show="isMunicipalScope">
                <select aria-label="Citizen verification barangay filter"
                    x-model="cvBarangayFilter"
                    class="pl-3 pr-8 py-2 text-sm rounded-lg border border-slate-200 bg-white text-navy-900 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition cursor-pointer"
                >
                    <option value="">All Barangays</option>
                    @foreach($barangayOptions as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <span x-show="!isMunicipalScope" x-cloak
                  class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 bg-purple-50 border border-purple-100 rounded-xl px-3 py-2 shrink-0">
                <i data-lucide="lock" class="w-3 h-3"></i>Brgy. <span x-text="$store.session.account?.scope"></span>
            </span>

            <div class="relative" x-show="cvType === 'Individual'" x-cloak>
                <select aria-label="Citizen verification tag filter"
                    x-model="cvTagFilter"
                    class="pl-3 pr-8 py-2 text-sm rounded-lg border border-slate-200 bg-white text-navy-900 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition cursor-pointer"
                >
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
                <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </span>
            </div>

            <button
                @click="cvSearch = ''; cvStatusFilter = ''; cvBarangayFilter = ''; cvTagFilter = ''"
                x-show="cvSearch !== '' || cvStatusFilter !== '' || cvBarangayFilter !== '' || cvTagFilter !== ''"
                x-cloak
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 transition shrink-0"
                title="Clear all filters"
            >
                <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
            </button>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            <template x-if="cvType === 'Individual'">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-navy-900" x-text="filteredCitizenAccounts.length"></span>
                    <span x-text="filteredCitizenAccounts.length === 1 ? 'citizen account' : 'citizen accounts'"></span>
                    <span x-show="cvSearch !== '' || cvStatusFilter !== '' || cvBarangayFilter !== '' || cvTagFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="citizenAccountList.length"></span> total
                    </span>
                </p>
            </template>
            <template x-if="cvType === 'Family'">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-navy-900" x-text="filteredCvFamilies.length"></span>
                    <span x-text="filteredCvFamilies.length === 1 ? 'family' : 'families'"></span>
                    <span x-show="cvSearch !== '' || cvStatusFilter !== '' || cvBarangayFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="cvFamilyList.length"></span> total
                    </span>
                </p>
            </template>
            <template x-if="cvType === 'Household'">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-navy-900" x-text="filteredCvHouseholds.length"></span>
                    <span x-text="filteredCvHouseholds.length === 1 ? 'household' : 'households'"></span>
                    <span x-show="cvSearch !== '' || cvStatusFilter !== '' || cvBarangayFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="cvHouseholdList.length"></span> total
                    </span>
                </p>
            </template>
        </div>
    </x-ui.card>

    {{-- ── INDIVIDUAL QUEUE TABLE — x-ui.table already renders its own
         bordered/shadowed card, so it's used directly here (matching the
         Registry tab's flush single-card tables) instead of being nested
         inside a second x-ui.card, which was the double-boxed, padded,
         "floating" look. ── --}}
    <x-ui.table x-show="cvType === 'Individual'">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Citizen</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Resident ID</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Registered</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Profile</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-if="filteredCitizenAccounts.length === 0">
                        <tr>
                            <td colspan="7" class="py-16 px-4 text-center">
                                <x-ui.empty-state icon="smartphone" title="No citizen accounts match this search." />
                            </td>
                        </tr>
                    </template>
                    <template x-for="i in filteredCitizenAccounts" :key="i.ref">
                        <tr @click="openIndividual(i)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group"
        tabindex="0"
        @keydown.enter.prevent="openIndividual(i)"
        @keydown.space.prevent="openIndividual(i)">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold text-xs ring-2 ring-white shadow-sm shrink-0" x-text="(i.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="i.name"></p>
                                        <p class="text-xs text-slate-400 truncate" x-text="i.citizen_account ? i.citizen_account.email : 'Not registered on Citizen Portal'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="i.ref"></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden 2xl:table-cell" x-text="'Brgy. ' + i.barangay"></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden 2xl:table-cell" x-text="i.citizen_account?.registered_at ? new Date(i.citizen_account.registered_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'"></td>
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2" x-show="i.citizen_account">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden shrink-0">
                                        <div class="h-full rounded-full bg-brand-500" :style="'width:' + (i.citizen_account?.profile_completeness || 0) + '%'"></div>
                                    </div>
                                    <span class="text-xs text-slate-400" x-text="(i.citizen_account?.profile_completeness || 0) + '%'"></span>
                                </div>
                                <span class="text-xs text-slate-300" x-show="!i.citizen_account">—</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    x-show="i.citizen_account"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200': i.citizen_account?.status === 'Approved',
                                        'bg-amber-50 text-amber-700 ring-amber-200': i.citizen_account?.status === 'Pending Review',
                                        'bg-indigo-50 text-indigo-700 ring-indigo-200': i.citizen_account?.status === 'Under Verification',
                                        'bg-rose-50 text-rose-700 ring-rose-200': i.citizen_account?.status === 'Rejected',
                                    }"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-emerald-500': i.citizen_account?.status === 'Approved',
                                            'bg-amber-500': i.citizen_account?.status === 'Pending Review',
                                            'bg-indigo-500': i.citizen_account?.status === 'Under Verification',
                                            'bg-rose-500': i.citizen_account?.status === 'Rejected',
                                        }"
                                    ></span>
                                    <span x-text="i.citizen_account?.status"></span>
                                </span>
                                <span
                                    x-show="!i.citizen_account"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200 whitespace-nowrap"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Not Registered
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                {{-- No one-click approve/reject from the list — identity
                                     verification always requires opening the full record
                                     (submitted ID, selfie, verification signals) first.
                                     "Review" is the only action available here. --}}
                                <button
                                    x-show="i.citizen_account && ['Pending Review', 'Under Verification'].includes(i.citizen_account.status) && (can('const_citizen_verification', 'verify') || can('const_citizen_verification', 'reject'))"
                                    @click="openIndividual(i)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors"
                                >
                                    <i data-lucide="scan-search" class="w-3.5 h-3.5"></i> Review
                                </button>
                                <span class="text-xs font-medium text-brand-600 hover:underline cursor-pointer" @click="openIndividual(i)" x-show="i.citizen_account && !['Pending Review', 'Under Verification'].includes(i.citizen_account.status)"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openIndividual(i)"
        @keydown.space.prevent="openIndividual(i)">View</span>
                                {{-- No citizen_account at all — this resident was never
                                     verified because they haven't signed up on the portal.
                                     There is nothing for an officer to approve/reject yet,
                                     so this is a status readout, not a button: the reminder
                                     already went out automatically when the record was
                                     added, and re-sends periodically until they register. --}}
                                <span
                                    x-show="!i.citizen_account"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 cursor-default"
                                    title="An automated Citizen Portal registration reminder was sent to this resident's contact on file."
                                >
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Already Notified for Account Verification
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
    </x-ui.table>

    {{-- ── FAMILY QUEUE TABLE ──────────────────────────────────────────────── --}}
    <x-ui.table x-show="cvType === 'Family'" x-cloak>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Family</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Head of Family</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Members</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Verification</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-if="filteredCvFamilies.length === 0">
                        <tr>
                            <td colspan="6" class="py-16 px-4 text-center">
                                <x-ui.empty-state icon="users-round" title="No families match this search." />
                            </td>
                        </tr>
                    </template>
                    <template x-for="f in filteredCvFamilies" :key="f.ref">
                        <tr @click="openFamily(f)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group"
        tabindex="0"
        @keydown.enter.prevent="openFamily(f)"
        @keydown.space.prevent="openFamily(f)">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold shrink-0 text-xs text-blue-700 ring-2 ring-white shadow-sm" x-text="(f.surname || '').substring(0,2).toUpperCase()"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="f.label"></p>
                                        <p class="text-xs text-slate-400 font-mono truncate" x-text="f.ref"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="'Brgy. ' + f.barangay"></td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap hidden xl:table-cell" x-text="f.head_name || '—'"></td>
                            <td class="px-4 py-3 text-center hidden 2xl:table-cell">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="f.member_count"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="verificationBadge(f.verification).class"
                                    x-text="f.verification"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                {{-- No one-click "mark as verified" from the list — verifying
                                     a family requires opening the record and reviewing its
                                     members first. --}}
                                <button
                                    x-show="f.verification !== 'Verified' && can('const_data_quality', 'verify')"
                                    @click="openFamily(f)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors"
                                >
                                    <i data-lucide="scan-search" class="w-3.5 h-3.5"></i> Review
                                </button>
                                <span class="text-xs font-medium text-brand-600 hover:underline cursor-pointer" @click="openFamily(f)" x-show="f.verification === 'Verified' || !can('const_data_quality', 'verify')"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openFamily(f)"
        @keydown.space.prevent="openFamily(f)">View</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
    </x-ui.table>

    {{-- ── HOUSEHOLD QUEUE TABLE ───────────────────────────────────────────── --}}
    <x-ui.table x-show="cvType === 'Household'" x-cloak>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Household</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Head</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Members</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Verification</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-if="filteredCvHouseholds.length === 0">
                        <tr>
                            <td colspan="6" class="py-16 px-4 text-center">
                                <x-ui.empty-state icon="house" title="No households match this search." />
                            </td>
                        </tr>
                    </template>
                    <template x-for="h in filteredCvHouseholds" :key="h.ref">
                        <tr @click="openHousehold(h)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group"
        tabindex="0"
        @keydown.enter.prevent="openHousehold(h)"
        @keydown.space.prevent="openHousehold(h)">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0 ring-2 ring-white shadow-sm"><i data-lucide="house" class="w-4 h-4 text-indigo-600"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="h.label"></p>
                                        <p class="text-xs text-slate-400 font-mono truncate" x-text="h.ref"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="'Brgy. ' + h.barangay"></td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap hidden xl:table-cell" x-text="h.head_name || '—'"></td>
                            <td class="px-4 py-3 text-center hidden 2xl:table-cell">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold" x-text="h.member_count"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="verificationBadge(h.verification).class"
                                    x-text="h.verification"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                {{-- No one-click "mark as verified" from the list — verifying
                                     a household requires opening the record and reviewing its
                                     members first. --}}
                                <button
                                    x-show="h.verification !== 'Verified' && can('const_data_quality', 'verify')"
                                    @click="openHousehold(h)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors"
                                >
                                    <i data-lucide="scan-search" class="w-3.5 h-3.5"></i> Review
                                </button>
                                <span class="text-xs font-medium text-brand-600 hover:underline cursor-pointer" @click="openHousehold(h)" x-show="h.verification === 'Verified' || !can('const_data_quality', 'verify')"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="openHousehold(h)"
        @keydown.space.prevent="openHousehold(h)">View</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
    </x-ui.table>
</div>
