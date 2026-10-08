{{-- Drop-in replacement for section "B1. KPI Cards" in resources/views/admin/partials/sakuna/tab-command.blade.php
     Same grid, same card size; Alpine bindings and setTab() clicks unchanged. --}}
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
    <x-ui.stat-card layout="compact" tone="rose"   object="flame"    label="Open Incidents"       valueBind="commandBoard.counts.incidents_open"     sublabelBind="commandBoard.counts.incidents_total + ' total'" @click="setTab('incidents')" :delay="0" />
    <x-ui.stat-card layout="compact" tone="teal"   object="building" label="Evac. Centers Open"   valueBind="commandBoard.counts.centers_open"       sublabelBind="commandBoard.centers.filter(c => c.pct >= 75).length + ' near/over capacity'" @click="setTab('evacuation-centers')" :delay="40" />
    <x-ui.stat-card layout="compact" tone="brand"  object="people"   label="Families Sheltered"   valueBind="commandBoard.counts.families_sheltered" sublabelBind="commandBoard.counts.individuals_sheltered + ' individuals'" @click="setTab('evacuees')" :delay="80" />
    <x-ui.stat-card layout="compact" tone="gold"   object="box"      label="Relief Released"      valueBind="commandBoard.counts.relief_released"    @click="setTab('relief')" :delay="120" />
    <x-ui.stat-card layout="compact" tone="violet" object="document" label="Damage Reports"       valueBind="commandBoard.counts.damage_reports"     @click="setTab('damage-assessment')" :delay="160" />
    <x-ui.stat-card layout="compact" tone="amber"  object="bell"     label="Active Public Alerts" valueBind="commandBoard.counts.alerts_live"        @click="setTab('alerts')" :delay="200" />
</div>{{-- /KPI Cards --}}

{{-- Matching loading skeleton (section "A", replace the six-card @for block) --}}
{{--
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
    @for ($i = 0; $i < 6; $i++)
        <div class="surface-glass rounded-[22px] min-h-[136px] p-4 space-y-3 animate-pulse">
            <div class="h-3 bg-slate-100 rounded w-20"></div>
            <div class="h-7 bg-slate-100 rounded-lg w-12"></div>
            <div class="h-3 bg-slate-100 rounded w-16"></div>
        </div>
    @endfor
</div>
--}}
