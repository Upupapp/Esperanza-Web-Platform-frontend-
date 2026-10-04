<x-layouts.citizen title="Notifications" subtitle="All your account and request updates in one place." active="notifications">
    <div
        x-data="{
            tab: 'all',
            markingAll: false,
            dokyuUrl: @js(route('citizen.document-requests')),
            tulongUrl: @js(route('citizen.assistance-requests')),
            colorClasses: {
                brand: 'text-brand-600 bg-brand-50', rose: 'text-rose-600 bg-rose-50', amber: 'text-amber-600 bg-amber-50',
                indigo: 'text-indigo-600 bg-indigo-50', teal: 'text-teal-600 bg-teal-50', cyan: 'text-cyan-600 bg-cyan-50',
                emerald: 'text-emerald-600 bg-emerald-50', purple: 'text-purple-600 bg-purple-50',
            },
            init() {
                this.$store.citizenNotifications.load();
                // A tab switch draws new rows; their icons need drawing too.
                this.$watch('visible', () => this.$nextTick(() => window.renderIcons?.()));
            },
            timeAgo(iso) {
                if (!iso) return '';
                const diffMs = Date.now() - new Date(iso).getTime();
                const mins = Math.floor(diffMs / 60000);
                if (mins < 1) return 'Just now';
                if (mins < 60) return mins + ' minute' + (mins === 1 ? '' : 's') + ' ago';
                const hrs = Math.floor(mins / 60);
                if (hrs < 24) return hrs + ' hour' + (hrs === 1 ? '' : 's') + ' ago';
                const days = Math.floor(hrs / 24);
                if (days < 7) return days + ' day' + (days === 1 ? '' : 's') + ' ago';
                return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
            goTo(n) {
                this.$store.citizenNotifications.markRead(n.id);
                if (n.link) { window.location.href = n.link; return; }
                if (n.portal === 'dokyu' || n.portal === 'tulong') {
                    window.location.href = (n.portal === 'tulong' ? this.tulongUrl : this.dokyuUrl) + '?ref=' + encodeURIComponent(n.ref || '');
                }
            },
            dynamicMatches(n) {
                if (this.tab === 'all') return true;
                if (this.tab === 'unread') return n.unread;
                if (this.tab === 'read') return !n.unread;
                return n.portal === this.tab;
            },
            get visible() { return this.$store.citizenNotifications.visible.filter((n) => this.dynamicMatches(n)); },
            // The four states x-ui.data-panel reads from the scope around it.
            // They sat in an x-data inside the panel, where the panel cannot
            // see them: every state threw, and the loaded list stayed hidden.
            get loading() { return this.$store.citizenNotifications.loading; },
            get failed() { return this.$store.citizenNotifications.failed; },
            get loaded() { return this.$store.citizenNotifications.state === 'loaded'; },
            get empty() { return this.loaded && this.visible.length === 0; },
            retry() { this.$store.citizenNotifications.load(); },
            message() { return this.$store.lang.current === 'en' ? 'Could not load notifications.' : 'Hindi mai-load ang mga abiso.'; },
            get totalCount() { return this.$store.citizenNotifications.visible.length; },
            get unreadTotal() { return this.$store.citizenNotifications.unreadCount; },
            get dokyuTotalCount() { return this.$store.citizenNotifications.visible.filter((n) => n.portal === 'dokyu').length; },
            get tulongTotalCount() { return this.$store.citizenNotifications.visible.filter((n) => n.portal === 'tulong').length; },
            markAllRead() {
                if (this.markingAll) return;
                this.markingAll = true;
                this.$store.citizenNotifications.markAllRead().finally(() => {
                    this.markingAll = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'All notifications marked as read.' : 'Lahat ng abiso ay minarkahan nang nabasa na.', variant: 'success' });
                });
            },
        }"
        class="animate-fade-up max-w-3xl"
    >

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <x-ui.stat-card label="Kabuuan" labelEn="Total" valueBind="totalCount" icon="inbox" color="brand" :delay="0" />
            <x-ui.stat-card label="Hindi Pa Nababasa" labelEn="Unread" valueBind="unreadTotal" icon="bell" color="orange" :delay="40" />
            <x-ui.stat-card label="Dokyu" labelEn="Dokyu" valueBind="dokyuTotalCount" icon="file-text" color="brand" :delay="80" />
            <x-ui.stat-card label="Tulong" labelEn="Tulong" valueBind="tulongTotalCount" icon="hand-heart" color="purple" :delay="120" />
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-card p-3 mb-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 bg-slate-100/70 rounded-xl p-1 w-fit overflow-x-auto">
                    <button @click="tab = 'all'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap" :class="tab === 'all' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><x-ui.t fil="Lahat" en="All" /></button>
                    <button @click="tab = 'unread'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5" :class="tab === 'unread' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <x-ui.t fil="Hindi Pa Nababasa" en="Unread" />
                        <span class="text-[9px] font-semibold bg-brand-100 text-brand-700 rounded-full px-1.5 py-0.5" x-show="unreadTotal > 0" x-text="unreadTotal"></span>
                    </button>
                    <button @click="tab = 'read'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap" :class="tab === 'read' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"><x-ui.t fil="Nabasa Na" en="Read" /></button>
                    <button @click="tab = 'dokyu'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5" :class="tab === 'dokyu' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="file-text" class="w-3 h-3"></i><x-ui.t fil="Dokyu" en="Dokyu" />
                    </button>
                    <button @click="tab = 'tulong'" class="px-3 py-1.5 text-[11px] font-medium rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5" :class="tab === 'tulong' ? 'bg-white text-navy-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        <i data-lucide="hand-heart" class="w-3 h-3"></i><x-ui.t fil="Tulong" en="Tulong" />
                    </button>
                </div>
                <button
                    @click="markAllRead()" x-bind:disabled="markingAll"
                    class="text-xs font-medium text-brand-600 hover:underline disabled:opacity-50 inline-flex items-center gap-1.5 shrink-0"
                >
                    <i data-lucide="loader-circle" class="w-3 h-3 animate-spin" x-show="markingAll" x-cloak></i>
                    <span x-text="markingAll ? ($store.lang.current === 'en' ? 'Marking…' : 'Minamarkahan…') : ($store.lang.current === 'en' ? 'Mark all as read' : 'Markahan Lahat na Nabasa')"></span>
                </button>
            </div>
            <p class="text-[11px] text-slate-500" x-text="visible.length + ' ' + ($store.lang.current === 'en' ? 'shown' : 'ipinapakita')"></p>
        </div>

        <x-ui.data-panel empty-title="Walang abisong tumutugma dito" empty-title-en="No notifications here" empty-icon="inbox">
                <x-ui.card padded="false">
                    <div class="divide-y divide-slate-50">
                        <template x-for="n in visible" :key="n.id">
                            <div
                                @click="goTo(n)"
                                class="group flex items-start gap-3 px-4 py-4 hover:bg-slate-50/70 transition-colors relative cursor-pointer"
                                role="button" tabindex="0"
                                @keydown.enter.prevent="goTo(n)" @keydown.space.prevent="goTo(n)"
                            >
                                <span class="absolute left-2 top-6 w-1.5 h-1.5 rounded-full bg-brand-500" x-show="n.unread"></span>
                                <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="colorClasses[n.color] || colorClasses.brand">
                                    <i :data-lucide="n.icon" class="w-4 h-4"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-medium text-slate-700 leading-snug" x-text="n.title"></p>
                                        <span class="text-[9px] font-semibold uppercase tracking-wide rounded-full px-1.5 py-0.5 shrink-0" x-show="n.portal === 'dokyu' || n.portal === 'tulong'" :class="n.portal === 'tulong' ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700'" x-text="n.portal === 'tulong' ? 'Tulong' : 'Dokyu'"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5" x-text="n.body"></p>
                                    <p class="text-[11px] text-slate-300 mt-1.5" x-text="timeAgo(n.createdAt)"></p>
                                </div>
                                <span class="text-[10px] font-medium text-slate-300 shrink-0 mt-0.5 whitespace-nowrap" x-show="n.pinned"><x-ui.t fil="Awtomatiko" en="Automated" /></span>
                                <button
                                    type="button" x-show="!n.pinned"
                                    @click.stop="$store.citizenNotifications.dismiss(n.id)"
                                    class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-opacity shrink-0 mt-0.5 focus-within:opacity-100 focus:opacity-100"
                                ><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
        </x-ui.data-panel>
    </div>
</x-layouts.citizen>
