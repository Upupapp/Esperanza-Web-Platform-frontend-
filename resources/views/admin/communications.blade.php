@php
    $announcements = [
        ['title' => 'Fiesta ng Esperanza 2026 — Schedule of Activities', 'category' => 'Community', 'author' => 'Corazon Villareal', 'date' => 'Jul 9, 2026', 'status' => 'Draft'],
        ['title' => 'Free Medical & Dental Mission — Brgy. Poblacion', 'category' => 'Health', 'author' => 'Dr. Leilani Domingo', 'date' => 'Jul 7, 2026', 'status' => 'Pending Review'],
        ['title' => 'Online Document Requests now accept GCash payment', 'category' => 'Public Service', 'author' => 'Paolo Reyes', 'date' => 'Jul 5, 2026', 'status' => 'Approved'],
        ['title' => 'Typhoon Preparedness Advisory', 'category' => 'Advisory', 'author' => 'MDRRMO', 'date' => 'Jul 3, 2026', 'status' => 'Completed'],
        ['title' => 'Free Skills Training for Out-of-School Youth', 'category' => 'Livelihood', 'author' => 'Corazon Villareal', 'date' => 'Jun 28, 2026', 'status' => 'Completed'],
        ['title' => '2025 Barangay Election Results Advisory', 'category' => 'Governance', 'author' => 'Atty. Marivic Ong', 'date' => 'Jan 15, 2026', 'status' => 'Archived'],
    ];

    $offices = [
        ['name' => 'Office of the Municipal Mayor', 'head' => 'Hon. Ricardo M. Espallardo', 'contact' => '(056) 333-1021', 'email' => 'mayor@esperanza.gov.ph'],
        ['name' => 'Municipal Social Welfare & Development Office', 'head' => 'Ms. Corazon P. Villareal', 'contact' => '(056) 333-1044', 'email' => 'mswdo@esperanza.gov.ph'],
        ['name' => 'Municipal Treasurer\'s Office', 'head' => 'Mr. Bienvenido T. Salazar', 'contact' => '(056) 333-1032', 'email' => 'treasury@esperanza.gov.ph'],
        ['name' => 'Municipal Health Office', 'head' => 'Dr. Leilani F. Domingo', 'contact' => '(056) 333-1056', 'email' => 'health@esperanza.gov.ph'],
        ['name' => 'Office of the Municipal Civil Registrar', 'head' => 'Atty. Marivic S. Ong', 'contact' => '(056) 333-1067', 'email' => 'civilregistrar@esperanza.gov.ph'],
        ['name' => 'ICT Office', 'head' => 'Engr. Paolo J. Reyes', 'contact' => '(056) 333-1099', 'email' => 'ict@esperanza.gov.ph'],
    ];

    $barangays = collect(config('esperanza.barangays'))->map(fn ($b) => [
        'name' => $b,
        'captain' => 'Hon. ' . collect(['Roberto','Elena','Manuel','Fe','Domingo','Rosario','Alfredo','Teresita'])->random() . ' ' . collect(['Cruz','Santos','Reyes','Bautista','Delos Reyes','Villanueva'])->random(),
    ]);

    $communityPosts = [
        ['author' => 'Elmer Bantillo', 'barangay' => 'Santiago', 'body' => 'Successful po ang blood donation drive natin ngayong araw dito sa Brgy. Santiago! Umabot ng 40+ na donors. Maraming salamat sa lahat ng dumalo at sa Red Cross Masbate!', 'image' => 'esperanza_community1.jpg', 'time' => '3 hrs ago', 'likes' => 47, 'comments' => 1, 'shares' => 6, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Josefina Marbella', 'barangay' => 'Baras', 'body' => 'Gusto ko lang po magpasalamat sa MSWDO at OSCA sa mabilis na pag-asikaso ng aking social pension application. Simple lang pero malaking tulong na po sa amin. Maraming salamat po talaga!', 'image' => null, 'time' => '1 day ago', 'likes' => 83, 'comments' => 1, 'shares' => 4, 'reports' => 0, 'featured' => true, 'status' => 'Approved'],
        ['author' => 'Michael Bacus', 'barangay' => 'Iligan', 'body' => 'May nakakita po ba nito? Nawala malapit sa covered court kagabi. Itim na may puting paa, mahiyain pero friendly naman. Tulungan niyo po ako i-share.', 'image' => 'esperanza_event3.jpg', 'time' => '1 day ago', 'likes' => 29, 'comments' => 0, 'shares' => 22, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Angelica Fajardo', 'barangay' => 'Rizal', 'body' => 'Coverage ng aming fiesta rehearsal kagabi! Ang saya-saya, sana dumagsa kayo sa opening program.', 'image' => 'esperanza_event2.jpg', 'time' => '2 days ago', 'likes' => 61, 'comments' => 0, 'shares' => 9, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Teresita Salazar', 'barangay' => 'Agoho', 'body' => 'Tanong lang po, kailan po ulit ang susunod na free medical mission? Gusto ko sanang isama ang lola ko na senior citizen.', 'image' => null, 'time' => '4 days ago', 'likes' => 12, 'comments' => 1, 'shares' => 1, 'reports' => 0, 'featured' => false, 'status' => 'Approved'],
        ['author' => 'Rodrigo Palma', 'barangay' => 'Domorog', 'body' => 'Bumili kayo sa amin, best price sa buong Masbate! Message niyo lang po ako dito para sa mga detalye at promo ngayong buwan.', 'image' => null, 'time' => '6 hrs ago', 'likes' => 2, 'comments' => 0, 'shares' => 0, 'reports' => 3, 'featured' => false, 'status' => 'Pending Review'],
    ];

    $tab = $tab ?? 'balita';
@endphp

<x-layouts.admin title="Communications" subtitle="Balita and the municipal directory." active="communications">
    <div x-data="{ tab: '{{ $tab }}', communityPosts: @js($communityPosts), postOpen: false, selectedPost: null }" class="animate-fade-up">

        <div x-show="!$store.session.can('communications')" x-cloak>
            <x-admin.access-restricted module="Announcements & Directory" icon="megaphone" />
        </div>

        <div x-show="$store.session.can('communications')" x-cloak>

        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl p-1 shadow-card w-fit mb-4 overflow-x-auto">
            <button @click="tab = 'balita'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'balita' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="megaphone" class="w-3.5 h-3.5"></i>Balita</button>
            <button @click="tab = 'community'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'community' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'">
                <i data-lucide="users-round" class="w-3.5 h-3.5"></i>Community Posts
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="tab === 'community' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-600'" x-show="communityPosts.filter(p => p.status === 'Pending Review').length > 0" x-text="communityPosts.filter(p => p.status === 'Pending Review').length"></span>
            </button>
            <button @click="tab = 'offices'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'offices' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="building-2" class="w-3.5 h-3.5"></i>Offices</button>
            <button @click="tab = 'barangays'" class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap" :class="tab === 'barangays' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-50'"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i>Barangays</button>
        </div>

        <!-- Balita -->
        <div x-show="tab === 'balita'" x-data="{ open: false }">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Published" value="28" icon="megaphone" color="brand" sublabel="This year" :delay="0" />
                <x-ui.stat-card label="Pending Review" value="3" icon="file-clock" color="orange" :delay="40" />
                <x-ui.stat-card label="Drafts" value="2" icon="pencil-line" color="purple" :delay="80" />
                <x-ui.stat-card label="Total Reach" value="14,208" icon="eye" color="green" sublabel="Citizen views this month" :delay="120" />
            </div>

            <div class="flex items-center justify-end mb-3">
                <x-ui.button @click="open = true" icon="plus" size="sm">New Balita</x-ui.button>
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Title</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Category</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Author</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Date</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($announcements as $a)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium max-w-xs">{{ $a['title'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden md:table-cell">{{ $a['category'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $a['author'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $a['date'] }}</td>
                            <td class="px-4 py-2.5"><x-ui.badge :status="$a['status']" /></td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.modal title="New Balita" maxWidth="lg">
                <div class="space-y-4">
                    <x-ui.input name="title" label="Title" placeholder="e.g. Free Medical Mission — Brgy. Poblacion" />
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>Community</option>
                                <option>Health</option>
                                <option>Public Service</option>
                                <option>Advisory</option>
                                <option>Livelihood</option>
                                <option>Governance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Visibility</label>
                            <select class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-700 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                                <option>All Barangays</option>
                                <option>Selected Barangays</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
                        <textarea rows="4" placeholder="Write the announcement content..." class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-brand-400 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200"></textarea>
                    </div>
                </div>

                <x-slot:footer>
                    <x-ui.button variant="secondary" size="sm" @click="open = false; $dispatch('toast', { message: 'Saved as draft.', variant: 'info' })">Save as Draft</x-ui.button>
                    <x-ui.button size="sm" icon="send" @click="open = false; $dispatch('toast', { message: 'Announcement submitted for review.', variant: 'success' })">Submit for Review</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        <!-- Community posts -->
        <div x-show="tab === 'community'" x-cloak x-init="$nextTick(() => window.renderIcons?.())">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <x-ui.stat-card label="Total Posts" value="{{ count($communityPosts) }}" valueBind="communityPosts.length" icon="users-round" color="brand" :delay="0" />
                <x-ui.stat-card label="Reported" value="{{ collect($communityPosts)->where('status', 'Pending Review')->count() }}" valueBind="communityPosts.filter(p => p.status === 'Pending Review').length" icon="flag" color="red" sublabel="Needs moderation" :delay="40" />
                <x-ui.stat-card label="Featured" value="{{ collect($communityPosts)->where('featured', true)->count() }}" valueBind="communityPosts.filter(p => p.featured).length" icon="star" color="gold" :delay="80" />
                <x-ui.stat-card label="Total Engagement" value="{{ collect($communityPosts)->sum(fn($p) => $p['likes'] + $p['comments'] + $p['shares']) }}" valueBind="communityPosts.reduce((n, p) => n + p.likes + p.comments + p.shares, 0)" icon="activity" color="purple" sublabel="Likes, comments &amp; shares" :delay="120" />
            </div>

            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Author</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Barangay</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Post</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Posted</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Engagement</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="(p, i) in communityPosts" :key="i">
                        <tr x-show="$store.session.inScope(p.barangay)" x-cloak class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 text-slate-700 font-medium whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <span x-text="p.author"></span>
                                    <i data-lucide="star" class="w-3.5 h-3.5 text-gold-500" style="fill: currentColor" x-show="p.featured"></i>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 hidden lg:table-cell" x-text="'Brgy. ' + p.barangay"></td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell max-w-xs truncate" x-text="p.body"></td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell whitespace-nowrap" x-text="p.time"></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                <span class="inline-flex items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-0.5"><i data-lucide="heart" class="w-3 h-3"></i><span x-text="p.likes"></span></span>
                                    <span class="inline-flex items-center gap-0.5"><i data-lucide="message-circle" class="w-3 h-3"></i><span x-text="p.comments"></span></span>
                                    <span class="inline-flex items-center gap-0.5 text-rose-500" x-show="p.reports > 0"><i data-lucide="flag" class="w-3 h-3"></i><span x-text="p.reports"></span></span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge status="Approved" x-show="p.status === 'Approved'" />
                                <x-ui.badge status="Pending Review" x-show="p.status === 'Pending Review'" x-cloak />
                                <x-ui.badge status="Archived" x-show="p.status === 'Archived'" x-cloak />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="selectedPost = p; postOpen = true" class="text-xs font-medium text-brand-600 hover:underline whitespace-nowrap">Moderate</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.table>

            <x-ui.modal title="Moderate Community Post" maxWidth="lg">
                <template x-if="selectedPost">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-navy-800 text-white flex items-center justify-center font-semibold ring-2 ring-white shadow-sm shrink-0 text-sm" x-text="selectedPost.author.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></div>
                            <div>
                                <p class="text-sm font-semibold text-navy-900" x-text="selectedPost.author"></p>
                                <p class="text-xs text-slate-400" x-text="'Brgy. ' + selectedPost.barangay + ' · ' + selectedPost.time"></p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed mb-3" x-text="selectedPost.body"></p>
                        <img :src="'{{ asset('images/esperanza/') }}/' + selectedPost.image" x-show="selectedPost.image" class="w-full max-h-72 object-cover rounded-xl mb-3">

                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-600 space-y-1.5">
                            <p><span class="text-slate-400">Engagement:</span> <span x-text="selectedPost.likes"></span> likes · <span x-text="selectedPost.comments"></span> comments · <span x-text="selectedPost.shares"></span> shares</p>
                            <p x-show="selectedPost.reports > 0"><span class="text-slate-400">Reports:</span> <span class="text-rose-600 font-medium" x-text="selectedPost.reports + ' citizen report(s) — review for possible spam or policy violation'"></span></p>
                        </div>
                    </div>
                </template>

                <x-slot:footer>
                    <x-ui.button variant="danger" size="sm" @click="selectedPost.status = 'Archived'; postOpen = false; $dispatch('toast', { message: 'Post hidden from Balita feed.', variant: 'error' })">Hide Post</x-ui.button>
                    <x-ui.button variant="secondary" size="sm" @click="selectedPost.featured = !selectedPost.featured; $dispatch('toast', { message: selectedPost.featured ? 'Post featured on Balita.' : 'Post unfeatured.', variant: 'success' })"><span x-text="selectedPost && selectedPost.featured ? 'Unfeature' : 'Feature Post'"></span></x-ui.button>
                    <x-ui.button size="sm" icon="check" @click="selectedPost.status = 'Approved'; postOpen = false; $dispatch('toast', { message: 'Post approved and kept live.', variant: 'success' })">Approve & Keep Live</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </div>

        <!-- Municipal offices -->
        <div x-show="tab === 'offices'" x-cloak>
            <div class="flex items-center justify-end mb-3">
                <x-ui.button icon="plus" size="sm">Add Office</x-ui.button>
            </div>
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Office</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Head</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden md:table-cell">Contact</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden lg:table-cell">Email</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($offices as $office)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $office['name'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell">{{ $office['head'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden md:table-cell font-mono text-xs">{{ $office['contact'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden lg:table-cell">{{ $office['email'] }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>

        <!-- Barangays -->
        <div x-show="tab === 'barangays'" x-cloak>
            <x-ui.table>
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Barangay Captain</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400 hidden sm:table-cell">Contact</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($barangays as $b)
                        <tr x-show="$store.session.inScope('{{ $b['name'] }}')" x-cloak class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2.5 text-slate-700 font-medium">Brgy. {{ $b['name'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500">{{ $b['captain'] }}</td>
                            <td class="px-4 py-2.5 text-slate-500 hidden sm:table-cell font-mono text-xs">0917 {{ rand(100,999) }} {{ rand(1000,9999) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <button class="text-slate-400 hover:text-brand-600 transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>

        </div>
    </div>
</x-layouts.admin>
