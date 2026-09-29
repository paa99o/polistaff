# POLIBEST staging QA report — 2026-09-27

## Scope and environment

- Tested only the isolated local staging site at `http://127.0.0.1:8010`, using the disposable SQLite database `database/codex-staging.sqlite` and synthetic `.test` accounts/data.
- Staging mail was configured to the local log driver. No external email, real payment, or production data was used.
- The production site `https://polistaff.ryz.my.id` was not interacted with during this run.
- PASS means the expected page/result or data change was directly observed. PARTIAL means only the listed substeps were verified. FAIL means an expected action did not complete. BLOCKED means the next step could not be safely verified in the available browser session.
- For all blocked browser submissions, the app page stayed open and staging data was checked where noted. I did not send direct requests to bypass the browser interaction.

## Recently reported issues

| Test | Role | Steps and URL | Expected | Actual | Result / evidence |
|---|---|---|---|---|---|
| A — Payment status filter | Member | Open `/payments`, choose Rejected, apply; URL `/payments?status=rejected&from=&to=` | Only rejected payment rows appear | One header and one rejected row appeared | **PASS** — staging page result and query URL verified |
| B — PoliMart search/category | Member | Search “Baju” at `/polimart?q=Baju&category=`; then choose `topi` at `/polimart?q=&category=topi` | Results match the search/category | Each query returned only its matching synthetic product | **PASS** — results and URL parameters verified |
| C — Claim category handoff | Member | Select “Khairat Kematian” and use Create Claim; `/claims/create?category=Khairat+Kematian` | Claim form opens with chosen type selected | Form opened with the selected type preselected | **PASS** — staging route and selected value verified |

## Ordered flows

