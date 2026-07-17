@php
    $notifications = [
        ['icon' => 'cake', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'Happy Birthday, Maria Fe! 🎉', 'body' => 'The Municipality of Esperanza wishes you good health, happiness, and blessings on your special day.', 'time' => 'Today', 'unread' => true, 'pinned' => true, 'popup' => 'show-birthday-popup'],
        ['icon' => 'check-circle-2', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Barangay Clearance is ready for release', 'body' => 'Claim your document at the Poblacion barangay hall. Bring a valid ID.', 'time' => '18 minutes ago', 'unread' => true, 'pinned' => false, 'popup' => null],
        ['icon' => 'file-text', 'color' => 'text-brand-600 bg-brand-50', 'title' => 'Cedula request under verification', 'body' => 'Your request #DR-2026-2210 is being reviewed by the Treasurer\'s Office.', 'time' => '2 hours ago', 'unread' => true, 'pinned' => false, 'popup' => null],
        ['icon' => 'megaphone', 'color' => 'text-gold-700 bg-gold-50', 'title' => 'New announcement published', 'body' => '"Fiesta ng Esperanza 2026" schedule of activities is now live.', 'time' => 'Yesterday, 4:12 PM', 'unread' => true, 'pinned' => false, 'popup' => 'show-fiesta-popup'],
        ['icon' => 'hand-heart', 'color' => 'text-purple-600 bg-purple-50', 'title' => 'Assistance request approved', 'body' => 'Your medical assistance request #AR-2026-0142 was approved.', 'time' => '2 days ago', 'unread' => true, 'pinned' => false, 'popup' => null],
        ['icon' => 'calendar-days', 'color' => 'text-indigo-600 bg-indigo-50', 'title' => 'Upcoming event reminder', 'body' => 'Barangay Poblacion Medical Mission is happening in 5 days.', 'time' => '3 days ago', 'unread' => false, 'pinned' => false, 'popup' => null],
        ['icon' => 'user-round-check', 'color' => 'text-cyan-600 bg-cyan-50', 'title' => 'Profile verification completed', 'body' => 'Your resident profile has been verified by the MSWDO.', 'time' => 'Jun 30, 2026', 'unread' => false, 'pinned' => false, 'popup' => null],
        ['icon' => 'file-check-2', 'color' => 'text-emerald-600 bg-emerald-50', 'title' => 'Certificate of Residency released', 'body' => 'Request #DR-2026-1998 has been marked as completed.', 'time' => 'Jun 14, 2026', 'unread' => false, 'pinned' => false, 'popup' => null],
    ];
@endphp

<x-layouts.citizen title="Notifications" subtitle="All your account and request updates in one place." active="notifications">
    <div x-data="{ tab: 'all', read: [], removed: [] }" class="animate-fade-up max-w-3xl">

        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit">
                <button @click="tab = 'all'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="tab === 'all' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><x-ui.t fil="Lahat" en="All" /></button>
                <button @click="tab = 'unread'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors" :class="tab === 'unread' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><x-ui.t fil="Hindi Pa Nababasa" en="Unread" /></button>
            </div>
            <button
                @click="read = [...Array({{ count($notifications) }}).keys()]; $dispatch('toast', { message: $store.lang.current === 'en' ? 'All notifications marked as read.' : 'Lahat ng abiso ay minarkahan nang nabasa na.', variant: 'success' })"
                class="text-xs font-medium text-brand-600 hover:underline"
            ><x-ui.t fil="Markahan Lahat na Nabasa" en="Mark all as read" /></button>
        </div>

        <x-ui.card padded="false">
            <div class="divide-y divide-slate-50">
                @foreach($notifications as $n)
                    <div
                        x-show="!removed.includes({{ $loop->index }}) && (tab === 'all' || ({{ $n['unread'] ? 'true' : 'false' }} && !read.includes({{ $loop->index }})))"
                        @if($n['popup']) @click="$dispatch('{{ $n['popup'] }}'); read.includes({{ $loop->index }}) || read.push({{ $loop->index }})" @endif
                        class="group flex items-start gap-3 px-4 py-4 hover:bg-slate-50/70 transition-colors relative {{ $n['popup'] ? 'cursor-pointer' : '' }}"
                    >
                        @if($n['unread'])
                            <span class="absolute left-2 top-6 w-1.5 h-1.5 rounded-full bg-brand-500" x-show="!read.includes({{ $loop->index }})"></span>
                        @endif
                        <span class="w-9 h-9 rounded-lg {{ $n['color'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $n['icon'] }}" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700 leading-snug">{{ $n['title'] }}</p>
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
            </div>
        </x-ui.card>
    </div>
</x-layouts.citizen>
