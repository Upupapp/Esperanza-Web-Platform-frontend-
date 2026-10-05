@php
    // The module/submodule HIERARCHY (names, icons, which actions exist per
    // submodule) is structural RBAC catalog metadata that rarely changes —
    // kept from the contract config, same reasoning as the barangay-name
    // list elsewhere. What is NOT kept from config anymore: which roles
    // exist, who is assigned to them, and what each role is actually
    // granted — all three now come from GET /admin/roles and GET /admin/users
    // (production-readiness programme, 2026-09-25). The role list used to be
    // config('esperanza_rbac.roles') with a hand-maintained `users` count
    // baked in ("46 personnel") that could never match reality.
    $permissionModules = config('esperanza_rbac.permission_modules');
    $tab = $tab ?? 'personnel';
@endphp

<x-layouts.admin title="User Management" subtitle="Manage LGU personnel accounts, roles, and access." active="users">
    <div x-data="{ tab: '{{ $tab }}' }" class="animate-fade-up">
    {{--
        Note on structure: each tab below (and the Add Personnel wizard
        inside the Personnel tab) owns a fully independent x-data scope and
        fetches its own copy of /admin/roles (and /barangays, where used)
        rather than sharing one fetch through a parent scope. Alpine's
        nested-x-data method/getter bodies do not reliably see an ancestor
        scope's reactive properties through `this` the way inline template
        expressions (x-show, x-text) do -- only directive expressions walk
        the merged ancestor scope; a method's own function body does not.
        Each tab's role list is small (~9 rows), so the duplicate fetches
        cost nothing that matters, and every scope stays simple to reason
        about in isolation (production-readiness programme, 2026-09-25).
    --}}

        <div x-show="!$store.session.can('user_management')" x-cloak>
            <x-admin.access-restricted module="User Management" icon="user-cog" />
        </div>

        <div x-show="$store.session.can('user_management')" x-cloak>

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4">
            <button @click="tab = 'personnel'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'personnel' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="user-cog" class="w-3.5 h-3.5"></i>Personnel</button>
            <button @click="tab = 'roles'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'roles' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i>Roles</button>
            <button @click="tab = 'permissions'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'permissions' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="key-round" class="w-3.5 h-3.5"></i>Permissions</button>
            <button @click="tab = 'matrix'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5" :class="tab === 'matrix' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="table-properties" class="w-3.5 h-3.5"></i>Dokyu &amp; Tulong Matrix</button>
        </div>

        {{-- ============================= PERSONNEL ============================= --}}
        <div
            x-data="{
                open: false,
                search: '',
                users: [],
                usersLoading: true,
                usersFailed: false,
                statusStyles: {
                    'Approved': ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
                    'Pending Review': ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
                    'Rejected': ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
                    'Archived': ['bg-slate-100 text-slate-500 ring-slate-200', 'bg-slate-300'],
                },
                roleNamesByKey: {},
                roleName(key) { return this.roleNamesByKey[key] || key; },
                // Edit and reset password: the list only offered Archive, though the
                // server takes PUT /admin/users/{id} and POST .../reset-password.
                editOpen: false,
                edit: null,
                editRoles: [],
                editBarangays: [],
                editDepartments: [],
                editSaving: false,
                editError: '',
                tempPassword: '',
                resetting: false,
                resetMessage: '',
                async openEdit(u) {
                    this.edit = { id: u.id, name: u.name, email: u.email || '', roleKey: u.roleKey, dept: u.dept, scope: u.scope, status: u.status, original: { ...u } };
                    this.editError = ''; this.tempPassword = ''; this.resetMessage = '';
                    this.editOpen = true;
                    if (!this.editRoles.length) {
                        try {
                            const [rolesResult, barangaysResult, directoryResult] = await Promise.all([$api.get('/admin/roles'), $api.get('/barangays'), $api.get('/directory')]);
                            this.editRoles = (rolesResult.data || []).filter((r) => r.key !== 'citizen');
                            this.editBarangays = (barangaysResult.data || []).map((b) => b.name);
                            this.editDepartments = [...new Set((directoryResult.data || []).map((o) => o.office))].sort();
                        } catch { /* the current values stay selectable below */ }
                    }
                },
                get editDeptOptions() { return [...new Set([this.edit?.dept, ...this.editDepartments].filter(Boolean))]; },
                async saveEdit() {
                    if (this.editSaving || !this.edit) return;
                    const was = this.edit.original;
                    const changes = {};
                    if (this.edit.name.trim() !== was.name) changes.name = this.edit.name.trim();
                    if (this.edit.email.trim().toLowerCase() !== (was.email || '')) changes.email = this.edit.email.trim();
                    if (this.edit.roleKey !== was.roleKey) changes.role_key = this.edit.roleKey;
                    if (this.edit.dept !== was.dept) changes.dept = this.edit.dept;
                    if (this.edit.scope !== was.scope) changes.scope = this.edit.scope;
                    if (this.edit.status !== was.status) changes.status = this.edit.status;
                    if (!Object.keys(changes).length) { this.editOpen = false; return; }
                    this.editSaving = true; this.editError = '';
                    try {
                        await $api.put('/admin/users/' + this.edit.id, changes);
                        this.editOpen = false;
                        this.$dispatch('toast', { message: 'Saved ' + (changes.name || was.name) + '.', variant: 'success' });
                        await this.loadUsers();
                    } catch (err) {
                        this.editError = err.localised ? err.localised('en') : 'Could not save the changes.';
                    } finally { this.editSaving = false; }
                },
                async resetPassword() {
                    if (this.resetting || !this.edit || this.tempPassword.length < 10) return;
                    this.resetting = true; this.editError = ''; this.resetMessage = '';
                    try {
                        await $api.post('/admin/users/' + this.edit.id + '/reset-password', { password: this.tempPassword });
                        this.tempPassword = '';
                        this.resetMessage = 'Temporary password set and every session signed out. Give it to the officer yourself; nothing is emailed.';
                    } catch (err) {
                        this.editError = err.localised ? err.localised('en') : 'Could not set the password.';
                    } finally { this.resetting = false; }
                },
                async loadUsers() {
                    this.usersLoading = true;
                    this.usersFailed = false;
                    try {
                        const [usersResult, rolesResult] = await Promise.all([
                            $api.getAll('/admin/users'),
                            $api.get('/admin/roles').catch(() => ({ data: [] })),
                        ]);
                        this.roleNamesByKey = Object.fromEntries((rolesResult.data || []).map((r) => [r.key, r.name]));
                        this.users = (usersResult.data || []).map((u) => ({
                            name: u.name, id: u.employee_id, roleKey: u.role, role: this.roleName(u.role),
                            dept: u.dept, status: u.status, scope: u.scope, email: u.email,
                        }));
                    } catch { this.usersFailed = true; } finally { this.usersLoading = false; }
                },
                get filteredUsers() {
                    const q = this.search.trim().toLowerCase();
                    return this.users.filter(u => q === '' || u.name.toLowerCase().includes(q) || u.id.toLowerCase().includes(q) || u.role.toLowerCase().includes(q));
                },
                get totalCount() { return this.users.length; },
                get activeCount() { return this.users.filter(u => u.status === 'Approved').length; },
                get pendingCount() { return this.users.filter(u => u.status === 'Pending Review').length; },
                get archivedCount() { return this.users.filter(u => u.status === 'Archived' || u.status === 'Rejected').length; },
            }"
            x-init="loadUsers()"
            @personnel-created.window="loadUsers()"
            x-show="tab === 'personnel'"
        >
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Total Personnel" valueBind="totalCount" icon="users" color="brand" :delay="0" />
                <x-ui.stat-card label="Active Accounts" valueBind="activeCount" icon="user-round-check" color="green" :delay="40" />
                <x-ui.stat-card label="Pending Approval" valueBind="pendingCount" icon="user-round-search" color="orange" :delay="80" />
                <x-ui.stat-card label="Deactivated" valueBind="archivedCount" icon="user-x" color="red" :delay="120" />
            </div>

            <div class="bg-white border border-slate-100 rounded-2xl shadow-card p-3 mb-4 flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input aria-label="Search personnel" type="text" x-model="search" placeholder="Search personnel..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                </div>
                <p class="text-[11px] text-slate-500 shrink-0" x-text="filteredUsers.length + ' of ' + users.length + ' personnel'"></p>
                <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>
                <x-ui.button @click="open = true" icon="user-plus" size="sm" class="shrink-0">Add Personnel</x-ui.button>
            </div>

            <div x-show="usersLoading" class="space-y-2"><x-ui.skeleton class="h-12" /><x-ui.skeleton class="h-12 opacity-80" /><x-ui.skeleton class="h-12 opacity-60" /></div>
            <div x-show="usersFailed" x-cloak><x-ui.error-state messageExpr="'Could not load personnel.'" retryExpr="loadUsers()" :retryable="true" /></div>

            <div x-show="!usersLoading && !usersFailed">
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Personnel</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Employee ID</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Role</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell">Department</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Access Scope</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="u in filteredUsers" :key="u.id">
                        <tr class="hover:bg-slate-50/70 transition-colors group">
                            <td class="px-4 py-2.5 flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold shrink-0 text-xs ring-2 ring-white shadow-sm" x-text="u.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></span>
                                <span class="text-slate-700 font-medium whitespace-nowrap group-hover:text-brand-600 transition-colors" x-text="u.name"></span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell font-mono text-xs" x-text="u.id"></td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="u.role"></td>
                            <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="u.dept"></td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span
                                    class="inline-flex items-center gap-1.5 text-[11px] font-medium rounded-full px-2.5 py-1 whitespace-nowrap border"
                                    :class="u.scope === 'Municipal' ? 'text-brand-700 bg-brand-50 border-brand-100' : 'text-purple-700 bg-purple-50 border-purple-100'"
                                >
                                    <i :data-lucide="u.scope === 'Municipal' ? 'landmark' : 'map-pin'" class="w-3 h-3"></i>
                                    <span x-text="u.scope === 'Municipal' ? 'Municipality-wide' : 'Brgy. ' + u.scope"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap" :class="(statusStyles[u.status] || statusStyles['Approved'])[0]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="(statusStyles[u.status] || statusStyles['Approved'])[1]"></span>
                                    <span x-text="u.status"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="openEdit(u)"
                                    class="text-slate-500 hover:text-brand-600 transition-colors mr-2" title="Edit or reset password"
                                    :aria-label="'Edit ' + u.name"
                                ><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                <button
                                    x-show="u.status !== 'Archived'"
                                    @click="if (confirm('Archive ' + u.name + '\'s account? They will be signed out everywhere and can no longer log in.')) { $api.post('/admin/users/' + u.id + '/archive').then(() => loadUsers()).catch((e) => $dispatch('toast', { message: e.localised ? e.localised('en') : 'Could not archive.', variant: 'error' })); }"
                                    class="text-slate-500 hover:text-rose-600 transition-colors" title="Archive"
                                ><i data-lucide="user-x" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>

            <div class="flex flex-col items-center text-center py-10 bg-white border border-slate-100 rounded-2xl mt-3" x-show="filteredUsers.length === 0">
                <span class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mb-4"><i data-lucide="search-x" class="w-7 h-7"></i></span>
                <p class="text-sm font-medium text-slate-600">No personnel match this search.</p>
            </div>
            </div>

            {{-- ---------------- Edit personnel / reset password ---------------- --}}
            <div x-data="{ get open() { return editOpen; }, set open(v) { editOpen = v; } }">
                <x-ui.modal title="Edit Personnel Account" maxWidth="md">
                    <template x-if="edit">
                        <div class="space-y-4">
                            <p class="text-xs text-slate-500">Employee ID <span class="font-mono text-slate-700" x-text="edit.id"></span></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <x-ui.input name="edit_name" label="Name" x-model="edit.name" />
                                <x-ui.input name="edit_email" type="email" label="Work Email" x-model="edit.email" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="edit_role" class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                                    <select id="edit_role" x-model="edit.roleKey" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                        <option :value="edit.original.roleKey" x-text="roleName(edit.original.roleKey)"></option>
                                        <template x-for="r in editRoles.filter((r) => r.key !== edit.original.roleKey)" :key="'edit-role-' + r.key"><option :value="r.key" x-text="r.name"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_dept" class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                                    <select id="edit_dept" x-model="edit.dept" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                        <template x-for="d in editDeptOptions" :key="'edit-dept-' + d"><option :value="d" x-text="d"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="edit_scope" class="block text-sm font-medium text-slate-700 mb-1.5">Scope</label>
                                    <select id="edit_scope" x-model="edit.scope" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                        <option value="Municipal">Municipality-wide</option>
                                        <template x-for="b in [...new Set([edit.original.scope, ...editBarangays])].filter((b) => b && b !== 'Municipal')" :key="'edit-scope-' + b"><option :value="b" x-text="'Brgy. ' + b"></option></template>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_status" class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                                    <select id="edit_status" x-model="edit.status" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                        <option value="Approved">Active</option>
                                        <option value="Archived">Archived (cannot sign in)</option>
                                        <option x-show="edit.original.status === 'Pending Review'" value="Pending Review">Pending Review</option>
                                    </select>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2.5">
                                <p class="text-sm font-medium text-slate-700">Reset password</p>
                                <p class="text-xs text-slate-500">Sets a temporary password and signs the officer out everywhere. At least 10 characters with upper and lower case, a number and a symbol.</p>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <div class="flex-1"><x-ui.input name="edit_temp_password" type="password" label="" aria-label="Temporary password" placeholder="Temporary password" x-model="tempPassword" autocomplete="new-password" /></div>
                                    <x-ui.button variant="secondary" size="sm" icon="key-round" x-bind:disabled="resetting || tempPassword.length < 10" @click="resetPassword()">
                                        <span x-text="resetting ? 'Setting…' : 'Set password'"></span>
                                    </x-ui.button>
                                </div>
                                <p class="text-xs text-emerald-700" x-show="resetMessage" x-text="resetMessage"></p>
                            </div>
                            <p class="text-xs text-rose-600" x-show="editError" x-text="editError" role="alert"></p>
                        </div>
                    </template>
                    <x-slot:footer>
                        <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                        <x-ui.button size="sm" icon="save" x-bind:disabled="editSaving" @click="saveEdit()">
                            <span x-text="editSaving ? 'Saving…' : 'Save Changes'"></span>
                        </x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            </div>

            {{-- ---------------- Add Personnel wizard ---------------- --}}
            <div
                x-data="{
                    step: 1,
                    modules: @js($permissionModules),
                    roles: [],
                    barangays: [],
                    departments: [],
                    submitting: false,
                    createError: null,
                    form: { firstName: '', lastName: '', email: '', password: '', dept: '', roleKey: 'department_staff', scope: 'municipal', barangay: '' },
                    get selectedRole() { return this.roles.find(r => r.key === this.form.roleKey); },
                    grantedCount(role) { return Object.values(role.permissions || {}).reduce((n, arr) => n + arr.length, 0); },
                    accessedModules(role) {
                        return this.modules.filter(m => m.submodules.some(s => (role?.permissions?.[s.key] || []).length > 0));
                    },
                    next() { if (this.step < 4) this.step++; },
                    back() { if (this.step > 1) this.step--; },
                    reset() { this.step = 1; this.createError = null; this.form = { firstName: '', lastName: '', email: '', password: '', dept: this.departments[0] || '', roleKey: 'department_staff', scope: 'municipal', barangay: this.barangays[0] || '' }; },
                    async loadOptions() {
                        try {
                            const [rolesResult, barangaysResult, directoryResult] = await Promise.all([
                                $api.get('/admin/roles'),
                                $api.get('/barangays'),
                                $api.get('/directory'),
                            ]);
                            this.roles = (rolesResult.data || []).filter((r) => r.key !== 'citizen');
                            this.barangays = (barangaysResult.data || []).map((b) => b.name);
                            this.departments = [...new Set((directoryResult.data || []).map((o) => o.office))].sort();
                        } catch { /* the modal's own fields just stay empty; nothing to submit without them anyway */ }
                        if (!this.form.dept) this.form.dept = this.departments[0] || '';
                        if (!this.form.barangay) this.form.barangay = this.barangays[0] || '';
                    },
                    async createPersonnel() {
                        if (this.submitting) return;
                        this.submitting = true;
                        this.createError = null;
                        try {
                            await $api.post('/admin/users', {
                                name: (this.form.firstName + ' ' + this.form.lastName).trim(),
                                email: this.form.email,
                                password: this.form.password,
                                role_key: this.form.roleKey,
                                dept: this.form.dept,
                                scope: this.form.scope === 'municipal' ? 'Municipal' : this.form.barangay,
                            });
                            window.dispatchEvent(new CustomEvent('personnel-created'));
                            this.$dispatch('toast', { message: 'Personnel account created.', variant: 'success' });
                        } catch (err) {
                            this.createError = err.localised ? err.localised('en') : 'Could not create the account.';
                        } finally {
                            this.submitting = false;
                        }
                    },
                }"
                x-init="loadOptions(); $watch('open', v => { if (!v) reset(); })"
            >
                <x-ui.modal title="Add Personnel Account" maxWidth="3xl" :fullscreen="true">
                    <div class="space-y-4">
                        <!-- Stepper -->
                        <div class="flex items-center gap-2 mb-1">
                            <template x-for="(label, i) in ['Account Info', 'Assign Role', 'Access Scope', 'Review Access']" :key="i">
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="flex items-center gap-2 flex-1">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[10.5px] font-semibold shrink-0 transition-colors duration-[160ms] ease-standard"
                                            :class="step > i + 1 ? 'bg-emerald-500 text-white' : (step === i + 1 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500')"
                                        >
                                            <i data-lucide="check" class="w-3 h-3" x-show="step > i + 1"></i>
                                            <span x-show="step <= i + 1" x-text="i + 1"></span>
                                        </span>
                                        <span class="text-[11px] font-medium hidden sm:inline" :class="step === i + 1 ? 'text-navy-900' : 'text-slate-500'" x-text="label"></span>
                                    </div>
                                    <div class="h-px flex-1 bg-slate-100" x-show="i < 3"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Step 1: Account Info -->
                        <div x-show="step === 1" x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <x-ui.input name="first_name" label="First Name" placeholder="Angeline" x-model="form.firstName" />
                                <x-ui.input name="last_name" label="Last Name" placeholder="Mercado" x-model="form.lastName" />
                            </div>
                            <x-ui.input name="email" type="email" label="Work Email" icon="mail" placeholder="angeline.mercado@esperanza.gov.ph" x-model="form.email" />
                            <x-ui.input name="password" type="password" label="Temporary Password" icon="lock" placeholder="At least 10 characters" x-model="form.password" />
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="department">Department</label>
                                <select id="department" x-model="form.dept" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <template x-for="d in departments" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Step 2: Assign Role -->
                        <div x-show="step === 2" x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0">
                            <p class="text-xs text-slate-500 mb-3">Select the role that best matches this personnel's responsibilities. Permissions are inherited from the role and can be reviewed in the next step.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-80 overflow-y-auto scrollbar-thin pr-1 -mr-1">
                                <template x-for="role in roles" :key="role.key">
                                    <button
                                        type="button" @click="form.roleKey = role.key"
                                        class="text-left rounded-xl border p-3 transition-all duration-[160ms] ease-standard"
                                        :class="form.roleKey === role.key ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <div class="flex items-center gap-2.5 mb-1.5">
                                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="role.color">
                                                <i :data-lucide="role.icon" class="w-4 h-4"></i>
                                            </span>
                                            <p class="text-xs font-semibold text-navy-900" x-text="role.name"></p>
                                        </div>
                                        <p class="text-[11px] text-slate-500 leading-snug line-clamp-2" x-text="role.desc"></p>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Step 3: Access Scope -->
                        <div x-show="step === 3" x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-3.5">
                            <p class="text-xs text-slate-500 mb-1">Define the geographic boundary of this account's access — a Municipal account can act across the whole LGU, while a Barangay account only sees that barangay's data.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <button
                                    type="button" @click="form.scope = 'municipal'"
                                    class="text-left rounded-xl border p-3.5 transition-all duration-[160ms] ease-standard"
                                    :class="form.scope === 'municipal' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-2"><i data-lucide="landmark" class="w-4.5 h-4.5"></i></span>
                                    <p class="text-xs font-semibold text-navy-900">Municipal</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Municipality-wide access across all 20 barangays.</p>
                                </button>
                                <button
                                    type="button" @click="form.scope = 'barangay'"
                                    class="text-left rounded-xl border p-3.5 transition-all duration-[160ms] ease-standard"
                                    :class="form.scope === 'barangay' ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-100' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <span class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-2"><i data-lucide="map-pin" class="w-4.5 h-4.5"></i></span>
                                    <p class="text-xs font-semibold text-navy-900">Barangay</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Restricted to the data of one specific barangay.</p>
                                </button>
                            </div>

                            <div x-show="form.scope === 'barangay'" x-cloak x-transition:enter="transition duration-[160ms] ease-standard" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="barangay-8">Barangay</label>
                                <select id="barangay-8" x-model="form.barangay" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                                    <template x-for="b in barangays" :key="b">
                                        <option :value="b" x-text="'Brgy. ' + b"></option>
                                    </template>
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1.5">This account will only be able to view and manage data belonging to Brgy. <span x-text="form.barangay" class="font-medium text-slate-600"></span>.</p>
                            </div>
                        </div>

                        <!-- Step 4: Review Access -->
                        <div x-show="step === 4" x-transition:enter="transition duration-[220ms] ease-standard" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-3.5">
                            <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3.5">
                                <x-ui.avatar name="New Personnel" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-700" x-text="(form.firstName || 'First') + ' ' + (form.lastName || 'Last')"></p>
                                    <p class="text-xs text-slate-500" x-text="form.dept + ' · ' + (selectedRole ? selectedRole.name : '')"></p>
                                </div>
                                <span class="text-xs font-semibold text-brand-600 shrink-0" x-text="selectedRole ? grantedCount(selectedRole) + ' permissions' : ''"></span>
                            </div>

                            <div class="flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5" :class="form.scope === 'municipal' ? 'border-brand-100 bg-brand-50/60' : 'border-purple-100 bg-purple-50/60'">
                                <i :data-lucide="form.scope === 'municipal' ? 'landmark' : 'map-pin'" class="w-4 h-4 shrink-0" :class="form.scope === 'municipal' ? 'text-brand-600' : 'text-purple-600'"></i>
                                <p class="text-xs font-medium" :class="form.scope === 'municipal' ? 'text-brand-700' : 'text-purple-700'">
                                    <span x-show="form.scope === 'municipal'">Municipality-wide access — can view and manage data across all 20 barangays.</span>
                                    <span x-show="form.scope === 'barangay'">Barangay-restricted access — will only see data belonging to Brgy. <span x-text="form.barangay"></span>.</span>
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">Accessible Modules</p>
                                <div class="flex flex-wrap gap-1.5 mb-3.5">
                                    <template x-for="mod in (selectedRole ? accessedModules(selectedRole) : [])" :key="mod.key">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-brand-50 text-brand-700 text-[11px] font-medium">
                                            <i :data-lucide="mod.icon" class="w-3 h-3"></i>
                                            <span x-text="mod.name"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <template x-if="selectedRole">
                                <x-admin.permission-tree modules="modules" bind="selectedRole.permissions" locked="true" :compact="true" />
                            </template>
                        </div>

                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 border border-slate-100 px-3.5 py-2.5">
                            <i data-lucide="info" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5"></i>
                            <p class="text-xs text-slate-500">Nothing is emailed: give the officer their Employee ID and this temporary password yourself, and ask them to change it after signing in.</p>
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-ui.button variant="secondary" size="sm" x-show="step === 1" @click="open = false">Cancel</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" x-show="step > 1" @click="back()" icon="arrow-left">Back</x-ui.button>
                        <x-ui.button size="sm" x-show="step < 4" @click="next()" icon="arrow-right">Continue</x-ui.button>
                        <x-ui.button size="sm" x-show="step === 4" icon="user-plus" x-bind:disabled="submitting" @click="await createPersonnel(); if (!createError) open = false">
                            <span x-text="submitting ? 'Creating…' : 'Create Account'"></span>
                        </x-ui.button>
                        <p class="text-xs text-rose-600 mt-1" x-show="createError" x-text="createError"></p>
                    </x-slot:footer>
                </x-ui.modal>
            </div>
        </div>

        {{-- ============================= ROLES ============================= --}}
        <div
            x-data="{
                roles: [],
                loading: true,
                failed: false,
                modules: @js($permissionModules),
                open: false,
                creating: false,
                createError: '',
                confirmDelete: null,
                newRole: { name: '', description: '', from: '', color: 'text-slate-600 bg-slate-100' },
                colors: ['text-slate-600 bg-slate-100', 'text-brand-700 bg-brand-50', 'text-purple-700 bg-purple-50', 'text-emerald-700 bg-emerald-50', 'text-orange-700 bg-orange-50', 'text-rose-700 bg-rose-50', 'text-teal-700 bg-teal-50'],
                async loadRoles() {
                    this.loading = true;
                    this.failed = false;
                    try {
                        const result = await $api.get('/admin/roles');
                        this.roles = result.data || [];
                    } catch { this.failed = true; } finally { this.loading = false; this.$nextTick(() => window.renderIcons?.()); }
                },
                // The server key: lower case, digits and underscores, starting with a letter.
                roleKey(name) {
                    const key = (name || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 36);
                    return key === '' ? '' : (/^[a-z]/.test(key) ? key : 'role_' + key);
                },
                // New Role used to toast that it needed a backend; POST /admin/roles existed.
                async createRole() {
                    if (this.creating || this.newRole.name.trim().length < 3) return;
                    this.creating = true;
                    this.createError = '';
                    const base = this.roles.find((r) => r.key === this.newRole.from);
                    const permissions = Object.entries(base?.permissions || {}).flatMap(([submodule, actions]) => actions.map((action) => ({ submodule, action })));
                    try {
                        const res = await $api.post('/admin/roles', {
                            key: this.roleKey(this.newRole.name), name: this.newRole.name.trim(),
                            description: this.newRole.description.trim() || null, icon: 'shield', color: this.newRole.color, permissions,
                        });
                        this.open = false;
                        this.newRole = { name: '', description: '', from: '', color: 'text-slate-600 bg-slate-100' };
                        this.$dispatch('toast', { message: 'Role created. Set its permissions on the Permissions tab.', variant: 'success' });
                        await this.loadRoles();
                        window.dispatchEvent(new CustomEvent('roles-changed', { detail: res.data?.key }));
                    } catch (err) {
                        this.createError = err.localised ? err.localised('en') : 'Could not create the role.';
                    } finally { this.creating = false; }
                },
                // Two clicks: the first turns the button into Confirm delete.
                async deleteRole(role) {
                    if (this.confirmDelete !== role.key) { this.confirmDelete = role.key; return; }
                    try {
                        await $api.delete('/admin/roles/' + role.key);
                        this.$dispatch('toast', { message: 'Role ' + role.name + ' deleted.', variant: 'success' });
                        await this.loadRoles();
                        window.dispatchEvent(new CustomEvent('roles-changed'));
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not delete the role.', variant: 'error' });
                    } finally { this.confirmDelete = null; }
                },
                get totalAccounts() { return this.roles.reduce((n, r) => n + (r.accounts || 0), 0); },
                grantedCount(role) { return Object.values(role.permissions || {}).reduce((n, arr) => n + arr.length, 0); },
                moduleCount(role) { return this.modules.filter(m => m.submodules.some(s => (role.permissions?.[s.key] || []).length > 0)).length; },
            }"
            x-init="loadRoles()"
            x-show="tab === 'roles'" x-cloak
        >
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="text-sm text-slate-500" x-text="roles.length + ' roles · ' + totalAccounts + ' personnel accounts'"></p>
                <x-ui.button icon="plus" size="sm" @click="open = true; createError = ''">New Role</x-ui.button>
            </div>

            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <x-ui.skeleton class="h-32 rounded-2xl" /><x-ui.skeleton class="h-32 rounded-2xl opacity-80" /><x-ui.skeleton class="h-32 rounded-2xl opacity-60" />
            </div>
            <div x-show="failed" x-cloak><x-ui.error-state messageExpr="'Could not load roles.'" retryExpr="loadRoles()" :retryable="true" /></div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" x-show="!loading && !failed">
                <template x-for="role in roles" :key="role.key">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-[220ms] ease-standard">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="role.color || 'text-slate-600 bg-slate-50'">
                                    <i :data-lucide="role.icon || 'shield'" class="w-5 h-5"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-navy-900 flex items-center gap-1.5">
                                        <span x-text="role.name"></span>
                                        <span x-show="role.built_in === false" class="text-[10px] font-medium text-slate-500 bg-slate-100 rounded-full px-1.5 py-0.5">Custom</span>
                                    </p>
                                    <p class="text-xs text-slate-500" x-text="(role.accounts || 0) + ' personnel'"></p>
                                </div>
                            </div>
                            <span x-show="!role.editable" class="text-slate-300" title="System role — not editable"><i data-lucide="lock" class="w-3.5 h-3.5"></i></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-3.5 leading-relaxed line-clamp-3" x-text="role.description"></p>
                        <div class="mt-3.5 pt-3.5 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-500"><span class="font-semibold text-slate-600" x-text="moduleCount(role)"></span> modules · <span class="font-semibold text-slate-600" x-text="grantedCount(role)"></span> permissions</span>
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    x-show="role.built_in === false && !role.accounts"
                                    @click="deleteRole(role)"
                                    class="text-xs font-medium text-rose-600 hover:underline"
                                    x-text="confirmDelete === role.key ? 'Confirm delete' : 'Delete'"
                                ></button>
                                <button
                                    @click="tab = 'permissions'; window.dispatchEvent(new CustomEvent('select-role', { detail: role.key }))"
                                    class="text-xs font-medium text-brand-600 hover:underline flex items-center gap-1"
                                >View <i data-lucide="eye" class="w-3 h-3"></i></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <x-ui.modal title="New Role" maxWidth="sm">
                <div class="space-y-4">
                    <x-ui.input name="role_name" label="Role name" placeholder="e.g. Cashier" x-model="newRole.name" />
                    <div>
                        <label for="role_description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                        <textarea id="role_description" rows="2" x-model="newRole.description" placeholder="What this role is for" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard"></textarea>
                    </div>
                    <div>
                        <label for="role_from" class="block text-sm font-medium text-slate-700 mb-1.5">Start with the permissions of</label>
                        <select id="role_from" x-model="newRole.from" class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-[160ms] ease-standard">
                            <option value="">No permissions yet (set them on the Permissions tab)</option>
                            <template x-for="r in roles.filter((r) => r.editable)" :key="'from-' + r.key"><option :value="r.key" x-text="r.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <p class="block text-sm font-medium text-slate-700 mb-1.5">Colour</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in colors" :key="c">
                                <button type="button" @click="newRole.color = c" class="w-9 h-9 rounded-lg flex items-center justify-center ring-2 ring-offset-1 transition-all duration-[160ms] ease-standard" :class="[c, newRole.color === c ? 'ring-brand-400' : 'ring-transparent']" :aria-label="'Colour ' + c.split(' ')[0].replace('text-', '')" :aria-pressed="newRole.color === c ? 'true' : 'false'"><i data-lucide="shield" class="w-4 h-4"></i></button>
                            </template>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Role key: <span class="font-mono text-slate-600" x-text="roleKey(newRole.name) || '—'"></span></p>
                </div>
                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button size="sm" icon="plus" x-bind:disabled="creating || newRole.name.trim().length < 3" @click="createRole()">
                        <span x-text="creating ? 'Creating…' : 'Create Role'"></span>
                    </x-ui.button>
                    <p class="text-xs text-rose-600 mt-1" x-show="createError" x-text="createError"></p>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        {{-- ============================= PERMISSIONS ============================= --}}
        <div
            x-data="{
                roles: [],
                loading: true,
                failed: false,
                modules: @js($permissionModules),
                selectedRoleKey: 'municipal_admin',
                workingPerms: {},
                dirty: false,
                saving: false,
                get selectedRole() { return this.roles.find(r => r.key === this.selectedRoleKey); },
                async loadRoles() {
                    this.loading = true;
                    this.failed = false;
                    try {
                        const result = await $api.get('/admin/roles');
                        this.roles = result.data || [];
                        if (!this.roles.some((r) => r.key === this.selectedRoleKey)) this.selectedRoleKey = this.roles[0]?.key;
                        this.loadWorking();
                    } catch { this.failed = true; } finally { this.loading = false; }
                },
                selectRole(key) { this.selectedRoleKey = key; this.loadWorking(); },
                loadWorking() { this.workingPerms = JSON.parse(JSON.stringify(this.selectedRole?.permissions || {})); this.dirty = false; },
                totalGranted() { return Object.values(this.workingPerms).reduce((n, arr) => n + arr.length, 0); },
                totalAvailable() { return this.modules.reduce((n, m) => n + m.submodules.reduce((n2, s) => n2 + s.perms.length, 0), 0); },
                moduleGranted(mod) { return mod.submodules.reduce((n, s) => n + (this.workingPerms[s.key]?.length || 0), 0); },
                moduleTotal(mod) { return mod.submodules.reduce((n, s) => n + s.perms.length, 0); },
                accessedModules() { return this.modules.filter(m => this.moduleGranted(m) > 0); },
                // The role's saved permission set as a CSV: every module,
                // submodule and permission in the matrix, and whether the role
                // holds it. This button used to toast 'frontend preview only'.
                downloadReport() {
                    const role = this.selectedRole;
                    if (!role) return;
                    const held = role.permissions || {};
                    const rows = [['Role', 'Module', 'Submodule', 'Permission', 'Granted']];
                    this.modules.forEach((m) => m.submodules.forEach((sub) => sub.perms.forEach((p) => {
                        rows.push([role.name, m.name, sub.name, p, (held[sub.key] || []).includes(p) ? 'Yes' : 'No']);
                    })));
                    const csv = rows.map((r) => r.map((v) => JSON.stringify(String(v ?? ''))).join(',')).join('\n');
                    const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv], { type: 'text/csv' })), download: 'permissions-' + role.key + '.csv' });
                    a.click();
                },
                async savePerms() {
                    if (this.saving) return;
                    this.saving = true;
                    const permissions = Object.entries(this.workingPerms).flatMap(([submodule, actions]) => actions.map((action) => ({ submodule, action })));
                    try {
                        await $api.put('/admin/roles/' + this.selectedRoleKey + '/permissions', { permissions });
                        this.selectedRole.permissions = JSON.parse(JSON.stringify(this.workingPerms));
                        this.dirty = false;
                        this.$dispatch('toast', { message: 'Permissions updated for ' + this.selectedRole.name + '.', variant: 'success' });
                    } catch (err) {
                        this.$dispatch('toast', { message: err.localised ? err.localised('en') : 'Could not save permissions.', variant: 'error' });
                    } finally {
                        this.saving = false;
                    }
                },
            }"
            x-init="loadRoles(); $watch('workingPerms', () => dirty = true); window.addEventListener('select-role', e => selectRole(e.detail)); window.addEventListener('roles-changed', async (e) => { await loadRoles(); if (e.detail) selectRole(e.detail); })"
            x-show="tab === 'permissions'" x-cloak
            class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start"
        >
            <div x-show="loading" class="lg:col-span-12"><x-ui.skeleton class="h-40" /></div>
            <div x-show="failed" x-cloak class="lg:col-span-12"><x-ui.error-state messageExpr="'Could not load roles.'" retryExpr="loadRoles()" :retryable="true" /></div>

            <!-- Sticky role sidebar -->
            <div class="lg:col-span-3 lg:sticky lg:top-4 space-y-1.5" x-show="!loading && !failed">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 px-1 mb-2">System Roles</p>
                <template x-for="role in roles" :key="role.key">
                    <button
                        type="button" @click="selectRole(role.key)"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-left transition-colors duration-[160ms] ease-standard"
                        :class="selectedRoleKey === role.key ? 'bg-brand-50 ring-1 ring-brand-200' : 'hover:bg-slate-50'"
                    >
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="role.color || 'text-slate-600 bg-slate-50'">
                            <i :data-lucide="role.icon || 'shield'" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold truncate" :class="selectedRoleKey === role.key ? 'text-brand-700' : 'text-navy-900'" x-text="role.name"></p>
                            <p class="text-[10.5px] text-slate-500" x-text="(role.accounts || 0) + ' personnel'"></p>
                        </div>
                        <i data-lucide="lock" class="w-3 h-3 text-slate-300 shrink-0" x-show="!role.editable"></i>
                    </button>
                </template>
            </div>

            <!-- Role description + permission tree -->
            <div class="lg:col-span-6 space-y-4" x-show="!loading && !failed && selectedRole">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <div class="flex items-center gap-3">
                            <span class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="selectedRole?.color || 'text-slate-600 bg-slate-50'">
                                <i :data-lucide="selectedRole?.icon" class="w-5 h-5"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-navy-900 flex items-center gap-2">
                                    <span x-text="selectedRole?.name"></span>
                                    <span x-show="!selectedRole?.editable" class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-500 bg-slate-100 rounded-full px-2 py-0.5"><i data-lucide="lock" class="w-2.5 h-2.5"></i>System</span>
                                </p>
                                <p class="text-xs text-slate-500" x-text="(selectedRole?.accounts || 0) + ' personnel assigned'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-ui.button
                                variant="secondary" size="sm" icon="download"
                                x-show="$store.session.canExportReports()"
                                @click="downloadReport()"
                            >Download Report</x-ui.button>
                            <x-ui.button size="sm" icon="save" x-show="selectedRole?.editable" x-bind:disabled="!dirty || saving" @click="savePerms()">
                                <span x-text="saving ? 'Saving…' : 'Save Changes'"></span>
                            </x-ui.button>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3 leading-relaxed" x-text="selectedRole?.description"></p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-navy-900">Module Permissions</p>
                        <p class="text-[11px] text-slate-500" x-show="!selectedRole?.editable">Read-only — system role</p>
                    </div>
                    <x-admin.permission-tree modules="modules" bind="workingPerms" locked="!selectedRole?.editable" />
                </div>
            </div>

            <!-- Sticky permission summary -->
            <div class="lg:col-span-3 lg:sticky lg:top-4 space-y-4" x-show="!loading && !failed && selectedRole">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-3">Permission Summary</p>
                    <div class="flex items-end gap-1.5 mb-1">
                        <span class="text-2xl font-semibold text-navy-900" x-text="totalGranted()"></span>
                        <span class="text-xs text-slate-500 mb-1">/ <span x-text="totalAvailable()"></span> granted</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mb-4">
                        <div class="h-full bg-brand-500 rounded-full transition-all duration-[600ms] ease-standard" :style="`width: ${totalAvailable() ? (totalGranted() / totalAvailable() * 100) : 0}%`"></div>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="mod in modules" :key="mod.key">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-slate-600 flex items-center gap-1.5 truncate"><i :data-lucide="mod.icon" class="w-3 h-3 text-slate-500 shrink-0"></i><span class="truncate" x-text="mod.name"></span></span>
                                    <span class="text-slate-500 shrink-0 ml-2" x-text="moduleGranted(mod) + '/' + moduleTotal(mod)"></span>
                                </div>
                                <div class="h-1 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-brand-400 rounded-full transition-all duration-[600ms] ease-standard" :style="`width: ${moduleTotal(mod) ? (moduleGranted(mod) / moduleTotal(mod) * 100) : 0}%`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-3">Accessible Modules</p>
                    <div class="flex flex-wrap gap-1.5" x-show="accessedModules().length > 0">
                        <template x-for="mod in accessedModules()" :key="mod.key">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-brand-50 text-brand-700 text-[10.5px] font-medium">
                                <i :data-lucide="mod.icon" class="w-3 h-3"></i>
                                <span x-text="mod.name"></span>
                            </span>
                        </template>
                    </div>
                    <p class="text-[11px] text-slate-500" x-show="accessedModules().length === 0">No module access granted.</p>
                </div>
            </div>
        </div>

        {{-- ============================= DOKYU & TULONG MATRIX ============================= --}}
        <div x-show="tab === 'matrix'" x-cloak>
            <div class="mb-4">
                <p class="text-sm font-semibold text-navy-900">Role &times; Module &times; Scope &times; Permission</p>
                <p class="text-xs text-slate-500 mt-0.5 max-w-2xl">Read-only reference showing how each of the 8 system roles is authorized across Dokyu and Tulong — the grant level, how row visibility is scoped, and which of the key routing actions each role can take. Built from the existing role/permission configuration; to change an actual grant, use the Permissions tab.</p>
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden 2xl:table-cell">Dokyu / Tulong Grant</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 hidden 2xl:table-cell">Scope</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Assign</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Review</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Approve</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reject</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Release</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reports</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @php
                        $matrixRows = [
                            ['role' => 'Super Administrator', 'grant' => 'Full', 'scope' => 'Municipality-wide', 'assign' => true, 'review' => true, 'approve' => true, 'reject' => true, 'release' => true, 'reports' => true],
                            ['role' => 'Municipal Administrator', 'grant' => 'Full', 'scope' => 'Municipality-wide', 'assign' => true, 'review' => true, 'approve' => true, 'reject' => true, 'release' => true, 'reports' => true],
                            ['role' => 'Department Head', 'grant' => 'View + Edit + Review/Approve/Reject/Release', 'scope' => 'Own office', 'assign' => true, 'review' => true, 'approve' => true, 'reject' => true, 'release' => true, 'reports' => true],
                            ['role' => 'Department Staff', 'grant' => 'View + Edit (process)', 'scope' => 'Own office', 'assign' => false, 'review' => false, 'approve' => false, 'reject' => false, 'release' => false, 'reports' => false],
                            ['role' => 'Front Desk Officer', 'grant' => 'View + Edit (intake only)', 'scope' => 'Municipality-wide', 'assign' => false, 'review' => false, 'approve' => false, 'reject' => false, 'release' => false, 'reports' => false],
                            ['role' => 'Records Officer', 'grant' => 'View + Edit, Civil Registry only', 'scope' => 'Municipality-wide', 'assign' => false, 'review' => false, 'approve' => false, 'reject' => false, 'release' => false, 'reports' => true],
                            ['role' => 'Barangay Secretary', 'grant' => 'View + Edit + Review/Approve/Release, Barangay Certificates only', 'scope' => 'Own barangay', 'assign' => false, 'review' => true, 'approve' => true, 'reject' => false, 'release' => true, 'reports' => false],
                            ['role' => 'Citizen', 'grant' => 'Own requests only', 'scope' => 'Own records only', 'assign' => false, 'review' => false, 'approve' => false, 'reject' => false, 'release' => false, 'reports' => false],
                        ];
                    @endphp
                    @foreach($matrixRows as $row)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 font-medium text-navy-900 whitespace-nowrap">{{ $row['role'] }}</td>
                            <td class="px-4 py-3 text-slate-500 max-w-xs hidden 2xl:table-cell">{{ $row['grant'] }}</td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap hidden 2xl:table-cell">{{ $row['scope'] }}</td>
                            @foreach(['assign', 'review', 'approve', 'reject', 'release', 'reports'] as $action)
                                <td class="px-4 py-3 text-center">
                                    @if($row[$action])
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 inline-block"></i>
                                    @else
                                        <span class="text-slate-300">&mdash;</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <p class="text-xs text-slate-500 leading-relaxed">
                    <strong class="text-slate-700">Report downloads</strong> stay restricted to roles with an existing reporting responsibility (Municipal Administrator, Records Officer), plus Department Head gains it newly &mdash; scoped to their own department's data, not municipality-wide. Department Staff and Front Desk do not get report download, per the least-privilege principle: a user who can view operational requests doesn't automatically need export/report access.
                </p>
            </div>
        </div>

        </div>
    </div>
</x-layouts.admin>
