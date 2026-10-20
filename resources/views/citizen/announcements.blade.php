{{-- Balita, the resident's side: the LGU's posts with their photos and videos,
     to like, comment on and share. Residents do not post (owner, 2026-10-20:
     they "can only see and browse"). Logic in resources/js/scope-citizen.js
     (balitaFeed); the media grid and the viewer are x-balita.*
     (resources/js/balita-media.js). ?post=ID opens a post. --}}
<x-layouts.citizen title="Balita" subtitle="Latest news from the municipality." active="announcements">
    <div x-data="balitaFeed" class="animate-fade-up max-w-6xl mx-auto">
        <x-ui.page-intro id="balita-feed" icon="megaphone" title="Manatiling Updated sa Balita" titleEn="Stay Updated with Balita">
            <x-ui.t
                fil="Mga balita, larawan, at video mula sa munisipyo. I-like, i-comment, o ibahagi ang mga post."
                en="News, photos, and videos from the municipality. Like, comment on, or share posts."
            />
        </x-ui.page-intro>

        <div x-show="loading" class="space-y-3"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-56 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="$store.lang.current === 'en' ? 'Could not load Balita.' : 'Hindi ma-load ang Balita.'" retryExpr="loadFirst()" :retryable="true" /></div>

        <div x-show="!loading && !failed" x-cloak class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px] gap-5 items-start">
            <div class="min-w-0">
                {{-- What to show --}}
                <div class="surface-glass rounded-[26px] p-3 mb-3 space-y-2.5">
                    <div class="flex flex-wrap items-center gap-1.5 surface-pressed rounded-xl p-1" role="tablist" :aria-label="t('Show posts', 'Ipakita ang mga post')">
                        <template x-for="[key, en, fil, icon] in tabs" :key="key">
                            <button type="button" role="tab" :aria-selected="tab === key" @click="tab = key"
                                x-show="key !== 'barangay' || $store.citizenSession.account?.barangay"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11.5px] font-medium rounded-xl transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                :class="tab === key ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                                <i :data-lucide="icon" class="w-3.5 h-3.5"></i><span x-text="t(en, fil)"></span>
                            </button>
                        </template>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="flex items-center gap-2 text-[11.5px] text-ink-muted">
                            <span x-text="t('Category', 'Kategorya')"></span>
                            <select x-model="category" class="min-h-[36px] rounded-xl border-0 surface-pressed-sm px-3 text-xs text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                                <option value="" x-text="t('All categories', 'Lahat ng kategorya')"></option>
                                <template x-for="[value, fil] in categories" :key="value"><option :value="value" x-text="t(value, fil)" :selected="value === category"></option></template>
                            </select>
                        </label>
                        <p class="text-[11px] text-ink-muted" x-text="shown.length + ' ' + t(shown.length === 1 ? 'post' : 'posts', 'post')" aria-live="polite"></p>
                    </div>
                </div>

                {{-- The feed --}}
                <div class="space-y-3">
                    <template x-for="post in shown" :key="post.key">
                        <article :id="'post-' + post.key" class="surface-glass rounded-[26px] overflow-hidden scroll-mt-24"
                            :class="focusKey === post.key && 'ring-2 ring-brand-400'"
                            :aria-label="(post.title || post.author) + ', ' + fmtTime(post.at)">
                            {{-- Who and when --}}
                            <header class="flex items-center gap-2.5 p-3.5 pb-2.5">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink flex items-center gap-1.5 min-w-0">
                                        <span class="truncate" x-text="post.author"></span>
                                        <i data-lucide="badge-check" class="w-4 h-4 text-brand-500 shrink-0" :aria-label="t('Official Esperanza LGU account', 'Opisyal na account ng Esperanza LGU')"></i>
                                    </p>
                                    <p class="text-[11px] text-ink-muted flex flex-wrap items-center gap-x-1.5">
                                        <span x-text="whereLine(post)"></span>
                                        <span x-show="post.category" class="inline-flex px-1.5 py-px rounded-full bg-slate-100 text-ink-body text-[10px] font-semibold" x-text="categoryLabel(post.category)"></span>
                                        <span x-show="post.greeting" class="inline-flex items-center gap-1 px-1.5 py-px rounded-full bg-gold-100 text-gold-700 text-[10px] font-semibold"><i data-lucide="party-popper" class="w-2.5 h-2.5"></i><x-ui.t fil="Pagbati" en="Greeting" /><span x-show="occasionLabel(post.greeting)" x-text="'· ' + occasionLabel(post.greeting)"></span></span>
                                    </p>
                                </div>
                            </header>

                            {{-- Title, text, photos and videos --}}
                            <h2 x-show="post.title" class="px-3.5 pb-1 text-[15px] font-bold text-ink leading-snug" x-text="post.title"></h2>
                            <div x-show="post.body" class="px-3.5 pb-2.5">
                                <p class="text-[13.5px] text-slate-700 leading-relaxed whitespace-pre-line break-words" :class="!post.expanded && isLong(post) && 'line-clamp-6'" x-text="post.body"></p>
                                <button x-show="isLong(post)" type="button" @click="post.expanded = !post.expanded" class="mt-1 text-xs font-semibold text-brand-700 hover:underline" x-text="post.expanded ? t('See less', 'Mas kaunti') : t('See more', 'Tingnan pa')"></button>
                            </div>
                            <x-balita.gallery media="post.media" label="mediaLabel(post)" />

                            {{-- Counts --}}
                            <div x-show="post.likes > 0 || post.comments_count > 0" class="flex items-center justify-between px-3.5 pt-2.5 text-[11px] text-ink-muted">
                                <span class="flex items-center gap-1" x-show="post.likes > 0">
                                    <span class="w-4 h-4 rounded-full bg-rose-500 flex items-center justify-center"><i data-lucide="heart" class="w-2.5 h-2.5 text-white" style="fill: white;"></i></span>
                                    <span x-text="post.likes"></span>
                                </span>
                                <button type="button" @click="toggleComments(post)" class="hover:underline ml-auto" x-show="post.comments_count > 0" x-text="post.comments_count + ' ' + t(post.comments_count === 1 ? 'comment' : 'comments', 'komento')"></button>
                            </div>

                            {{-- Like, comment, share --}}
                            <div class="grid grid-cols-3 gap-1 px-2.5 py-1.5 mt-1.5 border-t border-brand-600/10">
                                <button type="button" @click="toggleLike(post)" :aria-pressed="post.liked_by_me" :disabled="post.liking"
                                    class="flex items-center justify-center gap-1.5 text-xs font-medium py-2 rounded-xl hover:bg-white/60 transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                                    :class="post.liked_by_me ? 'text-rose-600' : 'text-ink-muted'">
                                    <i data-lucide="heart" class="w-4 h-4 transition-transform" :class="post.liked_by_me && 'scale-110'" :style="post.liked_by_me ? 'fill: currentColor' : ''"></i>
                                    <span x-text="t('Like', 'Gusto')"></span>
                                </button>
                                <button type="button" @click="toggleComments(post)" :aria-expanded="post.showComments" class="flex items-center justify-center gap-1.5 text-xs font-medium py-2 rounded-xl hover:bg-white/60 transition-colors text-ink-muted focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i><span x-text="t('Comment', 'Komento')"></span>
                                </button>
                                <button type="button" @click="share(post)" class="flex items-center justify-center gap-1.5 text-xs font-medium py-2 rounded-xl hover:bg-white/60 transition-colors text-ink-muted focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                    <i data-lucide="share-2" class="w-4 h-4"></i><span x-text="t('Share', 'Ibahagi')"></span>
                                </button>
                            </div>

                            {{-- Comments --}}
                            <div x-show="post.showComments" x-cloak class="bg-slate-50/70 border-t border-brand-600/10 px-3.5 py-3 space-y-3">
                                <p x-show="post.comments === null" class="text-[11px] text-ink-muted text-center py-2" x-text="t('Loading…', 'Naglo-load…')"></p>
                                <template x-for="c in (post.comments || [])" :key="c.id">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white flex items-center justify-center font-semibold shrink-0 text-[10px] mt-0.5" aria-hidden="true"><span x-text="initials(c.author)"></span></div>
                                        <div class="min-w-0 flex-1">
                                            <div class="bg-white rounded-2xl px-3.5 py-2 inline-block max-w-full border border-brand-600/10">
                                                <p class="text-[11px] font-semibold text-ink" x-text="c.author"></p>
                                                <p class="text-xs text-slate-700 mt-0.5 leading-snug whitespace-pre-line break-words" x-text="c.body"></p>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 ml-1">
                                                <span class="text-[10.5px] text-ink-muted" x-text="fmtTime(c.created_at)"></span>
                                                <button x-show="c.mine" type="button" @click="deleteComment(post, c)" class="text-[10.5px] font-semibold text-rose-700 hover:underline" x-text="t('Delete', 'Burahin')"></button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <button x-show="post.commentsMore" type="button" @click="loadComments(post, true)" class="text-[11px] font-semibold text-brand-700 hover:underline" x-text="t('Show more comments', 'Ipakita ang iba pang komento')"></button>
                                <p x-show="post.comments !== null && post.comments.length === 0" class="text-[11px] text-ink-muted text-center py-2" x-text="t('No comments yet. Be the first.', 'Wala pang komento. Ikaw ang mauna.')"></p>

                                <form @submit.prevent="addComment(post)" class="flex items-center gap-2.5">
                                    <x-citizen.avatar class="w-7 h-7 text-[10px]" />
                                    <div class="flex-1 relative">
                                        <input type="text" :id="'comment-' + post.key" x-model="post.draft" maxlength="1000"
                                            :aria-label="t('Write a comment', 'Sumulat ng komento')"
                                            :placeholder="t('Write a comment…', 'Sumulat ng komento…')"
                                            class="w-full rounded-full py-2 pl-4 pr-10 text-xs text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none border-0 surface-pressed-sm">
                                        <button type="submit" :disabled="!post.draft.trim() || post.sending" :aria-label="t('Send comment', 'Ipadala ang komento')"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full flex items-center justify-center text-brand-600 hover:bg-brand-50 disabled:opacity-40"><i data-lucide="send" class="w-3.5 h-3.5"></i></button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    </template>

                    <div x-show="shown.length === 0" class="flex flex-col items-center text-center py-12 px-4 surface-glass rounded-[26px]">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="megaphone-off" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600" x-text="emptyText()"></p>
                    </div>

                    <div x-show="hasMore" class="flex justify-center pt-1">
                        <x-ui.button variant="secondary" icon="chevrons-down" x-bind:disabled="loadingMore" @click="loadMore()">
                            <span x-text="loadingMore ? t('Loading…', 'Naglo-load…') : t('Load more posts', 'Ipakita ang iba pang post')"></span>
                        </x-ui.button>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="hidden lg:block sticky top-4 max-h-[calc(100vh-6rem)] overflow-y-auto scrollbar-thin space-y-4 pr-1" :aria-label="t('More from Balita', 'Iba pa sa Balita')">
                <x-ui.card padded="false" class="p-3.5">
                    <h2 class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5 text-ink-muted"></i><x-ui.t fil="Kamakailang Post" en="Recent Posts" /></h2>
                    <div class="space-y-2.5">
                        <template x-for="rp in recentPosts" :key="'recent-' + rp.key">
                            <button type="button" @click="tab = 'all'; category = ''; focusKey = rp.key; $nextTick(() => document.getElementById('post-' + rp.key)?.scrollIntoView({ behavior: 'smooth', block: 'center' }))" class="w-full flex items-start gap-2 text-left rounded-xl hover:bg-white/60 p-1 -m-1">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-7 h-7 rounded-full object-cover shrink-0">
                                <span class="min-w-0">
                                    <span class="block text-xs font-medium text-ink truncate" x-text="rp.author"></span>
                                    <span class="block text-[11px] text-ink-muted leading-snug" x-text="rp.title || (rp.body.length > 62 ? rp.body.slice(0, 62) + '…' : (rp.body || (rp.media.length ? t('Photos and videos', 'Mga larawan at video') : '')))"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false" class="p-3.5" x-show="trendingPosts.length">
                    <h2 class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5"><i data-lucide="flame" class="w-3.5 h-3.5 text-orange-500"></i><x-ui.t fil="Trending sa Balita" en="Trending in Balita" /></h2>
                    <div class="space-y-2.5">
                        <template x-for="tp in trendingPosts" :key="'trending-' + tp.key">
                            <div class="flex items-start gap-2">
                                <img src="{{ asset('images/esperanza/esperanza-seal-128.png') }}" alt="" class="w-7 h-7 rounded-full object-cover shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-ink truncate" x-text="tp.author"></p>
                                    <p class="text-[11px] text-ink-muted leading-snug" x-text="tp.title || (tp.body.length > 50 ? tp.body.slice(0, 50) + '…' : tp.body)"></p>
                                    <p class="text-[10.5px] text-rose-700 font-medium mt-0.5 flex items-center gap-1"><i data-lucide="heart" class="w-2.5 h-2.5" style="fill: currentColor"></i><span x-text="tp.likes + ' ' + t(tp.likes === 1 ? 'like' : 'likes', 'gusto')"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false" class="p-3.5">
                    <h2 class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5"><i data-lucide="calendar-days" class="w-3.5 h-3.5 text-ink-muted"></i><x-ui.t fil="Paparating na Kaganapan" en="Upcoming Events" /></h2>
                    <div class="space-y-2.5">
                        <template x-for="ev in events" :key="ev.key">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-700 flex flex-col items-center justify-center shrink-0 leading-none">
                                    <span class="text-xs font-bold" x-text="ev.date ? new Date(ev.date + 'T00:00:00').getDate() : ''"></span>
                                    <span class="text-[8px] font-semibold uppercase" x-text="ev.date ? fmtDate(ev.date + 'T00:00:00', { month: 'short', day: undefined, year: undefined }) : ''"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-ink truncate" x-text="ev.title || ev.name"></p>
                                    <p class="text-[11px] text-ink-muted truncate" x-text="ev.venue"></p>
                                </div>
                            </div>
                        </template>
                        <p class="text-[11px] text-ink-muted" x-show="!events.length"><x-ui.t fil="Walang paparating na kaganapan." en="No upcoming events." /></p>
                    </div>
                    <a href="{{ route('citizen.events') }}" class="block text-center text-[11px] font-medium text-brand-700 hover:underline mt-2.5 pt-2.5 border-t border-brand-600/10"><x-ui.t fil="Tingnan Lahat" en="View All" /></a>
                </x-ui.card>
            </aside>
        </div>

        <x-balita.viewer />
    </div>
</x-layouts.citizen>
