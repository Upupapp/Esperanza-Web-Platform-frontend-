@props(['type' => null])
{{-- The Dokyu and Tulong on the resident's record (GET /citizen/records, owner
     2026-10-13). With no type: My Profile's "Dokyu & Tulong Received", every
     record. With one: that page's records from the office, shown only when
     there are some. Logic in resources/js/citizen-records.js. --}}
<div
    x-data="citizenRecords(@js($type), @js(['dokyu' => route('citizen.document-requests'), 'tulong' => route('citizen.assistance-requests')]))"
    @if($type) x-show="list.length" x-cloak @endif
    {{ $attributes->merge(['class' => 'surface-glass rounded-[26px] p-5']) }}
>
    <div class="flex items-center justify-between gap-2 mb-1">
        <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
            <i data-lucide="{{ $type ? 'history' : 'hand-coins' }}" class="w-4 h-4 text-ink-muted"></i>
            @if($type)
                <x-ui.t fil="Mga dating record mula sa office" en="Past records from the office" />
            @else
                <x-ui.t fil="Mga Natanggap na Dokyu at Tulong" en="Dokyu & Tulong Received" />
            @endif
        </h3>
        <span x-show="list.length" x-cloak class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700" x-text="list.length"></span>
    </div>
    <p class="text-xs text-ink-muted mb-3 leading-relaxed">
        @if($type)
            <x-ui.t fil="Naitala ng office para sa iyo bago ka nagka-account, o sa counter nila. Pang-view lang." en="What the office recorded for you before you had an account, or at its counter. For viewing only." />
        @else
            <x-ui.t fil="Ang mga dokumento at tulong sa record mo: ang naitala ng mga office, at ang mga in-file mo rito." en="The documents and assistance on your record: what the offices recorded, and what you filed here." />
        @endif
    </p>

    <p x-show="loading" class="text-xs text-ink-muted flex items-center gap-1.5"><span><i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i></span><x-ui.t fil="Naglo-load…" en="Loading…" /></p>
    <p x-show="!loading && failed" x-cloak class="text-xs text-rose-700"><x-ui.t fil="Hindi ma-load." en="Could not load them." /> <button type="button" class="underline" @click="load()"><x-ui.t fil="Subukang muli" en="Try again" /></button></p>
    @unless($type)
        <p x-show="!loading && !failed && pending" x-cloak class="text-xs text-ink-muted"><x-ui.t fil="Lalabas dito ang mga Dokyu at Tulong mo kapag na-verify na ng barangay ang account mo at na-confirm na sa iyo ang record." en="They appear here once your barangay verifies your account and confirms the record is yours." /></p>
        <p x-show="!loading && !failed && !pending && !list.length" x-cloak class="text-xs text-ink-muted"><x-ui.t fil="Wala pang dokumento o tulong sa record mo." en="No documents or assistance on your record yet." /></p>
    @endunless

    <ul x-show="list.length" x-cloak class="divide-y divide-brand-600/[.08] -mx-1">
        <template x-for="q in list" :key="q.ref">
            <li>
                <a :href="link(q) || null" :class="link(q) ? 'hover:bg-white/60 cursor-pointer' : 'cursor-default'" class="flex items-start gap-3 py-2.5 px-1 rounded-xl transition-colors">
                    <span class="w-8 h-8 rounded-xl grid place-items-center shrink-0" :class="q.type === 'tulong' ? 'bg-rose-50 text-rose-600' : 'bg-brand-50 text-brand-600'">
                        <span x-show="q.type === 'tulong'"><i data-lucide="hand-heart" class="w-4 h-4"></i></span>
                        <span x-show="q.type !== 'tulong'"><i data-lucide="file-text" class="w-4 h-4"></i></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-ink leading-snug" x-text="q.service || '—'"></span>
                        <span class="block text-xs text-ink-muted mt-0.5" x-text="line(q)"></span>
                        <span x-show="q.purpose" class="block text-xs text-ink-muted" x-text="t('For: ', 'Para sa: ') + q.purpose"></span>
                        <span class="block text-[11px] font-semibold mt-0.5" :class="q.from_office ? 'text-brand-700' : 'text-ink-muted'" x-text="source(q)"></span>
                    </span>
                    {{-- LANGUAGE (TAB 09): a ruled status name, shown as is. --}}
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset whitespace-nowrap shrink-0" :class="tone(q.status)" x-text="q.status"></span>
                </a>
            </li>
        </template>
    </ul>
    @unless($type)
        <p x-show="total" x-cloak class="mt-2 pt-2 border-t border-brand-600/[.08] text-xs text-ink" x-text="t('Assistance received in all: ', 'Kabuuang tulong na natanggap: ') + peso(total)"></p>
    @endunless
</div>
