{{-- One notification, inside <template x-for="n in ..."> (the bell, dense, and
     the Notifications page). Redesign 28 Nov 2026, after eBPCO's: an unread
     one is a blue-tinted row with a bold title, its time in blue and a blue dot
     at the end; pressing the dot marks it read, and so does opening its link.
     There is no Unread tag and no Mark as read button. The row is a link only
     where it leads somewhere this person may open (store.href(): on the staff
     site, the checks of the page it leads to), and then says where ("Open
     conversation" for a message from a resident). On the resident site in
     both languages. --}}
@props(['portal' => 'admin', 'dense' => false])
@php
    $citizen = $portal === 'citizen';
    $S = $citizen ? '$store.citizenNotifications' : '$store.adminNotifications';
@endphp
<div class="relative flex items-start gap-1 transition-colors {{ $dense ? 'pl-4 pr-2 py-3' : 'pl-4 sm:pl-5 pr-2 sm:pr-3 py-3.5' }}"
     :class="n.unread ? 'bg-brand-100/70 hover:bg-brand-100' : 'hover:bg-white/80'">
    <a :href="{{ $S }}.href(n)" @click="{{ $S }}.open(n){{ $dense ? '; close()' : '' }}"
       class="flex items-start gap-3 flex-1 min-w-0 rounded-xl outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
        <span class="{{ $dense ? 'w-9 h-9 rounded-xl' : 'w-10 h-10 rounded-2xl' }} flex items-center justify-center shrink-0"
              :class="{ brand: 'text-brand-600 bg-white', sky: 'text-sky-600 bg-sky-50', amber: 'text-amber-600 bg-amber-50', rose: 'text-rose-600 bg-rose-50', emerald: 'text-emerald-600 bg-emerald-50', indigo: 'text-indigo-600 bg-indigo-50', purple: 'text-purple-600 bg-purple-50', teal: 'text-teal-600 bg-teal-50' }[n.color] || 'text-brand-600 bg-white'">
            <i :data-lucide="n.icon" class="w-4 h-4"></i>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm leading-snug text-ink {{ $dense ? 'line-clamp-2' : '' }}" :class="n.unread ? 'font-bold' : 'font-medium'" x-text="n.title"></span>
            <span class="block text-xs text-ink-muted mt-0.5 {{ $dense ? 'line-clamp-1' : 'line-clamp-3' }}" x-text="n.body"></span>
            <span class="mt-1 flex flex-wrap items-center gap-x-1.5 text-[11.5px] leading-5">
                <span :class="n.unread ? 'font-semibold text-brand-700' : 'text-ink-faint'" x-text="{{ $S }}.ago(n)"></span>
                <span class="text-ink-faint" aria-hidden="true">·</span>
                <span class="text-ink-faint" x-text="{{ $S }}.kindName(n.portal)"></span>
                <span x-show="{{ $S }}.action(n)" class="ml-auto inline-flex items-center gap-1 font-semibold text-brand-600 whitespace-nowrap"><span x-text="{{ $S }}.action(n)"></span><i data-lucide="arrow-right" class="w-3 h-3"></i></span>
            </span>
        </span>
    </a>
    <button type="button" x-show="n.unread" @click.stop="{{ $S }}.markRead(n.id)"
            @if($citizen) :aria-label="$store.lang.current === 'en' ? 'Mark as read' : 'Markahang nabasa'" :title="$store.lang.current === 'en' ? 'Mark as read' : 'Markahang nabasa'" @else aria-label="Mark as read" title="Mark as read" @endif
            class="shrink-0 self-center w-9 h-9 rounded-full grid place-items-center hover:bg-brand-100 outline-none focus-visible:ring-4 focus-visible:ring-brand-100 transition-colors">
        <span class="w-2.5 h-2.5 rounded-full bg-brand-600" aria-hidden="true"></span>
    </button>
    <span x-show="!n.unread" class="w-9 shrink-0" aria-hidden="true"></span>
</div>
