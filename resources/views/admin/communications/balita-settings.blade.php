{{-- Balita settings (welfare round 2026-10-29), for the officers who approve
     Balita (comm_news approve): the watermark on photos officers upload.
     Opened by the gear on the Balita tab (`balita-settings` on window).
     Component balitaSettings in resources/js/scope-admin-records.js;
     GET/PUT /admin/balita/watermark {enabled, mark, size}. The preview draws
     the mark over a sample photo to the server's own numbers; the wordmark is
     drawn here with HTML (the seal and the words), not the server's PNG. --}}
<div x-data="balitaSettings" @balita-settings.window="wmShow()">
    <div x-data="{ get open() { return wmOpen; }, set open(v) { wmOpen = v; } }">
    <x-ui.modal title="Balita settings" maxWidth="md">
        <div x-show="wmLoading && !wmSaved" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-48 rounded-2xl" /><x-ui.skeleton class="h-12 rounded-2xl opacity-70" /></div>
        <div x-show="wmFailed && !wmLoading" x-cloak><x-ui.error-state messageExpr="'Could not read the Balita settings.'" retryExpr="wmLoad()" :retryable="true" /></div>

        <div x-show="wmSaved" x-cloak class="space-y-5">
            <section aria-labelledby="wm-heading" class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h4 id="wm-heading" class="text-sm font-bold text-ink flex items-center gap-1.5"><i data-lucide="stamp" class="w-4 h-4 text-brand-600"></i>Photo watermark</h4>
                        <p class="text-xs text-ink-muted mt-0.5">The municipal seal in the lower-right corner of every photo officers upload to Balita and Events, so a copied photo still shows where it came from. The original is kept unmarked. A change applies to photos uploaded from now on.</p>
                    </div>
                    <button type="button" role="switch" :aria-checked="wm.enabled ? 'true' : 'false'" aria-labelledby="wm-heading" @click="wm.enabled = !wm.enabled"
                        class="relative shrink-0 w-12 h-7 rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100" :class="wm.enabled ? 'bg-brand-600' : 'bg-slate-300'">
                        <span class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="wm.enabled && 'translate-x-5'"></span>
                    </button>
                </div>

                {{-- The preview: a sample photo as uploaded, with the mark where the server puts it --}}
                <figure class="space-y-1.5">
                    <div class="relative rounded-2xl overflow-hidden bg-slate-200 aspect-[4/3]" data-testid="watermark-preview">
                        {{-- Drawn only while the window is open: the photo and the seal are not loaded with the page. --}}
                        <template x-if="wmOpen">
                        <div class="absolute inset-0">
                        <img src="{{ asset('images/esperanza/esperanza-aerial.jpg') }}" alt="Sample photo: the coast of Esperanza from above" class="absolute inset-0 w-full h-full object-cover">
                        <div x-show="wm.enabled && wm.mark === 'seal'" class="absolute" :style="wmBox()" aria-hidden="true">
                            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="" class="block w-full h-auto p-[3.1%] drop-shadow-[0_1px_4px_rgb(0_0_0/.45)]">
                        </div>
                        <div x-show="wm.enabled && wm.mark === 'wordmark'" class="absolute @container aspect-[1053/228]" :style="wmBox()" aria-hidden="true">
                            <img src="{{ asset('images/esperanza/esperanza-seal.png') }}" alt="" class="absolute left-[1.33%] top-[6.1%] h-[87.7%] w-auto drop-shadow-[0_1px_3px_rgb(0_0_0/.5)]">
                            <span class="absolute left-[22.8%] top-[18%] whitespace-nowrap font-bold leading-none text-white text-[4.9cqw] [text-shadow:0_0_.7cqw_rgb(0_0_0/.8)]">MUNICIPALITY OF ESPERANZA</span>
                            <span class="absolute left-[22.8%] top-[54%] whitespace-nowrap leading-none text-white/95 text-[3.4cqw] [text-shadow:0_0_.6cqw_rgb(0_0_0/.8)]">Province of Masbate</span>
                        </div>
                        </div>
                        </template>
                    </div>
                    <figcaption class="text-[11px] text-ink-muted" x-text="wm.enabled ? 'Preview: a 2000 × 1500 photo as residents will see it.' : 'No watermark: photos are posted as uploaded.'"></figcaption>
                </figure>

                <fieldset :disabled="!wm.enabled" class="space-y-2 disabled:opacity-50">
                    <legend class="text-xs font-semibold text-slate-700 mb-1.5">Mark</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-center gap-3 rounded-xl border px-3 py-2.5 cursor-pointer transition-colors focus-within:ring-4 focus-within:ring-brand-100" :class="wm.mark === 'seal' ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:bg-white/70'">
                            <input type="radio" name="wm-mark" value="seal" x-model="wm.mark" class="text-brand-600 focus:ring-brand-500">
                            <img src="{{ asset('images/esperanza/esperanza-seal-64.png') }}" alt="" class="w-8 h-8">
                            <span class="text-sm font-medium text-ink">Municipal seal</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border px-3 py-2.5 cursor-pointer transition-colors focus-within:ring-4 focus-within:ring-brand-100" :class="wm.mark === 'wordmark' ? 'border-brand-400 bg-brand-50' : 'border-slate-200 hover:bg-white/70'">
                            <input type="radio" name="wm-mark" value="wordmark" x-model="wm.mark" class="text-brand-600 focus:ring-brand-500">
                            <img src="{{ asset('images/esperanza/esperanza-seal-64.png') }}" alt="" class="w-8 h-8">
                            <span class="text-sm font-medium text-ink">Seal with “Municipality of Esperanza”</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset :disabled="!wm.enabled" class="disabled:opacity-50">
                    <legend class="text-xs font-semibold text-slate-700 mb-1.5">Size</legend>
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 gap-1">
                        @foreach(['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $wmSize => $wmLabel)
                            <label class="cursor-pointer">
                                <input type="radio" name="wm-size" value="{{ $wmSize }}" x-model="wm.size" class="peer sr-only">
                                <span class="block h-8 px-3.5 leading-8 rounded-lg text-xs font-semibold text-ink-muted peer-checked:bg-white peer-checked:text-ink peer-checked:shadow-sm peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100">{{ $wmLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-ink-muted mt-1.5">A share of the photo's shorter side, so a portrait and a landscape photo carry the same mark. Photos smaller than 320 pixels on a side are left as they are.</p>
                </fieldset>
            </section>

            {{-- The Facebook Page lives in Settings > Integrations (welfare round 2026-10-29). --}}
            <section aria-labelledby="wm-fb-heading" class="pt-4 border-t border-brand-600/10 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h4 id="wm-fb-heading" class="text-sm font-bold text-ink flex items-center gap-1.5"><x-balita.fb-mark class="w-4 h-4 text-[#1877F2]" />Facebook Page</h4>
                    <p class="text-xs text-ink-muted mt-0.5" x-text="fbConnected() ? ('Connected as ' + fbPageName() + (fb.status === 'token_problem' ? '. The connection needs attention.' : (fb.auto_post === false ? '. Auto-post is off.' : '. Published posts go there by themselves.'))) : 'Not connected. Balita posts are not shared to Facebook.'"></p>
                </div>
                <a href="{{ route('admin.settings') }}?tab=integrations" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">Settings › Integrations<i data-lucide="arrow-right" class="w-4 h-4"></i></a>
            </section>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="wmOpen = false">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="check" x-bind:disabled="wmSaving || !wmDirty()" @click="wmSave()"><span x-text="wmSaving ? 'Saving…' : 'Save'"></span></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    </div>
</div>
