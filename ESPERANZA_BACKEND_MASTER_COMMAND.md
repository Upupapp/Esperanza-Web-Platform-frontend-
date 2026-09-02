# Esperanza Web Platform — Backend Master Command

**Municipality of Esperanza, Masbate · Region V (Bicol) · 20 barangays**

Issued 2026-08-29. Derived from the frontend at commit `dd7e0d4`
(`Upupapp/Esperanza-Web-Platform-frontend-`, branch `main`, 2026-08-28).

---

## How to read this book

The frontend is finished and frozen in shape. It is **frontend-only by
constitution** — `CLAUDE.md` forbids controllers, models, migrations, APIs and
auth in that repo, and the repo honours it: `app/` holds three skeleton files and
`routes/web.php` is 55 `Route::view` lines. Every screen runs on mock data in 15
`config/esperanza_*.php` files, and the whole site deploys as a static HTML
export.

That mock data is not decoration. It is a **specification written in PHP**: the
entities, the field names, the enums, the fee rules, the role matrix and the
request lifecycle are all already decided, already reviewed by the client, and
already visible on screen. This book turns that specification into a backend
work order.

**TAB 00 is the comprehensive command** — the single, self-contained brief to
hand a backend developer or agent that covers the whole programme end to end.
TABs 01–20 are the same programme broken into finishable units, each with its
own scope, inputs, deliverables and acceptance gate. Work TAB 00 if you want one
paste; work 01–20 if you want a schedule.

### Five hard rules for this backend

1. **The frontend is the contract.** Field names, enum values and status strings
   come from the `config/esperanza_*.php` files and are not up for
   redesign. If the backend wants a different shape, that is a change request
   against the frontend, not a silent divergence.
