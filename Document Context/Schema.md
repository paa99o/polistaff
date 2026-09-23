# Schema

## Overview

This section documents the tables involved in the activity, attendance, and
evidence workflow. Other PoliStaff entities are outside the scope of this
revision.

## Relationships

```text
users 1 -- * activities                  (created_by)
users 1 -- * activity_registrations
activities 1 -- * activity_registrations
users 1 -- * attendances
activities 1 -- * attendances
users 1 -- * activity_evidence_photos   (uploader)
activities 1 -- * activity_evidence_photos
```

## `activities`

Important fields:

| Field | Type | Rules |
| --- | --- | --- |
| `id` | bigint | Primary key |
| `created_by` | foreign key, nullable | Member who submitted the activity |
| `title` | string | Required |
| `date_time` | datetime | Activity start |
| `end_time` | datetime, nullable | Activity end; required by the current form |
| `location` | string | Required |
| `max_participants` | unsigned integer, nullable | `NULL` means no limit |
| `registration_opens_at` | datetime, nullable | Optional registration start |
| `registration_closes_at` | datetime, nullable | Optional registration end |
| `status` | enum | Workflow status |
| `qr_code_token` | string, nullable, unique | `NULL` until generated at activity start |

The obsolete `attendance_opens_at` and `attendance_closes_at` columns are
removed by migration `2026_09_24_000003_remove_activity_attendance_window_fields`.
The legacy `description`, `evidence_photo_path`, and `report_photo_path` columns
may remain for backward compatibility but are not used by the new submission
and evidence flow.

## `activity_registrations`

Links an authenticated user to an activity. The `(user_id, activity_id)` pair
is unique. Relevant statuses are `registered`, `waitlisted`, and `cancelled`.
Only `registered` grants attendance and evidence-upload eligibility.

## `attendances`

Stores `user_id`, `activity_id`, `scanned_at`, and the `qr_code_token` used for
the attendance event. The `(user_id, activity_id)` pair is unique, preventing
duplicate attendance records.

## `activity_evidence_photos`

Introduced by migration
`2026_09_24_000001_create_activity_evidence_photos_table`.

| Field | Type | Rules |
| --- | --- | --- |
| `id` | bigint | Primary key |
| `activity_id` | foreign key | References `activities`; cascade on delete |
| `user_id` | foreign key | Uploader; references `users`; cascade on delete |
| `path` | string | Relative path on the public storage disk |
| `created_at` | timestamp | Upload time |
| `updated_at` | timestamp | Last record update |

An activity has many evidence photos, and a user may upload evidence for many
activities in which they are registered.
