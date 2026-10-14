@php
    // Included twice by site-content.blade.php: $helpDoc is 'faqs' or 'tutorials'.
    $isFaq = $helpDoc === 'faqs';
    $noun = $isFaq ? 'question' : 'tutorial';
    $titleField = $isFaq ? 'q' : 'title';
@endphp
{{-- ============================= FAQS / TUTORIALS =============================
     The help residents read (site content docs faqs and tutorials), edited as
     a list and published as a new version like the documents above (scope
     round 2026-10-15). Each item shows how residents rated it.
     siteHelpEditor in resources/js/scope-admin-settings.js. --}}
<div x-data="siteHelpEditor('{{ $helpDoc }}')" x-show="contentTab === '{{ $helpDoc }}'" x-cloak x-effect="tab === 'site-content' && contentTab === '{{ $helpDoc }}' && start()">
    <x-ui.card x-show="denied" x-cloak><p class="text-sm text-slate-600">Your role cannot view site content.</p></x-ui.card>
    <div x-show="loading" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-20" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
    <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not load the {{ $isFaq ? 'FAQs' : 'tutorials' }}.'" retryExpr="load()" :retryable="true" /></div>

    <div x-show="loaded && !loading && !failed" x-cloak class="space-y-3">
        <x-ui.card :padded="false" class="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="{{ $helpDoc }}-title" class="{{ $ui['label'] }}">Page title</label>
                    <input id="{{ $helpDoc }}-title" type="text" x-model="title" maxlength="255" :disabled="!canEdit" class="{{ $ui['input'] }}">
                </div>
                <div>
                    <p class="{{ $ui['label'] }}">Published</p>
                    <p class="text-sm text-ink-muted py-2.5 px-4 rounded-2xl bg-slate-50 flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5 text-ink-faint shrink-0"></i><span x-text="versionLine"></span></p>
                </div>
            </div>
            <p class="mt-3 flex items-start gap-2 text-xs text-ink-muted"><i data-lucide="thumbs-up" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span x-text="ratingSummary"></span></p>
        </x-ui.card>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[12rem]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" x-model="search" aria-label="Search {{ $noun }}s" placeholder="Search {{ $noun }}s" class="{{ $ui['search'] }}">
            </div>
            <select x-model="category" aria-label="Category" class="{{ $ui['select'] }}">
                <option value="">All categories</option>
                <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
            </select>
            <p class="text-[11px] text-ink-muted" x-text="countLine"></p>
        </div>
        <p x-show="filtering && canEdit" x-cloak class="text-[11px] text-ink-muted">Clear the search and the category to change the order.</p>
        <datalist id="{{ $helpDoc }}-categories"><template x-for="c in categories" :key="c"><option :value="c"></option></template></datalist>

        <div class="rounded-2xl border border-brand-600/10 bg-white shadow-card divide-y divide-brand-600/[.08] overflow-hidden">
            <template x-for="item in shown" :key="item.uid">
                <div :id="'{{ $helpDoc }}-item-' + item.uid">
                    <div class="flex items-start gap-2 px-3 sm:px-4 py-3">
                        <button type="button" class="flex-1 min-w-0 flex items-start gap-3 text-left rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100" @click="toggle(item)" :aria-expanded="expanded === item.uid ? 'true' : 'false'" :aria-controls="'{{ $helpDoc }}-body-' + item.uid">
                            @if($isFaq)
                                <span class="w-6 h-6 rounded-lg bg-slate-100 text-ink-muted text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5" x-text="indexOf(item) + 1"></span>
                            @else
                                {{-- Keyed on the icon, so a new choice draws a new icon. --}}
                                <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                                    <template x-for="ic in [item.icon || 'circle-play']" :key="ic"><span class="inline-flex"><i :data-lucide="ic" class="w-4 h-4"></i></span></template>
                                </span>
                            @endif
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold text-ink" :class="expanded === item.uid ? 'break-words' : 'truncate'" x-text="item.{{ $titleField }} || 'New {{ $noun }}'"></span>
                                <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-ink-muted">
                                    @if($isFaq)
                                        <span x-text="item.category || 'No category'"></span>
                                    @else
                                        <span x-text="[item.category, item.duration].filter(Boolean).join(' · ') || 'No category'"></span>
                                        <span x-show="!item.url" class="text-amber-700">No video link yet</span>
                                    @endif
                                    <span x-show="hasErrors(item)" class="font-semibold text-rose-600">Needs fixing</span>
                                    <span class="inline-flex items-center gap-1" x-show="rating(item).any">
                                        <i data-lucide="thumbs-up" class="w-3 h-3"></i><span x-text="rating(item).yes + ' helpful'"></span>
                                        <span aria-hidden="true">·</span>
                                        <i data-lucide="thumbs-down" class="w-3 h-3"></i><span x-text="rating(item).no + ' not helpful'"></span>
                                    </span>
                                    <span x-show="!rating(item).any && item.rated.length">No ratings yet</span>
                                </span>
                            </span>
                            <span class="shrink-0 mt-1 text-ink-faint transition-transform duration-[160ms] ease-standard" :class="expanded === item.uid && 'rotate-180'"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                        </button>
                        <div class="flex items-center shrink-0" x-show="canEdit">
                            <button type="button" @click="move(item, -1)" :disabled="filtering || indexOf(item) === 0" :aria-label="'Move {{ $noun }} ' + (indexOf(item) + 1) + ' up'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-up" class="w-3.5 h-3.5"></i></button>
                            <button type="button" @click="move(item, 1)" :disabled="filtering || indexOf(item) === items.length - 1" :aria-label="'Move {{ $noun }} ' + (indexOf(item) + 1) + ' down'" class="{{ $ui['iconBtn'] }}"><i data-lucide="chevron-down" class="w-3.5 h-3.5"></i></button>
                            <button type="button" @click="remove(item)" :aria-label="'Remove {{ $noun }} ' + (indexOf(item) + 1)" class="{{ $ui['iconBtnDanger'] }}"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </div>

                    <div x-show="expanded === item.uid" x-cloak :id="'{{ $helpDoc }}-body-' + item.uid" class="px-3 sm:px-4 pb-4 pt-1 bg-slate-50/40 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($isFaq)
                            <div class="sm:col-span-2">
                                <label :for="'faqs-q-' + item.uid" class="{{ $ui['label'] }}">Question (English)</label>
                                <input :id="'faqs-q-' + item.uid" type="text" x-model="item.q" @input="clearErr(item, 'q')" maxlength="300" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p x-show="err(item, 'q')" x-text="err(item, 'q')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label :for="'faqs-a-' + item.uid" class="{{ $ui['label'] }}">Answer (English)</label>
                                <textarea :id="'faqs-a-' + item.uid" rows="4" x-model="item.a" @input="clearErr(item, 'a')" maxlength="4000" :disabled="!canEdit" class="{{ $ui['input'] }} leading-relaxed"></textarea>
                                <p x-show="err(item, 'a')" x-text="err(item, 'a')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div>
                                <label :for="'faqs-qfil-' + item.uid" class="{{ $ui['label'] }}">Question (Filipino, optional)</label>
                                <input :id="'faqs-qfil-' + item.uid" type="text" x-model="item.q_fil" @input="clearErr(item, 'q_fil')" maxlength="300" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p x-show="err(item, 'q_fil')" x-text="err(item, 'q_fil')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div>
                                <label :for="'faqs-cat-' + item.uid" class="{{ $ui['label'] }}">Category</label>
                                <input :id="'faqs-cat-' + item.uid" type="text" list="faqs-categories" x-model="item.category" @input="clearErr(item, 'category')" maxlength="60" placeholder="e.g. Documents" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p x-show="err(item, 'category')" x-text="err(item, 'category')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label :for="'faqs-afil-' + item.uid" class="{{ $ui['label'] }}">Answer (Filipino, optional)</label>
                                <textarea :id="'faqs-afil-' + item.uid" rows="3" x-model="item.a_fil" @input="clearErr(item, 'a_fil')" maxlength="4000" :disabled="!canEdit" class="{{ $ui['input'] }} leading-relaxed"></textarea>
                                <p x-show="err(item, 'a_fil')" x-text="err(item, 'a_fil')" class="{{ $ui['error'] }}"></p>
                            </div>
                        @else
                            <div class="sm:col-span-2">
                                <label :for="'tut-title-' + item.uid" class="{{ $ui['label'] }}">Title</label>
                                <input :id="'tut-title-' + item.uid" type="text" x-model="item.title" @input="clearErr(item, 'title')" maxlength="200" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p x-show="err(item, 'title')" x-text="err(item, 'title')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label :for="'tut-url-' + item.uid" class="{{ $ui['label'] }}">Video link</label>
                                <input :id="'tut-url-' + item.uid" type="url" inputmode="url" x-model="item.url" @input="clearErr(item, 'url')" maxlength="500" placeholder="https://" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p class="text-[11px] text-ink-muted mt-1">The full https:// address of the video.</p>
                                <p x-show="err(item, 'url')" x-text="err(item, 'url')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div>
                                <label :for="'tut-cat-' + item.uid" class="{{ $ui['label'] }}">Category</label>
                                <input :id="'tut-cat-' + item.uid" type="text" list="tutorials-categories" x-model="item.category" @input="clearErr(item, 'category')" maxlength="60" placeholder="e.g. Documents" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                <p x-show="err(item, 'category')" x-text="err(item, 'category')" class="{{ $ui['error'] }}"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label :for="'tut-dur-' + item.uid" class="{{ $ui['label'] }}">Length</label>
                                    <input :id="'tut-dur-' + item.uid" type="text" x-model="item.duration" @input="clearErr(item, 'duration')" maxlength="10" placeholder="3:24" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                    <p x-show="err(item, 'duration')" x-text="err(item, 'duration')" class="{{ $ui['error'] }}"></p>
                                </div>
                                <div>
                                    <label :for="'tut-icon-' + item.uid" class="{{ $ui['label'] }}">Icon</label>
                                    <select :id="'tut-icon-' + item.uid" x-model="item.icon" :disabled="!canEdit" class="{{ $ui['input'] }}">
                                        <template x-for="choice in iconChoices(item)" :key="choice[0]"><option :value="choice[0]" x-text="choice[1]" :selected="choice[0] === item.icon"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label :for="'tut-desc-' + item.uid" class="{{ $ui['label'] }}">Description (optional)</label>
                                <textarea :id="'tut-desc-' + item.uid" rows="2" x-model="item.description" @input="clearErr(item, 'description')" maxlength="1000" :disabled="!canEdit" class="{{ $ui['input'] }} leading-relaxed"></textarea>
                                <p x-show="err(item, 'description')" x-text="err(item, 'description')" class="{{ $ui['error'] }}"></p>
                            </div>
                        @endif
                    </div>
                </div>
            </template>
            <div class="px-4 py-8 text-center" x-show="!shown.length">
                <p class="text-xs text-ink-muted" x-text="items.length ? 'Nothing matches the search.' : 'No {{ $noun }}s yet.'"></p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3" x-show="canEdit">
            <button type="button" @click="add()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Add a {{ $noun }}</button>
            <div class="flex items-center gap-3">
                <span x-show="dirty" x-cloak class="text-[11px] font-medium text-amber-700">Unsaved changes</span>
                <x-ui.button size="sm" icon="check" x-bind:disabled="saving || !dirty" @click="publish()"><span x-text="saving ? 'Publishing…' : 'Publish'"></span></x-ui.button>
            </div>
        </div>
        <p x-show="error" x-cloak x-text="error" role="alert" class="text-xs text-rose-600"></p>
    </div>
</div>
