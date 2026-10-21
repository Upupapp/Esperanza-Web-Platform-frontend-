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

    {{-- The composer --}}
    <div x-data="{ get open() { return formOpen; }, set open(v) { v ? (formOpen = true) : requestClose(); } }">
    {{-- A window sized to its content (2026-10-21 UI pass): two columns on a
         computer - what, when and who on the left, the pop-up and its banner
         on the right - one on a phone. --}}
    <x-ui.modal maxWidth="3xl" :bare="true">
        <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-brand-600/10 shrink-0">
            <h3 class="text-base font-extrabold text-ink" x-text="editing ? 'Edit occasion' : 'New occasion'">New occasion</h3>
            <button type="button" @click="requestClose()" aria-label="Close" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-4 py-4 sm:px-5">
            <p x-show="editing && editing.state === 'paused'" x-cloak class="mb-3 rounded-2xl bg-amber-50 border border-amber-100 px-3.5 py-2 text-xs text-amber-900">This occasion is paused. Your changes apply when it is resumed.</p>
        <div class="grid lg:grid-cols-2 gap-4 items-start">
        <div class="space-y-4 min-w-0">

            {{-- 1. What it is --}}
            <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">1. What it is</legend>
                <div>
                    <label for="occ-name" class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-rose-500">*</span></label>
                    <input id="occ-name" type="text" x-model="form.name" maxlength="160" placeholder="e.g. Pista ng Barangay Baras 2026" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <p x-show="fieldError('name')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('name')"></p>
                </div>
                <div>
                    <label for="occ-description" class="block text-xs font-medium text-slate-700 mb-1">Description (for staff)</label>
                    <textarea id="occ-description" rows="2" maxlength="2000" x-model="form.description" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label for="occ-type" class="block text-xs font-medium text-slate-700 mb-1">Type</label>
                        <select id="occ-type" x-model="form.type" @change="onType()" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="t in contract.types" :key="t.key"><option :value="t.key" x-text="t.en" :selected="t.key === form.type"></option></template>
                        </select>
                    </div>
                    <div>
                        <p class="block text-xs font-medium text-slate-700 mb-1">Status</p>
                        <p class="rounded-xl bg-slate-50 border border-slate-200 py-2 px-3 text-sm text-ink-body" x-text="editing ? (editing.state_label ? editing.state_label.en : editing.state) + ' · ' + statusLabel(editing.status) : 'Draft until it is activated'"></p>
                    </div>
                </div>
            </fieldset>

            {{-- 2. When --}}
            <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">2. When it shows</legend>
                <div class="grid sm:grid-cols-3 gap-2">
                    <template x-for="t in contract.triggers" :key="t.key">
                        <label x-show="(form.type === 'birthday') === (t.key === 'birthday')" class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.trigger === t.key ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="occ-trigger" :value="t.key" x-model="form.trigger" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                            <span class="font-semibold text-ink" x-text="t.en"></span>
                        </label>
                    </template>
                </div>
                <div class="grid gap-2.5">
                    <div>
                        <label for="occ-start-date" class="block text-xs font-medium text-slate-700 mb-1" x-text="form.trigger === 'birthday' ? 'Runs from (optional)' : (form.trigger === 'annual' ? 'Starts (first year)' : 'Starts')"></label>
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,7.5rem)] gap-2">
                            <input id="occ-start-date" type="date" x-model="form.start_date" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <input type="time" x-model="form.start_time" aria-label="Start time" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-2.5 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <p x-show="fieldError('starts_at')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('starts_at')"></p>
                    </div>
                    <div>
                        <label for="occ-end-date" class="block text-xs font-medium text-slate-700 mb-1" x-text="form.trigger === 'birthday' ? 'Runs until (optional)' : 'Ends'"></label>
                        <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,7.5rem)] gap-2">
                            <input id="occ-end-date" type="date" x-model="form.end_date" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <input type="time" x-model="form.end_time" aria-label="End time" class="w-full min-w-0 rounded-xl border-0 surface-pressed py-2 px-2.5 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <p x-show="fieldError('ends_at')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('ends_at')"></p>
                    </div>
                </div>
                <p class="text-[10.5px] text-ink-muted" x-text="form.trigger === 'annual' ? 'Esperanza time. The same days and times come back every year; it never completes until archived.' : (form.trigger === 'birthday' ? 'Each resident sees it on their own birthday (Esperanza time), from the birthdate on their account, once a year at most.' : 'Esperanza time (Asia/Manila), whatever this computer is set to.')"></p>
            </fieldset>

            {{-- 3. Audience --}}
            <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">3. Who sees it</legend>
                <div class="grid sm:grid-cols-2 gap-2">
                    <label class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.audience === 'all' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="occ-audience" value="all" :checked="form.audience === 'all'" @change="setAudience('all')" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span><span class="block font-semibold text-ink">Every resident</span><span class="block text-[10.5px] text-ink-muted">The whole municipality</span></span>
                    </label>
                    <label class="flex items-start gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.audience === 'some' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="occ-audience" value="some" :checked="form.audience === 'some'" @change="setAudience('some')" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span><span class="block font-semibold text-ink">Chosen barangays or groups</span><span class="block text-[10.5px] text-ink-muted">One or several barangays, senior citizens, other groups</span></span>
                    </label>
                </div>
                <div x-show="form.audience === 'some'" x-cloak class="rounded-2xl border border-slate-200 bg-white/70 p-3 space-y-3">
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
                <p class="rounded-xl bg-brand-50/60 border border-brand-100 px-3 py-2 text-xs text-ink-body flex flex-wrap items-center gap-x-2 gap-y-1">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-brand-600"></i>
                    <span class="font-semibold" x-text="audienceSummary"></span>
                    <span x-show="reach" class="text-ink-muted" x-text="reach ? '· ' + reach.residents + ' resident account' + (reach.residents === 1 ? '' : 's') + ' now' + (form.trigger === 'birthday' ? ' (' + reach.with_birthdate + ' with a birthdate)' : '') : ''"></span>
                </p>
            </fieldset>

        </div>
        <div class="space-y-4 min-w-0">
            {{-- 4. The pop-up --}}
            <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">4. The pop-up</legend>
                <div class="grid sm:grid-cols-[minmax(0,1fr)_minmax(0,13rem)] gap-3 items-start">
                <div class="space-y-3 min-w-0">
                <div>
                    <label for="occ-title" class="block text-xs font-medium text-slate-700 mb-1">Title <span class="text-rose-500">*</span></label>
                    <input id="occ-title" type="text" x-model="form.popup_title" maxlength="160" placeholder="e.g. Maligayang Pista, {first_name}!" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <p class="text-[10.5px] text-ink-muted mt-1">{first_name} becomes each resident's first name.</p>
                </div>
                <div>
                    <label for="occ-body" class="block text-xs font-medium text-slate-700 mb-1">Message <span class="text-rose-500">*</span></label>
                    <textarea id="occ-body" rows="5" maxlength="2000" x-model="form.popup_body" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </div>
                </div>
                <div class="space-y-2 min-w-0">
                {{-- Its own picture, or - by choice - the banner's: a link of its own
                     to the same file, so removing either later leaves the other. --}}
                <div x-show="bannerPicture" x-cloak class="flex flex-wrap items-center gap-2 text-xs">
                    <button type="button" x-show="!sharesBannerPicture" @click="useBannerPicture(true)" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-ink-body hover:bg-brand-50"><i data-lucide="gallery-horizontal" class="w-3.5 h-3.5"></i>Use the banner's picture</button>
                </div>
                <div x-show="sharesBannerPicture" x-cloak class="space-y-2">
                    <p class="text-xs font-medium text-slate-700">Picture: the banner's</p>
                    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-[4/3]"><img :src="bannerPicture ? bannerPicture.url : ''" alt="" class="w-full h-full object-contain"></div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="useBannerPicture(false)" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink-body hover:bg-brand-50"><i data-lucide="image" class="w-3.5 h-3.5"></i>Use the pop-up's own picture</button>
                        <span class="text-[11px] text-ink-muted">The banner keeps its picture; changing either later never changes the other.</span>
                    </div>
                </div>
                <div x-show="!sharesBannerPicture">
                <template x-for="k in [formKey]" :key="k">
                    <x-balita.image-field model="image" existing="existingImage" rules="rules" label="Picture (optional)" aspect="aspect-[4/3]" fit="contain" field-id="occ-image" />
                </template>
                </div>
                </div>
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
                        <input id="occ-button-label" type="text" maxlength="60" x-model="form.popup_button_label" placeholder="e.g. See the program" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                    <div x-show="form.button === 'link'" x-cloak>
                        <label for="occ-button-url" class="block text-xs font-medium text-slate-700 mb-1">Link (https)</label>
                        <input id="occ-button-url" type="url" inputmode="url" x-model="form.popup_button_url" placeholder="https://" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                    <div x-show="form.button === 'post'" x-cloak>
                        <label for="occ-button-post" class="block text-xs font-medium text-slate-700 mb-1">Balita post</label>
                        <select id="occ-button-post" x-model="form.popup_post_id" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <option value="">Choose a post</option>
                            <template x-for="p in posts" :key="p.id"><option :value="p.id" x-text="p.title + (p.status === 'Approved' ? ' (scheduled)' : '')" :selected="String(p.id) === String(form.popup_post_id)"></option></template>
                        </select>
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
                <x-ui.button variant="secondary" size="sm" icon="eye" @click="previewForm()">Preview pop-up</x-ui.button>
            </fieldset>

            {{-- 5. Banner --}}
            <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-3.5 space-y-3">
                <legend class="px-1 text-xs font-bold uppercase tracking-wide text-ink-muted">5. Banner</legend>
                <div class="grid sm:grid-cols-3 gap-2">
                    <label class="flex items-center gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.banner_mode === 'none' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="occ-banner" value="none" x-model="form.banner_mode" class="border-slate-300 text-brand-600 focus:ring-brand-200"><span class="font-semibold text-ink">No banner</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border px-3 py-2 cursor-pointer text-xs" :class="form.banner_mode === 'existing' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="occ-banner" value="existing" x-model="form.banner_mode" class="border-slate-300 text-brand-600 focus:ring-brand-200"><span class="font-semibold text-ink">Use a banner</span>
                    </label>
                    <button type="button" @click="newBanner()" class="flex items-center gap-2 rounded-xl border border-dashed border-brand-600/30 px-3 py-2 text-xs font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Create a new banner</button>
                </div>
                <div x-show="form.banner_mode === 'existing'" x-cloak class="space-y-3">
                    <div>
                        <label for="occ-banner-id" class="block text-xs font-medium text-slate-700 mb-1">Banner</label>
                        <select id="occ-banner-id" x-model="form.banner_id" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <option value="">Choose a banner</option>
                            <template x-for="b in banners" :key="b.id"><option :value="b.id" x-text="b.title + ' · ' + statusLabel(b.status) + (b.start_date || b.end_date ? ' · ' + (b.start_date || '…') + ' – ' + (b.end_date || '…') : '')" :selected="String(b.id) === String(form.banner_id)"></option></template>
                        </select>
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
            </fieldset>

            <ul x-show="formWarnings.length" x-cloak class="space-y-1.5" aria-label="To check">
                <template x-for="w in formWarnings" :key="w">
                    <li class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2 text-xs text-amber-900 flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="w"></span></li>
                </template>
            </ul>
        </div>
        </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 px-5 py-3 border-t border-brand-600/10 bg-white shrink-0">
            <p x-show="problem && touched" x-cloak role="status" class="mr-auto text-xs text-rose-700 flex items-center gap-1.5"><i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0"></i><span x-text="problem"></span></p>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="requestClose()">Cancel</x-ui.button>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing || !!problem" @click="save('draft')"><span x-text="editing ? 'Save changes' : 'Save draft'">Save draft</span></x-ui.button>
            <x-ui.button size="sm" icon="circle-play" x-show="!editing || editing.state === 'draft'" x-bind:disabled="processing || !!problem" @click="save('activate')"><span x-text="can('approve') ? 'Save and activate' : 'Save and send for review'">Save and activate</span></x-ui.button>
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
