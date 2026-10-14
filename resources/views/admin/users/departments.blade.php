{{-- ============================= DEPARTMENTS =============================
     The municipal offices staff belong to and services are handled by
     (DepartmentsController, scope round 2026-10-15; um_personnel view,
     create, edit). A rename is carried into everything that names the
     office, in one step. departmentsTab in resources/js/scope-admin-settings.js. --}}
<div x-data="departmentsTab" x-show="tab === 'departments'" x-cloak x-effect="tab === 'departments' && start()">
    <x-ui.card x-show="denied" x-cloak>
        <p class="text-sm text-slate-600">Your role cannot view the departments.</p>
    </x-ui.card>

    <div x-show="!denied" class="space-y-3">
        <div class="surface-glass rounded-[26px] p-3 flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="search" aria-label="Search offices" placeholder="Search offices or heads" class="{{ $ui['search'] }}">
            </div>
            <select x-model="show" aria-label="Show" class="{{ $ui['select'] }}">
                <option value="">All offices</option>
                <option value="on">Active</option>
                <option value="off">Switched off</option>
            </select>
            <p class="text-[11px] text-ink-muted shrink-0" x-show="loaded" x-text="plural(rows.length, 'office', 'offices') + ' · ' + activeCount + ' active' + (offCount ? ' · ' + offCount + ' switched off' : '')"></p>
            <x-ui.button size="sm" icon="plus" x-show="canCreate" @click="create()" class="shrink-0">Add office</x-ui.button>
        </div>
        <div class="{{ $ui['note'] }}">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
            <p>The offices staff accounts are assigned to and services are handled by. Barangay halls are not listed: each barangay works as its own office. The head shown is the one named in the Government Directory.</p>
        </div>

        <div x-show="loading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
        <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not load the departments.'" retryExpr="load()" :retryable="true" /></div>

        <div x-show="loaded && !loading && !failed" x-cloak>
            <x-ui.table>
                <thead>
                    <tr class="bg-well/70">
                        <th class="{{ $ui['th'] }}">Office</th>
                        <th class="{{ $ui['th'] }} hidden md:table-cell">Head</th>
                        <th class="{{ $ui['th'] }} hidden sm:table-cell text-right">Staff</th>
                        <th class="{{ $ui['th'] }} hidden sm:table-cell text-right">Services</th>
                        <th class="{{ $ui['th'] }} hidden sm:table-cell">Status</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="o in shown" :key="o.id">
                        <tr class="hover:bg-white/60 transition-colors">
                            <td class="px-4 py-3 min-w-[10rem]">
                                <p class="text-slate-700 font-medium" x-text="o.name"></p>
                                <p class="text-[11px] text-ink-muted" x-text="o.short ? 'Short name: ' + o.short : 'No short name'"></p>
                                <p class="text-[11px] text-ink-muted sm:hidden" x-text="plural(o.staff, 'staff account', 'staff accounts') + ' · ' + plural(o.services, 'service', 'services')"></p>
                                <p class="text-[11px] text-ink-muted md:hidden" x-text="headLine(o)"></p>
                                <p class="text-[11px] font-semibold sm:hidden" :class="o.active ? 'text-emerald-700' : 'text-slate-500'" x-text="o.active ? 'Active' : 'Switched off'"></p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-xs" :class="o.head ? 'text-slate-700' : 'text-ink-muted'" x-text="headLine(o)"></td>
                            <td class="px-4 py-3 hidden sm:table-cell text-right tabular-nums text-slate-700" x-text="o.staff"></td>
                            <td class="px-4 py-3 hidden sm:table-cell text-right tabular-nums text-slate-700" x-text="o.services"></td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2 py-0.5 ring-1 ring-inset whitespace-nowrap" :class="o.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="o.active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    <span x-text="o.active ? 'Active' : 'Switched off'"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span x-show="canEdit" class="inline-flex items-center gap-2">
                                    <button type="button" @click="toggle(o)" @click.outside="if (confirmToggle === o.id) confirmToggle = null" :disabled="toggling === o.id"
                                            class="text-xs font-medium hover:underline disabled:opacity-50" :class="o.active ? 'text-rose-600' : 'text-brand-600'"
                                            x-text="toggling === o.id ? 'Saving…' : (confirmToggle === o.id ? (o.active ? 'Confirm switch off' : 'Confirm switch on') : (o.active ? 'Switch off' : 'Switch on'))"></button>
                                    <button type="button" @click="edit(o)" :aria-label="'Edit ' + o.name" class="p-1.5 rounded-lg text-ink-muted hover:text-brand-600 hover:bg-white/70 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>
            <div x-show="!shown.length" class="surface-glass rounded-[26px] mt-3">
                <x-ui.empty-state icon="building-2" title="No offices match" description="Change the search or the filter." />
            </div>
        </div>
    </div>

    {{-- ---------------- Add or edit an office ---------------- --}}
    <x-ui.modal title="Office" maxWidth="md">
        <div class="space-y-4">
            <p class="text-xs text-ink-muted" x-text="mode === 'create' ? 'A new municipal office. Staff accounts and services can be assigned to it once it is added.' : 'Editing ' + (sel?.name || '') + '.'"></p>
            <div>
                <label for="dept-name" class="{{ $ui['label'] }}">Office name</label>
                <input id="dept-name" type="text" x-model="form.name" maxlength="160" :aria-invalid="fieldErrors.name ? 'true' : 'false'" placeholder="e.g. Municipal Tourism Office" class="{{ $ui['input'] }}">
                <p x-show="fieldErrors.name" x-text="fieldErrors.name" class="{{ $ui['error'] }}"></p>
            </div>
            <div>
                <label for="dept-short" class="{{ $ui['label'] }}">Short name (optional)</label>
                <input id="dept-short" type="text" x-model="form.short" maxlength="40" placeholder="e.g. MTO" class="{{ $ui['input'] }}">
                <p x-show="fieldErrors.short" x-text="fieldErrors.short" class="{{ $ui['error'] }}"></p>
            </div>
            <label x-show="mode === 'edit'" class="flex items-start gap-3 rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 cursor-pointer">
                <input type="checkbox" x-model="form.active" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Active</span>
                    <span class="block text-xs text-ink-muted mt-0.5">A switched-off office stays on the records that name it, but is no longer offered when assigning staff or services.</span>
                </span>
            </label>
            <div x-show="renaming" x-cloak class="flex items-start gap-2.5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3" role="note">
                <i data-lucide="triangle-alert" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                <p class="text-xs text-amber-800"><span class="font-semibold">Renaming changes the name everywhere it is used.</span> Every service, request, staff account, directory entry, support ticket and appointment schedule (and its bookings) that names this office is updated to the new name, in one step.</p>
            </div>
            <div x-show="switchingOff" x-cloak class="{{ $ui['warn'] }}">
                <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-px"></i>
                <p>Saving switches this office off. Its staff and services keep it until they are moved.</p>
            </div>
            <p x-show="error" x-text="error" role="alert" class="text-xs text-rose-600"></p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" x-bind:disabled="saving || !dirty" @click="save()"><span x-text="saving ? 'Saving…' : (mode === 'create' ? 'Add office' : (renaming ? 'Rename everywhere' : 'Save'))"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
