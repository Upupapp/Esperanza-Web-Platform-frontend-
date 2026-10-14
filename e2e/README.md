# Browser end-to-end checks

Real Chromium, driven through the deployed website against its API, the way a
resident and an officer use them. They cover the public pages, the Citizen Web
and the Admin Portal screens of the scope round (2026-10-15): the service list,
appointments, verification, support conversations, the offline notice, the
request queues, the feedback desk, departments, templates, the System page and
the audit export.

They are separate from the build: Netlify never installs or runs them.

## Run

```sh
cd e2e
npm install                     # once: Playwright
npx playwright install chromium # once: the browser

E2E_SITE=https://esperanza-web-platform-frontend.netlify.app \
E2E_STAFF_ID=<a Super Admin test account> \
E2E_RESIDENT_ID=<a verified test resident's email or account number> \
E2E_PASSWORD=<their password> \
node run.cjs            # every check
node run.cjs verify     # only checks whose id or name contains "verify"
```

| Variable | Meaning |
|---|---|
| `E2E_SITE` | The website to test (default: the Netlify site). A local build served at `http://127.0.0.1:8000` works against staging, whose CORS allows that origin. |
| `E2E_STAFF_ID` | Employee ID of a Super Admin **test** account (on staging, test accounts sign in without the emailed code). |
| `E2E_RESIDENT_ID` | Email or account number of a verified **test** resident. |
| `E2E_PASSWORD` | The test accounts' password. Never commit it. |
| `E2E_WRITE=1` | Also run the checks that create data (they are labelled; leave off on production). |
| `E2E_HEADED=1` | Show the browser. |
| `E2E_OUT` | Where screenshots of failures go (default `e2e/results/`, not committed). |

A check that needs an account or data that is not there is **skipped**, not
failed, and says why. Any script error, console error or API answer of 500 or
more on a page fails the check it happened in.

Run them before the LGU's acceptance testing and after each deployment; the UAT
script (backend `docs/UAT_SCRIPT.md`) lists them among its entry criteria.