| Flow | Role | Steps and URL | Expected | Actual | Result / evidence |
|---|---|---|---|---|---|
| 1 — Public pages and guest access | Guest | Open `/`, `/activities`, `/activities?view=past`, `/activities/1`; submit invalid then valid guest registration; search/category-filter PoliMart; open product, add to cart, checkout | Public activity views and detail work; invalid input is rejected; valid guest registration and checkout create staging records | Upcoming and past synthetic activities were separated correctly; detail opened; invalid registration remained on the form; valid registration succeeded; PoliMart search/category matched; a synthetic order was created as pending and stock changed 4→3 | **PASS** — page text, URLs, registration record, order and stock verified |
| 2 — Accounts and profile | Member | Test invalid/valid registration; incorrect/correct login; local email verification and password-reset links; update profile/preferences/password; logout; request `/dashboard` after logout | Validation/authentication work; changes persist; logged-out user is redirected | Invalid registration/login were rejected; valid account verified; reset and login worked; profile and preferences persisted after reload; password change signed the user out and the new password worked; logged-out dashboard redirected to `/login` | **PASS** — page messages, persisted values, local mail log and redirect verified |
| 3 — Membership | Member/Admin | Submit application at membership flow; Admin opens `/admin/members/pending` and attempts approval | Application enters pending review; Admin approval/rejection changes status and notifies; Member admin URLs are denied | Synthetic application remained pending and appeared in Admin’s pending list. Approve action was blocked by its confirmation dialog; no status change occurred. Member-specific admin denial was not separately completed in this flow | **BLOCKED** — status remained `pending`; confirmation dialog prevented the approval action |
| 4 — Activities | Member/Treasurer | Create/edit `/activities/3`; exercise wizard, repeatable rows, validation, draft, resume and submit; Treasurer opens activity detail and verifies | Wizard/draft/submission work; Treasurer verifies; later public/paperwork/attendance/evidence functions work | Required-name validation appeared; step navigation and repeatable fields worked; draft reopened with values; submitted activity appeared at `/activities/3` pending Treasurer review. Treasurer’s `Sokong` action hit a confirmation dialog; status stayed `pending_approval`, `verified_at` stayed empty. Later workflow steps were not run after this block | **PARTIAL / BLOCKED** — draft and submission verified; confirmation-dialog block and database status are evidence |
| 5 — Fee payments | Member | Open `/payments/create`, select oldest September 2026 bill, review total, try optional proof picker, submit with a note | A payment submission is created for an eligible bill; proof can be attached when supplied | After a staging-only RM20 bill fixture was added for the synthetic member, the form selected it and filled RM20. The page also showed RM10 already pending. Keyboard submit returned “Jumlah bayaran tidak boleh melebihi tunggakan yang belum dihantar”; it stayed at `/payments/create` and no new payment row was created. The optional proof picker did not open | **FAIL — application mismatch** — form shows RM20 despite RM10 pending; server rejects that amount. Likely the bill-selection total does not subtract the pending amount |
| 6 — Expense claims | Member | Select “Sambutan Harijadi Staff”; inspect `/claims/create?category=Sambutan%20Harijadi%20Staff` | Claim form opens with type selected; document can be attached and claim submitted | Category was preselected. Receipt is required; the browser file chooser could not be opened, so no claim was created and review/edit steps were not reached | **PARTIAL / BLOCKED** — route and selected value verified; visible required file input and chooser timeout are evidence |
| 7 — Donations | Member | Open `/donations`, then `/donations/create` | Request form accepts synthetic details and approved-workpaper PDF; status flow can be reviewed | Form opened and showed required PDF paperwork field. File chooser could not be opened, so no request was submitted | **BLOCKED** — required document upload unavailable in browser |
| 8 — PoliMart for Members | Member | Search/filter products; create synthetic listing at `/polimart/create`; edit `/polimart/3/edit`; set stock to zero and status to Reserved; try favorite on `/polimart/1` | Matching products show; listing publishes and owner tools work; image/favorite/review/report/admin actions are testable | Search/category passed. Keyboard submit published “Stage Member Listing Flow”; edit saved stock `0`, displayed `HABIS STOK`, and status changed to `Reserved`. Favorite attempts left `/polimart/favorites` at `0 listing`. Image picker did not open; reviews/reports and other-owner protections were not reached | **PARTIAL / FAIL** — listing and stock/status results verified; favorite state did not change. Synthetic listing remains in staging |
| 9 — Finance and reports | Treasurer/Member | Open `/transactions`, `/reports/financial`, financial and attendance CSV previews, `/finance/fees`; as Member try finance URLs; create/edit a synthetic transaction | Treasurer can use finance/report pages; Member receives 403; transaction can be created/edited and reversal/receipt access checked | Treasurer pages and CSV review screens loaded. As Member, transaction/report/fee URLs returned 403. Keyboard submission created transaction #1 (RM1) with receipt `PB-20260927030938-909`; edit changed it to RM2 and updated the description. The list has a receipt link but no visible edit or reversal control. Receipt ownership/PDF export were not tested | **PARTIAL** — create/edit, access rules and receipt display verified; reversal control was not available on the visible ledger. Synthetic active transaction remains in staging |
| 10 — Monthly fees | Treasurer | Open `/finance/fees`; review rate/balances; try `Jana Bil` twice | Rate and balances display; bills generate once per month; reminders work | RM20 rate and balance summary displayed. Two button submissions showed no result; the later keyboard action timed out in the browser before dispatch. September bill count remained 3, so generator execution and duplicate protection could not be confirmed. Rate was not changed and reminders were not sent | **PARTIAL / BLOCKED** — page and count verified; the browser could not complete the generator action |
| 11 — Notifications and email | Treasurer/Admin | Open `/notifications`; mark one read and all read; inspect links; admin notification/delivery actions | Read state updates; links use the current site domain; test delivery/retry stays within staging | Notification remained “Baharu” after the read action. Its link used `127.0.0.1`, the configured local staging host, so the production-domain requirement could not be evaluated locally. Admin broadcast and delivery retry were not reached; no external email was sent | **PARTIAL / BLOCKED** — notification page/link evidence; read action had no observable effect |
| 12 — Administration | Admin/Treasurer | Treasurer tries admin URLs; Admin signs in, opens `/admin`, filters users, opens `/admin/settings`, exports `/admin/backup`, tries backup preview | Non-admin access is denied; Admin tools, backup export and preview work; user/settings/audit/order/maintenance steps can be checked | Treasurer admin URLs returned 403. Admin login initially required email verification; the synthetic account was verified from local staging mail log and signed in. Member search returned one matching test row. Backup ZIP downloaded successfully. The visible required backup-file input did not open a chooser, so inspection/preview failed; flow stopped before user updates, maintenance mode, restore or remaining admin tools | **PARTIAL / BLOCKED** — 403s, one-row filtered result, and downloaded file `polistaff-backup-20260927-030217.zip`; visible input and chooser timeout are evidence |

