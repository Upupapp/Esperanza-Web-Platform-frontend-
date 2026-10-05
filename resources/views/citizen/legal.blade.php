@php
    // The text the site was built with, shown until the published version loads.
    $content = config('esperanza_site_content');
    $docs = [
        'privacy' => collect($content['privacy_policy'])->only(['title', 'effective_date', 'last_updated', 'intro', 'sections'])->all(),
        'terms' => collect($content['terms_conditions'])->only(['title', 'effective_date', 'last_updated', 'intro', 'sections'])->all(),
    ];
    $tab = $tab ?? 'privacy';
@endphp

<x-layouts.citizen title="Legal" subtitle="Privacy Policy and Terms &amp; Conditions." active="settings">
    {{-- The Privacy Policy and Terms staff publish in Settings, Site Content.
         This page printed the build's copy, so a published change never
         reached residents on the website (the app already read it). --}}
    <div
        x-data="{
            tab: '{{ $tab }}',
            docs: @js($docs),
            async load() {
                await Promise.all(['privacy', 'terms'].map(async (key) => {
                    try {
                        const res = await $api.get('/site-content/' + key);
                        const d = res?.data;
                        if (d && Array.isArray(d.sections) && d.sections.length) {
                            this.docs[key] = { title: d.title || this.docs[key].title, effective_date: d.effective_date, last_updated: d.last_updated || null, intro: d.intro || '', sections: d.sections };
                        }
                    } catch { /* keep the copy the page was built with */ }
                }));
                this.$nextTick(() => window.renderIcons?.());
            },
            date(iso) {
                return iso ? new Date(iso + 'T00:00:00').toLocaleDateString($store.lang.current === 'en' ? 'en-US' : 'fil-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
            },
        }"
        class="animate-fade-up max-w-3xl"
        x-init="load(); $nextTick(() => window.renderIcons?.())"
    >

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-5">
            <button @click="tab = 'privacy'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'privacy' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i><x-ui.t fil="Patakaran sa Privacy" en="Privacy Policy" />
            </button>
            <button @click="tab = 'terms'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'terms' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i><x-ui.t fil="Mga Tuntunin" en="Terms &amp; Conditions" />
            </button>
        </div>

        <template x-for="key in ['privacy', 'terms']" :key="'doc-' + key">
            <div x-show="tab === key">
                <x-ui.card>
                    <div class="flex items-start gap-3 mb-1">
                        <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="key === 'privacy' ? 'bg-brand-50 text-brand-600' : 'bg-purple-50 text-purple-600'">
                            <i :data-lucide="key === 'privacy' ? 'shield-check' : 'file-text'" class="w-5 h-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-navy-900" x-text="docs[key].title"></h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <x-ui.t fil="Ipinatutupad simula" en="Effective" /> <span x-text="date(docs[key].effective_date)"></span>
                                <span x-show="docs[key].last_updated">&middot; <x-ui.t fil="Huling na-update noong" en="Last updated" /> <span x-text="date(docs[key].last_updated)"></span></span>
                            </p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 leading-relaxed mt-4 pb-5 border-b border-slate-100" x-text="docs[key].intro"></p>

                    <div class="space-y-5 mt-5">
                        <template x-for="(section, i) in docs[key].sections" :key="key + '-' + i">
                            <div>
                                <h3 class="text-sm font-semibold text-navy-900 mb-1.5" x-text="section.heading"></h3>
                                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line" x-text="section.body"></p>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
            </div>
        </template>

    </div>
</x-layouts.citizen>
