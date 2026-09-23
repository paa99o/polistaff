# Architecture

## Overview

PoliStaff is a Laravel application rendered with Blade templates. Activity
business rules are enforced in models and controllers, while role middleware
and gates control access to protected operations.

## Activity module

| Layer | Main files | Responsibility |
| --- | --- | --- |
| Routing | `routes/web.php` | Authenticated activity, QR, registration, and evidence endpoints |
| Validation | `app/Http/Requests/ActivityRequest.php` | Activity submission and schedule validation |
| Controller | `app/Http/Controllers/ActivityController.php` | Lifecycle, approval, QR generation, and evidence upload |
| Attendance | `app/Http/Controllers/AttendanceController.php` | QR scans and manual attendance |
| Registration | `app/Http/Controllers/ActivityRegistrationController.php` | Member, guest, capacity, and waiting-list flow |
| Domain model | `app/Models/Activity.php` | Registration state, attendance period, and finish checks |
| Evidence model | `app/Models/ActivityEvidencePhoto.php` | Photo ownership and relationships |
| Views | `resources/views/activities` | Submission, detail, attendance, QR, and evidence gallery UI |

The QR-generation route is protected by `role:treasurer`. The attendance scan
route remains authenticated because it is the URL encoded into generated QR
images, but it is not exposed as a main navigation item.

`ActivityController::index()` is the calendar landing page. Its status cards
link to `ActivityController::statusList()` through
`activities.status-list`, which renders a separate paginated page for each
allowed status. The approved list alone renders detail and completed-activity
report actions; the other status lists are read-only.

## Request flows

### Submission and approval

```text
Member submission
  -> ActivityRequest validation
  -> Activity(status=pending_approval, qr_code_token=NULL)
  -> Treasurer verification
  -> Admin approval
  -> Registration available
```

### Attendance

```text
Activity reaches start time
  -> Treasurer generates token
  -> QR appears beside the activity-detail card
  -> QR contains attendance scan URL and token
  -> Registered member scans
  -> Activity::attendanceIsOpen() verifies status, token, start, and end
  -> Attendance is created once per member/activity
```

### Evidence

```text
Activity passes end_time
  -> Registered member selects 1-10 images
  -> Controller verifies status, finish time, and registration
  -> Laravel validates image type and 8 MB limit
  -> File stored on public/activity-evidence
  -> ActivityEvidencePhoto stores activity, uploader, and path
  -> Activity page renders the shared gallery
```

## Storage

- Evidence images use Laravel's `public` filesystem disk.
- Production deployment must expose public storage, normally through
  `php artisan storage:link`.
- Database records store relative paths rather than public URLs.

## Database changes

The revised workflow requires these migrations, in order:

1. Create `activity_evidence_photos`.
2. Make `activities.qr_code_token` nullable.
3. Remove standalone attendance-window columns.

Run `php artisan migrate` after deploying the code.

## Verification

- PHP syntax and Blade compilation should pass before release.
- Feature tests should cover QR timing, token regeneration, registration
  eligibility, upload timing, multi-image validation, and authorization.
- Database-backed checks require the configured MySQL service to be running.

## Report export pipeline

Downloadable reports use a three-state request flow:

```text
Report action
  -> Preview page
     -> PDF: embedded inline response (?render=1)
     -> CSV: HTML table generated from the same records
  -> User verifies content
  -> Attachment response (?download=1)
```

`resources/views/reports/download-preview.blade.php` is the shared preview UI.
`resources/views/reports/_export-menu.blade.php` is the shared single-button
format selector.
`ReportController` supplies financial, attendance, and activity exports, while
`TransactionController` uses the same view for receipt PDFs. Authorization is
performed before choosing preview, inline render, or attachment output.

The attendance report supports both PDF and CSV. Both formats use the same
member, activity, and date filters through `attendanceReportRows()`.

The former `reports.overview` route, controller action, and Blade view were
removed. `reports.financial` is now the canonical destination for financial
reporting links from navigation and admin actions.

## Fee payment selection

`PaymentSubmissionController::create()` first ensures monthly bills exist, then
loads two independent collections for `payments/create.blade.php`: outstanding
unpaid/partial/overdue bills for the selectable payment table, and paid bills
whose `billing_month` is in the current year for the read-only completion
table. Keeping these collections separate prevents completed months from being
submitted again while still making the member's current-year payment history
visible.
