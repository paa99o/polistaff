# Engineering Handoff

## Handoff summary

**Date:** 24 September 2026  
**Area:** Activity submission, QR attendance, post-activity evidence, and report previews  
**State:** Implementation and supporting documentation are complete in the
working tree. Database migration and the full feature suite still need to be
run in a healthy local environment.

The activity workflow was changed so attendance scheduling is derived directly
from the activity start and end times. A QR token is no longer generated when
an activity is submitted. Evidence is now a post-activity, multi-image gallery
contributed by registered members.

## Product decisions

1. Activity submission no longer collects a description.
2. Activity submission no longer contains attendance opening and closing
   fields.
3. A new activity has a `NULL` QR token.
4. Only the treasurer generates or regenerates the QR while an approved
   activity is in progress. Admin is not a QR-generation fallback.
5. Attendance scanning and manual attendance are valid only during the activity
   period and after a QR has been generated.
6. Registered members can upload activity photos only after the activity end
   time.
7. Evidence supports up to 10 images per request, with an 8 MB limit per image.
8. Waitlisted, cancelled, unregistered, and guest participants cannot use the
   authenticated member evidence flow.

## Implementation map

### Backend

- `app/Http/Requests/ActivityRequest.php`
  - Removed description, attendance-window, and submission-evidence rules.
- `app/Models/Activity.php`
  - Added the `evidencePhotos` relationship.
  - `attendanceIsOpen()` now checks approved status, an existing token, and the
    activity start/end range.
- `app/Models/ActivityEvidencePhoto.php`
  - New model linking one uploaded image to its activity and uploader.
- `app/Http/Controllers/ActivityController.php`
  - Creates activities with no QR token.
  - Generates/replaces QR tokens only during an approved activity.
  - Accepts post-activity multi-image evidence uploads.
- `app/Http/Controllers/AttendanceController.php`
  - Applies the activity-period and QR-token rule to manual attendance.
- `routes/web.php`
  - Replaced the legacy report-photo endpoint with
    `activities.evidence-photos`.

### Frontend

- `resources/views/activities/_form.blade.php`
  - Removed description, attendance-window, and early evidence inputs.
- `resources/views/activities/show.blade.php`
  - Added the evidence gallery and finished-activity upload form.
  - Prevents empty QR tokens and links from being rendered.
  - Shows the QR card beside activity details to all eligible viewers.
  - Shows QR generation only to the treasurer during the activity period.
- `resources/views/layouts/app.blade.php` and
  `resources/views/dashboard/index.blade.php`
  - Removed standalone **Imbas Kehadiran** shortcuts. The scan route remains the
    target encoded in each generated QR.
- `resources/views/activities/index.blade.php`
  - Removed the legacy report-photo upload action.
- Public activity and approval-email views no longer render activity
  descriptions.

### Database

Apply these migrations in order:

1. `2026_09_24_000001_create_activity_evidence_photos_table.php`
2. `2026_09_24_000002_make_activity_qr_token_nullable.php`
3. `2026_09_24_000003_remove_activity_attendance_window_fields.php`

The first migration creates evidence ownership records. The second permits
activities to exist without a QR token. The third removes the obsolete
attendance-window columns.

## Deployment and local setup

From the project root:

```bash
composer install
npm install
php artisan migrate
php artisan storage:link
npm run build
```

For local development, ensure the database values in `.env` match the running
database service, then use:

```bash
composer run dev
```

The evidence gallery relies on the public storage link. Missing images after a
successful upload usually indicate that `php artisan storage:link` has not been
run or the web server cannot read `storage/app/public`.

## Verification completed

- All changed PHP files passed `php -l` syntax checks.
- Blade templates compiled successfully with `php artisan view:cache`.
- Routes for QR regeneration and evidence upload were registered successfully.
- `git diff --check` reported no whitespace errors.

## Verification still required

The complete feature suite has not been confirmed. During handoff:

- The configured MySQL connection at `127.0.0.1:3306` refused connections.
- A separate test attempt encountered a Windows access-denied error while
  Laravel renamed a compiled Blade file in `storage/framework/views`.
- Existing activity tests still contain expectations for the old early-evidence
  and standalone attendance-window workflow and should be updated.

