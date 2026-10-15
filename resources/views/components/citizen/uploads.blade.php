{{-- Documents Uploaded: every file the resident attached to their Dokyu and
     Tulong requests, from the app or the website (GET /citizen/uploads), the
     ones a later file replaced marked as such. A file opens by its signed link
     once the virus check has cleared it; until then the row says why. Logic in
     resources/js/scope-citizen.js (citizenUploads); the app's
     documents_uploaded_screen.dart. --}}
<div x-data="citizenUploads" {{ $attributes->merge(['class' => 'surface-glass rounded-[26px] p-5']) }} id="documents-uploaded">
    <div class="flex items-center justify-between gap-2 mb-1">
        <h3 class="text-sm font-semibold text-ink flex items-center gap-2"><i data-lucide="file-up" class="w-4 h-4 text-ink-muted"></i><x-ui.t fil="Mga In-upload na Dokumento" en="Documents Uploaded" /></h3>
        <span x-show="rows.length" x-cloak class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700" x-text="rows.length"></span>
    </div>
    <p class="text-xs text-ink-muted mb-3 leading-relaxed"><x-ui.t fil="Ang mga dokumentong in-upload mo sa iyong mga kahilingan sa Dokyu at Tulong, mula sa app o sa website." en="The documents you uploaded with your Dokyu and Tulong requests, from the app or the website." /></p>

    <div class="flex items-center gap-1 surface-pressed-sm rounded-xl p-1 w-fit mb-3" role="tablist" :aria-label="$store.lang.current === 'en' ? 'Show documents from' : 'Ipakita ang mga dokumento mula sa'">
        <template x-for="f in ['all', 'dokyu', 'tulong']" :key="f">
            <button type="button" role="tab" :aria-selected="filter === f" @click="filter = f"
                class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap"
                :class="filter === f ? 'bg-white text-ink shadow-sm' : 'text-ink-muted hover:text-slate-700'"
                x-text="(f === 'all' ? ($store.lang.current === 'en' ? 'All' : 'Lahat') : (f === 'dokyu' ? 'Dokyu' : 'Tulong')) + (state === 'loaded' ? ' (' + count(f) + ')' : '')"></button>
        </template>
    </div>

    <div x-show="state === 'loading'" class="space-y-2"><x-ui.skeleton class="h-14 rounded-2xl" /><x-ui.skeleton class="h-14 rounded-2xl opacity-70" /></div>
    <div x-show="state === 'error'" x-cloak class="flex flex-wrap items-center gap-3 text-xs text-rose-700" role="alert">
        <span x-text="errorMessage"></span>
        <x-ui.button size="sm" variant="secondary" icon="rotate-ccw" @click="load()"><x-ui.t fil="Subukang muli" en="Try again" /></x-ui.button>
    </div>
    <div x-show="state === 'loaded' && !list.length" x-cloak class="rounded-2xl surface-pressed-sm px-4 py-4 text-center">
        <p class="text-sm font-semibold text-ink"><x-ui.t fil="Wala pang in-upload na dokumento" en="No documents uploaded yet" /></p>
        <p class="text-xs text-ink-muted mt-0.5"><x-ui.t fil="Lalabas dito ang mga dokumentong in-upload mo sa pagsumite ng kahilingan sa Dokyu o Tulong." en="Documents you upload while submitting a Dokyu or Tulong request will appear here." /></p>
    </div>

    <ul class="divide-y divide-brand-600/[.08]" x-show="state === 'loaded' && list.length" x-cloak>
        <template x-for="row in list" :key="row.id">
            <li class="flex items-start gap-3 py-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="row.type === 'tulong' ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-600'">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-700 truncate" x-text="row.requirement || row.name"></p>
                    <p class="text-[11px] text-ink-muted truncate" x-text="[row.name !== row.requirement ? row.name : null, row.service, row.ref].filter(Boolean).join(' · ')"></p>
                    <p class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="px-1.5 py-px rounded text-[10px] font-semibold" :class="row.type === 'tulong' ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700'" x-text="row.type === 'tulong' ? 'Tulong' : 'Dokyu'"></span>
                        <span x-show="!row.current" class="px-1.5 py-px rounded text-[10px] font-semibold bg-slate-100 text-slate-600"><x-ui.t fil="Pinalitan na" en="Replaced" /></span>
                        <span class="text-[11px] text-ink-muted" x-show="row.uploadedAt" x-text="fmtDate(row.uploadedAt)"></span>
                    </p>
                    <p x-show="!row.url" class="text-[11px] text-amber-700 mt-1" x-text="why(row)"></p>
                </div>
                <a x-show="row.url" :href="row.url" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:underline mt-1"
                    :aria-label="($store.lang.current === 'en' ? 'Open ' : 'Buksan ang ') + (row.requirement || row.name)">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i><x-ui.t fil="Buksan" en="Open" />
                </a>
            </li>
        </template>
    </ul>
</div>
