{{-- Recent activity, on every dashboard view: the audit entries GET
     /admin/dashboard sends (dash, the Overview's x-data, the parent of every
     view). An account that cannot open Audit Logs gets only its own actions
     (activities_scope own, 18 Nov): "Your recent activity", and no View All
     to a page it cannot open. --}}
<div {{ $attributes }}>
    <x-ui.card class="h-full">
        <div class="flex items-center justify-between gap-3 mb-1">
            <h3 class="text-lg font-extrabold text-ink" x-text="activityTitle">Recent activity</h3>
            <a x-show="mayAudit" x-cloak href="{{ route('admin.audit-logs') }}" class="text-[13.5px] font-bold text-brand-600 hover:text-brand-700">View All</a>
        </div>
        <div>
            <template x-for="(activity, i) in (dash?.activities || []).slice(0, 5)" :key="i">
                <div class="flex items-start gap-3 px-1 py-3.5" :class="i && 'border-t border-brand-600/[.09]'">
                    <span class="w-[38px] h-[38px] rounded-full surface-pressed grid place-items-center text-brand-600 shrink-0">
                        <i :data-lucide="activityIcon(activity)" class="w-[17px] h-[17px]"></i>
                    </span>
                    <div class="min-w-0">
                        {{-- In words: a failed sign-in read 'null — auth.login_failed' (QA run 2026-10-05). --}}
                        <p class="text-sm leading-snug"><span class="font-bold text-ink" x-text="activity.actor || 'System'"></span><span class="text-ink-muted"> — </span><span class="font-semibold text-ink-muted" x-text="activity.summary || activity.action"></span></p>
                        <p class="text-xs font-semibold text-brand-600 mt-0.5 break-words" x-text="[moduleLabel(activity.module), activity.target].filter(Boolean).join(' · ')"></p>
                        <p class="text-[11.5px] text-ink-faint mt-0.5" x-text="activity.at ? new Date(activity.at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></p>
                    </div>
                </div>
            </template>
            <p class="py-6 text-center text-sm text-ink-muted" x-show="dash && !(dash.activities || []).length">No recent activity.</p>
            <div x-show="!dash" class="space-y-3 py-2">
                <x-ui.skeleton class="h-4 w-3/4" /><x-ui.skeleton class="h-4 w-2/3" /><x-ui.skeleton class="h-4 w-1/2" />
            </div>
        </div>
    </x-ui.card>
</div>
