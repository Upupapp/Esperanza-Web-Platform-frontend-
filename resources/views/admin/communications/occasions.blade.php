{{-- The Occasion & Pop-up manager (Banners & Occasions, 2026-10-21). Logic in
     resources/js/admin-occasions.js (adminOccasions). An occasion is written,
     reviewed and activated like any LGU content; Scheduled, Active, Paused and
     Completed are worked out by the server from its dates and its pause
     switch. The server decides who sees a pop-up and how often. Previewing
     never publishes. --}}
<div
    x-data="adminOccasions(@js(config('esperanza_occasions')), @js(config('esperanza.barangays')), @js(config('esperanza_occasions.image.max_bytes')))"
    x-init="$store.session.whenReady().then(() => tabAllowed('banners') && load())"
    @banner-created.window="bannerCreated($event.detail)"
    @banners-view-shown.window="$event.detail === 'occasions' && load()"
>
    <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search occasions" type="text" x-model="search" @input.debounce.400ms="load()" placeholder="Search occasions..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
            </div>
            <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
            <x-ui.button x-show="can('create')" @click="openNew()" icon="plus" size="sm" class="shrink-0">New occasion</x-ui.button>
        </div>
        <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto">
            <template x-for="c in stateChips" :key="c.key">
                <button type="button" @click="setState(c.key)" :aria-pressed="state === c.key" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="state === c.key ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                    <span x-text="c.label"></span><span x-show="chipCount(c.key)" class="ml-1 text-ink-muted tabular-nums" x-text="chipCount(c.key)"></span>
                </button>
            </template>
        </div>
    </div>

    <p class="text-xs text-ink-muted mb-3 flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span>A pop-up greets residents when they open Esperanza, on the website and in the app, one at a time. Who sees it, when and how often is decided on the server.</span></p>

    <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
    <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load occasions.'" retryExpr="load()" :retryable="true" /></div>

    {{-- A grid of cards (2026-10-21 UI pass): three across on a wide screen,
         two on a medium one, one on a phone. Each card shows what an officer
         scans for; everything else is under View, and the rarer actions
         under More. --}}
    <div x-show="!loading && !failed">
        <div x-show="occasions.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="o in occasions" :key="o.id">
            <article class="surface-glass rounded-[22px] overflow-visible flex flex-col">
                <button type="button" @click="view(o)" class="relative block aspect-[2/1] rounded-t-[22px] overflow-hidden bg-gradient-to-br from-gold-300 to-orange-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-brand-300" :aria-label="'View ' + o.name">
                    <img x-show="o.popup && o.popup.image && o.popup.image.url" :src="o.popup && o.popup.image ? o.popup.image.url : ''" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                    <span x-show="!(o.popup && o.popup.image)" class="absolute inset-0 grid place-items-center text-white/90"><i :data-lucide="o.trigger === 'birthday' ? 'cake' : 'party-popper'" class="w-9 h-9"></i></span>
                    <span class="absolute left-2.5 top-2.5 inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset shadow-sm" :class="stateTone(o.state)" x-text="o.state_label ? o.state_label.en : o.state"></span>
                    <span x-show="(o.warnings || []).length" class="absolute right-2.5 top-2.5 inline-flex items-center gap-1 text-[10px] font-semibold rounded-full px-2 py-0.5 bg-amber-50 text-amber-800 shadow-sm" :title="(o.warnings || []).map(w => w.en).join('\n')"><i data-lucide="triangle-alert" class="w-3 h-3"></i><span x-text="(o.warnings || []).length + ' to check'"></span></span>
                </button>
                <div class="p-3.5 flex-1 min-w-0 space-y-1.5">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[10px] font-semibold rounded-full px-2 py-0.5 bg-gold-100 text-gold-700" x-text="typeLabel(o.type)"></span>
                        <span x-show="o.state === 'draft' && o.status !== 'Draft'" class="text-[10.5px] text-ink-muted" x-text="statusLabel(o.status)"></span>
                    </div>
                    <h3 class="text-sm font-semibold text-ink leading-snug line-clamp-2" :title="o.name" x-text="o.name"></h3>
                    <ul class="text-[11px] text-ink-muted space-y-1">
                        <li class="flex items-start gap-1.5"><i data-lucide="calendar" class="w-3 h-3 mt-0.5 shrink-0"></i><span x-text="cardWhen(o)"></span></li>
                        <li class="flex items-start gap-1.5"><i data-lucide="users" class="w-3 h-3 mt-0.5 shrink-0"></i><span class="line-clamp-2" x-text="o.audience_summary ? o.audience_summary.en : ''"></span></li>
                        <li class="flex items-start gap-1.5"><i data-lucide="repeat" class="w-3 h-3 mt-0.5 shrink-0"></i><span x-text="frequencyLabel(o.popup ? o.popup.frequency : '') + (o.seen_by ? ' · ' + o.seen_by + ' saw it' : '')"></span></li>
                        <li x-show="o.banner" class="flex items-start gap-1.5"><i data-lucide="gallery-horizontal" class="w-3 h-3 mt-0.5 shrink-0"></i><span class="line-clamp-1" :title="o.banner ? o.banner.title : ''" x-text="o.banner ? (o.banner_sync ? 'Banner shown with it' : 'Banner attached') : ''"></span></li>
                    </ul>
                </div>
                {{-- Send back with remarks: the server takes it, the page had no way to (2026-11-13). --}}
                <div x-show="o._back" x-cloak class="px-3 pb-2 space-y-1.5">
                    <label class="block text-[11px] font-semibold text-ink-body" :for="'back-o-' + o.id">Remarks for the writer (required to send it back)</label>
                    <textarea :id="'back-o-' + o.id" x-model="o._remarks" rows="2" class="w-full text-xs rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:ring-4 focus:ring-brand-100 outline-none" placeholder="What to change, at least 5 characters"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="o._back = false" class="px-3 py-1.5 rounded-xl text-[11px] font-medium text-ink-muted hover:bg-white/70">Cancel</button>
                        <button type="button" @click="sendBack(o)" :disabled="processing || (o._remarks || '').trim().length < 5" class="px-3 py-1.5 rounded-xl text-[11px] font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40">Send back</button>
                    </div>
                </div>
                <div class="flex items-center gap-1 px-2 py-1.5 border-t border-brand-600/10">
                    <button type="button" x-show="o.editable && can('edit')" @click="openEdit(o)" class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold text-brand-700 hover:bg-brand-50 inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                    <button type="button" x-show="!(o.editable && can('edit'))" @click="view(o)" class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold text-brand-700 hover:bg-brand-50 inline-flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>View</button>
                    <button type="button" @click="previewRow(o)" class="px-2.5 py-1.5 rounded-xl text-[11px] font-medium text-ink-body hover:bg-white/70 inline-flex items-center gap-1.5"><i data-lucide="eye" class="w-3.5 h-3.5"></i>Preview</button>
                    {{-- The rest: one menu, so the card stays short. --}}
                    <div class="relative ml-auto" x-data="{ menu: false }" @keydown.escape.stop="menu = false" @click.outside="menu = false">
                        <button type="button" @click="menu = !menu" :aria-expanded="menu" aria-haspopup="menu" :aria-label="'More actions for ' + o.name" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-white/70"><i data-lucide="ellipsis" class="w-4 h-4"></i></button>
                        <div x-show="menu" x-cloak x-transition.opacity role="menu" class="absolute right-0 bottom-full mb-1 z-20 min-w-[11rem] rounded-2xl bg-white shadow-float border border-brand-600/10 p-1">
                            <button type="button" role="menuitem" x-show="o.editable && can('edit')" @click="menu = false; view(o)" class="w-full px-3 py-2 rounded-xl text-left text-xs text-ink-body hover:bg-brand-50 inline-flex items-center gap-2"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>View</button>
                            <button type="button" role="menuitem" x-show="can('create')" @click="menu = false; act(o, 'duplicate')" :disabled="processing" class="w-full px-3 py-2 rounded-xl text-left text-xs text-ink-body hover:bg-brand-50 inline-flex items-center gap-2 disabled:opacity-50"><i data-lucide="copy" class="w-3.5 h-3.5"></i>Duplicate</button>
                            <button type="button" role="menuitem" x-show="(o.state === 'active' || o.state === 'scheduled') && can('approve')" @click="menu = false; act(o, 'pause')" :disabled="processing" class="w-full px-3 py-2 rounded-xl text-left text-xs text-amber-800 hover:bg-amber-50 inline-flex items-center gap-2 disabled:opacity-50"><i data-lucide="pause" class="w-3.5 h-3.5"></i>Pause</button>
                            <button type="button" role="menuitem" x-show="o.state === 'paused' && can('approve')" @click="menu = false; act(o, 'resume')" :disabled="processing" class="w-full px-3 py-2 rounded-xl text-left text-xs text-emerald-700 hover:bg-emerald-50 inline-flex items-center gap-2 disabled:opacity-50"><i data-lucide="play" class="w-3.5 h-3.5"></i>Resume</button>
                            <button type="button" role="menuitem" x-show="o.state === 'draft' && (canMove(o, 'Pending Review') || canMove(o, 'Approved') || canMove(o, 'Released') || canMove(o, 'Draft'))" @click="menu = false; act(o, 'activate')" :disabled="processing" class="w-full px-3 py-2 rounded-xl text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50 inline-flex items-center gap-2 disabled:opacity-50"><i data-lucide="circle-play" class="w-3.5 h-3.5"></i><span x-text="can('approve') ? 'Activate' : 'Send for review'"></span></button>
                            <button type="button" role="menuitem" x-show="o.status === 'Pending Review' && canMove(o, 'Rejected')" @click="menu = false; o._back = true" class="w-full px-3 py-2 rounded-xl text-left text-xs text-rose-700 hover:bg-rose-50 inline-flex items-center gap-2"><i data-lucide="undo-2" class="w-3.5 h-3.5"></i>Send back</button>
                            <button type="button" role="menuitem" x-show="canMove(o, 'Archived')" @click="menu = false; act(o, 'archive')" :disabled="processing" class="w-full px-3 py-2 rounded-xl text-left text-xs text-ink-muted hover:bg-slate-50 inline-flex items-center gap-2 disabled:opacity-50"><i data-lucide="archive" class="w-3.5 h-3.5"></i>Archive</button>
                        </div>
                    </div>
                </div>
            </article>
        </template>
        </div>
        <div class="flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="occasions.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="party-popper" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600" x-text="state !== 'all' || search.trim() ? 'No occasions match these filters.' : 'No occasions yet.'"></p>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-ui.button variant="secondary" size="sm" x-show="state !== 'all' || search.trim()" @click="search = ''; setState('all')">Clear filters</x-ui.button>
                <x-ui.button size="sm" icon="plus" x-show="can('create')" @click="openNew()">New occasion</x-ui.button>
            </div>
        </div>
    </div>

    {{-- View: everything about one occasion, and what to check --}}
    <div x-data="{ get open() { return viewOpen; }, set open(v) { viewOpen = v; } }">
    <x-ui.modal title="Occasion" maxWidth="lg">
        <template x-if="viewing">
            <div class="space-y-3 text-xs text-ink-body">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="stateTone(viewing.state)" x-text="viewing.state_label ? viewing.state_label.en : viewing.state"></span>
                    <span class="text-[11px] text-ink-muted" x-text="'Status: ' + statusLabel(viewing.status)"></span>
                </div>
                <p class="text-base font-bold text-ink" x-text="viewing.name"></p>
                <p x-show="viewing.description" class="whitespace-pre-line" x-text="viewing.description"></p>
                <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-1.5">
                    <dt class="text-ink-muted">Type</dt><dd x-text="typeLabel(viewing.type)"></dd>
                    <dt class="text-ink-muted">When</dt><dd x-text="triggerLabel(viewing.trigger) + ' · ' + windowText(viewing)"></dd>
                    <dt class="text-ink-muted">Audience</dt><dd x-text="viewing.audience_summary ? viewing.audience_summary.en : ''"></dd>
                    <dt class="text-ink-muted">How often</dt><dd x-text="frequencyLabel(viewing.popup.frequency) + (viewing.popup.dismissal === 'until_dismissed' ? ', until the resident taps “Don’t show again”' : '')"></dd>
                    <dt class="text-ink-muted">Button</dt><dd x-text="viewing.popup.button_label ? viewing.popup.button_label + ' → ' + (viewing.popup.button_url || ('Balita: ' + (viewing.popup.post_title || 'post #' + viewing.popup.post_id))) : 'None'"></dd>
                    <dt class="text-ink-muted">Banner</dt><dd x-text="viewing.banner ? viewing.banner.title + ' (' + statusLabel(viewing.banner.status) + ')' + (viewing.banner_sync ? ', shown during the occasion' : ', on its own dates') : 'None'"></dd>
                    <dt class="text-ink-muted">Seen by</dt><dd x-text="viewing.seen_by + ' resident(s)'"></dd>
                    <dt class="text-ink-muted">Written by</dt><dd x-text="viewing.created_by || '—'"></dd>
                </dl>
                <p x-show="viewing.review_remarks" class="rounded-xl bg-rose-50 border border-rose-100 px-2.5 py-1.5 text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="viewing.review_remarks"></span></p>
                <ul x-show="(viewing.warnings || []).length" class="space-y-1.5">
                    <template x-for="w in viewing.warnings" :key="w.code">
                        <li class="rounded-xl bg-amber-50 border border-amber-100 px-2.5 py-1.5 text-amber-900 flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="w.en"></span></li>
                    </template>
                </ul>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" icon="eye" x-show="viewing" @click="previewRow(viewing)">Preview</x-ui.button>
            <x-ui.button size="sm" icon="pencil" x-show="viewing && viewing.editable && can('edit')" @click="openEdit(viewing)">Edit</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>

    {{-- The composer: a five-step wizard (2026-10-21) in a compact window. One
         step shows at a time; the others stay in the page (x-show), so nothing
         typed or uploaded is lost moving between them. Next checks the step;
         Save Draft is there on every step; Save and activate only on Review. --}}
    <div x-data="{ get open() { return formOpen; }, set open(v) { v ? (formOpen = true) : requestClose(); } }">
    <x-ui.modal maxWidth="xl" :bare="true">
        <div class="px-5 pt-3 pb-3 bg-white border-b border-brand-600/10 shrink-0 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-extrabold text-ink" x-text="editing ? 'Edit occasion' : 'Create occasion'">Create occasion</h3>
                    <p class="text-[11px] text-ink-muted truncate" x-text="form.name.trim() || 'Pop-ups and greetings residents see when they open Esperanza'"></p>
                </div>
                <button type="button" @click="requestClose()" aria-label="Close" class="w-9 h-9 -mr-1.5 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            {{-- Every step, on a tablet or computer --}}
            <ol class="hidden md:grid grid-cols-5 gap-1" aria-label="Steps">
                <template x-for="s in steps" :key="s.n">
                    <li class="relative">
                        <span x-show="s.n > 1" aria-hidden="true" class="absolute z-0 top-[18px] right-1/2 h-0.5 w-[calc(100%+0.25rem)]" :class="s.n <= maxStep ? 'bg-brand-200' : 'bg-slate-200'"></span>
                        <button type="button" @click="goTo(s.n)" :disabled="processing || !canReach(s.n)" :aria-current="step === s.n ? 'step' : null"
                                :aria-label="'Step ' + s.n + ' of 5: ' + s.label + (stepFlagged(s.n) ? ' (needs attention)' : (s.n < maxStep || (s.n === maxStep && s.n !== step) ? ' (visited)' : ''))"
                                class="relative z-10 w-full flex flex-col items-center gap-1 rounded-xl px-1 py-1 text-center focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 disabled:cursor-not-allowed">
                            <span class="w-7 h-7 rounded-full grid place-items-center text-xs font-bold ring-4 ring-white transition-colors"
                                  :class="step === s.n ? 'bg-brand-600 text-white' : (stepFlagged(s.n) ? 'bg-rose-100 text-rose-700' : (s.n <= maxStep ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-ink-faint'))">
                                <svg x-show="step !== s.n && s.n < maxStep && !stepFlagged(s.n)" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span x-show="step === s.n || s.n >= maxStep || stepFlagged(s.n)" x-text="stepFlagged(s.n) && step !== s.n ? '!' : s.n"></span>
                            </span>
                            <span class="text-[11px] leading-tight font-semibold" :class="step === s.n ? 'text-ink' : (s.n <= maxStep ? 'text-ink-body' : 'text-ink-faint')" x-text="s.label"></span>
                        </button>
                    </li>
                </template>
            </ol>
            {{-- On a phone: one line and a bar --}}
            <div class="md:hidden">
                <p class="text-xs font-semibold text-ink" x-text="'Step ' + step + ' of 5 — ' + stepInfo.label"></p>
                <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden" role="progressbar" aria-label="Progress" aria-valuemin="1" aria-valuemax="5" :aria-valuenow="step"><div class="h-full rounded-full bg-brand-500 transition-all" :style="'width:' + (step / 5 * 100) + '%'"></div></div>
            </div>
        </div>

        <div id="occ-wizard-body" class="flex-1 min-h-0 sm:min-h-[min(34rem,62vh)] overflow-y-auto scrollbar-thin px-4 py-4 sm:px-5 space-y-3">
            <p x-show="editing && editing.state === 'paused'" x-cloak class="rounded-2xl bg-amber-50 border border-amber-100 px-3.5 py-2 text-xs text-amber-900">This occasion is paused. Your changes apply when it is resumed.</p>
            <div>
                <h4 id="occ-step-title" tabindex="-1" class="text-sm font-bold text-ink outline-none" x-text="stepInfo.label">Details</h4>
                <p class="text-[11px] text-ink-muted" x-text="['What the occasion is. The name and description are for staff; residents see the pop-up.', 'When residents see it.', 'Who sees it.', 'What residents see when they open Esperanza, and the banner it may carry.', 'Check everything, then save it as a draft or activate it.'][step - 1]"></p>
            </div>
            <div x-show="(tried[step] && stepProblems(step).length) || serverErrorsFor(step).length" x-cloak role="alert" class="rounded-xl bg-rose-50 border border-rose-100 px-3 py-2 text-xs text-rose-800">
                <p class="font-semibold" x-text="stepProblems(step).length ? 'Before going on, fix what is marked on this step.' : 'The server did not accept this step:'"></p>
                <ul class="mt-1 list-disc pl-4 space-y-0.5">
                    <template x-for="m in serverErrorsFor(step)" :key="m"><li x-text="m"></li></template>
                </ul>
            </div>

            {{-- Step 1. Details --}}
            <section x-show="step === 1" class="space-y-3" aria-labelledby="occ-step-title">
                <div>
                    <label for="occ-name" class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-rose-500">*</span></label>
                    <input id="occ-name" type="text" x-model="form.name" maxlength="160" placeholder="e.g. Pista ng Barangay Baras 2026" aria-describedby="occ-name-err" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('name', 'name') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('name', 'name')">
                    <p id="occ-name-err" x-show="issue('name', 'name')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('name', 'name')"></span></p>
                </div>
                <div>
                    <label for="occ-description" class="block text-xs font-medium text-slate-700 mb-1">Description (for staff)</label>
                    <textarea id="occ-description" rows="3" maxlength="2000" x-model="form.description" placeholder="Notes for other staff: what it is for, who asked for it." class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                    <p x-show="fieldError('description')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('description')"></p>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label for="occ-type" class="block text-xs font-medium text-slate-700 mb-1">Type</label>
                        <select id="occ-type" x-model="form.type" @change="onType()" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="t in contract.types" :key="t.key"><option :value="t.key" x-text="t.en" :selected="t.key === form.type"></option></template>
                        </select>
                        <p class="text-[10.5px] text-ink-muted mt-1" x-show="form.type === 'birthday'" x-cloak>A birthday shows on each resident's own birthday.</p>
                    </div>
                    <div>
                        <p class="block text-xs font-medium text-slate-700 mb-1">Status</p>
                        <p class="rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 text-sm text-ink-body" x-text="editing ? (editing.state_label ? editing.state_label.en : editing.state) + ' · ' + statusLabel(editing.status) : 'Draft until it is activated'"></p>
                        <p class="text-[10.5px] text-ink-muted mt-1">Set by the workflow: activated with a later start, it shows as Scheduled.</p>
                    </div>
                </div>
            </section>

            {{-- Step 2. Schedule & Trigger --}}
            <section x-show="step === 2" x-cloak class="space-y-3" aria-labelledby="occ-step-title">
                <div role="radiogroup" aria-label="Trigger" class="grid sm:grid-cols-3 gap-2">
                    <template x-for="t in contract.triggers" :key="t.key">
                        <label x-show="(form.type === 'birthday') === (t.key === 'birthday')" class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.trigger === t.key ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="occ-trigger" :value="t.key" x-model="form.trigger" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                            <span class="font-semibold text-ink" x-text="t.en"></span>
                        </label>
                    </template>
                </div>
                <p x-show="issue('trigger', 'trigger')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('trigger', 'trigger')"></span></p>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label for="occ-start-date" class="block text-xs font-medium text-slate-700 mb-1"><span x-text="form.trigger === 'birthday' ? 'Runs from (optional)' : (form.trigger === 'annual' ? 'Starts (first year)' : 'Starts')"></span> <span x-show="form.trigger !== 'birthday'" class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,7.5rem)] gap-2">
                            <input id="occ-start-date" type="date" x-model="form.start_date" aria-describedby="occ-start-err" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('start', 'starts_at') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('start', 'starts_at')">
                            <input type="time" x-model="form.start_time" aria-label="Start time" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-2.5 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <p id="occ-start-err" x-show="issue('start', 'starts_at')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('start', 'starts_at')"></span></p>
                    </div>
                    <div>
                        <label for="occ-end-date" class="block text-xs font-medium text-slate-700 mb-1"><span x-text="form.trigger === 'birthday' ? 'Runs until (optional)' : 'Ends'"></span> <span x-show="form.trigger !== 'birthday'" class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,7.5rem)] gap-2">
                            <input id="occ-end-date" type="date" x-model="form.end_date" aria-describedby="occ-end-err" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('end', 'ends_at') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('end', 'ends_at')">
                            <input type="time" x-model="form.end_time" aria-label="End time" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-2.5 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <p id="occ-end-err" x-show="issue('end', 'ends_at')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('end', 'ends_at')"></span></p>
                    </div>
                </div>
                <p class="text-[10.5px] text-ink-muted" x-text="form.trigger === 'annual' ? 'Esperanza time. The same days and times come back every year; it never completes until archived.' : (form.trigger === 'birthday' ? 'Each resident sees it on their own birthday (Esperanza time), from the birthdate on their account, once a year at most.' : 'Esperanza time (Asia/Manila), whatever this computer is set to.')"></p>
                <p x-show="startsLater" x-cloak class="rounded-xl bg-sky-50 border border-sky-100 px-3 py-2 text-xs text-sky-900 flex items-start gap-1.5"><i data-lucide="calendar-clock" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span>It starts later: once activated it shows as <strong>Scheduled</strong> and starts by itself.</span></p>
            </section>

            {{-- Step 3. Audience --}}
            <section x-show="step === 3" x-cloak class="space-y-3" aria-labelledby="occ-step-title">
                <div role="radiogroup" aria-label="Audience" class="grid sm:grid-cols-2 gap-2">
                    <label class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.audience === 'all' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="occ-audience" value="all" :checked="form.audience === 'all'" @change="setAudience('all')" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span><span class="block font-semibold text-ink">Every resident</span><span class="block text-[10.5px] text-ink-muted">The whole municipality</span></span>
                    </label>
                    <label class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.audience === 'some' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input id="occ-audience-some" type="radio" name="occ-audience" value="some" :checked="form.audience === 'some'" @change="setAudience('some')" aria-describedby="occ-audience-err" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span><span class="block font-semibold text-ink">Chosen barangays or groups</span><span class="block text-[10.5px] text-ink-muted">One or several barangays, senior citizens, other groups</span></span>
                    </label>
                </div>
                <div x-show="form.audience === 'some'" x-cloak class="rounded-2xl border bg-white/70 p-3 space-y-3" :class="issue('audience') ? 'border-rose-300' : 'border-slate-200'">
                    <div>
                        <p class="text-xs font-semibold text-ink mb-1.5">Groups</p>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="s in contract.audience_sectors" :key="s.key">
                                <button type="button" @click="toggle('sectors', s.key)" :aria-pressed="form.sectors.includes(s.key)" class="rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="form.sectors.includes(s.key) ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-slate-200 text-ink-muted hover:border-slate-300'" x-text="s.en"></button>
                            </template>
                        </div>
                        <p class="text-[10.5px] text-ink-muted mt-1">Senior citizens are worked out from the birthdate (60 or older). The other groups come from barangay constituent records.</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <p class="text-xs font-semibold text-ink">Barangays <span class="font-normal text-ink-muted" x-text="form.barangays.length ? '(' + form.barangays.length + ')' : '(all)'"></span></p>
                            <button type="button" x-show="form.barangays.length" @click="form.barangays = []; countReach()" class="text-[11px] text-brand-700 hover:underline">Clear</button>
                        </div>
                        <input type="search" x-model="barangayQuery" aria-label="Find a barangay" placeholder="Find a barangay..." class="w-full mb-2 rounded-xl border-0 surface-pressed-sm py-1.5 px-3 text-xs focus:ring-4 focus:ring-brand-100 outline-none">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-44 overflow-y-auto scrollbar-thin">
                            <template x-for="b in barangayMatches" :key="b">
                                <label class="flex items-center gap-1.5 text-[11px] text-ink-body"><input type="checkbox" :checked="form.barangays.includes(b)" @change="toggle('barangays', b)" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"><span x-text="b"></span></label>
                            </template>
                        </div>
                    </div>
                </div>
                <p id="occ-audience-err" x-show="issue('audience')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('audience')"></span></p>
                <p class="rounded-xl bg-brand-50/60 border border-brand-100 px-3 py-2 text-xs text-ink-body flex flex-wrap items-center gap-x-2 gap-y-1">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-brand-600"></i>
                    <span class="font-semibold" x-text="audienceSummary"></span>
                    <span x-show="reach" class="text-ink-muted" x-text="reach ? '· ' + reach.residents + ' resident account' + (reach.residents === 1 ? '' : 's') + ' now' + (form.trigger === 'birthday' ? ' (' + reach.with_birthdate + ' with a birthdate)' : '') : ''"></span>
                </p>
            </section>

            {{-- Step 4. Pop-up Content, and the banner it may carry --}}
            <section x-show="step === 4" x-cloak class="space-y-4" aria-labelledby="occ-step-title">
                <div class="space-y-3 min-w-0">
                    <div>
                        <label for="occ-title" class="block text-xs font-medium text-slate-700 mb-1">Title <span class="text-rose-500">*</span></label>
                        <input id="occ-title" type="text" x-model="form.popup_title" maxlength="160" placeholder="e.g. Maligayang Pista, {first_name}!" aria-describedby="occ-title-err" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('popup_title', 'popup_title') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('popup_title', 'popup_title')">
                        <p id="occ-title-err" x-show="issue('popup_title', 'popup_title')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('popup_title', 'popup_title')"></span></p>
                        <p class="text-[10.5px] text-ink-muted mt-1">{first_name} becomes each resident's first name.</p>
                    </div>
                    <div>
                        <label for="occ-body" class="block text-xs font-medium text-slate-700 mb-1">Message <span class="text-rose-500">*</span></label>
                        <textarea id="occ-body" rows="4" maxlength="2000" x-model="form.popup_body" aria-describedby="occ-body-err" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('popup_body', 'popup_body') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('popup_body', 'popup_body')"></textarea>
                        <p id="occ-body-err" x-show="issue('popup_body', 'popup_body')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('popup_body', 'popup_body')"></span></p>
                    </div>
                    {{-- Its own picture, or - by choice - the banner's: a link of its own
                         to the same file, so removing either later leaves the other. --}}
                    <div class="space-y-2">
                        <div x-show="bannerPicture" x-cloak class="flex flex-wrap items-center gap-2 text-xs">
                            <button type="button" x-show="!sharesBannerPicture" @click="useBannerPicture(true)" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-ink-body hover:bg-brand-50"><i data-lucide="gallery-horizontal" class="w-3.5 h-3.5"></i>Use the banner's picture</button>
                        </div>
                        <div x-show="sharesBannerPicture" x-cloak class="space-y-2">
                            <p class="text-xs font-medium text-slate-700">Picture: the banner's</p>
                            <div class="max-w-xs rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-[4/3]"><img :src="bannerPicture ? bannerPicture.url : ''" alt="" class="w-full h-full object-contain"></div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="useBannerPicture(false)" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink-body hover:bg-brand-50"><i data-lucide="image" class="w-3.5 h-3.5"></i>Use the pop-up's own picture</button>
                                <span class="text-[11px] text-ink-muted">The banner keeps its picture; changing either later never changes the other.</span>
                            </div>
                        </div>
                        <div x-show="!sharesBannerPicture" class="max-w-xs">
                            <template x-for="k in [formKey]" :key="k">
                                <x-balita.image-field model="image" existing="existingImage" rules="rules" label="Picture (optional)" aspect="aspect-[4/3]" fit="contain" field-id="occ-image" />
                            </template>
                        </div>
                        <p x-show="issue('picture', 'media_id')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('picture', 'media_id')"></span></p>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-2">
                        <div>
                            <label for="occ-button" class="block text-xs font-medium text-slate-700 mb-1">Button</label>
                            <select id="occ-button" x-model="form.button" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="none">No button</option>
                                <option value="post">Opens a Balita post</option>
                                <option value="link">Opens a link</option>
                            </select>
                        </div>
                        <div x-show="form.button !== 'none'" x-cloak>
                            <label for="occ-button-label" class="block text-xs font-medium text-slate-700 mb-1">Button label</label>
                            <input id="occ-button-label" type="text" maxlength="60" x-model="form.popup_button_label" placeholder="e.g. See the program" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('button_label', 'popup_button_label') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('button_label', 'popup_button_label')">
                            <p x-show="issue('button_label', 'popup_button_label')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('button_label', 'popup_button_label')"></span></p>
                        </div>
                        <div x-show="form.button === 'link'" x-cloak>
                            <label for="occ-button-url" class="block text-xs font-medium text-slate-700 mb-1">Link (https)</label>
                            <input id="occ-button-url" type="url" inputmode="url" x-model="form.popup_button_url" placeholder="https://" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('button_url', 'popup_button_url') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('button_url', 'popup_button_url')">
                            <p x-show="issue('button_url', 'popup_button_url')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('button_url', 'popup_button_url')"></span></p>
                        </div>
                        <div x-show="form.button === 'post'" x-cloak>
                            <label for="occ-button-post" class="block text-xs font-medium text-slate-700 mb-1">Balita post</label>
                            <select id="occ-button-post" x-model="form.popup_post_id" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('button_post', 'popup_post_id') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('button_post', 'popup_post_id')">
                                <option value="">Choose a post</option>
                                <template x-for="p in posts" :key="p.id"><option :value="p.id" x-text="p.title + (p.status === 'Approved' ? ' (scheduled)' : '')" :selected="String(p.id) === String(form.popup_post_id)"></option></template>
                            </select>
                            <p x-show="issue('button_post', 'popup_post_id')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('button_post', 'popup_post_id')"></span></p>
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label for="occ-frequency" class="block text-xs font-medium text-slate-700 mb-1">How often one resident sees it</label>
                            <select id="occ-frequency" x-model="form.popup_frequency" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="f in contract.popup_frequencies" :key="f.key"><option :value="f.key" x-text="f.en" :selected="f.key === form.popup_frequency"></option></template>
                            </select>
                            <p class="text-[10.5px] text-ink-muted mt-1">Never again on moving between screens; one missed shows on the next opening while it is on.</p>
                        </div>
                        <div>
                            <label for="occ-dismissal" class="block text-xs font-medium text-slate-700 mb-1">Closing it</label>
                            <select id="occ-dismissal" x-model="form.popup_dismissal" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="d in contract.popup_dismissals" :key="d.key"><option :value="d.key" x-text="d.en" :selected="d.key === form.popup_dismissal"></option></template>
                            </select>
                        </div>
                    </div>
                </div>
                <x-ui.button variant="secondary" size="sm" icon="eye" @click="previewForm()">Preview pop-up</x-ui.button>
                {{-- The banner it may carry, in the same step (2026-10-21) --}}
                <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                    <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">Banner (optional)</legend>
                    <p class="text-[11px] text-ink-muted">A banner in the Home carousel, if it has one.</p>
                    <div role="radiogroup" aria-label="Banner" class="grid sm:grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.banner_mode === 'none' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="occ-banner" value="none" x-model="form.banner_mode" class="border-slate-300 text-brand-600 focus:ring-brand-200"><span class="font-semibold text-ink">No banner</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.banner_mode === 'existing' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="occ-banner" value="existing" x-model="form.banner_mode" class="border-slate-300 text-brand-600 focus:ring-brand-200"><span class="font-semibold text-ink">Use an existing banner</span>
                        </label>
                        <button type="button" @click="newBanner()" class="flex items-center gap-2 rounded-xl border border-dashed border-brand-600/30 px-3 py-2 text-xs font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Create a new banner</button>
                    </div>
                    <div x-show="form.banner_mode === 'existing'" x-cloak class="space-y-3">
                        <div>
                            <label for="occ-banner-id" class="block text-xs font-medium text-slate-700 mb-1">Banner <span class="text-rose-500">*</span></label>
                            <select id="occ-banner-id" x-model="form.banner_id" aria-describedby="occ-banner-err" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none" :class="issue('banner_id', 'banner_id') ? 'ring-2 ring-rose-300' : ''" :aria-invalid="!!issue('banner_id', 'banner_id')">
                                <option value="">Choose a banner</option>
                                <template x-for="b in banners" :key="b.id"><option :value="b.id" x-text="b.title + ' · ' + statusLabel(b.status) + (b.start_date || b.end_date ? ' · ' + (b.start_date || '…') + ' – ' + (b.end_date || '…') : '')" :selected="String(b.id) === String(form.banner_id)"></option></template>
                            </select>
                            <p id="occ-banner-err" x-show="issue('banner_id', 'banner_id')" x-cloak class="text-[11px] text-rose-700 mt-1 flex items-start gap-1"><i data-lucide="circle-alert" class="w-3 h-3 shrink-0 mt-px"></i><span x-text="issue('banner_id', 'banner_id')"></span></p>
                        </div>
                        <div x-show="chosenBanner" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white/70 p-2">
                            <div class="w-36 aspect-[3/1] rounded-xl overflow-hidden bg-gradient-to-br shrink-0" :class="chosenBanner ? chosenBanner.theme : ''">
                                <img x-show="chosenBanner && chosenBanner.image" :src="chosenBanner && chosenBanner.image ? chosenBanner.image.url : ''" alt="" class="w-full h-full object-cover">
                            </div>
                            <p class="text-[11px] text-ink-muted" x-text="chosenBanner ? chosenBanner.title + ' · ' + (chosenBanner.audience_summary ? chosenBanner.audience_summary.en : '') : ''"></p>
                        </div>
                        <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer text-xs">
                            <input type="checkbox" x-model="form.banner_sync" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                            <span><span class="block font-semibold text-ink">Show the banner during this occasion</span><span class="block text-[10.5px] text-ink-muted mt-0.5">To this occasion's audience, while it is on. The banner's own dates are not changed, so other occasions using it are not affected.</span></span>
                        </label>
                        <div x-show="form.banner_sync" x-cloak>
                            <label for="occ-banner-display" class="block text-xs font-medium text-slate-700 mb-1">Where in the carousel</label>
                            <select id="occ-banner-display" x-model="form.banner_display" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="d in contract.banner_displays" :key="d.key"><option :value="d.key" x-text="d.en" :selected="d.key === form.banner_display"></option></template>
                            </select>
                        </div>
                    </div>
                    <p x-show="form.banner_mode === 'none'" class="text-[11px] text-ink-muted">A new banner opens in the Banners form; once saved it comes back here as this occasion's banner. It needs approving like any banner.</p>
                    <ul x-show="bannerWarnings.length" x-cloak class="space-y-1.5" aria-label="To check">
                        <template x-for="w in bannerWarnings" :key="w">
                            <li class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2 text-xs text-amber-900 flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="w"></span></li>
                        </template>
                    </ul>
                </fieldset>
            </section>

            {{-- Step 5. Review & Activate, with the pop-up as residents will see it --}}
            <section x-show="step === 5" x-cloak class="grid lg:grid-cols-[minmax(0,1fr)_16rem] gap-4 items-start" aria-labelledby="occ-step-title">
                <div class="space-y-3 min-w-0">
                <div x-show="firstBadStep" x-cloak role="alert" class="rounded-xl bg-rose-50 border border-rose-100 px-3 py-2 text-xs text-rose-800 flex flex-wrap items-center gap-2">
                    <i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0"></i><span class="flex-1 min-w-0" x-text="problem"></span>
                    <button type="button" @click="goTo(firstBadStep); tried = { ...tried, [firstBadStep]: true }" class="font-semibold underline" x-text="'Go to step ' + firstBadStep"></button>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-1 gap-3 items-start">
                    <template x-for="card in [
                        { n: 1, rows: [['Name', form.name.trim() || '—'], ['Type', typeLabel(form.type)], ['Description', form.description.trim() || '—']] },
                        { n: 2, rows: [['Trigger', triggerLabel(form.trigger)], ['When', scheduleSummary]] },
                        { n: 3, rows: [['Audience', audienceSummary], ['Reach now', reach ? reach.residents + ' resident account' + (reach.residents === 1 ? '' : 's') : '—']] },
                        { n: 4, rows: [['Title', form.popup_title.trim() || '—'], ['Message', form.popup_body.trim() || '—'], ['Picture', pictureSummary], ['Button', buttonSummary], ['How often', frequencyLabel(form.popup_frequency)], ['Closing it', dismissalLabel(form.popup_dismissal)], ['Banner', bannerSummary]] },
                    ]" :key="card.n">
                        <div class="rounded-2xl border bg-white/70 p-3" :class="stepFlagged(card.n) || (firstBadStep === card.n) ? 'border-rose-200' : 'border-brand-600/10'">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-ink-muted" x-text="card.n + ' · ' + steps[card.n - 1].label"></p>
                                <button type="button" @click="goTo(card.n)" :aria-label="'Edit ' + steps[card.n - 1].label" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="pencil" class="w-3 h-3"></i>Edit</button>
                            </div>
                            <dl class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-x-2 gap-y-1 text-xs">
                                <template x-for="row in card.rows" :key="row[0]">
                                    <div class="contents"><dt class="text-ink-muted" x-text="row[0]"></dt><dd class="text-ink-body break-words line-clamp-3 whitespace-pre-line" x-text="row[1]"></dd></div>
                                </template>
                            </dl>
                            <button type="button" x-show="card.n === 4" @click="previewForm()" class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 hover:underline"><i data-lucide="eye" class="w-3.5 h-3.5"></i>Preview pop-up</button>
                        </div>
                    </template>
                </div>
                <ul x-show="formWarnings.length" x-cloak class="space-y-1.5" aria-label="To check">
                    <template x-for="w in formWarnings" :key="w">
                        <li class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2 text-xs text-amber-900 flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="w"></span></li>
                    </template>
                </ul>
                {{-- What the buttons below do, before either is pressed --}}
                <div x-show="!editing || editing.state === 'draft'" class="rounded-2xl border border-brand-100 bg-brand-50/50 px-3.5 py-2.5 text-xs text-ink-body flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-ink" x-text="can('approve') ? 'When activated:' : 'When sent:'"></span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="stateTone(activateNote.state)" x-text="activateNote.label"></span>
                    <span class="basis-full sm:basis-auto sm:flex-1 min-w-0" x-text="activateNote.text"></span>
                    <span class="basis-full text-[11px] text-ink-muted">Save Draft keeps it as a draft: residents see nothing.</span>
                </div>
                <p x-show="editing && editing.state !== 'draft'" x-cloak class="rounded-2xl border border-brand-100 bg-brand-50/50 px-3.5 py-2.5 text-xs text-ink-body" x-text="'Saving applies the changes to this occasion as it is now (' + (editing && editing.state_label ? editing.state_label.en : '') + ').'"></p>
                </div>
                {{-- Live preview: a picture of the pop-up, nothing in it acts --}}
                <aside class="lg:sticky lg:top-0 space-y-1.5 min-w-0" aria-label="Live preview">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5"><i data-lucide="eye" class="w-3.5 h-3.5"></i>Live preview</p>
                    <div class="max-w-[18rem] rounded-[22px] overflow-hidden border shadow-sm" :class="livePreview.birthday ? 'border-gold-200 bg-gradient-to-b from-gold-50 to-white' : 'border-brand-600/10 bg-white'" aria-hidden="true">
                        <img x-show="livePreview.image" :src="livePreview.image || ''" alt="" class="w-full aspect-[16/9] object-cover">
                        <div class="px-4 pt-4 pb-3 text-center">
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold rounded-full px-2 py-0.5" :class="livePreview.birthday ? 'bg-gold-200 text-gold-800' : 'bg-gold-100 text-gold-700'" x-text="livePreview.birthday ? 'MALIGAYANG KAARAWAN' : livePreview.type"></span>
                            <p class="mt-2 text-sm font-extrabold text-ink leading-snug break-words" x-text="livePreview.title"></p>
                            <p class="mt-1.5 text-xs text-ink-body leading-relaxed whitespace-pre-line break-words line-clamp-6" x-text="livePreview.message"></p>
                        </div>
                        <div class="flex flex-wrap justify-center gap-1.5 px-4 pb-3">
                            <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-ink-body">Salamat!</span>
                            <span x-show="livePreview.button" class="inline-flex items-center rounded-xl bg-brand-600 px-3 py-1.5 text-[11px] font-semibold text-white" x-text="livePreview.button"></span>
                        </div>
                        <p x-show="livePreview.dismissible" class="pb-3 text-center text-[10.5px] font-semibold text-ink-muted underline">Huwag nang ipakita</p>
                    </div>
                    <p class="text-[10.5px] text-ink-muted" x-text="livePreview.frequency + '. A preview only: nothing is sent.'"></p>
                    <button type="button" @click="previewForm()" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 hover:underline"><i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>Open the full preview</button>
                </aside>
            </section>
        </div>

        {{-- Back, Save Draft and Next on every step; Save and activate on Review --}}
        <div class="flex flex-wrap items-center gap-2 px-5 py-3 border-t border-brand-600/10 bg-white shrink-0">
            <x-ui.button variant="secondary" size="sm" x-show="step === 1" x-bind:disabled="processing" @click="requestClose()">Cancel</x-ui.button>
            <x-ui.button variant="secondary" size="sm" icon="arrow-left" x-show="step > 1" x-cloak x-bind:disabled="processing" @click="back()">Back</x-ui.button>
            <p class="hidden sm:flex mr-auto text-xs text-ink-muted items-center gap-1.5" role="status">
                <span x-show="image.busy" x-cloak class="inline-flex items-center gap-1.5"><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i>Uploading the picture…</span>
                <span x-show="!image.busy && touched" x-cloak class="inline-flex items-center gap-1.5"><i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>Unsaved changes</span>
            </p>
            <span class="sm:hidden mr-auto"></span>
            <x-ui.button variant="secondary" size="sm" loading="processing" x-bind:title="image.busy ? 'Wait for the picture to finish uploading' : ''" @click="save('draft')"><span x-text="editing ? 'Save changes' : 'Save draft'">Save draft</span></x-ui.button>
            <x-ui.button size="sm" icon="arrow-right" iconPosition="right" class="max-sm:w-full" x-show="step < 5" x-bind:disabled="processing" @click="next()"><span x-text="stepInfo.next">Next: Schedule</span></x-ui.button>
            <x-ui.button size="sm" icon="circle-play" loading="processing" class="max-sm:w-full" x-show="step === 5 && (!editing || editing.state === 'draft')" x-cloak @click="save('activate')"><span x-text="can('approve') ? 'Save and activate' : 'Save and send for review'">Save and activate</span></x-ui.button>
        </div>
    </x-ui.modal>
    </div>

    {{-- The pop-up as a resident sees it. Nothing here is saved or sent. --}}
    <div x-data="{ get open() { return previewOpen; }, set open(v) { previewOpen = v; } }">
    <x-ui.modal title="Pop-up preview" maxWidth="sm">
        <template x-if="previewOf">
            <div>
                <p class="text-xs text-ink-muted mb-3" x-text="'What residents see when they open Esperanza · ' + previewOf.frequency + '. A preview only: nothing is sent.'"></p>
                <div class="rounded-[24px] overflow-hidden border" :class="previewOf.birthday ? 'border-gold-200 bg-gradient-to-b from-gold-50 to-white' : 'border-brand-600/10 bg-white'">
                    <img x-show="previewOf.image" :src="previewOf.image || ''" alt="" class="w-full aspect-[16/9] object-cover">
                    <div x-show="previewOf.birthday && !previewOf.image" class="pt-6 grid place-items-center text-gold-500"><i data-lucide="cake" class="w-10 h-10"></i></div>
                    <div class="px-5 pt-5 pb-4 text-center">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold rounded-full px-2.5 py-0.5" :class="previewOf.birthday ? 'bg-gold-200 text-gold-800' : 'bg-gold-100 text-gold-700'"><i data-lucide="party-popper" class="w-3 h-3"></i><span x-text="previewOf.birthday ? 'MALIGAYANG KAARAWAN' : previewOf.type"></span></span>
                        <h4 class="mt-2.5 text-lg font-extrabold text-ink leading-snug" x-text="previewOf.title"></h4>
                        <p class="mt-2 text-sm text-ink-body leading-relaxed whitespace-pre-line" x-text="previewOf.message"></p>
                    </div>
                    {{-- A picture of the buttons, not buttons: nothing here acts. --}}
                    <div class="flex flex-wrap justify-center gap-2 px-5 pb-3" aria-hidden="true">
                        <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-ink-body">Salamat!</span>
                        <span x-show="previewOf.button" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white"><span x-text="previewOf.button"></span><i x-show="previewOf.external" data-lucide="external-link" class="w-3.5 h-3.5"></i></span>
                    </div>
                    <p x-show="previewOf.dismissible" class="pb-4 text-center text-[11px] font-semibold text-ink-muted underline" aria-hidden="true">Huwag nang ipakita</p>
                </div>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="previewOpen = false">Close preview</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
