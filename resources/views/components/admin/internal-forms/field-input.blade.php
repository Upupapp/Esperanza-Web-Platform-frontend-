@props(['model' => 'formData'])
{{-- One question of an internal form, for the Alpine field `f` in scope,
     written into the object named by `model` (the form's own answers, or a
     step's part filled by its officer). Every type the office papers need
     (2026-11-03): text, number, date, time, textarea, select, checkbox,
     checkbox-group. Tables are their own step in the wizard. --}}
<div :class="['checkbox-group', 'textarea'].includes(f.type) || f.wide ? 'sm:col-span-2 lg:col-span-3' : ''">
    <template x-if="['text', 'number', 'date', 'time'].includes(f.type) || !f.type">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
            <input :aria-label="f.label" :type="f.type || 'text'" x-model="{{ $model }}[f.key]" :placeholder="f.hint || ''"
                class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
        </div>
    </template>
    <template x-if="f.type === 'textarea'">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
            <textarea :aria-label="f.label" rows="3" x-model="{{ $model }}[f.key]" :placeholder="f.hint || ''"
                class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-ink-body focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
        </div>
    </template>
    <template x-if="f.type === 'select'">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1" x-text="f.label + (f.required ? ' *' : '')"></label>
            <select :aria-label="f.label" x-model="{{ $model }}[f.key]" class="w-full rounded-2xl border-0 surface-pressed py-2.5 px-4 text-sm text-slate-700 focus:bg-white/90 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                <option value="">—</option>
                <template x-for="opt in f.options" :key="opt"><option :value="opt" x-text="opt" :selected="{{ $model }}[f.key] === opt"></option></template>
            </select>
        </div>
    </template>
    <template x-if="f.type === 'checkbox'">
        <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="{{ $model }}[f.key] && 'border-brand-300 bg-brand-50/60 text-brand-700'">
            <input type="checkbox" class="mt-0.5 rounded text-brand-600 focus:ring-brand-200" x-model="{{ $model }}[f.key]">
            <span x-text="f.label + (f.required ? ' *' : '')"></span>
        </label>
    </template>
    <template x-if="f.type === 'checkbox-group'">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-2" x-text="f.label + (f.required ? ' *' : '')"></label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <template x-for="opt in f.options" :key="opt">
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 cursor-pointer hover:border-slate-300 transition-colors" :class="({{ $model }}[f.key] || []).includes(opt) && 'border-brand-300 bg-brand-50/60 text-brand-700'">
                        <input type="checkbox" class="rounded text-brand-600 focus:ring-brand-200" :checked="({{ $model }}[f.key] || []).includes(opt)"
                            @change="{ const l = [...({{ $model }}[f.key] || [])]; const i = l.indexOf(opt); i === -1 ? l.push(opt) : l.splice(i, 1); {{ $model }}[f.key] = l; }">
                        <span x-text="opt"></span>
                    </label>
                </template>
            </div>
        </div>
    </template>
    <p x-show="f.help" x-cloak class="text-[11px] text-ink-muted mt-1" x-text="f.help"></p>
</div>
