{{-- ============================================================
     FILE: modal-tulong-report.blade.php
     Tulong -> Download Report (owner, 2026-11-20): the office's standard
     Summary Report - the figures, the graphs, the executive summary - for
     Tulong only, over a period, for one program or all, and from every
     record or only those filed in Esperanza or only the imported past ones.
     Logic: tulongReport() in resources/js/dashboards.js. The report itself
     opens in the page's <x-admin.report-sheet> (preview, PDF, Excel, print).
     LANGUAGE (TAB 09): staff site, English only by owner ruling.
     ============================================================ --}}
<div class="contents" x-data="tulongReport()">
<button type="button" x-show="allowed" x-cloak @click="start()"
        title="The office's Summary Report for Tulong, with graphs: preview it, then save it as PDF or Excel, or print it."
        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-2xl px-3 py-2 border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors shrink-0">
    <i data-lucide="file-down" class="w-3.5 h-3.5"></i> Download Report
</button>
<x-ui.modal title="Download the Tulong report" maxWidth="md">
    <div class="space-y-5 text-sm">
        <p class="text-[13px] text-ink-muted leading-relaxed">The office's standard Summary Report: the figures at a glance, a graph over each table, and an executive summary. Preview it first, then save it as PDF or Excel, or print it.</p>

        <div x-show="offices.length > 1" class="space-y-1.5">
            <label for="tr-office" class="block text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Office</label>
            <select id="tr-office" x-model="office" @change="officeChanged()" class="w-full text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                <template x-for="o in offices" :key="o.name"><option :value="o.name" x-text="o.name" :selected="o.name === office"></option></template>
            </select>
        </div>

        <div class="space-y-1.5">
            <p class="text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Period</p>
            <x-admin.dashboards.period-picker />
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <label for="tr-service" class="block text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Program</label>
                <select id="tr-service" x-model="service" class="w-full text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <option value="">All programs</option>
                    <template x-for="o in services" :key="o.key"><option :value="o.key" x-text="o.name" :selected="o.key === service"></option></template>
                </select>
            </div>
            <div class="space-y-1.5">
                <label for="tr-source" class="block text-[11.5px] font-bold uppercase tracking-[.07em] text-ink-muted">Source</label>
                <select id="tr-source" x-model="source" class="w-full text-xs rounded-2xl py-2 px-3 border-0 surface-pressed-sm focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none">
                    <option value="">All records</option>
                    <template x-for="o in sourceOptions" :key="o.value"><option :value="o.value" x-text="o.label" :selected="o.value === source"></option></template>
                </select>
            </div>
        </div>
        <p class="text-[12px] text-ink-muted leading-relaxed">Past records imported from the office's own files count on the day the office gave the assistance, and the report says how many there are. Choose a Source to leave them out or to show only them.</p>
    </div>
    <x-slot:footer>
        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="ghost" size="sm" @click="open = false">Cancel</x-ui.button>
            <x-ui.button size="sm" icon="eye" x-bind:disabled="!office || !from || !to || periodTooLong()" @click="preview()">Preview Report</x-ui.button>
        </div>
    </x-slot:footer>
</x-ui.modal>
</div>
