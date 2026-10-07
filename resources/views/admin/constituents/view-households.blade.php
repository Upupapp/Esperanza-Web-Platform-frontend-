{{-- view-households.blade.php --}}
{{-- Households table view — rendered inside constituents.blade.php Alpine x-data scope --}}

<div class="space-y-4">

    {{-- Header row: count + add button --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-semibold text-ink" x-text="filteredHouseholds.length"></span>
            <span x-text="filteredHouseholds.length === 1 ? 'household' : 'households'"></span>
        </p>
        <button
            @click="showAddHousehold = true; addHouseholdStep = 1; $nextTick(()=>window.renderIcons?.())"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Household
        </button>
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead>
            <tr class="bg-well/70">
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Household</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden xl:table-cell">Barangay / Sitio</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden 2xl:table-cell">Head</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Members</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden 2xl:table-cell">Dwelling</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap hidden 2xl:table-cell">Amenities</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Record</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em]r whitespace-nowrap">Actions</th>
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
            <template x-for="h in filteredHouseholds" :key="h.ref">
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
                                {{-- Flood risk chip --}}
                                <span
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
                        <p class="text-xs text-ink-muted" x-text="h.sitio ? ('Sitio ' + h.sitio) : '—'"></p>
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

                    {{-- Amenities: electricity / water / toilet icons --}}
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <div class="flex items-center justify-center gap-2">
                            <span :title="h.electricity ? 'Has Electricity' : 'No Electricity'">
                                <i data-lucide="zap" class="w-4 h-4 transition-colors" :class="h.electricity ? 'text-amber-500' : 'text-ink-faint'"></i>
                            </span>
                            <span :title="h.water ? 'Has Water Source' : 'No Water Source'">
                                <i data-lucide="droplets" class="w-4 h-4 transition-colors" :class="h.water ? 'text-blue-500' : 'text-ink-faint'"></i>
                            </span>
                            <span :title="h.toilet ? 'Has Toilet Facility' : 'No Toilet Facility'">
                                <i data-lucide="toilet" class="w-4 h-4 transition-colors" :class="h.toilet ? 'text-emerald-500' : 'text-ink-faint'"></i>
                            </span>
                        </div>
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
                        <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity focus-within:opacity-100 focus:opacity-100">
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

    {{-- Footer pagination hint --}}
    <template x-if="filteredHouseholds.length > 0">
        <p class="text-xs text-ink-muted text-right pt-1">
            <span x-text="filteredHouseholds.length"></span> record<span x-text="filteredHouseholds.length===1?'':'s'"></span> shown
        </p>
    </template>

</div>
