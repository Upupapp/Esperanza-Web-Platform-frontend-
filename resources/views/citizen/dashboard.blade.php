<x-layouts.citizen
    title="Dashboard"
    subtitle="Welcome back to your Esperanza Citizen Portal."
    active="dashboard"
>

    <div
        x-data="{
            ready: false,
            requests: [],
            balitaPreview: [],
            events: [],
            alerts: [],
            kpi: { dokyu: 0, dokyuReady: 0, tulong: 0, tulongApproved: 0 },

            async init() {
                await Promise.all([this.loadRequests(), this.loadBalita(), this.loadEvents(), this.loadAlerts()]);
                this.$store.citizenNotifications.load();
                setTimeout(() => (this.ready = true), 250);
            },

            async loadRequests() {
                try {
                    // The tiles count ALL of the resident's requests. They used to
                    // count only the five most recent rows fetched for the list
                    // below, so a sixth request never showed in any total.
                    const result = await $api.getAll('/citizen/requests', { params: { sort: '-submitted_at' } });
                    const all = result.data || [];
                    this.requests = all.slice(0, 5);
                    const thisMonth = new Date().toISOString().slice(0, 7);
                    this.kpi.dokyu = all.filter((r) => r.type === 'dokyu').length;
                    this.kpi.dokyuReady = all.filter((r) => r.type === 'dokyu' && r.status === 'Mark to Release').length;
                    this.kpi.tulong = all.filter((r) => r.type === 'tulong').length;
                    // A Tulong case is approved once it reaches Assistance Processing.
                    this.kpi.tulongApproved = all.filter((r) => r.type === 'tulong' && ['Assistance Processing', 'Ready for Release', 'Completed'].includes(r.status) && (r.submitted || '').startsWith(thisMonth)).length;
                } catch { this.requests = []; }
            },
            async loadBalita() {
                try {
                    const result = await $api.get('/announcements', { params: { per_page: 4 } });
                    this.balitaPreview = (result.data || []).map((post, i) => ({
                        id: post.id ?? i,
                        postId: post.id ?? null,
                        official: post.official,
                        author: post.author,
                        initials: post.official ? 'LGU' : (post.author || '').split(/\s+/).filter(Boolean).map((p) => p[0]).slice(0, 2).join(''),
                        barangay: post.barangay,
                        title: post.title,
                        body: post.body,
                        time: post.published_at ? new Date(post.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '',
                        likes: post.likes || 0,
                        liked: post.liked_by_me === true,
                        commentCount: post.comments_count || 0,
                    }));
                } catch { this.balitaPreview = []; }
            },
            // Published emergency alerts (Sakuna), the public feed the backend
            // has always served and no citizen page ever asked for (web backlog
            // C8). The resident's own barangay plus municipality-wide ones.
            async loadAlerts() {
                try {
                    const barangay = this.$store.citizenSession.account?.barangay;
                    const result = await $api.get('/alerts', { params: barangay ? { barangay } : {} });
                    this.alerts = result.data || [];
                    this.$nextTick(() => window.renderIcons?.());
                } catch { this.alerts = []; }
            },
            alertText(field) {
                if (!field) return '';
                if (typeof field === 'string') return field;
                return this.$store.lang.current === 'en' ? (field.en || field.fil || '') : (field.fil || field.en || '');
            },
            async loadEvents() {
                try {
                    // What is still to come, from today in Esperanza (the month's
                    // first events showed even after they had passed, 2026-10-21).
                    const from = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
                    const result = await $api.get('/events', { params: { from, per_page: 3 } });
                    this.events = (result.data || []).slice(0, 3).map((e) => ({
                        date: e.date ? new Date(e.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '',
                        time: e.time || '',
                        title: e.title || e.name,
                        venue: e.venue || '',
                        link: e.link || null,
                    }));
                } catch { this.events = []; }
            },
            // The birthday greeting comes from the server now, as an occasion
            // pop-up (x-citizen.greeting-popup), on the resident's birthday in
            // Esperanza, once a year (2026-10-21).
            // A real like. This used to flip the heart on screen only, so the
            // like vanished on reload and never reached the Balita feed.
            async toggleBalitaLike(post) {
                if (post.pending) return;
                post.pending = true;
                try {
                    const res = await $api.post('/citizen/announcements/' + post.id + '/like');
                    post.liked = res.data.liked;
                    post.likes = res.data.likes;
                } catch (err) {
                    this.$dispatch('toast', { message: err.localised ? err.localised(this.$store.lang.current) : 'Could not like this.', variant: 'error' });
                } finally { post.pending = false; }
            },
        }"
    >

        <!-- Skeleton state -->
        <div x-show="!ready" x-transition.opacity.duration.300ms>
            <x-ui.skeleton class="h-36 w-full rounded-[32px] mb-5" />

            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(160px,1fr))]">
                @for ($i = 0; $i < 4; $i++)
                    <div class="surface-glass rounded-[26px] p-[18px] space-y-3">
                        <x-ui.skeleton class="h-3 w-24" />
                        <x-ui.skeleton class="h-9 w-16" />
                        <x-ui.skeleton class="h-6 w-28 rounded-full" />
                    </div>
                @endfor
            </div>
        </div>

        <!-- Real content -->
        <div
            x-show="ready"
            x-cloak
            x-transition:enter="transition duration-[300ms] ease-standard"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
        >

            <!-- Emergency alerts: above everything else, while any is in force -->
            <template x-for="alert in alerts" :key="alert.ref">
                <div role="alert" class="mb-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 shadow-card">
                    <i data-lucide="siren" class="w-5 h-5 text-rose-700 shrink-0 mt-0.5"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-rose-900">
                            <span x-text="alertText(alert.title)"></span>
                            <span class="ml-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700" x-show="alert.level" x-text="alert.level"></span>
                        </p>
                        <p class="text-sm text-rose-900/90 mt-0.5 whitespace-pre-line" x-text="alertText(alert.body)"></p>
                        <p class="text-xs text-rose-800 mt-1">
                            <span x-text="alert.barangays && alert.barangays.length ? alert.barangays.join(', ') : ($store.lang.current === 'en' ? 'Whole municipality' : 'Buong munisipyo')"></span>
                            <span x-show="alert.published_at"> · <span x-text="alert.published_at ? new Date(alert.published_at).toLocaleString($store.lang.current === 'en' ? 'en-US' : 'fil-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></span></span>
                        </p>
                    </div>
                </div>
            </template>

            <!-- Welcome hero -->
            <div class="hero-banner mb-5">
                <div class="px-6 py-7 sm:px-[34px] sm:py-[30px] flex flex-col lg:flex-row lg:items-center gap-6">

                    <div class="flex items-center gap-4 sm:gap-5 flex-1 min-w-0">
                        <div class="relative shrink-0">
                            <x-citizen.avatar fallback="clay-gold" class="w-14 h-14 sm:w-[72px] sm:h-[72px] text-xl sm:text-2xl ring-2 ring-white/40" />

                            {{-- Only for a verified resident, as on the profile (live test
                                 2026-10-12: every account had it, one still Pending Review too). --}}
                            <span x-show="$store.citizenSession.account?.fullServiceAccess" x-cloak
                                  :title="$store.lang.current === 'en' ? 'Verified Resident' : 'Verified na Residente'"
                                  class="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full bg-emerald-500 border-2 border-navy-900 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-white"></i>
                            </span>
                        </div>

                        <div class="min-w-0">
                            {{-- Today's date in the browser: now() here was the day the site was built. --}}
                            <p
                                class="text-xs font-bold uppercase tracking-[.1em] text-gold-400"
                                x-text="new Date().toLocaleDateString($store.lang.current === 'en' ? 'en-US' : 'fil-PH', { weekday: 'long', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' })"
                            ></p>

                            {{-- Wraps rather than cuts: "Magandang araw, …" was cut to "Magandan…" at laptop width (2026-10-15). --}}
                            <h2
                                class="mt-1 text-[22px] sm:text-[28px] font-extrabold leading-tight tracking-[-.01em] text-white text-balance break-words"
                                x-text="($store.lang.current === 'en' ? 'Good day, ' : 'Magandang araw, ') + ($store.citizenSession.account?.firstName ?? 'Guest') + ' 👋'"
                            ></h2>

                            <p class="text-[15px] text-[#dce5ff] mt-1">
                                Brgy.
                                <span x-text="$store.citizenSession.account?.barangay ?? '—'"></span>
                                ·
                                <x-ui.t fil="narito ang buod ng iyong account." en="here's your account overview." />
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        {{-- Appointments (scope round 2026-10-15). --}}
                        <x-ui.button
                            :href="route('citizen.appointments')"
                            variant="ghost"
                            size="sm"
                            icon="calendar-clock"
                            class="!text-white !bg-white/14 hover:!bg-white/24 !border !border-white/28 backdrop-blur-[10px]"
                        >
                            <x-ui.t fil="Appointment" en="Appointments" />
                        </x-ui.button>

                        <x-ui.button
                            :href="route('citizen.assistance-requests')"
                            variant="ghost"
                            size="sm"
                            icon="hand-heart"
                            class="!text-white !bg-white/14 hover:!bg-white/24 !border !border-white/28 backdrop-blur-[10px]"
                        >
                            Tulong
                        </x-ui.button>

                        <x-ui.button
                            :href="route('citizen.document-requests')"
                            variant="gold"
                            size="sm"
                            icon="file-plus"
                        >
                            <x-ui.t fil="Bagong Kahilingan sa Dokyu" en="New Dokyu Request" />
                        </x-ui.button>
                    </div>

                </div>

                <!-- My Barangay — integrated into the hero, same footer-strip
                     pattern as the Profile page's completeness bar -->
                <div
                    x-show="$store.barangay.mine"
                    x-cloak
                    class="flex items-center gap-3 px-6 sm:px-[34px] py-3.5 border-t border-white/15 bg-white/[.06]"
                >
                    <div class="relative w-8 h-8 shrink-0">
                        <div class="absolute inset-0 rounded-full bg-white/10 flex items-center justify-center text-white/40">
                            <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
                        </div>

                        <img
                            :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                            x-show="$store.barangay.mine?.seal"
                            x-on:error="$el.style.display='none'"
                            alt=""
                            class="relative w-8 h-8 rounded-full object-cover ring-1 ring-white/30"
                        >
                    </div>

                    <p class="min-w-0 flex-1 text-[13px] text-[#dce5ff] truncate">
                        <x-ui.t fil="Barangay" en="Barangay" />
                        <span class="font-semibold text-white" x-text="$store.barangay.mine?.label"></span>
                        <span class="text-white/25 mx-1">·</span>
                        <x-ui.t fil="Punong Barangay:" en="Punong Barangay:" />
                        <span class="text-white/90 font-medium" x-text="$store.barangay.mine?.officials?.punong_barangay"></span>
                    </p>

                    <a
                        :href="'tel:' + ($store.barangay.mine?.contact || '').replace(/[^0-9+]/g, '')"
                        x-show="$store.barangay.mine?.contact"
                        :title="$store.barangay.mine?.contact"
                        class="w-7 h-7 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/70 hover:text-white transition-colors shrink-0"
                    >
                        <i data-lucide="phone" class="w-3 h-3"></i>
                    </a>

                    <a
                        href="{{ route('citizen.directory') }}"
                        class="shrink-0 inline-flex items-center gap-0.5 text-[11px] font-medium text-white/70 hover:text-white transition-colors"
                    >
                        <x-ui.t fil="Tingnan" en="View" />
                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <x-citizen.banner-carousel />

            @php
                $introFil = 'Ang <strong>Dokyu</strong> ay para sa paghingi ng mga dokumento ng munisipyo (clearance, ID, permit). Ang <strong>Tulong</strong> ay para sa pag-apply sa mga programang ayuda. Parehong simple ang hakbang: pumili, punan ang detalye, magbayad kung kailangan, pagkatapos i-review at i-submit — masusubaybayan mo anumang oras mula sa dashboard na ito.';

                $introEn = "<strong>Dokyu</strong> is for requesting municipal documents (clearances, IDs, permits). <strong>Tulong</strong> is for applying to assistance and ayuda programs. Both use the same simple steps: choose, fill in details, pay if needed, then review and submit — you can track progress anytime from this dashboard.";
            @endphp

            <x-ui.page-intro
                id="dashboard"
                icon="compass"
                title="Bago ka lang? Narito ang mabilisang gabay."
                titleEn="New here? Here's a quick orientation."
            >
                <span x-html="$store.lang.current === 'en' ? @js($introEn) : @js($introFil)">
                    {!! $introFil !!}
                </span>
            </x-ui.page-intro>

            <!-- KPI row -->
            <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(160px,1fr))] mb-5">

                <x-ui.stat-card layout="compact"
                    class="h-full"
                    :href="route('citizen.document-requests')"
                    label="Dokyu"
                    valueBind="kpi.dokyu"
                    icon="file-text"
                    color="brand"
                    sublabelBind="kpi.dokyuReady + ($store.lang.current === 'en' ? ' ready for release' : ' handa nang kunin')"
                    :delay="0"
                />

                <x-ui.stat-card layout="compact"
                    class="h-full"
                    :href="route('citizen.assistance-requests')"
                    label="Tulong"
                    valueBind="kpi.tulong"
                    icon="hand-heart"
                    color="deep"
                    sublabelBind="kpi.tulongApproved + ($store.lang.current === 'en' ? ' approved this month' : ' aprubado ngayong buwan')"
                    :delay="40"
                />

                <x-ui.stat-card layout="compact"
                    class="h-full"
                    :href="route('citizen.notifications')"
                    label="Hindi Nabasang Abiso"
                    labelEn="Unread Notifications"
                    valueBind="$store.citizenNotifications.unreadCount"
                    icon="bell"
                    color="orange"
                    :delay="80"
                />

                <x-ui.stat-card layout="compact"
                    class="h-full"
                    :href="route('citizen.profile')"
                    label="Pagkakumpleto ng Profile"
                    labelEn="Profile Completeness"
                    valueBind="($store.citizenSession.account?.profileCompleteness ?? 0) + '%'"
                    icon="user-round-check"
                    color="light"
                    sublabel="Magdagdag ng valid ID para matapos"
                    sublabelEn="Add a valid ID to finish"
                    :delay="120"
                />

            </div>

            <!-- Middle row: my requests + announcements -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start mb-5">

                <x-ui.card padded="false" class="lg:col-span-2">

                    <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-2">
                        <h3 class="text-lg font-extrabold text-ink">
                            <x-ui.t fil="Mga Kamakailang Kahilingan" en="My Recent Requests" />
                        </h3>

                        <a
                            href="{{ route('citizen.document-requests') }}"
                            class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700 shrink-0"
                        >
                            <x-ui.t fil="Tingnan Lahat" en="View All" />
                        </a>
                    </div>

                    <div class="divide-y divide-brand-600/[.08]">

                        <template x-for="req in requests" :key="req.ref">
                            <a
                                :href="(req.type === 'tulong' ? '{{ route('citizen.assistance-requests') }}' : '{{ route('citizen.document-requests') }}') + '?ref=' + encodeURIComponent(req.ref)"
                                class="flex items-center justify-between gap-4 px-4 py-2.5 hover:bg-white/60 transition-colors"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="req.service"></p>
                                    <p class="text-xs text-ink-muted mt-0.5 font-mono">
                                        <span x-text="req.ref"></span> ·
                                        <x-ui.t fil="Isinumite" en="Submitted" />
                                        <span x-text="req.submitted ? new Date(req.submitted).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''"></span>
                                    </p>
                                </div>
                                <x-ui.badge status-bind="req.status" class="shrink-0" />
                            </a>
                        </template>

                        <p class="px-4 py-6 text-center text-xs text-ink-muted" x-show="!requests.length">
                            <x-ui.t fil="Wala ka pang kahilingan." en="You have no requests yet." />
                        </p>

                    </div>

                </x-ui.card>

                <x-ui.card padded="false">

                    <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-2">
                        <h3 class="text-lg font-extrabold text-ink">
                            Balita
                        </h3>

                        <a
                            href="{{ route('citizen.announcements') }}"
                            class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700 shrink-0"
                        >
                            <x-ui.t fil="Tingnan Lahat" en="View All" />
                        </a>
                    </div>

                    <div class="divide-y divide-brand-600/[.08]">

                        <template x-for="post in balitaPreview" :key="post.id">

                            <div class="px-4 py-2.5 hover:bg-white/60 transition-colors">

                                {{-- Each post opens itself in Balita (?post=ID), not the
                                     top of the feed (parity check, 2026-10-26). --}}
                                <a
                                    :href="'{{ route('citizen.announcements') }}' + (post.postId !== null ? '?post=' + encodeURIComponent(post.postId) : '')"
                                    class="flex items-start gap-2.5"
                                >

                                    <img
                                        :src="'{{ asset('images/esperanza/esperanza-seal.png') }}'"
                                        x-show="post.official"
                                        alt="Official Municipality of Esperanza post"
                                        class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0"
                                    >

                                    <div
                                        x-show="!post.official"
                                        class="w-8 h-8 rounded-full clay-blue font-extrabold flex items-center justify-center font-semibold shrink-0 text-[10px]"
                                        x-text="post.initials"
                                    ></div>

                                    <div class="min-w-0 flex-1">

                                        <p class="text-xs font-semibold text-ink flex items-center gap-1 truncate">

                                            <span x-text="post.author"></span>

                                            <i
                                                data-lucide="badge-check"
                                                class="w-3 h-3 text-brand-500 shrink-0"
                                                x-show="post.official"
                                            ></i>

                                        </p>

                                        <p
                                            class="text-xs font-medium text-ink mt-0.5 line-clamp-1 leading-snug"
                                            x-show="post.title"
                                            x-text="post.title"
                                        ></p>

                                        <p
                                            class="text-xs text-ink-muted mt-0.5 line-clamp-2 leading-snug"
                                            x-text="post.body"
                                        ></p>

                                        <p
                                            class="text-[10.5px] text-ink-muted mt-1"
                                            x-text="post.time"
                                        ></p>

                                    </div>

                                </a>

                                <div class="flex items-center gap-3 mt-1.5 ml-[42px]">

                                    <button
                                        type="button"
                                        @click="toggleBalitaLike(post)"
                                        class="flex items-center gap-1 text-[10.5px] font-medium transition-colors"
                                        :class="post.liked ? 'text-rose-600' : 'text-ink-muted hover:text-slate-600'"
                                    >
                                        <i
                                            data-lucide="heart"
                                            class="w-3 h-3 transition-transform"
                                            :class="post.liked && 'scale-125'"
                                            :style="post.liked ? 'fill: currentColor' : ''"
                                        ></i>

                                        <span x-text="post.likes"></span>
                                    </button>

                                    <a
                                        :href="'{{ route('citizen.announcements') }}' + (post.postId !== null ? '?post=' + encodeURIComponent(post.postId) : '')"
                                        class="flex items-center gap-1 text-[10.5px] font-medium text-ink-muted hover:text-slate-600 transition-colors"
                                        x-show="post.commentCount > 0"
                                    >
                                        <i data-lucide="message-circle" class="w-3 h-3"></i>
                                        <span x-text="post.commentCount"></span>
                                    </a>

                                </div>

                            </div>

                        </template>

                        <p class="px-4 py-6 text-center text-xs text-ink-muted" x-show="!balitaPreview.length">
                            <x-ui.t fil="Wala pang balita." en="No announcements yet." />
                        </p>

                    </div>

                </x-ui.card>

            </div>

            <!-- Upcoming events -->
            <x-ui.card padded="false">

                <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-2">
                    <h3 class="text-lg font-extrabold text-ink">
                        <x-ui.t fil="Mga Paparating na Kaganapan" en="Upcoming Events" />
                    </h3>

                    <a
                        href="{{ route('citizen.events') }}"
                        class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700 shrink-0"
                    >
                        <x-ui.t fil="Tingnan Lahat" en="View All" />
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 px-4 pb-4">

                    <template x-for="(event, i) in events" :key="i">
                        <a
                            :href="event.link || '{{ route('citizen.events') }}'"
                            class="block rounded-2xl border border-brand-600/10 p-3.5 hover:border-brand-200 hover:shadow-card hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                        >
                            <div class="flex items-center gap-2 text-xs font-medium text-brand-600 mb-1.5">
                                <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
                                <span x-text="[event.date, event.time].filter(Boolean).join(' · ')"></span>
                            </div>
                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="event.title"></p>
                            <p class="text-xs text-ink-muted mt-1 flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                <span x-text="event.venue"></span>
                            </p>
                        </a>
                    </template>

                    <p class="col-span-3 py-6 text-center text-xs text-ink-muted" x-show="!events.length">
                        <x-ui.t fil="Walang paparating na kaganapan." en="No upcoming events." />
                    </p>

                </div>

            </x-ui.card>

        </div>
    </div>

</x-layouts.citizen>
