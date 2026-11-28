<?php

use Illuminate\Support\Facades\Route;

// Entry point — the single authentication screen where the user chooses
// between the Citizen Portal and the LGU Personnel (Administrative) Portal.
Route::view('/', 'auth.select-portal')->name('home');

// Citizen auth
Route::view('/citizen/login', 'auth.citizen-login')->name('citizen.login');
Route::view('/citizen/register', 'auth.citizen-register')->name('citizen.register');
Route::view('/citizen/password-reset', 'auth.citizen-password-reset')->name('citizen.password-reset');

// LGU Personnel auth
Route::view('/admin/login', 'auth.admin-login')->name('admin.login');

// Shared logout stub — frontend-only, returns to the portal chooser.
Route::view('/logout', 'auth.select-portal')->name('logout');
// Served by Netlify for any address that is not a page (netlify.toml).
Route::view('/404', 'errors.not-found')->name('not-found');

// Citizen Portal
Route::view('/citizen/dashboard', 'citizen.dashboard')->name('citizen.dashboard');
Route::view('/citizen/profile', 'citizen.profile')->name('citizen.profile');
Route::view('/citizen/document-requests', 'citizen.document-requests')->name('citizen.document-requests');
Route::view('/citizen/assistance-requests', 'citizen.assistance-requests')->name('citizen.assistance-requests');
Route::view('/citizen/notifications', 'citizen.notifications')->name('citizen.notifications');
Route::view('/citizen/directory', 'citizen.directory')->name('citizen.directory');
Route::view('/citizen/announcements', 'citizen.announcements')->name('citizen.announcements');
Route::view('/citizen/events', 'citizen.events')->name('citizen.events');
Route::view('/citizen/settings', 'citizen.settings')->name('citizen.settings');
// Scope round 2026-10-15: appointments at an office. (A separate Services page
// was removed the same day by the owner: each service list lives in its own
// tab, Dokyu or Tulong.)
Route::view('/citizen/appointments', 'citizen.appointments')->name('citizen.appointments');
// Emergency (Sakuna, the citizen slice): hotlines, evacuation centres, alerts,
// and reporting an incident and following it - the app's Sakuna tab.
Route::view('/citizen/emergency', 'citizen.emergency')->name('citizen.emergency');

// Public document verification: the QR on an issued document opens this page
// with ?code=, and anyone can type a code or control number (2026-10-15).
Route::view('/verify', 'verify')->name('verify');

// Help & Support — FAQs, Tutorials, and Support merged into one page with tabs.
// The three route names are kept so existing links deep-link into the right tab.
Route::view('/citizen/help', 'citizen.help', ['tab' => 'faqs'])->name('citizen.faqs');
Route::view('/citizen/help/tutorials', 'citizen.help', ['tab' => 'tutorials'])->name('citizen.tutorials');
Route::view('/citizen/help/support', 'citizen.help', ['tab' => 'support'])->name('citizen.support');

// Legal — Privacy Policy and Terms & Conditions merged into one page with tabs,
// same pattern as Help & Support above.
Route::view('/citizen/legal/privacy-policy', 'citizen.legal', ['tab' => 'privacy'])->name('citizen.privacy-policy');
Route::view('/citizen/legal/terms', 'citizen.legal', ['tab' => 'terms'])->name('citizen.terms');

// Administrative Portal
Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
// Notifications: the bell's "See all notifications" (28 Nov 2026); no sidebar entry.
Route::view('/admin/notifications', 'admin.notifications')->name('admin.notifications');
// Appointments: bookings, schedules and closed days, with their own sidebar
// entry (owner, 2026-10-15; first a Dashboard tab, whose address still opens it).
Route::view('/admin/appointments', 'admin.appointments')->name('admin.appointments');
Route::view('/admin/dashboard/appointments', 'admin.appointments');
Route::view('/admin/document-requests', 'admin.document-requests')->name('admin.document-requests');
Route::view('/admin/assistance-requests', 'admin.assistance-requests')->name('admin.assistance-requests');
// Each tab's own types, edited where they are worked (owner, 2026-10-30; they
// were Settings > Service Catalog). Pages of their own, so the editor adds
// nothing to the Dokyu and Tulong pages.
Route::view('/admin/document-requests/types', 'admin.request-types', ['kind' => 'dokyu'])->name('admin.document-requests.types');
Route::view('/admin/assistance-requests/types', 'admin.request-types', ['kind' => 'tulong'])->name('admin.assistance-requests.types');
// Staff access (owner decision 2026-11-20): what the office's Department Staff
// may do, set by its Head. A view of each tab, never a sidebar entry.
Route::view('/admin/document-requests/staff-access', 'admin.staff-access', ['kind' => 'dokyu'])->name('admin.document-requests.staff-access');
Route::view('/admin/assistance-requests/staff-access', 'admin.staff-access', ['kind' => 'tulong'])->name('admin.assistance-requests.staff-access');
Route::view('/admin/payments', 'admin.payments')->name('admin.payments');

