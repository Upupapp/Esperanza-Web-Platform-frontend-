{{-- The clay doughnut (design handoff README "Donut cards", STYLE_GUIDE §3.13).
     rows: an Alpine expression for [{ label, value, color? }]. Colours come from
     the chart palette, largest slice first, unless keepColors (a severity scale
     whose colours mean something). Hovering a slice or its legend row lifts it;
     the centre then shows that row's count and share. --}}
@props(['rows', 'totalLabel' => 'Total', 'empty' => 'No data yet.', 'keepColors' => false])

<div x-data="clayDonut({{ $keepColors ? '{ keep: true }' : '' }})" x-effect="draw({{ $rows }})" @mouseleave="hover = null" {{ $attributes->merge(['class' => 'flex flex-col items-center gap-5']) }}>
    <div class="relative w-[200px] max-w-full aspect-square">
        <svg viewBox="0 0 200 200" class="w-full h-full overflow-visible" aria-hidden="true">
            <circle cx="100" cy="100" r="81" fill="none" stroke="rgb(29 71 214 / .08)" stroke-width="31"></circle>
            <g x-ref="segs"></g>
        </svg>
        <div class="absolute inset-[42px] rounded-full surface-pressed grid place-items-center text-center pointer-events-none">
            <div class="px-2 min-w-0">
                <p class="text-[30px] font-extrabold leading-none tracking-[-.02em] text-ink tabular-nums" x-text="(hover !== null && rows[hover] ? rows[hover].value : total).toLocaleString()"></p>
                <p class="mt-1.5 text-[11px] font-semibold leading-tight text-ink-muted" x-text="hover !== null && rows[hover] ? rows[hover].label + ' · ' + pct(rows[hover]) + '%' : @js($totalLabel)">{{ $totalLabel }}</p>
            </div>
        </div>
    </div>
    <div class="w-full space-y-1">
        <template x-for="(row, i) in rows" :key="row.label">
            <div
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all duration-200 cursor-default"
                :class="hover === i ? 'bg-white/80 shadow-[0_1px_3px_rgb(11_23_48/.08)]' : (hover !== null ? 'opacity-50' : '')"
                @mouseenter="hover = i"
            >
                <span class="w-3 h-3 rounded-[4px] shrink-0 shadow-[inset_1px_1px_2px_rgb(255_255_255/.5),inset_-1px_-1px_2px_rgb(0_0_0/.15)]" :style="'background:' + row.color"></span>
                <span class="flex-1 min-w-0 truncate text-[13px] font-semibold text-ink-body" x-text="row.label"></span>
                <span class="text-[13.5px] font-extrabold text-ink tabular-nums" x-text="row.value.toLocaleString()"></span>
                <span class="w-10 text-right text-[11.5px] font-semibold text-ink-faint tabular-nums" x-text="pct(row) + '%'"></span>
            </div>
        </template>
        <p x-show="!rows.length" class="text-sm text-ink-muted text-center py-2">{{ $empty }}</p>
    </div>
</div>
