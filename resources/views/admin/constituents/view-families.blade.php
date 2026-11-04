{{-- view-families.blade.php --}}
{{-- Families table view — rendered inside constituents.blade.php Alpine x-data scope --}}

<div class="space-y-4">

    {{-- Header row: count + add button --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-semibold text-ink" x-text="filteredFamilies.length"></span>
            <span x-text="filteredFamilies.length === 1 ? 'family' : 'families'"></span>
        </p>
        <button
            @click="showAddFamily = true; addFamilyStep = 1; $nextTick(()=>window.renderIcons?.())"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add family
        </button>
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead>
            <tr class="bg-well/70">
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Family</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden xl:table-cell">Barangay</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden 2xl:table-cell">Household</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap hidden xl:table-cell">Head of Family</th>
                <th class="px-4 py-3 text-center text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Members</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Record</th>
                <th class="px-4 py-3 text-left text-[11.5px] font-bold text-ink-muted uppercase tracking-[.07em] whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-600/[.08]">

            {{-- Empty state row --}}
            <template x-if="filteredFamilies.length === 0">
                <tr>
                    <td colspan="7" class="py-16 px-4 text-center">
                        <x-ui.empty-state
                            icon="users-round"
                            title="No families found"
                            description="Try adjusting your search or filters, or add a new family record."
                        />
                    </td>
                </tr>
            </template>

            {{-- Family rows --}}
            <template x-for="f in paged(filteredFamilies, 'fam')" :key="f.ref">
                <tr
                    class="hover:bg-blue-50/40 cursor-pointer transition-colors group"
                    @click="openFamily(f)"

        tabindex="0"
        @keydown.enter.prevent="openFamily(f)"
        @keydown.space.prevent="openFamily(f)">
                    {{-- Family label + ID + type chip --}}
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center ring-2 ring-white shadow-sm">
                                <span class="text-blue-700 font-bold text-xs" x-text="(f.surname || '').substring(0,2).toUpperCase()"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink truncate" x-text="f.label"></p>
                                <p class="text-xs text-ink-muted font-mono" x-text="f.ref"></p>
                                {{-- Family type chip --}}
                                <span
                                    class="inline-flex items-center mt-0.5 px-1.5 py-0.5 rounded text-xs font-medium"
                                    :class="{
                                        'bg-slate-100 text-slate-600': f.family_type === 'Nuclear',
                                        'bg-purple-100 text-purple-700': f.family_type === 'Extended',
                                        'bg-amber-100 text-amber-700': f.family_type === 'Single Parent'
                                    }"
                                    x-text="f.family_type"
                                ></span>
                            </div>
                        </div>
                    </td>

                    {{-- Barangay --}}
                    <td class="px-4 py-3 hidden xl:table-cell">
                        <p class="text-sm text-ink font-medium" x-text="f.barangay"></p>
                        <p class="text-xs text-ink-muted" x-text="f.sitio ? ('Sitio ' + f.sitio) : '—'"></p>
                    </td>

                    {{-- Household link --}}
                    <td class="px-4 py-3 hidden 2xl:table-cell">
                        <template x-if="f.household_ref">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-600 font-medium">
                                <i data-lucide="house" class="w-3 h-3"></i>
                                <span x-text="f.household_ref"></span>
                            </span>
                        </template>
                        <template x-if="!f.household_ref">
                            <span class="text-xs text-ink-muted italic">Unlinked</span>
                        </template>
                    </td>

                    {{-- Head of Family --}}
                    <td class="px-4 py-3 hidden xl:table-cell">
                        <div class="flex items-center gap-2">
                            <template x-if="f.head_name">
                                <div class="w-8 h-8 text-xs rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="(f.head_name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            </template>
                            <span class="text-sm text-ink" x-text="f.head_name || '—'"></span>
                        </div>
                    </td>

                    {{-- Member count --}}
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold" x-text="f.member_count"></span>
                    </td>

                    {{-- Verification badge --}}
                    <td class="px-4 py-3">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                            :class="verificationBadge(f.verification).class"
                            x-text="verificationBadge(f.verification).label"
                        ></span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3" @click.stop>
                        <div class="flex items-center justify-start gap-1">
                            <button
                                @click="openFamily(f)"
                                title="View family detail"
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
    <x-admin.pager list="filteredFamilies" key="fam" />

    {{-- Footer pagination hint --}}
    <template x-if="filteredFamilies.length > 0">
        <p class="text-xs text-ink-muted text-right pt-1">
            <span x-text="filteredFamilies.length"></span> record<span x-text="filteredFamilies.length===1?'':'s'"></span> shown
        </p>
    </template>

</div>
