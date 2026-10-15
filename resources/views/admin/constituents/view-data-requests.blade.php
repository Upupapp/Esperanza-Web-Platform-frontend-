{{-- view-data-requests.blade.php — residents' data requests (scope round
     2026-10-15): to see, correct, erase or get a copy of their data, filed
     from the portal's Settings. Filed since TAB 17, but no screen received
     them. Rendered inside the dataRequests component (tab-citizen-verification);
     GET /admin/data-requests, POST /admin/data-requests/{ref}/resolve. --}}

<div class="space-y-4">
    <x-ui.card class="px-4 py-3 sm:px-5 sm:py-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="inline-flex items-center gap-1 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto" role="group" aria-label="Which requests">
                <template x-for="f in [['open', 'Open'], ['closed', 'Closed'], ['all', 'All']]" :key="f[0]">
                    <button type="button" @click="drSetFilter(f[0])" :aria-pressed="drFilter === f[0]" class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-colors whitespace-nowrap" :class="drFilter === f[0] ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="f[1]"></button>
                </template>
            </div>
            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-ink-muted"><i data-lucide="search" class="w-3.5 h-3.5"></i></span>
                <input aria-label="Search data requests" type="search" x-model="drSearch" placeholder="Reference, account no. or name…" class="w-full pl-8 pr-4 py-2 text-sm rounded-xl placeholder-slate-400 text-ink focus:outline-none transition border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
            </div>
            <select aria-label="Kind of request" x-model="drKind" class="py-2 pl-3 pr-8 text-sm rounded-xl text-ink focus:outline-none transition cursor-pointer border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                <option value="">All kinds</option>
                <template x-for="(label, key) in drKinds" :key="key"><option :value="key" x-text="label"></option></template>
            </select>
        </div>
    </x-ui.card>

    <div x-show="drLoading && !drRows.length" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-20" /><x-ui.skeleton class="h-20 opacity-80" /></div>
    <div x-show="drFailed" x-cloak><x-ui.error-state messageExpr="'Could not load the data requests.'" retryExpr="drLoad()" /></div>
    <div x-show="drLoaded && !drLoading && !drFailed && !drRows.length" x-cloak>
        <x-ui.empty-state icon="file-lock" title="No data requests here." description="Residents ask from the portal's Settings to see, correct, erase or get a copy of their data." />
    </div>

    <ul x-show="drRows.length && !drFailed" class="space-y-2.5" :aria-busy="drLoading">
        <template x-for="r in drRows" :key="r.ref">
            <li>
                <button type="button" @click="drPick(r)" class="w-full text-left surface-glass rounded-2xl px-4 py-3 hover:shadow-card-hover focus-visible:ring-4 focus-visible:ring-brand-100 outline-none transition">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-semibold text-ink" x-text="r.ref"></span>
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-slate-100 text-slate-700" x-text="drKindLabel(r.kind)"></span>
                        <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset whitespace-nowrap" :class="drTone(r.status)"><span class="w-1.5 h-1.5 rounded-full" :class="drDot(r.status)"></span><span x-text="r.status"></span></span>
                    </span>
                    <span class="block text-sm font-medium text-ink mt-1 break-words" x-text="[r.citizen?.name || 'Resident', r.citizen?.account_no, r.citizen?.barangay ? 'Brgy. ' + r.citizen.barangay : null].filter(Boolean).join(' · ')"></span>
                    <span class="block text-xs text-ink-muted mt-0.5 line-clamp-2 break-words" x-text="r.kind === 'name_change' && r.requested ? drReasonLabel(r.reason) + ': ' + (r.current_name || '') + ' → ' + drNewName(r) : r.detail"></span>
                    <span class="block text-[11px] text-ink-muted mt-1" x-text="'Filed ' + when(r.submitted_at) + (r.resolved_at ? ' · closed ' + when(r.resolved_at) : '')"></span>
                </button>
            </li>
        </template>
    </ul>
    <div x-show="drPage < drLastPage && !drFailed" x-cloak class="flex justify-center">
        <x-ui.button variant="secondary" size="sm" x-bind:disabled="drMoreLoading" @click="drMore()"><span x-text="drMoreLoading ? 'Loading…' : 'Show more'"></span></x-ui.button>
    </div>

    {{-- One request: what was asked, and closing it with what was done. --}}
    <div x-data="{ get open() { return drSel !== null; }, set open(v) { if (!v && !drBusy) drSel = null; } }">
        <x-ui.modal title="Data request" maxWidth="md">
            <template x-if="drSel">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-sm font-bold text-ink" x-text="drSel.ref"></span>
                        <span class="text-[11px] font-semibold rounded-full px-2 py-0.5 bg-slate-100 text-slate-700" x-text="drKindLabel(drSel.kind)"></span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset whitespace-nowrap" :class="drTone(drSel.status)"><span class="w-1.5 h-1.5 rounded-full" :class="drDot(drSel.status)"></span><span x-text="drSel.status"></span></span>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                        <div class="flex justify-between gap-2"><dt class="text-ink-muted">Resident</dt><dd class="font-semibold text-ink text-right" x-text="drSel.citizen?.name || '—'"></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-ink-muted">Account No.</dt><dd class="font-mono font-semibold text-ink" x-text="drSel.citizen?.account_no || '—'"></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-ink-muted">Barangay</dt><dd class="font-semibold text-ink" x-text="drSel.citizen?.barangay ? 'Brgy. ' + drSel.citizen.barangay : '—'"></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-ink-muted">Filed</dt><dd class="font-semibold text-ink text-right" x-text="when(drSel.submitted_at) || '—'"></dd></div>
                    </dl>
                    {{-- A change of name (2026-10-16): the name now, the name asked for, why, and the proof. --}}
                    <div x-show="drSel.kind === 'name_change' && drSel.requested" class="rounded-xl border border-brand-200 bg-brand-50/40 p-3 space-y-2" data-testid="dr-name-change">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div><p class="text-ink-muted">Name now</p><p class="font-semibold text-ink break-words" x-text="drSel.current_name || drSel.citizen?.name || '—'"></p></div>
                            <div><p class="text-ink-muted">Asked for</p><p class="font-bold text-brand-800 break-words" x-text="drNewName(drSel) || '—'"></p></div>
                            <div><p class="text-ink-muted">Reason</p><p class="font-semibold text-ink" x-text="drReasonLabel(drSel.reason)"></p></div>
                            <div><p class="text-ink-muted">Proof expected</p><p class="text-ink" x-text="drProofHint(drSel.reason)"></p></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-brand-600/10">
                            <a x-show="drSel.proof?.url" :href="drSel.proof?.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-brand-200 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="file-search" class="w-3.5 h-3.5"></i><span x-text="'Open the proof: ' + (drSel.proof?.name || 'file')"></span></a>
                            <span x-show="drSel.proof && !drSel.proof.url" class="text-xs text-amber-700">The proof is still being scanned; reopen the request in a minute.</span>
                            <span x-show="!drSel.proof" class="text-xs text-rose-700">No proof was attached.</span>
                        </div>
                        <p x-show="drIsOpen(drSel)" class="text-[11px] text-ink-muted">Check the proof against the name asked for. To apply it, use Apply the new name: the account's Correct details opens with it filled in, and saving there changes the name, keeps the old one in the history, and closes this request. To decline, choose Rejected below and say why.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1">What they asked</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line break-words rounded-xl bg-slate-50 border border-brand-600/10 p-3" x-text="drSel.detail"></p>
                    </div>
                    <div x-show="drSel.resolution" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                        <p class="text-[11px] font-semibold text-emerald-800" x-text="(drIsOpen(drSel) ? 'Note' : 'Closed') + (drSel.handled_by ? ' by ' + drSel.handled_by : '') + (drSel.resolved_at ? ', ' + when(drSel.resolved_at) : '')"></p>
                        <p class="text-sm text-slate-700 whitespace-pre-line break-words mt-1" x-text="drSel.resolution"></p>
                    </div>
                    <p x-show="drSel.kind === 'correction' && drIsOpen(drSel)" class="text-xs text-ink-muted flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i><span>A verified account's name, birth date, sex and barangay are corrected in the account: Open account, then Correct details. Saving there closes this request.</span></p>
                    <form x-show="drIsOpen(drSel) && drMayResolve" @submit.prevent="drResolve()" class="space-y-2.5" novalidate>
                        <fieldset>
                            <legend class="text-xs font-semibold text-ink mb-1.5">Outcome</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <template x-for="o in drOutcomes" :key="o[0]">
                                    <label class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer transition-colors" :class="drOutcome === o[0] ? 'border-brand-300 bg-brand-50/60' : 'border-slate-200 hover:border-slate-300'">
                                        <input type="radio" name="dr-outcome" :value="o[0]" x-model="drOutcome" class="mt-0.5 text-brand-600 focus:ring-brand-200">
                                        <span><span class="block text-xs font-semibold text-ink" x-text="o[0]"></span><span class="block text-[11px] text-ink-muted" x-text="o[1]"></span></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>
                        <div>
                            <label for="dr-note" class="block text-xs font-semibold text-ink mb-1">What was done <span class="font-normal text-ink-muted">(the resident reads this)</span></label>
                            <textarea id="dr-note" x-model="drNote" rows="3" maxlength="2000" placeholder="e.g. We sent a copy of your records to your email on file." class="w-full text-sm rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition"></textarea>
                            <p class="text-[11px] text-ink-muted mt-0.5" x-show="drNote.trim().length < 5">At least 5 characters.</p>
                        </div>
                        <p x-show="drError" class="text-xs text-rose-600" role="alert" x-text="drError"></p>
                    </form>
                </div>
            </template>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" icon="user-round-search" x-show="drSel?.citizen?.account_no && !(drSel?.kind === 'name_change' && drIsOpen(drSel) && drMayResolve)" @click="drOpenAccount(drSel)">Open account</x-ui.button>
                <x-ui.button variant="secondary" size="sm" icon="user-pen" x-show="drSel?.kind === 'name_change' && drSel?.requested && drIsOpen(drSel) && drMayResolve" @click="drOpenAccount(drSel)">Apply the new name</x-ui.button>
                <x-ui.button size="sm" icon="check" x-show="drSel && drIsOpen(drSel) && drMayResolve" x-bind:disabled="drBusy || drNote.trim().length < 5" @click="drResolve()"><span x-text="drBusy ? 'Saving…' : 'Save'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</div>
