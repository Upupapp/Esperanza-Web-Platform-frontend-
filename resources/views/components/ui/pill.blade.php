@props(['color' => 'slate', 'dot' => false])

@php
    // Generic categorical chip — same shape/spacing/typography as x-ui.badge,
    // but for non-workflow labels (type, risk level, tag, module-specific
    // status) that aren't part of the universal status system in x-ui.badge.
    $colors = [
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'green' => 'bg-green-50 text-green-700 ring-green-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
    ];
    $dots = [
        'slate' => 'bg-slate-400', 'blue' => 'bg-blue-500', 'indigo' => 'bg-indigo-500',
        'purple' => 'bg-purple-500', 'amber' => 'bg-amber-500', 'orange' => 'bg-orange-500',
        'emerald' => 'bg-emerald-500', 'green' => 'bg-green-500', 'rose' => 'bg-rose-500',
        'teal' => 'bg-teal-500', 'cyan' => 'bg-cyan-500', 'sky' => 'bg-sky-500', 'brand' => 'bg-brand-500',
    ];
    $classes = $colors[$color] ?? $colors['slate'];
    $dotClass = $dots[$color] ?? $dots['slate'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 min-h-[26px] px-2.5 py-1.5 rounded-full text-xs font-semibold tracking-[0.02em] ring-1 ring-inset whitespace-nowrap $classes"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }} shrink-0"></span>
    @endif
    {{ $slot }}
</span>
