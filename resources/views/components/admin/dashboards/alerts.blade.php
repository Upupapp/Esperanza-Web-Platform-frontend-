{{-- Sakuna alerts in effect here (GET /admin/dashboard sakuna.alerts: not
     expired, aimed at everyone or this barangay). rows: an Alpine expression,
     the Overview's list by default. --}}
@props(['rows' => '(dash?.sakuna?.alerts || [])'])
<div {{ $attributes }}>
    <x-ui.card class="h-full">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-extrabold text-ink">Sakuna alerts</h3>
            <span class="min-w-[26px] h-[26px] px-2 rounded-full clay-gold grid place-items-center text-[12.5px] font-extrabold" x-text="{{ $rows }}.length"></span>
        </div>
        <div class="space-y-3">
            <template x-for="alert in {{ $rows }}" :key="alert.ref">
                <a href="{{ route('admin.sakuna.alerts') }}" class="flex items-start gap-3 rounded-[18px] bg-white/55 border border-white/80 px-3.5 py-3 shadow-glass-sm hover:-translate-y-0.5 transition-transform duration-200">
                    <span class="w-[42px] h-[42px] rounded-2xl clay-gold-light grid place-items-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-5 h-5"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-bold text-ink leading-snug truncate" x-text="alert.title?.en"></p>
                        <p class="text-[12.5px] text-ink-muted" x-text="alert.type + ' · ' + alert.level"></p>
                        <p class="text-[11.5px] text-ink-faint mt-0.5" x-text="alert.published_at ? new Date(alert.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''"></p>
                    </div>
                    <span class="shrink-0 surface-pressed-sm rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-[.04em] text-ink-body" x-text="alert.level"></span>
                </a>
            </template>
            <p class="py-6 text-center text-sm text-ink-muted" x-show="!{{ $rows }}.length">No alerts in effect.</p>
            <a href="{{ route('admin.sakuna.alerts') }}" class="inline-block text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All Alerts</a>
        </div>
    </x-ui.card>
</div>
