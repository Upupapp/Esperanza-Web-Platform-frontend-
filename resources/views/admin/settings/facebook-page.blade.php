{{-- Facebook Page (welfare round 2026-10-29): Balita posts copied to the LGU's
     Facebook Page when published. For anyone who can post in Balita
     (comm_news create; owner, 2026-10-29) and technical administrators
     (set_system); changing it takes comm_news create or set_system edit. Three states: not
     connected (how to get the Page ID and a token, and the form), connected
     (the Page, who connected it, auto-post, default sharing, check,
     disconnect), and a token problem (reconnect). The token is sent once and
     never comes back from the server.
     GET/PUT/PATCH/DELETE /admin/integrations/facebook, POST .../check.
     Component facebookPage in resources/js/scope-admin-settings.js; the setup
     steps follow the research (scratchpad research/facebook-pages.md: a
     System User token from Meta Business Suite, which does not expire and is
     not tied to one person; the Meta app must be Live). --}}
<div x-data="facebookPage" x-show="visible" x-cloak x-effect="tab === 'integrations' && start()" data-testid="facebook-page-card">
<x-ui.card>
    <div class="flex items-start justify-between gap-3 mb-1">
        <h3 class="text-sm font-semibold text-ink flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#1877F2] text-white grid place-items-center shrink-0" aria-hidden="true"><x-balita.fb-mark class="w-4 h-4" /></span>
            Facebook Page
        </h3>
        <span x-show="loaded" class="{{ $ui['pill'] }}" :class="@js($tones)[stateTone]" x-text="stateLabel"></span>
    </div>
    <p class="text-xs text-ink-muted mb-4">Published Balita posts are copied to the municipality's Facebook Page, with a link back to the post on the website.</p>

    <div x-show="loading && !loaded" class="space-y-2" aria-busy="true"><x-ui.skeleton class="h-14" /><x-ui.skeleton class="h-24 opacity-80" /></div>
    <div x-show="failed && !loading" x-cloak><x-ui.error-state messageExpr="'Could not read the Facebook connection.'" retryExpr="load()" :retryable="true" /></div>

    <div x-show="loaded" x-cloak class="space-y-4" aria-live="polite">
        {{-- Token problem: posting is paused --}}
        <div x-show="state === 'problem'" role="alert" class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-900 space-y-1">
            <p class="font-semibold flex items-center gap-1.5"><i data-lucide="triangle-alert" class="w-4 h-4 shrink-0"></i>The Facebook connection needs attention. Posting is paused.</p>
            <p x-show="fb.last_error" class="text-xs break-words" x-text="'Facebook said: ' + fb.last_error"></p>
            <p class="text-xs">Posts published meanwhile wait, and go out once the Page is reconnected. A token stops working when the person who made it changes their password or leaves the Page, or when the system user or the app is removed from the Page.</p>
        </div>

        {{-- Connected (or connected with a problem): the Page and its choices --}}
        <div x-show="state !== 'none'" class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-brand-600/10 p-3">
                <div class="min-w-0">
                    <p class="text-sm text-ink">Connected as <a :href="fb.page_link || ('https://www.facebook.com/' + (fb.page_id || ''))" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline break-words" x-text="fb.page_name || fb.page_id"></a><i data-lucide="external-link" class="inline w-3 h-3 ml-1 text-ink-muted"></i></p>
                    <p class="text-xs text-ink-muted font-mono" x-text="'Page ID ' + (fb.page_id || '')"></p>
                    <p class="text-xs text-ink-muted mt-1" x-show="connectedLine" x-text="connectedLine"></p>
                    <p class="text-xs text-ink-muted" x-show="fb.last_checked_at" x-text="'Last checked ' + when(fb.last_checked_at)"></p>
                </div>
            </div>

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p id="fb-auto-label" class="text-sm font-medium text-ink">Auto-post</p>
                    <p class="text-xs text-ink-muted">Post to Facebook by itself when a Balita post is published. Off: nothing goes to Facebook unless an approver presses "Post to Facebook" on the post.</p>
                </div>
                <button type="button" role="switch" aria-labelledby="fb-auto-label" :aria-checked="fb.auto_post ? 'true' : 'false'" :disabled="!canManage || patching" @click="setAutoPost(!fb.auto_post)"
                    class="relative shrink-0 w-12 h-7 rounded-full transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 disabled:opacity-50" :class="fb.auto_post ? 'bg-brand-600' : 'bg-slate-300'">
                    <span class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="fb.auto_post && 'translate-x-5'"></span>
                </button>
            </div>

            <div>
                <label for="fb-default-share" class="{{ $ui['label'] }}">Default sharing</label>
                <select id="fb-default-share" :value="fb.default_share" @change="setDefaultShare($event.target.value)" :disabled="!canManage || patching" class="{{ $ui['select'] }} w-full sm:w-auto">
                    <option value="municipal" :selected="fb.default_share === 'municipal'">Municipal-wide posts</option>
                    <option value="all" :selected="fb.default_share === 'all'">All posts, barangay posts too</option>
                    <option value="none" :selected="fb.default_share === 'none'">None: only when the writer ticks it</option>
                </select>
                <p class="text-xs text-ink-muted mt-1">What "Also post on Facebook when published" starts as in the Balita editor. The writer can change it on each post.</p>
            </div>

            <p x-show="checkNote" class="text-xs rounded-xl px-3 py-2" :class="checkOk ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'" x-text="checkNote"></p>

            {{-- Disconnect asks here, not in a browser pop-up --}}
            <div x-show="confirmDisconnect" x-cloak role="alertdialog" aria-labelledby="fb-disconnect-q" class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 space-y-2">
                <p id="fb-disconnect-q" class="text-sm font-semibold text-rose-900">Disconnect the Facebook Page?</p>
                <p class="text-xs text-rose-900">Balita stops posting to it, and the saved token is deleted. Posts already on Facebook stay there, and keep their "View on Facebook" links.</p>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button size="sm" variant="danger" icon="unplug" x-bind:disabled="disconnecting" @click="disconnect()"><span x-text="disconnecting ? 'Disconnecting…' : 'Disconnect'"></span></x-ui.button>
                    <x-ui.button size="sm" variant="secondary" @click="confirmDisconnect = false">Keep it connected</x-ui.button>
                </div>
            </div>

            <div x-show="canManage && !formOpen && !confirmDisconnect" class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm" variant="secondary" icon="refresh-cw" x-bind:disabled="checking" @click="check()"><span x-text="checking ? 'Checking…' : 'Check connection'"></span></x-ui.button>
                <x-ui.button size="sm" x-show="state === 'problem'" icon="plug-zap" @click="openForm()">Reconnect</x-ui.button>
                <x-ui.button size="sm" variant="ghost" icon="key-round" x-show="state !== 'problem'" @click="openForm()">Replace token</x-ui.button>
                <x-ui.button size="sm" variant="ghost" icon="unplug" @click="confirmDisconnect = true">Disconnect</x-ui.button>
            </div>
            <p x-show="!canManage" class="{{ $ui['note'] }}"><i data-lucide="lock" class="w-4 h-4 shrink-0 mt-px"></i><span>Only officers who can post in Balita, and technical administrators, change this.</span></p>
        </div>

        {{-- Not connected --}}
        <p x-show="state === 'none'" class="{{ $ui['note'] }}"><i data-lucide="info" class="w-4 h-4 shrink-0 mt-px"></i><span>Not connected. Balita posts are not shared to Facebook.</span></p>

        {{-- How to get the Page ID and a token, and the form --}}
        <div x-show="canManage && (state === 'none' || formOpen)" class="space-y-4">
            <details class="rounded-xl border border-brand-600/10 bg-white/60 p-3 text-xs text-ink-body" :open="state === 'none'">
                <summary class="cursor-pointer select-none text-sm font-semibold text-ink">How to get the Page ID and an access token</summary>
                <ol class="mt-2 space-y-1.5 list-decimal pl-5">
                    <li>Open <span class="font-semibold">Meta Business Suite</span> (business.facebook.com) with an account that manages the LGU's business portfolio, then <span class="font-semibold">Settings › Business settings</span>. The municipality's Facebook Page and its Meta app must both belong to that portfolio.</li>
                    <li>Under <span class="font-semibold">Users › System users</span>, add a system user (Admin). Use <span class="font-semibold">Assign assets</span> to give it the Page, with permission to create content, and the app.</li>
                    <li>Press <span class="font-semibold">Generate token</span>, choose the app, set it to <span class="font-semibold">Never</span> expire, and tick <span class="font-mono">pages_manage_posts</span>, <span class="font-mono">pages_read_engagement</span> and <span class="font-mono">pages_show_list</span>. Copy the token at once: it is shown only once.</li>
                    <li>Find the <span class="font-semibold">Page ID</span>: on the Page, <span class="font-semibold">About › Page transparency</span>. It is a long number.</li>
                    <li>In the Meta app's dashboard, set the app to <span class="font-semibold">Live</span>. While it is in Development mode, posts are seen only by the app's own admins.</li>
                </ol>
                <p class="mt-2 text-ink-muted">A Page access token made by a person (for example in the Graph API Explorer) also works, but it stops when that person changes their Facebook password or leaves the Page. A system user's token is not tied to anyone.</p>
            </details>

            <form @submit.prevent="connect()" class="space-y-3" novalidate>
                <div>
                    <label for="fb-page-id" class="{{ $ui['label'] }}">Page ID</label>
                    <input id="fb-page-id" type="text" inputmode="numeric" autocomplete="off" x-model="form.page_id" maxlength="40" placeholder="e.g. 104512345678901" class="{{ $ui['input'] }} font-mono">
                    <p x-show="fieldError('page_id')" class="{{ $ui['error'] }}" x-text="fieldError('page_id')"></p>
                </div>
                <div>
                    <label for="fb-token" class="{{ $ui['label'] }}">Access token</label>
                    <div class="relative">
                        <input id="fb-token" :type="showToken ? 'text' : 'password'" autocomplete="off" spellcheck="false" x-model="form.access_token" maxlength="1000" placeholder="Paste the token here" class="{{ $ui['input'] }} font-mono pr-11">
                        <button type="button" @click="showToken = !showToken" :aria-label="showToken ? 'Hide token' : 'Show token'" :aria-pressed="showToken ? 'true' : 'false'" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg grid place-items-center text-ink-muted hover:text-ink hover:bg-white/70">
                            <span x-show="!showToken"><i data-lucide="eye" class="w-4 h-4"></i></span>
                            <span x-show="showToken" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                        </button>
                    </div>
                    <p x-show="fieldError('access_token')" class="{{ $ui['error'] }}" x-text="fieldError('access_token')"></p>
                    <p class="text-[11px] text-ink-muted mt-1">The server checks it with Facebook before saving and keeps it encrypted. It is never shown again.</p>
                </div>
                <p x-show="connectError" role="alert" class="text-xs text-rose-700" x-text="connectError"></p>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button type="submit" size="sm" icon="plug-zap" x-bind:disabled="connecting || !formReady"><span x-text="connecting ? 'Checking with Facebook…' : (state === 'none' ? 'Connect page' : 'Save and reconnect')"></span></x-ui.button>
                    <x-ui.button size="sm" variant="secondary" x-show="state !== 'none'" @click="closeForm()">Cancel</x-ui.button>
                </div>
            </form>
        </div>
        <p x-show="state === 'none' && !canManage" class="{{ $ui['note'] }}"><i data-lucide="lock" class="w-4 h-4 shrink-0 mt-px"></i><span>Only officers who can post in Balita, and technical administrators, connect the Page.</span></p>
    </div>
</x-ui.card>
</div>