2. **Fail closed.** Every permission, geographic-scope and department-scope
   check must be re-implemented server-side and must deny by default. The
   client's own gating in `resources/js/app.js` **also fails closed as of
   TAB 08** — `can()`, `canPerm()`, `inScope()`, `inDeptScope()` and
   `canExportReports()` all return `false` with no session — but that is
   defence in depth, not the boundary. Nothing on the client may be trusted
   either way.

   *(Superseded 2026-08-30. This rule previously described the client as
   failing OPEN and justified the server rule with that. The instruction was
   always right; only its justification expired. Do not go looking for a
   fail-open client gate — there isn't one.)*
3. **The status vocabulary is closed, and it now has two parts.** Dokyu and
   every other service use the fifteen values listed in `CLAUDE.md`. **Tulong
   runs its own separate vocabulary** — a documented exception in `CLAUDE.md`,
   specified in `PROCESS_TAB_FLOW.txt` PART 2 and detailed in TAB 08. Neither
   set may be extended, and neither may be used for the other.
4. **Bilingual is a data requirement, not a view concern.** The UI ships an
   `en`/`fil` language store defaulting to **Filipino**, and citizen
   notifications already carry `en` and `fil` bodies. Any content the backend
   owns and the citizen reads needs both.
5. **This is government data about identifiable residents.** RA 10173 (Data
   Privacy Act) applies to every table in TABs 05, 06 and 15. The
   `view_sensitive` permission already exists in the role matrix; the backend
   has to make it mean something.

---

## What the frontend already decided — the measured inventory

Every figure below was counted from the repo at `dd7e0d4`, not estimated.

| Dimension | Count | Source |
| --- | --- | --- |
| Barangays | 20 | `config/esperanza.php` |
| Page routes | 55 | `routes/web.php` |
| Blade views | 98 (36,338 lines) | `resources/views/**` |
| Permission modules | 10 | `esperanza_rbac.permission_modules` |
| Permission submodules | 77 | same |
| Permission types | 28 | `esperanza_rbac.permission_types` |
| Distinct (module, submodule, action) triples | **490** | same |
| Roles | 9 (1 uneditable) | `esperanza_rbac.roles` |
| Personnel accounts seeded | 33 across 26 departments | `esperanza_rbac.accounts` |
| Dokyu services | 28 submodules / 25 form specs | `esperanza_rbac`, `esperanza_service_form_specs` |
| Municipal document specs (fees, requirements, process, fields) | 15 | `esperanza_municipal_documents` |
| Tulong programs | 13 | `esperanza_tulong_types` |
| Internal staff forms | 24 across 4 offices | `esperanza_internal_forms` |
| Sakuna submodules | 10 (11 tab routes) | `esperanza_rbac`, `routes/web.php` |
| Status vocabularies | **2** — 15 canonical (Dokyu et al.) + Tulong's own 11 | `CLAUDE.md`, `PROCESS_TAB_FLOW.txt` PARTs 1 and 2 |
| Standard rejection reasons | 14 | `esperanza_validation_guide.standard_reasons` |
| Decision outcomes | 5 | same |
| Evidence hierarchy levels | 4 | same |
| Services with written validation checks | 31 (19 Dokyu, 12 Tulong) | same |
| Declared source gaps needing LGU confirmation | 6 | `esperanza_validation_guide.known_gaps` |

**Seeded scale to design against** (`esperanza_constituents.municipalStats`):
17,842 residents · 4,918 households · 6,204 families · 13,894 verified ·
8,420 citizen accounts.

---

## TAB 00 — THE COMPREHENSIVE BACKEND COMMAND

> Hand this section, whole, to the backend developer or agent. It is
> self-contained. TABs 01–20 expand it; they do not contradict it.

### The command

Build the production backend for the **Esperanza Web Platform**, the LGU digital
platform for the Municipality of Esperanza, Masbate. A complete, client-reviewed
frontend already exists at `Upupapp/Esperanza-Web-Platform-frontend-` (commit
`dd7e0d4`). It is a Laravel 12 + Blade + Alpine application that currently runs
entirely on mock data and deploys as a static export. Your job is to replace
that mock data with a real, secured, auditable backend **without changing a
single field name, enum value or status string the frontend already uses**.

Treat the frontend repo as the requirements document. Specifically, treat these
files as normative:

```
CLAUDE.md                              the project constitution + the 15 canonical statuses
PROCESS_TAB_FLOW.txt                   the complete Dokyu/Tulong request lifecycle
config/esperanza.php                   the 20 barangays
config/esperanza_rbac.php              10 modules, 77 submodules, 28 permission types,
                                       490 permission triples, 9 roles, 33 accounts
config/esperanza_municipal_documents.php   15 document specs: fees, requirements,
                                       process steps, and the exact form fields
config/esperanza_tulong_types.php      13 assistance programs with amounts and requirements
config/esperanza_service_form_specs.php    25 multi-step citizen form definitions
config/esperanza_document_type_lookup.php  25 document -> template/preview mappings
config/esperanza_barangay_documents.php    per-barangay officials, seals, and issuable documents
config/esperanza_constituents.php      household / family / individual / coverage / data-quality shapes
config/esperanza_validation_guide.php  decision outcomes, 14 standard reasons,
                                       the 4-level evidence hierarchy, per-service checks
config/esperanza_payments.php          the payment record shape
config/esperanza_site_content.php      banners, privacy policy, terms, support content
config/esperanza_balita.php            announcements and community posts
config/esperanza_internal_forms.php    24 staff forms across 4 offices
resources/js/app.js                    the client-side session/permission model you must
                                       re-implement server-side and FAIL CLOSED
```

**Deliver, in this order:**

1. **A published API contract first.** OpenAPI 3.1, versioned, in its own repo or
   its own directory, reviewed and frozen before implementation. Every endpoint,
   every enum, every error body. The contract is the authority for both sides;
   the frontend team generates its client from it. Nothing ships that the
   contract does not describe.

2. **Identity and access.** Citizen self-registration with the documented flow
   (Registration → Verification → Resident Profile → Dashboard), and **no admin
   self-registration** — LGU personnel accounts are provisioned internally, as
   the frontend already implies. Sessions, refresh, lockout, password policy,
   and MFA for any role that can approve, release, or read sensitive data.

3. **RBAC that actually enforces.** Reproduce the full matrix: 10 modules, 77
   submodules, 28 permission types, 490 triples, 9 roles. Enforce **three
   independent scopes** on every request, exactly as the frontend models them:
   *module access*, *geographic scope* (`Municipal` sees all; a barangay account
   sees only its own barangay), and *department scope* (only Department Head and
   Department Staff are office-restricted; Front Desk, Records Officer,
   Municipal Admin and Super Admin cross offices). Super Administrator is not
   editable and not deletable. Deny by default, always.

4. **The service catalog as data, not code.** 28 Dokyu services and 13 Tulong
   programs, each with its office, level, fee rule, processing days,
   requirements list, process steps and form field spec, all administrable from
   the Settings → Service Catalog UI that already exists. Fees are **rules, not
   constants** — Cedula is `₱5.00 base + ₱1.00 per ₱1,000 income`, Business
   Permit is capital-based, Business Permit Renewal is based on prior-year gross
   receipts, Locational Clearance is project-based. Model fee computation as an
   evaluable rule with a stored, auditable result per request.

5. **The request lifecycle, precisely as specified.** `PROCESS_TAB_FLOW.txt` is
   not a summary — it is the spec. **PART 1 is Dokyu only**; Tulong was split off
into PART 2 and is TAB 08's separate engine.
   Implement all of it: the 7 admin statuses; auto-advance from Submitted to
   Under Verification on first open; per-requirement statuses (Pending Review,
   Approved, Flagged for Replacement, Rejected, Resubmitted); the rule that
   flagging *any* requirement rolls the whole request to Under Review; the
   citizen-facing Resubmitted state that the admin side deliberately does not
   have; Reject requiring typed decision remarks; Release blocked until proof of
   release is captured; Released and Rejected terminal. And the integrity rules:
   **the ref never changes, nothing is ever silently overwritten, every
   requirement keeps a full `versions` history, and whole-request remarks
   survive reopening.**

6. **Everything that hangs off it.** Requirement file uploads with malware
   scanning and signed, expiring, permission-checked URLs. Document issuance
   that renders the real certificate (the frontend already has the templates,
   the per-barangay officials and the seals) with a QR or serial that a third
   party can verify. Payments: assessment, invoice, receipt, reconciliation.
   Notifications in Filipino and English. Papeles, the citizen document
   repository, with `view_sensitive` enforced. Constituents with household /
   family / individual relationships, profiling coverage per barangay, and the
   data-quality queue with merge. Communications: announcements with a
   Draft → Review → Publish → Archive lifecycle, community-post moderation,
   events, directory, blotter, banners. The 24 internal staff forms. The full
   Sakuna disaster-operations module. Reports and analytics with real exports.
   Settings, branding and site content.

7. **Audit and privacy as first-class features.** The Audit Logs UI already
   exists and is currently decorative. Every state transition, every permission
   grant, every sensitive-record read, every export, every login must be written
   to an append-only audit trail carrying actor, role, scope, target, before/after
   and timestamp. Apply RA 10173: lawful basis, retention schedule, data-subject
   access and correction, breach procedure.

**Constraints, non-negotiable:**

- Do not rename anything the frontend reads. Match `config/esperanza_*.php` keys
  exactly.
- Do not add a status to either vocabulary. `CLAUDE.md`'s fifteen govern Dokyu
  and everything else; Tulong has its own set (TAB 08). Do not mix them.
  `Processing`, `Assigned` and `Completed` exist in that vocabulary but are
  **deliberately unused** as Dokyu/Tulong stages — leave them unused.
- Do not trust the client. The frontend's permission helpers fail *open*; yours
  must fail *closed*.
- Do not invent policy. Six services carry explicit "no official Esperanza source
  found" gaps in `esperanza_validation_guide.known_gaps`; those need an LGU
  ruling before their rules are coded. Ship them behind the existing catalog
  data and flag them, rather than guessing fees or eligibility.
- Bilingual (`fil` default, `en` secondary) for every citizen-facing string the
  backend owns.

**Definition of done:** the frontend runs against the real API with its
`config/esperanza_*.php` mock files deleted, no field renamed, no status added,
every one of the 490 permission triples enforced server-side, and an
independently-run acceptance suite proving the lifecycle in
`PROCESS_TAB_FLOW.txt` — including the correction loop and the version history —
behaves exactly as written.

---
## The TAB index

| TAB | Title | Depends on | Size |
| --- | --- | --- | --- |
| 00 | The comprehensive backend command | — | the whole programme |
| 01 | Foundation, environments, and the API contract | — | M |
| 02 | Identity, registration, and sessions | 01 | M |
| 03 | RBAC — 490 triples, 3 scopes, fail closed | 01, 02 | L |
| 04 | Reference data and the service catalog | 01 | L |
| 05 | Constituents — residents, families, households | 01–03 | L |
| 06 | Papeles — the citizen document repository | 03, 05, 09 | M |
| 07 | Dokyu — document request lifecycle | 03, 04, 09 | XL |
| 08 | Tulong — assistance request lifecycle | 07 | M |
| 09 | Uploads, storage, and file integrity | 01 | M |
| 10 | Document issuance, rendering, and verification | 04, 07 | L |
| 11 | Payments — assessment, receipt, reconciliation | 04, 07, 08 | L |
| 12 | Communications — balita, events, directory, blotter, banners | 03 | L |
| 13 | Notifications — bilingual, multi-channel | 07, 08, 12 | M |
| 14 | Internal forms — 24 forms, 4 offices | 03 | M |
| 15 | Sakuna — disaster risk and emergency operations | 03, 05 | XL |
| 16 | Reports, analytics, and exports | 05, 07, 08, 11 | L |
| 17 | Audit logs and data privacy (RA 10173) | 02, 03 | L |
| 18 | Settings, branding, and site content | 03 | M |
| 19 | Non-functional — performance, availability, operations | all | M |
| 20 | Contract handover and certification gate | all | M |

---

## TAB 01 — Foundation, environments, and the API contract

**Scope.** Stand up the backend repository, environments, and the versioned API
contract that both sides build against.

**Do.**

- Create the backend as its **own repository**. Do not add it to the frontend
  repo — that repo's constitution forbids backend code, and a test in the
  frontend would have to be broken to allow it.
- Publish an **OpenAPI 3.1 contract** and freeze it before implementation. Every
  endpoint, request body, response body, enum, pagination envelope and error
  shape. Version it. The frontend generates its client from this document; when
  the two disagree, the contract wins and the divergence is a bug in whichever
  side drifted.
- Standardise the envelope once, at the start: pagination, sorting, filtering,
  and a single error body carrying a machine code, a Filipino message and an
  English message. Every list endpoint in this platform is filtered by barangay,
  by status, by date range and by service type — design that once rather than
  28 times.
- Environments: local, staging, production. Staging seeded with the **exact
  fixture data currently in `config/esperanza_*.php`**, so the frontend can be
  pointed at staging and look identical to the demo the client already approved.
- CI from commit one: lint, static analysis, tests, contract validation, and a
  migration-drift check.

**Acceptance.** The contract validates, is published, and a frontend developer
can generate a typed client from it and compile.

---

## TAB 02 — Identity, registration, and sessions

**Scope.** Real authentication for two distinct populations with different rules.

**The frontend has already decided the shape.** One entry screen offers
**Citizen Login** or **LGU Personnel Login**. Citizen registration exists at
`/citizen/register` and is a two-step form (Personal Info → Create Account,
labelled bilingually). **Admin registration does not exist and must not be
built** — personnel accounts are provisioned internally.

**Do.**

- Citizen registration → verification → resident profile → dashboard, as the
  documented standard workflow. Decide and implement the verification channel
  (mobile OTP is the realistic choice for this municipality; email alone will
  exclude residents).
- Citizen accounts carry: `id` (the `ESP-RES-YYYY-####` account number the
  frontend already displays), name parts, email, mobile, barangay, purok,
  address, birthdate, sex, civil status, occupation, `residentSince`,
  `profileCompleteness`, `status`. Those keys are from
  `config/esperanza_citizens.accounts` — match them.
- **`profileCompleteness` is a computed server value**, not a stored number the
  client can set. Define the formula once; the citizen profile page and the
  admin profiling coverage report must agree.
- Personnel accounts carry `id`, `name`, `roleKey`, `dept`, `scope`, `status`,
  and derived `permissions`/`moduleKeys`. Statuses observed in the seed:
  `Approved`, `Pending Review`, `Archived` — personnel accounts have their own
  approval gate, so build it.
- Sessions: server-side, revocable, with an explicit lifetime. Lockout and rate
  limiting on both login screens. **MFA required** for any role holding
  `approve`, `release`, `delete` or `view_sensitive`.
- Password reset, mobile change and email change all need re-verification.

**Do not.** Do not build admin self-registration. Do not let a citizen account
and a personnel account share an identity table without a hard type
discriminator — they have different lifecycles, different scopes, and different
privacy obligations.

**Acceptance.** A citizen can register, verify, and reach a dashboard. A
personnel account cannot be created except by an authorised provisioner, and
that provisioning is audited.

---

## TAB 03 — RBAC: 490 triples, three scopes, fail closed

**Scope.** The single most detailed thing the frontend has already specified,
and the single most dangerous thing to approximate.

**The matrix, as measured:**

| Module | Submodules | Permission triples | Actions used |
| --- | --- | --- | --- |
| `dokyu` | 28 | 215 | view, create, edit, review, approve, reject, release, archive, assign |
| `tulong` | 13 | 99 | view, create, edit, review, approve, reject, assign, release, archive |
| `sakuna` | 10 | 67 | view, create, edit, dispatch, escalate, close, export, approve, validate, assign, resolve, open, check_in, transfer, check_out, deploy, return, release, review, publish, cancel, archive, print |
| `communications` | 6 | 30 | view, create, edit, delete, review, approve, archive, reject |
| `constituents` | 6 | 27 | view, create, edit, delete, export, verify, assign, merge, resolve, view_sensitive, reject |
| `payments` | 2 | 12 | view, review, approve, reject, export, print |
| `reports` | 4 | 12 | view, export, print |
| `user_management` | 3 | 10 | view, create, edit, delete, export |
| `settings` | 4 | 8 | view, edit |
| `papeles` | 1 | 5 | view, view_sensitive, verify, edit, export |
| **Total** | **77** | **490** | 28 distinct types |

**The nine roles**, with the user counts the frontend advertises:

| Role | Users | Editable | Reach |
| --- | --- | --- | --- |
| Super Administrator | 1 | **no** | everything, including roles and system config |
| Municipal Administrator | 2 | yes | full operations + personnel and role management |
| Department Head | 6 | yes | reviews/approves/releases **their own department's** requests |
| Department Staff | 24 | yes | day-to-day processing **within their department** |
| Front Desk Officer | 8 | yes | intake across offices, walk-in assistance |
| Records Officer | 3 | yes | resident/household/barangay records, civil registry copies |
| Information Officer | 2 | yes | announcements, events, community-post moderation |
| Barangay Secretary | 20 | yes | **their own barangay's** certifications and clearances |
| Citizen | 8,420 | yes | own requests and own profile only |

**Three independent scopes, all enforced server-side:**

1. **Module access** — does the account hold the module at all.
2. **Geographic scope** — `scope === 'Municipal'` sees every barangay; any other
   value sees only the barangay it names. 20 of the 33 seeded accounts are
   barangay-scoped.
3. **Department scope** — *only* `department_head` and `department_staff` are
   restricted to their own office. Front Desk, Records Officer, Municipal Admin
   and Super Admin deliberately cross offices. This asymmetry is explicit in
   `resources/js/app.js` and is a real business rule, not an oversight.

**Do.**

- Model permissions as `(module, submodule, action)` and check them per request.
  A role is a named bundle of triples; a bundle is not the check.
- Enforce scope **in the query**, not in the response filter. A barangay
  secretary must not be able to page past their barangay by manipulating a
  parameter.
- Super Administrator: uneditable, undeletable, and the attempt is audited.
- Role edits are audited with before/after (TAB 17).
- Provide the effective-permission read the Users → Permissions screen needs:
  given an account, the resolved triple set.

**Critical.** No session, no permission, no data. The client's helpers now deny
by default too (TAB 08), so both sides agree — but agreement is not delegation:
the server is the only boundary, and it must reach that answer on its own rather
than trusting anything the client sends.

**Acceptance.** An automated test asserts all 490 triples: each role's granted
triples succeed and each denied triple returns 403. A barangay-scoped account
cannot read another barangay's rows by any parameter. An unauthenticated request
gets nothing.

---

## TAB 04 — Reference data and the service catalog

**Scope.** The catalog every other module reads from. Get this wrong and Dokyu,
Tulong, Payments and Reports are all wrong.

**Contents, as measured:**

- **20 barangays** (`config/esperanza.php`) — the canonical list, and
  `CLAUDE.md` requires everything to pull from it rather than hardcode.
- **Per-barangay profiles** (`config/esperanza_barangay_documents.php`):
  officials (`punong_barangay`, `barangay_secretary`), a full officials roster,
  history, **seal image**, contact, and the documents that barangay issues.
  These feed printed certificates, so they are issuance data, not decoration.
- **15 municipal document specs** (`config/esperanza_municipal_documents.php`),
  each with `key`, `name`, `category`, `office`, `office_short`, `level`,
  `title`, `formNo`, `fee`, `days`, `requirements[]`, `process[]`, `fields[]`,
  and a `reference_pdf`. Offices in play: MCRO, MSWDO, OSCA, MPDO, BPLO,
  Treasurer's Office, Civil Registrar, Agriculture. Levels: `Municipal` and
  `Multi-Office`.
- **13 Tulong programs** (`config/esperanza_tulong_types.php`) with `category`,
  `level`, `office`, `amount`, `fee`, `days`, `requirements[]`, `process[]`.
  Offices: MSWDO, OSCA, MPESO, MDRRMO, Mayor's Office.
- **25 multi-step form specs** (`config/esperanza_service_form_specs.php`) —
  the actual citizen-facing form: steps, and per-field `key`, `label`, `type`,
  `required`. Several entries are `alias` pointers to another spec; preserve
  aliasing rather than duplicating.
- **25 document→template mappings** (`config/esperanza_document_type_lookup.php`)
  with `docKey`, `previewType`, `certTitle`, `certSubtitle`, `nameField`.

**Fees are rules, not numbers.** Observed in the seed:

| Service | Fee rule |
| --- | --- |
| Cedula | `₱5.00 base + ₱1.00 per ₱1,000 income` |
| Business Permit (New) | `₱500.00 and up` — based on declared capital |
| Business Permit Renewal | based on prior-year gross receipts |
| Locational Clearance | `₱500.00 and up` — based on project |
| Civil registry copies (MCRO) | `₱155.00` flat |
| Certificate of Residency | `₱50.00` |
| RPT Clearance | `₱100.00` |
| Social Pension (Tulong) | `₱100.00` |
| Most Tulong programs, PWD ID, Senior Citizen ID, Pet Registration, Indigency, Delayed Registration | Free |

Model a fee as an evaluable rule with named inputs, and **store the computed
amount and the inputs used** on the request, so a receipt can be reproduced and
audited years later.

**Do.** Make the catalog administrable — the Settings → Service Catalog
submodule already exists in the permission matrix
(`settings / Service Catalog (Dokyu/Tulong Fees & Requirements)`). Version
catalog changes: a request must be assessed against the catalog **as it stood
when it was submitted**, not as it stands today.

**Blocked pending LGU ruling** — the frontend itself declares six gaps
(`esperanza_validation_guide.known_gaps`). Do not code policy for these until
the LGU confirms: Locational Clearance (no official MPDO form supplied),
Educational Assistance (generic scholarship shape only), Medical/Burial/
Financial/Food Relief (no complete official Esperanza application form found),
Social Pension (no pension-specific form found), Cedula/RPT/Live Birth (no
citizen-fillable request form found), Business Permit New (generic placeholder;
confirm the BPLO checklist and ordinance).

**Acceptance.** Catalog is seeded to match the frontend exactly; fee computation
is unit-tested against every rule above; a catalog edit does not retroactively
change an already-assessed request.

---

## TAB 05 — Constituents: residents, families, households

**Scope.** The resident master data the whole platform validates against.

**Shapes, from `config/esperanza_constituents.php`** (match these keys):

- **Households** (25 seeded): `id, label, barangay, sitio, address, head_id,
  head_name, family_ids, family_count, member_count, dwelling_type,
  housing_tenure, electricity, water, toilet, flood_risk`.
- **Families** (27): `id, label, surname, family_type, barangay, sitio,
  household_id, head_id, head_name, member_ids, member_count, profile_pct,
  verification, status, programs, notes`.
- **Individuals** (35): `id, name, sex, age, dob, civil_status, barangay, sitio,
  household_id, family_id, relation, contact, occupation, education,
  income_range, tags`.
- **Barangay coverage** (20 rows): `name, residents, profiled, hh, hh_profiled,
  encoder, last_visit, status, pct, hh_pct`.
- **Data quality issues** (12): `id, type, severity, barangay, record_id,
  record_name, description, assigned_to, created, status, merge_candidate`.

**Do.**

- Model the household → family → individual hierarchy properly, with the
  household head and family head as references into individuals, not as loose
  strings. The frontend denormalises `head_name` for display; the backend must
  keep the relation authoritative and derive the name.
- `age` is derived from `dob`. Never store it.
- **Profiling coverage is computed**, per barangay and municipality-wide, from
  real records. It currently drives a headline figure the client sees; if it is
  a stored number it will be wrong within a week.
- **The data-quality queue is a real workflow**: detect duplicates and
  inconsistencies, assign to an encoder, resolve, and **merge** — `merge`,
  `resolve`, `verify` and `view_sensitive` are all already permissions on this
  module. A merge must be reversible or, at minimum, fully reconstructible from
  the audit trail.
- `view_sensitive` gates the fields that identify and locate a person. Decide
  which fields those are with the LGU's Data Protection Officer, and enforce it
  at the query layer.

**Acceptance.** Coverage percentages recompute correctly after a profile is
added; a merge is audited and reconstructible; a barangay-scoped account sees
only its own barangay's residents.

---

## TAB 06 — Papeles: the citizen document repository

**Scope.** The per-citizen document vault, browsed Barangay → Individual →
Documents.

**Shape** (`resources/views/admin/papeles.blade.php`): documents carry `id,
name, type, category, file_type, uploaded, status`, grouped under a citizen by
`citizen_id`, with a `pending` count per citizen. Categories observed: Identity/
Verification, Civil Registry, Barangay Certificates, Tax & Property.

**Do.**

- Store the document, its type, its category, its issuing source, its upload
  time and its verification state.
- Only 5 permissions exist on this module and they are the whole surface:
  `view`, `view_sensitive`, `verify`, `edit`, `export`. There is deliberately
  **no delete** — a citizen's document repository is not something an
  administrator may erase. Honour that; if a document must go, it is an audited
  archival, not a deletion.
- Reuse: a document already verified in Papeles should be attachable to a new
  request without re-upload. That is the point of the repository, and it is what
  makes the third Dokyu request cheaper than the first.
- Retention and lawful basis per RA 10173 (TAB 17).

**Acceptance.** A document verified once is reusable across requests; no
endpoint can hard-delete; `view_sensitive` is enforced.

---

## TAB 07 — Dokyu: the document request lifecycle

**Scope.** The heart of the platform, and the most precisely specified part of
it. `PROCESS_TAB_FLOW.txt` is the specification — implement it literally.

### Whole-request statuses (7)

`Submitted` → `Under Verification` → `Approved` → `Mark to Release` → `Released`,
with `Under Review` on the correction branch and `Rejected` terminal.

### The three paths

```
STANDARD    Submitted -> Under Verification -> Approved -> Mark to Release -> Released
CORRECTION  Submitted -> Under Verification -> Under Review -> Approved
                      -> Mark to Release -> Released        (loop as many times as needed)
REJECTION   Submitted -> Under Verification -> Rejected     (also from Under Review)
```

### The rules that are easy to get wrong

- **Opening a Submitted request auto-advances it to Under Verification.** There
  is no "start review" action.
- **Per-requirement statuses** are their own vocabulary: `Pending Review`,
  `Approved`, `Flagged for Replacement`, `Rejected`, `Resubmitted`.
- **Flagging any one requirement rolls the whole request to Under Review.** That
  per-requirement flag *is* the mechanism — there is no whole-request "needs
  documents" action, deliberately, so the reason stays attached to the exact
  document that is wrong.
- Flagging requires a **reason plus remarks**, specific to that document.
- **Mark as Resubmitted** preserves the flagged file and its reason as history,
  swaps in the new file, and sets that requirement to `Resubmitted`. The
  requirement is then reviewed like any other.
- **Approve is the same action from both Under Verification and Under Review.**
  There is no separate "acknowledge the resubmission" gate on the admin side.
- **Reject requires typed decision remarks** — the action is unavailable until
  text exists, and that text is what the citizen reads.
- **Release is blocked until proof-of-release evidence is captured or uploaded.**
- `Released` and `Rejected` are terminal. Nothing moves out of them.
- **The citizen side has a status the admin side does not**: when a citizen
  submits their correction, the whole request goes to `Resubmitted` — never back
  to `Submitted`, never a new ref.
- `Processing`, `Assigned` and `Completed` are in the platform's universal
  vocabulary but are **intentionally not used** as Dokyu/Tulong stages. Leave
  them unused.

### Integrity, stated as requirements

- The **ref never changes** for the life of the request.
- **Nothing is silently overwritten.** Every requirement keeps a `versions[]`
  array; the current file, status and reason are pushed into it before being
  replaced.
- Whole-request remarks are preserved and shown when an Under Review request is
  reopened.

### Also required

- Assessment against the catalog version in force at submission (TAB 04).
- Department routing from the service's office, and the `assign` permission.
- Decision support surfaced from `esperanza_validation_guide`: 5 decision
  outcomes, **14 standard rejection reasons** (Unreadable upload, Cropped/
  incomplete, Missing front/back, Wrong document type, Expired document, Name
  mismatch, DOB mismatch, Address mismatch, Missing signature/seal, Relationship
  proof needed, Stronger identity proof, Manual issuer verification, Confirmed
  ineligible, Confirmed fraudulent/altered), the 4-level evidence hierarchy, and
  the per-service checks for 19 Dokyu services.
- **The mismatch rule** from the validation guide: if uploaded documents do not
  match the Resident Master Profile, do not silently proceed — this is a
  first-class server-side check, not guidance text.

**Acceptance.** An acceptance suite walks all three paths including a
two-iteration correction loop, and asserts: ref stability, complete version
history, remark persistence, reject-without-remarks refused, release-without-
proof refused, and terminal states immovable.

---

## TAB 08 — Tulong: the assistance request lifecycle

**Scope.** 13 assistance programs on a lifecycle **of their own**. Build a
second engine; do not reuse TAB 07's.

> **This TAB was rewritten on 2026-09-02.** It previously said Tulong shared
> Dokyu's lifecycle exactly. That is no longer true: the frontend deliberately
> split Tulong off (`PROCESS_TAB_FLOW.txt` **PART 2 — TULONG**, and a documented
> exception in `CLAUDE.md`). A backend built to the old text would implement the
> wrong workflow for every assistance program the municipality runs.

**The reasoning behind the split, which the backend must respect:** Tulong is a
**social-welfare case**, not a document-preparation request. It moves through
screening, an eligibility assessment by a social worker, an approval decision
that may change the amount, and then disbursement — not through verification of
someone's uploaded paperwork. None of PART 1 applies.

### Main stage sequence (7)

```
Submitted -> Initial Screening -> Assessment -> For Approval
          -> Assistance Processing -> Ready for Release -> Completed
```

| Status | Meaning |
| --- | --- |
| `Submitted` | Citizen submitted; admin has not opened it |
| `Initial Screening` | Admin checking the submission and requirements |
| `Assessment` | A social worker records eligibility findings and a **recommended** type/amount or package |
| `For Approval` | Awaiting an approval decision, which **may set an approved amount that differs from the recommended one** |
| `Assistance Processing` | Approved; disbursement or enrolment being prepared. Fields branch on `processingKind` |
| `Ready for Release` | Prepared and scheduled. **Blocked until Proof of Release evidence is captured** |
| `Completed` | Released and received. Terminal — and deliberately **not** called `Released`, because it records who released it, to whom, the actual amount and a reference number in one step |

### Side states — holds on the main line, not stages

| State | Branches off | Returns to |
| --- | --- | --- |
| `Needs Requirements` | Initial Screening | Initial Screening, once resolved |
| `Returned for Reassessment` | For Approval | Assessment |
| `Rejected` | Initial Screening, Assessment, or For Approval | terminal |
| `Cancelled` | Submitted through Assessment (citizen or office withdrew before a decision) | terminal |

`Rejected`, `Cancelled` and `Completed` are the only terminal states.

### Transitions, exactly

| From | Action | To |
| --- | --- | --- |
| Submitted | Start Screening | Initial Screening |
| Initial Screening | Proceed to Assessment / Request Additional Requirements / Reject\* / Cancel† | Assessment / Needs Requirements / Rejected / Cancelled |
| Needs Requirements | Requirements Received / Cancel† | Initial Screening / Cancelled |
| Assessment | Submit for Approval (**requires an eligibility finding recorded**) / Reject\* / Cancel† | For Approval / Rejected / Cancelled |
| For Approval | Approve Assistance / Return for Reassessment‡ / Reject\* | Assistance Processing / Returned for Reassessment / Rejected |
| Returned for Reassessment | Resume Assessment | Assessment |
| Assistance Processing | Mark Ready for Release | Ready for Release |
| Ready for Release | Confirm Release (**blocked without Proof of Release**) | Completed |

\* Reject requires a typed reason. † Cancel requires a typed reason.
‡ Return for Reassessment requires a typed reason — why the recommended
amount or package needs re-verification.

### `processingKind` — the field that stops per-program special-casing

Every entry in `config/esperanza_tulong_types.php` carries a `processingKind` of
`cash`, `goods`, `medical` or `registration`. Assessment, Approval, Processing
and Release all branch their fields on that value rather than on the program
name:

- **`cash`** — release method and amount (Burial, Educational, Financial,
  Pension, Solo Parent)
- **`medical`** — hospital/provider and referral fields
- **`goods`** — package, quantity, distribution fields
- **`registration`** — enrolment confirmation instead of a payout (TUPAD, TESDA,
  Livelihood, Kabuhayan, Business Plan, ERPAT)

**Model this as data on the type, not as branches in code.** The frontend
explicitly avoided special-casing Solo Parent Cash Assistance; the backend
should not reintroduce that.

### Record shape — added lazily as a case advances

```
screening    { notes, missingRequirements: [] }
assessment   { assessedBy, assessmentDate, eligibility, notes,
               recommendedAssistance, recommendedAmount, recommendedPackage }
approval     { approvedBy, approvalDate, approvedType, approvedAmount,
               approvedPackage, remarks }
returnReason, cancelReason
processing   { releaseMethod, disbursementMethod, voucherRef, package,
               quantity, distributionMethod, coverage, hospitalProvider,
               referralDetails, processingNotes }
release      { scheduledDate, releaseLocation, reference }
completion   { dateReleased, releasedBy, recipient, actualAmount,
               actualPackage, reference, remarks }
history: []  { stage, date, by, note } -- appended on every transition
```

**`recommendedAmount` and `approvedAmount` are separate fields on purpose.** The
approver may change the figure, and both numbers have to survive for audit —
collapsing them into one loses the fact that a decision was made.

### Citizen side

A 7-step stepper mirroring the main sequence. `Needs Requirements` shows the
same correction panel as Dokyu's `Under Review`, but resubmitting returns the
case to **`Initial Screening`** — Tulong has **no `Resubmitted` state**.
`Returned for Reassessment` is shown as a plain, non-actionable note, because it
is internal staff re-verification and not something the citizen can fix.
`Rejected` and `Cancelled` are both terminal informational notices with no
resubmit action.

### What is still shared with TAB 07

Requirement uploads and their versioning (TAB 09), notifications (TAB 13),
payments where a program charges a fee (TAB 11), and the audit trail (TAB 17).
**The status machine is not shared. Nothing else about the two should be
copied across.**

### Also required

- **Entitlements, not just fees.** Medical Assistance (AICS) ranges to ₱150,000;
  Burial up to ₱5,000; Social Pension ₱1,000/month; TUPAD pays minimum wage for
  a fixed number of days; several programs are goods or training slots.
- **Eligibility is case-assessed.** MSWDO social workers determine the amount.
  The backend records the assessment and the assessor; it does not compute the
  award.
- **Beneficiary de-duplication.** Assistance attracts repeat and duplicate
  claims across barangays. Check against the resident master profile (TAB 05)
  and surface prior awards to the assessor at assessment time.
- Categories: Medical & Health, Burial & Bereavement, Education, Financial
  Assistance, Food & Relief, Social Protection, Employment & Livelihood.
- Four program families still need an LGU ruling before their rules are coded
  (TAB 04).

**Acceptance.** An acceptance suite walks the full main sequence plus each side
state and its return path; a reject, cancel or return without a typed reason is
refused; `Confirm Release` without Proof of Release is refused; an approved
amount differing from the recommended one is preserved on both fields; and the
`processingKind` branches are exercised for all four kinds.

---

## TAB 09 — Uploads, storage, and file integrity

**Scope.** Everything a citizen or officer attaches.

**Do.**

- Accept the documented types only; validate by **content sniffing**, not by
  extension or client-supplied MIME.
- Size limits per type. The frontend's own editor caps images at 5 MB
  (`MAX_IMAGE_BYTES` in `resources/js/dokyu-editor.js`) — align.
- **Malware scanning before a file is readable by anyone.** Government intake of
  arbitrary citizen uploads without scanning is not defensible.
- Private object storage. **No public URLs.** Serve through short-lived signed
  URLs that are issued only after a permission and scope check.
- Content-addressed storage with a stored hash, so a `versions[]` entry can
  prove the file it points at is the file that was reviewed.
- Strip EXIF/GPS from citizen photo uploads unless there is a stated reason to
  keep it — this is resident location data.
- Never serve an uploaded file inline from the application origin; force a
  download disposition or serve from an isolated origin.

**Acceptance.** An unauthenticated or out-of-scope actor cannot fetch any file
by any URL; a signed URL expires; an infected test file is rejected at intake.

---

## TAB 10 — Document issuance, rendering, and verification

**Scope.** Turning an approved request into the actual certificate.

**What already exists on the client.** The frontend renders and prints
documents today: a rich-text document editor (`resources/js/dokyu-editor.js`,
730 lines), `html2pdf.js` for citizen-side download, per-barangay seals and
officials in `config/esperanza_barangay_documents.php`, and 25
document→template mappings with `certTitle`/`certSubtitle`/`nameField`.

**Do.**

- **Render the official document server-side.** Client-side PDF generation is
  fine for a preview; it is not acceptable as the source of an official record.
  The server produces the authoritative artefact, stores it, and hashes it.
- Templates parameterised by: service, barangay, the correct officials for that
  barangay, seal, control/serial number, issue date, validity, and the fields
  from the form spec.
- **A verifiable serial or QR** on every issued document, resolving to a public
  verification endpoint that confirms authenticity without exposing the
  resident's personal data. This is the single highest-value backend addition
  over the current frontend, and it is what makes a printed LGU certificate
  checkable.
- Control-number allocation must be gapless and auditable per document type and
  per year.
- Reprints are recorded, numbered, and marked as reprints.
- Proof of release: the evidence captured at the Release step (TAB 07) is stored
  against the request.

**Acceptance.** An issued document verifies through the public endpoint; a
tampered copy does not; every issuance and reprint appears in the audit log.

---

## TAB 11 — Payments: assessment, receipt, reconciliation

**Scope.** Money. Treat accordingly.

**Record shape** (`config/esperanza_payments.php`): `ref, citizen, barangay,
type (Dokyu|Tulong), item, amount, method, receipt, status, date`. Statuses seen
in the seed: `Approved`, `Under Review`, `Pending Review`, `Rejected`.

**Do.**

- **Assessment** produces an amount from the catalog fee rule plus the request's
  own inputs (declared income for Cedula, declared capital for a new Business
  Permit, prior-year gross receipts for renewal). Store the inputs and the
  computed result together.
- **Idempotency on every payment write.** A double-submitted payment must not
  produce two receipts.
- **Official receipt numbering** must be gapless, sequential, auditable, and
  reconcilable to the Treasurer's Office. Coordinate the format with them before
  coding it.
- Support the methods the LGU actually accepts; the seed already models a
  `method` and a `receipt`, and the UI carries payment-method branding assets.
  If an online channel is in scope, that is a separate integration with its own
  settlement, refund and dispute handling — scope it explicitly rather than
  assuming.
- Refunds and voids are their own audited transactions, never edits.
- A daily reconciliation report the Treasurer can tie out.

**Acceptance.** Fee computation is unit-tested per rule; receipt numbers are
gapless under concurrency; a replayed payment request produces one receipt.

---

## TAB 12 — Communications: balita, events, directory, blotter, banners

**Scope.** Six submodules under one permission module, with a real editorial
workflow.

**The workflow is already specified** in `CLAUDE.md`:
`Draft → Review → Publish → Archive`. The permission set on this module —
`view, create, edit, delete, review, approve, archive, reject` — matches it.

**Contents:**

- **Balita (announcements + community posts)** — `config/esperanza_balita.php`:
  posts carry `official, author, barangay, category, time, body, image, video,
  likes, shares, comments`; 7 categories (All, Official, Community, Health,
  Public Service, Advisory, Livelihood); a media library.
  **Community posts are user-generated and need moderation** — the permission
  matrix names `Community Posts (Moderation)` as its own submodule. Build report,
  hide, and takedown, with an audit trail.
- **Events** — `key, label, name, title, date, day, time, venue, barangay,
  category, weeks`. Calendar-shaped; needs recurrence and timezone handled once.
- **Government Directory and Barangay Directory** — offices, officials, contacts.
  The barangay half overlaps `config/esperanza_barangay_documents.php`
  officials data; **keep one source of truth**, since the same roster prints on
  certificates (TAB 10).
- **Blotter** — an incident record. This is sensitive personal data about named
  individuals; scope it tightly, restrict `view_sensitive`, and log every read.
- **Banners** — `config/esperanza_site_content.banners`: `id, title, subtitle,
  image, cta_label, cta_route, theme, status, audience, start_date, end_date,
  order, created_by, last_updated`. Scheduled and audience-targeted; the
  scheduling window is real and must be enforced server-side.

**Acceptance.** The four-state editorial lifecycle is enforced; a scheduled
banner appears and disappears on its own dates; a moderated post is removed from
every citizen surface.

---

## TAB 13 — Notifications: bilingual, multi-channel

**Scope.** Everything the citizen and the officer get told.

**Shape** (`resources/views/citizen/notifications.blade.php`): a notification
carries `category` (dokyu, tulong, account, community, events), `title`, `body`,
**both `en` and `fil` text**, `icon`, `color`, `pill`, `ref`, `link`, `time`,
`unread`, `pinned`, and a `popup` flag.

**Do.**

- **Every notification is authored in Filipino and English.** The app defaults
  to `fil`. This is a schema requirement, not a translation task to defer.
- Trigger notifications from the lifecycle, not from the UI: submitted, moved to
  Under Verification, requirement flagged (naming the requirement and the
  reason), resubmission received, approved, marked to release, released,
  rejected with remarks, payment assessed, payment received, announcement
  published, event reminder, Sakuna alert.
- Channels: in-app always; SMS and email where the citizen has a verified
  destination. **SMS is the channel that actually reaches this municipality** —
  budget and rate-limit it deliberately.
- Per-citizen preferences, and an admin notification-settings surface that
  already exists in the Settings UI (new document requests, new assistance
  requests, overdue requests, weekly summary).
- Delivery status and retry. A notification that silently failed is worse than
  none, because the officer believes the citizen was told.
- **Sakuna public alerts are a different class** — see TAB 15.

**Acceptance.** Every lifecycle transition emits the right notification in both
languages; delivery failures are visible and retried; a citizen's channel
preferences are honoured.

---

## TAB 14 — Internal forms: 24 forms, 4 offices

**Scope.** Staff self-service forms, deliberately separate from citizen-facing
Dokyu and Tulong.

**The inventory** (`config/esperanza_internal_forms.php`):

| Office | Forms |
| --- | --- |
| Human Resource Management Office | Application for Leave, Personal Data Sheet, Medical Certificate, Clearance, Locator Slip, Pass Slip (6) |
| General Services Office | Borrower's Slip, Trip Ticket, Fuel Pass, Inventory Custodian Slip, Return Slip, Waste Materials Report, Pre/Post Repair Inspection (7) |
| Municipal Agriculture Office | Animal Health Monitoring, Dog Vaccination Report, Livestock Inventory, Annual Target, Accomplishment Report (5) |
| Social Welfare & Development Office | ERPAT Form, General Intake Sheet, FACED Assistance Record, ERPAT Association Officers, ERPAT Quarterly Meeting, BVAW/KALIPI Meeting (6) |

Submissions carry `ref, officeKey, office, form, submittedBy, submitted,
status`.

**Do.**

- One submission engine, 24 form definitions as data — do not write 24
  controllers.
- These forms have **approval chains** (a leave application needs a supervisor,
  a trip ticket needs GSO authorisation). Model an approval chain per form type
  rather than a single approver field.
- The HR Personal Data Sheet is the CS Form 212 — it is bulky, structured, and
  personal. Treat it as sensitive.
- Printable output matching the official form layout.

**Acceptance.** A form is added by configuration alone; an approval chain routes
correctly; submissions are scoped to the submitting office.

---

## TAB 15 — Sakuna: disaster risk and emergency operations

**Scope.** Ten submodules, 67 permission triples, and the widest action
vocabulary in the platform — `dispatch, escalate, close, validate, open,
check_in, transfer, check_out, deploy, return, publish, cancel` all appear here
and nowhere else.

**The ten submodules:** Command Center · Vulnerability Assessment · Incidents ·
Evacuation Centers · Evacuees · Resources · Relief Operations · Damage
Assessment · Public Alerts · Reports. An eleventh tab, **BDRRMC Forms**, exists
in the routes and ships six form templates.

**Do.**

- **Incidents**: report → validate → dispatch → escalate → resolve → close, with
  location, severity, responder assignment and a timeline. The action verbs are
  already in the permission matrix; use exactly those.
- **Evacuation centers**: capacity, current occupancy, services, personnel,
  status, and an occupancy history.
- **Evacuees**: `check_in`, `transfer` between centers, `check_out` — tracked by
  **family unit**, not only by individual, because that is how evacuation
  actually works and how the seed models it (head, members, relation, barangay,
  sitio). Link to the resident master profile (TAB 05) where possible, but
  **accept unregistered evacuees** — a disaster is not the moment to require an
  account.
- **Resources**: inventory, readiness, `deploy` and `return`, personnel.
- **Relief operations**: distribution per barangay per family, with
  **beneficiary de-duplication** — the same failure mode as Tulong (TAB 08), and
  more consequential under time pressure.
- **Damage assessment**: per-barangay, per-sector, with photo evidence, feeding
  the standard post-disaster reports.
- **Public alerts**: types, levels, target groups, channels. `publish` and
  `cancel` are both permissions — a cancellation must reach everyone the alert
  reached.
- **BDRRMC forms**: six templates with sign-off.
- **Command Center** is a read model over all of the above — live counts,
  markers, feed, decisions, timeline. Build it as a projection, not as another
  write path.

**Non-functional, specific to this module.** Sakuna is used when the network is
degraded and the office may be dark. Design for **offline-tolerant field
capture with later sync**, idempotent writes, and a public alert path that does
not depend on the admin portal being reachable. Its data is also the most
sensitive in the platform — evacuee lists name displaced residents and their
locations.

**Acceptance.** An incident runs the full verb chain; an evacuee family
transfers between centers with history intact; a duplicate relief distribution
is refused; a cancelled alert reaches the original audience.

---

## TAB 16 — Reports, analytics, and exports

**Scope.** Eight report families the frontend already names, plus the analytics
surface.

**The reports**, from `resources/views/admin/reports.blade.php`: Document
Requests Summary · Assistance Programs Report · Resident Profiling Coverage ·
Revenue & Fees Collected · Barangay Activity Report · Citizen Satisfaction
Survey · Internal Forms Submissions · Sakuna Incident Reports. Generated reports
carry `title, generated, by, format` — PDF is the default.

**Do.**

- Compute from real data, never from cached display numbers. The mock
  `municipalStats` figures (17,842 residents, 4,918 households, 6,204 families,
  13,894 verified) are **fixtures, not targets** — the real numbers will differ
  and the report must say what is true.
- **Every export is a permission check and an audit event.** The permission
  matrix already restricts `export` on Reports to Super Admin, Municipal Admin,
  Department Head and Records Officer — Information Officer is view-only. An
  export of resident data is a bulk disclosure of personal information; log who,
  what, when, and how many rows.
- Scope every report by the requester's geographic and department scope. A
  barangay secretary's "Barangay Activity Report" is their barangay's.
- Long-running reports run asynchronously and notify on completion.
- **Citizen Satisfaction Survey has no collection mechanism anywhere in the
  frontend.** Either build the survey capture or drop the report — do not ship a
  report over data that is never gathered.
- Analytics: trends over time, per-service volumes, processing-time
  distributions, per-barangay comparison. Processing time is the number that
  will actually change how the municipal hall is run — measure it per status
  transition, from the audit trail.

**Acceptance.** Every report reconciles against the underlying records; exports
are permission-gated, scoped and audited; no report reads a denormalised
display value.

---

## TAB 17 — Audit logs and data privacy (RA 10173)

**Scope.** The Audit Logs UI exists today and shows `user, module, time` over
mock rows. Make it real, and make the platform lawful.

**Audit — do.**

- **Append-only.** No update path, no delete path, not even for an
  administrator. Storage separate from application tables.
- Every entry: actor, actor role, actor scope, action, target type, target id,
  before, after, timestamp, IP, user agent, request id.
- **What must be logged, at minimum:** every request status transition and every
  per-requirement decision (with reason and remarks); every document issuance
  and reprint; every payment, refund and void; every permission or role change;
  every personnel account creation, suspension and archival; every login,
  failed login and lockout; **every read of a `view_sensitive` field**; every
  export with its row count; every merge in the data-quality queue; every
  Sakuna alert publish and cancel.
- Searchable and filterable by actor, module, target and date — that is what the
  existing UI promises.
- Retention aligned with the LGU's records schedule; audit logs typically
  outlive the records they describe.

**Privacy — do.**

- Name the **lawful basis** for each category of processing and record it.
- **Consent capture at registration**, versioned against the Privacy Policy and
  Terms that `config/esperanza_site_content.php` already holds with
  `effective_date` and `last_updated` — so you can prove which version a
  resident agreed to.
- **Data-subject rights**: access, correction, objection, erasure where lawful.
  The Citizen Portal needs a route to exercise them, and the backend needs a
  workflow to service them.
- **Retention and disposal schedules** per data class. Evacuee lists, blotter
  entries and assistance case notes should not live forever by default.
- Breach detection and the 72-hour NPC notification procedure, written down
  before it is needed.
- Encryption at rest and in transit; field-level encryption for the most
  sensitive attributes.
- **Register the system with the National Privacy Commission** and appoint a
  Data Protection Officer — an LGU obligation, not an engineering one, but it
  blocks go-live.

**Acceptance.** An auditor can reconstruct any request's complete history from
the log alone; a sensitive-field read appears in the log; a data-subject access
request can be serviced end to end.

---

## TAB 18 — Settings, branding, and site content

**Scope.** Four submodules, `view` and `edit` only — the smallest module, and
the one that makes the platform maintainable without a developer.

- **General settings** — municipality identity, office hours, contact.
- **Municipality branding** — seal, colour theme (the UI ships Brand Blue, Navy,
  Gold, Emerald), and the assets that print on documents. Changing branding
  changes issued documents; version it.
- **Site content** — Privacy Policy, Terms & Conditions and Support, each with
  `title, effective_date, last_updated, last_updated_by, intro, sections`, plus
  support `channels` and bilingual `office_hours`/`office_hours_fil`. **Content
  is versioned and effective-dated** because consent is captured against a
  version (TAB 17).
- **Service catalog** — the Dokyu/Tulong fees and requirements from TAB 04,
  administrable and versioned.
- **Admin notification settings** — new document requests, new assistance
  requests, overdue requests, weekly summary (TAB 13).

**Acceptance.** A policy edit produces a new effective-dated version without
mutating the old one; a branding change is versioned and does not retroactively
alter already-issued documents.

---

## TAB 19 — Non-functional: performance, availability, operations

**Design targets, sized from the seeded scale** (17,842 residents, 8,420 citizen
accounts, 33 personnel):

- **Concurrency** is low and bursty — a few dozen staff, a few hundred citizens
  at peak. This is not a scale problem; it is a **reliability and connectivity**
  problem. Optimise for working on a poor connection, not for throughput.
- **Response budgets**: list endpoints under 500 ms at p95 with realistic data
  volumes; a single request detail under 300 ms.
- **Payloads matter more than usual.** The frontend already ships a 1.66 MB JS
  bundle and, on `/admin/sakuna`, 1.59 MB of HTML per page. Do not add heavy API
  payloads on top of that. Paginate everything; never return an unbounded list.
- **Availability**: define the window the municipal hall actually needs (office
  hours, plus Sakuna which is 24/7 by nature). They are different requirements —
  say so explicitly rather than averaging them.
- **Backups**: automated, encrypted, off-site, and **restore-tested on a
  schedule**. An untested backup is a belief, not a backup.
- **Disaster recovery** with a stated RPO and RTO. For a disaster-management
  module this is not academic.
- **Observability**: structured logs, error tracking, uptime checks, and alerting
  that reaches a human.
- **Rate limiting** on auth, uploads, exports and public verification; the
  document-verification endpoint (TAB 10) is public and will be probed.
- Zero-downtime, reversible migrations.

**Acceptance.** A restore drill succeeds from a real backup; load at 5× expected
peak stays inside budget; alerting fires on a simulated outage.

---

## TAB 20 — Contract handover and certification gate

**Scope.** Proving the backend actually replaces the mock data without changing
the frontend.

**Do.**

1. **Freeze the contract.** The OpenAPI document from TAB 01 is the authority.
   Both repos pin a version. A drift check runs in CI on both sides.
2. **Seed staging with the frontend's own fixtures** — the exact contents of the
   `config/esperanza_*.php` files. The client has already approved how those
   screens look; staging must reproduce them from the database.
3. **Cut over module by module**, deleting each `config/esperanza_*.php` file
   only when its endpoints are live and its screens are verified. Deleting them
   all at once removes the specification while you still need it.
4. **Run the certification suite:**
   - all 490 permission triples, allowed and denied;
   - all three lifecycle paths including a repeated correction loop, with ref
     stability and version history asserted;
   - fee computation for every rule in TAB 04;
   - receipt-number gaplessness under concurrency;
   - scope isolation — barangay and department — attempted and refused;
   - bilingual coverage of every citizen-facing string;
   - an audit entry for every mutating operation;
   - document verification: genuine passes, tampered fails.
5. **Publish the verdict as CERTIFIED or NOT CERTIFIED with the evidence**, the
   way this programme's other books do. A partial pass is NOT CERTIFIED with a
   named list, not a qualified yes.

**Definition of done for the whole programme:** the frontend runs against the
real API with every `config/esperanza_*.php` mock file deleted, no field
renamed, no status added, every permission triple enforced server-side, and the
certification suite green.

---

## Open questions for the LGU — these block coding, not planning

These are not engineering unknowns. Each needs a municipal decision, and each is
already flagged as a gap by the frontend's own validation guide or is implied by
a UI surface with no data behind it.

1. **Six services have no official Esperanza source form** — Locational
   Clearance (MPDO), Educational Assistance, the AICS family (Medical, Burial,
   Financial, Food Relief), Social Pension, Cedula/RPT/Live Birth citizen
   request forms, and Business Permit (New). Confirm current forms, fees,
   requirements and eligibility before their rules are coded.
2. **Official receipt numbering and reconciliation format** — must come from the
   Municipal Treasurer's Office.
3. **Online payment**: in scope or not? It changes TAB 11 substantially.
4. **Citizen verification channel**: mobile OTP, email, or in-person at the
   barangay? This determines who can actually register.
5. **Which fields are `view_sensitive`** — needs the Data Protection Officer.
6. **Retention schedules** per data class, especially evacuee lists, blotter
   entries and assistance case notes.
7. **Citizen Satisfaction Survey** — the report exists with no collection
   mechanism anywhere. Build the survey, or drop the report.
8. **Availability commitment** — office hours for the portal versus 24/7 for
   Sakuna. These need separate, stated targets.
9. **Barangay officials as one source of truth** — the same roster appears in the
   Directory and on printed certificates. Confirm who maintains it and how often
   it changes (it changes every barangay election).
10. **NPC registration and the appointed Data Protection Officer** — an LGU
    obligation that gates go-live.

---

## Provenance

Every count, field list, enum and rule in this book was read from the frontend
repository at commit `dd7e0d4` on 2026-08-29 — not recalled, not assumed. The
frontend was cloned, built (`composer install`, `npm ci`, `npm run build`), and
all 56 GET routes were rendered and verified to return 200 before this book was
written. Where the frontend does not decide something, this book says so and
routes it to the LGU rather than inventing it.

Companion documents in this repository: `FRONTEND_PENDING.md` (the front-end
findings register, F-001..F-009), `PROCESS_TAB_FLOW.txt` (the lifecycle
specification), and `CLAUDE.md` (the project constitution).
