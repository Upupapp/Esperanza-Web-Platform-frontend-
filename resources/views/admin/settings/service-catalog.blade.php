{{-- ============================= SERVICE CATALOG =============================
     Every service, offered or not (GET /admin/settings/catalog: the tab read
     the public list, which hides switched-off services), and one editor per
     service: its details (name, office, category, offered), its rules
     published as a new version (fee, time, required documents, its workflow
     - each step with who is responsible and how long it takes - and the
     request form's questions), and its history (scope round 2026-10-15;
     workflow, questions and new services 2026-10-17). Logic: catalogEditor
     in resources/js/scope-admin-settings.js. --}}
<div x-data="catalogEditor" x-show="tab === 'service-catalog'" x-cloak x-effect="tab === 'service-catalog' && start()">
    <x-ui.card x-show="denied" x-cloak>
        <p class="text-sm text-slate-600">Your role cannot view the Service Catalog.</p>
    </x-ui.card>

    <div x-show="!denied" class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="search" aria-label="Search services" placeholder="Search by name, office or category" class="{{ $ui['search'] }}">
            </div>
            <select x-model="kind" aria-label="Kind of service" class="{{ $ui['select'] }}">
                <option value="">Dokyu and Tulong</option>
                <option value="dokyu">Dokyu (documents)</option>
                <option value="tulong">Tulong (assistance)</option>
            </select>
            <select x-model="office" aria-label="Office" class="{{ $ui['select'] }} max-w-full sm:max-w-[15rem]">
                <option value="">All offices</option>
                <template x-for="o in officeFilterChoices" :key="o"><option :value="o" x-text="o"></option></template>
            </select>
            <select x-model="offered" aria-label="Offered or switched off" class="{{ $ui['select'] }}">
                <option value="">Offered or not</option>
                <option value="on">Offered</option>
                <option value="off">Switched off</option>
            </select>
        </div>

        {{-- A new service starts switched off, to be finished first (2026-10-17). --}}
        <div x-show="canEdit && loaded" x-cloak class="flex justify-end">
            <x-ui.button size="sm" icon="plus" @click="startAdding()">Add a service</x-ui.button>
        </div>
        <div x-show="!canEdit" class="{{ $ui['lock'] }}">
            <i data-lucide="lock" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <p><span class="font-semibold text-slate-600">View only.</span> The Service Catalog sets what every resident pays and submits, so only roles with Service Catalog editing can change it.</p>
        </div>

        <div x-show="loading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
        <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not load the service catalog.'" retryExpr="load()" :retryable="true" /></div>

        <div x-show="loaded && !loading && !failed" x-cloak>
            <p class="text-xs text-ink-muted mb-2" x-text="summary"></p>
            <x-ui.table>
                <thead>
                    <tr class="bg-well/70">
                        <th class="{{ $ui['th'] }}">Service</th>
                        <th class="{{ $ui['th'] }} hidden md:table-cell">Office</th>
                        <th class="{{ $ui['th'] }} hidden sm:table-cell">Fee and time</th>
                        <th class="{{ $ui['th'] }} hidden lg:table-cell">Version</th>
                        <th class="{{ $ui['th'] }}">Status</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="s in shown" :key="s.key">
                        <tr class="hover:bg-white/60 transition-colors cursor-pointer group" @click="edit(s)">
                            <td class="px-4 py-3 min-w-[11rem]">
                                <p class="text-slate-700 font-medium group-hover:text-brand-600 transition-colors" x-text="s.name"></p>
                                <p class="text-[11px] text-ink-muted" x-text="kindLabel(s.type) + (s.category ? ' · ' + s.category : '')"></p>
                                <p class="text-[11px] text-ink-muted md:hidden" x-text="s.office"></p>
                                <p class="text-[11px] text-ink-muted sm:hidden" x-text="(s.fee || 'No fee set') + (s.days ? ' · ' + s.days : '')"></p>
                            </td>
                            <td class="px-4 py-3 text-ink-muted hidden md:table-cell" x-text="s.office"></td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <p class="text-slate-700" x-text="s.fee || '—'"></p>
                                <p class="text-[11px] text-ink-muted" x-text="s.days || ''"></p>
                            </td>
                            <td class="px-4 py-3 text-[11px] text-ink-muted hidden lg:table-cell" x-text="'v' + (s.version || 1) + ' · ' + plural(s.requirements, 'document', 'documents') + ' · ' + plural(s.steps, 'step', 'steps')"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2 py-0.5 ring-1 ring-inset whitespace-nowrap" :class="s.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="s.active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    <span x-text="s.active ? 'Offered' : 'Switched off'"></span>
                                </span>
                                <span x-show="s.policy_blocked" class="block mt-1 text-[10.5px] font-medium text-amber-700">Awaiting LGU ruling</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click.stop="edit(s)" :aria-label="(canEdit ? 'Edit ' : 'View ') + s.name" class="p-1.5 rounded-lg text-ink-muted hover:text-brand-600 hover:bg-white/70 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>
            <div x-show="!shown.length" class="surface-glass rounded-[26px] mt-3">
                <x-ui.empty-state icon="search-x" title="No services match" description="Change the search or the filters." />
            </div>
        </div>
    </div>

    {{-- ---------------- One service ---------------- --}}
    <x-ui.modal title="Service Details" maxWidth="xl">
        <div x-show="detailLoading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-10" /><x-ui.skeleton class="h-40 opacity-80" /></div>
        <div x-show="detailFailed && !detailLoading" x-cloak><x-ui.error-state messageExpr="'Could not load this service.'" retryExpr="loadDetail()" :retryable="true" /></div>
        <template x-if="svc && !detailLoading">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-base font-bold text-ink break-words" x-text="svc.name"></p>
                        <p class="text-[11px] text-ink-muted font-mono break-all" x-text="svc.key"></p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-brand-50 text-brand-700" x-text="kindLabel(svc.type)"></span>
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-slate-100 text-slate-600" x-text="'Version ' + (svc.current?.version || 1)"></span>
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5" :class="svc.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="svc.active ? 'Offered' : 'Switched off'"></span>
                    </div>
                </div>

                <div x-show="svc.policy_blocked" class="{{ $ui['warn'] }}">
                    <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-px"></i>
                    <p><span class="font-semibold">Awaiting an LGU ruling.</span> <span x-text="svc.policy_gap_note"></span> Its fee, documents and steps cannot change until then; its details can.</p>
                </div>
                <div x-show="!rulesShapeOk" class="{{ $ui['note'] }}">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                    <p>This service's documents or steps are stored in a shape this screen does not edit, so they are shown read only.</p>
                </div>

                <nav aria-label="Service sections" class="flex gap-1 overflow-x-auto scrollbar-thin surface-pressed-sm rounded-2xl p-1">
                    @foreach([['details', 'info', 'Details'], ['fee', 'banknote', 'Fee & time'], ['documents', 'file-check', 'Required documents'], ['steps', 'workflow', 'Workflow'], ['form', 'text-cursor-input', 'Request form'], ['history', 'history', 'History']] as [$sectionKey, $sectionIcon, $sectionLabel])
                        <button type="button" @click="showSection('{{ $sectionKey }}')" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-xl whitespace-nowrap transition-colors" :class="section === '{{ $sectionKey }}' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'" :aria-current="section === '{{ $sectionKey }}' ? 'true' : null"><i data-lucide="{{ $sectionIcon }}" class="w-3.5 h-3.5"></i>{{ $sectionLabel }}</button>
                    @endforeach
                </nav>

                {{-- Details: not versioned; a request keeps the office it was filed with. --}}
                <section x-show="section === 'details'" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="svc-name" class="{{ $ui['label'] }}">Service name</label>
                            <input id="svc-name" type="text" x-model="details.name" maxlength="160" :disabled="!canEdit" class="{{ $ui['input'] }}">
                        </div>
                        <div>
                            <label for="svc-office" class="{{ $ui['label'] }}">Responsible office</label>
                            <template x-if="!isBarangayService">
                                <select id="svc-office" x-model="details.office" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                    <template x-for="o in officeOptions" :key="o"><option :value="o" x-text="o" :selected="o === details.office"></option></template>
                                </select>
                            </template>
                            <template x-if="isBarangayService">
                                <p class="text-sm text-slate-700 py-2.5 px-4 rounded-2xl bg-slate-50">Barangay Office (each barangay's own hall)</p>
                            </template>
                            <p class="text-[11px] text-ink-muted mt-1" x-text="isBarangayService ? 'A barangay service stays with the barangays: it cannot move to a municipal office.' : 'From the Departments list under User Management.'"></p>
                        </div>
                        <div>
                            <label for="svc-category" class="{{ $ui['label'] }}">Category</label>
                            <input id="svc-category" type="text" list="svc-categories" x-model="details.category" maxlength="80" :disabled="!canEdit" class="{{ $ui['input'] }}">
                            <datalist id="svc-categories"><template x-for="c in categoryChoices" :key="c"><option :value="c"></option></template></datalist>
                        </div>
                    </div>
                    <label class="flex items-start gap-3 rounded-2xl border border-brand-600/10 bg-white/60 p-3.5" :class="canEdit ? 'cursor-pointer' : 'cursor-not-allowed opacity-80'">
                        <input type="checkbox" x-model="details.active" :disabled="!canEdit" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span>
                            <span class="block text-sm font-medium text-slate-700">Offered to residents</span>
                            <span class="block text-xs text-ink-muted mt-0.5">A switched-off service takes no new requests and leaves the portals' service lists. Requests already filed carry on.</span>
                        </span>
                    </label>
                    <div x-show="svc.active && !details.active" class="{{ $ui['warn'] }}">
                        <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-px"></i>
                        <p>Saving switches this service off for residents.</p>
                    </div>
                    <p x-show="detailsError" x-text="detailsError" role="alert" class="{{ $ui['error'] }}"></p>
                </section>

                {{-- Fee and time: part of the version. --}}
                <section x-show="section === 'fee'" class="space-y-3">
                    <p class="text-xs text-ink-muted">The fee, time, documents and steps are published together as version <span x-text="nextVersion"></span>. Requests already filed keep the version they were filed under.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="svc-fee" class="{{ $ui['label'] }}">Fee</label>
                            <input id="svc-fee" type="text" x-model="rules.fee" maxlength="120" :disabled="rulesLocked" :aria-invalid="feeChanged && feeError ? 'true' : 'false'" aria-describedby="svc-fee-hint" class="{{ $ui['input'] }}">
                            <p id="svc-fee-hint" class="text-[11px] text-ink-muted mt-1">Free, an amount such as ₱155.00, or a rule such as “₱5.00 base + ₱1.00 per ₱1,000 income”.</p>
                            <p x-show="feeChanged && feeError" x-text="feeError" class="{{ $ui['error'] }}"></p>
                        </div>
                        <div>
                            <label for="svc-days" class="{{ $ui['label'] }}">Processing time</label>
                            <input id="svc-days" type="text" x-model="rules.days" maxlength="120" placeholder="e.g. 1-2 working days" :disabled="rulesLocked" class="{{ $ui['input'] }}">
                        </div>
                    </div>
                </section>

                {{-- Required documents and processing steps: the same list editor. --}}
                @foreach([['documents', 'requirements', 'document', 'newReq', 'addRequirement', 'What a resident attaches or brings, in the order the portals list them.', 'No documents required.', 'Add a required document']] as [$listSection, $listKey, $listNoun, $listInput, $listAdd, $listHelp, $listEmpty, $listPlaceholder])
                    <section x-show="section === '{{ $listSection }}'" class="space-y-2">
                        <p class="text-xs text-ink-muted">{{ $listHelp }}</p>
                        <ol class="space-y-1.5">
                            <template x-for="(entry, i) in rules.{{ $listKey }}" :key="'{{ $listKey }}-' + i">
                                <li class="flex items-center gap-2 rounded-xl bg-slate-50 px-2.5 py-1.5 focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-100">
                                    <span class="w-5 shrink-0 text-right text-[11px] font-semibold text-ink-muted" x-text="(i + 1) + '.'"></span>
                                    <template x-if="typeof entry !== 'object' || entry === null">
                                        <input type="text" x-model="rules.{{ $listKey }}[i]" :disabled="rulesLocked" :aria-label="'{{ ucfirst($listNoun) }} ' + (i + 1)" class="flex-1 min-w-0 bg-transparent border-0 p-0 py-1 text-sm text-slate-700 focus:outline-none focus:ring-0 disabled:opacity-70">
                                    </template>
                                    {{-- Asked only for some answers: the wording edits, the condition shows. --}}
                                    <template x-if="entry && typeof entry === 'object'">
                                        <span class="flex-1 min-w-0" data-conditional-requirement>
                                            <input type="text" x-model="entry.label" :disabled="rulesLocked" :aria-label="'{{ ucfirst($listNoun) }} ' + (i + 1)" class="w-full bg-transparent border-0 p-0 py-1 text-sm text-slate-700 focus:outline-none focus:ring-0 disabled:opacity-70">
                                            <span class="block text-[11px] text-brand-700" x-text="reqCondition(entry)"></span>
                                        </span>
                                    </template>
                                    <span class="flex items-center shrink-0" x-show="!rulesLocked">
                                        <button type="button" @click="move('{{ $listKey }}', i, -1)" :disabled="i === 0" :aria-label="'Move {{ $listNoun }} ' + (i + 1) + ' up'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="move('{{ $listKey }}', i, 1)" :disabled="i === rules.{{ $listKey }}.length - 1" :aria-label="'Move {{ $listNoun }} ' + (i + 1) + ' down'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="remove('{{ $listKey }}', i)" :aria-label="'Remove {{ $listNoun }} ' + (i + 1)" class="{{ $ui['iconBtnDanger'] }}"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </span>
                                </li>
                            </template>
                        </ol>
                        <p x-show="!rules.{{ $listKey }}.length" class="text-xs text-ink-muted px-1">{{ $listEmpty }}</p>
                        <div class="flex items-center gap-2 pt-1" x-show="!rulesLocked">
                            <label for="svc-new-{{ $listSection }}" class="sr-only">{{ $listPlaceholder }}</label>
                            <input id="svc-new-{{ $listSection }}" type="text" x-model="{{ $listInput }}" @keydown.enter.prevent="{{ $listAdd }}()" placeholder="{{ $listPlaceholder }}…" class="{{ $ui['inputSm'] }} flex-1 min-w-0">
                            <x-ui.button variant="secondary" size="sm" icon="plus" @click="{{ $listAdd }}()">Add</x-ui.button>
                        </div>
                    </section>
                @endforeach

                {{-- The workflow (2026-10-17): the Citizen's Charter steps, who is
                     responsible, how long each takes. A request filed under it
                     shows these steps; officers mark the office steps done, and
                     Approve and Release wait for the office steps before them. --}}
                <section x-show="section === 'steps'" class="space-y-2" data-testid="workflow-editor">
                    <p class="text-xs text-ink-muted">Each step of the service, first to last, as residents see it: who is responsible and how many working days it takes (the Citizen's Charter). Officers mark <span class="font-semibold">office steps</span> done on each request; Approve and Release wait for the office steps before them.</p>
                    <div x-show="svc && !tracked" class="{{ $ui['note'] }}">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
                        <p>Requests of the current version show these steps as a plain list. From the version you publish here, each request tracks them.</p>
                    </div>
                    <ol class="space-y-2">
                        <template x-for="(st, i) in rules.workflow" :key="'wf-' + i">
                            <li class="rounded-2xl border border-brand-600/10 bg-white/70 p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 shrink-0 text-right text-xs font-bold text-ink-muted" x-text="(i + 1) + '.'"></span>
                                    <input type="text" x-model="st.name" maxlength="120" :disabled="rulesLocked" :aria-label="'Step ' + (i + 1) + ' name'" class="{{ $ui['inputSm'] }} flex-1 min-w-0">
                                    <span class="flex items-center shrink-0" x-show="!rulesLocked">
                                        <button type="button" @click="move('workflow', i, -1)" :disabled="i === 0" :aria-label="'Move step ' + (i + 1) + ' up'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="move('workflow', i, 1)" :disabled="i === rules.workflow.length - 1" :aria-label="'Move step ' + (i + 1) + ' down'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="remove('workflow', i)" :aria-label="'Remove step ' + (i + 1)" class="{{ $ui['iconBtnDanger'] }}"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pl-8">
                                    <div>
                                        <label :for="'wf-kind-' + i" class="{{ $ui['label'] }}">Kind</label>
                                        <select :id="'wf-kind-' + i" x-model="st.kind" :disabled="rulesLocked" class="{{ $ui['inputSm'] }} w-full">
                                            <template x-for="k in stepKinds" :key="k[0]"><option :value="k[0]" x-text="k[1]" :selected="k[0] === st.kind"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label :for="'wf-who-' + i" class="{{ $ui['label'] }}">Responsible</label>
                                        <input :id="'wf-who-' + i" type="text" list="wf-responsible" x-model="st.responsible" maxlength="120" :disabled="rulesLocked" placeholder="e.g. Barangay Secretary" class="{{ $ui['inputSm'] }} w-full">
                                    </div>
                                    <div>
                                        <label :for="'wf-days-' + i" class="{{ $ui['label'] }}">Working days</label>
                                        <input :id="'wf-days-' + i" type="number" min="0" max="365" inputmode="numeric" x-model="st.days" :disabled="rulesLocked" placeholder="—" class="{{ $ui['inputSm'] }} w-full">
                                    </div>
                                </div>
                                <p class="pl-8 text-[11px] text-ink-muted" x-text="stepKindHelp(st.kind)"></p>
                            </li>
                        </template>
                    </ol>
                    <datalist id="wf-responsible"><template x-for="r in responsibleChoices" :key="r"><option :value="r"></option></template></datalist>
                    <div class="flex items-center gap-2 pt-1" x-show="!rulesLocked">
                        <label for="svc-new-step" class="sr-only">Add an office step</label>
                        <input id="svc-new-step" type="text" x-model="newStep" @keydown.enter.prevent="addStep()" placeholder="Add an office step, e.g. Site inspection…" class="{{ $ui['inputSm'] }} flex-1 min-w-0">
                        <x-ui.button variant="secondary" size="sm" icon="plus" @click="addStep()">Add</x-ui.button>
                    </div>
                </section>

                {{-- The request form's questions (2026-10-17): published with the
                     version; the website and the app draw the form from them. --}}
                <section x-show="section === 'form'" class="space-y-2" data-testid="form-editor">
                    <p class="text-xs text-ink-muted">The questions residents answer when they file. Published with the version: the website and the app draw the form from them. Ask only what the office needs.</p>
                    <ol class="space-y-2">
                        <template x-for="(f, i) in rules.fields" :key="'q-' + i">
                            <li class="rounded-2xl border border-brand-600/10 bg-white/70 p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 shrink-0 text-right text-xs font-bold text-ink-muted" x-text="(i + 1) + '.'"></span>
                                    <input type="text" x-model="f.label" maxlength="160" :disabled="rulesLocked" :aria-label="'Question ' + (i + 1) + ' wording'" placeholder="The question, e.g. Date of birth" class="{{ $ui['inputSm'] }} flex-1 min-w-0">
                                    <span class="flex items-center shrink-0" x-show="!rulesLocked">
                                        <button type="button" @click="moveQuestion(i, -1)" :disabled="i === 0" :aria-label="'Move question ' + (i + 1) + ' up'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="moveQuestion(i, 1)" :disabled="i === rules.fields.length - 1" :aria-label="'Move question ' + (i + 1) + ' down'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                                        <button type="button" @click="remove('fields', i)" :aria-label="'Remove question ' + (i + 1)" class="{{ $ui['iconBtnDanger'] }}"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </span>
                                </div>
                                <div class="pl-8 grid grid-cols-1 sm:grid-cols-2 gap-2" x-show="!isKept(f)">
                                    <div>
                                        <label :for="'q-kind-' + i" class="{{ $ui['label'] }}">Answer</label>
                                        <select :id="'q-kind-' + i" x-model="f.type" :disabled="rulesLocked" class="{{ $ui['inputSm'] }} w-full">
                                            <template x-for="k in questionKinds" :key="k[0]"><option :value="k[0]" x-text="k[1]" :selected="k[0] === f.type"></option></template>
                                        </select>
                                    </div>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 sm:pt-5">
                                        <input type="checkbox" x-model="f.required" :disabled="rulesLocked" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200"> Required
                                    </label>
                                    <div class="sm:col-span-2" x-show="hasOptions(f)">
                                        <label :for="'q-opts-' + i" class="{{ $ui['label'] }}">Choices, one per line</label>
                                        <textarea :id="'q-opts-' + i" x-model="f.optionsText" rows="3" :disabled="rulesLocked" class="{{ $ui['inputSm'] }} w-full"></textarea>
                                    </div>
                                </div>
                                <p class="pl-8 text-[11px] text-ink-muted" x-show="isKept(f)" x-text="'Kept as it is: ' + keptLabel(f) + '. Its wording can change.'"></p>
                            </li>
                        </template>
                    </ol>
                    <p x-show="!rules.fields.length" class="text-xs text-ink-muted px-1">No questions: residents write the purpose and attach the documents.</p>
                    <div class="pt-1" x-show="!rulesLocked">
                        <x-ui.button variant="secondary" size="sm" icon="plus" @click="addQuestion()">Add a question</x-ui.button>
                    </div>
                    <p x-show="entitlement" class="text-xs text-slate-600"><span class="font-semibold">Entitlement:</span> <span x-text="entitlement"></span></p>
                </section>

                <p x-show="rulesError && ['fee', 'documents', 'steps', 'form'].includes(section)" x-text="rulesError" role="alert" class="{{ $ui['error'] }}"></p>

                {{-- History: the published versions, and detail changes from the audit log. --}}
                <section x-show="section === 'history'" class="space-y-3">
                    <p class="text-xs text-ink-muted">Each published version, newest first. A version is in force from the moment it is published, for requests filed from then on.</p>
                    <ol class="space-y-2">
                        <template x-for="v in history" :key="'v-' + v.version">
                            <li class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-ink" x-text="'Version ' + v.version"></p>
                                    <span x-show="v.current" class="text-[10.5px] font-semibold rounded-full px-2 py-0.5 bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">Current</span>
                                </div>
                                <p class="text-[11px] text-ink-muted mt-0.5" x-text="'In force from ' + (when(v.published_at) || 'the start') + ' · ' + v.by"></p>
                                <ul class="mt-1.5 space-y-0.5 text-xs text-slate-600 list-disc pl-4">
                                    <template x-for="(c, ci) in v.changes" :key="ci"><li x-text="c"></li></template>
                                </ul>
                            </li>
                        </template>
                    </ol>
                    <p x-show="!history.length" class="text-xs text-ink-muted">No versions recorded.</p>

                    <div x-show="canSeeLog" class="pt-1">
                        <p class="text-xs font-semibold text-ink mb-1.5">Changes to the name, office, category and availability</p>
                        <p x-show="logLoading" class="text-xs text-ink-muted">Loading…</p>
                        <p x-show="logFailed && !logLoading" class="text-xs text-rose-600">These changes could not be loaded. <button type="button" class="underline" @click="loadLog()">Try again</button></p>
                        <p x-show="logLoaded && !log.length" class="text-xs text-ink-muted">None recorded.</p>
                        <ul class="space-y-1.5">
                            <template x-for="entry in log" :key="entry.id">
                                <li class="rounded-xl bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] text-ink-muted" x-text="when(entry.time) + ' · ' + entry.user"></p>
                                    <template x-for="fd in entry.fields" :key="fd.field">
                                        <p class="text-xs text-slate-700 break-words"><span class="font-medium" x-text="fd.label + ':'"></span> <span x-text="fd.from"></span> → <span x-text="fd.to"></span></p>
                                    </template>
                                </li>
                            </template>
                        </ul>
                    </div>
                </section>
            </div>
        </template>

        <x-slot:footer>
            <p class="mr-auto hidden sm:block text-[11px] font-medium text-amber-700" x-show="detailsDirty || rulesDirty" x-cloak>Unsaved changes</p>
            <x-ui.button variant="secondary" size="sm" @click="open = false">Close</x-ui.button>
            <x-ui.button size="sm" icon="check" x-show="canEdit && svc && section === 'details'" x-bind:disabled="savingDetails || !detailsDirty" @click="saveDetails()"><span x-text="savingDetails ? 'Saving…' : 'Save details'"></span></x-ui.button>
            <x-ui.button size="sm" icon="upload" x-show="svc && !rulesLocked && ['fee', 'documents', 'steps', 'form'].includes(section)" x-bind:disabled="publishing || !rulesDirty" @click="publish()"><span x-text="publishing ? 'Publishing…' : 'Publish version ' + nextVersion"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ---------------- Adding a service (2026-10-17) ---------------- --}}
    <div x-data="{ get open() { return adding; }, set open(v) { if (!creating) adding = v; } }">
        <x-ui.modal title="Add a service" maxWidth="lg">
            <form @submit.prevent="createService()" class="space-y-3" novalidate data-testid="add-service">
                <p class="text-xs text-ink-muted">It starts <span class="font-semibold">switched off</span>, with a simple form and workflow. Finish its questions, documents and steps, then switch it on under Details.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="new-svc-type" class="{{ $ui['label'] }}">Kind</label>
                        <select id="new-svc-type" x-model="draft.type" @change="draft.permissions_like = ''; suggestLike()" class="{{ $ui['input'] }}">
                            <option value="dokyu">Dokyu (a document)</option>
                            <option value="tulong">Tulong (assistance)</option>
                        </select>
                    </div>
                    <div>
                        <label for="new-svc-office" class="{{ $ui['label'] }}">Responsible office</label>
                        <select id="new-svc-office" x-model="draft.office" @change="suggestLike()" class="{{ $ui['input'] }}">
                            <option value="">Choose…</option>
                            <template x-for="o in draftOffices" :key="o"><option :value="o" x-text="o === 'Barangay Office' ? 'Barangay Office (each barangay’s hall)' : o"></option></template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="new-svc-name" class="{{ $ui['label'] }}">Service name</label>
                        <input id="new-svc-name" type="text" x-model="draft.name" maxlength="160" placeholder="e.g. Certificate of Good Moral Character" class="{{ $ui['input'] }}">
                    </div>
                    <div>
                        <label for="new-svc-category" class="{{ $ui['label'] }}">Category</label>
                        <input id="new-svc-category" type="text" list="svc-categories" x-model="draft.category" maxlength="80" class="{{ $ui['input'] }}">
                    </div>
                    <div>
                        <label for="new-svc-fee" class="{{ $ui['label'] }}">Fee</label>
                        <input id="new-svc-fee" type="text" x-model="draft.fee" maxlength="120" class="{{ $ui['input'] }}">
                    </div>
                    <div>
                        <label for="new-svc-days" class="{{ $ui['label'] }}">Processing time</label>
                        <input id="new-svc-days" type="text" x-model="draft.days" maxlength="120" class="{{ $ui['input'] }}">
                    </div>
                    <div>
                        <label for="new-svc-like" class="{{ $ui['label'] }}">Handled by the officers of</label>
                        <select id="new-svc-like" x-model="draft.permissions_like" aria-describedby="new-svc-like-hint" class="{{ $ui['input'] }}">
                            <option value="">Choose a service…</option>
                            <template x-for="r in likeChoices" :key="r.key"><option :value="r.key" x-text="r.name + ' (' + r.office + ')'" :selected="r.key === draft.permissions_like"></option></template>
                        </select>
                        <p id="new-svc-like-hint" class="text-[11px] text-ink-muted mt-1">Whoever may process that service may process this one (Roles and permissions).</p>
                    </div>
                </div>
                <p x-show="createError" x-text="createError" role="alert" class="{{ $ui['error'] }}"></p>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" x-bind:disabled="creating" @click="adding = false">Cancel</x-ui.button>
                <x-ui.button size="sm" icon="plus" x-bind:disabled="creating" @click="createService()"><span x-text="creating ? 'Adding…' : 'Add service'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
