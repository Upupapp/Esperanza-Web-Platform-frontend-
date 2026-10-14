{{-- The officer's own work and late work, on the Dokyu and Tulong queues
     (scope round 2026-10-15). Both are asked of the server (assigned=me,
     overdue=1) and the queue's other filters still apply. The number is the
     overdue requests in the queue as loaded. Reads mineOnly, overdueOnly,
     requests, requestsLoading and loadRequests() from the page. --}}
<div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Show only">
    <button type="button" @click="mineOnly = !mineOnly; loadRequests()" :aria-pressed="mineOnly" class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-[11px] font-medium whitespace-nowrap transition-colors" :class="mineOnly ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-slate-200 bg-slate-50 text-ink-muted hover:text-slate-700'"><i data-lucide="user-check" class="w-3.5 h-3.5"></i>Assigned to me</button>
    <button type="button" @click="overdueOnly = !overdueOnly; loadRequests()" :aria-pressed="overdueOnly" class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-[11px] font-medium whitespace-nowrap transition-colors" :class="overdueOnly ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-ink-muted hover:text-slate-700'">
        <i data-lucide="alarm-clock" class="w-3.5 h-3.5"></i>Overdue
        <span x-show="!requestsLoading && requests.some((r) => r.overdue)" x-cloak class="min-w-[18px] rounded-full bg-rose-600 px-1.5 text-center text-[10px] font-bold leading-[18px] text-white" x-text="requests.filter((r) => r.overdue).length"></span>
    </button>
</div>
