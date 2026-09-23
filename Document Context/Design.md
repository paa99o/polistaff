# PoliStaff Design System

## Direction

PoliStaff uses a light, engineered operations interface. The visual language is
precise and editorial: a white canvas, sharp rectangular geometry, thin
hairlines, disciplined typography, and restrained blue identity accents.

## Brand

- Product name: **POLISTAFF**
- Descriptor: **Portal Pengurusan Kelab Staf**
- Temporary mark: `PS` monogram until the official logo is supplied

## Color

The approved palette is:

- Primary: `#3878F8`
- Secondary: `#E0F2FE`
- Background: `#FFFFFF`
- Text: `#334155`
- Surface: `#FFFFFF`

Primary is reserved for important actions, active navigation, focus indicators,
and selected data. Status must always include a text label or icon; color alone
must not communicate meaning.

## Typography

- Family: Inter, with the system sans-serif stack as fallback
- Scale: 12, 14, 16, 20, 24, 32, 40, 48, and 64 pixels only
- Headings: 600–700 weight and 1.2 line height
- Body: 400–500 weight and 1.5 line height
- Readable prose width: maximum 720 pixels

## Layout

- Mobile-first targets: 375px, 768px, and 1440px
- Base spacing unit: 8px
- Desktop content width: maximum 1280px
- Grid: 12 columns with 24px gutters
- Sidebar: 264px desktop, off-canvas below 768px
- Top navigation: 72px and sticky

## Components

- Cards, controls, buttons, and inputs: 0px radius
- Circular controls are reserved for icon-only actions and avatars
- Buttons: minimum 44px tall
- Inputs: 48px tall with labels above
- Cards: white surface, 1px hairline border, no shadow
- Focus: 2px primary outline with 2px offset
- Motion: 240ms ease-in-out

## Dashboard Hierarchy

1. Personalized greeting and pending-action summary
2. Role-specific summary cards
3. Required actions
4. Upcoming activities and operational information
5. Recent notifications

The official logo will replace the temporary monogram without changing the
navigation dimensions.

## Activity Experience

### Status cards and lists

- Status cards are navigation controls: selecting one opens a dedicated list
  page and never expands a filtered list beneath the calendar.
- The calendar landing page contains the cards and calendar only.
- The approved list exposes **Lihat Butiran** and, after the activity ends,
  **Muat Turun Kertas Kerja**.
- Rejected, pending-review, and treasurer-verified lists are read-only tables
  with no row action buttons.

### Submission form

The activity form asks only for information needed before approval:

- activity name;
- date, start time, and end time;
- location; and
- optional registration opening and closing times.

Do not show description, attendance-window, QR, or evidence-upload controls in
the submission form. Evidence belongs to the post-activity state.

### Time-based states

The activity detail screen must make the current state understandable without
depending on color alone:

| State | Primary UI |
| --- | --- |
| Before approval | Approval status and edit action for eligible owner |
| Approved, before start | Registration state; QR generation unavailable |
| In progress | QR card beside activity details; generation control for treasurer only |
| Finished | Evidence gallery and upload control for registered members |

### QR panel

- Display the generate button only while the approved activity is in progress.
- Display the QR card beside the activity-detail card to every user who may view
  the approved activity.
- Before generation, show a neutral message explaining that the QR is available
  only during the activity and is waiting for the treasurer.
- Do not expose a standalone **Imbas Kehadiran** navigation or dashboard action.
- Never render an empty token or empty attendance link.
- After generation, display the QR, its active-until message, and operational
  attendance information together.

### Evidence gallery

- Use a responsive grid: two columns on small screens and three where space
  allows.
- Preserve image aspect ratio and use descriptive alternative text.
- The upload control accepts multiple images and explains that it becomes
  available only after the activity is complete.
- Validation errors must appear beside the upload control.

## Report Preview Experience

- Each report toolbar uses one **Eksport Laporan** dropdown to avoid multiple
  competing download buttons.
- PDF, CSV, and **Cetak / Simpan PDF** are format choices inside that dropdown.
- Choosing a file format opens its preview; it does not immediately download.
- The preview header explains that the file has not been downloaded yet.
- Primary actions appear together: **Kembali**, **Buka / Cetak** for PDF, and
  **Muat Turun PDF/CSV**.
- PDF reports use a large embedded viewer so pagination and layout can be
  inspected before download.
- CSV reports use a responsive table with the final report headings and rows.
- Empty reports show an explicit empty state instead of a blank viewer.
- The final download action uses the primary button style; preview and back
  actions use secondary styles.
- Do not show a **Ringkasan** action in the financial report toolbar. The
  financial report already contains the required totals, trend, category, and
  transaction information.

## Fee Payment Summary Cards

- Fee summary cards reuse the same visual structure as activity summary cards:
  icon tile, large value, label, and muted supporting text.
- Each card is a full-card button that opens its related detail modal.
- Text elements must remain separate blocks so amounts, labels, and month counts
  never run together.
- Keyboard focus must be visible on every clickable summary card.

## Fee Payment Selection

- Show outstanding months in the selectable table, with the oldest outstanding
  month marked as the first month to pay.
- Below it, show a clearly labelled **Bayaran Tahun Semasa** table containing
  completed months, amount paid, and a textual **Selesai** status.
- Completed-month rows are informational only and must have no checkbox or
  other payment action.
