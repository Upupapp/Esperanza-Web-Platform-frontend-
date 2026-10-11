@props(['list', 'key'])
{{-- Fifty rows at a time (live test 2026-10-12): with a municipality's
     residents imported, every list on the Constituents page drew thousands
     of rows at once and the page took minutes to open. Uses the page's
     paged()/pageOf()/pageCount() helpers; hidden when one page holds all. --}}
<div x-show="pageCount({{ $list }}) > 1" x-cloak class="flex flex-wrap items-center justify-between gap-2 px-1 pt-3 text-xs text-ink-muted">
    <span x-text="'Showing ' + (((pageOf('{{ $key }}', {{ $list }}) - 1) * pageSize) + 1).toLocaleString() + '–' + Math.min(pageOf('{{ $key }}', {{ $list }}) * pageSize, {{ $list }}.length).toLocaleString() + ' of ' + {{ $list }}.length.toLocaleString()"></span>
    <div class="flex items-center gap-1">
        <button type="button" class="px-2.5 py-1 rounded-lg hover:bg-white/70 disabled:opacity-40" :disabled="pageOf('{{ $key }}', {{ $list }}) <= 1" @click="turnPage('{{ $key }}', -1, {{ $list }})">Previous</button>
        <span class="px-1" x-text="'Page ' + pageOf('{{ $key }}', {{ $list }}) + ' of ' + pageCount({{ $list }})"></span>
        <button type="button" class="px-2.5 py-1 rounded-lg hover:bg-white/70 disabled:opacity-40" :disabled="pageOf('{{ $key }}', {{ $list }}) >= pageCount({{ $list }})" @click="turnPage('{{ $key }}', 1, {{ $list }})">Next</button>
    </div>
</div>
