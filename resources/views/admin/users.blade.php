@php
    $users = [
        ['name' => 'Juan Dela Cruz', 'id' => 'ESP-2024-0011', 'role' => 'Municipal Administrator', 'dept' => 'Office of the Mayor', 'status' => 'Approved'],
        ['name' => 'Corazon Villareal', 'id' => 'ESP-2024-0032', 'role' => 'Department Head', 'dept' => 'MSWDO', 'status' => 'Approved'],
        ['name' => 'Paolo Reyes', 'id' => 'ESP-2025-0087', 'role' => 'ICT Officer', 'dept' => 'ICT Office', 'status' => 'Approved'],
        ['name' => 'Marivic Ong', 'id' => 'ESP-2023-0004', 'role' => 'Department Head', 'dept' => 'Civil Registrar', 'status' => 'Approved'],
        ['name' => 'Leilani Domingo', 'id' => 'ESP-2024-0056', 'role' => 'Department Head', 'dept' => 'Municipal Health Office', 'status' => 'Approved'],
        ['name' => 'Bienvenido Salazar', 'id' => 'ESP-2022-0019', 'role' => 'Encoder', 'dept' => 'Treasurer\'s Office', 'status' => 'Approved'],
        ['name' => 'Angeline Mercado', 'id' => 'ESP-2026-0142', 'role' => 'Encoder', 'dept' => 'MSWDO', 'status' => 'Pending Review'],
        ['name' => 'Ferdinand Cortez', 'id' => 'ESP-2021-0002', 'role' => 'Encoder', 'dept' => 'Treasurer\'s Office', 'status' => 'Archived'],
    ];

    $roles = [
        [
            'name' => 'Municipal Administrator', 'users' => 2, 'color' => 'text-navy-700 bg-navy-50', 'icon' => 'shield-check',
            'desc' => 'Full access to all modules, user management, and system settings.',
            'perms' => ['Manage users & roles', 'Approve document requests', 'Approve assistance requests', 'Publish announcements', 'View audit logs', 'Manage branding'],
        ],
        [
            'name' => 'Department Head', 'users' => 5, 'color' => 'text-brand-700 bg-brand-50', 'icon' => 'briefcase',
            'desc' => 'Manages requests and staff within their assigned department.',
            'perms' => ['Approve document requests', 'Approve assistance requests', 'Publish announcements', 'View department reports'],
        ],
        [
            'name' => 'Encoder', 'users' => 28, 'color' => 'text-emerald-700 bg-emerald-50', 'icon' => 'keyboard',
            'desc' => 'Handles day-to-day data entry and request processing.',
            'perms' => ['Process document requests', 'Process assistance requests', 'Update resident profiles'],
        ],
        [
            'name' => 'Viewer', 'users' => 7, 'color' => 'text-purple-700 bg-purple-50', 'icon' => 'eye',
            'desc' => 'Read-only access for oversight and reporting purposes.',
            'perms' => ['View dashboards', 'View reports & analytics'],
        ],
    ];

    $tab = $tab ?? 'personnel';
@endphp

<x-layouts.admin title="User Management" subtitle="Manage LGU personnel accounts, roles, and access." active="users">
    <div x-data="{ tab: '{{ $tab }}', open: false }" class="animate-fade-up">

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
            <button @click="tab = 'personnel'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'personnel' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="user-cog" class="w-3.5 h-3.5"></i>Personnel</button>
            <button @click="tab = 'roles'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'roles' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i>Roles</button>
        </div>

        <!-- Personnel -->
        <div x-show="tab === 'personnel'">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Total Personnel" value="42" icon="users" color="brand" :delay="0" />
                <x-ui.stat-card label="Active Accounts" value="38" icon="user-round-check" color="green" :delay="40" />
                <x-ui.stat-card label="Pending Approval" value="3" icon="user-round-search" color="orange" :delay="80" />
                <x-ui.stat-card label="Deactivated" value="1" icon="user-x" color="red" :delay="120" />
            </div>

            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" placeholder="Search personnel..." class="w-64 pl-10 pr-4 py-2.5 text-sm rounded-xl bg-white border border-slate-200 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200 shadow-card">
                </div>
                <x-ui.button @click="open = true" icon="user-plus" size="sm">Add Personnel</x-ui.button>
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Personnel</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Employee ID</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Role</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Department</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 flex items-center gap-3">
                                <x-ui.avatar :name="$u['name']" size="sm" />
                                <span class="text-slate-700 font-medium whitespace-nowrap">{{ $u['name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell font-mono text-xs">{{ $u['id'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $u['role'] }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $u['dept'] }}</td>
                            <td class="px-4 py-3"><x-ui.badge :status="$u['status']" /></td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="more-vertical" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>

        <!-- Roles -->
        <div x-show="tab === 'roles'" x-cloak>
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="text-sm text-slate-500">{{ count($roles) }} roles · 42 personnel accounts</p>
                <x-ui.button icon="plus" size="sm">New Role</x-ui.button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($roles as $role)
                    <x-ui.card>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-11 h-11 rounded-xl {{ $role['color'] }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $role['icon'] }}" class="w-5 h-5"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-navy-900">{{ $role['name'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $role['users'] }} personnel</p>
                                </div>
                            </div>
                            <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        </div>
                        <p class="text-xs text-slate-500 mt-3.5">{{ $role['desc'] }}</p>
                        <div class="mt-3.5 pt-3.5 border-t border-slate-100 space-y-2">
                            @foreach($role['perms'] as $perm)
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>{{ $perm }}
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>

        <x-ui.modal title="Add Personnel Account" maxWidth="lg">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input name="first_name" label="First Name" placeholder="Angeline" />
                    <x-ui.input name="last_name" label="Last Name" placeholder="Mercado" />
                </div>
                <x-ui.input name="email" type="email" label="Work Email" icon="mail" placeholder="angeline.mercado@esperanza.gov.ph" />
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                        <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                            <option>Office of the Mayor</option>
                            <option>MSWDO</option>
                            <option>Treasurer's Office</option>
                            <option>Civil Registrar</option>
                            <option>Municipal Health Office</option>
                            <option>ICT Office</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                        <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                            <option>Encoder</option>
                            <option>Department Head</option>
                            <option>Municipal Administrator</option>
                            <option>Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-100 px-3.5 py-2.5">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400 shrink-0 mt-0.5"></i>
                    <p class="text-xs text-slate-500">A temporary password will be sent to the personnel's work email upon account creation.</p>
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                <x-ui.button size="sm" icon="user-plus" @click="open = false; $dispatch('toast', { message: 'Personnel account created — invite sent.', variant: 'success' })">Create Account</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.admin>
