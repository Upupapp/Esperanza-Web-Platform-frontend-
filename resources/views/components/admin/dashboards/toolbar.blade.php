{{-- The head of an area dashboard (Balita, Sakuna, Records, Front desk):
     what it is, the barangay it covers (a select for a municipal account, the
     name alone for a barangay's own), the period, and the report button for
     an account that may open Reports. Reads the areaDashboard x-data. --}}
@props(['eyebrow', 'title' => 'scopeTitle', 'sakunaReports' => false])
<div class="surface-glass rounded-[22px] px-4 py-4 sm:px-5 flex flex-col gap-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11.5px] font-bold uppercase tracking-[.08em] text-ink-muted">{{ $eyebrow }}</p>
            <h3 class="text-xl font-extrabold text-ink leading-tight text-balance" x-text="{{ $title }}"></h3>
            {{ $slot }}
        </div>
        <div class="flex flex-wrap items-center gap-2 min-w-0 max-w-full">
            <label class="sr-only" :for="'area-brgy-' + area">Barangay</label>
            <select :id="'area-brgy-' + area" x-show="choosesBarangay" x-cloak x-model="barangay" @change="load()" class="text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none max-w-full">
                <option value="">All barangays</option>
                <template x-for="b in barangayChoices" :key="b">
                    <option :value="b" x-text="'Brgy. ' + b" :selected="b === barangay"></option>
                </template>
            </select>
            @if ($sakunaReports)
                <x-ui.button :href="route('admin.reports')" icon="download" x-show="$store.session.can('reports')" x-cloak>Export Report</x-ui.button>
                <x-ui.button :href="route('admin.sakuna.reports')" icon="file-chart-column" x-show="!$store.session.can('reports') && $store.session.canPerm('sakuna_reports', 'view')" x-cloak>Open Sakuna Reports</x-ui.button>
            @else
                <x-ui.button :href="route('admin.reports')" icon="download" x-show="$store.session.can('reports')" x-cloak>Export Report</x-ui.button>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-admin.dashboards.period-picker />
        <p class="text-[12.5px] font-semibold text-ink-muted" x-show="data" x-text="periodText"></p>
    </div>
</div>
