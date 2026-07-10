@php
    $notifPrefs = [
        ['label' => 'Document request updates', 'desc' => 'Get notified when a request status changes.', 'checked' => true],
        ['label' => 'Assistance request updates', 'desc' => 'Get notified about assistance program progress.', 'checked' => true],
        ['label' => 'Municipal announcements', 'desc' => 'News and advisories from the municipality.', 'checked' => true],
        ['label' => 'Event reminders', 'desc' => 'Reminders before events you\'re following.', 'checked' => false],
        ['label' => 'SMS notifications', 'desc' => 'Receive critical updates via SMS as backup.', 'checked' => false],
    ];
@endphp

<x-layouts.citizen title="Settings" subtitle="Manage your account preferences." active="settings">
    <div x-data="{ tab: 'notifications' }" class="animate-fade-up grid grid-cols-1 lg:grid-cols-4 gap-5">

        <div class="lg:col-span-1">
            <x-ui.card padded="false" class="p-2">
                <nav class="space-y-0.5">
                    @foreach(['notifications' => ['bell', 'Notifications'], 'security' => ['shield', 'Security'], 'privacy' => ['lock', 'Privacy'], 'preferences' => ['sliders-horizontal', 'Preferences']] as $key => [$icon, $label])
                        <button
                            @click="tab = '{{ $key }}'"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            :class="tab === '{{ $key }}' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50'"
                        >
                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>{{ $label }}
                        </button>
                    @endforeach
                </nav>
            </x-ui.card>
        </div>

        <div class="lg:col-span-3">
            <div x-show="tab === 'notifications'">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-1">Notification Preferences</h3>
                    <p class="text-xs text-slate-400 mb-4">Choose what updates you want to receive.</p>
                    <div class="divide-y divide-slate-50" x-data="{}">
                        @foreach($notifPrefs as $pref)
                            <label class="flex items-center justify-between gap-4 py-3.5 cursor-pointer">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700">{{ $pref['label'] }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $pref['desc'] }}</p>
                                </div>
                                <div class="relative shrink-0" x-data="{ on: {{ $pref['checked'] ? 'true' : 'false' }} }">
                                    <button
                                        @click="on = !on"
                                        type="button"
                                        class="w-10 h-6 rounded-full transition-colors duration-200 relative"
                                        :class="on ? 'bg-brand-600' : 'bg-slate-200'"
                                    >
                                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200" :class="on && 'translate-x-4'"></span>
                                    </button>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div x-show="tab === 'security'" x-cloak class="space-y-5">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">Change Password</h3>
                    <div class="space-y-4 max-w-md">
                        <x-ui.input name="current_password" type="password" label="Current Password" icon="lock" placeholder="••••••••" />
                        <x-ui.input name="new_password" type="password" label="New Password" icon="lock" placeholder="••••••••" />
                        <x-ui.input name="confirm_password" type="password" label="Confirm New Password" icon="lock" placeholder="••••••••" />
                        <x-ui.button icon="check" @click="$dispatch('toast', { message: 'Password updated — this is a frontend preview, no data is saved.', variant: 'success' })">Update Password</x-ui.button>
                    </div>
                </x-ui.card>
                <x-ui.card class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-navy-900">Two-Factor Authentication</p>
                        <p class="text-xs text-slate-400 mt-0.5">Add an extra layer of security to your account.</p>
                    </div>
                    <x-ui.button variant="secondary" size="sm">Enable 2FA</x-ui.button>
                </x-ui.card>
            </div>

            <div x-show="tab === 'privacy'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-1">Data Privacy</h3>
                    <p class="text-xs text-slate-400 mb-4">Manage how your resident data is used across the platform, in line with the Data Privacy Act of 2012.</p>
                    <div class="flex items-start gap-3 rounded-xl bg-slate-50 border border-slate-100 p-4">
                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-slate-500 leading-relaxed">The Municipality of Esperanza collects and processes your personal data solely for the purpose of delivering public services. Your data is not shared with third parties without consent.</p>
                    </div>
                    <x-ui.button variant="secondary" size="sm" icon="download" class="mt-4">Request My Data</x-ui.button>
                </x-ui.card>
            </div>

            <div x-show="tab === 'preferences'" x-cloak>
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-navy-900 mb-4">General Preferences</h3>
                    <div class="space-y-4 max-w-md">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Language</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>English</option>
                                <option>Filipino</option>
                                <option>Bicolano</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Time Zone</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>(GMT+8:00) Manila</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.citizen>
