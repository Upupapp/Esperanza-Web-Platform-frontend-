{{-- ============================= SITE CONTENT ============================= --}}
<div
    x-data="{
        contentTab: 'privacy',
        loading: true, failed: false,
        previewOpen: false,
        expandedSection: 0,
        openIconPicker: null,
        saving: false,
        // Empty documents until the published ones load: the editor's
        // bindings read these fields at once, and threw on null.
        docs: {
            privacy: { title: '', version: null, effective_date: '', intro: '', sections: [] },
            terms: { title: '', version: null, effective_date: '', intro: '', sections: [] },
            support: { title: '', version: null, effective_date: '', intro: '', channels: [], office_hours: '', office_hours_fil: '' },
        },
        supportIconOptions: @js($supportIconOptions),
        async load() {
            this.loading = true; this.failed = false;
            try {
                const [privacy, terms, support] = await Promise.all([
                    $api.get('/admin/settings/site-content/privacy'),
                    $api.get('/admin/settings/site-content/terms'),
                    $api.get('/admin/settings/site-content/support'),
                ]);
                this.docs = { privacy: privacy.data, terms: terms.data, support: support.data };
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        get doc() { return this.contentTab === 'privacy' ? this.docs.privacy : this.docs.terms; },
        addSection() {
            this.doc.sections.push({ heading: 'New Section', body: '' });
            this.expandedSection = this.doc.sections.length - 1;
        },
        removeSection(i) {
            this.doc.sections.splice(i, 1);
            this.expandedSection = null;
        },
        moveSection(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.doc.sections.length) return;
            const tmp = this.doc.sections[i];
            this.doc.sections[i] = this.doc.sections[j];
            this.doc.sections[j] = tmp;
            if (this.expandedSection === i) this.expandedSection = j;
            else if (this.expandedSection === j) this.expandedSection = i;
        },
        async publish(docKey, doc) {
            if (this.saving) return;
            this.saving = true;
            try {
                await $api.post('/admin/settings/site-content/' + docKey, {
                    title: doc.title, effective_date: doc.effective_date, intro: doc.intro,
                    // Support Info has no sections: an empty list is still sent.
                    sections: doc.sections || [], channels: doc.channels || [],
                    office_hours: doc.office_hours, office_hours_fil: doc.office_hours_fil,
                });
                this.$dispatch('toast', { message: 'Published a new version.', variant: 'success' });
                await this.load();
            } catch (err) {
                this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not publish.', variant: 'error' });
            } finally { this.saving = false; }
        },
        addChannel() {
            this.docs.support.channels = this.docs.support.channels || [];
            this.docs.support.channels.push({ key: 'channel_' + Date.now(), icon: 'phone', label: '', label_fil: '', value: '', color: 'text-brand-600 bg-brand-50' });
        },
        removeChannel(i) { this.docs.support.channels.splice(i, 1); },
    }"
    x-show="tab === 'site-content'" x-cloak
    x-init="load()"
