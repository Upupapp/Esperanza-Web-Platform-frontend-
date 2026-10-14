{{-- The service directory (scope round 2026-10-15): every Dokyu document and
     Tulong program the LGU offers, from the public catalog (GET /services),
     with its office, fee, processing time, requirements and steps. "Start
     request" opens that page's wizard on the service (?new=KEY). Logic in
     resources/js/scope-citizen.js (citizenServices). --}}
<x-layouts.citizen title="Services" subtitle="Every document and assistance program the municipality offers." active="services">
    <div
        x-data="citizenServices(@js(['dokyu' => route('citizen.document-requests'), 'tulong' => route('citizen.assistance-requests')]))"
        class="animate-fade-up"
    >
        <x-ui.page-intro id="services" icon="layout-grid" title="Lahat ng serbisyo, nasa isang lugar" titleEn="Every service in one place">
            <x-ui.t fil="Hanapin ang kailangan mo, alamin ang mga requirement, bayarin at tagal ng pagproseso, saka simulan ang kahilingan. Ang Dokyu ay para sa mga dokumento; ang Tulong ay para sa mga programang ayuda." en="Find what you need, check its requirements, fee and processing time, then start your request. Dokyu is for documents; Tulong is for assistance programs." />
        </x-ui.page-intro>

        <div class="surface-glass rounded-[26px] p-3 mb-4">
            <div class="flex flex-col lg:flex-row lg:items-center gap-2.5">
                <div class="relative flex-1 min-w-0">
                    <i data-lucide="search" class="w-4 h-4 text-ink-faint absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="search" x-model="q"
                        :aria-label="$store.lang.current === 'en' ? 'Search services' : 'Maghanap ng serbisyo'"
                        :placeholder="$store.lang.current === 'en' ? 'Search a service, office or requirement…' : 'Maghanap ng serbisyo, opisina o requirement…'"
                        class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 pl-10 pr-4 text-sm text-ink placeholder:text-ink-faint focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"
                    >
                </div>
                <div class="flex items-center gap-1 surface-pressed-sm rounded-2xl p-1 shrink-0 self-start lg:self-auto" role="group" :aria-label="$store.lang.current === 'en' ? 'Kind of service' : 'Uri ng serbisyo'">
                    <template x-for="k in ['all', 'dokyu', 'tulong']" :key="k">
                        <button type="button" @click="kind = k" :aria-pressed="kind === k"
                            class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-colors whitespace-nowrap"
                            :class="kind === k ? 'bg-white/85 text-brand-600 shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'text-ink-muted hover:text-ink'"
                            x-text="(k === 'all' ? t('All', 'Lahat') : (k === 'dokyu' ? 'Dokyu' : 'Tulong')) + ' · ' + count(k)"></button>
                    </template>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-2.5">
                <div>
                    <label for="service-category" class="sr-only"><x-ui.t fil="Kategorya" en="Category" /></label>
                    <select id="service-category" x-model="category" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        <option value="all" x-text="t('All categories', 'Lahat ng kategorya')"></option>
                        <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                    </select>
                </div>
                <div>
                    <label for="service-office" class="sr-only"><x-ui.t fil="Opisina" en="Office" /></label>
                    <select id="service-office" x-model="office" class="w-full min-h-[44px] rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                        <option value="all" x-text="t('All offices', 'Lahat ng opisina')"></option>
                        <template x-for="o in offices" :key="o.value"><option :value="o.value" x-text="o.label"></option></template>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 mt-2.5 px-1" x-show="!loading && !failed" x-cloak>
                <p class="text-xs text-ink-muted" aria-live="polite" x-text="filtered.length + ' ' + t(filtered.length === 1 ? 'service' : 'services', 'serbisyo')"></p>
                <button type="button" x-show="filtersOn" @click="clearFilters()" class="text-xs font-semibold text-brand-600 hover:underline"><x-ui.t fil="I-clear ang mga filter" en="Clear filters" /></button>
            </div>
        </div>

        <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" aria-busy="true">
            <x-ui.skeleton class="h-48 rounded-[26px]" />
            <x-ui.skeleton class="h-48 rounded-[26px] opacity-80" />
            <x-ui.skeleton class="h-48 rounded-[26px] opacity-60 hidden md:block" />
        </div>

        <div x-show="!loading && failed" x-cloak class="surface-glass rounded-[26px]">
            <x-ui.error-state title="Hindi ma-load" titleEn="Could not load" messageExpr="failureText" retryExpr="load()" />
        </div>

        <div x-show="!loading && !failed && !filtered.length" x-cloak class="surface-glass rounded-[26px] flex flex-col items-center text-center py-14 px-6">
            <span class="w-16 h-16 rounded-[22px] surface-pressed flex items-center justify-center mb-4" aria-hidden="true"><i data-lucide="search-x" class="w-7 h-7 text-ink-muted"></i></span>
            <p class="text-sm font-bold text-ink"><x-ui.t fil="Walang serbisyong tumutugma." en="No services match your search." /></p>
            <p class="text-sm text-ink-muted mt-1"><x-ui.t fil="Subukan ang ibang salita o i-clear ang mga filter." en="Try other words or clear the filters." /></p>
        </div>

        <div x-show="!loading && !failed && filtered.length" x-cloak class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="s in filtered" :key="s.key">
                <article class="surface-glass rounded-[26px] p-5 flex flex-col">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl grid place-items-center shrink-0" :class="s.type === 'tulong' ? 'bg-rose-50 text-rose-600' : 'clay-light'">
                            <span x-show="s.type === 'tulong'"><i data-lucide="hand-heart" class="w-5 h-5"></i></span>
                            <span x-show="s.type !== 'tulong'"><i data-lucide="file-text" class="w-5 h-5"></i></span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-bold text-ink leading-snug break-words" x-text="s.name"></h2>
                            <p class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="s.type === 'tulong' ? 'bg-rose-50 text-rose-700' : 'bg-brand-50 text-brand-700'" x-text="s.type === 'tulong' ? 'Tulong' : 'Dokyu'"></span>
                                <span x-show="s.category" class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600" x-text="s.category"></span>
                            </p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2 text-xs flex-1">
                        <div class="flex items-start gap-2">
                            <dt class="shrink-0 pt-px"><span class="sr-only"><x-ui.t fil="Opisina" en="Office" /></span><i data-lucide="building-2" class="w-3.5 h-3.5 text-ink-faint"></i></dt>
                            <dd class="text-ink-body min-w-0" x-text="officeName(s)"></dd>
                        </div>
                        <div class="flex items-start gap-2">
                            <dt class="shrink-0 pt-px"><span class="sr-only"><x-ui.t fil="Bayad" en="Fee" /></span><i data-lucide="receipt" class="w-3.5 h-3.5 text-ink-faint"></i></dt>
                            <dd class="text-ink-body min-w-0" x-text="fee(s)"></dd>
                        </div>
                        <div class="flex items-start gap-2">
                            <dt class="shrink-0 pt-px"><span class="sr-only"><x-ui.t fil="Tagal ng pagproseso" en="Processing time" /></span><i data-lucide="clock" class="w-3.5 h-3.5 text-ink-faint"></i></dt>
                            <dd class="text-ink-body min-w-0" x-text="days(s)"></dd>
                        </div>
                    </dl>
                    <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t border-brand-600/10">
                        <x-ui.button variant="secondary" size="sm" icon="list-checks" @click="show(s.key)"><x-ui.t fil="Mga detalye" en="Details" /></x-ui.button>
                        <a :href="startHref(s)" class="ml-auto inline-flex items-center justify-center gap-1.5 min-h-[36px] px-3.5 py-2 rounded-2xl text-xs font-semibold clay-blue btn-shine hover:brightness-95">
                            <span x-text="startLabel(s)"></span><i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </article>
            </template>
        </div>

        {{-- One service: requirements and processing steps. --}}
        <x-ui.modal title="Detalye ng Serbisyo" titleEn="Service Details" maxWidth="lg">
            <template x-if="selected">
                <div>
                    <div class="flex items-start gap-3">
                        <span class="w-11 h-11 rounded-2xl grid place-items-center shrink-0" :class="selected.type === 'tulong' ? 'bg-rose-50 text-rose-600' : 'clay-light'">
                            <span x-show="selected.type === 'tulong'"><i data-lucide="hand-heart" class="w-5 h-5"></i></span>
                            <span x-show="selected.type !== 'tulong'"><i data-lucide="file-text" class="w-5 h-5"></i></span>
                        </span>
                        <div class="min-w-0">
                            <p class="text-base font-bold text-ink leading-snug" x-text="selected.name"></p>
                            <p class="text-xs text-ink-muted mt-0.5" x-text="[selected.type === 'tulong' ? 'Tulong' : 'Dokyu', selected.category, level(selected), selected.form_no].filter(Boolean).join(' · ')"></p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mt-5">
                        <div class="rounded-2xl surface-pressed-sm px-3.5 py-2.5">
                            <dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Opisina" en="Office" /></dt>
                            <dd class="text-sm font-semibold text-ink mt-0.5" x-text="officeName(selected)"></dd>
                        </div>
                        <div class="rounded-2xl surface-pressed-sm px-3.5 py-2.5">
                            <dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Bayad" en="Fee" /></dt>
                            <dd class="text-sm font-semibold text-ink mt-0.5" x-text="fee(selected)"></dd>
                        </div>
                        <div class="rounded-2xl surface-pressed-sm px-3.5 py-2.5">
                            <dt class="text-[10.5px] font-bold uppercase tracking-[.06em] text-ink-muted"><x-ui.t fil="Tagal ng pagproseso" en="Processing time" /></dt>
                            <dd class="text-sm font-semibold text-ink mt-0.5" x-text="days(selected)"></dd>
                        </div>
                    </dl>

                    <p x-show="selected.computedFee" class="text-xs text-ink-muted mt-2.5 flex items-start gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i><x-ui.t fil="Kinukuwenta ang eksaktong bayarin mula sa halagang ilalagay mo sa form." en="The exact fee is worked out from the amount you declare on the form." /></p>
                    <p x-show="selected.policy_blocked" class="text-xs text-amber-800 mt-2.5 flex items-start gap-1.5"><i data-lucide="triangle-alert" class="w-3.5 h-3.5 shrink-0 mt-px"></i><x-ui.t fil="Hinihintay pa ang pagpapasya ng LGU sa bayarin ng serbisyong ito; kukumpirmahin ito ng opisina." en="The LGU has not yet ruled on this service's fee; the office will confirm it." /></p>
                    <div x-show="selected.entitlement" class="rounded-2xl bg-rose-50/70 border border-rose-100 px-3.5 py-2.5 mt-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-[.06em] text-rose-700"><x-ui.t fil="Maaaring matanggap" en="What you may receive" /></p>
                        <p class="text-sm text-ink mt-0.5" x-text="selected.entitlement"></p>
                    </div>

                    <h3 class="text-sm font-bold text-ink mt-6 mb-2 flex items-center gap-2"><i data-lucide="clipboard-list" class="w-4 h-4 text-brand-600"></i><x-ui.t fil="Mga Requirement" en="Requirements" /></h3>
                    <ul class="space-y-1.5" x-show="selected.requirements.length">
                        <template x-for="(req, i) in selected.requirements" :key="i">
                            <li class="flex items-start gap-2 text-sm text-ink-body"><i data-lucide="circle-check" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i><span x-text="req"></span></li>
                        </template>
                    </ul>
                    <p x-show="!selected.requirements.length" class="text-sm text-ink-muted"><x-ui.t fil="Walang nakalistang requirement. Itanong sa opisina kung may kailangan." en="No requirements are listed. Ask the office if anything is needed." /></p>

                    <h3 class="text-sm font-bold text-ink mt-6 mb-2 flex items-center gap-2"><i data-lucide="route" class="w-4 h-4 text-brand-600"></i><x-ui.t fil="Mga Hakbang sa Pagproseso" en="Processing Steps" /></h3>
                    <ol class="relative space-y-3 pl-1" x-show="selected.steps.length">
                        <template x-for="(step, i) in selected.steps" :key="i">
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full clay-light grid place-items-center text-[11px] font-bold shrink-0" x-text="i + 1"></span>
                                <span class="text-sm text-ink-body pt-0.5" x-text="step"></span>
                            </li>
                        </template>
                    </ol>
                    <p x-show="!selected.steps.length" class="text-sm text-ink-muted"><x-ui.t fil="Walang nakalistang hakbang." en="No steps are listed." /></p>
                </div>
            </template>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
                <a x-show="selected" :href="selected ? startHref(selected) : '#'" class="inline-flex items-center justify-center gap-2 min-h-[36px] px-3.5 py-2 rounded-2xl text-xs font-semibold clay-blue btn-shine hover:brightness-95">
                    <span x-text="selected ? startLabel(selected) : ''"></span><i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.citizen>
