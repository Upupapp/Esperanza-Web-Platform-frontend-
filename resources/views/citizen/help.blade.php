@php
    $tab = $tab ?? 'faqs';
@endphp

<x-layouts.citizen title="Help & Support" subtitle="FAQs, tutorials, and support from the ICT Office." active="help">
    <div
        x-data="{
            tab: '{{ $tab }}',
            open: false,
            loading: true,

            faqs: [],
            faqsFailed: false,
            faqSearch: '',
            faqCategory: 'all',
            // Each item may carry q_fil / a_fil; English where it does not
            // (live test 2026-10-07, UX-6: the questions were English only).
            faqText(f, key) {
                return this.$store.lang.current !== 'en' && f[key + '_fil'] ? f[key + '_fil'] : f[key];
            },
            categoryNames: { Documents: 'Dokumento', Assistance: 'Tulong', Account: 'Account', Payments: 'Bayad', Technical: 'Teknikal' },
            faqCategoryLabel(c) {
                if (c === 'all') return this.$store.lang.current === 'en' ? 'All' : 'Lahat';
                return this.$store.lang.current === 'en' ? c : (this.categoryNames[c] || c);
            },
            // The categories staff publish in Settings, not a fixed list (scope round 2026-10-15).
            get faqCategories() { return ['all', ...new Set(this.faqs.map((f) => f.category).filter(Boolean))]; },
            get filteredFaqs() {
                return this.faqs.map((f, i) => ({ ...f, i })).filter(f =>
                    (this.faqCategory === 'all' || f.category === this.faqCategory) &&
                    [f.q, f.a, f.q_fil, f.a_fil].join(' ').toLowerCase().includes(this.faqSearch.toLowerCase())
                );
            },

            tutorials: [],
            tutorialSearch: '',
            tutorialCategory: 'all',
            watched: [],
            activeTutorial: null,
            get tutorialCategories() { return ['all', ...new Set(this.tutorials.map((t) => t.category).filter(Boolean))]; },
            get filteredTutorials() {
                return this.tutorials.map((t, i) => ({ ...t, i })).filter(t =>
                    (this.tutorialCategory === 'all' || t.category === this.tutorialCategory) &&
                    [t.title, t.description].join(' ').toLowerCase().includes(this.tutorialSearch.toLowerCase())
                );
            },
            watchTutorial(t) { this.activeTutorial = t; this.open = true; },
            // Only tutorials with something behind them are shown: the cards had
            // running times and no video (live test 2026-10-07, PR-8). Published
            // tutorials carry a link (url) and a write-up since 2026-10-15.
            get hasTutorials() { return this.tutorials.length > 0; },
            markWatched(i) { if (!this.watched.includes(i)) this.watched.push(i); },

            channels: [],
            officeHours: '', officeHoursFil: '',

            tickets: [],
            ticketsLoading: true,
            ticketsFailed: false,

            async init() {
                this.loading = true;
                // Each part on its own: a list not published yet must not take the
                // support channels and the tickets down with it (2026-10-15).
                const read = (p) => p.catch(() => null);
                const [, tutorials, support] = await Promise.all([
                    this.loadFaqs(),
                    read($api.get('/site-content/tutorials')),
                    read($api.get('/site-content/support')),
                    this.loadTickets(),
                ]);
                this.tutorials = (tutorials?.data?.sections || [])
                    .map((t) => ({ ...t, link: t.url || t.video_url || '', views: t.views ?? null }))
                    .filter((t) => t.title && (t.link || t.description))
                    .map((t) => ({ ...t, playable: !!t.video_url || /[.](mp4|webm|ogg|m4v)([?#]|$)/i.test(t.link) }));
                if (this.tab === 'tutorials' && !this.tutorials.length) this.tab = 'faqs';
                this.channels = support?.data?.channels || [];
                this.officeHours = support?.data?.office_hours || '';
                this.officeHoursFil = support?.data?.office_hours_fil || '';
                this.loading = false;
                this.$nextTick(() => window.renderIcons?.());
            },
            // Not published yet (404) reads as an empty list; anything else is a failure to retry.
            async loadFaqs() {
                this.faqsFailed = false;
                try {
                    const res = await $api.get('/site-content/faqs');
                    this.faqs = (res.data?.sections || []).filter((f) => f && f.q);
                } catch (err) {
                    this.faqs = [];
                    this.faqsFailed = err?.status !== 404;
                }
            },
            async loadTickets() {
                this.ticketsLoading = true;
                this.ticketsFailed = false;
                try {
                    const res = await $api.getAll('/citizen/support/tickets');
                    this.tickets = res.data || [];
                } catch {
                    this.ticketsFailed = true;
                } finally {
                    this.ticketsLoading = false;
                    this.$nextTick(() => window.renderIcons?.());
                }
            },
        }"
        class="animate-fade-up"
    >

        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-1.5 surface-pressed-sm rounded-2xl p-1 w-fit">
                <button @click="tab = 'faqs'" class="tap px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="tab === 'faqs' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="circle-question-mark" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Tanong" en="FAQs" /></button>
                <button x-show="hasTutorials" x-cloak @click="tab = 'tutorials'" class="tap px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="tab === 'tutorials' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"><i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Gabay" en="Tutorials" /></button>
                <button @click="tab = 'support'" class="tap px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="tab === 'support' ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'">
                    <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i><x-ui.t fil="Suporta" en="Support" />
                    <span class="text-[11px] font-semibold rounded-full px-1.5 py-0.5" :class="tab === 'support' ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-ink-muted'" x-text="tickets.length"></span>
                </button>
            </div>
        </div>

        <!-- FAQs -->
        <div x-show="tab === 'faqs'" class="max-w-3xl mx-auto">
            <div class="surface-glass rounded-[26px] p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <div class="relative flex-1 min-w-[180px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-faint absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Faq search"
                            type="text" x-model="faqSearch"
                            x-bind:placeholder="$store.lang.current === 'en' ? 'Search FAQs...' : 'Maghanap ng tanong...'"
                            class="w-full rounded-2xl border-0 surface-pressed py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="c in faqCategories" :key="c">
                        <button
                            @click="faqCategory = c"
                            class="tap px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap"
                            :class="faqCategory === c ? 'clay-blue' : 'surface-pressed text-ink-muted hover:bg-slate-100'"
                            x-text="faqCategoryLabel(c)"
                        ></button>
                    </template>
                    <span class="text-[11px] text-ink-muted ml-auto" x-text="filteredFaqs.length + ' ' + ($store.lang.current === 'en' ? 'results' : 'resulta')"></span>
                </div>
            </div>

            <div x-show="loading" class="space-y-2.5" aria-busy="true">
                <x-ui.skeleton class="h-14 rounded-[26px]" />
                <x-ui.skeleton class="h-14 rounded-[26px] opacity-70" />
            </div>

            <div x-show="!loading && faqsFailed" x-cloak class="surface-glass rounded-[26px]">
                <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'Could not load the FAQs.' : 'Hindi ma-load ang mga tanong.'" retryExpr="loadFaqs()" class="!py-10" />
            </div>

            <div class="space-y-2.5" x-show="!loading && !faqsFailed" x-data="{ activeIndex: 0 }">
                <template x-for="faq in filteredFaqs" :key="faq.i">
                    <div class="surface-glass rounded-[26px] overflow-hidden">
                        <button @click="activeIndex = activeIndex === faq.i ? null : faq.i" :aria-expanded="activeIndex === faq.i" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                            <span class="text-sm font-medium text-slate-700" x-text="faqText(faq, 'q')"></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-ink-muted shrink-0 transition-transform duration-[160ms] ease-standard" :class="activeIndex === faq.i && 'rotate-180'"></i>
                        </button>
                        <div
                            x-show="activeIndex === faq.i"
                            x-cloak
                            x-transition:enter="transition duration-[200ms] ease-emphasized"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="px-5 pb-4 -mt-1"
                        >
                            <p class="text-sm text-ink-muted leading-relaxed whitespace-pre-line" x-text="faqText(faq, 'a')"></p>
                            {{-- Was this helpful? Posted (POST /citizen/help/feedback, keyed by the
                                 question) and remembered on this device (scope round 2026-10-15). --}}
                            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-50" x-data="helpFeedback('faqs', faq.q)">
                                <span class="text-[11px] text-ink-muted"><x-ui.t fil="Nakatulong ba ito?" en="Was this helpful?" /></span>
                                <button type="button" @click="send(true)" :disabled="sending" :aria-pressed="vote === true"
                                    class="tap inline-flex items-center gap-1 rounded-xl px-2 py-1 text-[11px] font-medium transition-colors disabled:opacity-60"
                                    :class="vote === true ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 [&_svg]:fill-current' : 'text-ink-muted hover:bg-white/60 hover:text-ink'">
                                    <i data-lucide="thumbs-up" class="w-3.5 h-3.5"></i><x-ui.t fil="Oo" en="Yes" />
                                </button>
                                <button type="button" @click="send(false)" :disabled="sending" :aria-pressed="vote === false"
                                    class="tap inline-flex items-center gap-1 rounded-xl px-2 py-1 text-[11px] font-medium transition-colors disabled:opacity-60"
                                    :class="vote === false ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 [&_svg]:fill-current' : 'text-ink-muted hover:bg-white/60 hover:text-ink'">
                                    <i data-lucide="thumbs-down" class="w-3.5 h-3.5"></i><x-ui.t fil="Hindi" en="No" />
                                </button>
                                <span x-show="vote !== null" x-cloak class="text-[11px] text-ink-muted ml-1"><x-ui.t fil="Salamat sa sagot." en="Thanks for answering." /></span>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-brand-600/10" x-show="filteredFaqs.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600" x-show="faqs.length"><x-ui.t fil="Walang tanong na tumutugma." en="No FAQs match your search." /></p>
                    <p class="text-sm font-medium text-slate-600" x-show="!faqs.length"><x-ui.t fil="Wala pang nailathalang mga tanong. Magsumite ng support ticket kung may kailangan ka." en="No FAQs are published yet. Submit a support ticket if you need help." /></p>
                </div>
            </div>
        </div>

        <!-- Tutorials -->
        <div x-show="tab === 'tutorials'" x-cloak>
            <div class="surface-glass rounded-[26px] p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <div class="relative flex-1 min-w-[180px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-ink-faint absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input aria-label="Tutorial search"
                            type="text" x-model="tutorialSearch"
                            x-bind:placeholder="$store.lang.current === 'en' ? 'Search tutorials...' : 'Maghanap ng gabay...'"
                            class="w-full rounded-2xl border-0 surface-pressed py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="c in tutorialCategories" :key="c">
                        <button
                            @click="tutorialCategory = c"
                            class="tap px-3 py-1.5 text-[11px] font-medium rounded-xl transition-colors whitespace-nowrap"
                            :class="tutorialCategory === c ? 'clay-blue' : 'surface-pressed text-ink-muted hover:bg-slate-100'"
                            x-text="faqCategoryLabel(c)"
                        ></button>
                    </template>
                    <span class="text-[11px] text-ink-muted ml-auto" x-text="filteredTutorials.length + ' ' + ($store.lang.current === 'en' ? 'results' : 'resulta')"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="tut in filteredTutorials" :key="tut.i">
                    <div @click="watchTutorial(tut)" class="group surface-glass rounded-[26px] hover:shadow-card-hover transition-all duration-[220ms] ease-standard overflow-hidden cursor-pointer"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="watchTutorial(tut)"
        @keydown.space.prevent="watchTutorial(tut)">
                        <div class="relative h-32 bg-gradient-to-br from-navy-900 to-brand-800 flex items-center justify-center">
                            <div class="w-11 h-11 rounded-full bg-white/15 backdrop-blur flex items-center justify-center group-hover:scale-110 group-hover:bg-white/25 transition-all duration-[220ms] ease-standard">
                                <span x-show="tut.link"><i data-lucide="play" class="w-5 h-5 text-white ml-0.5"></i></span>
                                <span x-show="!tut.link"><i data-lucide="book-open" class="w-5 h-5 text-white"></i></span>
                            </div>
                            <span class="absolute bottom-2.5 right-2.5 text-[11px] font-medium text-white bg-navy-950/70 px-2 py-0.5 rounded-md" x-show="tut.duration" x-text="tut.duration"></span>
                            <span class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center" x-show="watched.includes(tut.i)" x-cloak>
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </div>
                        <div class="p-4">
                            <span x-show="tut.category" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full mb-2">
                                <i :data-lucide="tut.icon || 'graduation-cap'" class="w-3 h-3"></i><span x-text="tut.category"></span>
                            </span>
                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="tut.title"></p>
                            <p x-show="tut.description" class="text-xs text-ink-muted mt-1 line-clamp-2" x-text="tut.description"></p>
                            <p x-show="tut.views !== null" class="text-[11px] text-ink-muted mt-1.5 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i><span x-text="tut.views"></span> <x-ui.t fil="beses napanood" en="views" /></p>
                        </div>
                    </div>
                </template>

                <div class="sm:col-span-2 xl:col-span-3 flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-brand-600/10" x-show="filteredTutorials.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-ink-muted flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang gabay na tumutugma." en="No tutorials match your search." /></p>
                </div>
            </div>
        </div>

        <!-- Support: a new ticket, my tickets, and each ticket's conversation
             (supportDesk in resources/js/scope-citizen.js, 2026-10-15). -->
        <div x-show="tab === 'support'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-5" x-data="supportDesk()">
            <div class="lg:col-span-2 space-y-5 min-w-0">
                {{-- A disaster or an accident is not a support ticket: it goes to
                     the MDRRMO as an incident report (2026-10-17). --}}
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3" data-testid="help-incident-link">
                    <p class="text-xs text-rose-900 flex-1"><span class="font-bold"><x-ui.t fil="Baha, sunog, pagguho o aksidente?" en="Flooding, fire, a landslide or an accident?" /></span> <x-ui.t fil="Huwag itong isumite bilang ticket: mag-ulat ng insidente para agad itong makita ng MDRRMO." en="Don't file it as a ticket: report an incident so the MDRRMO sees it at once." /></p>
                    <a href="{{ route('citizen.emergency') }}?report=1" class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-rose-600 text-white px-3.5 py-2 text-xs font-semibold hover:bg-rose-700"><i data-lucide="flag" class="w-3.5 h-3.5"></i><x-ui.t fil="Mag-ulat ng insidente" en="Report an incident" /></a>
                </div>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink mb-4"><x-ui.t fil="Magsumite ng Support Ticket" en="Submit a Support Ticket" /></h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="ticket-category" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kategorya" en="Category" /></label>
                                <select id="ticket-category" x-model="form.category" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <template x-for="c in categories" :key="c">
                                        <option :value="c" :selected="c === form.category" x-text="categoryLabel(c)"></option>
                                    </template>
                                </select>
                            </div>
                            <x-ui.input name="subject" label="Subject" labelFil="Paksa" maxlength="160" x-model="form.subject" x-bind:placeholder="$store.lang.current === 'en' ? 'Brief summary of your concern' : 'Maikling buod ng iyong concern'" />
                        </div>
                        <div>
                            <label for="ticket-request" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kaugnay na kahilingan (opsyonal)" en="Related request (optional)" /></label>
                            <select id="ticket-request" x-model="form.requestRef" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                <option value="" x-text="t('None', 'Wala')"></option>
                                <template x-for="r in requests" :key="r.ref">
                                    <option :value="r.ref" :selected="r.ref === form.requestRef" x-text="requestLabel(r)"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-ink-muted mt-1"><x-ui.t fil="Mapupunta ang ticket sa opisinang humahawak ng kahilingang ito." en="The ticket goes to the office handling that request." /></p>
                        </div>
                        <div>
                            <label for="ticket-message" class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Mensahe" en="Message" /></label>
                            <textarea id="ticket-message" rows="4" maxlength="4000" x-model="form.message" :placeholder="$store.lang.current === 'en' ? 'Describe your issue in detail' : 'Ilarawan nang detalyado ang iyong problema'" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs text-slate-600 flex-1"><x-ui.t fil="Ikabit ang Screenshot (opsyonal)" en="Attach Screenshot (optional)" /></p>
                                <button
                                    type="button" x-show="!file"
                                    @click="attachOpen = !attachOpen"
                                    class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                <div class="flex items-center gap-1.5 shrink-0 min-w-0" x-show="file" x-cloak>
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium min-w-0">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5 shrink-0"></i><span class="truncate max-w-[160px]" x-text="file ? file.name : ''"></span>
                                    </span>
                                    <button type="button" :aria-label="t('Remove the file', 'Alisin ang file')" @click="file = null" class="text-ink-faint hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                            <div x-show="attachOpen" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-brand-600/10">
                                <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-brand-600 hover:underline cursor-pointer">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i><x-ui.t fil="Pumili ng larawan o PDF" en="Choose an image or PDF" />
                                    <input type="file" class="sr-only" accept="image/*,application/pdf" @change="pickFile($event.target.files[0]); $event.target.value = ''">
                                </label>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submit()"
                            :disabled="submitting || !form.subject.trim() || !form.message.trim()"
                            class="btn-shine inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-brand-500 to-brand-600 text-white px-5 py-2.5 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-[160ms] ease-standard active:scale-[0.98] disabled:opacity-50"
                        >
                            <span x-show="submitting" x-cloak><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i></span>
                            <span x-text="$store.lang.current === 'en' ? (submitting ? 'Submitting…' : 'Submit ticket') : (submitting ? 'Sinusumite…' : 'Isumite ang ticket')"></span>
                        </button>
                    </div>
                </x-ui.card>

                <x-ui.card :padded="false">
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-ink"><x-ui.t fil="Aking mga Support Ticket" en="My Support Tickets" /></h3>
                        <span class="text-[11px] text-ink-muted" x-text="tickets.length + ' ' + ($store.lang.current === 'en' ? 'total' : 'kabuuan')"></span>
                    </div>
                    <div x-show="ticketsLoading" class="px-4 pb-4 space-y-2" aria-busy="true">
                        <x-ui.skeleton class="h-12 rounded-xl" />
                        <x-ui.skeleton class="h-12 rounded-xl opacity-70" />
                    </div>
                    <div x-show="!ticketsLoading && ticketsFailed" x-cloak>
                        <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="$store.lang.current === 'en' ? 'Could not load your tickets.' : 'Hindi ma-load ang iyong mga ticket.'" retryExpr="loadTickets()" class="!py-8" />
                    </div>
                    <ul x-show="!ticketsLoading && !ticketsFailed" class="divide-y divide-brand-600/[.08]">
                        <template x-for="tk in tickets" :key="tk.ref">
                            <li>
                                <button type="button" @click="openThread(tk)" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-white/60 transition-colors">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-slate-700 truncate" x-text="tk.subject"></span>
                                        <span class="block text-xs text-ink-muted mt-0.5"><span class="font-mono" x-text="tk.ref"></span> · <span x-text="fmtDateTime(tk.last_activity_at || tk.created_at)"></span></span>
                                        <span class="block text-[11px] text-ink-muted mt-0.5 truncate" x-text="[categoryLabel(tk.category), tk.request_ref, tk.messages_count ? tk.messages_count + ' ' + t(tk.messages_count === 1 ? 'reply' : 'replies', 'tugon') : ''].filter(Boolean).join(' · ')"></span>
                                    </span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="tone(tk.status)[0]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="tone(tk.status)[1]"></span><span x-text="tk.status"></span>
                                        </span>
                                        <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint"></i>
                                    </span>
                                </button>
                            </li>
                        </template>
                        <li class="flex flex-col items-center text-center py-10" x-show="tickets.length === 0">
                            <span class="w-10 h-10 rounded-full bg-slate-100 text-ink-muted flex items-center justify-center mb-2"><i data-lucide="inbox" class="w-4.5 h-4.5"></i></span>
                            <p class="text-xs text-ink-muted"><x-ui.t fil="Wala ka pang support ticket." en="You have no support tickets yet." /></p>
                        </li>
                    </ul>
                </x-ui.card>
            </div>

            <div class="space-y-3.5">
                <template x-for="ch in channels" :key="ch.key ?? ch.value">
                    <x-ui.card class="flex items-center gap-3.5">
                        <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="ch.color"><i :data-lucide="ch.icon" class="w-5 h-5"></i></span>
                        <div class="min-w-0">
                            <p class="text-xs text-ink-muted" x-text="$store.lang.current === 'en' ? ch.label : (ch.label_fil || ch.label)"></p>
                            <p class="text-sm font-medium text-ink truncate" x-text="ch.value"></p>
                        </div>
                    </x-ui.card>
                </template>

                <x-ui.card class="bg-slate-50/60">
                    <p class="text-xs text-ink-muted flex items-center gap-1.5 mb-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i><x-ui.t fil="Oras ng Opisina" en="Office Hours" /></p>
                    <p class="text-sm text-slate-700 font-medium" x-text="$store.lang.current === 'en' ? officeHours : (officeHoursFil || officeHours)"></p>
                </x-ui.card>
            </div>

            {{-- One ticket's conversation: the resident's messages and the office's replies. --}}
            <div x-data="{ get open() { return threadOpen }, set open(v) { threadOpen = v } }">
                <x-ui.modal title="Usapan sa Ticket" titleEn="Ticket Conversation" maxWidth="lg">
                    <template x-if="thread">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-mono text-ink-muted" x-text="thread.ref"></p>
                                    <p class="text-base font-bold text-ink break-words" x-text="thread.subject || '…'"></p>
                                    <p class="text-xs text-ink-muted mt-0.5" x-text="[thread.category ? categoryLabel(thread.category) : '', thread.office].filter(Boolean).join(' · ')"></p>
                                    <p x-show="thread.request_ref" class="text-xs text-ink-muted mt-0.5"><x-ui.t fil="Kahilingan:" en="Request:" /> <a :href="thread.request_ref ? (String(thread.request_ref).startsWith('TUL') ? '{{ route('citizen.assistance-requests') }}' : '{{ route('citizen.document-requests') }}') + '?ref=' + encodeURIComponent(thread.request_ref) : '#'" class="font-mono font-semibold text-brand-600 hover:underline" x-text="thread.request_ref"></a></p>
                                </div>
                                <span x-show="thread.status" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0" :class="tone(thread.status)[0]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="tone(thread.status)[1]"></span><span x-text="thread.status"></span>
                                </span>
                            </div>
                            <p x-show="thread.status" class="text-[11px] text-ink-muted mt-1.5" x-text="statusNote(thread.status)"></p>

                            <div x-show="threadLoading && !messages.length" class="space-y-2 mt-4" aria-busy="true">
                                <x-ui.skeleton class="h-16 rounded-2xl w-3/4 ml-auto" />
                                <x-ui.skeleton class="h-16 rounded-2xl w-3/4 opacity-70" />
                            </div>
                            <div x-show="!threadLoading && threadFailure" x-cloak>
                                <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="threadError" retryExpr="openThread(thread.ref)" class="!py-8" />
                            </div>

                            <ol id="ticket-thread-messages" x-show="messages.length" class="mt-4 space-y-3 max-h-[45vh] overflow-y-auto scrollbar-thin pr-1" aria-live="polite">
                                <template x-for="m in messages" :key="m.key">
                                    <li class="flex" :class="m.from === 'staff' ? 'justify-start' : 'justify-end'">
                                        <div class="max-w-[85%] rounded-2xl px-3.5 py-2.5" :class="m.from === 'staff' ? 'bg-white border border-brand-600/10 rounded-tl-md' : 'bg-brand-600 rounded-tr-md'">
                                            <p class="text-[11px] font-semibold flex items-center gap-1.5" :class="m.from === 'staff' ? 'text-brand-700' : 'text-white/85'">
                                                <span x-show="m.from === 'staff'"><i data-lucide="building-2" class="w-3 h-3"></i></span>
                                                <span x-text="m.name"></span>
                                            </p>
                                            <p class="text-sm whitespace-pre-line break-words mt-0.5" :class="m.from === 'staff' ? 'text-ink' : 'text-white'" x-text="m.body"></p>
                                            <p class="text-[10.5px] mt-1" :class="m.from === 'staff' ? 'text-ink-muted' : 'text-white/75'" x-text="fmtDateTime(m.at)"></p>
                                        </div>
                                    </li>
                                </template>
                            </ol>
                            <p x-show="thread.attachment" class="text-[11px] text-ink-muted mt-2 flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i><x-ui.t fil="Nakakabit:" en="Attached:" /> <span class="truncate" x-text="thread.attachment ? thread.attachment.name : ''"></span></p>

                            <div x-show="closed" x-cloak role="status" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                <p class="text-xs text-slate-700 flex items-start gap-2"><i data-lucide="lock" class="w-4 h-4 shrink-0"></i><x-ui.t fil="Sarado na ang ticket na ito. Gumawa ng bagong ticket kung may kailangan ka pa." en="This ticket is closed. Open a new ticket if you still need help." /></p>
                                <x-ui.button size="sm" class="mt-2.5" icon="plus" @click="startNew()"><x-ui.t fil="Gumawa ng bagong ticket" en="Open a new ticket" /></x-ui.button>
                            </div>
                            <p x-show="!closed && thread.status === 'Completed'" class="mt-3 text-[11px] text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2"><x-ui.t fil="Minarkahan ng opisina na nalutas na ito. Sumagot sa ibaba kung kailangan mo pa ng tulong, at bubuksan itong muli." en="The office marked this resolved. Reply below if you still need help, and it will reopen." /></p>

                            <div x-show="!closed && thread.subject" class="mt-4">
                                <label for="ticket-reply" class="block text-xs font-semibold text-ink-body mb-1"><x-ui.t fil="Ang iyong tugon" en="Your reply" /></label>
                                <textarea id="ticket-reply" x-model="reply" rows="3" maxlength="4000" @keydown.ctrl.enter="sendReply()" @keydown.meta.enter="sendReply()"
                                    class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none"></textarea>
                                <p x-show="replyError" role="alert" class="text-[11px] text-rose-700 mt-1" x-text="replyError"></p>
                                <div class="flex justify-end mt-2">
                                    <button type="button" @click="sendReply()" :disabled="replying || !reply.trim()" :aria-busy="replying"
                                        class="inline-flex items-center justify-center gap-2 min-h-[36px] px-3.5 py-2 rounded-2xl text-xs font-semibold clay-blue btn-shine hover:brightness-95 disabled:pointer-events-none disabled:bg-none disabled:bg-[#e4e9f4] disabled:text-ink-faint disabled:shadow-none">
                                        <span x-show="replying" x-cloak><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i></span>
                                        <span x-show="!replying"><i data-lucide="send" class="w-4 h-4"></i></span>
                                        <span x-text="replying ? t('Sending…', 'Ipinapadala…') : t('Send reply', 'Ipadala ang tugon')"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-slot:footer>
                        <x-ui.button variant="secondary" size="sm" @click="threadOpen = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>

        <!-- Watch Tutorial: the video when it is a file the page can play, the
             write-up, and the link itself (a YouTube page opens in a new tab). -->
        <x-ui.modal title="Panoorin ang Gabay" titleEn="Watch Tutorial" maxWidth="lg">
            <template x-if="activeTutorial">
                <div>
                    <template x-if="activeTutorial.playable">
                        <video :src="activeTutorial.link" controls preload="metadata" class="w-full aspect-video rounded-xl bg-navy-950 mb-4"></video>
                    </template>
                    <span x-show="activeTutorial.category" class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full mb-2">
                        <i :data-lucide="activeTutorial.icon || 'graduation-cap'" class="w-3 h-3"></i><span x-text="activeTutorial.category"></span>
                    </span>
                    <p class="text-sm font-semibold text-ink leading-snug" x-text="activeTutorial.title"></p>
                    <p x-show="activeTutorial.duration" class="text-xs text-ink-muted mt-1 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i><span x-text="activeTutorial.duration"></span></p>
                    <p x-show="activeTutorial.description" class="text-sm text-ink-body mt-3 leading-relaxed whitespace-pre-line" x-text="activeTutorial.description"></p>
                    <p x-show="activeTutorial.views !== null" class="text-xs text-ink-muted mt-1.5 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i><span x-text="activeTutorial.views"></span> <x-ui.t fil="beses napanood" en="views" /></p>
                    <a x-show="activeTutorial.link" :href="activeTutorial.link || '#'" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:underline">
                        <i data-lucide="external-link" class="w-4 h-4"></i><span x-text="activeTutorial.playable ? ($store.lang.current === 'en' ? 'Open the video in a new tab' : 'Buksan ang video sa bagong tab') : ($store.lang.current === 'en' ? 'Watch the video' : 'Panoorin ang video')"></span>
                    </a>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                <x-ui.button size="sm" @click="markWatched(activeTutorial.i); open = false" icon="check">
                    <x-ui.t fil="Markahang Napanood" en="Mark as Watched" />
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.citizen>
