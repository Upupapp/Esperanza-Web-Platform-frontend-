{{-- view-households.blade.php --}}
{{-- Households table view — rendered inside constituents.blade.php Alpine x-data scope --}}

<div class="space-y-4">

    {{-- Header row: count + add button --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-semibold text-ink" x-text="filteredHouseholds.length"></span>
            <span x-text="filteredHouseholds.length === 1 ? 'household' : 'households'"></span>
        </p>
        {{-- Only a role that may add households sees it (QA sweep 2026-11-18: view-only
             roles filled in the whole form and were refused on Save). --}}
        <button x-show="can('const_household','create')"
            @click="showAddHousehold = true; addHouseholdStep = 1; $nextTick(()=>window.renderIcons?.())"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add household
        </button>
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead>
            <tr class="bg-well/70">
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Household</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden xl:table-cell">Barangay / Sitio</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Head</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Members</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Dwelling</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Amenities</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Record</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-600/[.08]">

            {{-- Empty state row --}}
            <template x-if="filteredHouseholds.length === 0">
                <tr>
                    <td colspan="8" class="py-16 px-4 text-center">
                        <x-ui.empty-state
                            icon="house"
                            title="No households found"
                            description="Try adjusting your search or filters, or register a new household."
                        />
                    </td>
                </tr>
            </template>

            {{-- Household rows --}}
            <template x-for="h in paged(filteredHouseholds, 'hh')" :key="h.ref">
                <tr
                    class="hover:bg-blue-50/40 cursor-pointer transition-colors group"
                    @click="openHousehold(h)"

        tabindex="0"
        @keydown.enter.prevent="openHousehold(h)"
        @keydown.space.prevent="openHousehold(h)">
                    {{-- Household label + ID + flood risk badge --}}
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center ring-2 ring-white shadow-sm">
                                <i data-lucide="house" class="w-4 h-4 text-indigo-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink truncate" x-text="h.label"></p>
                                <p class="text-xs text-ink-muted font-mono" x-text="h.ref"></p>
                                {{-- Flood risk chip, only when the row carries the household's
                                     flood risk (QA sweep 2026-11-18: the list rows have none,
                                     and every household read Unknown Flood Risk). --}}
                                <span x-show="h.flood_risk !== undefined"
                                    class="inline-flex items-center mt-0.5 gap-1 px-1.5 py-0.5 rounded text-xs font-medium"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': h.flood_risk === 'Low',
                                        'bg-amber-100 text-amber-700': h.flood_risk === 'Medium',
                                        'bg-rose-100 text-rose-700': h.flood_risk === 'High'
                                    }"
                                >
                                    <i data-lucide="waves-horizontal" class="w-3 h-3"></i>
                                    <span x-text="(h.flood_risk || 'Unknown') + ' Flood Risk'"></span>
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- Barangay / Sitio --}}
                    <td class="px-4 py-3 hidden xl:table-cell">
                        <p class="text-sm text-ink font-medium" x-text="h.barangay"></p>
                        {{-- The street address too, when the row carries it (#35). --}}
                        <p class="text-xs text-ink-muted truncate max-w-[16rem]" :title="h.address || ''" x-text="[h.sitio ? ('Sitio ' + h.sitio) : '', h.address || ''].filter(Boolean).join(' · ') || '—'"></p>
                    </td>

                    {{-- Head --}}
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <div class="flex items-center gap-2">
                            <template x-if="h.head_name">
                                <div class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(h.head_name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            </template>
                            <span class="text-sm text-ink" x-text="h.head_name || '—'"></span>
                        </div>
                    </td>

                    {{-- Member count --}}
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="h.member_count"></span>
                    </td>

                    {{-- Dwelling type --}}
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <p class="text-sm text-ink leading-tight" x-text="h.dwelling_type || '—'"></p>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="h.housing_tenure || ''"></p>
                    </td>

                    {{-- Amenities: electricity / water / toilet icons, read from the
                         household's words (None is not available), and only when the
                         row carries them: a dash, not 'No Electricity', otherwise. --}}
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <div x-show="h.electricity !== undefined || h.water !== undefined || h.toilet !== undefined" class="flex items-center justify-center gap-2">
                            <span :title="'Electricity: ' + (h.electricity || 'not recorded')">
                                <i data-lucide="zap" class="w-4 h-4 transition-colors" :class="hasAmenity(h.electricity) ? 'text-amber-500' : 'text-ink-faint'"></i>
                            </span>
                            <span :title="'Water source: ' + (h.water || 'not recorded')">
                                <i data-lucide="droplets" class="w-4 h-4 transition-colors" :class="hasAmenity(h.water) ? 'text-blue-500' : 'text-ink-faint'"></i>
                            </span>
                            <span :title="'Toilet facility: ' + (h.toilet || 'not recorded')">
                                <i data-lucide="toilet" class="w-4 h-4 transition-colors" :class="hasAmenity(h.toilet) ? 'text-emerald-500' : 'text-ink-faint'"></i>
                            </span>
                        </div>
                        <p x-show="h.electricity === undefined && h.water === undefined && h.toilet === undefined" class="text-center text-sm text-ink-faint">—</p>
                    </td>

                    {{-- Verification badge --}}
                    <td class="px-4 py-3">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                            :class="verificationBadge(h.verification).class"
                            x-text="verificationBadge(h.verification).label"
                        ></span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3" @click.stop>
                        <div class="flex items-center justify-start gap-1">
                            <button
                                @click="openHousehold(h)"
                                title="View household detail"
                                class="p-1.5 rounded-md text-ink-muted hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            >
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>

        </tbody>
    </x-ui.table>
    <x-admin.pager list="filteredHouseholds" key="hh" />

    {{-- Footer pagination hint --}}
    <template x-if="filteredHouseholds.length > 0">
        <p class="text-xs text-ink-muted text-right pt-1">
            <span x-text="filteredHouseholds.length"></span> record<span x-text="filteredHouseholds.length===1?'':'s'"></span> shown
        </p>
    </template>

</div>
