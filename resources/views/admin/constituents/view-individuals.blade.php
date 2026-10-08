{{-- ============================================================
     FILE: view-individuals.blade.php
     Partial: Individuals table — rendered inside constituents.blade.php
     Alpine scope: parent x-data (filteredIndividuals, openIndividual, etc.)
     ============================================================ --}}

<div class="flex flex-col gap-4">

    {{-- ── Result count bar ── --}}
    <div class="flex items-center justify-between px-1">
        <p class="text-sm text-ink-muted">
            Showing
            <span class="font-semibold text-ink" x-text="filteredIndividuals.length"></span>
            of
            <span class="font-semibold text-ink" x-text="individuals.length"></span>
            residents
        </p>
    </div>

    {{-- ── Table card ── --}}
    <x-ui.table>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-well/70">
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">
                        Resident
                    </th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden xl:table-cell">
                        Barangay / Sitio
                    </th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden 2xl:table-cell">
                        Household
                    </th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">
                        Record
                    </th>
                    <th class="text-left px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">
                        Status
                    </th>
                    <th class="text-center px-4 py-3 text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-600/[.08] bg-white">

                {{-- ── Empty state ── --}}
                <template x-if="filteredIndividuals.length === 0">
                    <tr>
                        <td colspan="6" class="py-16 px-4 text-center">
                            <x-ui.empty-state
                                icon="users"
                                title="No residents found"
                                description="Try adjusting your search filters, or add a new resident record."
                            >
                                <template x-if="can('const_resident','create')">
                                    <button
                                        @click="showAddIndividual = true"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl clay-blue text-sm font-medium hover:brightness-95 transition-colors"
                                    >
                                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                                        Add Resident
                                    </button>
                                </template>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                </template>

                {{-- ── Individual rows ── --}}
                <template x-for="r in filteredIndividuals" :key="r.ref">
                    <tr
                        class="hover:bg-white/60 transition-colors cursor-pointer group"
                        @click="openIndividual(r)"

        tabindex="0"
        @keydown.enter.prevent="openIndividual(r)"
        @keydown.space.prevent="openIndividual(r)">

                        {{-- Resident: avatar + name + ID + tags --}}
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="flex-shrink-0 relative">
                                    <div class="relative overflow-hidden w-10 h-10 text-sm rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0"><span x-text="(r.name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></span><img x-show="photoOf(r.ref)" x-cloak :src="photoOf(r.ref)" alt="" class="absolute inset-0 w-full h-full object-cover" x-on:error="$el.style.display='none'"></div>
                                    {{-- Sex indicator dot --}}
                                    <span
                                        class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white flex items-center justify-center text-white"
                                        :class="r.sex === 'Female' ? 'bg-pink-400' : 'bg-blue-400'"
                                        :title="r.sex || '—'"
                                    ></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="font-semibold text-ink text-sm leading-tight truncate group-hover:text-brand-600 transition-colors"
                                        x-text="r.name"
                                    ></p>
                                    <p class="text-xs text-ink-muted mt-0.5 flex items-center gap-1">
                                        <span x-text="'ID: ' + r.ref"></span>
                                        <i x-show="r.citizen_account" data-lucide="smartphone" class="w-3 h-3 text-brand-500" title="Linked to Citizen Portal account"></i>
                                    </p>
                                    {{-- Tag chips --}}
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <template x-for="tag in (r.tags || [])" :key="tag">
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tracking-wide uppercase"
                                                :class="{
                                                    'bg-amber-100 text-amber-700': tag === 'senior' || tag === 'osca',
                                                    'bg-blue-100 text-blue-700': tag === 'pwd',
                                                    'bg-green-100 text-green-700': tag === 'youth',
                                                    'bg-violet-100 text-violet-700': tag === 'child',
                                                    'bg-orange-100 text-orange-700': tag === 'osy',
                                                    'bg-cyan-100 text-cyan-700': tag === '4ps',
                                                    'bg-rose-100 text-rose-700': tag === 'solo_parent',
                                                    'bg-lime-100 text-lime-700': tag === 'farmer',
                                                    'bg-red-100 text-red-700': tag === 'high_risk',
                                                }"
                                                x-text="tag === 'solo_parent' ? 'Solo Parent' : tag === 'high_risk' ? 'High Risk' : tag.toUpperCase()"
                                            ></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Barangay / Sitio --}}
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-body truncate" x-text="'Brgy. ' + r.barangay"></p>
                                <p class="text-xs text-ink-muted mt-0.5 truncate" x-text="r.sitio ? 'Sitio ' + r.sitio : '—'"></p>
                            </div>
                        </td>

                        {{-- Household / Family --}}
                        <td class="px-4 py-3 hidden 2xl:table-cell">
                            <div class="min-w-0">
                                <p class="text-xs text-slate-600 font-medium">
                                    HH: <span class="text-ink-body font-semibold" x-text="r.household?.ref || '—'"></span>
                                </p>
                                <p class="text-xs text-ink-muted mt-0.5">
                                    Fam: <span x-text="r.family?.ref || '—'"></span>
                                </p>
                                <p
                                    class="text-[10px] font-semibold uppercase text-ink-muted mt-1 bg-slate-100 rounded px-1 py-0.5 inline-block"
                                    x-text="r.relation || 'Member'"
                                ></p>
                            </div>
                        </td>

                        {{-- Verification badge --}}
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                :class="verificationBadge(r.verification).class"
                            >
                                <template x-if="r.verification === 'Verified'">
                                    <i data-lucide="shield-check" class="w-3 h-3"></i>
                                </template>
                                <template x-if="r.verification === 'Partially Verified'">
                                    <i data-lucide="shield-half" class="w-3 h-3"></i>
                                </template>
                                <template x-if="r.verification === 'For Validation'">
                                    <i data-lucide="search" class="w-3 h-3"></i>
                                </template>
                                <template x-if="r.verification === 'Missing Documents'">
                                    <i data-lucide="file-x" class="w-3 h-3"></i>
                                </template>
                                <template x-if="r.verification === 'Unverified'">
                                    <i data-lucide="shield-x" class="w-3 h-3"></i>
                                </template>
                                <template x-if="r.verification === 'Rejected'">
                                    <i data-lucide="ban" class="w-3 h-3"></i>
                                </template>
                                <span x-text="r.verification"></span>
                            </span>
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                :class="statusBadge(r.status)"
                                x-text="r.status"
                            ></span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3" @click.stop>
                            <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity focus-within:opacity-100 focus:opacity-100">
                                <button
                                    @click="openIndividual(r)"
                                    title="View resident detail"
                                    class="p-1.5 rounded-md text-ink-muted hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                >
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>

                    </tr>
                </template>

            </tbody>
        </table>
    </x-ui.table>

</div>
