@php
    // TAB 04: fixtures live in resources/data/citizen/notifications.php so this view stays presentation only.
    $__fixtures = require base_path('resources/data/citizen/notifications.php');
    $notifications = $__fixtures['notifications'];

    $categoryMeta = $__fixtures['categoryMeta'];

    // Build each row's full click behavior server-side (mark read + open popup
    // and/or navigate) so the template stays a single, uniform @click binding.
    foreach ($notifications as $i => $n) {
        $actions = ["read.includes({$i}) || read.push({$i})"];
        if (!empty($n['popup'])) {
            $actions[] = "\$dispatch('{$n['popup']}')";
        }
        if (!empty($n['link'])) {
            $url = route($n['link']) . (!empty($n['ref']) ? '?ref=' . urlencode($n['ref']) : '');
            $actions[] = "window.location.href = '" . $url . "'";
        }
        $notifications[$i]['clickAction'] = implode('; ', $actions);
    }

    $dokyuCount = collect($notifications)->where('category', 'dokyu')->count();
    $tulongCount = collect($notifications)->where('category', 'tulong')->count();
    $unreadCount = collect($notifications)->where('unread', true)->count();
@endphp

<x-layouts.citizen title="Notifications" subtitle="All your account and request updates in one place." active="notifications">
    <div
        x-data="{
            tab: 'all',
            read: [],
            removed: [],
            unreadFlags: @js(collect($notifications)->pluck('unread')->all()),
            categories: @js(collect($notifications)->pluck('category')->all()),
            markingAll: false,
            // Real, request-driven notifications (submission + every admin
            // status change) live in $store.citizenNotifications, persisted
            // via localStorage — see resources/js/app.js. They're merged in
            // above the static seed list below (account/community/events
            // content that isn't request-driven).
            dokyuUrl: @js(route('citizen.document-requests')),
            tulongUrl: @js(route('citizen.assistance-requests')),
            colorClasses: {
                brand: 'text-brand-600 bg-brand-50', rose: 'text-rose-600 bg-rose-50', amber: 'text-amber-600 bg-amber-50',
                indigo: 'text-indigo-600 bg-indigo-50', teal: 'text-teal-600 bg-teal-50', cyan: 'text-cyan-600 bg-cyan-50',
                emerald: 'text-emerald-600 bg-emerald-50',
            },
            timeAgo(iso) {
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
                window.location.href = (n.portal === 'tulong' ? this.tulongUrl : this.dokyuUrl) + '?ref=' + encodeURIComponent(n.ref);
            },
            dynamicMatches(n) {
                if (this.tab === 'all') return true;
                if (this.tab === 'unread') return n.unread;
                if (this.tab === 'read') return !n.unread;
                return n.portal === this.tab;
            },
            isRead(i) { return !this.unreadFlags[i] || this.read.includes(i); },
            matches(i) {
                if (this.removed.includes(i)) return false;
                if (this.tab === 'all') return true;
                if (this.tab === 'unread') return !this.isRead(i);
                if (this.tab === 'read') return this.isRead(i);
                return this.categories[i] === this.tab;
            },
            get visibleCount() {
                return this.unreadFlags.map((_, i) => i).filter(i => this.matches(i)).length
                    + this.$store.citizenNotifications.items.filter(n => this.dynamicMatches(n)).length;
            },
            get unreadTotal() {
                return this.unreadFlags.map((_, i) => i).filter(i => !this.removed.includes(i) && !this.isRead(i)).length
                    + this.$store.citizenNotifications.unreadCount;
            },
            get totalCount() { return this.unreadFlags.length + this.$store.citizenNotifications.items.length; },
            get dokyuTotalCount() { return {{ $dokyuCount }} + this.$store.citizenNotifications.items.filter(n => n.portal === 'dokyu').length; },
            get tulongTotalCount() { return {{ $tulongCount }} + this.$store.citizenNotifications.items.filter(n => n.portal === 'tulong').length; },
            markAllRead() {
                if (this.markingAll) return;
                this.markingAll = true;
                setTimeout(() => {
                    this.read = [...this.unreadFlags.keys()];
                    this.$store.citizenNotifications.markAllRead();
                    this.markingAll = false;
                    this.$dispatch('toast', { message: this.$store.lang.current === 'en' ? 'All notifications marked as read.' : 'Lahat ng abiso ay minarkahan nang nabasa na.', variant: 'success' });
                }, 500);
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
            <p class="text-[11px] text-slate-400" x-text="visibleCount + ' ' + ($store.lang.current === 'en' ? 'shown' : 'ipinapakita')"></p>
        </div>

        <x-ui.card padded="false">
            <div class="divide-y divide-slate-50">
                <template x-for="n in $store.citizenNotifications.items" :key="n.id">
                    <div
                        x-show="dynamicMatches(n)"
                        @click="goTo(n)"
                        class="group flex items-start gap-3 px-4 py-4 hover:bg-slate-50/70 transition-colors relative cursor-pointer"
                    
        role="button"
        tabindex="0"
        @keydown.enter.prevent="goTo(n)"
        @keydown.space.prevent="goTo(n)">
                        <span class="absolute left-2 top-6 w-1.5 h-1.5 rounded-full bg-brand-500" x-show="n.unread"></span>
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="colorClasses[n.color] || colorClasses.brand">
                            <i :data-lucide="n.icon" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-slate-700 leading-snug" x-text="n.title"></p>
                                <span class="text-[9px] font-semibold uppercase tracking-wide rounded-full px-1.5 py-0.5 shrink-0" :class="n.portal === 'tulong' ? 'bg-purple-50 text-purple-700' : 'bg-brand-50 text-brand-700'" x-text="n.portal === 'tulong' ? 'Tulong' : 'Dokyu'"></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="n.body"></p>
                            <p class="text-[11px] text-slate-300 mt-1.5" x-text="timeAgo(n.createdAt)"></p>
                        </div>
                        <button
                            type="button"
                            @click.stop="$store.citizenNotifications.remove(n.id)"
                            class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-opacity shrink-0 mt-0.5"
                        ><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                </template>
                @foreach($notifications as $n)
                    @php $cat = $categoryMeta[$n['category']] ?? null; @endphp
                    <div
                        x-show="matches({{ $loop->index }})"
                        @click="{{ $n['clickAction'] }}"
                        class="group flex items-start gap-3 px-4 py-4 hover:bg-slate-50/70 transition-colors relative cursor-pointer"
                    
        role="button"
        tabindex="0"
        @keydown.enter.prevent="{{ $n['clickAction'] }}"
        @keydown.space.prevent="{{ $n['clickAction'] }}">
                        <span class="absolute left-2 top-6 w-1.5 h-1.5 rounded-full bg-brand-500" x-show="!isRead({{ $loop->index }})"></span>
                        <span class="w-9 h-9 rounded-lg {{ $n['color'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $n['icon'] }}" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p
                                    class="text-sm font-medium text-slate-700 leading-snug"
                                    @if($n['popup'] === 'show-birthday-popup') x-text="'Happy Birthday, ' + ($store.citizenSession.account?.firstName ?? 'Resident') + '! 🎉'" @endif
                                >{{ $n['popup'] === 'show-birthday-popup' ? '' : $n['title'] }}</p>
                                @if($cat)
                                    <span class="text-[9px] font-semibold uppercase tracking-wide rounded-full px-1.5 py-0.5 shrink-0 {{ $cat['pill'] }}"><x-ui.t :fil="$cat['fil']" :en="$cat['en']" /></span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $n['body'] }}</p>
                            <p class="text-[11px] text-slate-300 mt-1.5">{{ $n['time'] }}</p>
                        </div>
                        @if($n['pinned'])
                            <span class="text-[10px] font-medium text-slate-300 shrink-0 mt-0.5 whitespace-nowrap"><x-ui.t fil="Awtomatiko" en="Automated" /></span>
                        @else
                            <button
                                type="button"
                                @click.stop="removed.push({{ $loop->index }})"
                                class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 transition-opacity shrink-0 mt-0.5"
                            ><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        @endif
                    </div>
                @endforeach

                <div class="flex flex-col items-center text-center py-12" x-show="visibleCount === 0">
                    <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-4"><i data-lucide="inbox" class="w-7 h-7"></i></span>
                    <p class="text-sm font-medium text-slate-600"><x-ui.t fil="Walang abisong tumutugma dito." en="No notifications here." /></p>
                    <p class="text-xs text-slate-400 mt-0.5"><x-ui.t fil="Subukan ang ibang kategorya sa itaas." en="Try a different category above." /></p>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-layouts.citizen>
