{{-- An occasion pop-up from the LGU (Banners & Occasions, 2026-10-21): a
     birthday, a fiesta, a holiday, an LGU event. The server picks at most one
     for this resident - activated, not paused, on for them now (their
     barangay, their group, their birthday), due by its frequency and not
     dismissed - from GET /citizen/popups/active. The birthday greeting is one
     of these now, not a check made in this browser.

     This site is many pages, so "every time Esperanza is opened" means once per
     browser session here: a pop-up shown in this tab is not shown again on the
     next page (sessionStorage, per pop-up and occurrence). It waits for a
     Sakuna alert, the "You are verified" notice or any other open window to be
     done with, and is recorded as seen when it is SHOWN, so a tab closed over
     it still counts. "Huwag nang ipakita" asks the server never to show it
     again this time round. --}}
@php
    $confettiColors = ['bg-brand-400', 'bg-gold-400', 'bg-purple-400', 'bg-rose-400', 'bg-emerald-400'];
    // Fixed, not random: this is a static export, and a random layout would
    // rewrite every page on every build.
    $confetti = collect(range(0, 19))->map(fn ($i) => [
        'left' => 3 + ($i * 37) % 94,
        'delay' => (($i * 53) % 900) / 1000,
        'rotate' => ($i * 71) % 360,
        'color' => $confettiColors[$i % count($confettiColors)],
    ]);
@endphp
<div
    x-data="{
        g: null,
        open: false,
        waited: 0,
        busy: false,
        balita: '{{ route('citizen.announcements') }}',
        t(en, fil) { return $store.lang.current === 'en' ? en : fil; },
        async init() {
            if (!$store.citizenSession.account || !$api.getToken()) return;
            let res = null;
            try { res = await $api.get('/citizen/popups/active'); } catch (e) { return; }
            const g = res && res.data;
            if (!g || !g.id || this.shownHere(g)) return;
            this.g = g;
            setTimeout(() => this.tryShow(), 1600);
        },
        key(g) { return 'esperanza_popup_shown_' + g.id + '_' + (g.occurrence || 'once'); },
        shownHere(g) { try { return sessionStorage.getItem(this.key(g)) === '1'; } catch (e) { return false; } },
        // An alert or the welcome first, then whatever window is open. The
        // bell's first load is waited for a few seconds at most: a page
        // without it must still greet.
        blocked() {
            const n = $store.citizenNotifications;
            if (n.pendingAlert || n.pendingWelcome) return true;
            if (document.body.classList.contains('overflow-hidden')) return true;
            return (n.state === 'idle' || n.state === 'loading') && this.waited < 10;
        },
        tryShow() {
            if (this.blocked()) { this.waited += 1; setTimeout(() => this.tryShow(), 1000); return; }
            if (!this.g || this.shownHere(this.g)) return;
            try { sessionStorage.setItem(this.key(this.g), '1'); } catch (e) { /* it may show again on the next page */ }
            this.open = true;
            $api.post('/citizen/popups/' + encodeURIComponent(this.g.id) + '/seen').catch(() => {});
            this.$nextTick(() => window.renderIcons?.());
        },
        async dontShowAgain() {
            if (this.busy || !this.g) return;
            this.busy = true;
            try { await $api.post('/citizen/popups/' + encodeURIComponent(this.g.id) + '/dismiss'); } catch (e) { /* it may come back once more */ }
            this.busy = false;
            this.open = false;
        },
        get kind() {
            const g = this.g;
            if (!g) return '';
            const label = g.type_label || g.occasion_label || {};
            return this.t(label.en || label.fil || '', label.fil || label.en || '');
        },
        get image() { return this.g ? (this.g.image_url || this.g.poster_url || null) : null; },
        // Only an https link leaves the portal; the server checks it too.
        get link() { const u = this.g && this.g.button && this.g.button.url; return u && /^https:\/\//i.test(u) ? u : null; },
        get postLink() { return this.g && this.g.post_id ? this.balita + '?post=' + encodeURIComponent(this.g.post_id) : null; },
        get buttonLabel() { const l = this.g && this.g.button && this.g.button.label; return l || (this.link ? this.t('Open', 'Buksan') : this.t('See it in Balita', 'Tingnan sa Balita')); },
    }"
    class="contents"
>
    <x-ui.modal title="Pagbati mula sa Esperanza" titleEn="Greetings from Esperanza" maxWidth="md">
        <template x-if="g">
            <div class="flex flex-col items-center text-center pb-1">
                <div x-show="g.birthday" class="relative h-20 -mx-5 -mt-5 mb-1 w-[calc(100%+2.5rem)] overflow-hidden bg-gradient-to-b from-gold-50/70 to-transparent" aria-hidden="true">
                    @foreach($confetti as $piece)
                        <span class="absolute top-0 w-1.5 h-3 rounded-sm {{ $piece['color'] }} motion-safe:animate-confetti-fall" style="left: {{ $piece['left'] }}%; animation-delay: {{ $piece['delay'] }}s; transform: rotate({{ $piece['rotate'] }}deg);"></span>
                    @endforeach
                </div>
                <img x-show="image" :src="image || ''" :alt="g.image_alt || g.title" class="-mx-6 mb-4 w-[calc(100%+3rem)] max-w-none max-h-[50vh] object-contain bg-gold-50" :class="g.birthday ? '' : '-mt-5'">
                <span x-show="!image" class="w-16 h-16 rounded-full bg-gradient-to-br from-gold-100 to-brand-50 flex items-center justify-center mb-3 motion-safe:animate-check-pop" aria-hidden="true"><i :data-lucide="g.birthday ? 'cake' : 'party-popper'" class="w-8 h-8 text-gold-700"></i></span>
                <span x-show="g.birthday" class="text-[11px] font-bold tracking-widest text-gold-700">MALIGAYANG KAARAWAN</span>
                <span x-show="!g.birthday && kind" class="inline-flex items-center gap-1 text-[11px] font-semibold rounded-full px-2.5 py-0.5 bg-gold-100 text-gold-700"><i data-lucide="party-popper" class="w-3 h-3"></i><span x-text="kind"></span></span>
                <h3 class="mt-2.5 text-lg font-extrabold text-ink leading-snug" x-text="g.title"></h3>
                <p class="mt-2 text-sm text-ink-body leading-relaxed whitespace-pre-line max-w-[440px]" x-text="g.message"></p>
                <button x-show="g.dismissible" type="button" @click="dontShowAgain()" :disabled="busy" class="mt-3 text-xs font-semibold text-ink-muted underline hover:text-ink disabled:opacity-50"><x-ui.t fil="Huwag nang ipakita" en="Don't show again" /></button>
            </div>
        </template>
        <x-slot:footer>
            <x-ui.button variant="secondary" size="sm" @click="open = false"><x-ui.t fil="Salamat!" en="Thank you!" /></x-ui.button>
            <template x-if="link">
                <x-ui.button href="#" x-bind:href="link" target="_blank" rel="noopener noreferrer" size="sm" icon="external-link" iconPosition="right" @click="open = false"><span x-text="buttonLabel"></span></x-ui.button>
            </template>
            <template x-if="!link && postLink">
                <x-ui.button href="#" x-bind:href="postLink" size="sm" icon="megaphone" @click="open = false"><span x-text="buttonLabel"></span></x-ui.button>
            </template>
        </x-slot:footer>
    </x-ui.modal>
</div>
