{{-- Banners (Banners & Occasions, 2026-10-21). Logic in resources/js/admin-occasions.js
     (adminBanners). A banner's picture is uploaded like a Balita photo and
     checked by its real type; the preview shows the crop the carousel makes
     on a computer (3:1, at most 240 px tall) and in the app (about 2.3:1). --}}
<div
    x-data="adminBanners(@js(config('esperanza_occasions')), @js(config('esperanza.barangays')), @js(config('esperanza_occasions.image.max_bytes')))"
    x-init="$store.session.whenReady().then(() => tabAllowed('banners') && load())"
    @banner-new.window="openAdd($event.detail?.returnTo || null)"
    @banners-view-shown.window="$event.detail === 'banners' && load()"
>
    <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
    <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load banners.'" retryExpr="load()" :retryable="true" /></div>

    <div x-show="!loading && !failed">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <x-ui.stat-card layout="compact" label="Total Banners" valueBind="banners.length" icon="gallery-horizontal" color="brand" :delay="0" />
        <x-ui.stat-card layout="compact" label="Live on Home" valueBind="banners.filter(b => b.live).length" icon="eye" color="green" sublabel="Published and within its dates" :delay="40" />
        <x-ui.stat-card layout="compact" label="Pending Review" valueBind="banners.filter(b => b.status === 'Pending Review').length" icon="file-clock" color="orange" :delay="80" />
        <x-ui.stat-card layout="compact" label="Drafts" valueBind="banners.filter(b => b.status === 'Draft').length" icon="pencil-line" color="purple" :delay="120" />
    </div>

    <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input aria-label="Search banner titles" type="text" x-model="search" placeholder="Search banner titles..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
            </div>
            <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
            <x-ui.button x-show="can('create')" @click="openAdd()" icon="plus" size="sm" class="shrink-0">New banner</x-ui.button>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full">
                <template x-for="s in ['All', 'Draft', 'Pending Review', 'Approved', 'Released', 'Archived']" :key="s">
                    <button type="button" @click="statusFilter = s" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="statusFilter === s ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="s === 'All' ? 'All' : statusLabel(s)"></button>
                </template>
            </div>
            <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + banners.length + ' banners'"></p>
        </div>
    </div>

    <p class="text-xs text-ink-muted mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>Residents see banners in this order in the Home carousel (website and app) while they are published and within their dates, if they are in the banner's audience.</p>

    <div class="space-y-3">
        <template x-for="(b, idx) in filtered" :key="b.id">
            <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard flex flex-col sm:flex-row">
                <div class="relative w-full sm:w-64 aspect-[3/1] sm:aspect-auto sm:h-auto shrink-0 bg-gradient-to-br overflow-hidden" :class="b.theme">
                    <img x-show="b.image && b.image.url" :src="b.image ? b.image.url : ''" :alt="b.alt_text || b.title" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-950/70 via-transparent to-transparent"></div>
                    <div class="absolute inset-0 flex flex-col justify-end p-3">
                        <p class="text-white font-semibold text-xs leading-snug line-clamp-2" x-text="b.title"></p>
                    </div>
                    <span x-show="b.live" class="absolute top-2 left-2 text-[9px] font-bold text-white bg-emerald-600 rounded-full px-2 py-0.5">LIVE</span>
                    <span x-show="!b.image" class="absolute top-2 right-2 text-[9px] font-semibold text-white bg-navy-950/60 rounded-full px-2 py-0.5">No picture</span>
                </div>
                <div class="flex-1 p-3.5 flex flex-col sm:flex-row sm:items-center gap-3 min-w-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-1">
                            <x-ui.badge statusBind="b.status" labelBind="({ 'Released': 'Published', 'Rejected': 'Sent back', 'Pending Review': 'Waiting for review' })[b.status] || b.status" />
                            <span class="text-[10px] text-ink-muted font-mono" x-text="'#' + b.id"></span>
                        </div>
                        <p class="text-sm font-semibold text-ink truncate" x-text="b.title"></p>
                        <p class="text-xs text-ink-muted mt-0.5 truncate" x-text="b.subtitle"></p>
                        <p class="text-[11px] text-ink-muted mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <i data-lucide="calendar" class="w-3 h-3"></i><span x-text="(b.start_date || 'Any time') + ' – ' + (b.end_date || 'no end')"></span>
                            <span class="text-ink-faint">·</span>
                            <i data-lucide="users" class="w-3 h-3"></i><span x-text="audienceText(b)"></span>
                        </p>
                        <p x-show="(b.used_by || []).length" x-cloak class="text-[11px] text-gold-700 mt-1 flex items-center gap-1.5"><i data-lucide="party-popper" class="w-3 h-3"></i><span x-text="'Used by ' + (b.used_by || []).map(o => o.name).join(', ')"></span></p>
                        {{-- Send back with remarks: the server takes it, the page had no way to (2026-11-13). --}}
                        <div x-show="b._back" x-cloak class="mt-2 space-y-1.5">
                            <label class="block text-[11px] font-semibold text-ink-body" :for="'back-' + b.id">Remarks for the writer (required to send it back)</label>
                            <textarea :id="'back-' + b.id" x-model="b._remarks" rows="2" class="w-full text-xs rounded-xl px-3 py-2 border-0 surface-pressed-sm focus:ring-4 focus:ring-brand-100 outline-none" placeholder="What to change, at least 5 characters"></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="b._back = false" class="px-3 py-1.5 rounded-xl text-[11px] font-medium text-ink-muted hover:bg-white/70">Cancel</button>
                                <button type="button" @click="sendBack(b)" :disabled="(b._remarks || '').trim().length < 5" class="px-3 py-1.5 rounded-xl text-[11px] font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40">Send back</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0 self-start sm:self-center">
                        <div class="flex flex-col mr-1">
                            <button aria-label="Move up" type="button" @click="move(b, -1)" :disabled="idx === 0" class="p-1 rounded text-ink-muted hover:text-navy-700 hover:bg-white/60 disabled:opacity-30 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                            <button aria-label="Move down" type="button" @click="move(b, 1)" :disabled="idx === filtered.length - 1" class="p-1 rounded text-ink-muted hover:text-navy-700 hover:bg-white/60 disabled:opacity-30 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <button type="button" x-show="b.status === 'Draft' && canMove(b, 'Pending Review')" @click="transition(b, 'Pending Review')" title="Submit for review" aria-label="Submit for review" class="p-2 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors"><i data-lucide="send" class="w-4 h-4"></i></button>
                        <button type="button" x-show="b.status === 'Pending Review' && canMove(b, 'Rejected')" @click="b._back = !b._back" title="Send back" aria-label="Send back" class="p-2 rounded-xl text-ink-muted hover:text-rose-600 hover:bg-rose-50 transition-colors"><i data-lucide="undo-2" class="w-4 h-4"></i></button>
                        <button type="button" x-show="b.status === 'Pending Review' && canMove(b, 'Approved')" @click="transition(b, 'Approved')" title="Approve" aria-label="Approve" class="p-2 rounded-xl text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 transition-colors"><i data-lucide="check" class="w-4 h-4"></i></button>
                        <button type="button" x-show="b.status === 'Approved' && canMove(b, 'Released')" @click="transition(b, 'Released')" title="Publish to the carousel" aria-label="Publish to the carousel" class="p-2 rounded-xl text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 transition-colors"><i data-lucide="upload" class="w-4 h-4"></i></button>
                        <button type="button" x-show="b.status !== 'Archived' && canMove(b, 'Archived')" @click="transition(b, 'Archived')" title="Archive (remove from the carousel)" aria-label="Archive" class="p-2 rounded-xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="archive" class="w-4 h-4"></i></button>
                        <button type="button" x-show="editable(b) && can('edit')" @click="openEdit(b)" title="Edit banner" aria-label="Edit banner" class="p-2 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button type="button" x-show="b.status !== 'Released' && can('delete')" @click="remove(b)" title="Delete banner" aria-label="Delete banner" class="p-2 rounded-xl text-ink-muted hover:text-rose-600 hover:bg-rose-50 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="filtered.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="gallery-horizontal" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No banners match these filters.</p>
        </div>
    </div>
    </div>

    <div x-data="{ get open() { return formOpen; }, set open(v) { v ? (formOpen = true) : requestClose(); } }">
    <x-ui.modal :bare="true" maxWidth="2xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-brand-600/10">
            <h3 class="text-base font-extrabold text-ink" x-text="mode === 'add' ? 'New banner' : 'Edit banner'"></h3>
            <button aria-label="Close" type="button" @click="requestClose()" class="w-9 h-9 -mr-1.5 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all shrink-0"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="px-5 py-4 max-h-[72vh] overflow-y-auto scrollbar-thin">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-6 items-start">
            <div class="space-y-4 min-w-0">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-title">Title <span class="text-rose-500">*</span></label>
                    <input id="banner-title" type="text" x-model="form.title" maxlength="160" placeholder="e.g. Fiesta ng Esperanza 2026" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-subtitle">Subtitle</label>
                    <textarea id="banner-subtitle" rows="2" maxlength="255" x-model="form.subtitle" placeholder="Short supporting line shown under the title" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </div>

                <template x-for="k in [formKey]" :key="k">
                    <x-balita.image-field model="image" existing="existingImage" rules="rules" label="Banner picture" aspect="aspect-[3/1]" field-id="banner-image" />
                </template>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-alt">Picture description (alt text) <span x-show="image.id" class="text-rose-500">*</span></label>
                    <input id="banner-alt" type="text" x-model="form.alt_text" maxlength="255" placeholder="e.g. Street dancers in the plaza during the fiesta" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <p class="text-[10.5px] text-ink-muted mt-1">Read aloud to residents who use a screen reader.</p>
                </div>

                <div>
                    <p class="block text-xs font-medium text-slate-700 mb-1.5">Overlay color</p>
                    <div class="grid grid-cols-6 gap-2">
                        <template x-for="(name, key) in themes" :key="key">
                            <button type="button" @click="form.theme = key" class="h-8 rounded-xl bg-gradient-to-br transition-all duration-[160ms] ease-standard ring-2 ring-offset-1" :class="[key, form.theme === key ? 'ring-brand-400' : 'ring-transparent']" :aria-label="name + ' overlay'" :aria-pressed="form.theme === key"></button>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-placement">Placement</label>
                        <select id="banner-placement" x-model="form.placement" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="p in contract.banner_placements" :key="p.key"><option :value="p.key" x-text="p.en" :selected="p.key === form.placement"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-order">Order</label>
                        <input id="banner-order" type="number" min="0" x-model.number="form.order" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-start">Start date</label>
                        <input id="banner-start" type="date" x-model="form.start_date" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-end">End date</label>
                        <input id="banner-end" type="date" x-model="form.end_date" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                </div>
                <p class="text-[10.5px] text-ink-muted -mt-2">Dates are Esperanza's. Empty: from now on, or with no end. Status follows review: Draft, Waiting for review, Approved, Published, Archived.</p>

                <fieldset>
                    <legend class="block text-xs font-medium text-slate-700 mb-1.5">Audience</legend>
                    <details class="rounded-2xl border border-slate-200 bg-white/70 px-3 py-2">
                        <summary class="cursor-pointer text-xs text-ink-body" x-text="formAudience"></summary>
                        <div class="mt-2 space-y-2">
                            <p class="text-[10.5px] text-ink-muted">Choose nothing for every resident. Visitors who are not signed in see only banners for everyone.</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="s in contract.audience_sectors" :key="s.key">
                                    <button type="button" @click="toggle('sectors', s.key)" :aria-pressed="form.sectors.includes(s.key)" class="rounded-full border px-2.5 py-1 text-[11px] font-medium" :class="form.sectors.includes(s.key) ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-slate-200 text-ink-muted hover:border-slate-300'" x-text="s.en"></button>
                                </template>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-40 overflow-y-auto scrollbar-thin">
                                <template x-for="b in barangayNames" :key="b">
                                    <label class="flex items-center gap-1.5 text-[11px] text-ink-body"><input type="checkbox" :checked="form.barangays.includes(b)" @change="toggle('barangays', b)" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"><span x-text="b"></span></label>
                                </template>
                            </div>
                        </div>
                    </details>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-cta-label">Button label</label>
                        <input id="banner-cta-label" type="text" maxlength="60" x-model="form.cta_label" placeholder="e.g. View Schedule" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-cta-route">Links to</label>
                        <select id="banner-cta-route" x-model="form.cta_route" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="(name, key) in ctaRoutes" :key="key"><option :value="key" x-text="name" :selected="key === form.cta_route"></option></template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- The crop residents see --}}
            <aside class="space-y-3 lg:sticky lg:top-0" aria-label="Preview">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5"><i data-lucide="eye" class="w-3.5 h-3.5"></i>Preview</p>
                    <div class="flex items-center gap-1 surface-pressed rounded-xl p-1">
                        <button type="button" @click="previewDevice = 'desktop'" :aria-pressed="previewDevice === 'desktop'" class="px-2.5 py-1 text-[11px] font-medium rounded-lg inline-flex items-center gap-1" :class="previewDevice === 'desktop' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted'"><i data-lucide="monitor" class="w-3 h-3"></i>Website</button>
                        <button type="button" @click="previewDevice = 'mobile'" :aria-pressed="previewDevice === 'mobile'" class="px-2.5 py-1 text-[11px] font-medium rounded-lg inline-flex items-center gap-1" :class="previewDevice === 'mobile' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted'"><i data-lucide="smartphone" class="w-3 h-3"></i>App</button>
                    </div>
                </div>
                <div :class="previewDevice === 'mobile' ? 'mx-auto w-[280px] rounded-[28px] border-[6px] border-navy-900 bg-slate-50 p-2.5' : ''">
                    <div class="relative rounded-2xl overflow-hidden shadow-card w-full" :style="previewDevice === 'mobile' ? 'aspect-ratio: 390 / 172' : 'aspect-ratio: 21 / 7'">
                        <img x-show="image.url" :src="image.url || ''" :alt="form.alt_text || form.title" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r" :class="(form.theme || 'from-brand-600 to-navy-800') + (image.url ? ' opacity-85' : '')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-navy-950/70 via-transparent to-transparent"></div>
                        <div class="relative h-full flex flex-col justify-end" :class="previewDevice === 'mobile' ? 'p-3' : 'p-4 sm:px-8'">
                            <p class="text-white font-bold leading-tight drop-shadow-sm" :class="previewDevice === 'mobile' ? 'text-sm' : 'text-base sm:text-lg'" x-text="form.title || 'Banner title'"></p>
                            <p x-show="previewDevice === 'desktop' && form.subtitle" class="text-white/85 text-xs mt-1 leading-snug" x-text="form.subtitle"></p>
                            <span x-show="form.cta_label" class="inline-flex items-center gap-1 mt-2 w-fit bg-white/95 text-ink text-[11px] font-semibold px-2.5 py-1.5 rounded-lg"><span x-text="form.cta_label"></span><i data-lucide="arrow-right" class="w-3 h-3"></i></span>
                        </div>
                    </div>
                </div>
                <p class="text-[10.5px] text-ink-muted" x-text="previewDevice === 'mobile' ? 'The app shows the middle of the picture, about 2.3 : 1, under the overlay.' : 'The website shows the middle of the picture at 3 : 1 (at most 240 pixels tall), under the overlay. Keep faces and words away from the edges.'"></p>
            </aside>
        </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 px-5 py-3 border-t border-brand-600/10">
            <p x-show="problem && touched" x-cloak role="status" class="mr-auto text-xs text-rose-700 flex items-center gap-1.5"><i data-lucide="circle-alert" class="w-3.5 h-3.5 shrink-0"></i><span x-text="problem"></span></p>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="requestClose()">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" x-bind:disabled="processing || !!problem" @click="save()"><span x-text="processing ? 'Saving…' : (mode === 'add' ? 'Save banner' : 'Save changes')">Save banner</span></x-ui.button>
        </div>
    </x-ui.modal>
    </div>
</div>
