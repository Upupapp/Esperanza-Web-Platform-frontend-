@php
    $tab = $tab ?? 'balita';
@endphp

{{-- One sidebar entry, Balita, for its tabs: the Blotter and residents'
     Feedback are tabs here (owner, 2026-10-07: as few sidebar entries as
     possible). /blotter, /banners and /feedback (and the old
     /admin/settings/support) still open this page on that tab. The directory
     (Offices, Barangays) moved to Settings > Direktoryo (owner, 2026-10-21);
     its old addresses open it there. --}}
{{-- On its Feedback tab the page is titled Feedback, not Balita: when opened
     there (the sidebar entry of officers who only answer tickets) and when the
     tab is chosen (syncHeader) (2026-10-15). --}}
@php
    $feedbackSubtitle = 'Tickets from residents, routed to the office that owns them.';
    $balitaSubtitle = "Announcements, banners and occasions, events, the barangay blotter and residents' feedback.";
@endphp
<x-layouts.admin :title="$tab === 'feedback' ? 'Feedback' : 'Balita'" :subtitle="$tab === 'feedback' ? $feedbackSubtitle : $balitaSubtitle" active="communications">
    <div
        x-data="{
            tab: '{{ $tab }}',
            // One submodule per tab. The Barangay Secretary holds only their
            // directory entry and opened Balita to 'Something went wrong' and a
            // 'Walang pahintulot' pop-up per tab (QA run 2026-10-05).
            tabSubs: { balita: 'comm_news', banners: 'comm_banners', events: 'comm_events', blotter: 'comm_directory', feedback: 'comm_feedback' },
            tabAllowed(t) { return $store.session.canPerm(this.tabSubs[t], 'view'); },
            // Each tab's own address, so a reload stays on it; a tab without
            // a route of its own is ?tab= (scope round 2026-10-15).
            tabPaths: { balita: '/admin/communications', banners: '/admin/communications/banners', blotter: '/admin/communications/blotter', feedback: '/admin/communications/feedback' },
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
                // The directory is Settings > Direktoryo now (2026-10-21), from
                // ?tab= or the old /admin/communications/offices address.
                const old = ['offices', 'barangays'].includes(asked) ? asked : (['offices', 'barangays'].includes(this.tab) ? this.tab : null);
                if (old) {
                    window.location.replace('/admin/settings/directory' + (old === 'barangays' ? '?view=barangays' : ''));
                    return;
                }
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
            // Who does what here, for the roles that run Balita (owner,
            // 2026-10-21: who is the Municipal Information Officer's lead?).
            // The review alerts follow the same chain (StaffAlerts::reviewChain).
            get roleNote() {
                return {
                    super_admin: 'Super Administrator: you can do everything here. Day to day the Municipal Information Office runs Balita and the Municipal Administrator leads it, so posts and events sent for review reach you only when neither is set up.',
                    municipal_admin: 'Municipal Administrator: for the Office of the Mayor, you lead the Municipal Information Office. You can do everything here, and what the Information Office sends for a second look comes to you.',
                    info_officer: 'Municipal Information Officer: you publish Balita, banners, occasions and events for the whole municipality, and review what the offices send. Your lead is the Municipal Administrator: save a draft and send it for review to get their second look.',
                    department_head: 'Department Head: you approve and publish your office’s Balita posts; the Municipal Information Office can too.',
                    mdrrmo_head: 'MDRRMO Head: you write, approve and publish Balita posts, such as disaster updates, and approve or delete what your staff send; the Municipal Information Office can too. An urgent warning belongs in Sakuna > Alerts, which also reaches residents on their phones.',
                }[this.$store.session.account?.roleKey] || '';
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

        <p x-show="roleNote" x-cloak class="mb-3 flex items-start gap-2 rounded-2xl bg-white/70 border border-brand-600/10 px-3.5 py-2.5 text-xs text-ink-body"><i data-lucide="users-round" class="w-4 h-4 text-brand-600 shrink-0 mt-px"></i><span x-text="roleNote"></span></p>

        {{-- max-w-full: the tabs scroll inside the bar on a phone instead of
             widening the page (scope round 2026-10-15). --}}
        <div role="tablist" aria-label="Communications sections" class="flex items-center gap-1 bg-white rounded-[14px] border border-slate-200 px-2 max-w-full mb-4 overflow-x-auto [scrollbar-width:none]">
            <button x-show="tabAllowed('balita')" @click="go('balita')" role="tab" :aria-selected="tab === 'balita' ? 'true' : 'false'" class="shrink-0 h-[52px] px-4 border-b-[3px] text-sm transition-colors flex items-center gap-2 whitespace-nowrap focus:outline-none focus-visible:bg-slate-100" :class="tab === 'balita' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-ink-muted font-medium hover:bg-slate-100 hover:text-ink'"><i data-lucide="megaphone" class="w-[18px] h-[18px]"></i>Balita</button>
            <button x-show="tabAllowed('banners')" @click="go('banners')" role="tab" :aria-selected="tab === 'banners' ? 'true' : 'false'" class="shrink-0 h-[52px] px-4 border-b-[3px] text-sm transition-colors flex items-center gap-2 whitespace-nowrap focus:outline-none focus-visible:bg-slate-100" :class="tab === 'banners' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-ink-muted font-medium hover:bg-slate-100 hover:text-ink'"><i data-lucide="gallery-horizontal" class="w-[18px] h-[18px]"></i>Banners &amp; Occasions</button>
            <button x-show="tabAllowed('events')" @click="go('events')" role="tab" :aria-selected="tab === 'events' ? 'true' : 'false'" class="shrink-0 h-[52px] px-4 border-b-[3px] text-sm transition-colors flex items-center gap-2 whitespace-nowrap focus:outline-none focus-visible:bg-slate-100" :class="tab === 'events' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-ink-muted font-medium hover:bg-slate-100 hover:text-ink'"><i data-lucide="calendar-days" class="w-[18px] h-[18px]"></i>Events</button>
            <button x-show="tabAllowed('blotter')" @click="go('blotter')" role="tab" :aria-selected="tab === 'blotter' ? 'true' : 'false'" class="shrink-0 h-[52px] px-4 border-b-[3px] text-sm transition-colors flex items-center gap-2 whitespace-nowrap focus:outline-none focus-visible:bg-slate-100" :class="tab === 'blotter' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-ink-muted font-medium hover:bg-slate-100 hover:text-ink'"><i data-lucide="book-text" class="w-[18px] h-[18px]"></i>Blotter</button>
            <button x-show="tabAllowed('feedback')" @click="go('feedback')" role="tab" :aria-selected="tab === 'feedback' ? 'true' : 'false'" class="shrink-0 h-[52px] px-4 border-b-[3px] text-sm transition-colors flex items-center gap-2 whitespace-nowrap focus:outline-none focus-visible:bg-slate-100" :class="tab === 'feedback' ? 'border-brand-600 text-brand-600 font-semibold' : 'border-transparent text-ink-muted font-medium hover:bg-slate-100 hover:text-ink'"><i data-lucide="message-square-reply" class="w-[18px] h-[18px]"></i>Feedback<span x-show="feedbackOpen" x-cloak class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700" :title="feedbackOpen + ' open'"><span x-text="feedbackOpen"></span><span class="sr-only"> open</span></span></button>
        </div>

        @include('admin.communications.balita')

        {{-- ============================= EVENTS ============================= --}}
        {{-- Logic in resources/js/scope-admin-records.js (adminEvents). An event
             reaches residents (2026-10-21): publishing it tells them in the bell
             and on their phones and, unless "Post in Balita too" is unticked,
             posts it in Balita with its photos. A published event can still be
             put right, and withdrawing one before its day cancels it.
             Round 2 (2026-10-30): residents can register for an event, and
             those who attend can receive a certificate - set in its form; the
             day itself is its Registrations window (event-desk partial). --}}
        <div
            x-show="tab === 'events'" x-cloak
            x-data="adminEvents(@js(config('esperanza.barangays')))"
            x-init="$store.session.whenReady().then(() => tabAllowed('events') && load())"
            x-on:events-edit.window="editByKey($event.detail)"
        >
            <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /></div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load events.'" retryExpr="load()" :retryable="true" /></div>

            <div x-show="!loading && !failed">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card layout="compact" label="Published" valueBind="count('Released')" icon="calendar-check" color="brand" :delay="0" />
                <x-ui.stat-card layout="compact" label="Pending Review" valueBind="count('Pending Review')" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card layout="compact" label="Drafts and sent back" valueBind="count('Draft') + count('Rejected')" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card layout="compact" label="Reminders set by residents" valueBind="events.filter(e => e.status === 'Released' && e.upcoming).reduce((n, e) => n + (e.reminders_count || 0), 0)" icon="bell-ring" color="green" :delay="120" />
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
                    <x-ui.button x-show="can('create')" @click="openNew()" icon="plus" size="sm" class="shrink-0">New Event</x-ui.button>
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
                    <div class="surface-glass rounded-[26px] overflow-hidden hover:shadow-card-hover transition-all duration-[220ms] ease-standard flex flex-col">
                        <img x-show="e.image_url" :src="e.image_url" alt="" loading="lazy" class="w-full h-32 object-cover">
                        <div class="p-3.5 flex items-start gap-3">
                            <div class="shrink-0 w-12 h-12 rounded-xl bg-slate-50 border border-brand-600/10 flex flex-col items-center justify-center overflow-hidden">
                                <span class="w-full text-center text-[9px] font-bold text-white py-0.5" :class="categoryColor[e.category] || 'bg-slate-400'" x-text="e.date ? new Date(e.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' }).toUpperCase() : ''"></span>
                                <span class="text-sm font-bold text-ink leading-none mt-1" x-text="e.date ? new Date(e.date + 'T00:00:00').getDate() : ''"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                    <span class="text-[10px] font-semibold text-white rounded-full px-2 py-0.5" :class="categoryColor[e.category] || 'bg-slate-400'" x-text="e.category"></span>
                                    <x-ui.badge statusBind="e.status" />
                                    <span x-show="e.status === 'Released' && !e.upcoming" class="text-[10px] font-medium text-ink-muted bg-slate-100 rounded-full px-2 py-0.5">Ended</span>
                                </div>
                                <p class="text-sm font-semibold text-ink leading-snug line-clamp-2" x-text="e.title || e.name"></p>
                                <p class="text-xs text-ink-muted mt-1 flex items-center gap-1" x-show="e.time"><i data-lucide="clock" class="w-3 h-3 shrink-0"></i><span x-text="e.time"></span></p>
                                <p class="text-xs text-ink-muted mt-0.5 flex items-center gap-1 truncate" x-show="e.venue"><i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i><span x-text="e.venue"></span></p>
                            </div>
                        </div>
                        <div class="px-3.5 pb-3 space-y-2">
                            <p x-show="e.description" class="text-xs text-slate-600 line-clamp-2" x-text="e.description"></p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5" :class="e.barangay ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700'">
                                    <i :data-lucide="e.barangay ? 'map-pin' : 'landmark'" class="w-2.5 h-2.5"></i>
                                    <span x-text="e.barangay ? 'Brgy. ' + e.barangay : 'Municipality-wide'"></span>
                                </span>
                                <a x-show="e.balita_post_id" :href="'/admin/communications/?tab=balita&post=' + e.balita_post_id" class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5 bg-amber-50 text-amber-700 hover:underline"><i data-lucide="newspaper" class="w-2.5 h-2.5"></i>In Balita</a>
                                <span x-show="!e.balita_post_id && e.post_to_balita && !['Released', 'Archived'].includes(e.status)" class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5 bg-slate-100 text-ink-muted"><i data-lucide="newspaper" class="w-2.5 h-2.5"></i>Goes into Balita when published</span>
                                <span x-show="e.reminders_count" class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5 bg-emerald-50 text-emerald-700"><i data-lucide="bell-ring" class="w-2.5 h-2.5"></i><span x-text="e.reminders_count + (e.reminders_count === 1 ? ' resident' : ' residents') + ' want a reminder'"></span></span>
                                <span x-show="e.registration?.open || e.registration?.count || e.attended_count" class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5" :class="regTone(e)"><i data-lucide="ticket" class="w-2.5 h-2.5"></i><span x-text="regChip(e)"></span></span>
                                <span x-show="e.certificate?.enabled" class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5" :class="e.certificates_issued_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"><i data-lucide="award" class="w-2.5 h-2.5"></i><span x-text="e.certificates_issued_at ? 'Certificates issued' : 'Certificates for attendees'"></span></span>
                            </div>
                            <p x-show="e.status === 'Rejected' && e.review_remarks" class="text-[11px] text-red-700 bg-red-50 rounded-xl px-2.5 py-1.5"><span class="font-semibold">Sent back:</span> <span x-text="e.review_remarks"></span></p>
                        </div>
                        <div class="flex border-t border-brand-600/10 mt-auto">
                            <button x-show="e.status === 'Pending Review' && canMove(e, 'Approved')" @click="openReview(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-orange-600 hover:bg-orange-50/60 transition-colors"><i data-lucide="history" class="w-3.5 h-3.5"></i>Review</button>
                            <button x-show="canEdit(e)" @click="openEdit(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-600 hover:bg-brand-50/60 transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit</button>
                            <button x-show="canDesk(e)" @click="openDesk(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-indigo-600 hover:bg-indigo-50/60 transition-colors whitespace-nowrap"><i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>Registrations</button>
                            <button x-show="e.status === 'Draft' && canMove(e, 'Pending Review')" @click="submit(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-600 hover:bg-brand-50/60 transition-colors"><i data-lucide="send" class="w-3.5 h-3.5"></i>Submit for Review</button>
                            <button x-show="e.status === 'Approved' && canMove(e, 'Released')" @click="release(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-emerald-600 hover:bg-emerald-50/60 transition-colors"><i data-lucide="upload" class="w-3.5 h-3.5"></i>Publish</button>
                            <button x-show="isCancel(e) && canMove(e, 'Archived')" @click="openCancel(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-red-600 hover:bg-red-50/60 transition-colors"><i data-lucide="calendar-x" class="w-3.5 h-3.5"></i>Cancel Event</button>
                            <button x-show="!isCancel(e) && ['Draft', 'Approved', 'Released', 'Rejected'].includes(e.status) && canMove(e, 'Archived')" @click="archive(e)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-ink-muted hover:bg-white/60 transition-colors"><i data-lucide="archive" class="w-3.5 h-3.5"></i>Archive</button>
                            <button x-show="canDelete(e)" @click="deleteEvent(e)" :disabled="processing" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-rose-700 hover:bg-rose-50/60 transition-colors disabled:opacity-50"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Delete</button>
                        </div>
                    </div>
                </template>
                <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filtered.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600">No events match these filters.</p>
                </div>
            </div>
            </div>

            {{-- New and Edit share this form, drawn bare for a title that says
                 which, as Balita's is. --}}
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
                    <p x-show="editing && editing.status !== 'Released'" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'Editing an event in ' + (editing?.status || '') + '. It stays there until it is sent on.'"></span></p>
                    <p x-show="editing && editing.status === 'Released'" x-cloak class="text-xs text-amber-800 bg-amber-50 rounded-xl px-3 py-2 flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i><span>This event is published. Residents who can see it are told of a new date, time or venue, and its Balita post is updated too.</span></p>
                    <x-ui.input name="event_title" label="Event Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" x-model="form.name" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input name="event_date" type="date" label="Date" x-model="form.date" />
                        <x-ui.input name="event_time" type="time" label="Time" x-model="form.time" />
                    </div>
                    <x-ui.input name="event_venue" label="Venue" placeholder="e.g. Municipal Plaza" x-model="form.venue" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="event-description">What it is about <span class="text-ink-muted font-normal">(optional)</span></label>
                        <textarea id="event-description" x-model="form.description" rows="4" maxlength="2000" placeholder="Who it is for, what to bring, who to ask" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        <p class="text-[11px] text-ink-muted text-right tabular-nums" x-text="form.description.length + ' / 2000'"></p>
                    </div>
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
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Every resident sees it and is told</p>
                            </button>
                            <button type="button" @click="form.audience = 'Barangay'" :aria-pressed="form.audience === 'Barangay'" class="text-left rounded-2xl border p-3 transition-all duration-[160ms] ease-standard" :class="form.audience === 'Barangay' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                <p class="text-xs font-semibold text-ink">Specific Barangay</p>
                                <p class="text-[10.5px] text-ink-muted mt-0.5">Only that barangay sees it and is told</p>
                            </button>
                        </div>
                        <div x-show="form.audience === 'Barangay'" x-cloak x-transition:enter="transition ease-out duration-[160ms]" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="mt-2.5">
                            <select aria-label="Event barangay" x-model="form.barangay" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="b === form.barangay"></option></template>
                            </select>
                        </div>
                    </div>

                    {{-- Photos: the first is the event's poster on the calendar,
                         and all of them go with its Balita post. --}}
                    <template x-for="k in [formKey]" :key="k">
                        <x-balita.media-field base="/admin/balita" model="media" existing="(editing ? (editing.media || []) : [])" :english="true" />
                    </template>

                    <label class="flex items-start gap-2.5 rounded-2xl border border-slate-200 p-3 cursor-pointer" :class="editing && editing.balita_post ? 'opacity-60 cursor-not-allowed' : ''">
                        <input type="checkbox" x-model="form.post_to_balita" :disabled="!!(editing && editing.balita_post)" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold text-ink">Post in Balita too</span>
                            <span class="block text-[11px] text-ink-muted" x-text="editing && editing.balita_post ? 'It is already in Balita. Its post follows the changes you make here.' : 'When it is published, it also goes into the Balita feed with its photos, where residents can like, comment and share it. Residents are told once, about the event.'"></span>
                        </span>
                    </label>

                    @include('admin.communications.event-form-extras')
                </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10 bg-white shrink-0">
                    <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing || media.busy" @click="saveEvent(false)"><span x-text="editing ? 'Save Changes' : 'Save as Draft'">Save as Draft</span></x-ui.button>
                    <x-ui.button size="sm" icon="send" x-show="!editing || !['Pending Review', 'Released'].includes(editing.status)" x-bind:disabled="processing || media.busy" @click="saveEvent(true)"><span x-text="!editing ? 'Submit for Review' : (editing.status === 'Rejected' ? 'Save and Resubmit' : 'Save and Submit for Review')">Submit for Review</span></x-ui.button>
                </div>
            </x-ui.modal>

            <div x-data="{ get open() { return reviewOpen; }, set open(v) { reviewOpen = v; } }">
            <x-ui.modal title="Review Event" maxWidth="lg">
                <template x-if="selected">
                    <div>
                        <img x-show="selected.image_url" :src="selected.image_url" alt="" class="w-full max-h-48 object-cover rounded-xl mb-3">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-ink-muted" x-text="(selected.barangay ? 'Brgy. ' + selected.barangay : 'Municipality-wide') + (selected.date ? (' · ' + dayLabel(selected.date)) : '')"></p>
                            </div>
                            <span class="text-[10px] font-medium text-white rounded-full px-2 py-0.5 shrink-0" :class="categoryColor[selected.category] || 'bg-slate-400'" x-text="selected.category"></span>
                        </div>
                        <p class="text-sm font-semibold text-ink mb-2" x-text="selected.title || selected.name"></p>
                        <p x-show="selected.description" class="text-xs text-slate-600 whitespace-pre-line mb-3" x-text="selected.description"></p>
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
                        <div class="rounded-xl bg-slate-50 border border-brand-600/10 p-3.5 text-xs text-ink-muted mb-3 space-y-1">
                            <p x-text="'Approving publishes it to the Events calendar and tells ' + (selected.barangay ? 'the residents of Brgy. ' + selected.barangay : 'every resident') + ' in their bell and on their phones.'"></p>
                            <p x-text="selected.post_to_balita ? 'It also goes into Balita with its photos.' : 'It does not go into Balita.'"></p>
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

            <div x-data="{ get open() { return cancelOpen; }, set open(v) { cancelOpen = v; } }">
            <x-ui.modal title="Cancel Event" maxWidth="md">
                <template x-if="selected">
                    <div class="space-y-3">
                        <p class="text-sm text-ink"><span class="font-semibold" x-text="selected.title || selected.name"></span> <span class="text-ink-muted" x-text="'on ' + dayLabel(selected.date)"></span></p>
                        <p class="text-xs text-ink-muted" x-text="'It comes off the Events calendar' + (selected.balita_post_id ? ' and out of Balita' : '') + ', and ' + (selected.barangay ? 'the residents of Brgy. ' + selected.barangay : 'every resident') + ' who can see it, and anyone who asked for a reminder, are told it is cancelled.'"></p>
                        <div>
                            <label class="block text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1" for="event-cancel-reason">Reason residents will read (optional)</label>
                            <textarea id="event-cancel-reason" x-model="cancelReason" rows="2" maxlength="500" placeholder="e.g. Postponed because of the typhoon. A new date will be announced." class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>
                    </div>
                </template>
                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="cancelOpen = false">Keep It</x-ui.button>
                    <x-ui.button variant="danger" size="sm" icon="calendar-x" x-bind:disabled="processing" @click="confirmCancel()">Cancel Event</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
            </div>

            @include('admin.communications.event-desk')
            {{-- An attendee's certificate, opened from Registrations: after it, so it opens above. --}}
            <x-welfare.certificate />
        </div>

        {{-- ============================= BANNERS & OCCASIONS ============================= --}}
        {{-- Owner, 2026-10-21: "Banners" became "Banners & Occasions", two views of
             one tab: the banners in the Home carousel, and the Occasion & Pop-up
             manager. Both are comm_banners'. --}}
        <div
            x-show="tab === 'banners'" x-cloak
            x-data="{
                view: new URLSearchParams(window.location.search).get('view') === 'occasions' ? 'occasions' : 'banners',
                show(v) {
                    this.view = v;
                    // Each view shows what is saved now: a banner changed in one
                    // is current in the other (2026-10-21).
                    window.dispatchEvent(new CustomEvent('banners-view-shown', { detail: v }));
                    try { history.replaceState(history.state, '', '/admin/communications/banners' + (v === 'occasions' ? '?view=occasions' : '')); } catch (e) { /* the view still changes */ }
                },
            }"
        >
            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit max-w-full overflow-x-auto mb-4" role="group" aria-label="Banners and occasions">
                <button type="button" @click="show('banners')" :aria-pressed="view === 'banners'" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors whitespace-nowrap inline-flex items-center gap-1.5" :class="view === 'banners' ? 'bg-white text-ink shadow-sm font-semibold' : 'text-ink-muted hover:text-slate-700'"><i data-lucide="gallery-horizontal" class="w-3.5 h-3.5"></i>Banners</button>
                <button type="button" @click="show('occasions')" :aria-pressed="view === 'occasions'" class="px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors whitespace-nowrap inline-flex items-center gap-1.5" :class="view === 'occasions' ? 'bg-white text-ink shadow-sm font-semibold' : 'text-ink-muted hover:text-slate-700'"><i data-lucide="party-popper" class="w-3.5 h-3.5"></i>Occasions &amp; Pop-ups</button>
            </div>
            <div x-show="view === 'banners'">@include('admin.communications.banners')</div>
            <div x-show="view === 'occasions'" x-cloak>@include('admin.communications.occasions')</div>
        </div>

        {{-- The comments of an announcement, for officers:
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
