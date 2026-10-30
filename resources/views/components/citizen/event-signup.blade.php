{{-- Registering for an event, on one event's card of the resident's Events
     page (Esperanza round 2, 2026-10-30). Reads the card's `event` (the
     row's `registration` {open, capacity, closes_at, count, full}; its key
     is event.ref) and the resident's own registration from the eventRegs
     store. One of:
     - open: Magparehistro, with the places left and until when;
     - full / closed: said so, and why;
     - guest: sign in to register;
     - registered: the code in big letters and its QR (what the officer at
       the door types or scans), and Kanselahin, confirmed here on the page;
     - attended: Dumalo ka, and the certificate once issued;
     - missed / unrecorded: after the day, if the office marked them absent
       or has not marked them yet.
     Logic: eventSignup() in resources/js/citizen-events.js. --}}
<div x-data="eventSignup(event)" x-effect="suMakeQr()" x-show="suState() !== 'none'" x-cloak class="mt-3 pt-3 border-t border-brand-600/10">
    {{-- Open --}}
    <div x-show="suState() === 'open'" class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-ink flex items-center gap-1.5"><i data-lucide="ticket" class="w-3.5 h-3.5 text-brand-600"></i><x-ui.t fil="Bukas ang pagpaparehistro" en="Registration is open" /></p>
            <p class="text-[11px] text-ink-muted mt-0.5" x-show="suOpenLine()" x-text="suOpenLine()"></p>
        </div>
        <x-ui.button size="sm" icon="ticket" loading="suBusy" @click="suRegister()" class="w-full sm:w-auto"><x-ui.t fil="Magparehistro" en="Register" /></x-ui.button>
    </div>

    {{-- Full, or closed --}}
    <div x-show="suState() === 'full' || suState() === 'closed'" class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2.5 flex items-start gap-2.5">
        <i data-lucide="ticket" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-700" x-show="suState() === 'full'"><x-ui.t fil="Puno na ang kaganapang ito" en="This event is full" /></p>
            <p class="text-xs font-semibold text-slate-700" x-show="suState() === 'closed'"><x-ui.t fil="Sarado na ang pagpaparehistro" en="Registration has closed" /></p>
            <p class="text-[11px] text-ink-muted mt-0.5" x-show="suState() === 'full'"><span x-text="suFullLine()"></span> <x-ui.t fil="Maaaring may magkansela pa, kaya tumingin muli mamaya, o magtanong sa opisinang nangangasiwa." en="Someone may still cancel, so look again later, or ask the office running it." /></p>
            <p class="text-[11px] text-ink-muted mt-0.5" x-show="suState() === 'closed'"><span x-text="suClosedLine()"></span> <x-ui.t fil="Kung nais mo pa ring dumalo, magtanong sa opisinang nangangasiwa." en="If you would still like to attend, ask the office running it." /></p>
        </div>
    </div>

    {{-- Not signed in --}}
    <div x-show="suState() === 'guest'" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-ink-muted flex items-center gap-1.5"><i data-lucide="ticket" class="w-3.5 h-3.5 text-brand-600"></i><x-ui.t fil="Bukas ang pagpaparehistro. Mag-sign in para makapagparehistro." en="Registration is open. Sign in to register." /></p>
        <x-ui.button size="sm" variant="secondary" icon="log-in" href="{{ route('citizen.login') }}"><x-ui.t fil="Mag-sign in" en="Sign in" /></x-ui.button>
    </div>

    {{-- Registered: the code and its QR --}}
    <div x-show="suState() === 'registered'" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3.5">
        <div class="flex flex-wrap sm:flex-nowrap items-center gap-4">
            <div class="w-28 h-28 shrink-0 rounded-xl bg-white p-1.5 border border-emerald-100 [&>svg]:w-full [&>svg]:h-full" x-html="suQr" role="img" :aria-label="($store.lang.current === 'en' ? 'QR code of your registration code ' : 'QR code ng iyong registration code ') + suShowCode(suActive()?.code)"></div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-emerald-800 flex items-center gap-1.5"><i data-lucide="circle-check" class="w-4 h-4"></i><x-ui.t fil="Nakarehistro ka" en="You are registered" /></p>
                <p class="text-[11px] text-emerald-900/80 mt-1"><x-ui.t fil="Ang iyong code:" en="Your code:" /></p>
                <p class="font-mono text-2xl sm:text-3xl font-extrabold tracking-[.14em] text-ink leading-tight select-all" x-text="suShowCode(suActive()?.code)"></p>
                <p class="text-[11px] text-ink-muted mt-1"><x-ui.t fil="Ipakita ang code o QR na ito pagdating mo." en="Show this code or QR when you arrive." /> <span x-show="!suStarted()"><x-ui.t fil="Ipapaalala namin ito isang araw bago." en="We will remind you the day before." /></span></p>
                <button type="button" x-show="!suAsk && !suStarted()" @click="suAsk = true" class="mt-2 inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-50"><i data-lucide="x" class="w-3.5 h-3.5"></i><x-ui.t fil="Kanselahin ang pagpaparehistro" en="Cancel my registration" /></button>
            </div>
        </div>
        <div x-show="suAsk" x-cloak class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3" aria-live="polite">
            <p class="text-xs font-semibold text-rose-700"><x-ui.t fil="Kanselahin ang iyong pagpaparehistro?" en="Cancel your registration?" /></p>
            <p class="text-[11px] text-rose-700/90 mt-0.5"><x-ui.t fil="Maibibigay sa iba ang iyong puwesto. Maaari kang magparehistro muli habang bukas pa ito at may puwesto pa." en="Your place goes to someone else. You can register again while it is open and there is room." /></p>
            <div class="flex flex-wrap justify-end gap-2 mt-2">
                <x-ui.button variant="secondary" size="sm" @click="suAsk = false"><x-ui.t fil="Huwag na" en="Keep it" /></x-ui.button>
                <x-ui.button variant="danger" size="sm" icon="x" loading="suBusy" @click="suCancel()"><x-ui.t fil="Oo, kanselahin" en="Yes, cancel" /></x-ui.button>
            </div>
        </div>
    </div>

    {{-- Attended, and the certificate --}}
    <div x-show="suState() === 'attended'" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 px-3.5 py-3 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-emerald-800 flex items-center gap-1.5"><i data-lucide="badge-check" class="w-4 h-4"></i><x-ui.t fil="Dumalo ka" en="You attended" /></p>
            <p class="text-[11px] text-emerald-900/80 mt-0.5" x-show="!suActive()?.certificate"><x-ui.t fil="Salamat sa pagdalo! Kung may sertipiko ang kaganapang ito, makikita mo ito rito kapag naibigay na." en="Thank you for coming! If this event gives certificates, yours shows here once it is issued." /></p>
            <p class="text-[11px] text-emerald-900/80 mt-0.5" x-show="suActive()?.certificate"><x-ui.t fil="Handa na ang iyong sertipiko." en="Your certificate is ready." /></p>
        </div>
        <x-ui.button size="sm" variant="secondary" icon="award" x-show="suActive()?.certificate" @click="suCert()"><x-ui.t fil="Tingnan ang sertipiko" en="View certificate" /></x-ui.button>
    </div>

    {{-- After the day, not marked as attended --}}
    <div x-show="suState() === 'missed' || suState() === 'unrecorded'" class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2.5 flex items-start gap-2.5">
        <i data-lucide="info" class="w-4 h-4 text-ink-muted shrink-0 mt-0.5"></i>
        <p class="text-[11px] text-slate-600" x-show="suState() === 'missed'"><x-ui.t fil="Nakarehistro ka, pero hindi ka naitalang dumalo. Kung dumalo ka, makipag-ugnayan sa opisinang nangasiwa." en="You registered, but you were not recorded as attending. If you did attend, contact the office that ran it." /></p>
        <p class="text-[11px] text-slate-600" x-show="suState() === 'unrecorded'"><x-ui.t fil="Nakarehistro ka. Hindi pa naitatala ng opisina ang mga dumalo." en="You registered. The office has not recorded who attended yet." /></p>
    </div>
</div>
