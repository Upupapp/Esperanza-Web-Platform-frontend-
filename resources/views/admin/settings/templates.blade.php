{{-- ============================= DOCUMENT TEMPLATES =============================
     Each document type's printed title, subtitle, note and validity,
     published as a new version with who published it (TemplatesController,
     scope round 2026-10-15). Under the Service Catalog permission, where the
     rest of a service's rules are edited. The portals read the current
     wording ($store.docTemplates) and print it on documents.
     templatesTab in resources/js/scope-admin-settings.js. --}}
<div x-data="templatesTab" x-show="tab === 'templates'" x-cloak x-effect="tab === 'templates' && start()">
    <x-ui.card x-show="denied" x-cloak>
        <p class="text-sm text-slate-600">Your role cannot view document templates: they are part of the Service Catalog permission.</p>
    </x-ui.card>

    <div x-show="!denied" class="space-y-3">
        <div class="{{ $ui['note'] }}">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
            <p>The wording printed on each kind of document. Saving publishes a new version: both portals print the new wording on documents from now on.</p>
        </div>
        <div x-show="!canEdit" class="{{ $ui['lock'] }}">
            <i data-lucide="lock" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <p><span class="font-semibold text-slate-600">View only.</span> Changing a template needs Service Catalog editing.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="search" aria-label="Search document templates" placeholder="Search by document, title or note" class="{{ $ui['search'] }}">
            </div>
            <select x-model="type" aria-label="Kind of document" class="{{ $ui['select'] }}" x-show="types.length > 1">
                <option value="">All documents</option>
                <template x-for="t in types" :key="t"><option :value="t" x-text="typeLabel(t) + 's'"></option></template>
            </select>
        </div>

        <div x-show="loading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
        <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not load the document templates.'" retryExpr="load()" :retryable="true" /></div>

        <div x-show="loaded && !loading && !failed" x-cloak>
            <x-ui.table>
                <thead>
                    <tr class="bg-well/70">
                        <th class="{{ $ui['th'] }}">Document</th>
                        <th class="{{ $ui['th'] }} hidden md:table-cell">Title and subtitle</th>
                        <th class="{{ $ui['th'] }} hidden lg:table-cell">Note</th>
                        <th class="{{ $ui['th'] }} hidden sm:table-cell">Valid for</th>
                        <th class="{{ $ui['th'] }}">Version</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-600/[.08]">
                    <template x-for="t in shown" :key="t.doc_key">
                        <tr class="hover:bg-white/60 transition-colors cursor-pointer group" @click="edit(t)">
                            <td class="px-4 py-3 min-w-[11rem]">
                                <p class="text-slate-700 font-medium group-hover:text-brand-600 transition-colors" x-text="t.service || t.doc_key"></p>
                                <p class="text-[11px] text-ink-muted" x-text="typeLabel(t.preview_type)"></p>
                                <p class="text-[11px] text-slate-600 md:hidden mt-0.5" x-text="t.cert_title"></p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <p class="text-xs font-semibold tracking-wide text-slate-700" x-text="t.cert_title"></p>
                                <p class="text-[11px] italic text-ink-muted" x-show="t.cert_subtitle" x-text="t.cert_subtitle"></p>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-xs text-ink-muted max-w-xs"><span class="line-clamp-2" x-text="t.note || '—'"></span></td>
                            <td class="px-4 py-3 hidden sm:table-cell text-xs text-ink-muted whitespace-nowrap" x-text="validityText(t.validity_months)"></td>
                            <td class="px-4 py-3 text-xs text-ink-muted whitespace-nowrap" x-text="'v' + t.version + (t.versions > 1 ? ' · ' + t.versions + ' versions' : '')"></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click.stop="edit(t)" :aria-label="(canEdit ? 'Edit ' : 'View ') + (t.service || t.doc_key)" class="p-1.5 rounded-lg text-ink-muted hover:text-brand-600 hover:bg-white/70 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>
            <div x-show="!shown.length" class="surface-glass rounded-[26px] mt-3">
                <x-ui.empty-state icon="file-search" title="No templates match" description="Change the search or the filter." />
            </div>
        </div>
    </div>

    {{-- ---------------- One document type ---------------- --}}
    <x-ui.modal title="Document Template" maxWidth="lg">
        <template x-if="sel">
            <div class="space-y-4">
                <div>
                    <p class="text-base font-bold text-ink break-words" x-text="sel.service || sel.doc_key"></p>
                    <p class="text-[11px] text-ink-muted"><span class="font-mono" x-text="sel.doc_key"></span> · <span x-text="typeLabel(sel.preview_type)"></span> · <span x-text="'Version ' + sel.version"></span></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label for="tpl-title" class="{{ $ui['label'] }}">Title</label>
                        <input id="tpl-title" type="text" x-model="form.cert_title" maxlength="160" :disabled="!canEdit" :aria-invalid="fieldErrors.cert_title ? 'true' : 'false'" class="{{ $ui['input'] }}">
                        <p x-show="fieldErrors.cert_title" x-text="fieldErrors.cert_title" class="{{ $ui['error'] }}"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tpl-subtitle" class="{{ $ui['label'] }}">Subtitle (optional)</label>
                        <input id="tpl-subtitle" type="text" x-model="form.cert_subtitle" maxlength="200" :disabled="!canEdit" class="{{ $ui['input'] }}">
                        <p x-show="fieldErrors.cert_subtitle" x-text="fieldErrors.cert_subtitle" class="{{ $ui['error'] }}"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tpl-note" class="{{ $ui['label'] }}">Note printed on the document (optional)</label>
                        <textarea id="tpl-note" rows="3" x-model="form.note" maxlength="600" :disabled="!canEdit" placeholder="e.g. Not valid without the dry seal of the barangay." class="{{ $ui['input'] }} leading-relaxed"></textarea>
                        <p class="text-[11px] text-ink-muted mt-1" x-text="form.note.length + ' of 600 characters'"></p>
                        <p x-show="fieldErrors.note" x-text="fieldErrors.note" class="{{ $ui['error'] }}"></p>
                    </div>
                    <div>
                        <label for="tpl-validity" class="{{ $ui['label'] }}">Valid for (months)</label>
                        <input id="tpl-validity" type="number" inputmode="numeric" min="1" max="60" step="1" x-model="form.validity_months" :disabled="!canEdit" placeholder="6" class="{{ $ui['input'] }}">
                        <p class="text-[11px] text-ink-muted mt-1">Empty: the standard 6 months. Applies to documents issued from now on.</p>
                        <p x-show="fieldErrors.validity_months" x-text="fieldErrors.validity_months" class="{{ $ui['error'] }}"></p>
                    </div>
                </div>

                {{-- The live preview: the document's head and foot as the wording reads now. --}}
                <div>
                    <p class="{{ $ui['label'] }}">Preview</p>
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 text-center shadow-sm">
                        <p class="text-[10px] uppercase tracking-[.14em] text-ink-muted">Republic of the Philippines</p>
                        <p class="text-[10.5px] uppercase tracking-[.1em] text-ink-muted" x-text="sel.preview_type === 'barangay' ? 'Office of the Punong Barangay' : @js('Municipality of ' . config('esperanza.municipality') . ', ' . config('esperanza.province'))"></p>
                        <p class="mt-3 text-lg font-extrabold tracking-wide text-ink break-words" x-text="preview.title"></p>
                        <p class="text-xs italic text-slate-600 break-words" x-show="preview.subtitle" x-text="preview.subtitle"></p>
                        <div class="my-4 space-y-1.5" aria-hidden="true">
                            <div class="h-2 rounded bg-slate-100"></div>
                            <div class="h-2 rounded bg-slate-100 w-11/12 mx-auto"></div>
                            <div class="h-2 rounded bg-slate-100 w-4/5 mx-auto"></div>
                        </div>
                        <p class="text-[11px] text-slate-600 border-t border-dashed border-slate-200 pt-2 break-words" x-show="preview.note" x-text="preview.note"></p>
                        <p class="text-[10.5px] text-ink-muted mt-1" x-text="preview.validity"></p>
                    </div>
                </div>

                <div>
                    <p class="{{ $ui['label'] }}">Versions</p>
                    <p x-show="historyLoading" class="text-xs text-ink-muted">Loading…</p>
                    <p x-show="historyFailed && !historyLoading" class="text-xs text-rose-600">The versions could not be loaded. <button type="button" class="underline" @click="loadHistory()">Try again</button></p>
                    <ol class="space-y-1.5 max-h-64 overflow-y-auto scrollbar-thin">
                        <template x-for="h in history" :key="'h-' + h.version">
                            <li class="rounded-xl bg-slate-50 px-3 py-2">
                                <div class="flex flex-wrap items-center justify-between gap-x-2">
                                    <p class="text-xs font-semibold text-ink" x-text="'Version ' + h.version + (h.version === sel.version ? ' (current)' : '')"></p>
                                    <p class="text-[11px] text-ink-muted" x-text="(when(h.published_at) || 'Date not recorded') + ' · ' + (h.published_by && h.published_by !== 'seed' ? h.published_by : 'original wording')"></p>
                                </div>
                                <p class="text-xs text-slate-700 mt-0.5 break-words"><span x-text="h.cert_title"></span><span x-show="h.cert_subtitle" class="italic text-ink-muted" x-text="' · ' + h.cert_subtitle"></span></p>
                                <p class="text-[11px] text-ink-muted break-words" x-show="h.note" x-text="'Note: ' + h.note"></p>
                                <p class="text-[11px] text-ink-muted" x-text="'Valid for ' + validityText(h.validity_months)"></p>
                            </li>
                        </template>
                    </ol>
                </div>

                <p x-show="error" x-text="error" role="alert" class="text-xs text-rose-600"></p>
            </div>
        </template>

        <x-slot:footer>
            <p class="mr-auto hidden sm:block text-[11px] font-medium text-amber-700" x-show="canEdit && dirty" x-cloak>Unsaved changes</p>
            <x-ui.button variant="secondary" size="sm" @click="open = false">Close</x-ui.button>
            <x-ui.button size="sm" icon="check" x-show="canEdit" x-bind:disabled="saving || !dirty" @click="save()"><span x-text="saving ? 'Publishing…' : 'Publish new version'"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
