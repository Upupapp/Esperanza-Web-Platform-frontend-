{{-- The Balita dashboard (owner, 18 Nov): GET /admin/dashboard/summary?area=balita.
     An editor (one who approves: the Information Officer, the Municipal and
     Super Administrator, a Department Head, the MDRRMO head) sees the posts,
     events and banners waiting for review and how Balita reached residents;
     a writer (MIO Staff and every other officer who writes) sees only their
     own work, plus the top posts across Balita, which are public anyway.
     Inside the Dashboard page's dashboardHost and its overview x-data. --}}
@php($comms = route('admin.communications'))
<div x-data="areaDashboard('balita')" class="flex flex-col gap-[22px]">

    <x-admin.dashboards.toolbar eyebrow="Balita dashboard" title="data && !editor ? 'Your posts, events and banners' : 'Posts, events and banners'">
        <p class="mt-0.5 text-[13px] text-ink-muted" x-show="data" x-text="editor ? scopeTitle + ' · what waits for your review and how posts reached residents' : 'Your own work, and the top posts across Balita'"></p>
    </x-admin.dashboards.toolbar>

    <div x-show="loading && !data" class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
        @for ($i = 0; $i < 6; $i++)
            <div class="surface-glass rounded-[22px] min-h-[120px] p-4 space-y-3"><x-ui.skeleton class="h-3 w-24" /><x-ui.skeleton class="h-9 w-16" /></div>
        @endfor
    </div>
    <div x-show="failed" x-cloak>
        <x-ui.error-state messageExpr="error" retryExpr="load()" :retryable="true" />
    </div>

    <template x-if="data">
        <div class="flex flex-col gap-[22px] transition-opacity duration-200" :class="loading && 'opacity-60'">

            {{-- Editor --}}
            <div x-show="editor" class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <div x-show="has('waiting_review')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&status=Pending%20Review'" label="Waiting for your review" valueBind="num(k.waiting_review)" icon="inbox" color="orange" sublabelBind="k.review_other_offices ? num(k.review_other_offices) + ' more from other offices' : (partsText || 'Posts, events and banners')" :delay="0" /></div>
                <div x-show="has('published')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&status=Released'" label="Published" valueBind="num(k.published)" icon="megaphone" color="brand" sublabelBind="num(k.scheduled) + ' scheduled · ' + num(k.live_now) + ' live now'" :delay="40" /></div>
                <div x-show="has('banners_live')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.communications.banners')" label="Live banners" valueBind="num(k.banners_live)" icon="gallery-horizontal" color="purple" sublabel="On the residents' home screen" :delay="80" /></div>
                <div x-show="has('events_upcoming')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=events'" label="Upcoming events" valueBind="num(k.events_upcoming)" icon="calendar-days" color="light" sublabelBind="num(k.registrations) + ' registered'" :delay="120" /></div>
                <div x-show="has('reactions')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita'" label="Reactions" valueBind="num(k.reactions)" icon="heart" color="deep" sublabelBind="num(k.comments) + ' comments · ' + num(k.shares) + ' shares so far'" :delay="160" /></div>
                <div x-show="has('alerts_live')"><x-ui.stat-card layout="compact" class="h-full" :href="route('admin.sakuna.alerts')" label="Live public alerts" valueBind="num(k.alerts_live)" icon="bell-ring" color="gold" sublabelBind="num(k.alerts_drafts) + (k.alerts_drafts === 1 ? ' draft' : ' drafts') + ' to publish'" :delay="200" /></div>
            </div>

            {{-- Writer --}}
            <div x-show="!editor" class="grid gap-4 grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
                <div x-show="has('drafts')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&mine=1&status=Draft'" label="Your drafts" valueBind="num(k.drafts)" icon="pencil-line" color="slate" sublabel="Not sent for review yet" :delay="0" /></div>
                <div x-show="has('waiting_review')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&mine=1&status=Pending%20Review'" label="Waiting for review" valueBind="num(k.waiting_review)" icon="clock" color="orange" sublabelBind="partsText || 'Sent for review'" :delay="40" /></div>
                <div x-show="has('sent_back')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&mine=1&status=Rejected'" label="Sent back to you" valueBind="num(k.sent_back)" icon="circle-x" color="red" sublabel="Needs your changes" :delay="80" /></div>
                <div x-show="has('published')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=balita&mine=1&status=Released'" label="Published" valueBind="num(k.published)" icon="megaphone" color="brand" sublabelBind="num(k.live_now) + ' of yours live now'" :delay="120" /></div>
                <div x-show="has('reactions')"><x-ui.stat-card layout="compact" class="h-full" label="Reactions" valueBind="num(k.reactions)" icon="heart" color="deep" sublabelBind="num(k.comments) + ' comments · ' + num(k.shares) + ' shares so far'" :delay="160" /></div>
                <div x-show="has('events_upcoming')"><x-ui.stat-card layout="compact" class="h-full" :href="$comms . '?tab=events'" label="Your upcoming events" valueBind="num(k.events_upcoming)" icon="calendar-days" color="light" sublabelBind="num(k.registrations) + ' registered'" :delay="200" /></div>
            </div>

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(300px,1fr))]">
                <x-admin.dashboards.action-list />

                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink" x-text="editor ? 'Posts by status' : 'Your posts by status'"></h3>
                        <a href="{{ $comms }}?tab=balita" class="shrink-0 whitespace-nowrap text-[13.5px] font-bold text-brand-600 hover:text-brand-700">Open Balita</a>
                    </div>
                    <p class="-mt-3 mb-3 text-[12px] text-ink-muted">Every post, any date</p>
                    <x-ui.clay-donut rows="statusRows" total-label="Posts" empty="No posts yet." :keep-colors="true" />
                </x-ui.card>

                {{-- Editor: the queue, oldest first; the Municipal Administrator's second looks first. --}}
                <x-ui.card x-show="editor">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink">Waiting for your review</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">Oldest first</span>
                    </div>
                    <div>
                        <template x-for="(r, i) in queue" :key="r.kind + r.id">
                            <div class="flex items-center gap-3 py-2.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[14px] font-bold text-ink leading-snug truncate" x-text="r.title"></p>
                                    <p class="text-[12px] text-ink-muted truncate">
                                        <span class="inline-flex items-center rounded-full px-1.5 py-px mr-1 text-[10.5px] font-bold uppercase bg-brand-50 text-brand-700" x-text="kindWord(r)"></span>
                                        <span x-show="r.second_look" class="inline-flex items-center rounded-full px-1.5 py-px mr-1 text-[10.5px] font-bold uppercase bg-amber-100 text-amber-800">Second look</span>
                                        <span x-text="[r.writer, r.office, r.barangay ? 'Brgy. ' + r.barangay : null].filter(Boolean).join(' · ')"></span>
                                    </p>
                                </div>
                                <span class="shrink-0 text-[12px] font-semibold tabular-nums" :class="r.days >= 3 ? 'text-rose-600' : 'text-ink-muted'" x-text="waited(r.days)"></span>
                                <a :href="itemUrl(r)" class="shrink-0 inline-flex items-center rounded-xl px-3 py-1.5 text-[12.5px] font-bold text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors">Open</a>
                            </div>
                        </template>
                        <p x-show="!queue.length" class="py-6 text-center text-sm text-ink-muted">Nothing waits for your review.</p>
                    </div>
                </x-ui.card>

                {{-- Writer: what came back, with the reviewer's remarks. --}}
                <x-ui.card x-show="!editor">
                    <h3 class="text-lg font-extrabold text-ink mb-3">Sent back to you</h3>
                    <div>
                        <template x-for="(r, i) in sentBack" :key="r.kind + r.id">
                            <div class="py-2.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 text-[14px] font-bold text-ink leading-snug" x-text="r.title"></p>
                                    <a :href="itemUrl(r, 'Rejected')" class="shrink-0 inline-flex items-center rounded-xl px-3 py-1.5 text-[12.5px] font-bold text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors" x-text="'Edit ' + kindWord(r)"></a>
                                </div>
                                <p x-show="r.review_remarks" class="mt-1 text-[12.5px] text-ink-body italic" x-text="r.review_remarks"></p>
                                <p class="mt-0.5 text-[11.5px] text-ink-faint" x-text="kindWord(r) + ' · ' + shortDate(r.updated_at)"></p>
                            </div>
                        </template>
                        <p x-show="!sentBack.length" class="py-6 text-center text-sm text-ink-muted">Nothing was sent back to you.</p>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <x-ui.card class="flex flex-col xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-3">
                        <h3 class="text-lg font-extrabold text-ink" x-text="editor ? 'Published each month' : 'Your posts each month'"></h3>
                        <p x-show="hasTrend" class="flex items-center gap-3 text-[13px] font-semibold text-ink-muted">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>Written</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold-600"></span>Published</span>
                        </p>
                    </div>
                    <x-ui.clay-trend class="my-auto" x-show="hasTrend" data="trend" label="Posts written and published in each of the last twelve months" />
                    <p x-show="!hasTrend" class="my-auto py-10 text-center text-sm text-ink-muted" x-text="editor ? 'No posts written in the last twelve months.' : 'You have not written a post in the last twelve months.'"></p>
                </x-ui.card>

                <x-ui.card class="xl:col-span-2" x-show="editor">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-4">
                        <h3 class="text-lg font-extrabold text-ink">Published by office</h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">In the period</span>
                    </div>
                    <x-ui.clay-bars rows="officeRows" empty="Nothing published in this period." />
                </x-ui.card>

                <x-ui.card class="xl:col-span-2" x-show="!editor">
                    <h3 class="text-lg font-extrabold text-ink mb-3">Waiting for review</h3>
                    <div>
                        <template x-for="(r, i) in myWaiting" :key="r.kind + r.id">
                            <div class="flex items-center justify-between gap-3 py-2.5" :class="i && 'border-t border-brand-600/[.09]'">
                                <div class="min-w-0">
                                    <p class="text-[14px] font-bold text-ink leading-snug truncate" x-text="r.title"></p>
                                    <p class="text-[11.5px] text-ink-faint" x-text="kindWord(r) + ' · sent ' + shortDate(r.waiting_since)"></p>
                                </div>
                                <span class="shrink-0 text-[12px] font-semibold text-ink-muted tabular-nums" x-text="waited(r.days)"></span>
                            </div>
                        </template>
                        <p x-show="!myWaiting.length" class="py-6 text-center text-sm text-ink-muted">Nothing of yours is waiting for review.</p>
                    </div>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
                <x-ui.card class="xl:col-span-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <h3 class="text-lg font-extrabold text-ink" x-text="editor ? 'Top posts' : 'Top posts in Balita'"></h3>
                        <span class="text-[12.5px] font-semibold text-ink-muted">Most likes and comments in the period</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" x-show="topPosts.length">
                            <thead>
                                <tr class="text-left text-[11.5px] font-bold uppercase tracking-[.06em] text-ink-muted">
                                    <th class="py-2 pr-3">Post</th><th class="py-2 px-2 hidden sm:table-cell">Published</th>
                                    <th class="py-2 px-2 text-right">Likes</th><th class="py-2 px-2 text-right">Comments</th><th class="py-2 pl-2 text-right">Shares</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-600/[.08]">
                                <template x-for="p in topPosts" :key="p.id">
                                    <tr>
                                        <td class="py-2.5 pr-3">
                                            <a :href="'{{ $comms }}?tab=balita&post=' + p.id" class="font-semibold text-ink-body hover:text-brand-600 line-clamp-2" x-text="p.title"></a>
                                            <span x-show="p.mine" class="inline-flex mt-0.5 rounded-full px-1.5 py-px text-[10.5px] font-bold uppercase bg-gold-100 text-gold-700">Yours</span>
                                        </td>
                                        <td class="py-2.5 px-2 text-ink-muted whitespace-nowrap hidden sm:table-cell" x-text="shortDate(p.published_at)"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums font-bold" x-text="num(p.likes)"></td>
                                        <td class="py-2.5 px-2 text-right tabular-nums" x-text="num(p.comments)"></td>
                                        <td class="py-2.5 pl-2 text-right tabular-nums" x-text="num(p.shares)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="!topPosts.length" class="py-6 text-center text-sm text-ink-muted">No posts published in this period.</p>
                    </div>
                </x-ui.card>
                <x-admin.dashboards.activity class="xl:col-span-2" />
            </div>
        </div>
    </template>
</div>
