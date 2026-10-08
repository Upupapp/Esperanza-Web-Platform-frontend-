{{-- The signed-in resident: their LGU-approved photo, or their initials until
     there is one (owner, 2026-10-08: the photo showed on the profile only).
     The photo's link is short-lived; refresh() renews it on every page, and a
     link that has run out falls back to the initials instead of a broken image.
     `fallback` is the initials' colour. --}}
{{-- LANGUAGE (TAB 09): no words of its own; initials and a photo read the same in both languages. --}}
@props(['fallback' => 'clay-blue'])

<span {{ $attributes->merge(['class' => 'relative grid place-items-center shrink-0 rounded-full overflow-hidden font-extrabold ' . $fallback]) }}>
    <span x-text="$store.citizenSession.initials()"></span>
    <img
        x-show="$store.citizenSession.photo()" x-cloak
        :src="$store.citizenSession.photo()"
        x-on:error="$store.citizenSession.photoFailed()"
        alt=""
        class="absolute inset-0 w-full h-full object-cover"
    >
</span>
