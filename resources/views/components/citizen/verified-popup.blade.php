{{-- "You are verified" opens once, on whatever page the resident is on, as
     soon as the bell picks the notice up (it refreshes every minute). It was
     only a red dot on the bell, while Dokyu and Tulong still said to wait for
     approval (live test 2026-10-07, UX-9). The account is read again so those
     buttons switch on without a reload. --}}
<div
    x-data="{
        get notice() { return $store.citizenNotifications.pendingWelcome; },
        get open() { return !!this.notice && !$store.citizenNotifications.pendingAlert; },
        set open(v) { if (!v && this.notice) $store.citizenNotifications.alertShown(this.notice.id); },
    }"
    x-init="$watch('open', (v) => { if (v) { $store.citizenSession.refresh?.().catch(() => {}); $nextTick(() => window.renderIcons?.()); } })"
    class="contents"
>
    <x-ui.modal title="Beripikado ka na!" titleEn="You are verified!" maxWidth="md">
        <div class="flex items-start gap-3">
            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0" aria-hidden="true"><i data-lucide="badge-check" class="w-5 h-5"></i></span>
            <p class="text-sm text-slate-600 leading-relaxed"><x-ui.t fil="Beripikado na ng LGU ang iyong account. Puwede ka nang humiling ng dokumento sa Dokyu o mag-apply ng tulong sa Tulong." en="The LGU has verified your account. You can now request documents in Dokyu or apply for assistance in Tulong." /></p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" :href="route('citizen.assistance-requests')" @click="open = false"><x-ui.t fil="Mag-apply ng Tulong" en="Apply for Tulong" /></x-ui.button>
            <x-ui.button size="sm" icon="file-plus" :href="route('citizen.document-requests')" @click="open = false"><x-ui.t fil="Humiling ng Dokumento" en="Request a Document" /></x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
