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
            isBirthdayToday: false,

            async init() {
                await Promise.all([this.loadRequests(), this.loadBalita(), this.loadEvents(), this.loadAlerts()]);
                this.checkBirthday();
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
                        official: post.official,
                        author: post.author,
                        initials: post.official ? 'LGU' : (post.author || '').split(/\s+/).filter(Boolean).map((p) => p[0]).slice(0, 2).join(''),
                        barangay: post.barangay,
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
                    const month = new Date().toISOString().slice(0, 7);
                    const result = await $api.get('/events', { params: { month } });
                    this.events = (result.data || []).slice(0, 3).map((e) => ({
                        date: e.date ? new Date(e.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '',
                        time: e.time || '',
                        title: e.title || e.name,
                        venue: e.venue || '',
                    }));
                } catch { this.events = []; }
            },
            checkBirthday() {
                const bd = this.$store.citizenSession.account?.birthdate;
                if (!bd) return;
                const b = new Date(bd), t = new Date();
                this.isBirthdayToday = b.getUTCMonth() === t.getMonth() && b.getUTCDate() === t.getDate();
                if (this.isBirthdayToday && !sessionStorage.getItem('esperanza_birthday_seen')) {
                    setTimeout(() => {
                        this.$dispatch('show-birthday-popup');
                        sessionStorage.setItem('esperanza_birthday_seen', '1');
                    }, 1400);
                }
            },
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
            <x-ui.skeleton class="h-28 w-full rounded-2xl mb-4" />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @for ($i = 0; $i < 4; $i++)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 space-y-3">
                        <x-ui.skeleton class="w-10 h-10 rounded-xl" />
                        <x-ui.skeleton class="h-6 w-16" />
                        <x-ui.skeleton class="h-3 w-24" />
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
            <div class="relative rounded-2xl overflow-hidden mb-4 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-700 shadow-card">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,theme(colors.white),transparent_55%)] pointer-events-none"></div>

                <div class="relative px-5 py-6 sm:px-7 sm:py-7 flex flex-col lg:flex-row lg:items-center gap-5">

                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="relative shrink-0">
                            <div
                                class="w-14 h-14 text-lg rounded-full bg-white/15 backdrop-blur border-2 border-white/40 text-white flex items-center justify-center font-semibold shadow-lg"
                                x-text="$store.citizenSession.initials()"
                            ></div>

                            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-navy-900 flex items-center justify-center">
                                <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                            </span>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[11px] font-medium text-white/50 uppercase tracking-wide">
                                {{ now()->format('l, F j') }}
                            </p>

                            <h2
                                class="text-xl font-semibold text-white truncate"
                                x-text="($store.lang.current === 'en' ? 'Good day, ' : 'Magandang araw, ') + ($store.citizenSession.account?.firstName ?? 'Guest') + ' 👋'"
                            ></h2>

                            <p class="text-sm text-white/60 mt-0.5">
                                Brgy.
                                <span x-text="$store.citizenSession.account?.barangay ?? '—'"></span>
                                ·
                                <x-ui.t fil="narito ang buod ng iyong account." en="here's your account overview." />
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <x-ui.button
                            :href="route('citizen.assistance-requests')"
                            variant="ghost"
                            size="sm"
                            icon="hand-heart"
                            class="!text-white !bg-white/10 hover:!bg-white/20 !border !border-white/20"
                        >
                            Tulong
                        </x-ui.button>

                        <x-ui.button
                            :href="route('citizen.document-requests')"
                            variant="secondary"
                            size="sm"
                            icon="file-plus"
                            class="!border-0"
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
                    class="relative flex items-center gap-3 px-5 sm:px-7 py-3 border-t border-white/10"
                >
                    <div class="relative w-8 h-8 shrink-0">
                        <div class="absolute inset-0 rounded-full bg-white/10 flex items-center justify-center text-white/40">
                            <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
                        </div>

                        <img
                            :src="$store.barangay.mine?.seal ? '{{ rtrim(asset(''), '/') }}/' + $store.barangay.mine.seal : ''"
                            x-show="$store.barangay.mine?.seal"
                            onerror="this.style.display='none'"
                            alt=""
                            class="relative w-8 h-8 rounded-full object-cover ring-1 ring-white/30"
                        >
                    </div>

                    <p class="min-w-0 flex-1 text-xs text-white/60 truncate">
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
                        class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/70 hover:text-white transition-colors shrink-0"
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-4">

                <x-ui.stat-card
                    :href="route('citizen.document-requests')"
                    label="Dokyu"
                    valueBind="kpi.dokyu"
                    icon="file-text"
                    color="brand"
                    sublabelBind="kpi.dokyuReady + ($store.lang.current === 'en' ? ' ready for release' : ' handa nang kunin')"
                    :delay="0"
                />

                <x-ui.stat-card
                    :href="route('citizen.assistance-requests')"
                    label="Tulong"
                    valueBind="kpi.tulong"
                    icon="hand-heart"
                    color="purple"
                    sublabelBind="kpi.tulongApproved + ($store.lang.current === 'en' ? ' approved this month' : ' aprubado ngayong buwan')"
                    :delay="40"
                />

                <x-ui.stat-card
                    :href="route('citizen.notifications')"
                    label="Hindi Nabasang Abiso"
                    labelEn="Unread Notifications"
                    valueBind="$store.citizenNotifications.unreadCount"
                    icon="bell"
                    color="orange"
                    :delay="80"
                />

                <x-ui.stat-card
                    :href="route('citizen.profile')"
                    label="Pagkakumpleto ng Profile"
                    labelEn="Profile Completeness"
                    valueBind="($store.citizenSession.account?.profileCompleteness ?? 0) + '%'"
                    icon="user-round-check"
                    color="green"
                    sublabel="Magdagdag ng valid ID para matapos"
                    sublabelEn="Add a valid ID to finish"
                    :delay="120"
                />

            </div>

            <!-- Middle row: my requests + announcements -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

                <x-ui.card padded="false" class="lg:col-span-2">

                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">
                            <x-ui.t fil="Mga Kamakailang Kahilingan" en="My Recent Requests" />
                        </h3>

                        <a
                            href="{{ route('citizen.document-requests') }}"
                            class="text-xs font-medium text-brand-600 hover:underline"
                        >
                            <x-ui.t fil="Tingnan Lahat" en="View All" />
                        </a>
                    </div>

                    <div class="divide-y divide-slate-50">

                        <template x-for="req in requests" :key="req.ref">
                            <a
                                :href="(req.type === 'tulong' ? '{{ route('citizen.assistance-requests') }}' : '{{ route('citizen.document-requests') }}') + '?ref=' + encodeURIComponent(req.ref)"
                                class="flex items-center justify-between gap-4 px-4 py-2.5 hover:bg-slate-50/70 transition-colors"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="req.service"></p>
                                    <p class="text-xs text-slate-500 mt-0.5 font-mono">
                                        <span x-text="req.ref"></span> ·
                                        <x-ui.t fil="Isinumite" en="Submitted" />
                                        <span x-text="req.submitted ? new Date(req.submitted).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''"></span>
                                    </p>
                                </div>
                                <x-ui.badge status-bind="req.status" class="shrink-0" />
                            </a>
                        </template>

                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!requests.length">
                            <x-ui.t fil="Wala ka pang kahilingan." en="You have no requests yet." />
                        </p>

                    </div>

                </x-ui.card>

                <x-ui.card padded="false">

                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900">
                            Balita
                        </h3>

                        <a
                            href="{{ route('citizen.announcements') }}"
                            class="text-xs font-medium text-brand-600 hover:underline"
                        >
                            <x-ui.t fil="Tingnan Lahat" en="View All" />
                        </a>
                    </div>

                    <div class="divide-y divide-slate-50">

                        <template x-for="post in balitaPreview" :key="post.id">

                            <div class="px-4 py-2.5 hover:bg-slate-50/70 transition-colors">

                                <a
                                    :href="'{{ route('citizen.announcements') }}'"
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
                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold shrink-0 text-[10px]"
                                        x-text="post.initials"
                                    ></div>

                                    <div class="min-w-0 flex-1">

                                        <p class="text-xs font-semibold text-navy-900 flex items-center gap-1 truncate">

                                            <span x-text="post.author"></span>

                                            <i
                                                data-lucide="badge-check"
                                                class="w-3 h-3 text-brand-500 shrink-0"
                                                x-show="post.official"
                                            ></i>

                                        </p>

                                        <p
                                            class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-snug"
                                            x-text="post.body"
                                        ></p>

                                        <p
                                            class="text-[10.5px] text-slate-500 mt-1"
                                            x-text="post.time"
                                        ></p>

                                    </div>

                                </a>

                                <div class="flex items-center gap-3 mt-1.5 ml-[42px]">

                                    <button
                                        type="button"
                                        @click="toggleBalitaLike(post)"
                                        class="flex items-center gap-1 text-[10.5px] font-medium transition-colors"
                                        :class="post.liked ? 'text-rose-600' : 'text-slate-500 hover:text-slate-600'"
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
                                        :href="'{{ route('citizen.announcements') }}'"
                                        class="flex items-center gap-1 text-[10.5px] font-medium text-slate-500 hover:text-slate-600 transition-colors"
                                        x-show="post.commentCount > 0"
                                    >
                                        <i data-lucide="message-circle" class="w-3 h-3"></i>
                                        <span x-text="post.commentCount"></span>
                                    </a>

                                </div>

                            </div>

                        </template>

                        <p class="px-4 py-6 text-center text-xs text-slate-500" x-show="!balitaPreview.length">
                            <x-ui.t fil="Wala pang balita." en="No announcements yet." />
                        </p>

                    </div>

                </x-ui.card>

            </div>

            <!-- Upcoming events -->
            <x-ui.card padded="false">

                <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                    <h3 class="text-sm font-semibold text-navy-900">
                        <x-ui.t fil="Mga Paparating na Kaganapan" en="Upcoming Events" />
                    </h3>

                    <a
                        href="{{ route('citizen.events') }}"
                        class="text-xs font-medium text-brand-600 hover:underline"
                    >
                        <x-ui.t fil="Tingnan Lahat" en="View All" />
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 px-4 pb-4">

                    <template x-for="(event, i) in events" :key="i">
                        <a
                            href="{{ route('citizen.events') }}"
                            class="block rounded-xl border border-slate-100 p-3.5 hover:border-brand-200 hover:shadow-card hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard"
                        >
                            <div class="flex items-center gap-2 text-xs font-medium text-brand-600 mb-1.5">
                                <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
                                <span x-text="event.date + ' · ' + event.time"></span>
                            </div>
                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="event.title"></p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                <span x-text="event.venue"></span>
                            </p>
                        </a>
                    </template>

                    <p class="col-span-3 py-6 text-center text-xs text-slate-500" x-show="!events.length">
                        <x-ui.t fil="Walang paparating na kaganapan." en="No upcoming events." />
                    </p>

                </div>

            </x-ui.card>

        </div>
    </div>

</x-layouts.citizen>
