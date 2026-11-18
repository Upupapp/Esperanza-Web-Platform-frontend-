{{-- "Needs your action" (owner, 18 Nov): the work waiting on this account,
     each row counted and scoped by the server (GET /admin/dashboard/inbox,
     loaded once by dashboardHost) and opening the list it counts. Only items
     with a count come; none at all reads "Nothing waiting on you." --}}
<div {{ $attributes }}>
    <x-ui.card class="h-full">
        <div class="flex items-center justify-between gap-3 mb-2">
            <h3 class="text-lg font-extrabold text-ink">Needs your action</h3>
            <span x-show="shownInbox.length" x-cloak class="min-w-[26px] h-[26px] px-2 rounded-full clay-gold grid place-items-center text-[12.5px] font-extrabold tabular-nums" x-text="inboxTotal"></span>
        </div>
        <div x-show="!inboxReady" class="space-y-3 py-1">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center gap-3"><x-ui.skeleton class="h-[38px] w-[38px] rounded-full" /><x-ui.skeleton class="h-4 flex-1" /></div>
            @endfor
        </div>
        <div x-show="inboxReady" x-cloak>
            <template x-for="(item, i) in shownInbox" :key="item.key">
                <a :href="item.href" class="group flex items-center gap-3 px-1 py-2.5 rounded-xl hover:bg-white/55 transition-colors duration-200" :class="i && 'border-t border-brand-600/[.09]'">
                    <span class="w-[38px] h-[38px] rounded-full grid place-items-center shrink-0" :class="inboxTone(item).icon">
                        <i :data-lucide="inboxIcon(item)" class="w-[17px] h-[17px]"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[14px] font-bold text-ink leading-snug" x-text="item.label"></span>
                        <span x-show="item.sublabel" class="block text-[12px] text-ink-muted leading-snug" x-text="item.sublabel"></span>
                    </span>
                    <span class="shrink-0 min-w-[30px] h-[26px] px-2 rounded-full grid place-items-center text-[12.5px] font-extrabold tabular-nums" :class="inboxTone(item).pill" x-text="Number(item.count).toLocaleString('en-PH')"></span>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-ink-faint shrink-0 transition-transform duration-200 group-hover:translate-x-0.5"></i>
                </a>
            </template>
            <div x-show="!shownInbox.length" class="surface-pressed rounded-2xl px-4 py-5 text-center">
                <span class="mx-auto mb-2 w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center"><i data-lucide="circle-check" class="w-5 h-5"></i></span>
                <p class="text-[14.5px] font-bold text-ink">Nothing waiting on you.</p>
                <p class="mt-1 text-[12.5px] text-ink-muted">New work shows here as soon as it comes in.</p>
            </div>
        </div>
    </x-ui.card>
</div>