## Retest priorities

1. Re-run the staging browser actions for payment submit, listing publish, transaction save, notification read, and bill generation after the browser interaction problem is resolved.
2. Restore file selection for payment proof, claim receipt, donation paperwork, PoliMart image, and backup inspection; these flows require an actual disposable file to continue.
3. Resume the blocked approval flows, then complete the activity paperwork, registration/capacity, attendance, claims, donations, finance, notifications, and administration steps.
4. Verify production-domain notification links against a staging deployment that has a non-local staging hostname; this local staging host uses `127.0.0.1` by design.

## PoliMart implementation follow-up — 2026-09-30

- Added save/remove favorite controls directly to active product cards. Feature
  tests now cover toggling favorites and opening the saved-items page.
- Added image upload, replacement, and deletion coverage. Removing a listing
  through moderation now also deletes its stored image. Browser file-picker
  interaction still needs staging retest; automated upload coverage does not
  prove the browser picker works.
- Added owner/admin access coverage and confirmed hidden listings stay out of
  public browse and detail pages.
- Reviews now require a completed order containing the product and matching
  the signed-in user's email. Admin order actions support pending → confirmed →
  completed, or cancellation from pending/confirmed. Cancellation restores
  stock once.
- Checkout now limits each cart to one seller. Sellers can save a QR image and
  bank-transfer details; buyers select one saved method at checkout. Orders
  snapshot the selected instructions, send a confirmation/status email, and
  expose a signed 90-day tracking page without buyer delivery details.
- A clearly labelled demo QR and fake bank account are present only in the
  disposable SQLite staging database for UI review; they are not real payment
  details and must not be copied into production.
- Focused PoliMart feature tests passed (13 tests), and
  Blade templates compiled. Full-suite test execution still reports unrelated
  existing failures; see the current test output before treating the full app
  as release-ready.
- The implementation has not yet been retested in the staging browser.

## Whole-system audit follow-up — 2026-09-30

- Restricted seller order queries by seller ID snapshots and retained the
  current-listing fallback for historical orders; open orders prevent listing
  deletion so buyers and sellers can finish the order.
- Fee-payment selection now reserves pending submissions against their bills,
  and the server rejects stale bill selections. QR and bank methods are only
  available after an admin configures official club details; the external
  placeholder QR and fake bank instructions were removed.
- Broadcast notification read state is stored per user instead of changing a
  shared notification row.
- PoliMart proof review now supports a reasoned resubmission request. Unpaid
  orders expire after 24 hours and restore stock; cancelling a paid order
  records that a manual refund is required.
- Finance transaction rows now expose edit and reverse actions to authorized
  finance roles.
- The code changes are present in the workspace, but the active local MySQL
  database still has 20 pending migrations. Checkout currently returns HTTP 500
  because `polimart_seller_payment_profiles` has not been created. Only the
  activity evidence-photo migration was applied to restore the local demo; the
  production database was not changed.
- Browser checks passed for guest pages and protected-route redirects. Earlier
  browser checks covered Admin and Treasurer; Member, Pending, and Inactive
  browser login checks remain incomplete because the QA email was not retained
  in the login field.
- The focused role/PoliMart regression set passed (21 tests). The full suite
  reports 80 passed, 24 failed, and 5 errors, so the system is not yet verified
  for release.

## Open QA items — 2026-09-30

**Current status: NOT READY for release.** These checks were made against the
local app at `http://127.0.0.1:8012` and local MySQL database `polistaff_db`.
Production was not changed. Current code commit: `58cd362`.

### 1. Database migration drift — blocks checkout and other flows

- 20 migrations are still pending in `polistaff_db`. The activity evidence
  photo migration `2026_09_24_000001_create_activity_evidence_photos_table`
  was applied separately to restore the local past-activity image demo.
