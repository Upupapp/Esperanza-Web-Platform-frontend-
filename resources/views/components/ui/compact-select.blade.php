{{-- A compact picker (Reports, 2026-11-18): a native <select> in a box of a
     set width whose closed face ends in "…" when the choice is longer than
     the box, while its list still shows every option in full. Chrome and Edge
     ignore text-overflow on a <select>, so the select's own words are
     transparent and the words on top are a span that truncates. The select
     stays the control: the keyboard, the screen reader (it reads the label and
     the whole option) and the native list. The full choice is also the box's
     tooltip. In forced colours the select shows its own words and arrow again
     and the span steps aside.

     id          the select's id
     label       its name, always given; shown above it with labelClass, else
                 for screen readers only
     model       what x-model writes
     shown       an Alpine expression: the words of the chosen option
     change      optional, an x-on:change statement
     The width (class) and x-show go on the outer wrapper. --}}
@props(['id', 'label', 'model', 'shown', 'change' => null, 'labelClass' => 'sr-only'])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1 min-w-0']) }}>
    <label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>
    <div class="group relative" x-bind:title="{{ $shown }}">
        <select id="{{ $id }}" x-model="{{ $model }}" @if ($change) x-on:change="{{ $change }}" @endif
            class="block w-full appearance-none cursor-pointer rounded-2xl border-0 py-2 pl-3 pr-8 text-xs text-transparent surface-pressed-sm outline-none focus:bg-white/90 focus:ring-4 focus:ring-brand-100 [&>option]:text-ink-body forced-colors:appearance-auto forced-colors:text-[CanvasText]">
            {{ $slot }}
        </select>
        <span aria-hidden="true" class="pointer-events-none absolute inset-0 flex items-center gap-1.5 pl-3 pr-2.5 text-xs text-ink-body forced-colors:hidden">
            <span class="min-w-0 flex-1 truncate" x-text="{{ $shown }}"></span>
            <svg class="h-3.5 w-3.5 shrink-0 text-ink-muted transition-colors group-hover:text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
        </span>
    </div>
</div>
