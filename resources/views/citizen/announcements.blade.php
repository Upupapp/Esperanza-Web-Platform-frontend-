<x-layouts.citizen title="Balita" subtitle="Latest news from the municipality — and your neighbors." active="announcements">
    <div
        x-data="{
            cat: 'All',
            loading: true,
            failed: false,
            posts: [],
            events: [],
            commentDraft: {},
            reportReason: {},
            reportOpenFor: null,
            reportSubmitting: {},
            // The post a notification opened (?post=ID), highlighted and scrolled to.
            focusPost: null,
            fromAnnouncement(a) {
                return {
                    kind: 'announcement', id: a.id, author: a.author, barangay: a.barangay, category: a.category,
                    title: a.title, body: a.body, image_url: a.image_url, official: true, likes: a.likes,
                    // Sent with the resident's token, the public feed says which
                    // announcements they liked (null only for an anonymous read).
                    comments_count: a.comments_count, liked_by_me: a.liked_by_me === true, mine: false, visible: true,
                    at: a.published_at, comments: null, showComments: false,
                };
            },
            // A New in Balita notification opens /citizen/announcements?post=ID
            // (Esperanza QA, 2026-10-07): that post is shown even when it is
            // older than the first page, and the page scrolls to it.
            async openLinkedPost() {
                const want = Number(new URLSearchParams(window.location.search).get('post'));
                if (!want) return;
                if (!this.posts.some((p) => p.kind === 'announcement' && p.id === want)) {
                    try {
                        const one = await $api.get('/announcements/' + want);
                        if (one.data) this.posts.unshift(this.fromAnnouncement(one.data));
                    } catch { return; }
                }
                this.cat = 'All';
                this.focusPost = 'announcement-' + want;
                this.$nextTick(() => document.getElementById('post-announcement-' + want)?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            },
            async init() {
                this.loading = true;
                this.failed = false;
                try {
                    // The resident's own engagement lives under /citizen (likes,
                    // comments, the community feed); only the announcement and
                    // event reads are public. The community feed failing must not
                    // blank the official announcements, so it fails on its own.
                    const [ann, comm, ev] = await Promise.all([
                        $api.get('/announcements', { params: { per_page: 30 } }),
                        $api.get('/citizen/community-posts', { params: { per_page: 30 } }).catch(() => ({ data: [] })),
                        $api.get('/events', { params: { per_page: 4, from: new Date().toISOString().slice(0, 10) } }).catch(() => ({ data: [] })),
                    ]);
                    const annPosts = (ann.data || []).map(a => this.fromAnnouncement(a));
                    const commPosts = (comm.data || []).map(p => ({
                        kind: 'community', id: p.id, author: p.author, barangay: p.barangay, category: p.category,
                        title: null, body: p.body, image_url: p.image_url, official: false, likes: p.likes,
                        comments_count: p.comments_count, liked_by_me: p.liked_by_me, mine: p.mine, visible: p.visible,
                        at: p.created_at, comments: null, showComments: false,
                    }));
                    this.posts = [...annPosts, ...commPosts].sort((a, b) => new Date(b.at) - new Date(a.at));
                    this.events = ev.data || [];
                } catch { this.failed = true; } finally { this.loading = false; }
                await this.openLinkedPost();
            },
            get categories() { return [...new Set(this.posts.map(p => p.category).filter(Boolean))].sort(); },
            matchesCat(post) {
                if (this.cat === 'MyBarangay') return post.barangay === this.$store.citizenSession.account?.barangay;
                return this.cat === 'All' || (this.cat === 'Official' && post.official) || (this.cat === 'Community' && !post.official) || this.cat === post.category;
            },
            get filteredPostCount() { return this.posts.filter(p => this.matchesCat(p)).length; },
            get recentPosts() { return this.posts.slice(0, 4); },
            get trendingPosts() { return [...this.posts].sort((a, b) => b.likes - a.likes).slice(0, 3); },
            initials(name) { return (name || '').split(/\s+/).filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase(); },
            fmtTime(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                const mins = Math.round((Date.now() - d.getTime()) / 60000);
                if (mins < 1) return $store.lang.current === 'en' ? 'Just now' : 'Ngayon lang';
                if (mins < 60) return mins + (mins === 1 ? (this.$store.lang.current === 'en' ? ' min ago' : ' minuto ang nakalipas') : (this.$store.lang.current === 'en' ? ' mins ago' : ' minuto ang nakalipas'));
                const hrs = Math.round(mins / 60);
                if (hrs < 24) return hrs + (this.$store.lang.current === 'en' ? ' hrs ago' : ' oras ang nakalipas');
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            engagementPath(post, action) {
                return (post.kind === 'announcement' ? '/citizen/announcements/' : '/citizen/community-posts/') + post.id + '/' + action;
            },
            async toggleLike(post) {
                const endpoint = this.engagementPath(post, 'like');
                try {
                    const res = await $api.post(endpoint);
                    post.liked_by_me = res.data.liked;
                    post.likes = res.data.likes;
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not like this.', variant: 'error' });
                }
            },
            async toggleComments(post) {
                post.showComments = !post.showComments;
                if (post.showComments && post.comments === null) {
                    await this.loadComments(post);
                }
            },
            async loadComments(post) {
                const endpoint = this.engagementPath(post, 'comments');
                try {
                    const res = await $api.get(endpoint, { params: { per_page: 50 } });
                    post.comments = res.data || [];
                } catch { post.comments = []; }
            },
            async addComment(post) {
                const text = (this.commentDraft[post.id] || '').trim();
                if (!text) return;
                const endpoint = this.engagementPath(post, 'comments');
                try {
                    const res = await $api.post(endpoint, { body: text });
                    if (post.comments === null) post.comments = [];
                    post.comments.push(res.data);
                    post.comments_count++;
                    this.commentDraft[post.id] = '';
                    this.$nextTick(() => window.renderIcons?.());
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not post your comment.', variant: 'error' });
                }
            },
            async submitReport(post) {
                const reason = (this.reportReason[post.id] || '').trim();
                if (!reason || this.reportSubmitting[post.id]) return;
                this.reportSubmitting[post.id] = true;
                try {
                    await $api.post(this.engagementPath(post, 'report'), { reason });
                    this.reportOpenFor = null;
                    this.reportReason[post.id] = '';
                    this.$dispatch('toast', { message: 'Naiulat ang post na ito sa LGU.', variant: 'success' });
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not report this post.', variant: 'error' });
                } finally { this.reportSubmitting[post.id] = false; }
            },
            postsToday(author) {
                const today = new Date().toDateString();
                return this.posts.filter(p => p.author === author && p.at && new Date(p.at).toDateString() === today).length;
            },
        }"
        x-init="init()"
        class="animate-fade-up max-w-6xl mx-auto"
    >
        <x-ui.page-intro id="balita-feed" icon="users-round" title="Manatiling Updated sa Balita" titleEn="Stay Updated with Balita">
            <x-ui.t
                fil="Tingnan ang mga balita, larawan, o video mula sa munisipyo at kapwa residente. I-like o i-comment ang mga post."
                en="See news, photos, and videos from the municipality and fellow residents. Like or comment on posts."
            />
        </x-ui.page-intro>

        <div x-show="loading" class="space-y-3"><x-ui.skeleton class="h-24" /><x-ui.skeleton class="h-40 opacity-80" /></div>
        <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load Balita.'" retryExpr="init()" :retryable="true" /></div>

        <div x-show="!loading && !failed" class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px] gap-5 items-start">
            <!-- Main column -->
            <div class="min-w-0">
                <!-- Category filter -->
                <div class="surface-glass rounded-[26px] p-3 mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 surface-pressed rounded-xl p-1 w-fit overflow-x-auto">
                    <template x-for="c in ['All', 'Official', 'Community', ...categories]" :key="c">
                        <button
                            @click="cat = c"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap"
                            :class="cat === c ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                            x-text="c === 'All' ? ($store.lang.current === 'en' ? 'All' : 'Lahat') : (c === 'Official' ? ($store.lang.current === 'en' ? 'Official' : 'Opisyal') : (c === 'Community' ? ($store.lang.current === 'en' ? 'Community' : 'Komunidad') : c))"
                        ></button>
                    </template>
                    <button
                        x-show="$store.barangay.mine" x-cloak
                        @click="cat = 'MyBarangay'"
                        class="px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap flex items-center gap-1"
                        :class="cat === 'MyBarangay' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                    ><i data-lucide="map-pinned" class="w-3 h-3"></i><x-ui.t fil="Aking Barangay" en="My Barangay" /></button>
                </div>
                <p class="text-[11px] text-ink-muted shrink-0" x-text="filteredPostCount + ' ' + ($store.lang.current === 'en' ? 'of ' + posts.length + ' posts' : 'sa ' + posts.length + ' post')"></p>
                </div>

                <!-- Feed -->
                <div class="space-y-3">
                    <template x-for="post in posts" :key="post.kind + post.id">
                        <div
                            x-show="matchesCat(post)"
                            :id="'post-' + post.kind + '-' + post.id"
                            class="surface-glass rounded-[26px] scroll-mt-24"
                            :class="focusPost === post.kind + '-' + post.id && 'ring-2 ring-brand-400'"
                        >
                            <!-- Header -->
                            <div class="flex items-center gap-2.5 p-3.5 pb-2.5">
                                <div class="relative shrink-0" x-data="{ showCard: false }" @mouseenter="showCard = true" @mouseleave="showCard = false">
                                    <img :src="'{{ asset('images/esperanza/esperanza-seal-128.png') }}'" x-show="post.official" alt="Official Municipality of Esperanza post" class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm cursor-pointer">
                                    <div x-show="!post.official" class="w-9 h-9 rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold text-xs cursor-pointer">
                                        <span x-text="initials(post.author)"></span>
                                    </div>

                                    <!-- Hover profile card -->
                                    <div
                                        x-show="showCard"
                                        x-transition:enter="transition duration-[160ms] ease-standard"
                                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                        x-cloak
                                        class="absolute left-0 top-full mt-2 z-20 w-64 surface-glass rounded-[22px] shadow-float border-white/90 p-4"
                                    >
                                        <div class="flex items-center gap-3 mb-3">
                                            <img :src="'{{ asset('images/esperanza/esperanza-seal-128.png') }}'" x-show="post.official" alt="Official Municipality of Esperanza post" class="w-12 h-12 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0">
                                            <div x-show="!post.official" class="w-12 h-12 rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0 text-sm">
                                                <span x-text="initials(post.author)"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-ink flex items-center gap-1 truncate">
                                                    <span x-text="post.author"></span>
                                                    <i data-lucide="badge-check" class="w-3.5 h-3.5 text-brand-500 shrink-0" x-show="post.official"></i>
                                                </p>
                                                <p class="text-xs text-ink-muted truncate" x-text="post.official ? 'Opisyal na Account ng LGU' : ('Residente — Brgy. ' + post.barangay)"></p>
                                            </div>
                                        </div>
                                        <div class="rounded-xl bg-slate-50 border border-brand-600/10 px-3 py-2.5 flex items-center gap-2.5">
                                            <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0"><i data-lucide="calendar-check" class="w-3.5 h-3.5"></i></span>
                                            <p class="text-xs text-slate-600 leading-snug">
                                                <template x-if="postsToday(post.author) > 0">
                                                    <span><span class="font-semibold text-ink" x-text="postsToday(post.author)"></span> <x-ui.t fil="post ngayong araw" en="post(s) today" /></span>
                                                </template>
                                                <template x-if="postsToday(post.author) === 0">
                                                    <x-ui.t fil="Walang bagong post ngayong araw" en="No new posts today" />
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink flex items-center gap-1.5 truncate">
                                        <span x-text="post.author"></span>
                                        <i data-lucide="badge-check" class="w-3.5 h-3.5 text-brand-500 shrink-0" x-show="post.official"></i>
                                    </p>
                                    <p class="text-[11px] text-ink-muted" x-text="(post.barangay ? 'Brgy. ' + post.barangay + ' · ' : '') + fmtTime(post.at) + (post.mine && !post.visible ? ' · ' + ($store.lang.current === 'en' ? 'Pending review — only visible to you' : 'Hinihintay ang pag-apruba — ikaw lang ang nakakakita nito') : '')"></p>
                                </div>

                                <!-- Post options -->
                                <div class="relative shrink-0" x-show="post.kind === 'community' && !post.mine" x-data="{ menuOpen: false }" @click.outside="menuOpen = false; reportOpenFor === post.id && (reportOpenFor = null)">
                                    <button aria-label="More options"
                                        type="button" @click="menuOpen = !menuOpen"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-ink-muted hover:bg-slate-100 hover:text-slate-600 transition-colors"
                                    ><i data-lucide="ellipsis" class="w-4 h-4"></i></button>
                                    <div
                                        x-show="menuOpen"
                                        x-transition:enter="transition duration-[160ms] ease-standard"
                                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                        x-cloak
                                        class="absolute right-0 top-full mt-1 z-20 w-56 surface-glass rounded-[22px] shadow-float border-white/90 p-1.5"
                                    >
                                        <template x-if="reportOpenFor !== post.id">
                                            <button
                                                type="button"
                                                @click="reportOpenFor = post.id"
                                                class="w-full flex items-center gap-2.5 px-2 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 rounded-2xl transition-colors"
                                            ><i data-lucide="flag" class="w-4 h-4"></i><x-ui.t fil="I-ulat" en="Report" /></button>
                                        </template>
                                        <template x-if="reportOpenFor === post.id">
                                            <div class="p-1">
                                                <label class="block text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-1">Reason</label>
                                                <textarea aria-label="Reason for report" x-model="reportReason[post.id]" rows="2" placeholder="Bakit mo ini-report ang post na ito?" class="w-full text-xs rounded-xl border border-slate-200 px-2.5 py-2 focus:border-rose-400 focus:ring-4 focus:ring-rose-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                                                <div class="flex items-center justify-end gap-2 mt-1.5">
                                                    <button type="button" @click="reportOpenFor = null; menuOpen = false" class="text-[11px] text-ink-muted hover:text-slate-600">Cancel</button>
                                                    <button type="button" :disabled="!((reportReason[post.id]||'').trim()) || reportSubmitting[post.id]" @click="submitReport(post); menuOpen = false" class="text-[11px] font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-xl px-2.5 py-1">Submit</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Title (an LGU announcement has one) and body -->
                            <p x-show="post.title" class="px-3.5 pb-1 text-sm font-semibold text-ink leading-snug" x-text="post.title"></p>
                            <p class="px-3.5 pb-2.5 text-[13px] text-slate-700 leading-relaxed whitespace-pre-line" x-text="post.body"></p>

                            <!-- Media: at most one attached image, per the real post schema -->
                            <div class="bg-slate-100" x-show="post.image_url">
                                <img :src="post.image_url" alt="" class="w-full max-h-72 object-cover">
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center justify-between px-3.5 pt-2.5 text-[11px] text-ink-muted" x-show="post.likes > 0 || post.comments_count > 0">
                                <span class="flex items-center gap-1" x-show="post.likes > 0">
                                    <span class="w-3.5 h-3.5 rounded-full bg-rose-500 flex items-center justify-center"><i data-lucide="heart" class="w-2 h-2 text-white" style="fill: white;"></i></span>
                                    <span x-text="post.likes"></span>
                                </span>
                                <button @click="toggleComments(post)" class="hover:underline" x-show="post.comments_count > 0">
                                    <span x-text="post.comments_count"></span> <x-ui.t fil="komento" en="comments" />
                                </button>
                            </div>

                            <!-- Actions -->
                            <div class="grid grid-cols-2 gap-1 px-2.5 py-1.5 mt-1.5 border-t border-brand-600/10">
                                <button
                                    @click="toggleLike(post)"
                                    class="flex items-center justify-center gap-1.5 text-xs font-medium py-1.5 rounded-xl hover:bg-white/60 transition-colors"
                                    :class="post.liked_by_me ? 'text-rose-600' : 'text-ink-muted'"
                                >
                                    <i data-lucide="heart" class="w-4 h-4 transition-transform" :class="post.liked_by_me && 'scale-125'" :style="post.liked_by_me ? 'fill: currentColor' : ''"></i>
                                    <x-ui.t fil="Gusto" en="Like" />
                                </button>
                                <button @click="toggleComments(post)" class="flex items-center justify-center gap-1.5 text-xs font-medium py-1.5 rounded-xl hover:bg-white/60 transition-colors text-ink-muted">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i><x-ui.t fil="Komento" en="Comment" />
                                </button>
                            </div>

                            <!-- Comments -->
                            <div x-show="post.showComments" x-cloak x-transition class="bg-slate-50/70 border-t border-brand-600/10 px-3.5 py-3 space-y-3">
                                <template x-if="post.comments === null">
                                    <p class="text-[11px] text-ink-muted text-center py-2">Loading…</p>
                                </template>
                                <template x-for="c in (post.comments || [])" :key="c.id">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 text-white flex items-center justify-center font-semibold shrink-0 text-[10px] mt-0.5">
                                            <span x-text="initials(c.author)"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="bg-white hover:shadow-card rounded-2xl px-3.5 py-2 inline-block max-w-full border border-brand-600/10 transition-shadow duration-[220ms] ease-standard">
                                                <p class="text-[11px] font-semibold text-ink" x-text="c.author"></p>
                                                <p class="text-xs text-slate-600 mt-0.5 leading-snug" x-text="c.body"></p>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 ml-1">
                                                <span class="text-[10.5px] text-ink-faint" x-text="fmtTime(c.created_at)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="post.comments !== null && post.comments.length === 0">
                                    <p class="text-[11px] text-ink-muted text-center py-2"><x-ui.t fil="Wala pang komento." en="No comments yet." /></p>
                                </template>

                                <div class="flex items-center gap-2.5">
                                    <x-citizen.avatar class="w-7 h-7 text-[10px]" />
                                    <div class="flex-1 relative">
                                        <input aria-label="Comment draft"
                                            type="text"
                                            x-model="commentDraft[post.id]"
                                            @keydown.enter="addComment(post)"
                                            x-bind:placeholder="$store.lang.current === 'en' ? 'Write a comment...' : 'Sumulat ng komento...'"
                                            class="w-full rounded-full py-2 pl-4 pr-10 text-xs text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard border-0 surface-pressed-sm"
                                        >
                                        <button aria-label="Send"
                                            type="button" @click="addComment(post)"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-brand-500 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                                        ><i data-lucide="send" class="w-3.5 h-3.5"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-brand-600/10" x-show="filteredPostCount === 0">
                        <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="megaphone-off" class="w-7 h-7"></i></span>
                        <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Wala pang balita sa kategoryang ito." en="No Balita posts in this category yet." /></p>
                    </div>
                </div>
            </div>

            <!-- Sidebar: sticks in place and scrolls independently of the feed -->
            <div class="hidden lg:block sticky top-4 max-h-[calc(100vh-6rem)] overflow-y-auto scrollbar-thin space-y-4 pr-1">
                <x-ui.card padded="false" class="p-3.5">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-ink-muted"></i><x-ui.t fil="Kamakailang Post" en="Recent Posts" />
                    </p>
                    <div class="space-y-2.5">
                        <template x-for="rp in recentPosts" :key="'recent-' + rp.kind + rp.id">
                            <div class="flex items-start gap-2">
                                <img :src="'{{ asset('images/esperanza/esperanza-seal-128.png') }}'" x-show="rp.official" alt="" class="w-7 h-7 rounded-full object-cover shrink-0">
                                <div x-show="!rp.official" class="w-7 h-7 rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0 text-[9px]" x-text="initials(rp.author)"></div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-ink truncate" x-text="rp.author"></p>
                                    <p class="text-[11px] text-ink-muted leading-snug" x-text="rp.title || (rp.body.length > 62 ? rp.body.slice(0, 62) + '…' : rp.body)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false" class="p-3.5">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5">
                        <i data-lucide="flame" class="w-3.5 h-3.5 text-orange-500"></i><x-ui.t fil="Trending sa Balita" en="Trending in Balita" />
                    </p>
                    <div class="space-y-2.5">
                        <template x-for="tp in trendingPosts" :key="'trending-' + tp.kind + tp.id">
                            <div class="flex items-start gap-2">
                                <img :src="'{{ asset('images/esperanza/esperanza-seal-128.png') }}'" x-show="tp.official" alt="" class="w-7 h-7 rounded-full object-cover shrink-0">
                                <div x-show="!tp.official" class="w-7 h-7 rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0 text-[9px]" x-text="initials(tp.author)"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-ink truncate" x-text="tp.author"></p>
                                    <p class="text-[11px] text-ink-muted leading-snug" x-text="tp.title || (tp.body.length > 50 ? tp.body.slice(0, 50) + '…' : tp.body)"></p>
                                    <p class="text-[10px] text-rose-500 font-medium mt-0.5 flex items-center gap-1"><i data-lucide="heart" class="w-2.5 h-2.5" style="fill: currentColor"></i><span x-text="tp.likes + ' likes'"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false" class="p-3.5">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide mb-2.5 flex items-center gap-1.5">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-ink-muted"></i><x-ui.t fil="Paparating na Kaganapan" en="Upcoming Events" />
                    </p>
                    <div class="space-y-2.5">
                        <template x-for="ev in events" :key="ev.key">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex flex-col items-center justify-center shrink-0 leading-none">
                                    <span class="text-xs font-bold" x-text="ev.date ? new Date(ev.date + 'T00:00:00').getDate() : ''"></span>
                                    <span class="text-[8px] font-semibold uppercase" x-text="ev.date ? new Date(ev.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short' }) : ''"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-ink truncate" x-text="ev.title || ev.name"></p>
                                    <p class="text-[11px] text-ink-muted truncate" x-text="ev.venue"></p>
                                </div>
                            </div>
                        </template>
                        <p class="text-[11px] text-ink-muted" x-show="!events.length"><x-ui.t fil="Walang paparating na kaganapan." en="No upcoming events." /></p>
                    </div>
                    <a href="{{ route('citizen.events') }}" class="block text-center text-[11px] font-medium text-brand-600 hover:underline mt-2.5 pt-2.5 border-t border-brand-600/10">
                        <x-ui.t fil="Tingnan Lahat" en="View All" />
                    </a>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.citizen>