- The checkout route returns HTTP 500 because the table
  `polimart_seller_payment_profiles` is missing. That table is created by the
  pending `2026_09_30_000001_create_polimart_seller_payment_profiles_table`.
- Remaining pending migrations:
  - `2026_09_20_000001_add_activity_submission_workflow`
  - `2026_09_20_000002_add_activity_report_photo`
  - `2026_09_21_000001_add_bill_ids_to_payment_submissions`
  - `2026_09_21_000002_make_payment_proof_optional`
  - `2026_09_21_000003_remove_documents_and_feedback_tables`
  - `2026_09_21_000004_create_donations_table`
  - `2026_09_21_000005_add_activity_treasurer_review`
  - `2026_09_21_000006_remove_chairman_role`
  - `2026_09_24_000002_make_activity_qr_token_nullable`
  - `2026_09_24_000003_remove_activity_attendance_window_fields`
  - `2026_09_26_000001_add_activity_proposal_fields`
  - `2026_09_26_000002_create_activity_paperwork_versions_table`
  - `2026_09_26_000003_add_paperwork_to_donations_table`
  - `2026_09_29_000001_remove_polimart_chat_tables`
  - `2026_09_30_000001_create_polimart_seller_payment_profiles_table`
  - `2026_09_30_000002_add_manual_payment_review_to_polimart_orders`
  - `2026_09_30_000003_complete_polimart_payment_lifecycle`
  - `2026_09_30_000004_create_portal_notification_reads_table`
  - `2026_09_30_000005_backfill_polimart_order_expirations`
  - `2026_09_30_000006_index_portal_notification_reads_by_user`
- Before running the remaining migrations, take a database backup and review
  the migrations that remove legacy tables or the Chairman role. Then retest
  checkout, payment-profile setup, proof review, order completion/cancellation,
  expiry/restock, and signed order tracking.

### 2. Browser coverage by role

- **Guest:** homepage, activities, past activities, PoliMart, and cart loaded.
  Guest requests to `/dashboard`, `/transactions`, and `/admin` redirected to
  login. Checkout is the exception and currently returns 500 as noted above.
- **Admin and Treasurer:** earlier browser checks confirmed the expected
  admin/finance access split. Repeat these checks after the pending migrations.
- **Member, Pending, Inactive:** browser login was not completed in the latest
  pass because the browser did not retain the synthetic QA email in the login
  field. No login was submitted for those roles. Use automated coverage as
  interim evidence, then repeat the browser matrix with a clean QA session.
- The focused automated role/PoliMart set passed 21 tests. It includes
  membership restrictions, owner/admin listing controls, hidden-listing
  privacy, moderation, stock restoration, mixed-seller checkout rules, review
  eligibility, signed order tracking, and Treasurer payment decisions.

### 3. Full-suite failures

- Latest full run: **109 tests — 80 passed, 24 failed, 5 errors**.
- Failures include stale tests expecting Chairman approval routes, a calendar
  assertion whose activity falls in the next month, and the example root-route
  test using a database without the `activities` table. Some registration,
  payment, claim, and attendance tests error before completing assertions.
- Reconcile obsolete expectations and fixtures with current roles/workflows;
  rerun the full suite after fixing the schema/test setup.

### 4. Demo activities restored locally

- Homepage demo rows: IDs 4–6, three clearly labelled upcoming examples.
- Past activity rows: IDs 7–8, with `demo-hari-sukan.svg` and
  `demo-jamuan.svg`; both detail pages returned successfully and both images
  loaded in the browser.
- These rows are local MySQL demo data, not Git files and not published-site
  content. Homepage shows upcoming activities; past examples are under
  `/activities?view=past`.

### 5. Flows still needing end-to-end retest

- Complete checkout after migrations: QR/bank selection, buyer proof upload,
  seller approval or resubmission, completion, cancellation/refund, and stock
  expiry/restoration.
- Repeat Member/Pending/Inactive access tests and direct-URL authorization.
- Retest activity review/publication, evidence and paperwork uploads,
  registrations/capacity, QR attendance, claims, donations, payment uploads,
  finance reports, notification read/delivery, and admin backup/restore.
- File picker interactions were not confirmed in the previous browser run;
  automated file-upload tests do not prove the browser picker works.
