@php
    $tab = $tab ?? 'balita';
@endphp

{{-- One sidebar entry, Balita, for all eight tabs: the Directory (Offices,
     Barangays), the Blotter and residents' Feedback are tabs here (owner,
     2026-10-07: as few sidebar entries as possible). /admin/communications/offices,
     /blotter, /banners and /feedback (and the old /admin/settings/support)
     still open this page on that tab. --}}
{{-- On its Feedback tab the page is titled Feedback, not Balita: when opened
     there (the sidebar entry of officers who only answer tickets) and when the
     tab is chosen (syncHeader) (2026-10-15). --}}
@php
    $feedbackSubtitle = 'Tickets from residents, routed to the office that owns them.';
    $balitaSubtitle = "Announcements, events, the directory, the barangay blotter and residents' feedback.";
@endphp
<x-layouts.admin :title="$tab === 'feedback' ? 'Feedback' : 'Balita'" :subtitle="$tab === 'feedback' ? $feedbackSubtitle : $balitaSubtitle" active="communications">
    <div
        x-data="{
            tab: '{{ $tab }}',
            // One submodule per tab. The Barangay Secretary holds only their
            // directory entry and opened Balita to 'Something went wrong' and a
            // 'Walang pahintulot' pop-up per tab (QA run 2026-10-05).
            tabSubs: { balita: 'comm_news', banners: 'comm_banners', community: 'comm_community', events: 'comm_events', offices: 'comm_directory', barangays: 'comm_barangay_directory', blotter: 'comm_directory', feedback: 'comm_feedback' },
            tabAllowed(t) { return $store.session.canPerm(this.tabSubs[t], 'view'); },
            // Each tab's own address, so a reload stays on it; a tab without
            // a route of its own is ?tab= (scope round 2026-10-15).
            tabPaths: { balita: '/admin/communications', banners: '/admin/communications/banners', offices: '/admin/communications/offices', blotter: '/admin/communications/blotter', feedback: '/admin/communications/feedback' },
            syncHeader(t) {
                const fb = t === 'feedback';
                const h = document.querySelector('[data-page-title]');
                const p = document.querySelector('[data-page-subtitle]');
                if (h) h.textContent = fb ? 'Feedback' : 'Balita';
                if (p) p.textContent = fb ? @js($feedbackSubtitle) : @js($balitaSubtitle);
                document.title = (fb ? 'Feedback' : 'Balita') + ' — Esperanza LGU Administrative Portal';
            },
            go(t) {
                this.tab = t;
                try { history.replaceState(history.state, '', this.tabPaths[t] || '/admin/communications?tab=' + t); } catch (e) { /* the tab still changes */ }
            },
            // Open feedback tickets, for the Feedback tab (set by feedbackDesk).
            feedbackOpen: null,
            async landOnAllowedTab() {
                // ?tab= opens a tab with no route of its own; a reply notification
                // links to ?tab=feedback&ref= (FeedbackDesk).
                const asked = new URLSearchParams(window.location.search).get('tab');
                if (asked && this.tabSubs[asked]) this.tab = asked;
                await $store.session.whenReady();
                // An account that may not use the tab asked for opens on Feedback
                // when it answers tickets (offices, the front desk, barangay
                // secretaries hold that and little else here), otherwise on
                // the first tab it may use.
                if (!this.tabAllowed(this.tab)) {
                    const allowed = this.tabAllowed('feedback') ? 'feedback' : Object.keys(this.tabSubs).find((t) => this.tabAllowed(t));
                    if (allowed) this.go(allowed);
                }
            },
            barangayNames: @js(config('esperanza.barangays')),
            initials(name) { return (name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase(); },
        }"
        x-init="landOnAllowedTab()"
        x-effect="syncHeader(tab)"
        class="animate-fade-up"
    >

        {{-- An officer who only answers feedback (comm_feedback) holds the
             module through it; checked by name as well, so such an account
             never meets this panel (scope round 2026-10-15). --}}
        <div x-show="!($store.session.can('communications') || $store.session.canPerm('comm_feedback', 'view'))" x-cloak>
            <x-admin.access-restricted module="Balita" icon="megaphone" />
        </div>

        <div x-show="$store.session.can('communications') || $store.session.canPerm('comm_feedback', 'view')" x-cloak>

        {{-- max-w-full: the eight tabs scroll inside the bar on a phone instead of
             widening the page (scope round 2026-10-15). --}}
        <div class="flex items-center gap-1.5 surface-pressed-sm rounded-2xl p-1 w-fit max-w-full mb-4 overflow-x-auto">
            <button x-show="tabAllowed('balita')" @click="go('balita')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'balita' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="megaphone" class="w-3.5 h-3.5"></i>Balita</button>
            <button x-show="tabAllowed('banners')" @click="go('banners')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'banners' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="gallery-horizontal" class="w-3.5 h-3.5"></i>Banners</button>
            <button x-show="tabAllowed('community')" @click="go('community')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'community' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="users-round" class="w-3.5 h-3.5"></i>Community Posts</button>
            <button x-show="tabAllowed('events')" @click="go('events')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'events' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>Events</button>
            <button x-show="tabAllowed('offices')" @click="go('offices')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="building-2" class="w-3.5 h-3.5"></i>Offices</button>
            <button x-show="tabAllowed('barangays')" @click="go('barangays')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i>Barangays</button>
            <button x-show="tabAllowed('blotter')" @click="go('blotter')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'blotter' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="book-text" class="w-3.5 h-3.5"></i>Blotter</button>
            <button x-show="tabAllowed('feedback')" @click="go('feedback')" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'feedback' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="message-square-reply" class="w-3.5 h-3.5"></i>Feedback<span x-show="feedbackOpen" x-cloak class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700" :title="feedbackOpen + ' open'"><span x-text="feedbackOpen"></span><span class="sr-only"> open</span></span></button>
        </div>

        {{-- ============================= BALITA (announcements) ============================= --}}
        {{-- Logic in resources/js/scope-admin-records.js (adminBalita). Photos and
             videos are attached while writing (x-balita.media-field); every button
             is a move the server lists for the post (available_transitions) and
             the officer may make (2026-10-18). --}}
        <div
            x-show="tab === 'balita'"
            x-data="adminBalita(@js(config('esperanza.barangays')))"
            x-init="$store.session.whenReady().then(() => tabAllowed('balita') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load Balita.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card layout="compact" label="Published" valueBind="count('Released')" icon="megaphone" color="brand" :delay="0" />
                <x-ui.stat-card layout="compact" label="Waiting for review" valueBind="count('Pending Review')" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card layout="compact" label="Drafts and sent back" valueBind="count('Draft') + count('Rejected')" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card layout="compact" label="Likes on published posts" valueBind="posts.filter(a => a.status === 'Released').reduce((n, a) => n + (a.likes || 0), 0)" icon="heart" color="green" :delay="120" />
            </div>

            <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search Balita" type="text" x-model="search" placeholder="Search titles and text..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    </div>
                    <select aria-label="Balita category" x-model="category" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                        <option value="All">All Categories</option>
                        <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <select aria-label="Balita audience" x-model="scope" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                        <option value="All">All Audiences</option>
                        <option value="Municipal">Municipal-wide</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                    <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
                    <x-ui.button x-show="can('create')" @click="openNew()" icon="plus" size="sm" class="shrink-0">New Balita</x-ui.button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full overflow-x-auto">
                        <template x-for="s in ['All', ...statuses]" :key="s">
                            <button type="button" @click="status = s" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="status === s ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                                <span x-text="s === 'All' ? 'All' : statusLabel(s)"></span><span x-show="s !== 'All' && count(s)" class="ml-1 text-ink-muted tabular-nums" x-text="count(s)"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + posts.length + ' posts'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="a in filtered" :key="a.id">
                    <article class="surface-glass rounded-[26px] overflow-hidden flex flex-col hover:shadow-card-hover transition-all duration-[220ms] ease-standard">
                        {{-- The first photo or video, and how many it carries --}}
                        <button type="button" x-show="cover(a)" @click="$store.balitaViewer.show(a.media, 0, a.title, true)" class="relative block aspect-[16/9] bg-slate-100 overflow-hidden focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-brand-300" :aria-label="'View the ' + (a.media || []).length + ' photo(s) and video(s) of ' + a.title">
                            <img x-show="cover(a) && (cover(a).kind === 'image' ? cover(a).url : cover(a).poster_url)" :src="cover(a) ? (cover(a).kind === 'image' ? cover(a).url : cover(a).poster_url) : ''" alt="" loading="lazy" class="w-full h-full object-cover">
                            <span x-show="cover(a) && cover(a).kind === 'video'" class="absolute inset-0 grid place-items-center"><span class="w-10 h-10 rounded-full bg-navy-950/60 text-white grid place-items-center"><i data-lucide="play" class="w-4 h-4"></i></span></span>
                            <span x-show="(a.media || []).length > 1" class="absolute right-2 bottom-2 rounded-full bg-navy-950/70 text-white text-[10.5px] font-semibold px-2 py-0.5 inline-flex items-center gap-1"><i data-lucide="images" class="w-3 h-3"></i><span x-text="(a.media || []).length"></span></span>
                        </button>
                        <div class="p-3.5 flex-1">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-[10px] font-semibold text-ink-muted bg-slate-100 rounded-full px-2 py-0.5" x-text="a.category"></span>
                                <x-ui.badge statusBind="a.status" />
                            </div>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5 mb-2" :class="!a.barangay ? 'bg-brand-50 text-brand-700' : 'bg-purple-50 text-purple-700'">
                                <i :data-lucide="!a.barangay ? 'landmark' : 'map-pin'" class="w-2.5 h-2.5"></i>
                                <span x-text="!a.barangay ? 'Municipality-wide' : 'Brgy. ' + a.barangay + ' only'"></span>
                            </span>
                            <h3 class="text-sm font-semibold text-ink leading-snug line-clamp-2 mb-1" x-text="a.title"></h3>
                            <p class="text-xs text-ink-muted line-clamp-2 mb-2" x-text="a.body"></p>
                            <p x-show="a.status === 'Rejected' && a.review_remarks" x-cloak class="mb-2 rounded-xl bg-rose-50 border border-rose-100 px-2.5 py-1.5 text-[11px] text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="a.review_remarks"></span></p>
                            <div class="flex items-center justify-between gap-2 text-[11px] text-ink-muted">
                                <span class="inline-flex items-center gap-1.5 min-w-0">
                                    <span class="w-6 h-6 rounded-full clay-blue font-extrabold flex items-center justify-center shrink-0 text-[9px]" x-text="initials(a.author)"></span>
                                    <span class="truncate" x-text="a.author"></span>
                                </span>
                                <span class="shrink-0" x-text="a.published_at ? 'Published ' + when(a.published_at).split(',').slice(0, 2).join(',') : 'Not yet published'"></span>
                            </div>
                            <div x-show="a.status === 'Released'" class="flex items-center gap-3 mt-2 text-[11px] text-ink-muted">
                                <span class="inline-flex items-center gap-1"><i data-lucide="heart" class="w-3 h-3"></i><span x-text="a.likes || 0"></span></span>
                                <button type="button" @click="openComments(a)" class="inline-flex items-center gap-1 hover:text-brand-700 hover:underline"><i data-lucide="message-circle" class="w-3 h-3"></i><span x-text="(a.comments_count || 0) + ' comment' + ((a.comments_count || 0) === 1 ? '' : 's')"></span></button>
                            </div>
                        </div>
                        <div class="flex flex-wrap border-t border-brand-600/10">
                            <button type="button" x-show="a.status === 'Pending Review' && (canMove(a, 'Approved') || canMove(a, 'Rejected'))" @click="openReview(a)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-semibold py-2.5 text-orange-700 hover:bg-orange-50/60"><i data-lucide="file-search" class="w-3.5 h-3.5"></i>Review</button>
                            <button type="button" x-show="canEdit(a)" @click="openEdit(a)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-700 hover:bg-brand-50/60"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                            <button type="button" x-show="a.status === 'Draft' && canMove(a, 'Pending Review')" @click="quick(a, 'Pending Review', 'Sent for review.')" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-700 hover:bg-brand-50/60"><i data-lucide="send" class="w-3.5 h-3.5"></i>Send for review</button>
                            <button type="button" x-show="a.status === 'Approved' && canMove(a, 'Released')" @click="quick(a, 'Released', 'Published. Residents are notified.')" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-semibold py-2.5 text-emerald-700 hover:bg-emerald-50/60"><i data-lucide="upload" class="w-3.5 h-3.5"></i>Publish</button>
                            <button type="button" x-show="canMove(a, 'Archived')" @click="quick(a, 'Archived', 'Archived. Residents no longer see it.')" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-ink-muted hover:bg-white/60"><i data-lucide="archive" class="w-3.5 h-3.5"></i>Archive</button>
                        </div>
                    </article>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filtered.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No Balita posts match these filters.</p>
                </div>
            </div>
            </div>

            {{-- New and Edit share this form. --}}
            <x-ui.modal maxWidth="lg" :fullscreen="true" :bare="true">
                <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-brand-600/10 shrink-0">
                    <h3 class="text-base font-extrabold text-ink" x-text="editing ? 'Edit Balita' : 'New Balita'">New Balita</h3>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="compact = !compact" :aria-label="compact ? 'Expand to full screen' : 'Shrink to popup'" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                            <span x-show="compact"><i data-lucide="maximize-2" class="w-4 h-4"></i></span>
                            <span x-show="!compact"><i data-lucide="minimize-2" class="w-4 h-4"></i></span>
                        </button>
                        <button type="button" @click="open = false" aria-label="Close dialog" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 sm:px-10 sm:py-8">
                <div class="space-y-4 max-w-3xl mx-auto">
                    <p x-show="editing" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'Editing a post in ' + statusLabel(editing?.status || '') + '. It stays there until it is sent on.'"></span></p>
                    <p x-show="editing && editing.status === 'Rejected' && editing.review_remarks" x-cloak class="rounded-2xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 text-xs text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="editing?.review_remarks"></span></p>
                    <x-ui.input name="title" label="Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" x-model="form.title" maxlength="255" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="balita-category">Category</label>
                        <select id="balita-category" x-model="form.category" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                            <template x-for="c in categories" :key="c"><option :value="c" x-text="c" :selected="c === form.category"></option></template>
                        </select>
                    </div>

                    <fieldset>
                        <legend class="block text-sm font-medium text-slate-700 mb-1.5">Audience</legend>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button" @click="form.audience = 'Municipal'" :aria-pressed="form.audience === 'Municipal'" class="text-left rounded-2xl border p-3 transition-all duration-[160ms] ease-standard" :class="form.audience === 'Municipal' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-1.5"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-ink">Municipal-wide</p>
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Every barangay sees it and is notified</p>
                            </button>
                            <button type="button" @click="form.audience = 'Barangay'" :aria-pressed="form.audience === 'Barangay'" class="text-left rounded-2xl border p-3 transition-all duration-[160ms] ease-standard" :class="form.audience === 'Barangay' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-ink">Specific Barangay</p>
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Only that barangay sees it and is notified</p>
                            </button>
                        </div>
                        <div x-show="form.audience === 'Barangay'" x-cloak class="mt-2.5">
                            <select aria-label="Barangay" x-model="form.barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="b === form.barangay"></option></template>
                            </select>
                        </div>
                    </fieldset>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="balita-content">Content</label>
                        <textarea id="balita-content" rows="6" placeholder="What residents need to know: what, when, where, who to contact." x-model="form.body" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                    </div>

                    <template x-for="k in [formKey]" :key="k">
                        <x-balita.media-field base="/admin/balita" model="media" existing="(editing ? (editing.media || []) : [])" :english="true" />
                    </template>
                </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10 bg-white shrink-0">
                    <p x-show="media.busy" x-cloak class="mr-auto text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i>Uploading photos and videos…</p>
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing || !formReady" @click="save(false)"><span x-text="editing ? 'Save Changes' : 'Save as Draft'">Save as Draft</span></x-ui.button>
                    <x-ui.button size="sm" icon="send" x-show="!editing || editing.status !== 'Pending Review'" x-bind:disabled="processing || !formReady" @click="save(true)"><span x-text="!editing ? 'Send for review' : (editing.status === 'Rejected' ? 'Save and resend for review' : 'Save and send for review')">Send for review</span></x-ui.button>
                </div>
            </x-ui.modal>

            {{-- Review: the post as residents will see it --}}
            <div x-data="{ get open() { return reviewOpen; }, set open(v) { reviewOpen = v; } }">
            <x-ui.modal title="Review Balita Post" maxWidth="lg">
                <template x-if="selected">
                    <div class="space-y-3">
                        <div class="rounded-2xl border border-brand-600/10 bg-white overflow-hidden">
                            <div class="flex items-center gap-2.5 p-3.5 pb-2">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-9 h-9 rounded-full object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink">Esperanza LGU</p>
                                    <p class="text-[11px] text-ink-muted" x-text="(!selected.barangay ? 'All barangays' : 'Brgy. ' + selected.barangay + ' only') + ' · ' + selected.category"></p>
                                </div>
                                <span class="text-[10px] font-semibold text-ink-muted bg-slate-100 rounded-full px-2 py-0.5">Preview</span>
                            </div>
                            <p class="px-3.5 pb-1 text-[15px] font-bold text-ink" x-text="selected.title"></p>
                            <p class="px-3.5 pb-3 text-[13px] text-slate-700 leading-relaxed whitespace-pre-line" x-text="selected.body"></p>
                            <x-balita.gallery media="selected.media" label="selected.title" :english="true" />
                        </div>
                        <p class="text-xs text-ink-muted">Written by <span class="font-semibold text-ink-body" x-text="selected.author"></span>. Approving publishes it at once and notifies <span x-text="selected.barangay ? 'the residents of Brgy. ' + selected.barangay : 'every resident'"></span> in the app and on the website.</p>
                        <div x-show="canMove(selected, 'Rejected')">
                            <label for="balita-remarks" class="block text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1">Remarks (required to send it back)</label>
                            <textarea id="balita-remarks" x-model="rejectReason" rows="2" placeholder="What needs to change?" class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        </div>
                    </div>
                </template>
                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" icon="pencil" x-show="selected && canEdit(selected)" @click="openEdit(selected)">Edit</x-ui.button>
                    <x-ui.button variant="danger" size="sm" x-show="selected && canMove(selected, 'Rejected')" x-bind:disabled="processing || rejectReason.trim().length < 5" @click="requestChanges()">Send back</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-show="selected && canMove(selected, 'Approved')" x-bind:disabled="processing" @click="approveAndPublish()">Approve &amp; Publish</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
            </div>
        </div>

        {{-- ============================= EVENTS ============================= --}}
        <div
            x-show="tab === 'events'" x-cloak
            x-data="{
                loading: true, failed: false,
                events: [],
                open: false, reviewOpen: false, selected: null, rejectReason: '',
                search: '', status: 'All', scope: 'All', category: 'All',
                processing: false,
                newForm: { name: '', category: 'Community', date: '', time: '', venue: '', audience: 'Municipal', barangay: '{{ config('esperanza.barangays')[0] ?? '' }}' },
                statuses: ['Draft', 'Pending Review', 'Approved', 'Released', 'Rejected', 'Archived'],
                categoryColor: { Community: 'bg-brand-500', Health: 'bg-emerald-500', 'Public Service': 'bg-indigo-500', Advisory: 'bg-amber-500', Livelihood: 'bg-teal-500', Governance: 'bg-purple-500' },
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const res = await $api.getAll('/admin/events');
                        this.events = res.data || [];
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                get categories() { return [...new Set(this.events.map(e => e.category).filter(Boolean))].sort(); },
                get filtered() {
                    return this.events.filter(e =>
                        (!e.barangay || this.$store.session.inScope(e.barangay)) &&
                        (this.status === 'All' || e.status === this.status) &&
                        (this.scope === 'All' || (this.scope === 'Municipal' ? !e.barangay : e.barangay === this.scope)) &&
                        (this.category === 'All' || e.category === this.category) &&
                        (this.search === '' || (e.title || e.name).toLowerCase().includes(this.search.toLowerCase()))
                    );
                },
                async createEvent(submit) {
                    if (this.processing || !this.newForm.name.trim() || !this.newForm.date) return;
                    this.processing = true;
                    try {
                        const key = this.newForm.name.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') + '-' + Date.now().toString(36);
                        await $api.post('/admin/events', {
                            key, name: this.newForm.name.trim(), title: this.newForm.name.trim(), date: this.newForm.date,
                            time: this.newForm.time || null, venue: this.newForm.venue || null, category: this.newForm.category,
                            barangay: this.newForm.audience === 'Barangay' ? this.newForm.barangay : null,
                        });
                        if (submit) {
                            await $api.post('/admin/events/' + key + '/transition', { to: 'Pending Review' });
                        }
                        this.open = false;
                        this.newForm = { name: '', category: 'Community', date: '', time: '', venue: '', audience: 'Municipal', barangay: this.barangayNames?.[0] || '' };
                        this.$dispatch('toast', { message: submit ? 'Submitted for review.' : 'Saved as draft.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not create the event.', variant: 'error' });
                    } finally { this.processing = false; }
                },
                async transition(event, to, remarks) {
                    try {
                        await $api.post('/admin/events/' + event.key + '/transition', { to, remarks: remarks || undefined });
                        return true;
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not update this event.', variant: 'error' });
                        return false;
                    }
                },
                async approveAndPublish() {
                    if (this.processing) return;
                    this.processing = true;
                    if (await this.transition(this.selected, 'Approved')) {
                        await this.transition(this.selected, 'Released');
                        this.reviewOpen = false;
                        this.$dispatch('toast', { message: 'Event approved and published.', variant: 'success' });
                        await this.load();
                    }
                    this.processing = false;
                },
                async requestChanges() {
                    if (this.processing || this.rejectReason.trim().length < 5) return;
                    this.processing = true;
                    if (await this.transition(this.selected, 'Rejected', this.rejectReason.trim())) {
                        this.reviewOpen = false; this.rejectReason = '';
                        this.$dispatch('toast', { message: 'Sent back with remarks.', variant: 'error' });
                        await this.load();
                    }
                    this.processing = false;
                },
                async release(e) { if (await this.transition(e, 'Released')) { this.$dispatch('toast', { message: 'Published.', variant: 'success' }); await this.load(); } },
                async archive(e) { if (await this.transition(e, 'Archived')) { this.$dispatch('toast', { message: 'Archived.', variant: 'success' }); await this.load(); } },
                // Editing an event still in the editorial flow, in the create
                // form (scope round 2026-10-15): PUT /admin/events/{key} takes a
                // Draft, Pending Review or Rejected event; only changed fields go.
                editing: null, editForm: {},
                get form() { return this.editing ? this.editForm : this.newForm; },
                canEdit(e) { return ['Draft', 'Pending Review', 'Rejected'].includes(e.status) && $store.session.canPerm('comm_events', 'edit'); },
                openNew() { this.editing = null; this.open = true; },
                // A time typed as text before (8:00 AM) for the time field; one
                // it cannot read leaves the field empty and is kept as it was.
                toTime(t) {
                    const m = /^(\d{1,2}):(\d{2})(?::\d{2})?\s*([AaPp][Mm])?$/.exec(String(t || '').trim());
                    if (!m) return '';
                    let h = Number(m[1]);
                    const ap = (m[3] || '').toLowerCase();
                    if (ap === 'pm' && h < 12) h += 12;
                    if (ap === 'am' && h === 12) h = 0;
                    return h > 23 ? '' : String(h).padStart(2, '0') + ':' + m[2];
                },
                openEdit(e) {
                    this.editForm = { name: e.title || e.name || '', category: e.category || 'Community', date: e.date || '', time: this.toTime(e.time), venue: e.venue || '', audience: e.barangay ? 'Barangay' : 'Municipal', barangay: e.barangay || this.barangayNames?.[0] || '' };
                    this.editing = e;
                    this.open = true;
                },
                saveEvent(submit) { return this.editing ? this.saveEdit(submit) : this.createEvent(submit); },
                async saveEdit(submit) {
                    const e = this.editing, f = this.editForm;
                    if (this.processing || !e || !f.name.trim() || !f.date) return;
                    const barangay = f.audience === 'Barangay' ? f.barangay : null;
                    const changes = {};
                    if (f.name.trim() !== (e.title || e.name)) { changes.name = f.name.trim(); changes.title = f.name.trim(); }
                    if (f.date !== e.date) changes.date = f.date;
                    if (f.time !== this.toTime(e.time)) changes.time = f.time || null;
                    if (f.venue.trim() !== (e.venue || '')) changes.venue = f.venue.trim() || null;
                    if (f.category !== (e.category || 'Community')) changes.category = f.category;
                    if (barangay !== (e.barangay || null)) changes.barangay = barangay;
                    if (!submit && !Object.keys(changes).length) {
                        this.open = false; this.editing = null;
                        this.$dispatch('toast', { message: 'Nothing was changed.', variant: 'info' });
                        return;
                    }
                    this.processing = true;
                    try {
                        // Each step that succeeds is kept on the event, so trying
                        // again after a failure repeats only what is left.
                        if (Object.keys(changes).length) { await $api.put('/admin/events/' + encodeURIComponent(e.key), changes); Object.assign(e, changes); }
                        if (submit && e.status === 'Rejected') { await $api.post('/admin/events/' + encodeURIComponent(e.key) + '/transition', { to: 'Draft' }); e.status = 'Draft'; }
                        if (submit) { await $api.post('/admin/events/' + encodeURIComponent(e.key) + '/transition', { to: 'Pending Review' }); e.status = 'Pending Review'; }
                        this.open = false; this.editing = null;
                        this.$dispatch('toast', { message: submit ? 'Saved and submitted for review.' : 'Changes saved.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save the changes.', variant: 'error' });
                    } finally { this.processing = false; }
                },
            }"
            x-init="$store.session.whenReady().then(() => tabAllowed('events') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load events.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card layout="compact" label="Published" valueBind="events.filter(e => e.status === 'Released').length" icon="calendar-check" color="brand" :delay="0" />
                <x-ui.stat-card layout="compact" label="Pending Review" valueBind="events.filter(e => e.status === 'Pending Review').length" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card layout="compact" label="Drafts" valueBind="events.filter(e => e.status === 'Draft').length" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card layout="compact" label="Total on File" valueBind="events.length" icon="calendar-days" color="green" :delay="120" />
            </div>

            <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search event titles" type="text" x-model="search" placeholder="Search event titles..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                    </div>
                    <select aria-label="Events category" x-model="category" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                        <option value="All">All Categories</option>
                        <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <select aria-label="Events scope" x-model="scope" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                        <option value="All">All Audiences</option>
                        <option value="Municipal">Municipal-wide</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                    <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
                    <x-ui.button @click="openNew()" icon="plus" size="sm" class="shrink-0">New Event</x-ui.button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto">
                        <template x-for="s in ['All', ...statuses]" :key="s">
                            <button type="button" @click="status = s" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="status === s ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="s"></button>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + events.length + ' events'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="e in filtered" :key="e.key">
                    <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard">
                        <div class="p-3.5 flex items-start gap-3">
                            <div class="shrink-0 w-12 h-12 rounded-xl bg-slate-50 border border-brand-600/10 flex flex-col items-center justify-center overflow-hidden">
                                <span class="w-full text-center text-[9px] font-bold text-white py-0.5" :class="categoryColor[e.category] || 'bg-slate-400'" x-text="e.date ? new Date(e.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' }).toUpperCase() : ''"></span>
                                <span class="text-sm font-bold text-ink leading-none mt-1" x-text="e.date ? new Date(e.date + 'T00:00:00').getDate() : ''"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="text-[10px] font-semibold text-white rounded-full px-2 py-0.5" :class="categoryColor[e.category] || 'bg-slate-400'" x-text="e.category"></span>
                                    <x-ui.badge statusBind="e.status" />
                                </div>
                                <p class="text-sm font-semibold text-ink leading-snug line-clamp-2" x-text="e.title || e.name"></p>
                                <p class="text-xs text-ink-muted mt-1 flex items-center gap-1" x-show="e.time"><i data-lucide="clock" class="w-3 h-3 shrink-0"></i><span x-text="e.time"></span></p>
                                <p class="text-xs text-ink-muted mt-0.5 flex items-center gap-1 truncate" x-show="e.venue"><i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i><span x-text="e.venue"></span></p>
                            </div>
                        </div>
                        <div class="px-3.5 pb-3">
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5" :class="e.barangay ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700'">
                                <i :data-lucide="e.barangay ? 'map-pin' : 'landmark'" class="w-2.5 h-2.5"></i>
                                <span x-text="e.barangay ? 'Brgy. ' + e.barangay : 'Municipality-wide'"></span>
                            </span>
                        </div>
                        <div class="flex border-t border-brand-600/10">
                            <button x-show="e.status === 'Pending Review'" @click="selected = e; reviewOpen = true; rejectReason = ''" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-orange-600 hover:bg-orange-50/60 transition-colors"><i data-lucide="history" class="w-3.5 h-3.5"></i>Review</button>
                            <button x-show="canEdit(e)" @click="openEdit(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-600 hover:bg-brand-50/60 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                            <button x-show="e.status === 'Draft'" @click="transition(e, 'Pending Review').then(ok => ok && load())" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-600 hover:bg-brand-50/60 transition-colors"><i data-lucide="send" class="w-3.5 h-3.5"></i>Submit for Review</button>
                            <button x-show="e.status === 'Approved'" @click="release(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-emerald-600 hover:bg-emerald-50/60 transition-colors"><i data-lucide="upload" class="w-3.5 h-3.5"></i>Publish</button>
                            <button x-show="['Released', 'Rejected', 'Draft'].includes(e.status)" @click="archive(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-ink-muted hover:bg-white/60 transition-colors"><i data-lucide="archive" class="w-3.5 h-3.5"></i>Archive</button>
                        </div>
                    </div>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filtered.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No events match these filters.</p>
                </div>
            </div>
            </div>

            {{-- New and Edit share this form (scope round 2026-10-15), drawn bare
                 for a title that says which, as Balita's is. --}}
            <x-ui.modal maxWidth="lg" :fullscreen="true" :bare="true">
                <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-brand-600/10 shrink-0">
                    <h3 class="text-base font-extrabold text-ink" x-text="editing ? 'Edit Event' : 'New Event'">New Event</h3>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="compact = !compact" :aria-label="compact ? 'Expand to full screen' : 'Shrink to popup'" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                            <span x-show="compact"><i data-lucide="maximize-2" class="w-4 h-4"></i></span>
                            <span x-show="!compact"><i data-lucide="minimize-2" class="w-4 h-4"></i></span>
                        </button>
                        <button type="button" @click="open = false" aria-label="Close dialog" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-6 sm:px-10 sm:py-8">
                <div class="space-y-4">
                    <p x-show="editing" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'Editing an event in ' + (editing?.status || '') + '. It stays there until it is sent on.'"></span></p>
                    <x-ui.input name="event_title" label="Event Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" x-model="form.name" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input name="event_date" type="date" label="Date" x-model="form.date" />
                        <x-ui.input name="event_time" type="time" label="Time" x-model="form.time" />
                    </div>
                    <x-ui.input name="event_venue" label="Venue" placeholder="e.g. Municipal Plaza" x-model="form.venue" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="category-2">Category</label>
                        <select id="category-2" x-model="form.category" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            <template x-for="c in Object.keys(categoryColor)" :key="c"><option x-text="c" :value="c"></option></template>
                            {{-- An event filed under another category keeps it (selected here, as Balita's). --}}
                            <template x-if="form.category && !Object.keys(categoryColor).includes(form.category)"><option :value="form.category" :selected="true" x-text="form.category"></option></template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Audience</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button" @click="form.audience = 'Municipal'" :aria-pressed="form.audience === 'Municipal'" class="text-left rounded-2xl border p-3 transition-all duration-[160ms] ease-standard" :class="form.audience === 'Municipal' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-1.5"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-ink">Municipal-wide</p>
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Visible to every barangay</p>
                            </button>
                            <button type="button" @click="form.audience = 'Barangay'" :aria-pressed="form.audience === 'Barangay'" class="text-left rounded-2xl border p-3 transition-all duration-[160ms] ease-standard" :class="form.audience === 'Barangay' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-ink">Specific Barangay</p>
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Only that barangay sees it</p>
                            </button>
                        </div>
                        <div x-show="form.audience === 'Barangay'" x-cloak x-transition:enter="transition ease-out duration-[160ms]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="mt-2.5">
                            <select aria-label="New event barangay" x-model="form.barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-ink-muted">Posters aren't part of this pass — event creation has no image field on the backend yet.</p>
                </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10 bg-white shrink-0">
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="saveEvent(false)"><span x-text="editing ? 'Save Changes' : 'Save as Draft'">Save as Draft</span></x-ui.button>
                    <x-ui.button size="sm" icon="send" x-show="!editing || editing.status !== 'Pending Review'" x-bind:disabled="processing" @click="saveEvent(true)"><span x-text="!editing ? 'Submit for Review' : (editing.status === 'Rejected' ? 'Save and Resubmit' : 'Save and Submit for Review')">Submit for Review</span></x-ui.button>
                </div>
            </x-ui.modal>

            <div x-data="{ get open() { return reviewOpen; }, set open(v) { reviewOpen = v; } }">
            <x-ui.modal title="Review Event" maxWidth="lg">
                <template x-if="selected">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-ink-muted" x-text="(selected.barangay ? 'Brgy. ' + selected.barangay : 'Municipality-wide') + (selected.date ? (' · ' + selected.date) : '')"></p>
                            </div>
                            <span class="text-[10px] font-medium text-white rounded-full px-2 py-0.5 shrink-0" :class="categoryColor[selected.category] || 'bg-slate-400'" x-text="selected.category"></span>
                        </div>
                        <p class="text-sm font-semibold text-ink mb-2" x-text="selected.title || selected.name"></p>
                        <dl class="grid grid-cols-2 gap-3 mb-3">
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3">
                                <dt class="text-[10px] text-ink-muted uppercase tracking-wide">Time</dt>
                                <dd class="text-sm font-semibold text-ink" x-text="selected.time || '—'"></dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3">
                                <dt class="text-[10px] text-ink-muted uppercase tracking-wide">Venue</dt>
                                <dd class="text-sm font-semibold text-ink" x-text="selected.venue || '—'"></dd>
                            </div>
                        </dl>
                        <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5 text-xs text-ink-muted mb-3">
                            This event is awaiting review before it appears on the citizen Events calendar. Approving will publish it immediately to its selected audience.
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1">Remarks (required to send back)</label>
                            <textarea aria-label="Remarks" x-model="rejectReason" rows="2" placeholder="What needs to change?" class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button variant="danger" size="sm" x-bind:disabled="processing || rejectReason.trim().length < 5" @click="requestChanges()">Request Changes</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-bind:disabled="processing" @click="approveAndPublish()">Approve &amp; Publish</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
            </div>
        </div>

        {{-- ============================= BANNERS ============================= --}}
        <div
            x-show="tab === 'banners'" x-cloak
            x-data="{
                loading: true, failed: false,
                banners: [],
                open: false, mode: 'add', deleteTarget: null, processing: false,
                search: '', statusFilter: 'All',
                form: { id: null, title: '', subtitle: '', imageFile: null, cta_label: '', cta_route: 'citizen.dashboard', theme: 'from-brand-600 to-navy-800', start_date: '', end_date: '', order: 1 },
                themes: { 'from-brand-600 to-navy-800': 'Brand', 'from-purple-600 to-navy-900': 'Purple', 'from-emerald-600 to-teal-800': 'Emerald', 'from-gold-500 to-orange-700': 'Gold', 'from-rose-600 to-navy-900': 'Rose', 'from-slate-700 to-navy-950': 'Slate' },
                ctaRoutes: { 'citizen.dashboard': 'Dashboard', 'citizen.document-requests': 'Dokyu', 'citizen.assistance-requests': 'Tulong', 'citizen.announcements': 'Balita', 'citizen.events': 'Events' },
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const res = await $api.getAll('/admin/banners');
                        this.banners = res.data || [];
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                get filtered() {
                    return this.banners
                        .filter(b => (this.statusFilter === 'All' || b.status === this.statusFilter) && (this.search === '' || b.title.toLowerCase().includes(this.search.toLowerCase())))
                        .slice().sort((a, c) => a.order - c.order);
                },
                openAdd() {
                    this.form = { id: null, title: '', subtitle: '', imageFile: null, cta_label: '', cta_route: 'citizen.dashboard', theme: 'from-brand-600 to-navy-800', start_date: '', end_date: '', order: this.banners.length + 1 };
                    this.mode = 'add'; this.open = true;
                },
                openEdit(b) { this.form = { ...b, imageFile: null }; this.mode = 'edit'; this.open = true; },
                async save() {
                    if (this.processing || !this.form.title.trim()) return;
                    this.processing = true;
                    try {
                        const body = new FormData();
                        body.append('title', this.form.title);
                        body.append('subtitle', this.form.subtitle || '');
                        body.append('cta_label', this.form.cta_label || '');
                        body.append('cta_route', this.form.cta_route || '');
                        body.append('theme', this.form.theme || '');
                        if (this.form.start_date) body.append('start_date', this.form.start_date);
                        if (this.form.end_date) body.append('end_date', this.form.end_date);
                        body.append('order', this.form.order || 1);
                        if (this.form.imageFile) body.append('image', this.form.imageFile);
                        if (this.mode === 'add') {
                            await $api.post('/admin/banners', body);
                        } else {
                            // PHP never populates a multipart body's fields on a
                            // true PUT request (only POST) -- send it as a POST
                            // with Laravel's own _method override instead of
                            // losing every field silently on an edit with a new
                            // image attached.
                            body.append('_method', 'PUT');
                            await $api.post('/admin/banners/' + this.form.id, body);
                        }
                        this.open = false;
                        this.$dispatch('toast', { message: this.mode === 'add' ? 'Banner created.' : 'Banner updated.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save the banner.', variant: 'error' });
                    } finally { this.processing = false; }
                },
                async transition(b, to) {
                    try {
                        await $api.post('/admin/banners/' + b.id + '/transition', { to });
                        await this.load();
                        this.$dispatch('toast', { message: 'Banner status set to ' + to + '.', variant: 'success' });
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not update this banner.', variant: 'error' });
                    }
                },
                async move(b, dir) {
                    const list = this.filtered;
                    const idx = list.findIndex(x => x.id === b.id);
                    const swapIdx = idx + dir;
                    if (swapIdx < 0 || swapIdx >= list.length) return;
                    const other = list[swapIdx];
                    try {
                        await Promise.all([
                            $api.put('/admin/banners/' + b.id, { order: other.order }),
                            $api.put('/admin/banners/' + other.id, { order: b.order }),
                        ]);
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not reorder.', variant: 'error' });
                    }
                },
            }"
            x-init="$store.session.whenReady().then(() => tabAllowed('banners') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load banners.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card layout="compact" label="Total Banners" valueBind="banners.length" icon="gallery-horizontal" color="brand" :delay="0" />
                <x-ui.stat-card layout="compact" label="Live on Dashboard" valueBind="banners.filter(b => b.live).length" icon="eye" color="green" sublabel="Released &amp; within its date window" :delay="40" />
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
                    <x-ui.button @click="openAdd()" icon="plus" size="sm" class="shrink-0">New Banner</x-ui.button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto">
                        <template x-for="s in ['All', 'Draft', 'Pending Review', 'Approved', 'Released', 'Archived']" :key="s">
                            <button type="button" @click="statusFilter = s" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="statusFilter === s ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" x-text="s"></button>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + banners.length + ' banners'"></p>
                </div>
            </div>

            <p class="text-xs text-ink-muted mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i>Banners are shown to citizens in this order, top to bottom, while Released and within their date window.</p>

            <div class="space-y-3">
                <template x-for="(b, idx) in filtered" :key="b.id">
                    <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard flex flex-col sm:flex-row">
                        <div class="relative w-full sm:w-56 h-32 sm:h-auto shrink-0 bg-gradient-to-br" :class="b.theme">
                            <div class="absolute inset-0 flex flex-col justify-end p-3">
                                <p class="text-white font-semibold text-xs leading-snug line-clamp-2" x-text="b.title"></p>
                            </div>
                            <span x-show="b.live" class="absolute top-2 left-2 text-[9px] font-bold text-white bg-emerald-600 rounded-full px-2 py-0.5">LIVE</span>
                        </div>
                        <div class="flex-1 p-3.5 flex flex-col sm:flex-row sm:items-center gap-3 min-w-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <x-ui.badge statusBind="b.status" />
                                    <span class="text-[10px] text-ink-muted font-mono" x-text="'#' + b.id"></span>
                                </div>
                                <p class="text-sm font-semibold text-ink truncate" x-text="b.title"></p>
                                <p class="text-xs text-ink-muted mt-0.5 truncate" x-text="b.subtitle"></p>
                                <p class="text-[11px] text-ink-muted mt-1.5 flex items-center gap-1.5 flex-wrap">
                                    <i data-lucide="calendar" class="w-3 h-3"></i><span x-text="(b.start_date || '—') + ' – ' + (b.end_date || '—')"></span>
                                    <span class="text-ink-faint">·</span>
                                    <i data-lucide="mouse-pointer-click" class="w-3 h-3"></i><span x-text="b.cta_label || 'No CTA'"></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0 self-start sm:self-center">
                                <div class="flex flex-col mr-1">
                                    <button aria-label="Move up" type="button" @click="move(b, -1)" :disabled="idx === 0" class="p-1 rounded text-ink-muted hover:text-navy-700 hover:bg-white/60 disabled:opacity-30 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                                    <button aria-label="Move down" type="button" @click="move(b, 1)" :disabled="idx === filtered.length - 1" class="p-1 rounded text-ink-muted hover:text-navy-700 hover:bg-white/60 disabled:opacity-30 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                                </div>
                                <button type="button" x-show="b.status === 'Draft'" @click="transition(b, 'Pending Review')" title="Submit for review" class="p-2 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors"><i data-lucide="send" class="w-4 h-4"></i></button>
                                <button type="button" x-show="b.status === 'Pending Review'" @click="transition(b, 'Approved')" title="Approve" class="p-2 rounded-xl text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 transition-colors"><i data-lucide="check" class="w-4 h-4"></i></button>
                                <button type="button" x-show="b.status === 'Approved'" @click="transition(b, 'Released')" title="Publish to dashboard" class="p-2 rounded-xl text-ink-muted hover:text-emerald-600 hover:bg-emerald-50 transition-colors"><i data-lucide="upload" class="w-4 h-4"></i></button>
                                <button type="button" x-show="b.status !== 'Archived'" @click="transition(b, 'Archived')" title="Archive (remove from dashboard)" class="p-2 rounded-xl text-ink-muted hover:text-slate-600 hover:bg-slate-100 transition-colors"><i data-lucide="archive" class="w-4 h-4"></i></button>
                                <button type="button" @click="openEdit(b)" title="Edit banner" class="p-2 rounded-xl text-ink-muted hover:text-brand-600 hover:bg-brand-50 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
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

            <x-ui.modal :bare="true" maxWidth="lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
                    <h3 class="text-sm font-semibold text-ink" x-text="mode === 'add' ? 'New Banner' : 'Edit Banner'"></h3>
                    <button aria-label="Close" @click="open = false" class="text-ink-muted hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto scrollbar-thin">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-title">Title <span class="text-rose-500">*</span></label>
                        <input id="banner-title" type="text" x-model="form.title" placeholder="e.g. Fiesta ng Esperanza 2026" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-subtitle">Subtitle</label>
                        <textarea id="banner-subtitle" rows="2" x-model="form.subtitle" placeholder="Short supporting line shown under the title" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-image">Banner Image</label>
                        <input id="banner-image" type="file" accept="image/*" @change="form.imageFile = $event.target.files[0]" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-brand-50 file:text-brand-700 file:text-xs">
                        <p class="text-[10.5px] text-ink-muted mt-1" x-show="mode === 'edit'">Leave empty to keep the current image.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Overlay Color</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="(label, key) in themes" :key="key">
                                <button type="button" @click="form.theme = key" class="h-9 rounded-xl bg-gradient-to-br transition-all duration-[160ms] ease-standard ring-2 ring-offset-1" :class="[key, form.theme === key ? 'ring-brand-400' : 'ring-transparent']" :title="label"></button>
                            </template>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-start">Start Date</label>
                            <input id="banner-start" type="date" x-model="form.start_date" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-end">End Date</label>
                            <input id="banner-end" type="date" x-model="form.end_date" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-cta-label">Button Label</label>
                            <input id="banner-cta-label" type="text" x-model="form.cta_label" placeholder="e.g. View Schedule" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1" for="banner-cta-route">Links To</label>
                            <select id="banner-cta-route" x-model="form.cta_route" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <template x-for="(label, key) in ctaRoutes" :key="key"><option :value="key" x-text="label"></option></template>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10">
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-bind:disabled="processing" @click="save()" x-text="processing ? 'Saving…' : (mode === 'add' ? 'Create Banner' : 'Save Changes')"></x-ui.button>
                </div>
            </x-ui.modal>
        </div>

        {{-- ============================= COMMUNITY POSTS ============================= --}}
        {{-- Residents' posts: pre-moderated, with their photos and videos, the
             reasons residents gave when they reported one, and the comments.
             Logic in resources/js/scope-admin-records.js (adminCommunity). --}}
        <div
            x-show="tab === 'community'" x-cloak
            x-data="adminCommunity"
            x-init="$store.session.whenReady().then(() => tabAllowed('community') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load community posts.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card layout="compact" label="Awaiting review" valueBind="countOf('pending')" icon="file-clock" color="orange" :delay="0" />
                <x-ui.stat-card layout="compact" label="Reported" valueBind="countOf('reported')" icon="flag" color="red" sublabel="Live or waiting, with a report" :delay="40" />
                <x-ui.stat-card layout="compact" label="Live" valueBind="countOf('published')" icon="users-round" color="brand" :delay="80" />
                <x-ui.stat-card layout="compact" label="Hidden / Taken down" valueBind="countOf('hidden') + countOf('removed')" icon="eye-off" color="purple" :delay="120" />
            </div>

            <div class="surface-glass rounded-[26px] p-3 mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Search author or post content" type="text" x-model="search" placeholder="Search author or post content..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                    </div>
                    <select aria-label="Community barangay" x-model="scope" class="text-xs rounded-2xl py-2 px-3 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                        <option value="All">All Barangays</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full overflow-x-auto">
                        <template x-for="[key, label] in views" :key="key">
                            <button type="button" @click="view = key" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="view === key ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                                <span x-text="label"></span><span x-show="key !== 'all' && countOf(key)" class="ml-1 text-ink-muted tabular-nums" x-text="countOf(key)"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + posts.length + ' posts'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <template x-for="p in filtered" :key="p.id">
                    <article class="surface-glass rounded-[26px] overflow-hidden flex flex-col hover:shadow-card-hover transition-all duration-[220ms] ease-standard">
                        <div class="flex items-center gap-2.5 p-3.5 pb-2.5">
                            <div class="w-10 h-10 rounded-full clay-blue font-extrabold flex items-center justify-center shrink-0 text-sm" x-text="initials(p.author)"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink truncate" x-text="p.author"></p>
                                <p class="text-[11px] text-ink-muted" x-text="['Brgy. ' + p.barangay, p.category, when(p.time)].filter(Boolean).join(' · ')"></p>
                            </div>
                            <span class="shrink-0 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="stateTones[p.review_state]" x-text="stateLabels[p.review_state] || p.review_state"></span>
                        </div>
                        <p class="px-3.5 pb-2.5 text-[13px] text-slate-700 leading-relaxed line-clamp-3 whitespace-pre-line" x-show="p.body" x-text="p.body"></p>
                        <x-balita.gallery media="p.media" label="'Post by ' + p.author" :english="true" class="mx-3.5 mb-2.5 rounded-xl" />
                        <div class="mt-auto flex items-center justify-between px-3.5 py-2.5 border-t border-brand-600/10">
                            <div class="flex items-center gap-3 text-xs text-ink-muted">
                                <span class="inline-flex items-center gap-1 text-rose-700 font-medium" x-show="p.report_count > 0"><i data-lucide="flag" class="w-3.5 h-3.5"></i><span x-text="p.report_count + ' report' + (p.report_count > 1 ? 's' : '')"></span></span>
                                <span class="inline-flex items-center gap-1" x-show="p.review_state === 'published'"><i data-lucide="heart" class="w-3.5 h-3.5"></i><span x-text="p.likes || 0"></span></span>
                                <button type="button" x-show="p.comments_count > 0" @click="openComments(p)" class="inline-flex items-center gap-1 hover:text-brand-700 hover:underline"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i><span x-text="p.comments_count"></span></button>
                            </div>
                            <button type="button" @click="moderate(p)" class="text-xs font-semibold text-brand-700 hover:underline whitespace-nowrap shrink-0" x-text="decisions(p).length ? (p.review_state === 'pending' ? 'Review' : 'Moderate') : 'View'"></button>
                        </div>
                    </article>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filtered.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="inbox" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600" x-text="view === 'pending' ? 'Nothing is waiting for review.' : 'No community posts here.'"></p>
                </div>
            </div>
            </div>

            <x-ui.modal title="Community Post" maxWidth="lg">
                <template x-if="selected">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full clay-blue font-extrabold flex items-center justify-center shrink-0 text-sm" x-text="initials(selected.author)"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink" x-text="selected.author"></p>
                                <p class="text-xs text-ink-muted" x-text="['Brgy. ' + selected.barangay, selected.category, when(selected.time)].filter(Boolean).join(' · ')"></p>
                            </div>
                            <span class="shrink-0 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="stateTones[selected.review_state]" x-text="stateLabels[selected.review_state] || selected.review_state"></span>
                        </div>
                        <p x-show="selected.body" class="text-sm text-slate-700 leading-relaxed whitespace-pre-line" x-text="selected.body"></p>
                        <x-balita.gallery media="selected.media" label="'Post by ' + selected.author" :english="true" class="rounded-xl" />

                        <div x-show="selected.report_count > 0" class="rounded-xl bg-rose-50/70 border border-rose-100 p-3">
                            <p class="text-xs font-semibold text-rose-800 mb-1.5" x-text="selected.report_count + ' resident report' + (selected.report_count > 1 ? 's' : '')"></p>
                            <ul class="space-y-1">
                                <template x-for="(r, i) in (selected.reports || [])" :key="i">
                                    <li class="text-xs text-rose-900"><span x-text="'“' + r.reason + '”'"></span> <span class="text-rose-700/80" x-text="'· ' + when(r.at)"></span></li>
                                </template>
                            </ul>
                        </div>
                        <p x-show="selected.moderation_reason" class="rounded-xl bg-slate-50 border border-brand-600/10 p-3 text-xs text-slate-700"><span class="text-ink-muted">Last decision's reason:</span> <span x-text="selected.moderation_reason"></span></p>
                        <p x-show="selected.review_state === 'withdrawn'" class="rounded-xl bg-slate-50 border border-brand-600/10 p-3 text-xs text-slate-700">Its author deleted this post. It is kept here for the record and cannot be moderated.</p>
                        <button type="button" x-show="selected.comments_count > 0" @click="openComments(selected)" class="text-xs font-semibold text-brand-700 hover:underline inline-flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i><span x-text="'Comments (' + selected.comments_count + ')'"></span></button>

                        <div x-show="decisions(selected).some((d) => d !== 'approve')">
                            <label for="community-reason" class="block text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1">Reason (required to hide or take down; the resident is shown it)</label>
                            <textarea id="community-reason" x-model="reason" rows="2" placeholder="e.g. Selling items is not allowed on Balita." class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <template x-for="d in decisions(selected)" :key="d">
                        <button type="button" @click="decide(d)" :disabled="processing || (d !== 'approve' && reason.trim().length < 5)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-semibold transition-colors disabled:opacity-50"
                            :class="d === 'approve' ? 'clay-blue' : (d === 'hide' ? 'bg-white border border-slate-200 text-ink hover:bg-slate-50' : 'bg-rose-600 text-white hover:bg-rose-700')"
                            x-text="decisionLabel(selected, d)"></button>
                    </template>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- The comments of an announcement or a community post, for officers:
             hide one (with a reason) or show it again. --}}
        <div x-data="balitaComments" @balita-comments.window="bcShow($event.detail)">
            <div x-data="{ get open() { return bcOpen; }, set open(v) { bcOpen = v; } }">
                <x-ui.modal title="Comments" maxWidth="lg">
                    <div class="space-y-3">
                        <p class="text-xs text-ink-muted"><span class="font-semibold text-ink-body" x-text="bcTitle"></span><span x-show="bcHiddenCount" x-text="' · ' + bcHiddenCount + ' hidden from residents'"></span></p>
                        <p x-show="bcLoading" class="text-xs text-ink-muted">Loading…</p>
                        <p x-show="bcFailed" x-cloak class="text-xs text-rose-700">Could not load the comments. <button type="button" class="underline" @click="bcLoad()">Try again</button></p>
                        <p x-show="!bcLoading && !bcFailed && !bcItems.length" class="text-xs text-ink-muted">No comments.</p>
                        <ul class="space-y-2.5">
                            <template x-for="c in bcItems" :key="c.id">
                                <li class="rounded-xl border px-3 py-2.5" :class="c.hidden ? 'border-slate-200 bg-slate-50' : 'border-brand-600/10 bg-white'">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-ink"><span x-text="c.author"></span> <span class="font-normal text-ink-muted" x-text="'· ' + when(c.created_at)"></span></p>
                                            <p class="text-sm mt-0.5 whitespace-pre-line break-words" :class="c.hidden ? 'text-ink-muted line-through' : 'text-slate-700'" x-text="c.body"></p>
                                            <p x-show="c.hidden" class="text-[11px] text-ink-muted mt-1" x-text="'Hidden' + (c.hidden_by ? ' by ' + c.hidden_by : '') + (c.hidden_reason ? ': ' + c.hidden_reason : '')"></p>
                                        </div>
                                        <div x-show="bcModerate" class="shrink-0">
                                            <button type="button" x-show="!c.hidden && bcHiding !== c.id" @click="bcHiding = c.id; bcReason = ''" class="text-[11px] font-semibold text-rose-700 hover:underline">Hide</button>
                                            <button type="button" x-show="c.hidden" :disabled="bcBusy" @click="bcRestore(c)" class="text-[11px] font-semibold text-brand-700 hover:underline">Show again</button>
                                        </div>
                                    </div>
                                    <form x-show="bcHiding === c.id" x-cloak @submit.prevent="bcHide(c)" class="mt-2 flex flex-wrap items-center gap-2">
                                        <input type="text" x-model="bcReason" maxlength="255" placeholder="Why hide it? (e.g. Abusive language)" aria-label="Reason for hiding the comment" class="flex-1 min-w-[180px] text-xs rounded-xl border border-slate-200 px-2.5 py-1.5 focus:ring-4 focus:ring-brand-100 outline-none">
                                        <button type="button" @click="bcHiding = null" class="text-[11px] text-ink-muted hover:underline">Cancel</button>
                                        <button type="submit" :disabled="bcBusy || bcReason.trim().length < 3" class="px-3 py-1.5 rounded-xl text-[11px] font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50">Hide comment</button>
                                    </form>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <x-slot:footer>
                        <x-ui.button size="sm" variant="secondary" @click="bcOpen = false">Close</x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>

        <x-balita.viewer />

        {{-- ============================= OFFICES (government directory) ============================= --}}
        <div
            x-show="tab === 'offices'" x-cloak
            x-data="{
                loading: true, failed: false,
                entries: [],
                open: false, mode: 'add', processing: false,
                form: { id: null, office: '', official_name: '', position: '', contact: '', email: '' },
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const res = await $api.get('/admin/directory', { params: { scope: 'government' } });
                        this.entries = res.data || [];
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                openAdd() { this.form = { id: null, office: '', official_name: '', position: '', contact: '', email: '' }; this.mode = 'add'; this.open = true; },
                openEdit(e) { this.form = { ...e }; this.mode = 'edit'; this.open = true; },
                async save() {
                    if (this.processing || !this.form.office.trim()) return;
                    this.processing = true;
                    try {
                        const payload = { office: this.form.office, official_name: this.form.official_name || null, position: this.form.position || null, contact: this.form.contact || null, email: this.form.email || null };
                        if (this.mode === 'add') { await $api.post('/admin/directory', payload); }
                        else { await $api.put('/admin/directory/' + this.form.id, payload); }
                        this.open = false;
                        this.$dispatch('toast', { message: this.mode === 'add' ? 'Office added.' : 'Office updated.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save.', variant: 'error' });
                    } finally { this.processing = false; }
                },
                async remove(e) {
                    if (!confirm('Remove ' + e.office + ' from the directory?')) return;
                    try {
                        await $api.delete('/admin/directory/' + e.id);
                        this.$dispatch('toast', { message: 'Office removed.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not remove.', variant: 'error' });
                    }
                },
            }"
            x-init="$store.session.whenReady().then(() => tabAllowed('offices') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the directory.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="flex items-center justify-end mb-3">
                <x-ui.button icon="plus" size="sm" @click="openAdd()">Add Office</x-ui.button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="e in entries" :key="e.id">
                    <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard">
                        <div class="relative h-24 bg-gradient-to-br from-brand-600 to-navy-800 flex items-center justify-center">
                            <i data-lucide="building-2" class="w-9 h-9 text-white/90"></i>
                        </div>
                        <div class="p-3.5">
                            <p class="text-sm font-semibold text-ink leading-snug mb-2.5" x-text="e.office"></p>
                            <div class="flex items-center gap-2 mb-2" x-show="e.official_name">
                                <div class="w-6 h-6 text-[9px] rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0" x-text="initials(e.official_name)"></div>
                                <span class="text-xs text-slate-600 truncate" x-text="e.official_name + (e.position ? ' — ' + e.position : '')"></span>
                            </div>
                            <p class="text-xs text-ink-muted flex items-center gap-1.5 mb-1" x-show="e.contact"><i data-lucide="phone" class="w-3 h-3 shrink-0"></i><span x-text="e.contact"></span></p>
                            <p class="text-xs text-ink-muted flex items-center gap-1.5 truncate" x-show="e.email"><i data-lucide="mail" class="w-3 h-3 shrink-0"></i><span x-text="e.email"></span></p>
                        </div>
                        <div class="flex border-t border-brand-600/10">
                            <button @click="openEdit(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                            <button @click="remove(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50/60 py-2.5 transition-colors border-l border-brand-600/10"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Remove</button>
                        </div>
                    </div>
                </template>
            </div>
            </div>

            <x-ui.modal :bare="true" maxWidth="md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-600/10">
                    <h3 class="text-sm font-semibold text-ink" x-text="mode === 'add' ? 'Add Office' : 'Edit Office'"></h3>
                    <button aria-label="Close" @click="open = false" class="text-ink-muted hover:text-slate-600"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <x-ui.input name="office_name" label="Office" x-model="form.office" />
                    <x-ui.input name="office_official" label="Official Name" x-model="form.official_name" />
                    <x-ui.input name="office_position" label="Position" x-model="form.position" />
                    <x-ui.input name="office_contact" label="Contact" x-model="form.contact" />
                    <x-ui.input name="office_email" type="email" label="Email" x-model="form.email" />
                </div>
                <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10">
                    <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-bind:disabled="processing" @click="save()">Save</x-ui.button>
                </div>
            </x-ui.modal>
        </div>

        {{-- ============================= BARANGAYS ============================= --}}
        <div
            x-show="tab === 'barangays'" x-cloak
            x-data="{
                loading: true, failed: false,
                barangays: [],
                open: false, selected: null, processing: false,
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const res = await $api.get('/barangays');
                        // seal_path comes back unprefixed (it names one of this
                        // site's own bundled assets, not something the backend
                        // hosts) — see window.staticAssetUrl's own comment.
                        this.barangays = (res.data || []).map((b) => ({ ...b, seal_path: window.staticAssetUrl(b.seal_path) }));
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                edit(b) { this.selected = { ...b }; this.open = true; },
                async save() {
                    if (this.processing) return;
                    this.processing = true;
                    try {
                        await $api.put('/admin/directory/barangay/' + encodeURIComponent(this.selected.name), {
                            punong_barangay: this.selected.punong_barangay, barangay_secretary: this.selected.barangay_secretary, contact: this.selected.contact,
                        });
                        this.open = false;
                        this.$dispatch('toast', { message: 'Brgy. ' + this.selected.name + ' directory entry updated.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save.', variant: 'error' });
                    } finally { this.processing = false; }
                },
            }"
            x-init="$store.session.whenReady().then(() => tabAllowed('barangays') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load barangays.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="b in barangays" :key="b.name">
                    <div x-show="$store.session.inScope(b.name)" x-cloak class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard">
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
                        <div class="p-3.5 pt-2.5">
                            <p class="text-xs text-ink-muted flex items-center gap-1.5 mb-1" x-show="b.contact"><i data-lucide="phone" class="w-3 h-3 shrink-0 text-ink-muted"></i><span x-text="b.contact"></span></p>
                            <p class="text-xs text-ink-muted flex items-center gap-1.5" x-show="b.barangay_secretary"><i data-lucide="user" class="w-3 h-3 shrink-0 text-ink-muted"></i><span x-text="'Secretary: ' + b.barangay_secretary"></span></p>
                        </div>
                        <button @click="edit(b)" x-show="$store.session.canPerm('comm_barangay_directory', 'edit')" class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50/60 py-2.5 border-t border-brand-600/10 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                    </div>
                </template>
            </div>

            <x-ui.modal title="Edit Barangay Directory Entry" maxWidth="md">
                <template x-if="selected">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 border border-brand-600/10 px-3.5 py-2.5">
                            <i data-lucide="map-pinned" class="w-4 h-4 text-ink-muted shrink-0"></i>
                            <p class="text-sm font-medium text-slate-700" x-text="'Brgy. ' + selected.name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Punong Barangay</label>
                            <input aria-label="Punong Barangay" type="text" x-model="selected.punong_barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay Secretary</label>
                            <input aria-label="Barangay Secretary" type="text" x-model="selected.barangay_secretary" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                            <input aria-label="Contact Number" type="text" x-model="selected.contact" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body font-mono focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                        </div>
                    </div>
                </template>
                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button size="sm" icon="check" x-bind:disabled="processing" @click="save()">Save Changes</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- ============================= BLOTTER ============================= --}}
        <div
            x-show="tab === 'blotter'" x-cloak
            x-data="{
                loading: true, failed: false,
                entries: [],
                search: '', barangayFilter: 'All',
                showAdd: false, processing: false,
                newEntry: { barangay: '{{ config('esperanza.barangays')[0] ?? '' }}', incident_date: '', incident_type: '', narrative: '' },
                async load() {
                    this.loading = true; this.failed = false;
                    try {
                        const res = await $api.getAll('/admin/blotter');
                        this.entries = res.data || [];
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                get filtered() {
                    return this.entries.filter(b =>
                        (this.barangayFilter === 'All' || b.barangay === this.barangayFilter) &&
                        (this.search === '' || (b.incident_type || '').toLowerCase().includes(this.search.toLowerCase()) || (b.narrative || '').toLowerCase().includes(this.search.toLowerCase()))
                    );
                },
                async addEntry() {
                    if (this.processing || !this.newEntry.incident_date || !this.newEntry.incident_type.trim() || !this.newEntry.narrative.trim()) return;
                    this.processing = true;
                    try {
                        await $api.post('/admin/blotter', this.newEntry);
                        this.showAdd = false;
                        this.newEntry = { barangay: this.barangayNames?.[0] || '', incident_date: '', incident_type: '', narrative: '' };
                        this.$dispatch('toast', { message: 'Blotter entry logged.', variant: 'success' });
                        await this.load();
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not log this entry.', variant: 'error' });
                    } finally { this.processing = false; }
                },
            }"
            x-init="$store.session.whenReady().then(() => tabAllowed('blotter') && load())"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-16" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load the blotter.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search incident type or details" type="text" x-model="search" placeholder="Search incident type or details..." class="w-full pl-9 pr-3 py-2 text-xs rounded-2xl focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm">
                </div>
                <select aria-label="Blotter barangay filter" x-model="barangayFilter" class="text-xs rounded-2xl px-3 py-2 focus:outline-none transition-colors border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100">
                    <option value="All">All Barangays</option>
                    <template x-for="bn in barangayNames" :key="bn"><option :value="bn" x-text="bn"></option></template>
                </select>
                <button @click="showAdd = true; $nextTick(() => window.renderIcons?.())" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-3.5 py-2 shadow-card transition-colors ml-auto">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Entry
                </button>
            </div>

            <div class="space-y-2.5">
                <template x-for="b in filtered" :key="b.ref">
                    <div class="surface-glass rounded-[26px] p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-slate-100 text-ink-muted flex items-center justify-center shrink-0"><i data-lucide="book-text" class="w-4 h-4"></i></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink truncate" x-text="b.incident_type"></p>
                                    <p class="text-xs text-ink-muted" x-text="'Brgy. ' + b.barangay + ' — ' + b.incident_date + ' — ' + b.ref"></p>
                                </div>
                            </div>
                            <x-ui.badge statusBind="b.status" />
                        </div>
                        <p class="text-sm text-slate-600 leading-relaxed pl-[46px]" x-show="!b.restricted" x-text="b.narrative"></p>
                        <p class="text-xs text-ink-muted italic pl-[46px] flex items-center gap-1.5" x-show="b.restricted"><i data-lucide="lock" class="w-3 h-3"></i>You don't have permission to view this incident's details.</p>
                    </div>
                </template>
                <div class="flex flex-col items-center text-center py-12 bg-white border border-brand-600/10 rounded-2xl" x-show="filtered.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No blotter entries match your filters.</p>
                </div>
            </div>
            </div>

            <div x-show="showAdd" x-cloak
                x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy-950/60 backdrop-blur-sm" @click.self="showAdd = false"
                role="dialog" aria-modal="true" @keydown.escape.window="showAdd = false">
                <div x-transition:enter="transition duration-[200ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="bg-white rounded-2xl shadow-float w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10">
                        <h3 class="text-sm font-semibold text-ink">Log Blotter Entry</h3>
                        <button aria-label="Close" @click="showAdd = false" class="text-ink-muted hover:text-slate-600 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="blotter-barangay">Barangay</label>
                            <select id="blotter-barangay" x-model="newEntry.barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <template x-for="bn in barangayNames" :key="bn"><option :value="bn" x-text="bn"></option></template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="blotter-date">Date</label>
                                <input id="blotter-date" type="date" x-model="newEntry.incident_date" :max="new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10)" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="blotter-type">Incident Type</label>
                                <input id="blotter-type" type="text" x-model="newEntry.incident_type" placeholder="e.g. Noise Complaint" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="blotter-narrative">Details</label>
                            <textarea id="blotter-narrative" rows="4" x-model="newEntry.narrative" placeholder="Describe the incident..." class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-brand-600/10 bg-slate-50/60">
                        <x-ui.button variant="secondary" size="sm" @click="showAdd = false">Cancel</x-ui.button>
                        <x-ui.button size="sm" x-bind:disabled="processing" @click="addEntry()">Log Entry</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================= FEEDBACK ============================= --}}
        @include('admin.communications.feedback')

        </div>
    </div>
</x-layouts.admin>
