{{-- ============================= BALITA (one feed) ============================= --}}
{{-- Logic in resources/js/scope-admin-records.js (adminBalita). One feed of the
     LGU's posts (owner, 2026-10-21): posts are no longer split by kind - the pop-ups
     residents see on an occasion are in Banners & Occasions. Any post can be
     scheduled. Every button is a move the server lists for the post
     (available_transitions) and the officer may make. --}}
<div
    x-show="tab === 'balita'"
    x-data="adminBalita(@js(config('esperanza.barangays')))"
    x-init="$store.session.whenReady().then(() => tabAllowed('balita') && load())"
>
    <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-24 opacity-80" /></div>
    <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load Balita.'" retryExpr="load()" :retryable="true" /></div>

    {{-- The feed (redesign 2026-10-26): one column of posts as residents see
         them, the composer and the filters above; the counts and what needs
         this officer beside it on a computer, above it on a phone. --}}
    <div x-show="!loading && !failed" class="flex flex-col lg:flex-row gap-6 items-start justify-center">

    {{-- Overview: the four counts, each a filter of the feed --}}
    <aside aria-label="Balita overview" class="order-first lg:order-last w-full lg:w-[300px] shrink-0 lg:sticky lg:top-4 space-y-1">
        <div class="flex items-center justify-between gap-2">
            <h2 class="px-2 py-1 text-[17px] font-semibold text-ink-body">Overview</h2>
            {{-- Balita settings: the photo watermark (welfare round 2026-10-29), for anyone who can post
                 (comm_news create; owner: "should apply to anyone who can post"). --}}
            <button type="button" x-show="$store.session.canPerm('comm_news', 'create')" x-cloak @click="window.dispatchEvent(new CustomEvent('balita-settings'))" class="h-9 px-2.5 rounded-[10px] inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink-body hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors"><i data-lucide="settings" class="w-4 h-4"></i>Balita settings</button>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-1 gap-1">
        <button type="button" @click="showStatus('Released')" :aria-pressed="!scheduledOnly && status === 'Released' ? 'true' : 'false'" class="w-full min-h-14 px-2 rounded-[10px] flex items-center gap-2 lg:gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors" :class="!scheduledOnly && status === 'Released' && 'bg-slate-200/60'">
            <span class="w-9 h-9 rounded-[10px] bg-brand-600 text-white grid place-items-center shrink-0"><i data-lucide="megaphone" class="w-[18px] h-[18px]"></i></span>
            <span class="flex-1 min-w-0 text-[13px] lg:text-[15px] leading-tight font-medium text-ink">Published</span>
            <span class="text-lg font-bold text-ink tabular-nums" x-text="card('published')"></span>
        </button>
        <button type="button" @click="showStatus('Pending Review')" :aria-pressed="!scheduledOnly && status === 'Pending Review' ? 'true' : 'false'" class="w-full min-h-14 px-2 rounded-[10px] flex items-center gap-2 lg:gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors" :class="!scheduledOnly && status === 'Pending Review' && 'bg-slate-200/60'">
            <span class="w-9 h-9 rounded-[10px] bg-orange-700 text-white grid place-items-center shrink-0"><i data-lucide="file-clock" class="w-[18px] h-[18px]"></i></span>
            <span class="flex-1 min-w-0 text-[13px] lg:text-[15px] leading-tight font-medium text-ink">Pending review</span>
            <span class="text-lg font-bold text-ink tabular-nums" x-text="card('pending_review')"></span>
        </button>
        <button type="button" @click="showStatus('Draft')" :aria-pressed="!scheduledOnly && status === 'Draft' ? 'true' : 'false'" class="w-full min-h-14 px-2 rounded-[10px] flex items-center gap-2 lg:gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors" :class="!scheduledOnly && status === 'Draft' && 'bg-slate-200/60'">
            <span class="w-9 h-9 rounded-[10px] bg-purple-700 text-white grid place-items-center shrink-0"><i data-lucide="pencil-line" class="w-[18px] h-[18px]"></i></span>
            <span class="flex-1 min-w-0 text-[13px] lg:text-[15px] leading-tight font-medium text-ink">Drafts and sent back</span>
            <span class="text-lg font-bold text-ink tabular-nums" x-text="card('drafts')"></span>
        </button>
        <button type="button" @click="showScheduled()" :aria-pressed="scheduledOnly ? 'true' : 'false'" class="w-full min-h-14 px-2 rounded-[10px] flex items-center gap-2 lg:gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors" :class="scheduledOnly && 'bg-slate-200/60'">
            <span class="w-9 h-9 rounded-[10px] bg-teal-700 text-white grid place-items-center shrink-0"><i data-lucide="calendar-clock" class="w-[18px] h-[18px]"></i></span>
            <span class="flex-1 min-w-0 text-[13px] lg:text-[15px] leading-tight font-medium text-ink">Scheduled</span>
            <span class="text-lg font-bold text-ink tabular-nums" x-text="card('scheduled')"></span>
        </button>
        </div>

        <div class="h-px bg-slate-300/70 mx-2 my-2" aria-hidden="true"></div>
        <h2 class="px-2 py-1 text-[17px] font-semibold text-ink-body">Needs your attention</h2>
        <button type="button" x-show="waitingForMe" x-cloak @click="showStatus('Pending Review')" class="w-full px-2 py-2.5 rounded-[10px] flex items-center gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors">
            <span class="w-9 h-9 rounded-full bg-orange-50 text-orange-700 grid place-items-center shrink-0"><i data-lucide="file-search" class="w-[18px] h-[18px]"></i></span>
            <span class="min-w-0">
                <span class="block text-[15px] text-ink"><strong class="font-semibold" x-text="waitingForMe + (waitingForMe === 1 ? ' post' : ' posts')"></strong> <span x-text="waitingForMe === 1 ? 'waits for your review' : 'wait for your review'"></span></span>
                <span class="block text-[13px] text-ink-muted">Approve it, or send it back with notes</span>
            </span>
        </button>
        <button type="button" x-show="sentBackToMe" x-cloak @click="showStatus('Rejected')" class="w-full px-2 py-2.5 rounded-[10px] flex items-center gap-3 text-left hover:bg-slate-200/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors">
            <span class="w-9 h-9 rounded-full bg-rose-50 text-rose-700 grid place-items-center shrink-0"><i data-lucide="undo-2" class="w-[18px] h-[18px]"></i></span>
            <span class="min-w-0">
                <span class="block text-[15px] text-ink"><strong class="font-semibold" x-text="sentBackToMe + (sentBackToMe === 1 ? ' post' : ' posts')"></strong> <span x-text="sentBackToMe === 1 ? 'was sent back' : 'were sent back'"></span></span>
                <span class="block text-[13px] text-ink-muted">Read the notes, change it and send it again</span>
            </span>
        </button>
        <p x-show="!waitingForMe && !sentBackToMe" class="px-2 py-1 text-[13px] text-ink-muted">Nothing waits for you.</p>
    </aside>

    <div class="w-full max-w-[680px] min-w-0 space-y-4">
        {{-- Share an update: opens the composer, with photos, a time or an advisory to start --}}
        <div x-show="can('create')" class="bg-white rounded-[14px] border border-slate-200 shadow-sm px-3 sm:px-4 py-3 flex items-center gap-1 sm:gap-3">
            <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="hidden sm:block w-[42px] h-[42px] rounded-full object-cover shrink-0">
            <button type="button" @click="openNew()" class="flex-1 min-w-0 h-11 px-[18px] rounded-full bg-slate-100 hover:bg-slate-200 text-left text-base text-ink-muted truncate focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors">Share an update with residents…</button>
            <button type="button" @click="compose('media')" aria-label="Post with photos or videos" title="Post with photos or videos" class="w-11 h-11 rounded-full grid place-items-center shrink-0 text-emerald-600 hover:bg-emerald-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-100 transition-colors"><i data-lucide="images" class="w-6 h-6"></i></button>
            <button type="button" @click="compose('schedule')" aria-label="Schedule a post" title="Schedule a post" class="w-11 h-11 rounded-full grid place-items-center shrink-0 text-brand-600 hover:bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors"><i data-lucide="calendar-clock" class="w-6 h-6"></i></button>
            <button type="button" @click="compose('advisory')" aria-label="Post an advisory" title="Post an advisory" class="w-11 h-11 rounded-full grid place-items-center shrink-0 text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-100 transition-colors"><i data-lucide="megaphone" class="w-6 h-6"></i></button>
        </div>

        {{-- Every officer writes Balita; a writer's post waits for a head (2026-10-19). --}}
        <p x-show="can('create') && !approver" x-cloak class="rounded-[14px] bg-sky-50 border border-sky-100 px-3.5 py-2.5 text-xs text-sky-900 flex items-start gap-2">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
            <span>You can write Balita posts with photos and videos and send them for review, with a time for them to go out if you like. Your Department Head or the Municipal Information Office approves and publishes them. You can change only your own posts<span x-show="ownBarangay" x-text="', and they go to Brgy. ' + ownBarangay + ' residents'"></span>.</span>
        </p>

        {{-- Filters: one row (redesign 2026-10-26); the status is a pill like the others. --}}
        <div class="bg-white rounded-[14px] border border-slate-200 shadow-sm p-3">
            <div class="flex flex-wrap gap-2">
                <label class="relative flex-1 basis-[100px] min-w-[100px] h-10 px-3 rounded-full bg-slate-100 flex items-center gap-2 text-ink-muted focus-within:ring-4 focus-within:ring-brand-100">
                    <i data-lucide="search" class="w-4 h-4 shrink-0"></i>
                    <span class="sr-only">Search Balita</span>
                    <input aria-label="Search Balita" type="search" x-model="search" placeholder="Search posts" class="flex-1 min-w-0 border-0 bg-transparent p-0 text-sm text-ink placeholder:text-ink-muted focus:ring-0 outline-none">
                </label>
                <span class="relative inline-flex items-center shrink-0">
                    <select aria-label="Balita category" x-model="category" class="appearance-none h-10 rounded-full border-0 bg-slate-100 hover:bg-slate-200 pl-3 pr-7 text-[13px] font-medium text-ink-body whitespace-nowrap cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                        <option value="All">All categories</option>
                        <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-2.5 w-3.5 h-3.5 text-ink-body pointer-events-none"></i>
                </span>
                <span class="relative inline-flex items-center shrink-0">
                    <select aria-label="Balita audience" x-model="scope" class="appearance-none h-10 rounded-full border-0 bg-slate-100 hover:bg-slate-200 pl-3 pr-7 text-[13px] font-medium text-ink-body whitespace-nowrap cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                        <option value="All">All audiences</option>
                        <option value="Municipal">Municipality-wide</option>
                        <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-2.5 w-3.5 h-3.5 text-ink-body pointer-events-none"></i>
                </span>
                {{-- Scheduled is not a status but sits with them: approved posts
                     waiting for their time (2026-10-21). --}}
                <span class="relative inline-flex items-center shrink-0">
                    <select aria-label="Balita status" x-model="statusChoice" class="appearance-none h-10 rounded-full border-0 bg-slate-100 hover:bg-slate-200 pl-3 pr-7 text-[13px] font-medium text-ink-body whitespace-nowrap cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                        <option value="All" x-text="'All statuses · ' + posts.length">All statuses</option>
                        <template x-for="s in statuses" :key="s"><option :value="s" x-text="statusLabel(s) + (count(s) ? ' · ' + count(s) : '')"></option></template>
                        <option value="Scheduled" x-text="'Scheduled' + (scheduledCount ? ' · ' + scheduledCount : '')">Scheduled</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-2.5 w-3.5 h-3.5 text-ink-body pointer-events-none"></i>
                </span>
            </div>
        </div>

        {{-- The posts, as residents see them, with what this officer may do --}}
        <template x-for="a in shownPosts" :key="a.id">
            {{-- relative: the screen-reader words inside stay in the card, or they stretch the page past its scroller. --}}
            <article class="relative bg-white rounded-[14px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 pt-3.5 flex items-start gap-2.5">
                    <span class="w-[42px] h-[42px] rounded-full bg-brand-600 text-white text-sm font-bold grid place-items-center shrink-0" aria-hidden="true" x-text="initials(a.author)"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[15px] font-semibold text-ink truncate" x-text="a.author"></p>
                        <p class="text-[13px] text-ink-muted flex flex-wrap items-center gap-x-1">
                            <span x-text="postWhen(a)"></span><span aria-hidden="true">·</span>
                            <span class="inline-flex items-center gap-1" :class="a.barangay && 'text-purple-700 font-medium'"><i :data-lucide="!a.barangay ? 'landmark' : 'map-pin'" class="w-3.5 h-3.5"></i><span x-text="!a.barangay ? 'Municipality-wide' : 'Brgy. ' + a.barangay + ' only'"></span></span><span aria-hidden="true">·</span>
                            <span x-text="a.category"></span>
                        </p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset" :class="statusTone(a.status)" x-text="statusLabel(a.status)"></span>
                </div>
                <div class="px-4 pt-3 pb-3 space-y-1">
                    <h3 class="text-lg font-bold text-ink break-words" x-text="a.title"></h3>
                    <p class="text-[15px] leading-relaxed text-ink-body whitespace-pre-line break-words line-clamp-6" x-text="a.body"></p>
                    <p x-show="a.status === 'Rejected' && a.review_remarks" x-cloak class="mt-2 rounded-xl bg-rose-50 border border-rose-100 px-3 py-2 text-[13px] text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="a.review_remarks"></span></p>
                </div>
                <x-balita.gallery media="a.media" label="a.title" :english="true" />
                <div x-show="a.status === 'Released'" class="px-4 py-2.5 flex items-center justify-between text-sm text-ink-muted">
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="heart" class="w-4 h-4"></i><span x-text="a.likes || 0"></span><span class="sr-only"> likes</span></span>
                    <button type="button" @click="openComments(a)" class="hover:text-brand-700 hover:underline" x-text="(a.comments_count || 0) + ' comment' + ((a.comments_count || 0) === 1 ? '' : 's')"></button>
                </div>
                @include('admin.communications.balita-facebook', ['post' => 'a', 'class' => 'px-4 pb-2.5'])
                <div class="mx-4 py-1 border-t border-slate-200 flex flex-wrap gap-1">
                    <button type="button" x-show="a.status === 'Pending Review' && (canMove(a, 'Approved') || canMove(a, 'Rejected'))" @click="openReview(a)" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-orange-700 hover:bg-orange-50 whitespace-nowrap"><i data-lucide="file-search" class="w-[18px] h-[18px]"></i>Review</button>
                    <button type="button" x-show="canEdit(a)" @click="openEdit(a)" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-brand-700 hover:bg-slate-100 whitespace-nowrap"><i data-lucide="pencil" class="w-[18px] h-[18px]"></i>Edit</button>
                    <button type="button" x-show="a.status === 'Draft' && canMove(a, 'Pending Review')" @click="quick(a, 'Pending Review', 'Sent for review.')" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-brand-700 hover:bg-slate-100 whitespace-nowrap"><i data-lucide="send" class="w-[18px] h-[18px]"></i>Send for review</button>
                    <button type="button" x-show="a.status === 'Approved' && canMove(a, 'Released') && !isScheduled(a)" @click="quick(a, 'Released', 'Published. Residents are notified.')" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 whitespace-nowrap"><i data-lucide="upload" class="w-[18px] h-[18px]"></i>Publish</button>
                    {{-- An approved post with a time waits for it; the server puts it out then. --}}
                    <p x-show="isScheduled(a)" class="flex-1 h-10 inline-flex items-center justify-center gap-2 px-2 text-sm font-medium text-sky-800 text-center"><i data-lucide="calendar-clock" class="w-[18px] h-[18px] shrink-0"></i><span x-text="goesLiveText(a)"></span></p>
                    <button type="button" x-show="canMove(a, 'Archived')" @click="quick(a, 'Archived', 'Archived. Residents no longer see it.')" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-ink-body hover:bg-slate-100 whitespace-nowrap"><i data-lucide="archive" class="w-[18px] h-[18px]"></i>Archive</button>
                    <button type="button" x-show="canDelete(a)" @click="deletePost(a)" :disabled="processing" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 whitespace-nowrap"><i data-lucide="trash-2" class="w-[18px] h-[18px]"></i>Delete</button>
                    <button type="button" x-show="can('create')" @click="useAsTemplate(a)" :disabled="processing" class="flex-1 h-10 rounded-lg inline-flex items-center justify-center gap-2 text-sm font-semibold text-ink-body hover:bg-slate-100 disabled:opacity-50 whitespace-nowrap"><i data-lucide="copy" class="w-[18px] h-[18px]"></i>Use as template</button>
                </div>
            </article>
        </template>

        <div x-show="filtered.length === 0" class="bg-white rounded-[14px] border border-slate-200 flex flex-col items-center text-center py-10">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No Balita posts match these filters.</p>
        </div>
        <div x-show="filtered.length" class="flex flex-col items-center gap-2 pt-1">
            <p class="text-[13px] text-ink-muted" x-text="'Showing ' + shownPosts.length + ' of ' + filtered.length + ' posts'"></p>
            <button type="button" x-show="shownPosts.length < filtered.length" @click="shown += 10" class="h-10 px-4 rounded-full bg-white border border-slate-200 text-sm font-semibold text-ink-body hover:bg-slate-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">Show more posts</button>
        </div>
    </div>
    </div>

    {{-- The composer (redesign 2026-10-26): one column, like writing the post
         itself. Who it is for and the category sit under the seal; photos,
         a schedule and a barangay are added from the bar below the words.
         Cancel, the X, Esc and a click outside all ask before throwing
         changes away. --}}
    <div x-data="{ get open() { return composerOpen; }, set open(v) { v ? (composerOpen = true) : requestClose(); } }">
    <x-ui.modal maxWidth="sm" :bare="true">
        <div class="relative flex items-center justify-center h-[60px] px-14 bg-white border-b border-slate-200 shrink-0">
            <h3 class="text-xl font-bold text-ink" x-text="editing ? 'Edit post' : 'Create post'">Create post</h3>
            <button type="button" @click="requestClose()" aria-label="Close" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full grid place-items-center bg-slate-100 text-ink-body hover:bg-slate-200 hover:text-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto scrollbar-thin bg-white px-4 pt-4 pb-3 space-y-3">
            <p x-show="editing" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'Editing a post in ' + statusLabel(editing?.status || '') + '. It stays there until it is sent on.'"></span></p>
            <p x-show="editing && editing.status === 'Rejected' && editing.review_remarks" x-cloak class="rounded-xl bg-rose-50 border border-rose-100 px-3 py-2 text-xs text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="editing?.review_remarks"></span></p>

            {{-- Posting as the LGU, to an audience, under a category --}}
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-12 h-12 rounded-full object-cover shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-semibold text-ink flex items-center gap-1">Esperanza LGU <i data-lucide="badge-check" class="w-4 h-4 text-brand-500"></i></p>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span x-show="ownBarangay" x-cloak class="inline-flex items-center gap-1.5 h-7 rounded-md bg-slate-100 px-2 text-[13px] font-semibold text-ink"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i><span x-text="'Brgy. ' + ownBarangay"></span></span>
                        <span x-show="!ownBarangay" class="relative inline-flex items-center">
                            <i data-lucide="users" class="absolute left-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                            <select aria-label="Who sees it" x-model="form.audience" class="appearance-none h-7 rounded-md border-0 bg-slate-100 hover:bg-slate-200 pl-7 pr-7 text-[13px] font-semibold text-ink cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="Municipal">All of Esperanza</option>
                                <option value="Barangay">One barangay</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                        </span>
                        <span x-show="!ownBarangay && form.audience === 'Barangay'" x-cloak class="relative inline-flex items-center">
                            <i data-lucide="map-pin" class="absolute left-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                            <select id="balita-barangay" aria-label="Barangay" x-model="form.barangay" class="appearance-none h-7 rounded-md border-0 bg-slate-100 hover:bg-slate-200 pl-7 pr-7 text-[13px] font-semibold text-ink cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="b === form.barangay"></option></template>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                        </span>
                        <span class="relative inline-flex items-center">
                            <i data-lucide="tag" class="absolute left-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                            <select aria-label="Category" x-model="form.category" class="appearance-none h-7 rounded-md border-0 bg-slate-100 hover:bg-slate-200 pl-7 pr-7 text-[13px] font-semibold text-ink cursor-pointer focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="c in categories" :key="c"><option :value="c" x-text="c" :selected="c === form.category"></option></template>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-2 w-3.5 h-3.5 text-ink pointer-events-none"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <label for="balita-title" class="sr-only">Title</label>
                <input id="balita-title" type="text" x-model="form.title" maxlength="255" placeholder="Add a title" class="w-full border-0 bg-transparent p-0 py-1 text-[22px] font-bold text-ink placeholder:text-ink-faint focus:ring-0 outline-none">
                <p x-show="fieldError('title')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('title')"></p>
            </div>
            <div>
                <label class="sr-only" for="balita-content">What residents need to know</label>
                {{-- Grows with what is written, up to a point; then it scrolls. --}}
                <textarea id="balita-content" rows="3" placeholder="What do residents need to know? What, when, where, who to contact." x-model="form.body" x-effect="form.body; $nextTick(() => { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 320) + 'px'; })" class="w-full resize-none border-0 bg-transparent p-0 text-lg leading-relaxed text-ink placeholder:text-ink-muted focus:ring-0 outline-none"></textarea>
            </div>

            {{-- The photos and videos; the green button below adds them. --}}
            <template x-for="k in [formKey]" :key="k">
                <x-balita.media-field base="/admin/balita" model="media" existing="(editing ? (editing.media || []) : [])" :english="true" look="composer" />
            </template>

            {{-- When it goes out, once a time is chosen: a strip that says when,
                 with the day and time while they are being chosen. --}}
            <div x-show="form.schedule" x-cloak class="rounded-[10px] bg-brand-50 text-brand-700">
                <div class="flex items-center gap-2 min-h-[44px] pl-3 pr-1.5">
                    <i data-lucide="calendar-clock" class="w-4 h-4 shrink-0"></i>
                    <p class="flex-1 min-w-0 text-sm"><span class="font-semibold">Scheduled</span> · <span x-text="scheduleProblem ? 'choose a day and time' : previewWhen"></span></p>
                    <button type="button" x-show="!scheduleEditing" @click="addSchedule()" class="h-8 px-2.5 rounded-md text-sm font-semibold hover:bg-brand-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200">Change</button>
                    <button type="button" @click="removeSchedule()" aria-label="Remove schedule, post now" title="Remove schedule, post now" class="w-8 h-8 rounded-full grid place-items-center hover:bg-brand-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <fieldset x-show="scheduleEditing || scheduleProblem" class="flex flex-wrap items-center gap-2 px-3 pb-3">
                    <legend class="sr-only">When it goes out</legend>
                    <label for="balita-publish-date" class="sr-only">Day</label>
                    <input id="balita-publish-date" type="date" x-model="form.publish_date" class="rounded-lg border border-brand-200 bg-white py-1.5 px-2.5 text-sm text-ink focus:ring-4 focus:ring-brand-100 outline-none">
                    <label for="balita-publish-time" class="sr-only">Time (Esperanza)</label>
                    <input id="balita-publish-time" type="time" x-model="form.publish_time" class="rounded-lg border border-brand-200 bg-white py-1.5 px-2.5 text-sm text-ink focus:ring-4 focus:ring-brand-100 outline-none">
                    <button type="button" x-show="!scheduleProblem" @click="scheduleEditing = false" class="h-8 px-3 rounded-md bg-white text-sm font-semibold hover:bg-brand-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200">Done</button>
                    <p class="w-full text-[11px] text-brand-700/80" x-text="approver ? 'Esperanza time (Asia/Manila). It goes out by itself then.' : 'Esperanza time (Asia/Manila). Once approved, it goes out by itself then.'"></p>
                    <p x-show="scheduleProblem" class="w-full text-[11px] text-rose-700" x-text="scheduleProblem"></p>
                </fieldset>
            </div>
            <p x-show="fieldError('publish_at')" x-cloak class="text-[11px] text-rose-700" x-text="fieldError('publish_at')"></p>

            {{-- Add to your post: only what the post can carry. --}}
            <div class="flex items-center justify-between gap-2 min-h-[58px] rounded-[10px] border border-slate-200 pl-4 pr-2">
                <p class="text-[15px] font-semibold text-ink">Add to your post</p>
                <div class="flex items-center gap-1">
                    <button type="button" @click="$dispatch('balita-add-media')" :disabled="media.ids.length >= 10" aria-label="Photos or videos" title="Photos or videos" class="w-10 h-10 rounded-full grid place-items-center text-emerald-600 hover:bg-emerald-50 disabled:opacity-40 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-100 transition-colors" :class="media.ids.length && 'bg-emerald-50'"><i data-lucide="images" class="w-6 h-6"></i></button>
                    <button type="button" @click="form.schedule && !scheduleEditing ? removeSchedule() : addSchedule()" :aria-pressed="form.schedule ? 'true' : 'false'" :aria-label="form.schedule ? 'Remove schedule, post now' : 'Schedule it'" :title="form.schedule ? 'Remove schedule, post now' : 'Schedule it'" class="w-10 h-10 rounded-full grid place-items-center text-brand-600 hover:bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors" :class="form.schedule && 'bg-brand-50'"><i data-lucide="calendar-clock" class="w-6 h-6"></i></button>
                    <button type="button" x-show="!ownBarangay" @click="toggleBarangay()" :aria-pressed="form.audience === 'Barangay' ? 'true' : 'false'" :aria-label="form.audience === 'Barangay' ? 'Send to all of Esperanza' : 'Send to one barangay'" :title="form.audience === 'Barangay' ? 'Send to all of Esperanza' : 'Send to one barangay'" class="w-10 h-10 rounded-full grid place-items-center text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-rose-100 transition-colors" :class="form.audience === 'Barangay' && 'bg-rose-50'"><i data-lucide="map-pin" class="w-6 h-6"></i></button>
                </div>
            </div>

            {{-- Also on the LGU's Facebook Page (welfare round 2026-10-29): shown while a
                 Page is connected and posts by itself; it starts as the Page's default
                 sharing says for this audience, and the writer may change it. --}}
            <label x-show="fbOffered()" x-cloak class="flex items-start gap-3 rounded-[10px] border border-slate-200 px-4 py-3 cursor-pointer hover:bg-slate-50 focus-within:ring-4 focus-within:ring-brand-100">
                <input type="checkbox" :checked="fbShareChecked()" @change="form.fb_share = $event.target.checked" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="min-w-0">
                    <span class="flex items-center gap-1.5 text-[15px] font-semibold text-ink"><x-balita.fb-mark class="w-4 h-4 text-[#1877F2]" />Also post on Facebook when published</span>
                    <span class="block text-xs text-ink-muted" x-text="'On ' + fbPageName() + (fb && fb.status === 'token_problem' ? '. The Facebook connection needs attention; the post goes there once it is fixed.' : ', with a link back to the post on the website.')"></span>
                </span>
            </label>
        </div>

        <div class="px-4 pt-3 pb-4 bg-white border-t border-slate-200 shrink-0 space-y-2">
            <p x-show="media.busy" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i>Uploading photos and videos…</p>
            <p x-show="!media.busy && dirty" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>Unsaved changes</p>
            <div class="flex items-center gap-2">
                <x-ui.button variant="secondary" class="shrink-0" x-bind:class="editing && editing.status === 'Pending Review' && !approver ? 'flex-1' : ''" x-bind:disabled="processing || !formReady" @click="save('draft')"><span x-text="editing ? 'Save changes' : 'Save draft'">Save draft</span></x-ui.button>
                <x-ui.button class="flex-1" x-show="!editing || editing.status !== 'Pending Review' || approver" x-bind:disabled="processing || !formReady" @click="save('send')"><span class="inline-flex items-center gap-2"><span x-show="!form.schedule"><i data-lucide="send" class="w-4 h-4"></i></span><span x-show="form.schedule" x-cloak><i data-lucide="calendar-clock" class="w-4 h-4"></i></span><span x-text="primaryLabel">Publish Now</span></span></x-ui.button>
            </div>
            <p class="text-xs text-ink-muted text-center" x-text="form.schedule ? 'Residents are notified when it goes out at its time. Posts stay in Balita until archived.' : 'Residents are notified when it goes out. Posts stay in Balita until archived.'"></p>
        </div>
    </x-ui.modal>
    </div>

    {{-- Review: the post as residents will see it --}}
    <div x-data="{ get open() { return reviewOpen; }, set open(v) { reviewOpen = v; } }">
    <x-ui.modal title="Review Balita Post" maxWidth="lg">
        <template x-if="selected">
            <div class="space-y-3">
                <div class="rounded-2xl border border-brand-600/10 bg-white overflow-hidden">
                    <div class="flex items-center gap-2.5 p-3.5 pb-2">
                        <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-9 h-9 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink flex items-center gap-1"><span x-text="selected.author"></span><i data-lucide="badge-check" class="w-3.5 h-3.5 text-brand-500"></i></p>
                            <p class="text-[11px] text-ink-muted" x-text="(!selected.barangay ? 'All barangays' : 'Brgy. ' + selected.barangay + ' only') + ' · ' + selected.category"></p>
                        </div>
                        <span class="text-[10px] font-semibold text-ink-muted bg-slate-100 rounded-full px-2 py-0.5">Preview</span>
                    </div>
                    <p class="px-3.5 pb-1 text-[15px] font-bold text-ink" x-text="selected.title"></p>
                    <p class="px-3.5 pb-3 text-[13px] text-slate-700 leading-relaxed whitespace-pre-line" x-text="selected.body"></p>
                    <x-balita.gallery media="selected.media" label="selected.title" :english="true" />
                </div>
                @include('admin.communications.balita-facebook', ['post' => 'selected', 'class' => ''])
                <p x-show="!hasFutureTime(selected)" class="text-xs text-ink-muted">Residents see it under the writer's name with the official seal. Approving publishes it at once and notifies <span x-text="selected.barangay ? 'the residents of Brgy. ' + selected.barangay : 'every resident'"></span> in the app and on the website.</p>
                <p x-show="hasFutureTime(selected)" x-cloak class="text-xs text-ink-muted">Residents see it under the writer's name with the official seal. Approving keeps it until <span x-text="when(selected.publish_at)"></span>; it goes out by itself then.</p>
                <div x-show="canMove(selected, 'Rejected')">
                    <label for="balita-remarks" class="block text-[11px] font-semibold text-ink-muted uppercase tracking-wide mb-1">Remarks (required to send it back)</label>
                    <textarea id="balita-remarks" x-model="rejectReason" rows="2" placeholder="What needs to change?" class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </div>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" icon="pencil" x-show="selected && canEdit(selected)" @click="openEdit(selected)">Edit</x-ui.button>
            <x-ui.button variant="danger" size="sm" x-show="selected && canMove(selected, 'Rejected')" x-bind:disabled="processing || rejectReason.trim().length < 5" @click="requestChanges()">Send back</x-ui.button>
            <x-ui.button size="sm" icon="check" x-show="selected && canMove(selected, 'Approved')" x-bind:disabled="processing" @click="approveAndPublish()"><span x-text="selected && hasFutureTime(selected) ? 'Approve' : 'Approve & Publish'">Approve &amp; Publish</span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>

    {{-- Delete, asked in the page (welfare round 2026-10-29): a post that went to
         Facebook can be taken off there too (delete_on_facebook=1). --}}
    <div x-data="{ get open() { return delOpen; }, set open(v) { if (!processing) delOpen = v; } }">
    <x-ui.modal title="Delete this post?" maxWidth="sm">
        <div class="space-y-3 text-sm text-ink-body">
            <p>Delete <span class="font-semibold text-ink" x-text="'“' + (delPost?.title || '') + '”'"></span> for good? Its comments and likes go with it. This cannot be undone.</p>
            <label x-show="delPost?.facebook?.status === 'posted'" class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer">
                <input type="checkbox" x-model="delFromFb" class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                <span><span class="block font-semibold text-ink">Also remove it from Facebook</span><span class="block text-xs text-ink-muted">Left unticked, the post stays on the Facebook Page.</span></span>
            </label>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="delOpen = false">Cancel</x-ui.button>
            <x-ui.button variant="danger" size="sm" icon="trash-2" x-bind:disabled="processing" @click="confirmDelete()"><span x-text="processing ? 'Deleting…' : 'Delete'"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>

    @include('admin.communications.balita-settings')
</div>
