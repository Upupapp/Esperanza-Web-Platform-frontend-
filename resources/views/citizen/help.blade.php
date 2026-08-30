@php
    $tab = $tab ?? 'faqs';

    // TAB 04: fixtures live in resources/data/citizen/help.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/citizen/help.php');
    $faqs = $__fixtures['faqs'];

    $tutorials = $__fixtures['tutorials'];

    // Support channels and office hours are admin-managed via
    // Settings > Site Content > Support Info (config/esperanza_site_content.php).
    $supportInfo = config('esperanza_site_content.support');
    $channels = collect($supportInfo['channels'])->map(fn ($c) => [
        'icon' => $c['icon'], 'label' => $c['label'], 'labelFil' => $c['label_fil'], 'value' => $c['value'], 'color' => $c['color'],
    ])->all();

    $ticketCategories = $__fixtures['ticketCategories'];

    $tickets = $__fixtures['tickets'];

    $availableFiles = $__fixtures['availableFiles'];
@endphp

<x-layouts.citizen title="Help & Support" subtitle="FAQs, tutorials, and support from the ICT Office." active="help">
    <div
        x-data="{
            tab: '{{ $tab }}',
            open: false,

            faqs: @js($faqs),
            faqSearch: '',
            faqCategory: 'all',
            helpful: {},
            get filteredFaqs() {
                return this.faqs.map((f, i) => ({ ...f, i })).filter(f =>
                    (this.faqCategory === 'all' || f.category === this.faqCategory) &&
                    (f.q + ' ' + f.a).toLowerCase().includes(this.faqSearch.toLowerCase())
                );
            },
            markHelpful(i, val) {
                this.helpful[i] = val;
                this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Thanks for your feedback!' : 'Salamat sa iyong feedback!', variant: 'success' });
            },

            tutorials: @js($tutorials),
            tutorialSearch: '',
            tutorialCategory: 'all',
            watched: [],
            activeTutorial: null,
            get filteredTutorials() {
                return this.tutorials.map((t, i) => ({ ...t, i })).filter(t =>
                    (this.tutorialCategory === 'all' || t.category === this.tutorialCategory) &&
                    t.title.toLowerCase().includes(this.tutorialSearch.toLowerCase())
                );
            },
            watchTutorial(t) { this.activeTutorial = t; this.open = true; },
            markWatched(i) { if (!this.watched.includes(i)) this.watched.push(i); },

            tickets: @js($tickets),
            ticketCategories: @js($ticketCategories),
            ticketForm: { category: '{{ $ticketCategories[0] }}', subject: '', message: '' },
            ticketAttachment: null,
            showAttachPicker: false,
            availableFiles: @js($availableFiles),
            submitting: false,
            submitTicket() {
                if (this.submitting || !this.ticketForm.subject || !this.ticketForm.message) return;
                this.submitting = true;
                setTimeout(() => {
                    this.submitting = false;
                    this.tickets.unshift({
                        ref: 'TCK-2026-' + Math.floor(1000 + Math.random() * 9000),
                        subject: this.ticketForm.subject,
                        status: 'Submitted',
                        time: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    });
                    this.ticketForm = { category: this.ticketCategories[0], subject: '', message: '' };
                    this.ticketAttachment = null;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'Support ticket submitted — our team will respond within 24 hours.' : 'Naisumite ang support ticket — sasagutin ng aming team sa loob ng 24 oras.', variant: 'success' });
                }, 800);
            },
        }"
        x-init="$nextTick(() => window.renderIcons?.())"
        class="animate-fade-up"
    >

        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit">
                <button @click="tab = 'faqs'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'faqs' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="circle-question-mark" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Tanong" en="FAQs" /></button>
                <button @click="tab = 'tutorials'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'tutorials' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Gabay" en="Tutorials" /></button>
                <button @click="tab = 'support'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'support' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                    <i data-lucide="life-buoy" class="w-3.5 h-3.5"></i><x-ui.t fil="Suporta" en="Support" />
                    <span class="text-[9px] font-semibold bg-white/25 rounded-full px-1.5 py-0.5" :class="tab === 'support' ? 'text-white' : 'bg-slate-100 text-slate-500'" x-text="tickets.length"></span>
                </button>
            </div>
        </div>

        <!-- FAQs -->
        <div x-show="tab === 'faqs'" class="max-w-3xl">
            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <div class="relative flex-1 min-w-[180px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-300 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input
                            type="text" x-model="faqSearch"
                            x-bind:placeholder="$store.lang.current === 'en' ? 'Search FAQs...' : 'Maghanap ng tanong...'"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="c in ['all', 'Documents', 'Assistance', 'Account', 'Payments', 'Technical']" :key="c">
                        <button
                            @click="faqCategory = c"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                            :class="faqCategory === c ? 'bg-brand-600 text-white' : 'bg-slate-100/70 text-slate-500 hover:bg-slate-100'"
                            x-text="c === 'all' ? ($store.lang.current === 'en' ? 'All' : 'Lahat') : c"
                        ></button>
                    </template>
                    <span class="text-[11px] text-slate-400 ml-auto" x-text="filteredFaqs.length + ' ' + ($store.lang.current === 'en' ? 'results' : 'resulta')"></span>
                </div>
            </div>

            <div class="space-y-2.5" x-data="{ activeIndex: 0 }">
                <template x-for="faq in filteredFaqs" :key="faq.i">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                        <button @click="activeIndex = activeIndex === faq.i ? null : faq.i" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                            <span class="text-sm font-medium text-slate-700" x-text="faq.q"></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-[160ms] ease-standard" :class="activeIndex === faq.i && 'rotate-180'"></i>
                        </button>
                        <div
                            x-show="activeIndex === faq.i"
                            x-cloak
                            x-transition:enter="transition duration-[200ms] ease-emphasized"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="px-5 pb-4 -mt-1"
                        >
                            <p class="text-sm text-slate-500 leading-relaxed" x-text="faq.a"></p>
                            <div class="flex items-center gap-2.5 mt-3 pt-3 border-t border-slate-50">
                                <span class="text-[11px] text-slate-400"><x-ui.t fil="Nakatulong ba ito?" en="Was this helpful?" /></span>
                                <button
                                    @click="markHelpful(faq.i, true)"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors"
                                    :class="helpful[faq.i] === true ? 'bg-emerald-50 text-emerald-600' : 'text-slate-300 hover:bg-slate-50 hover:text-slate-500'"
                                ><i data-lucide="thumbs-up" class="w-3.5 h-3.5" :style="helpful[faq.i] === true ? 'fill: currentColor' : ''"></i></button>
                                <button
                                    @click="markHelpful(faq.i, false)"
                                    class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors"
                                    :class="helpful[faq.i] === false ? 'bg-rose-50 text-rose-600' : 'text-slate-300 hover:bg-slate-50 hover:text-slate-500'"
                                ><i data-lucide="thumbs-down" class="w-3.5 h-3.5" :style="helpful[faq.i] === false ? 'fill: currentColor' : ''"></i></button>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-slate-100" x-show="filteredFaqs.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang tanong na tumutugma." en="No FAQs match your search." /></p>
                </div>
            </div>
        </div>

        <!-- Tutorials -->
        <div x-show="tab === 'tutorials'" x-cloak>
            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-2.5">
                    <div class="relative flex-1 min-w-[180px]">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-300 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input
                            type="text" x-model="tutorialSearch"
                            x-bind:placeholder="$store.lang.current === 'en' ? 'Search tutorials...' : 'Maghanap ng gabay...'"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="c in ['all', 'Documents', 'Account', 'Assistance', 'Payments']" :key="c">
                        <button
                            @click="tutorialCategory = c"
                            class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                            :class="tutorialCategory === c ? 'bg-brand-600 text-white' : 'bg-slate-100/70 text-slate-500 hover:bg-slate-100'"
                            x-text="c === 'all' ? ($store.lang.current === 'en' ? 'All' : 'Lahat') : c"
                        ></button>
                    </template>
                    <span class="text-[11px] text-slate-400 ml-auto" x-text="filteredTutorials.length + ' ' + ($store.lang.current === 'en' ? 'results' : 'resulta')"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="tut in filteredTutorials" :key="tut.i">
                    <div @click="watchTutorial(tut)" class="group bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-[220ms] ease-standard overflow-hidden cursor-pointer"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="watchTutorial(tut)"
        @keydown.space.prevent="watchTutorial(tut)">
                        <div class="relative h-32 bg-gradient-to-br from-navy-900 to-brand-800 flex items-center justify-center">
                            <div class="w-11 h-11 rounded-full bg-white/15 backdrop-blur flex items-center justify-center group-hover:scale-110 group-hover:bg-white/25 transition-all duration-[220ms] ease-standard">
                                <i data-lucide="play" class="w-5 h-5 text-white ml-0.5"></i>
                            </div>
                            <span class="absolute bottom-2.5 right-2.5 text-[11px] font-medium text-white bg-navy-950/70 px-2 py-0.5 rounded-md" x-text="tut.duration"></span>
                            <span class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center" x-show="watched.includes(tut.i)" x-cloak>
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </div>
                        <div class="p-4">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full mb-2">
                                <i :data-lucide="tut.icon" class="w-3 h-3"></i><span x-text="tut.category"></span>
                            </span>
                            <p class="text-sm font-medium text-slate-700 leading-snug" x-text="tut.title"></p>
                            <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i><span x-text="tut.views"></span> <x-ui.t fil="beses napanood" en="views" /></p>
                        </div>
                    </div>
                </template>

                <div class="sm:col-span-2 xl:col-span-3 flex flex-col items-center text-center py-12 bg-white rounded-2xl border border-slate-100" x-show="filteredTutorials.length === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang gabay na tumutugma." en="No tutorials match your search." /></p>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div x-show="tab === 'support'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-5">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4"><x-ui.t fil="Magsumite ng Support Ticket" en="Submit a Support Ticket" /></h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Kategorya" en="Category" /></label>
                                <select x-model="ticketForm.category" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <template x-for="c in ticketCategories" :key="c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </div>
                            <x-ui.input name="subject" label="Subject" placeholder="Brief summary of your concern" x-model="ticketForm.subject" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5"><x-ui.t fil="Mensahe" en="Message" /></label>
                            <textarea rows="4" x-model="ticketForm.message" placeholder="Describe your issue in detail" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs text-slate-600 flex-1"><x-ui.t fil="Ikabit ang Screenshot (opsyonal)" en="Attach Screenshot (optional)" /></p>
                                <button
                                    type="button" x-show="!ticketAttachment"
                                    @click="showAttachPicker = !showAttachPicker"
                                    class="text-[11px] font-medium text-brand-600 hover:underline shrink-0 whitespace-nowrap"
                                ><x-ui.t fil="Ikabit ang File" en="Attach File" /></button>
                                <div class="flex items-center gap-1.5 shrink-0" x-show="ticketAttachment">
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium whitespace-nowrap">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i><span x-text="ticketAttachment"></span>
                                    </span>
                                    <button type="button" @click="ticketAttachment = null" class="text-slate-300 hover:text-rose-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                            <div x-show="showAttachPicker" x-cloak x-transition class="mt-2.5 pt-2.5 border-t border-slate-100">
                                <x-ui.file-picker
                                    files="availableFiles"
                                    onSelect="ticketAttachment = file.name; showAttachPicker = false"
                                    uploadAction="ticketAttachment = 'New screenshot.png'; showAttachPicker = false; $dispatch('toast', { message: 'File upload is a frontend preview — no data is saved.', variant: 'info' })"
                                    :columns="2"
                                    :search="false"
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitTicket()"
                            :disabled="submitting || !ticketForm.subject || !ticketForm.message"
                            class="btn-shine inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-brand-500 to-brand-600 text-white px-5 py-2.5 text-sm font-medium shadow-card hover:shadow-card-hover transition-all duration-[160ms] ease-standard active:scale-[0.98] disabled:opacity-50"
                        >
                            <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="submitting" x-cloak></i>
                            <span x-text="$store.lang.current === 'en' ? (submitting ? 'Submitting…' : 'Submit Ticket') : (submitting ? 'Sinusumite…' : 'Isumite ang Ticket')"></span>
                        </button>
                    </div>
                </x-ui.card>

                <x-ui.card padded="false">
                    <div class="flex items-center justify-between px-4 pt-3.5 pb-2">
                        <h3 class="text-sm font-semibold text-navy-900"><x-ui.t fil="Aking mga Support Ticket" en="My Support Tickets" /></h3>
                        <span class="text-[11px] text-slate-400" x-text="tickets.length + ' ' + ($store.lang.current === 'en' ? 'total' : 'kabuuan')"></span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <template x-for="t in tickets" :key="t.ref">
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="t.subject"></p>
                                    <p class="text-xs text-slate-400 mt-0.5 font-mono" x-text="t.ref + ' · ' + t.time"></p>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap shrink-0"
                                    :class="{
                                        'bg-blue-50 text-blue-700 ring-blue-200': t.status === 'Submitted',
                                        'bg-brand-50 text-brand-700 ring-brand-200': t.status === 'Processing',
                                        'bg-green-50 text-green-700 ring-green-200': t.status === 'Completed',
                                    }"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-blue-500': t.status === 'Submitted',
                                            'bg-brand-500': t.status === 'Processing',
                                            'bg-green-500': t.status === 'Completed',
                                        }"
                                    ></span>
                                    <span x-text="t.status"></span>
                                </span>
                            </div>
                        </template>
                        <div class="flex flex-col items-center text-center py-10" x-show="tickets.length === 0">
                            <span class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-2"><i data-lucide="inbox" class="w-4.5 h-4.5"></i></span>
                            <p class="text-xs text-slate-400"><x-ui.t fil="Wala ka pang support ticket." en="You have no support tickets yet." /></p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-3.5">
                @foreach($channels as $ch)
                    <x-ui.card class="flex items-center gap-3.5">
                        <span class="w-11 h-11 rounded-xl {{ $ch['color'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $ch['icon'] }}" class="w-5 h-5"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-400"><x-ui.t :fil="$ch['labelFil']" :en="$ch['label']" /></p>
                            <p class="text-sm font-medium text-navy-900 truncate">{{ $ch['value'] }}</p>
                        </div>
                    </x-ui.card>
                @endforeach

                <x-ui.card class="bg-slate-50/60">
                    <p class="text-xs text-slate-500 flex items-center gap-1.5 mb-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i><x-ui.t fil="Oras ng Opisina" en="Office Hours" /></p>
                    <p class="text-sm text-slate-700 font-medium"><x-ui.t :fil="$supportInfo['office_hours_fil']" :en="$supportInfo['office_hours']" /></p>
                </x-ui.card>
            </div>
        </div>

        <!-- Watch Tutorial -->
        <x-ui.modal title="Panoorin ang Gabay" titleEn="Watch Tutorial" maxWidth="lg">
            <template x-if="activeTutorial">
                <div>
                    <div class="relative aspect-video rounded-xl bg-gradient-to-br from-navy-900 to-brand-800 flex items-center justify-center overflow-hidden mb-4">
                        <div class="w-16 h-16 rounded-full bg-white/15 backdrop-blur flex items-center justify-center">
                            <i data-lucide="play" class="w-7 h-7 text-white ml-1"></i>
                        </div>
                        <span class="absolute bottom-3 right-3 text-xs font-medium text-white bg-navy-950/70 px-2 py-1 rounded-md" x-text="activeTutorial.duration"></span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full mb-2">
                        <i :data-lucide="activeTutorial.icon" class="w-3 h-3"></i><span x-text="activeTutorial.category"></span>
                    </span>
                    <p class="text-sm font-semibold text-navy-900 leading-snug" x-text="activeTutorial.title"></p>
                    <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i><span x-text="activeTutorial.views"></span> <x-ui.t fil="beses napanood" en="views" /></p>
                    <p class="text-xs text-slate-400 mt-3"><x-ui.t fil="Ito ay isang frontend preview — walang aktwal na video na naka-play." en="This is a frontend preview — no actual video plays here." /></p>
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
