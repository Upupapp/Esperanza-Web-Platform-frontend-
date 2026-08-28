{{-- Left-side Canva-style template browser for the Dokyu Edit Document
     editor. Picking a template only ever changes DESIGN (seals, watermark,
     paper color, decorative accent) — it never touches the request's own
     CONTENT (applicant name, age, barangay, purpose, date, reference
     number, official/signatory all keep reading from the exact same
     doc-field/x-text bindings they always have); see applyTemplate()'s own
     comment in document-requests.blade.php for the full reasoning and
     exactly which real reference files each template's seal images came
     from. Collapsible so it never permanently costs the paper workspace.
     Sits outside #dp-print-area (dp-no-print) so it, and its collapse
     toggle, never appear in Print/PDF.

     Expects the surrounding Alpine scope (document-requests.blade.php) to
     expose: editingDocument, dokyuTemplates, activeTemplateKey,
     templateSidebarCollapsed, applyTemplate(key). --}}
<div
    class="dp-no-print shrink-0 transition-[width] duration-150"
    x-show="editingDocument"
    x-cloak
    :class="templateSidebarCollapsed ? 'w-11' : 'w-52'"
>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden sticky top-0">
        <button
            type="button"
            class="w-full flex items-center gap-1.5 px-2.5 py-2.5 border-b border-slate-100 text-[11px] font-semibold text-navy-900 hover:bg-slate-50"
            :class="templateSidebarCollapsed ? 'justify-center' : 'justify-between'"
            @click="templateSidebarCollapsed = !templateSidebarCollapsed"
            title="Templates"
        >
            <span x-show="!templateSidebarCollapsed">Templates</span>
            <i data-lucide="chevron-left" class="w-3.5 h-3.5 shrink-0 transition-transform" :class="templateSidebarCollapsed ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="!templateSidebarCollapsed" class="p-2 space-y-1.5 max-h-[65vh] overflow-y-auto scrollbar-thin">
            <template x-for="tpl in dokyuTemplates" :key="tpl.key">
                <button
                    type="button"
                    class="w-full text-left rounded-lg border p-2 flex items-center gap-2.5 transition-colors"
                    :class="activeTemplateKey === tpl.key ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:bg-slate-50'"
                    @click="applyTemplate(tpl.key)"
                    :title="'Use the ' + tpl.name + ' design'"
                >
                    <img :src="tpl.thumbnail" :alt="tpl.name + ' seal'" class="w-9 h-9 rounded-full object-contain bg-white border border-slate-200 shrink-0" loading="lazy">
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] font-semibold text-navy-900 truncate" x-text="tpl.name"></span>
                        <span class="block text-[10px] text-slate-400 truncate" x-text="tpl.description"></span>
                    </span>
                    <i data-lucide="check" class="w-3.5 h-3.5 text-brand-600 shrink-0" x-show="activeTemplateKey === tpl.key"></i>
                </button>
            </template>
        </div>
    </div>
</div>
