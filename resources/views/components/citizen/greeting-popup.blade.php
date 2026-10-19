{{-- An occasion greeting from the LGU (2026-10-19): a festival, a holiday,
     Foundation Day. The server picks at most one for this resident (approved,
     in its pop-up window, for their barangay, and due by its frequency: once,
     once a day, or every time Esperanza is opened) from
     GET /citizen/greetings/active.

     This site is many pages, so "every time Esperanza is opened" means once per
     browser session here: a greeting shown in this tab is not shown again on
     the next page (sessionStorage, per greeting). It waits for a Sakuna alert,
     the "You are verified" notice, the birthday greeting or any other open
     window to be done with, and is recorded as seen when it is SHOWN, so a tab
     closed over it still counts. Closing it never hides the post in Balita. --}}
<div
    x-data="{
        g: null,
        open: false,
        waited: 0,
        balita: '{{ route('citizen.announcements') }}',
        t(en, fil) { return $store.lang.current === 'en' ? en : fil; },
        async init() {
            if (!$store.citizenSession.account || !$api.getToken()) return;
            let res = null;
            try { res = await $api.get('/citizen/greetings/active'); } catch (e) { return; }
            const g = res && res.data;
            if (!g || !g.id || this.shownHere(g.id)) return;
            this.g = g;
            setTimeout(() => this.tryShow(), 1600);
        },
        key(id) { return 'esperanza_greeting_shown_' + id; },
        shownHere(id) { try { return sessionStorage.getItem(this.key(id)) === '1'; } catch (e) { return false; } },
        // An alert or the welcome first, then whatever window is open. The
        // bell's first load and the dashboard's birthday check are waited
        // for a few seconds at most: a page without them must still greet.
        blocked() {
            const n = $store.citizenNotifications;
            if (n.pendingAlert || n.pendingWelcome) return true;
            if (document.body.classList.contains('overflow-hidden')) return true;
            const birthday = document.querySelector('[data-birthday-check]');
            const settling = n.state === 'idle' || n.state === 'loading' || (!!birthday && birthday.dataset.birthdayCheck !== 'done');
            return settling && this.waited < 10;
        },
        tryShow() {
            if (this.blocked()) { this.waited += 1; setTimeout(() => this.tryShow(), 1000); return; }
            if (!this.g || this.shownHere(this.g.id)) return;
            try { sessionStorage.setItem(this.key(this.g.id), '1'); } catch (e) { /* it may show again on the next page */ }
            this.open = true;
            $api.post('/citizen/greetings/' + encodeURIComponent(this.g.id) + '/seen').catch(() => {});
            this.$nextTick(() => window.renderIcons?.());
        },
        get occasion() {
            const g = this.g;
            if (!g) return '';
            if (g.occasion === 'custom' && g.occasion_name) return g.occasion_name;
            const label = g.occasion_label || {};
            return this.t(label.en || label.fil || '', label.fil || label.en || '');
        },
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
                <img x-show="g.poster_url" :src="g.poster_url || ''" :alt="g.title" class="-mx-6 -mt-5 mb-4 w-[calc(100%+3rem)] max-w-none max-h-[50vh] object-contain bg-gold-50">
                <span x-show="!g.poster_url" class="w-16 h-16 rounded-full bg-gradient-to-br from-gold-100 to-brand-50 flex items-center justify-center mb-3 motion-safe:animate-check-pop" aria-hidden="true"><i data-lucide="party-popper" class="w-8 h-8 text-gold-700"></i></span>
                <span x-show="occasion" class="inline-flex items-center gap-1 text-[11px] font-semibold rounded-full px-2.5 py-0.5 bg-gold-100 text-gold-700"><i data-lucide="party-popper" class="w-3 h-3"></i><span x-text="occasion"></span></span>
                <h3 class="mt-2.5 text-lg font-extrabold text-ink leading-snug" x-text="g.title"></h3>
                <p class="mt-2 text-sm text-ink-body leading-relaxed whitespace-pre-line max-w-[440px]" x-text="g.message"></p>
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
