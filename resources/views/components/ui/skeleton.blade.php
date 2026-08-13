@props([])

{{-- Purely decorative placeholder — hidden from assistive tech. The region
     that contains one or more of these should carry aria-busy="true" so
     screen readers know content is still loading. --}}
<div aria-hidden="true" {{ $attributes->merge(['class' => 'skeleton']) }}></div>
