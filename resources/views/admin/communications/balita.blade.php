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

    <div x-show="!loading && !failed">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <x-ui.stat-card layout="compact" label="Published" valueBind="card('published')" icon="megaphone" color="brand" :delay="0" />
        <x-ui.stat-card layout="compact" label="Pending Review" valueBind="card('pending_review')" icon="file-clock" color="orange" :delay="40" />
        <x-ui.stat-card layout="compact" label="Drafts and sent back" valueBind="card('drafts')" icon="pencil-line" color="purple" :delay="80" />
        <x-ui.stat-card layout="compact" label="Scheduled" valueBind="card('scheduled')" icon="calendar-clock" color="green" :delay="120" />
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
            <x-ui.button x-show="can('create')" @click="openNew()" icon="plus" size="sm" class="shrink-0">Create post</x-ui.button>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full overflow-x-auto">
                <template x-for="s in ['All', ...statuses]" :key="s">
                    <button type="button" @click="showStatus(s)" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap" :class="status === s && !scheduledOnly ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                        <span x-text="s === 'All' ? 'All' : statusLabel(s)"></span><span x-show="s !== 'All' && count(s)" class="ml-1 text-ink-muted tabular-nums" x-text="count(s)"></span>
                    </button>
                </template>
                {{-- Not a status: approved posts waiting for their time (2026-10-21). --}}
                <span class="w-px h-4 bg-slate-300/70 shrink-0" aria-hidden="true"></span>
                <button type="button" @click="showScheduled()" :aria-pressed="scheduledOnly" class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap inline-flex items-center gap-1" :class="scheduledOnly ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'">
                    <i data-lucide="calendar-clock" class="w-3 h-3"></i>Scheduled<span x-show="scheduledCount" class="ml-0.5 text-ink-muted tabular-nums" x-text="scheduledCount"></span>
                </button>
            </div>
            <p class="text-[11px] text-ink-muted shrink-0" x-text="filtered.length + ' of ' + posts.length + ' posts'"></p>
        </div>
    </div>

    {{-- Every officer writes Balita; a writer's post waits for a head (2026-10-19). --}}
    <p x-show="can('create') && !approver" x-cloak class="mb-4 rounded-2xl bg-sky-50 border border-sky-100 px-3.5 py-2.5 text-xs text-sky-900 flex items-start gap-2">
        <i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i>
        <span>You can write Balita posts with photos and videos and send them for review, with a time for them to go out if you like. Your Department Head or the Municipal Information Office approves and publishes them. You can change only your own posts<span x-show="ownBarangay" x-text="', and they go to Brgy. ' + ownBarangay + ' residents'"></span>.</span>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
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
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold ring-1 ring-inset" :class="statusTone(a.status)" x-text="statusLabel(a.status)"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 mb-2">
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium rounded-full px-2 py-0.5" :class="!a.barangay ? 'bg-brand-50 text-brand-700' : 'bg-purple-50 text-purple-700'">
                            <i :data-lucide="!a.barangay ? 'landmark' : 'map-pin'" class="w-2.5 h-2.5"></i>
                            <span x-text="!a.barangay ? 'Municipality-wide' : 'Brgy. ' + a.barangay + ' only'"></span>
                        </span>
                        <span x-show="hasFutureTime(a) && a.status !== 'Released' && a.status !== 'Archived'" class="inline-flex items-center gap-1 text-[10px] font-semibold rounded-full px-2 py-0.5 bg-sky-50 text-sky-800">
                            <i data-lucide="calendar-clock" class="w-2.5 h-2.5"></i>
                            <span x-text="(isScheduled(a) ? 'Scheduled · ' : 'For ') + when(a.publish_at)"></span>
                        </span>
                    </div>
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
                    <button type="button" x-show="a.status === 'Approved' && canMove(a, 'Released') && !isScheduled(a)" @click="quick(a, 'Released', 'Published. Residents are notified.')" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-semibold py-2.5 text-emerald-700 hover:bg-emerald-50/60"><i data-lucide="upload" class="w-3.5 h-3.5"></i>Publish</button>
                    {{-- An approved post with a time waits for it; the server puts it out then. --}}
                    <p x-show="isScheduled(a)" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 px-2 text-sky-800 text-center"><i data-lucide="calendar-clock" class="w-3.5 h-3.5 shrink-0"></i><span x-text="goesLiveText(a)"></span></p>
                    <button type="button" x-show="canMove(a, 'Archived')" @click="quick(a, 'Archived', 'Archived. Residents no longer see it.')" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-ink-muted hover:bg-white/60"><i data-lucide="archive" class="w-3.5 h-3.5"></i>Archive</button>
                    <button type="button" x-show="can('create')" @click="useAsTemplate(a)" :disabled="processing" class="flex-1 flex items-center justify-center gap-1.5 text-xs font-medium py-2.5 text-brand-700 hover:bg-brand-50/60 disabled:opacity-50"><i data-lucide="copy" class="w-3.5 h-3.5"></i>Use as template</button>
                </div>
            </article>
        </template>
        <div class="col-span-full flex flex-col items-center text-center py-10" x-show="filtered.length === 0">
            <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
            <p class="text-sm font-medium text-slate-600">No Balita posts match these filters.</p>
        </div>
    </div>
    </div>

    {{-- The composer: who it is for, the words and photos, and when it goes
         out, with the post as residents will see it beside them. Cancel, the
         X, Esc and a click outside all ask before throwing changes away. --}}
    <div x-data="{ get open() { return composerOpen; }, set open(v) { v ? (composerOpen = true) : requestClose(); } }">
    <x-ui.modal maxWidth="2xl" :fullscreen="true" :bare="true">
        <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-brand-600/10 shrink-0">
            <h3 class="text-base font-extrabold text-ink" x-text="editing ? 'Edit post' : 'Create post'">Create post</h3>
            <div class="flex items-center gap-1">
                <button type="button" @click="compact = !compact" :aria-label="compact ? 'Expand to full screen' : 'Shrink to popup'" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all" :title="compact ? 'Expand to full screen' : 'Shrink to popup'">
                    <span x-show="compact"><i data-lucide="maximize-2" class="w-4 h-4"></i></span>
                    <span x-show="!compact"><i data-lucide="minimize-2" class="w-4 h-4"></i></span>
                </button>
                <button type="button" @click="requestClose()" aria-label="Close" class="w-9 h-9 rounded-xl grid place-items-center text-ink-muted hover:text-ink hover:bg-brand-50 transition-all"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto scrollbar-thin px-4 py-5 sm:px-8 sm:py-7">
        <div class="max-w-6xl mx-auto grid lg:grid-cols-[minmax(0,1fr)_380px] gap-6 items-start">
            <div class="space-y-4 min-w-0">
                <p x-show="editing" x-cloak class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="pencil" class="w-3.5 h-3.5 shrink-0"></i><span x-text="'Editing a post in ' + statusLabel(editing?.status || '') + '. It stays there until it is sent on.'"></span></p>
                <p x-show="editing && editing.status === 'Rejected' && editing.review_remarks" x-cloak class="rounded-2xl bg-rose-50 border border-rose-100 px-3.5 py-2.5 text-xs text-rose-800"><span class="font-semibold">Sent back:</span> <span x-text="editing?.review_remarks"></span></p>

                {{-- Posting as the LGU, to an audience, under a category --}}
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-11 h-11 rounded-full object-cover shrink-0">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-ink flex items-center gap-1">Esperanza LGU <i data-lucide="badge-check" class="w-3.5 h-3.5 text-brand-500"></i></p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span x-show="ownBarangay" x-cloak class="inline-flex items-center gap-1 rounded-lg bg-purple-50 text-purple-700 px-2 py-1 text-[11px] font-semibold"><i data-lucide="map-pin" class="w-3 h-3"></i><span x-text="'Brgy. ' + ownBarangay"></span></span>
                            <select x-show="!ownBarangay" aria-label="Who sees it" x-model="form.audience" class="rounded-lg border-0 bg-slate-100 py-1 pl-2 pr-7 text-[11px] font-semibold text-ink-body focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="Municipal">All of Esperanza</option>
                                <option value="Barangay">One barangay</option>
                            </select>
                            <select x-show="!ownBarangay && form.audience === 'Barangay'" x-cloak aria-label="Barangay" x-model="form.barangay" class="rounded-lg border-0 bg-slate-100 py-1 pl-2 pr-7 text-[11px] font-semibold text-ink-body focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="b in barangayNames" :key="b"><option :value="b" x-text="'Brgy. ' + b" :selected="b === form.barangay"></option></template>
                            </select>
                            <select aria-label="Category" x-model="form.category" class="rounded-lg border-0 bg-slate-100 py-1 pl-2 pr-7 text-[11px] font-semibold text-ink-body focus:ring-4 focus:ring-brand-100 outline-none">
                                <template x-for="c in categories" :key="c"><option :value="c" x-text="c" :selected="c === form.category"></option></template>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="balita-title" class="sr-only">Title</label>
                    <input id="balita-title" type="text" x-model="form.title" maxlength="255" placeholder="Title, e.g. Free Medical Mission — Brgy. Poblacion" class="w-full rounded-2xl border-0 surface-pressed py-3 px-4 text-base font-semibold text-ink placeholder:text-ink-faint placeholder:font-normal focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <p x-show="fieldError('title')" x-cloak class="text-[11px] text-rose-700 mt-1" x-text="fieldError('title')"></p>
                </div>
                <div>
                    <label class="sr-only" for="balita-content">What residents need to know</label>
                    <textarea id="balita-content" rows="7" placeholder="What do residents need to know? What, when, where, who to contact." x-model="form.body" class="w-full rounded-2xl border-0 surface-pressed py-3 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                </div>

                <template x-for="k in [formKey]" :key="k">
                    <x-balita.media-field base="/admin/balita" model="media" existing="(editing ? (editing.media || []) : [])" :english="true" />
                </template>

                {{-- When it goes out --}}
                <fieldset class="rounded-2xl border border-brand-600/10 bg-white/60 p-4 space-y-3">
                    <legend class="px-1 text-sm font-medium text-slate-700">When it goes out</legend>
                    <div class="grid sm:grid-cols-2 gap-2.5">
                        <label class="flex items-start gap-2.5 rounded-2xl border p-3 cursor-pointer transition-all duration-[160ms] ease-standard" :class="!form.schedule ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="balita-when" :value="false" :checked="!form.schedule" @change="form.schedule = false" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                            <span><span class="block text-xs font-semibold text-ink" x-text="approver ? 'Now' : 'As soon as it is approved'"></span><span class="block text-[10.5px] text-ink-muted mt-0.5">Residents are notified when it is published</span></span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-2xl border p-3 cursor-pointer transition-all duration-[160ms] ease-standard" :class="form.schedule ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="balita-when" :value="true" :checked="form.schedule" @change="form.schedule = true" class="mt-0.5 border-slate-300 text-brand-600 focus:ring-brand-200">
                            <span><span class="block text-xs font-semibold text-ink">At a time I choose</span><span class="block text-[10.5px] text-ink-muted mt-0.5">Once approved, it goes out by itself then</span></span>
                        </label>
                    </div>
                    <div x-show="form.schedule" x-cloak class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="balita-publish-date" class="block text-xs font-medium text-slate-700 mb-1">Day</label>
                            <input id="balita-publish-date" type="date" x-model="form.publish_date" class="rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label for="balita-publish-time" class="block text-xs font-medium text-slate-700 mb-1">Time (Esperanza)</label>
                            <input id="balita-publish-time" type="time" x-model="form.publish_time" class="rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        </div>
                    </div>
                    <p x-show="form.schedule && scheduleProblem" x-cloak class="text-[11px] text-rose-700" x-text="scheduleProblem"></p>
                    <p x-show="fieldError('publish_at')" x-cloak class="text-[11px] text-rose-700" x-text="fieldError('publish_at')"></p>
                    <p class="text-[10.5px] text-ink-muted">Times are Esperanza time (Asia/Manila), whatever this computer is set to. A post stays in Balita until it is archived.</p>
                </fieldset>
            </div>

            {{-- The post as residents see it in Balita --}}
            <aside class="lg:sticky lg:top-0 space-y-2" aria-label="Preview">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted flex items-center gap-1.5"><i data-lucide="eye" class="w-3.5 h-3.5"></i>Preview · what residents see</p>
                <div class="rounded-[22px] border border-brand-600/10 bg-white overflow-hidden shadow-sm">
                    <div class="flex items-center gap-2.5 p-3.5 pb-2">
                        <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-9 h-9 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink flex items-center gap-1">Esperanza LGU <i data-lucide="badge-check" class="w-3.5 h-3.5 text-brand-500"></i></p>
                            <p class="text-[11px] text-ink-muted" x-text="previewWhen + ' · ' + audienceText + ' · ' + form.category"></p>
                        </div>
                    </div>
                    <p class="px-3.5 pb-1 text-[15px] font-bold text-ink break-words" x-text="form.title.trim() || 'Title of the post'"></p>
                    <p class="px-3.5 pb-3 text-[13px] text-slate-700 leading-relaxed whitespace-pre-line break-words line-clamp-6" x-text="form.body.trim() || 'What residents need to know.'"></p>
                    <img x-show="media.cover" :src="media.cover || ''" alt="" class="w-full aspect-[16/9] object-cover">
                    <div class="flex items-center gap-4 px-3.5 py-2.5 border-t border-slate-100 text-[11px] text-ink-muted" aria-hidden="true">
                        <span class="inline-flex items-center gap-1"><i data-lucide="heart" class="w-3.5 h-3.5"></i>Like</span>
                        <span class="inline-flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i>Comment</span>
                        <span class="inline-flex items-center gap-1"><i data-lucide="share-2" class="w-3.5 h-3.5"></i>Share</span>
                    </div>
                </div>
                <p class="text-[10.5px] text-ink-muted">A preview only: nothing is saved or sent until you choose below.</p>
            </aside>
        </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2.5 px-6 py-4 border-t border-brand-600/10 bg-white shrink-0">
            <p x-show="media.busy" x-cloak class="mr-auto text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i>Uploading photos and videos…</p>
            <p x-show="!media.busy && dirty" x-cloak class="mr-auto text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>Unsaved changes</p>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing" @click="requestClose()">Cancel</x-ui.button>
            <x-ui.button variant="secondary" size="sm" x-bind:disabled="processing || !formReady" @click="save('draft')"><span x-text="editing ? 'Save changes' : 'Save Draft'">Save Draft</span></x-ui.button>
            <x-ui.button size="sm" icon="send" x-show="!editing || editing.status !== 'Pending Review' || approver" x-bind:disabled="processing || !formReady" @click="save('send')"><span x-text="primaryLabel">Publish Now</span></x-ui.button>
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
</div>
