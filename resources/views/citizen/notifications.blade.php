@php
    $notifications = [
        ['icon' => 'cake', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'Happy Birthday, Maria Fe! 🎉', 'body' => 'The Municipality of Esperanza wishes you good health, happiness, and blessings on your special day.', 'time' => 'Today', 'unread' => true, 'pinned' => true, 'popup' => 'show-birthday-popup', 'category' => 'account'],
        // Real Cristy Bonghanoy Dokyu/Tulong submissions, sourced from her
        // actual record (config/esperanza_constituents.php) — static/
        // guaranteed entries, not dependent on visiting another page first.
        // See also the "Recent Activity" feed on her admin Constituents
        // Overview tab, which shows the same underlying data.
        ['icon' => 'file-plus', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cristy submitted Barangay Business Clearance', 'body' => 'Your request for a Barangay Business Clearance (market stall permit renewal) has been submitted and is now in the queue. Ref: DR-2026-2233', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2233'],
        ['icon' => 'file-plus', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cristy submitted Barangay Clearance', 'body' => 'Your Barangay Clearance request has been submitted and is now in the queue. Ref: DR-2026-2255', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2255'],
        ['icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Cristy submitted Educational Assistance', 'body' => 'Your Educational Assistance (scholarship program) application has been submitted and is now in the queue. Ref: AR-2026-0224', 'time' => 'Aug 24, 2026', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0224'],
        ['icon' => 'circle-check', 'color' => 'text-green-600 bg-green-50', 'title' => 'Financial Assistance completed', 'body' => 'Cristy\'s livelihood capital support request has been marked Completed. Ref: AR-2026-0163', 'time' => 'May 18, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0163'],
        ['icon' => 'package-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Barangay Clearance is ready for release', 'body' => 'Claim your document at the Poblacion barangay hall. Bring a valid ID. Ref: DR-2026-2187', 'time' => '18 minutes ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2187'],
        ['icon' => 'loader', 'color' => 'text-orange-600 bg-orange-50', 'title' => 'Cedula request under verification', 'body' => 'Your request #DR-2026-2210 is being reviewed by the Treasurer\'s Office.', 'time' => '2 hours ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-2210'],
        ['icon' => 'package-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Educational Assistance is ready for release', 'body' => 'Claim your Educational Assistance at the Mayor\'s Office. Bring a valid ID. Ref: AR-2026-0098', 'time' => '5 hours ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0098'],
        ['icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'New announcement published', 'body' => '"Fiesta ng Esperanza 2026" schedule of activities is now live.', 'time' => 'Yesterday, 4:12 PM', 'unread' => true, 'pinned' => false, 'popup' => 'show-fiesta-popup', 'category' => 'community'],
        ['icon' => 'badge-check', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Medical Assistance request approved', 'body' => 'Your Tulong request #AR-2026-0142 was approved. We\'ll notify you when it\'s ready for release.', 'time' => '2 days ago', 'unread' => true, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0142'],
        ['icon' => 'loader', 'color' => 'text-orange-600 bg-orange-50', 'title' => 'Financial Assistance is being processed', 'body' => 'MSWDO is preparing your livelihood capital support. Ref #AR-2026-0061 — you\'ll be notified once it\'s released.', 'time' => '2 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0061'],
        ['icon' => 'file-x', 'color' => 'text-rose-600 bg-rose-50', 'title' => 'Business Permit request needs attention', 'body' => 'Ref #DR-2026-1655 was rejected — missing Fire Safety Inspection Certificate. Please resubmit.', 'time' => '3 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-1655'],
        ['icon' => 'calendar-days', 'color' => 'text-indigo-600 bg-indigo-50', 'title' => 'Upcoming event reminder', 'body' => 'Barangay Poblacion Medical Mission is happening in 5 days.', 'time' => '3 days ago', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'events'],
        ['icon' => 'user-round-check', 'color' => 'text-cyan-600 bg-cyan-50', 'title' => 'Profile verification completed', 'body' => 'Your resident profile has been verified by the MSWDO.', 'time' => 'Jun 30, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'account'],
        ['icon' => 'file-check', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Certificate of Residency released', 'body' => 'Request #DR-2026-1998 has been marked as completed.', 'time' => 'Jun 14, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'dokyu', 'link' => 'citizen.document-requests', 'ref' => 'DR-2026-1998'],
        ['icon' => 'circle-check', 'color' => 'text-green-600 bg-green-50', 'title' => 'Social Pension enrollment completed', 'body' => 'Your quarterly social pension request #AR-2026-0037 is complete and on schedule.', 'time' => 'Feb 12, 2026', 'unread' => false, 'pinned' => false, 'popup' => null, 'category' => 'tulong', 'link' => 'citizen.assistance-requests', 'ref' => 'AR-2026-0037'],
    ];

    $categoryMeta = [
        'dokyu' => ['fil' => 'Dokyu', 'en' => 'Dokyu', 'pill' => 'bg-brand-50 text-brand-700'],
        'tulong' => ['fil' => 'Tulong', 'en' => 'Tulong', 'pill' => 'bg-purple-50 text-purple-700'],
        'account' => ['fil' => 'Account', 'en' => 'Account', 'pill' => 'bg-gold-50 text-gold-700'],
        'community' => ['fil' => 'Balita', 'en' => 'Community', 'pill' => 'bg-slate-100 text-slate-600'],
        'events' => ['fil' => 'Kaganapan', 'en' => 'Events', 'pill' => 'bg-indigo-50 text-indigo-700'],
    ];

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
                    >
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
                    >
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
