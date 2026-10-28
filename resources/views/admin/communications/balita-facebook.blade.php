{{-- A Balita post's Facebook status (welfare round 2026-10-29), on its card
     and in the review window: Waiting / Posting / Posted - View on Facebook /
     Failed: reason - Retry / Not shared - Post to Facebook. From the row's
     `facebook` ({share, status, permalink, error, posted_at}); nothing shows
     while no Page is connected and the post never went there.
     POST /admin/announcements/{id}/facebook posts now or again (approvers).
     $post: the Alpine expression of the post (a, selected). --}}
<template x-for="fc in [fbChip({{ $post }})].filter(Boolean)" :key="fc.kind">
    <div class="{{ $class ?? '' }} flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px]" data-testid="balita-facebook-status">
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="fc.tone">
            <x-balita.fb-mark class="w-3.5 h-3.5" /><span x-text="fc.label"></span>
        </span>
        <a x-show="fc.kind === 'posted' && fc.permalink" :href="fc.permalink" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline">View on Facebook<i data-lucide="external-link" class="w-3 h-3"></i></a>
        <span x-show="fc.kind === 'failed' && fc.error" class="min-w-0 text-rose-800 break-words" x-text="fc.error"></span>
        <button type="button" x-show="(fc.kind === 'failed' || fc.kind === 'notShared') && fbMayPost({{ $post }})" @click="fbPost({{ $post }})" :disabled="fbBusy === {{ $post }}.id"
            class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline disabled:opacity-50 disabled:no-underline">
            <i :data-lucide="fc.kind === 'failed' ? 'rotate-ccw' : 'send'" class="w-3.5 h-3.5"></i><span x-text="fbBusy === {{ $post }}.id ? 'Sending…' : (fc.kind === 'failed' ? 'Retry' : 'Post to Facebook')"></span>
        </button>
    </div>
</template>
