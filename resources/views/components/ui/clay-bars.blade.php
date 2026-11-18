{{-- The pressed-track bar list of the dashboards (18 Nov; it was copied inline
     six times). rows: an Alpine expression for [{ label, value, pct, key?,
     text?, sub?, tag?, tone?, dot? }]: pct is the bar's width (0-100), text
     replaces the number on the right, sub is a line under the bar, tone the
     bar's colour (brand, orange, gold, teal, rose, slate) and dot a coloured
     dot before the label (orange, slate, brand, rose). columns: two columns
     from md up, for a card as wide as the page. --}}
@props(['rows', 'empty' => 'Nothing to show yet.', 'valueSuffix' => '', 'tone' => 'brand', 'columns' => false])

<div {{ $attributes->merge(['class' => $columns ? 'grid gap-x-8 gap-y-3.5 md:grid-cols-2' : 'space-y-3.5']) }}>
    <template x-for="row in ({{ $rows }})" :key="row.key ?? row.label">
        <div>
            <div class="flex items-center justify-between gap-3 mb-1.5">
                <span class="flex items-center gap-2 min-w-0 text-[14px] font-semibold text-ink-body">
                    <span x-show="row.dot" class="w-2 h-2 rounded-full shrink-0" :class="{ orange: 'bg-orange-500', slate: 'bg-slate-300', brand: 'bg-brand-500', rose: 'bg-rose-500' }[row.dot]"></span>
                    <span class="truncate" x-text="row.label"></span>
                    <span x-show="row.tag" class="text-[11px] font-bold uppercase text-ink-faint shrink-0" x-text="row.tag"></span>
                </span>
                <span class="text-sm font-bold text-ink shrink-0 tabular-nums" x-text="row.text ?? (Number(row.value || 0).toLocaleString('en-PH') + @js($valueSuffix))"></span>
            </div>
            <div class="h-3 rounded-full bg-track shadow-pressed-sm p-[2px]">
                <div class="h-full rounded-full bg-gradient-to-r transition-all duration-[600ms] ease-standard"
                     :class="{ brand: 'from-brand-400 to-brand-600', orange: 'from-orange-300 to-orange-500', gold: 'from-gold-400 to-gold-600', teal: 'from-teal-300 to-teal-500', rose: 'from-rose-300 to-rose-500', slate: 'from-slate-300 to-slate-400' }[row.tone || @js($tone)]"
                     :style="'width:' + Math.max(row.value ? 3 : 0, row.pct || 0) + '%'"></div>
            </div>
            <p x-show="row.sub" class="mt-1 text-[12px] text-ink-muted tabular-nums" x-text="row.sub"></p>
        </div>
    </template>
    <p x-show="!({{ $rows }}).length" class="py-6 text-center text-sm text-ink-muted">{{ $empty }}</p>
</div>
