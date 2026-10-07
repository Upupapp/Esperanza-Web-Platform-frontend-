{{-- The fourth state. TAB 06.

     Before the API existed nothing could fail, so nothing handled failure:
     there were 22 skeleton usages, 6 empty states, and zero error states across
     98 views. Every list, table, chart, detail panel and stat card needs all
     four -- loading, empty, error, loaded -- and the error one has to say WHAT
     failed and offer a way to try again.

     Pair it with the ApiError thrown by resources/js/api/client.js:

         <div x-show="error" x-cloak>
             <x-ui.error-state
                 messageExpr="error?.localised($store.lang.current)"
                 retryExpr="load()"
                 :retryable="true" />
         </div>

     Do NOT use this to render an empty list on failure. A caught exception that
     resolves to "no results" is the worst outcome available: the officer
     believes there is no data. --}}

@props([
    'icon' => 'triangle-alert',
    'title' => 'Something went wrong',
    'titleEn' => null,
    // Alpine expression resolving to the human-readable failure message.
    'messageExpr' => null,
    // Static fallback when there is no Alpine state to read from.
    'message' => null,
    // Alpine expression to run when the user presses Try again.
    'retryExpr' => null,
    'retryable' => true,
])

<div
    {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-14 px-6 animate-fade-in']) }}
    role="alert"
    aria-live="assertive"
>
    <div class="w-16 h-16 rounded-[22px] bg-rose-50 shadow-pressed-sm flex items-center justify-center mb-4" aria-hidden="true">
        <i data-lucide="{{ $icon }}" class="w-7 h-7 text-rose-500"></i>
    </div>

    <h3 class="text-sm font-bold text-ink"
        @if($titleEn) x-text="$store.lang.current === 'en' ? @js($titleEn) : @js($title)" @endif
    >{{ $title }}</h3>

    @if($messageExpr)
        <p class="text-sm text-ink-muted mt-1 max-w-sm" x-text="{{ $messageExpr }}"></p>
    @elseif($message)
        <p class="text-sm text-ink-muted mt-1 max-w-sm">{{ $message }}</p>
    @endif

    @if($retryable && $retryExpr)
        <div class="mt-5">
            <x-ui.button variant="secondary" size="sm" icon="rotate-ccw" @click="{{ $retryExpr }}">
                <x-ui.t fil="Subukang muli" en="Try again" />
            </x-ui.button>
        </div>
    @endif

    @isset($slot)
        <div class="mt-3">{{ $slot }}</div>
    @endisset
</div>
