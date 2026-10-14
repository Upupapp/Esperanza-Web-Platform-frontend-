{{-- The period a dashboard or a printed report covers: this month, last
     month, the quarter, the year, or two dates. Days are Esperanza's. Reads
     periods, period, from and to from the surrounding x-data. --}}
<div class="flex flex-wrap items-end gap-2">
    <div class="inline-flex flex-wrap p-1 rounded-2xl surface-pressed-sm" role="group" aria-label="Period">
        <template x-for="p in periods" :key="p.key">
            <button type="button" @click="setPeriod(p.key)" :aria-pressed="period === p.key"
                class="px-3 py-1.5 rounded-xl text-[12.5px] transition-all duration-200"
                :class="period === p.key ? 'bg-white/85 text-brand-600 font-bold shadow-[0_1px_2px_rgb(11_23_48/.08)]' : 'font-semibold text-ink-muted hover:text-ink'"
                x-text="p.label"></button>
        </template>
    </div>
    <div x-show="period === 'custom'" x-cloak class="flex flex-wrap items-center gap-2">
        <label class="sr-only" :for="$id('from')">From</label>
        <input :id="$id('from')" type="date" x-model="from" @change="customChanged()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
        <span class="text-xs text-ink-muted">to</span>
        <label class="sr-only" :for="$id('to')">To</label>
        <input :id="$id('to')" type="date" x-model="to" @change="customChanged()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
    </div>
</div>
