{{-- When an open request is due by its service's processing days, and
     whether it is past that (scope round 2026-10-15: the API's due and
     overdue). row: the Alpine expression of the request; assignee: also who
     has it, on screens too narrow for the queue's Assigned column. --}}
@props(['row' => 'r', 'assignee' => false])
<p x-show="{{ $row }}.due && !terminalStatuses.includes({{ $row }}.status)" x-cloak class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[11px] leading-tight text-ink-muted">
    <span x-show="{{ $row }}.overdue" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-1.5 py-0.5 font-semibold text-rose-700 ring-1 ring-inset ring-rose-200"><i data-lucide="alarm-clock" class="w-3 h-3"></i>Overdue</span>
    <span x-text="'Due ' + new Date({{ $row }}.due + 'T00:00:00+08:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'Asia/Manila' })"></span>
</p>
@if($assignee)
    <p x-show="{{ $row }}.assigned" x-cloak class="2xl:hidden mt-0.5 max-w-[14rem] truncate text-[11px] text-ink-muted" x-text="'Assigned: ' + {{ $row }}.assigned"></p>
@endif