>
    {{-- FAQs and Tutorials joined the documents here (scope round
         2026-10-15); they load when first opened, in their own component
         (siteHelpEditor, resources/js/scope-admin-settings.js). --}}
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <div class="flex flex-wrap items-center gap-1.5 surface-pressed rounded-xl p-1 max-w-full">
            @foreach(['privacy' => ['shield-check', 'Privacy Policy'], 'terms' => ['file-text', 'Terms & Conditions'], 'support' => ['life-buoy', 'Support Info'], 'faqs' => ['circle-help', 'FAQs'], 'tutorials' => ['circle-play', 'Tutorials']] as $docTab => [$docIcon, $docLabel])
                <button type="button" @click="contentTab = '{{ $docTab }}'" class="shrink-0 whitespace-nowrap px-3.5 py-1.5 text-xs font-medium rounded-xl transition-colors flex items-center gap-1.5" :class="contentTab === '{{ $docTab }}' ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'" :aria-current="contentTab === '{{ $docTab }}' ? 'true' : null"><i data-lucide="{{ $docIcon }}" class="w-3.5 h-3.5"></i>{{ $docLabel }}</button>
            @endforeach
        </div>
        <x-ui.button x-show="(contentTab === 'privacy' || contentTab === 'terms') && !loading && !failed" variant="secondary" size="sm" icon="eye" @click="previewOpen = true">Preview as citizen</x-ui.button>
    </div>

    <div
        x-show="!can('set_site_content','edit')"
        class="mb-4 flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
    >
        <i data-lucide="lock" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
        <p class="text-xs text-ink-muted">
            <span class="font-semibold text-slate-600">View only.</span>
            Site Content is published straight to the Citizen Portal, so only the <span class="font-medium text-slate-600">Super Administrator</span> and <span class="font-medium text-slate-600">Municipal Administrator</span> roles can edit it.
        </p>
    </div>

    <div x-show="contentTab === 'privacy' || contentTab === 'terms' || contentTab === 'support'">
    <div x-show="loading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-40 opacity-80" /></div>
    <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load site content.'" retryExpr="load()" :retryable="true" /></div>

    <div x-show="!loading && !failed">

    <!-- Privacy Policy / Terms & Conditions editor -->
    <div x-show="contentTab === 'privacy' || contentTab === 'terms'">
        <x-ui.card class="mb-3.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="effective-date-2">Effective Date</label>
                    <input id="effective-date-2" type="date" x-model="doc.effective_date" :disabled="!can('set_site_content','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Version</label>
                    <p class="text-sm text-ink-muted py-2.5 px-4 rounded-xl bg-slate-50 flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="'v' + doc.version + (doc.last_updated_by ? ' · updated by ' + doc.last_updated_by : '')"></span></p>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-700 mb-1">Introduction</label>
                <p class="text-[11px] text-ink-muted mb-1.5">The opening paragraph citizens read before the numbered sections below.</p>
                <textarea aria-label="Intro" rows="3" x-model="doc.intro" :disabled="!can('set_site_content','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body leading-relaxed focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed"></textarea>
            </div>
        </x-ui.card>

        <div class="rounded-2xl border border-brand-600/10 bg-white shadow-card divide-y divide-brand-600/[.08] overflow-hidden">
            <template x-for="(section, i) in doc.sections" :key="i">
                <div>
                    <div
                        class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-white/60 transition-colors"
                        @click="expandedSection = expandedSection === i ? null : i"

        role="button"
        tabindex="0"
        @keydown.enter.prevent="expandedSection = expandedSection === i ? null : i"
        @keydown.space.prevent="expandedSection = expandedSection === i ? null : i">
                        <span class="w-6 h-6 rounded-lg bg-slate-100 text-ink-muted text-[11px] font-bold flex items-center justify-center shrink-0" x-text="i + 1"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-ink truncate" x-text="section.heading || 'Untitled section'"></p>
                            <p class="text-xs text-ink-muted truncate" x-show="expandedSection !== i" x-text="section.body || 'No content yet'"></p>
                        </div>
                        <div class="flex items-center gap-0.5 shrink-0" x-show="can('set_site_content','edit')" @click.stop>
                            <button type="button" @click="moveSection(i, -1)" :disabled="i === 0" title="Move up" class="p-1.5 rounded-xl text-ink-muted hover:text-navy-700 hover:bg-slate-100 disabled:opacity-25 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                            <button type="button" @click="moveSection(i, 1)" :disabled="i === doc.sections.length - 1" title="Move down" class="p-1.5 rounded-xl text-ink-muted hover:text-navy-700 hover:bg-slate-100 disabled:opacity-25 disabled:pointer-events-none transition-colors"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                            <button type="button" @click="removeSection(i)" title="Remove section" class="p-1.5 rounded-xl text-ink-faint hover:text-rose-600 hover:bg-rose-50 transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-ink-faint shrink-0 transition-transform duration-[160ms] ease-standard" :class="expandedSection === i && 'rotate-180'"></i>
                    </div>
                    <div
                        x-show="expandedSection === i" x-cloak
                        x-transition:enter="transition duration-[200ms] ease-emphasized"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="px-4 pb-4 bg-slate-50/40"
                    >
                        <label class="block text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-1 mt-1">Section Heading</label>
                        <input aria-label="Section Heading"
                            type="text" x-model="section.heading" placeholder="Section heading" :disabled="!can('set_site_content','edit')"
                            class="w-full bg-transparent border-0 border-b-2 border-slate-200 px-0.5 py-1.5 text-base font-bold text-ink placeholder:text-ink-faint placeholder:font-normal focus:border-brand-400 outline-none transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                        <label class="block text-[10px] font-semibold text-ink-muted uppercase tracking-wide mb-1 mt-4">Section Content</label>
                        <textarea aria-label="Section Content"
                            rows="5" x-model="section.body" placeholder="Write what citizens need to know in this section…" :disabled="!can('set_site_content','edit')"
                            class="w-full rounded-2xl py-2.5 px-3.5 text-sm text-slate-700 leading-relaxed focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard resize-none disabled:opacity-60 disabled:cursor-not-allowed border-0 surface-pressed-sm"
                        ></textarea>
                        <p class="text-[11px] text-ink-muted mt-1.5" x-text="(section.body || '').length + ' characters'"></p>
                    </div>
                </div>
            </template>
            <div class="px-4 py-8 text-center" x-show="doc.sections.length === 0">
                <p class="text-xs text-ink-muted">No sections yet.</p>
            </div>
        </div>

        <div class="flex items-center justify-between mt-4" x-show="can('set_site_content','edit')">
            <button type="button" @click="addSection()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Add section</button>
            <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="publish(contentTab, doc)" x-text="saving ? 'Publishing…' : 'Save changes'"></x-ui.button>
        </div>
    </div>

    <!-- Support Info editor -->
    <div x-show="contentTab === 'support'" x-cloak>
        <x-ui.card class="mb-3.5">
            <label class="block text-xs font-medium text-slate-700 mb-1" for="support-page-introduction">Support Page Introduction</label>
            <textarea id="support-page-introduction" rows="2" x-model="docs.support.intro" :disabled="!can('set_site_content','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed"></textarea>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="office-hours">Office Hours</label>
                    <input id="office-hours" type="text" x-model="docs.support.office_hours" :disabled="!can('set_site_content','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1" for="office-hours-filipino">Office Hours (Filipino)</label>
                    <input id="office-hours-filipino" type="text" x-model="docs.support.office_hours_fil" :disabled="!can('set_site_content','edit')" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
            </div>
        </x-ui.card>

        <div class="space-y-2.5">
            <template x-for="(ch, i) in (docs.support.channels || [])" :key="ch.key">
                <div class="rounded-2xl border border-brand-600/10 bg-white shadow-card p-3.5 flex items-center gap-3" x-data="{ iconOpen: false }">
                    <div class="relative shrink-0">
                        <button
                            type="button" @click="iconOpen = !iconOpen" :disabled="!can('set_site_content','edit')"
                            class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-ink-muted hover:border-brand-300 hover:text-brand-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:border-slate-200 disabled:hover:text-ink-muted"
                        >
                            <i :data-lucide="ch.icon" class="w-5 h-5"></i>
                        </button>
                        <div
                            x-show="iconOpen" x-cloak @click.outside="iconOpen = false"
                            x-transition:enter="transition duration-[160ms] ease-emphasized" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            class="absolute z-20 top-full left-0 mt-1.5 w-44 rounded-xl bg-white border border-slate-200 shadow-card-hover p-1.5 grid grid-cols-4 gap-1"
                        >
                            <template x-for="opt in supportIconOptions" :key="opt">
                                <button
                                    type="button" @click="ch.icon = opt; iconOpen = false; $nextTick(() => window.renderIcons?.())" :title="opt"
                                    class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                                    :class="ch.icon === opt ? 'bg-brand-50 text-brand-600 ring-1 ring-brand-200' : 'text-ink-muted hover:bg-white/60 hover:text-slate-600'"
                                >
                                    <i :data-lucide="opt" class="w-4 h-4"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                    {{-- Residents read the Filipino label first (the Help page and
                         the app show label_fil, else label), so it is edited
                         beside the English one (sweep 2026-11-18). --}}
                    <div class="flex-1 min-w-0 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-medium text-ink-muted mb-1">Label</label>
                            <input aria-label="Label" type="text" x-model="ch.label" placeholder="e.g. Call the ICT Office" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-xs text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-ink-muted mb-1">Label (Filipino)</label>
                            <input aria-label="Label (Filipino)" type="text" x-model="ch.label_fil" placeholder="e.g. Tawagan ang ICT Office" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-xs text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="block text-[10px] font-medium text-ink-muted mb-1">Value</label>
                            <input aria-label="Value" type="text" x-model="ch.value" placeholder="e.g. (056) 333-1099" :disabled="!can('set_site_content','edit')" class="w-full rounded-xl border-0 surface-pressed py-2 px-3 text-xs text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard disabled:opacity-60 disabled:cursor-not-allowed">
                        </div>
                    </div>
                    <button type="button" @click="removeChannel(i)" x-show="can('set_site_content','edit')" title="Remove channel" class="p-1.5 rounded-xl text-ink-faint hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between mt-4" x-show="can('set_site_content','edit')">
            <button type="button" @click="addChannel()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Add contact channel</button>
            <x-ui.button size="sm" icon="check" x-bind:disabled="saving" @click="publish('support', docs.support)" x-text="saving ? 'Publishing…' : 'Save changes'"></x-ui.button>
        </div>
    </div>

    <!-- Citizen preview -->
    <div x-data="{ get open() { return previewOpen }, set open(v) { previewOpen = v } }">
        <x-ui.modal :bare="true" maxWidth="lg">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-600/10 shrink-0">
                <h3 class="text-sm font-semibold text-ink" x-text="contentTab === 'privacy' ? 'Preview — Privacy Policy' : 'Preview — Terms &amp; Conditions'"></h3>
                <button aria-label="Close" @click="previewOpen = false" class="text-ink-muted hover:text-slate-600 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="p-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                <h2 class="text-base font-bold text-ink" x-text="doc.title"></h2>
                <p class="text-xs text-ink-muted mt-0.5 mb-4" x-text="'Effective ' + doc.effective_date"></p>
                <p class="text-sm text-slate-600 leading-relaxed pb-4 border-b border-brand-600/10" x-text="doc.intro"></p>
                <div class="space-y-4 mt-4">
                    <template x-for="section in doc.sections" :key="section.heading">
                        <div>
                            <h3 class="text-sm font-semibold text-ink mb-1" x-text="section.heading"></h3>
                            <p class="text-xs text-slate-600 leading-relaxed" x-text="section.body"></p>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-brand-600/10 bg-slate-50/60 shrink-0">
                <x-ui.button size="sm" @click="previewOpen = false">Close preview</x-ui.button>
            </div>
        </x-ui.modal>
    </div>
    </div>
    </div>

    @include('admin.settings.site-help', ['helpDoc' => 'faqs'])
    @include('admin.settings.site-help', ['helpDoc' => 'tutorials'])
</div>
