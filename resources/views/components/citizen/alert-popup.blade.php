{{-- A Sakuna alert the LGU published for the resident's barangay opens here
     once, on whatever page they are on, as soon as the bell picks it up (it
     refreshes every minute). Before, an alert reached only someone who
     happened to open the dashboard (QA run 2026-10-05). --}}
<div
    x-data="{
        get alert() { return $store.citizenNotifications.pendingAlert; },
        get open() { return !!this.alert; },
        set open(v) { if (!v && this.alert) $store.citizenNotifications.alertShown(this.alert.id); },
    }"
    x-effect="if (open) $nextTick(() => window.renderIcons?.())"
    class="contents"
>
    <x-ui.modal title="Alerto ng Sakuna" titleEn="Emergency alert" maxWidth="md">
        <template x-if="alert">
            <div class="flex items-start gap-3">
                <span class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0" aria-hidden="true"><i data-lucide="siren" class="w-5 h-5"></i></span>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-700" x-show="alert.pill" x-text="alert.pill"></p>
                    <h3 class="text-base font-semibold text-ink" x-text="$store.lang.current === 'en' ? (alert.titleEn || alert.title) : (alert.titleFil || alert.title)"></h3>
                    <p class="text-sm text-slate-600 mt-1.5 whitespace-pre-line" x-text="$store.lang.current === 'en' ? (alert.bodyEn || alert.body) : (alert.bodyFil || alert.body)"></p>
                </div>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="open = false"><x-ui.t fil="Isara" en="Close" /></x-ui.button>
            {{-- The alert's own page is Sakuna (Emergency), not Home (parity check, 2026-10-26). --}}
            <x-ui.button :href="route('citizen.emergency')" size="sm" icon="shield-alert" @click="open = false"><x-ui.t fil="Buksan ang Sakuna" en="Open Sakuna" /></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