// Constituents — Registry, Profiling Coverage, and Data Quality as tabs.
Route::view('/admin/constituents', 'admin.constituents', ['tab' => 'registry'])->name('admin.constituents');
Route::view('/admin/constituents/families', 'admin.constituents', ['tab' => 'registry', 'view' => 'families'])->name('admin.constituents.families');
Route::view('/admin/constituents/households', 'admin.constituents', ['tab' => 'registry', 'view' => 'households'])->name('admin.constituents.households');
Route::view('/admin/constituents/profiling', 'admin.constituents', ['tab' => 'profiling'])->name('admin.resident-profiling');
Route::view('/admin/constituents/data-quality', 'admin.constituents', ['tab' => 'data-quality'])->name('admin.constituents.data-quality');

// Papeles — citizen document repository, browsed Barangay -> Individual -> Documents.
Route::view('/admin/papeles', 'admin.papeles')->name('admin.papeles');

// Communications — Balita (announcements, banners, events, blotter) in one page with tabs.
Route::view('/admin/communications', 'admin.communications', ['tab' => 'balita'])->name('admin.communications');
// The directory (offices and barangays) is Settings > Direktoryo (2026-10-21): the old address is
// Balita's page, which sends it on (Settings' own scripts load only under /admin/settings).
Route::view('/admin/communications/offices', 'admin.communications', ['tab' => 'offices'])->name('admin.directory');
Route::view('/admin/communications/blotter', 'admin.communications', ['tab' => 'blotter'])->name('admin.communications.blotter');
Route::view('/admin/communications/banners', 'admin.communications', ['tab' => 'banners'])->name('admin.communications.banners');
// Feedback and support tickets (2026-10-15) are Settings > Feedback since
// 2026-11-18: the old address sends the browser on, with its ?ref=.
Route::view('/admin/communications/feedback', 'admin.forward', ['to' => '/admin/settings/feedback', 'label' => 'Feedback', 'active' => 'settings'])->name('admin.communications.feedback');
Route::view('/admin/announcements', 'admin.communications', ['tab' => 'balita'])->name('admin.announcements');

// Reports & Analytics — merged into one page with tabs.
Route::view('/admin/reports', 'admin.reports', ['tab' => 'reports'])->name('admin.reports');
Route::view('/admin/reports/analytics', 'admin.reports', ['tab' => 'analytics'])->name('admin.analytics');

// User Management — Personnel and Roles merged into one page with tabs.
Route::view('/admin/users', 'admin.users', ['tab' => 'personnel'])->name('admin.users');
Route::view('/admin/users/roles', 'admin.users', ['tab' => 'roles'])->name('admin.roles');
Route::view('/admin/users/permissions', 'admin.users', ['tab' => 'permissions'])->name('admin.permissions');
Route::view('/admin/users/departments', 'admin.users', ['tab' => 'departments'])->name('admin.departments');

// Internal Forms — staff self-service forms (HR, GSO, Agriculture, MSWD), separate from citizen-facing Dokyu/Tulong.
Route::view('/admin/internal-forms', 'admin.internal-forms', ['office' => 'hr'])->name('admin.internal-forms');

