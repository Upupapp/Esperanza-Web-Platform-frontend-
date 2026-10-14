{{-- Track Request's history, Dokyu and Tulong alike (scope round 2026-10-15):
     every status change of the request, newest first, with its date and who
     moved it - "You", or the office (never an officer's own name). From the
     detail's history (GET /citizen/requests/{ref}); logic in
     resources/js/scope-citizen.js (requestTimeline). --}}
<div x-data="requestTimeline()" x-show="entries.length" class="rounded-xl border border-slate-200 p-3.5 mt-3">
    <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-3 flex items-center gap-1.5"><i data-lucide="history" class="w-3.5 h-3.5"></i><x-ui.t fil="Kasaysayan ng kahilingan" en="Request history" /></p>
    <ol class="relative ml-1.5 border-l-2 border-slate-200 space-y-4">
        <template x-for="(e, i) in entries" :key="e.key">
            <li class="relative pl-4">
                <span class="absolute -left-[7px] top-1 w-3 h-3 rounded-full ring-2 ring-white" :class="tone(e.status)[1]" aria-hidden="true"></span>
                <p class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="text-xs font-semibold text-ink" x-text="e.status"></span>
                    <span x-show="i === 0" class="text-[10px] font-bold uppercase tracking-wide text-brand-600"><x-ui.t fil="Kasalukuyan" en="Current" /></span>
                </p>
                <p class="text-[11px] text-ink-muted mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-0.5">
                    <span x-text="fmtDateTime(e.at)"></span>
                    <span class="inline-flex items-center gap-1 min-w-0">
                        <span x-show="e.mine"><i data-lucide="user-round" class="w-3 h-3"></i></span>
                        <span x-show="!e.mine"><i data-lucide="building-2" class="w-3 h-3"></i></span>
                        <span class="truncate" x-text="e.by"></span>
                    </span>
                </p>
                <p x-show="e.note" class="text-[11px] text-ink-body mt-0.5 break-words" x-text="e.note"></p>
            </li>
        </template>
    </ol>
</div>
