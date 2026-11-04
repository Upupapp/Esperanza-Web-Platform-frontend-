{{-- ============================= DIREKTORYO ============================= --}}
{{-- The directory residents read, kept here (owner, 2026-10-21): it was
     Balita's Offices and Barangays tabs. Laid out like the residents'
     Direktoryo (citizen/directory.blade.php): a search, Municipal Offices |
     Barangays, a count, the cards. Logic: directoryTab in
     resources/js/scope-admin-settings.js. A Barangay Secretary sees only
     Barangays, and only their own. --}}
<div x-show="tab === 'directory'" x-cloak x-data="directoryTab()">
    <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search the directory" type="text" x-model="search" :placeholder="view === 'offices' ? 'Search offices or officials...' : 'Search barangays or punong barangay...'" class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
            </div>
            <div x-show="canOffices && canBarangays" class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto shrink-0">
                <button type="button" @click="view = 'offices'" :aria-pressed="view === 'offices'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="view === 'offices' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                    <i data-lucide="building-2" class="w-3.5 h-3.5"></i>Municipal Offices
                </button>
                <button type="button" @click="view = 'barangays'" :aria-pressed="view === 'barangays'" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="view === 'barangays' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>Barangays
                </button>
            </div>
            <x-ui.button x-show="view === 'offices' && may('comm_directory', 'create')" icon="plus" size="sm" class="shrink-0" @click="openAddOffice()">Add office</x-ui.button>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] text-ink-muted" x-show="!loading && !failed" x-text="countLine"></p>
            <p class="text-[11px] text-ink-muted flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i>Residents see this in Direktoryo, on the website and in the app.</p>
        </div>
    </div>

    <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <x-ui.skeleton class="h-40 rounded-2xl" />
        <x-ui.skeleton class="h-40 rounded-2xl opacity-80" />
        <x-ui.skeleton class="h-40 rounded-2xl opacity-60" />
    </div>
    <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the directory.'" retryExpr="load()" :retryable="true" /></div>

    {{-- Municipal Offices --}}
    <div x-show="!loading && !failed && view === 'offices'">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="o in filteredOffices" :key="o.id">
                <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard flex flex-col">
                    <div class="relative h-20 bg-gradient-to-br from-brand-600 to-navy-800 flex items-center px-4">
                        <span class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center text-white"><i data-lucide="building-2" class="w-5 h-5"></i></span>
                    </div>
                    <div class="p-3.5 flex-1">
                        <p class="text-sm font-semibold text-ink leading-snug mb-2.5" x-text="o.office"></p>
                        <div class="flex items-center gap-2 mb-2" x-show="o.official_name">
                            <div class="w-6 h-6 text-[9px] rounded-full clay-blue font-extrabold flex items-center justify-center shrink-0" x-text="initials(o.official_name)"></div>
                            <span class="text-xs text-slate-600 truncate" x-text="o.official_name + (o.position ? ' — ' + o.position : '')"></span>
                        </div>
                        <p class="text-xs text-ink-muted flex items-center gap-1.5 mb-1" x-show="o.contact"><i data-lucide="phone" class="w-3 h-3 shrink-0"></i><span x-text="o.contact"></span></p>
                        <p class="text-xs text-ink-muted flex items-center gap-1.5 truncate" x-show="o.email"><i data-lucide="mail" class="w-3 h-3 shrink-0"></i><span x-text="o.email"></span></p>
                    </div>
                    <div class="flex border-t border-brand-600/10" x-show="may('comm_directory', 'edit') || may('comm_directory', 'delete')">
                        <button type="button" x-show="may('comm_directory', 'edit')" @click="openEditOffice(o)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                        <button type="button" x-show="may('comm_directory', 'delete')" @click="removing = o" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50/60 py-2.5 transition-colors border-l border-brand-600/10"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Remove</button>
                    </div>
                </div>
            </template>
        </div>
        <div class="flex flex-col items-center text-center py-10" x-show="filteredOffices.length === 0">
            <span class="w-14 h-14 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-6 h-6"></i></span>
            <p class="text-sm font-medium text-slate-600" x-text="offices.length ? 'No offices match your search.' : 'No offices yet.'"></p>
        </div>
    </div>

    {{-- Barangays --}}
    <div x-show="!loading && !failed && view === 'barangays'" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="b in filteredBarangays" :key="b.name">
                <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard flex flex-col">
                    <div class="flex items-center gap-3 p-3.5 pb-3 bg-gradient-to-br from-brand-50/70 to-white">
                        <div class="relative w-12 h-12 shrink-0">
                            <div class="absolute inset-0 rounded-full bg-white border-2 border-gold-400 flex items-center justify-center text-ink-faint"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                            <img :src="b.seal_path" x-show="b.seal_path" x-on:error="$el.style.display='none'" alt="" class="relative w-12 h-12 rounded-full object-cover border-2 border-gold-400">
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink truncate"><span>Brgy.</span> <span x-text="b.name"></span></p>
                            <p class="text-[11px] text-ink-muted truncate" x-text="b.punong_barangay || 'No punong barangay on file'"></p>
                        </div>
                    </div>
                    <div class="p-3.5 pt-2.5 flex-1">
                        <p class="text-xs text-ink-muted flex items-center gap-1.5 mb-1" x-show="b.contact"><i data-lucide="phone" class="w-3 h-3 shrink-0"></i><span x-text="b.contact"></span></p>
                        <p class="text-xs text-ink-muted flex items-center gap-1.5" x-show="b.barangay_secretary"><i data-lucide="user" class="w-3 h-3 shrink-0"></i><span x-text="'Secretary: ' + b.barangay_secretary"></span></p>
                    </div>
                    <button type="button" @click="editBarangay(b)" x-show="may('comm_barangay_directory', 'edit')" class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 border-t border-brand-600/10 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                </div>
            </template>
        </div>
        <div class="flex flex-col items-center text-center py-10" x-show="filteredBarangays.length === 0">
            <span class="w-14 h-14 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-3"><i data-lucide="search-x" class="w-6 h-6"></i></span>
            <p class="text-sm font-medium text-slate-600">No barangays match your search.</p>
        </div>
    </div>

    {{-- Add or edit an office --}}
    <div x-data="{ get open() { return officeOpen; }, set open(v) { officeOpen = v; } }">
        <x-ui.modal :bare="true" maxWidth="md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
                <h3 class="text-base font-extrabold text-ink" x-text="officeMode === 'add' ? 'Add office' : 'Edit office'"></h3>
                <button type="button" aria-label="Close" @click="officeOpen = false" class="w-9 h-9 -mr-1.5 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <x-ui.input name="office_name" label="Office" x-model="officeForm.office" />
                <x-ui.input name="office_official" label="Official Name" x-model="officeForm.official_name" />
                <x-ui.input name="office_position" label="Position" x-model="officeForm.position" />
                <x-ui.input name="office_contact" label="Contact" x-model="officeForm.contact" />
                <x-ui.input name="office_email" type="email" label="Email" x-model="officeForm.email" />
            </div>
            <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10">
                <x-ui.button variant="secondary" size="sm" @click="officeOpen = false">Cancel</x-ui.button>
                <x-ui.button size="sm" icon="check" x-bind:disabled="processing || !officeForm.office.trim()" @click="saveOffice()">Save</x-ui.button>
            </div>
        </x-ui.modal>
    </div>

    {{-- Remove an office, asked first --}}
    <div x-data="{ get open() { return !!removing; }, set open(v) { if (!v) removing = null; } }">
        <x-ui.modal title="Remove Office" maxWidth="sm">
            <p class="text-sm text-ink-body">Remove <span class="font-semibold" x-text="removing?.office"></span> from the directory? Residents will no longer see it in Direktoryo.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="removing = null">Keep it</x-ui.button>
                <x-ui.button variant="danger" size="sm" icon="trash-2" x-bind:disabled="processing" @click="confirmRemove()">Remove</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>

    {{-- Edit a barangay's officials --}}
    <div x-data="{ get open() { return barangayOpen; }, set open(v) { barangayOpen = v; } }">
        <x-ui.modal title="Edit Barangay" maxWidth="md">
            <template x-if="selected">
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 border border-brand-600/10 px-3.5 py-2.5">
                        <i data-lucide="map-pinned" class="w-4 h-4 text-ink-muted shrink-0"></i>
                        <p class="text-sm font-medium text-slate-700" x-text="'Brgy. ' + selected.name"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="brgy-captain">Punong Barangay</label>
                        <input id="brgy-captain" type="text" x-model="selected.punong_barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="brgy-secretary">Barangay Secretary</label>
                        <input id="brgy-secretary" type="text" x-model="selected.barangay_secretary" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="brgy-contact">Contact Number</label>
                        <input id="brgy-contact" type="text" x-model="selected.contact" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body font-mono focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                </div>
            </template>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="barangayOpen = false">Cancel</x-ui.button>
                <x-ui.button size="sm" icon="check" x-bind:disabled="processing" @click="saveBarangay()">Save changes</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
