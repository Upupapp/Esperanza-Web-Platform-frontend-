{{-- The four states of a data surface, as one component. TAB 06.

     Place inside an x-data="resource(...)" scope (see resources/js/api/resource.js)
     and it renders exactly one of loading / error / empty / loaded:

         <div x-data="resource(() => $api.get('/dokyu/requests'), { initial: @js($requests) })">
             <x-ui.data-panel empty-title="Wala pang kahilingan" empty-title-en="No requests yet">
                 <x-slot:skeleton>
                     <x-ui.skeleton class="h-16 mb-2" />
                     <x-ui.skeleton class="h-16 mb-2" />
                 </x-slot:skeleton>

                 {{-- the loaded state --}}
                 <template x-for="row in items" :key="row.id"> ... </template>
             </x-ui.data-panel>
         </div>

     Why a component rather than four x-show blocks per screen: there are 98
     views and the rules are easy to get subtly wrong. This one enforces them:

       - The loading region carries aria-busy and its skeletons are aria-hidden,
         so a screen reader is told "busy" rather than read a pile of empty divs.
       - `empty` can never be true before a load resolves, so a surface cannot
         claim "no results" while a request is still in flight.
       - The error branch always offers a way forward and never silently renders
         an empty list in place of a failure.
       - Exactly one branch is visible, so a partial failure on one panel leaves
         every other panel on the page intact.

     A surface with build-time fixtures passes them as `initial` and simply
     never leaves the loaded state -- it renders today exactly as it does now,
     and gains the other three the moment something calls load(). --}}

@props([
    'emptyIcon' => 'inbox',
    'emptyTitle' => 'Wala pang laman',
    'emptyTitleEn' => 'Nothing here yet',
    'emptyDescription' => null,
    'emptyDescriptionEn' => null,
    'errorIcon' => 'triangle-alert',
])

<div {{ $attributes }}>
    {{-- LOADING --}}
    <div x-show="loading" x-cloak aria-busy="true" aria-live="polite">
        @isset($skeleton)
            {{ $skeleton }}
        @else
            <x-ui.skeleton class="h-14 mb-2.5" />
            <x-ui.skeleton class="h-14 mb-2.5 opacity-80" />
            <x-ui.skeleton class="h-14 opacity-60" />
        @endisset
    </div>

    {{-- ERROR — never a blank panel, never a silent zero. --}}
    <div x-show="failed" x-cloak>
        <x-ui.error-state
            :icon="$errorIcon"
            messageExpr="message($store.lang.current)"
            retryExpr="retry()"
            :retryable="true" />
    </div>

    {{-- EMPTY — only reachable once a load has actually resolved. --}}
    <div x-show="empty" x-cloak>
        <x-ui.empty-state :icon="$emptyIcon" :title="$emptyTitle" :description="$emptyDescription">
            <span class="sr-only" x-text="$store.lang.current === 'en' ? @js($emptyTitleEn) : @js($emptyTitle)"></span>
        </x-ui.empty-state>
    </div>

    {{-- LOADED --}}
    <div x-show="loaded && !empty" x-cloak>
        {{ $slot }}
    </div>
</div>