After starting MySQL and confirming write permission on
`storage/framework/views`, run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan test
```

## Required regression scenarios

- Submit an activity and confirm its QR token is `NULL`.
- Confirm description, attendance-window, and evidence inputs are absent from
  create and edit forms.
- Reject QR generation before the start time and after the end time.
- Generate a QR during an approved activity and record attendance for a
  registered member.
- Confirm an admin receives `403` when attempting to generate a QR.
- Confirm only the treasurer sees the generate/regenerate control.
- Confirm the QR appears beside activity details only after generation.
- Reject attendance for an unregistered member and for an expired QR period.
- Regenerate a QR and verify that the previous token no longer works.
- Reject evidence from a registered member before the activity ends.
- Reject evidence from waitlisted, cancelled, unregistered, and guest users.
- Upload multiple valid images after the activity and verify gallery rendering.
- Reject more than 10 images, non-image files, and images larger than 8 MB.
- Verify public activity pages render completed evidence without exposing an
  empty QR token.

## Known compatibility notes

- Legacy `description`, `evidence_photo_path`, and `report_photo_path` columns
  remain in `activities` for compatibility but are not used by the revised
  submission and gallery flow.
- The existing PDF activity report still reads `report_photo_path`; it has not
  yet been changed to combine the new evidence gallery into the report.
- Deleting an evidence database record does not currently delete its physical
  image because no evidence-deletion feature exists yet.
- The working tree contains uncommitted implementation and documentation
  changes. Review `git status` before creating a commit.

## Documentation index

- `PRD.md`: user roles, functional requirements, and acceptance criteria.
- `Rules.md`: authoritative activity business rules.
- `Architecture.md`: request flows, components, storage, and deployment.
- `Schema.md`: activity-related tables and relationships.
- `Design.md`: activity form, QR panel, evidence gallery, and UI states.

## Suggested next steps

1. Start the database and apply the three migrations.
2. Resolve the Windows compiled-view permission issue if it recurs.
3. Replace outdated activity feature tests and add evidence authorization tests.
4. Run the complete test suite.
5. Decide whether the activity PDF should include all evidence photos.
6. Commit the implementation, migrations, tests, and documentation together.

## Report preview implementation

All report export links now stop at a shared confirmation screen before an
attachment is returned.

- `resources/views/reports/download-preview.blade.php` renders embedded PDF
  previews and table-based CSV previews.
- `ReportController` applies the flow to financial PDF/CSV, overall attendance
  CSV, per-activity attendance CSV, and activity-report PDF.
- `TransactionController` applies the same flow to transaction receipt PDFs.
- `render=1` returns an inline PDF for the embedded viewer.
- `download=1` returns the final attachment only after the user chooses the
  download action.
- Existing report links were renamed to make the preview step explicit.
- Report toolbars now use the shared `_export-menu.blade.php` dropdown, leaving
  one export button with PDF, CSV, and print choices inside it.
- Overall attendance exports now support PDF as well as CSV, and both retain
  the active member, activity, and date filters.

Regression checks should confirm that filters survive every preview/download
transition, preview responses do not include attachment headers, and final
downloads keep the expected filename and MIME type.

## Removed duplicate summary report

The standalone **Laporan Ringkasan** module was removed because its financial
metrics duplicated **Laporan Kewangan**. This includes its route, controller
action, Blade view, navigation item, financial-toolbar button, and feature test.
Admin report shortcuts now point directly to `reports.financial`. Do not restore
`reports.overview`; new financial analytics should be added to the main
financial report instead.

## Fee card alignment

The three fee-payment summary cards in `payments/index.blade.php` now reuse the
`admin-stat-card` structure from the activity page. The cards retain their modal
behaviour and use `payment-fee-stat-card` only for button reset and focus-state
styling. Keep future payment metrics in the icon/value/label/supporting-text
order so the layout remains consistent across themes and screen sizes.

## Fee payment history in submission form

`payments/create.blade.php` now renders two billing views. The original
checkbox table remains limited to outstanding bills, while a read-only
**Bayaran Tahun Semasa** table displays fee months with `status = paid` in the
current calendar year. This makes previously completed months visible without
allowing duplicate payment submissions. The data is supplied as
`paidCurrentYearBills` by `PaymentSubmissionController::create()`.

## Activity status-card navigation

The activity dashboard cards now navigate to
`activities.status-list` (`/activities/status/{status}`), rather than applying
a filter and rendering a list below the calendar. The new
`resources/views/activities/status-list.blade.php` is a paginated, read-only
list for rejected, pending-review, and treasurer-verified activities. The
approved list additionally provides **Lihat Butiran** and, when the activity is
finished, **Muat Turun Kertas Kerja**. The latter follows the existing report
preview-before-download flow.
