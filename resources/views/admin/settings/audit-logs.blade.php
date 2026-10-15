{{-- ============================= AUDIT LOGS =============================
     Filtered and paged on the server (scope round 2026-10-15): who (name or
     employee ID), which record (its reference or name, type, ID), module,
     action and dates in Esperanza; each entry's before and after, field by
     field; and the filtered log as CSV (um_audit export). The tab searched
     only the hundred entries it had loaded. auditLogTab in
     resources/js/scope-admin-settings.js. --}}
@php
    $auditModules = [
        'dokyu' => 'Dokyu', 'tulong' => 'Tulong', 'sakuna' => 'Sakuna', 'constituents' => 'Constituents',
        'communications' => 'Communications', 'payments' => 'Payments', 'papeles' => 'Papeles', 'appointments' => 'Appointments',
        'user_management' => 'User Management', 'identity' => 'Sign-in & Accounts', 'support' => 'Support', 'reports' => 'Reports',
        'privacy' => 'Privacy', 'settings' => 'Settings',
    ];
@endphp
<div x-data="auditLogTab" x-show="tab === 'audit-logs'" x-cloak x-effect="tab === 'audit-logs' && start()">
    <x-ui.card x-show="denied" x-cloak>
        <p class="text-sm text-slate-600">Your role cannot view the audit log.</p>
    </x-ui.card>

    <div x-show="!denied" class="space-y-3">
        <x-ui.card :padded="false" class="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div>
                    <label for="audit-actor" class="{{ $ui['label'] }}">Person</label>
                    <input id="audit-actor" type="search" x-model="f.actor" @input.debounce.400ms="apply()" placeholder="Name or employee ID" class="{{ $ui['inputSm'] }}">
                </div>
                <div>
                    <label for="audit-target" class="{{ $ui['label'] }}">Record</label>
                    <input id="audit-target" type="search" x-model="f.target" @input.debounce.400ms="apply()" placeholder="Reference or name" class="{{ $ui['inputSm'] }}">
                </div>
                <div>
                    <label for="audit-module" class="{{ $ui['label'] }}">Module</label>
                    <select id="audit-module" x-model="f.module" @change="apply()" class="{{ $ui['select'] }} w-full">
                        <option value="">All modules</option>
                        @foreach($auditModules as $moduleKey => $moduleName)
                            <option value="{{ $moduleKey }}">{{ $moduleName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="audit-action" class="{{ $ui['label'] }}">Action</label>
                    <input id="audit-action" type="search" list="audit-actions" x-model="f.action" @change="apply()" @keydown.enter.prevent="apply()" placeholder="e.g. office.created" class="{{ $ui['inputSm'] }}">
                    <datalist id="audit-actions"><template x-for="a in actions" :key="a"><option :value="a" x-text="actionLabel(a)"></option></template></datalist>
                </div>
                <div>
                    <label for="audit-from" class="{{ $ui['label'] }}">From</label>
                    <input id="audit-from" type="date" x-model="f.from" @change="apply()" :max="f.to || null" class="{{ $ui['inputSm'] }}">
                </div>
                <div>
                    <label for="audit-to" class="{{ $ui['label'] }}">To</label>
                    <input id="audit-to" type="date" x-model="f.to" @change="apply()" :min="f.from || null" class="{{ $ui['inputSm'] }}">
                </div>
                <div x-show="more" x-cloak>
                    <label for="audit-type" class="{{ $ui['label'] }}">Record type</label>
                    <input id="audit-type" type="search" list="audit-types" x-model="f.target_type" @change="apply()" @keydown.enter.prevent="apply()" placeholder="e.g. office" class="{{ $ui['inputSm'] }}">
                    <datalist id="audit-types"><template x-for="t in types" :key="t"><option :value="t"></option></template></datalist>
                </div>
                <div x-show="more" x-cloak>
                    <label for="audit-target-id" class="{{ $ui['label'] }}">Record ID</label>
                    <input id="audit-target-id" type="search" x-model="f.target_id" @change="apply()" @keydown.enter.prevent="apply()" placeholder="The exact ID" class="{{ $ui['inputSm'] }}">
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                <div class="flex items-center gap-3">
                    <button type="button" @click="more = !more" class="text-xs font-medium text-brand-600 hover:underline" :aria-expanded="more ? 'true' : 'false'" x-text="more ? 'Fewer filters' : 'More filters'"></button>
                    <button type="button" x-show="filtered" x-cloak @click="clear()" class="text-xs font-medium text-ink-muted hover:text-ink hover:underline">Clear filters</button>
                </div>
                <x-ui.button variant="secondary" size="sm" icon="download" x-show="canExport" x-bind:disabled="exporting || !!dateProblem" @click="exportCsv()"><span x-text="exporting ? 'Exporting…' : 'Export CSV'"></span></x-ui.button>
                <x-ui.button variant="secondary" size="sm" icon="file-down" x-show="canExport" x-bind:disabled="exportingPdf || !!dateProblem" @click="exportPdf()"><span x-text="exportingPdf ? 'Exporting…' : 'Export PDF'"></span></x-ui.button>
            </div>
            <p x-show="dateProblem" x-cloak x-text="dateProblem" role="alert" class="{{ $ui['error'] }} mt-2"></p>
            <p x-show="canExport && total > 10000" x-cloak class="text-[11px] text-ink-muted mt-2">An export holds the newest 10,000 matching entries; narrow the filters for older ones.</p>
        </x-ui.card>

        <div x-show="loading && !loaded" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
        <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not load the audit log.'" retryExpr="load()" :retryable="true" /></div>

        <div x-show="loaded && !failed" x-cloak class="transition-opacity" :class="loading && 'opacity-60 pointer-events-none'" :aria-busy="loading ? 'true' : 'false'">
            <p class="text-xs text-ink-muted mb-2" x-text="countLine"></p>
            <x-ui.table>
                <thead>
                    <tr class="bg-well/70">
                        <th class="{{ $ui['th'] }} hidden sm:table-cell">When</th>
                        <th class="{{ $ui['th'] }}">Person</th>
                        <th class="{{ $ui['th'] }}">Action</th>
                        <th class="{{ $ui['th'] }} hidden md:table-cell">Module</th>
                        <th class="{{ $ui['th'] }} hidden lg:table-cell">Record</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Details</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="log in rows" :key="log.id">
                        <tr class="hover:bg-white/60 transition-colors cursor-pointer" @click="show(log)">
                            <td class="px-4 py-2.5 text-xs text-ink-muted min-w-[7.5rem] hidden sm:table-cell" x-text="when(log.time) || '—'"></td>
                            <td class="px-4 py-2.5 min-w-[7rem]">
                                <p class="text-slate-700 font-medium" x-text="log.user || (log.actor_type === 'system' ? 'System' : '—')"></p>
                                <p class="text-[11px] text-ink-muted" x-show="log.role" x-text="log.role ? log.role.replace(/_/g, ' ') : ''"></p>
                            </td>
                            <td class="px-4 py-2.5 min-w-[9rem]">
                                <p class="text-slate-700" x-text="actionLabel(log.action)"></p>
                                <p class="text-[11px] text-ink-muted lg:hidden break-words" x-text="targetLine(log)"></p>
                                <p class="text-[11px] text-ink-muted sm:hidden" x-text="when(log.time)"></p>
                            </td>
                            <td class="px-4 py-2.5 hidden md:table-cell"><span class="text-[11px] font-medium text-ink-muted bg-slate-100 px-2 py-0.5 rounded-md whitespace-nowrap" x-text="moduleLabel(log.module)"></span></td>
                            <td class="px-4 py-2.5 hidden lg:table-cell text-xs text-ink-muted break-words max-w-xs" x-text="targetLine(log)"></td>
                            <td class="px-4 py-2.5 text-right">
                                <button type="button" @click.stop="show(log)" :aria-label="'Details of ' + actionLabel(log.action) + ', ' + (when(log.time) || '')" class="p-1.5 rounded-lg text-ink-muted hover:text-brand-600 hover:bg-white/70 transition-colors"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>
            <div x-show="!rows.length" class="surface-glass rounded-[26px] mt-3">
                <x-ui.empty-state icon="scroll-text" title="No entries" description="Nothing in the log matches these filters." />
            </div>
            {{-- Server pages: pageInfo, pageOf, pageCount and turnPage are auditLogTab's. --}}
            <x-admin.pager list="pageInfo" key="audit" />
        </div>
    </div>

    {{-- ---------------- One entry ---------------- --}}
    <x-ui.modal title="Audit Entry" maxWidth="lg">
        <template x-if="sel">
            <div class="space-y-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                    <div><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">When</dt><dd class="text-slate-700" x-text="when(sel.time) || '—'"></dd></div>
                    <div><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Person</dt><dd class="text-slate-700 break-words" x-text="actorLine(sel)"></dd></div>
                    <div><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Action</dt><dd class="text-slate-700 break-words"><span x-text="actionLabel(sel.action)"></span> <span class="font-mono text-[11px] text-ink-muted" x-text="'(' + sel.action + ')'"></span></dd></div>
                    <div><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Module</dt><dd class="text-slate-700" x-text="moduleLabel(sel.module)"></dd></div>
                    <div class="sm:col-span-2"><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Record</dt><dd class="text-slate-700 break-words" x-text="targetLine(sel)"></dd></div>
                    <div x-show="sel.row_count != null"><dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">Rows</dt><dd class="text-slate-700" x-text="sel.row_count"></dd></div>
                </dl>

                <div>
                    <p class="text-xs font-semibold text-ink mb-1.5" x-text="'Before and after' + (changedCount ? ' · ' + changedCount + (changedCount === 1 ? ' field changed' : ' fields changed') : '')"></p>
                    <p x-show="!changes.length" class="text-xs text-ink-muted">This entry records an action without field values.</p>
                    <div x-show="changes.length" class="rounded-2xl border border-brand-600/10 overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-well/70 text-left">
                                    <th scope="col" class="px-3 py-2 font-semibold text-ink-muted">Field</th>
                                    <th scope="col" class="px-3 py-2 font-semibold text-ink-muted">Before</th>
                                    <th scope="col" class="px-3 py-2 font-semibold text-ink-muted">After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-600/[.08]">
                                <template x-for="c in changes" :key="c.field">
                                    <tr :class="c.changed ? 'bg-amber-50/70' : ''">
                                        <th scope="row" class="px-3 py-2 text-left font-medium text-slate-700 align-top">
                                            <span x-text="c.label"></span>
                                            <span x-show="c.changed" class="ml-1 inline-block rounded-full bg-amber-100 px-1.5 py-px text-[10px] font-semibold text-amber-800 whitespace-nowrap">Changed</span>
                                        </th>
                                        <td class="px-3 py-2 align-top break-words min-w-[7rem]" :class="c.changed ? 'text-slate-700' : 'text-ink-muted'" x-text="c.from"></td>
                                        <td class="px-3 py-2 align-top break-words min-w-[7rem]" :class="c.changed ? 'text-ink font-semibold' : 'text-ink-muted'" x-text="c.to"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="contextRows.length">
                    <p class="text-xs font-semibold text-ink mb-1.5">Details</p>
                    <dl class="rounded-2xl bg-slate-50 px-3 py-2 space-y-1">
                        <template x-for="c in contextRows" :key="c.key">
                            <div class="flex flex-wrap gap-x-2 text-xs"><dt class="font-medium text-slate-600" x-text="c.label + ':'"></dt><dd class="text-slate-700 break-all" x-text="c.value"></dd></div>
                        </template>
                    </dl>
                </div>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="open = false">Close</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
