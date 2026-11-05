@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-14 px-6 animate-fade-in']) }}>
    <div class="w-16 h-16 rounded-[22px] surface-pressed flex items-center justify-center mb-4" aria-hidden="true">
        <i data-lucide="{{ $icon }}" class="w-7 h-7 text-ink-muted"></i>
    </div>
    <h3 class="text-sm font-bold text-ink">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-ink-muted mt-1 max-w-sm">{{ $description }}</p>
    @endif
    @if(isset($slot) && trim((string) $slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
