# Product Requirements Document

## Overview

PoliStaff is a staff-club management portal. This document currently focuses on
the activity lifecycle: submission, approval, registration, attendance, and
post-activity evidence.

## Users and responsibilities

- **Member**: submits an activity, registers for an approved activity, scans its
  attendance QR, and uploads evidence after taking part.
- **Treasurer**: reviews submitted activities and generates the attendance QR
  when an activity starts.
- **Admin**: gives final approval, can support attendance reporting, and can
  view audit information. Admin cannot generate an activity QR.
- **Guest**: may register for a public activity but cannot upload member
  evidence or record attendance through the authenticated member flow.

## Activity lifecycle

1. A member submits the activity name, date, start time, end time, location,
   and optional registration window.
2. The activity enters `pending_approval` and is reviewed by the treasurer.
3. Treasurer verification changes the status to `treasurer_verified`.
4. Admin approval changes the status to `approved` and opens the activity to
   registration according to its configured registration window.
5. At the scheduled start, the treasurer generates the attendance QR. No QR is
   created during activity submission.
6. Registered members scan the active QR during the activity.
7. After the end time, registered members can upload photos taken during the
   activity as evidence.

## Functional requirements

### Activity submission

- The submission form must not request an activity description.
- The submission form must not request attendance opening or closing times.
- Evidence photos must not be accepted during submission or editing.
- End time must be later than start time.
- A newly submitted activity must not have a QR token.

### Activity status lists

- Each activity-status card opens its own list page; it must not filter or
  append a list beneath the activity calendar.
- The approved-activity list provides a detail link for each activity and a
  kertas kerja download link once the activity has finished.
- Rejected, pending-review, and treasurer-verified lists are informational
  only and do not provide row actions.

### Attendance QR

- QR generation is available only for an approved activity while it is in
  progress: `date_time <= now <= end_time`.
- Generating a new QR invalidates the previous token.
- A QR scan is accepted only while the activity is in progress and the token is
  active.
- A member must have a `registered` registration for the activity before their
  attendance can be recorded.
- QR generation and attendance changes must be auditable.

### Post-activity evidence

- Evidence upload becomes available only after `end_time`.
- The activity must remain `approved`.
- The uploader must be a registered participant. Treasurer and admin accounts
  may upload for operational support.
- One submission may contain between 1 and 10 images.
- Each image must be no larger than 8 MB.
- Multiple members may contribute multiple evidence photos to the same
  activity.
- Each stored photo must retain its uploader and activity ownership.

### Fee payment selection

- The payment-submission screen must show outstanding bills that can still be
  selected for payment.
- It must also show all fee months already completed in the current calendar
  year, so a member can distinguish settled months from arrears.
- A completed month is a read-only record with status `Selesai`; it must not be
  selectable for another payment submission.

## Acceptance criteria

- Creating an activity leaves `qr_code_token` as `NULL`.
- Selecting an activity-status card opens a separate status-list page.
- Only rows in the approved status list show detail and completed-activity
  kertas kerja actions.
- Before the activity starts, QR generation returns a validation error.
- During an approved activity, only a treasurer can generate the QR and
  registered members can use it.
- Once generated, the QR is displayed beside the activity-detail card. Before
  generation, the same card shows that the QR is waiting for the treasurer.
- After the activity ends, the QR is no longer accepted.
- A registered member cannot upload evidence before the activity ends.
- A waitlisted, cancelled, unregistered, or guest participant cannot upload
  evidence through the member upload endpoint.
- After the activity ends, a registered member can upload several valid images
  and they appear in the activity gallery.

## Report preview requirements

- Every downloadable report or receipt must open a review screen before any
  attachment download begins.
- PDF previews must render the same generated PDF that will be downloaded.
- CSV previews must present the selected rows in a readable HTML table.
- The preview must provide explicit **Back**, **Open / Print** where applicable,
  and **Download** actions.
- Existing report filters must be preserved between the report, preview, inline
  PDF, and downloaded file.
- Authorization for a preview and its download must be identical.
- Each report screen must expose one **Export Report** control. File formats are
  selected inside that control instead of appearing as separate buttons.
- Financial and attendance reports must offer PDF and CSV where applicable.
- **Financial Report** is the single financial analytics workspace. A separate
  summary report is not provided because it duplicates the same information.