// Settings — General, Direktoryo, Feedback, Notifications, Security, Branding, Integrations, Audit Logs as tabs.
Route::view('/admin/settings', 'admin.settings', ['tab' => 'general'])->name('admin.settings');
Route::view('/admin/settings/branding', 'admin.settings', ['tab' => 'branding'])->name('admin.branding');
Route::view('/admin/settings/directory', 'admin.settings', ['tab' => 'directory'])->name('admin.settings.directory');
// Residents' feedback and support tickets (owner, 2026-11-18: out of Balita,
// into Settings), for anyone holding comm_feedback view.
Route::view('/admin/settings/feedback', 'admin.settings', ['tab' => 'feedback'])->name('admin.settings.feedback');
Route::view('/admin/settings/audit-logs', 'admin.settings', ['tab' => 'audit-logs'])->name('admin.audit-logs');
// Support tickets were Balita's Feedback tab from 2026-10-15; the old address
// sends the browser on to Settings > Feedback.
Route::view('/admin/settings/support', 'admin.forward', ['to' => '/admin/settings/feedback', 'label' => 'Feedback', 'active' => 'settings'])->name('admin.support');
// The Service Catalog left Settings (2026-10-30): its old address sends the
// browser on to Dokyu's Document types.
Route::view('/admin/settings/service-catalog', 'admin.forward', ['to' => '/admin/document-requests/types', 'label' => 'Document types', 'active' => 'document-requests'])->name('admin.settings.catalog');
Route::view('/admin/settings/templates', 'admin.settings', ['tab' => 'templates'])->name('admin.settings.templates');
Route::view('/admin/settings/system', 'admin.settings', ['tab' => 'system'])->name('admin.settings.system');
Route::view('/admin/settings/site-content', 'admin.settings', ['tab' => 'site-content'])->name('admin.settings.site-content');

// Sakuna — Disaster Risk Reduction, Emergency Operations, Evacuation, Relief, and Recovery Management.
// All sub-routes resolve to the same view with a tab parameter.
Route::view('/admin/sakuna', 'admin.sakuna', ['tab' => 'command-center'])->name('admin.sakuna');
Route::view('/admin/sakuna/vulnerability', 'admin.sakuna', ['tab' => 'vulnerability'])->name('admin.sakuna.vulnerability');
Route::view('/admin/sakuna/incidents', 'admin.sakuna', ['tab' => 'incidents'])->name('admin.sakuna.incidents');
Route::view('/admin/sakuna/missing-persons', 'admin.sakuna', ['tab' => 'missing-persons'])->name('admin.sakuna.missing-persons');
Route::view('/admin/sakuna/evacuation-centers', 'admin.sakuna', ['tab' => 'evacuation-centers'])->name('admin.sakuna.evacuation-centers');
Route::view('/admin/sakuna/evacuees', 'admin.sakuna', ['tab' => 'evacuees'])->name('admin.sakuna.evacuees');
Route::view('/admin/sakuna/resources', 'admin.sakuna', ['tab' => 'resources'])->name('admin.sakuna.resources');
Route::view('/admin/sakuna/relief', 'admin.sakuna', ['tab' => 'relief'])->name('admin.sakuna.relief');
Route::view('/admin/sakuna/damage-assessment', 'admin.sakuna', ['tab' => 'damage-assessment'])->name('admin.sakuna.damage-assessment');
Route::view('/admin/sakuna/bdrrmc-forms', 'admin.sakuna', ['tab' => 'bdrrmc-forms'])->name('admin.sakuna.bdrrmc-forms');
Route::view('/admin/sakuna/alerts', 'admin.sakuna', ['tab' => 'alerts'])->name('admin.sakuna.alerts');
Route::view('/admin/sakuna/reports', 'admin.sakuna', ['tab' => 'reports'])->name('admin.sakuna.reports');

// Lazy partials (page budget, 2026-11-25): a window most visits never open,
// kept out of its page's HTML and served on its own. One address per file in
// resources/views/lazy/; the export writes each to dist/partials/<name>.html
// (esperanza_export_partials) and the page's <x-lazy-partial> fetches it.
foreach (glob(resource_path('views/lazy/*.blade.php')) ?: [] as $lazyPartialFile) {
    $lazyPartialName = basename($lazyPartialFile, '.blade.php');
    Route::view('/partials/'.$lazyPartialName.'.html', 'lazy-frame', ['lazyPartialName' => $lazyPartialName]);
}
