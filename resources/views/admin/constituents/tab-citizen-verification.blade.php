{{-- tab-citizen-verification.blade.php — Citizen, Family & Household Verification Queue --}}
{{-- Rendered inside constituents.blade.php's outer Alpine x-data scope. --}}

<div class="space-y-6">

    {{-- ── ENTITY TYPE SWITCHER ───────────────────────────────────────────── --}}
    <div class="inline-flex items-center gap-1 bg-slate-100/70 rounded-xl p-1">
        <template x-for="t in ['Individual', 'Family', 'Household']" :key="t">
            <button
                type="button"
                @click="cvType = t; cvStatusFilter = ''; cvSearch = ''"
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

    {{-- ── SUMMARY STAT CARDS — Individual ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" x-show="cvType === 'Individual'">
        <x-ui.stat-card @click="cvStatusFilter = 'Pending Review'" class="cursor-pointer" label="Pending Review" valueBind="cvCounts.pending" icon="clock" color="orange" :delay="0" />
        <x-ui.stat-card @click="cvStatusFilter = 'Under Verification'" class="cursor-pointer" label="Under Verification" valueBind="cvCounts.underVerification" icon="search" color="brand" :delay="75" />
        <x-ui.stat-card @click="cvStatusFilter = 'Approved'" class="cursor-pointer" label="Approved" valueBind="cvCounts.approved" icon="badge-check" color="green" :delay="150" />
        <x-ui.stat-card @click="cvStatusFilter = 'Rejected'" class="cursor-pointer" label="Rejected" valueBind="cvCounts.rejected" icon="circle-x" color="red" :delay="225" />
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
                <input
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
                <select
                    x-model="cvStatusFilter"
                    class="pl-8 pr-8 py-2 text-sm rounded-lg border border-slate-200 bg-white text-navy-900 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition cursor-pointer"
                >
                    <option value="">All Statuses</option>
                    <template x-if="cvType === 'Individual'">
                        <template x-for="s in ['Pending Review', 'Under Verification', 'Approved', 'Rejected']" :key="s">
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

            <button
                @click="cvSearch = ''; cvStatusFilter = ''"
                x-show="cvSearch !== '' || cvStatusFilter !== ''"
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
                    <span x-show="cvSearch !== '' || cvStatusFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="citizenAccountList.length"></span> total
                    </span>
                </p>
            </template>
            <template x-if="cvType === 'Family'">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-navy-900" x-text="filteredCvFamilies.length"></span>
                    <span x-text="filteredCvFamilies.length === 1 ? 'family' : 'families'"></span>
                    <span x-show="cvSearch !== '' || cvStatusFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="cvFamilyList.length"></span> total
                    </span>
                </p>
            </template>
            <template x-if="cvType === 'Household'">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-navy-900" x-text="filteredCvHouseholds.length"></span>
                    <span x-text="filteredCvHouseholds.length === 1 ? 'household' : 'households'"></span>
                    <span x-show="cvSearch !== '' || cvStatusFilter !== ''" x-cloak>
                        &mdash; filtered from <span class="font-semibold" x-text="cvHouseholdList.length"></span> total
                    </span>
                </p>
            </template>
        </div>
    </x-ui.card>

    {{-- ── INDIVIDUAL QUEUE TABLE ──────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden" x-show="cvType === 'Individual'">
        <x-ui.table>
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
                    <template x-for="i in filteredCitizenAccounts" :key="i.citizen_account.id">
                        <tr @click="openIndividual(i)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold shrink-0 text-xs ring-2 ring-white shadow-sm" x-text="(i.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="i.name"></p>
                                        <p class="text-xs text-slate-400 truncate" x-text="i.citizen_account.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="i.id"></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden 2xl:table-cell" x-text="'Brgy. ' + i.barangay"></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden 2xl:table-cell" x-text="i.citizen_account.registered_at || '—'"></td>
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden shrink-0">
                                        <div class="h-full rounded-full bg-brand-500" :style="'width:' + (i.citizen_account.profile_completeness || 0) + '%'"></div>
                                    </div>
                                    <span class="text-xs text-slate-400" x-text="(i.citizen_account.profile_completeness || 0) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 ring-emerald-200': i.citizen_account.status === 'Approved',
                                        'bg-amber-50 text-amber-700 ring-amber-200': i.citizen_account.status === 'Pending Review',
                                        'bg-indigo-50 text-indigo-700 ring-indigo-200': i.citizen_account.status === 'Under Verification',
                                        'bg-rose-50 text-rose-700 ring-rose-200': i.citizen_account.status === 'Rejected',
                                    }"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-emerald-500': i.citizen_account.status === 'Approved',
                                            'bg-amber-500': i.citizen_account.status === 'Pending Review',
                                            'bg-indigo-500': i.citizen_account.status === 'Under Verification',
                                            'bg-rose-500': i.citizen_account.status === 'Rejected',
                                        }"
                                    ></span>
                                    <span x-text="i.citizen_account.status"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1" x-show="['Pending Review', 'Under Verification'].includes(i.citizen_account.status) && (can('const_citizen_verification', 'verify') || can('const_citizen_verification', 'reject'))">
                                    <button
                                        x-show="can('const_citizen_verification', 'verify')"
                                        @click="verifyCitizenAccount(i)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Approve"
                                    ><i data-lucide="check" class="w-4 h-4"></i></button>
                                    <button
                                        x-show="can('const_citizen_verification', 'reject')"
                                        @click="openIndividual(i)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Review & Reject"
                                    ><i data-lucide="x" class="w-4 h-4"></i></button>
                                </div>
                                <span class="text-xs font-medium text-brand-600 group-hover:underline" x-show="!['Pending Review', 'Under Verification'].includes(i.citizen_account.status)">View</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10" x-show="filteredCitizenAccounts.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="smartphone" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No citizen accounts match this search.</p>
        </div>
    </x-ui.card>

    {{-- ── FAMILY QUEUE TABLE ──────────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden" x-show="cvType === 'Family'" x-cloak>
        <x-ui.table>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Family</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Head of Family</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Members</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Profile</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Verification</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-for="f in filteredCvFamilies" :key="f.id">
                        <tr @click="openFamily(f)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold shrink-0 text-xs text-blue-700 ring-2 ring-white shadow-sm" x-text="(f.surname || '').substring(0,2).toUpperCase()"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="f.label"></p>
                                        <p class="text-xs text-slate-400 font-mono truncate" x-text="'#' + f.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="'Brgy. ' + f.barangay"></td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap hidden xl:table-cell" x-text="f.head_name || '—'"></td>
                            <td class="px-4 py-3 text-center hidden 2xl:table-cell">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="f.member_count"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden shrink-0">
                                        <div class="h-full rounded-full transition-all" :class="completionColor(f.profile_pct)" :style="'width:' + (f.profile_pct||0) + '%'"></div>
                                    </div>
                                    <span class="text-xs text-slate-400" x-text="(f.profile_pct||0) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="verificationBadge(f.verification).class"
                                    x-text="f.verification"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1" x-show="f.verification !== 'Verified' && can('const_citizen_verification', 'verify')">
                                    <button
                                        @click="verifyFamily(f)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Mark as Verified"
                                    ><i data-lucide="check" class="w-4 h-4"></i></button>
                                    <button
                                        @click="openFamily(f)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Review"
                                    ><i data-lucide="eye" class="w-4 h-4"></i></button>
                                </div>
                                <span class="text-xs font-medium text-brand-600 group-hover:underline" x-show="f.verification === 'Verified' || !can('const_citizen_verification', 'verify')">View</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10" x-show="filteredCvFamilies.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="users-round" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No families match this search.</p>
        </div>
    </x-ui.card>

    {{-- ── HOUSEHOLD QUEUE TABLE ───────────────────────────────────────────── --}}
    <x-ui.card class="overflow-hidden" x-show="cvType === 'Household'" x-cloak>
        <x-ui.table>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Household</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Barangay</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Head</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Members</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden 2xl:table-cell">Profile</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Verification</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    <template x-for="h in filteredCvHouseholds" :key="h.id">
                        <tr @click="openHousehold(h)" class="hover:bg-slate-50/80 transition-colors cursor-pointer group">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0 ring-2 ring-white shadow-sm"><i data-lucide="house" class="w-4 h-4 text-indigo-600"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 truncate group-hover:text-brand-600 transition-colors" x-text="h.label"></p>
                                        <p class="text-xs text-slate-400 font-mono truncate" x-text="'#' + h.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden xl:table-cell" x-text="'Brgy. ' + h.barangay"></td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap hidden xl:table-cell" x-text="h.head_name || '—'"></td>
                            <td class="px-4 py-3 text-center hidden 2xl:table-cell">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold" x-text="h.member_count"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap hidden 2xl:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden shrink-0">
                                        <div class="h-full rounded-full transition-all" :class="completionColor(h.profile_pct)" :style="'width:' + (h.profile_pct||0) + '%'"></div>
                                    </div>
                                    <span class="text-xs text-slate-400" x-text="(h.profile_pct||0) + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="verificationBadge(h.verification).class"
                                    x-text="h.verification"
                                ></span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1" x-show="h.verification !== 'Verified' && can('const_citizen_verification', 'verify')">
                                    <button
                                        @click="verifyHousehold(h)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Mark as Verified"
                                    ><i data-lucide="check" class="w-4 h-4"></i></button>
                                    <button
                                        @click="openHousehold(h)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Review"
                                    ><i data-lucide="eye" class="w-4 h-4"></i></button>
                                </div>
                                <span class="text-xs font-medium text-brand-600 group-hover:underline" x-show="h.verification === 'Verified' || !can('const_citizen_verification', 'verify')">View</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </x-ui.table>

        <div class="flex flex-col items-center text-center py-10" x-show="filteredCvHouseholds.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="house" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No households match this search.</p>
        </div>
    </x-ui.card>
</div>
