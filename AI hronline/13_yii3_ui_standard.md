# Yii3 UI Standard

Date recorded: 2026-11-23

This is the agreed UI standard for migrated HROnline Yii3 pages.

Use this document as the default reference before adding or changing a Yii3 page.

## Core Page Structure

Every migrated page should use one main page surface:

```html
<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            ...
        </div>
        ...
    </section>
</main>
```

Standards:

- Use one `page-main-card` per page.
- Put page title, subtitle, and page-level actions in `page-card-heading`.
- Use `profile-actions` for top-right actions.
- Use `button-link button-link-secondary` for back/cancel links.
- Do not show ICNO/UMSPER in card headings unless the page specifically needs it.
- Content should fill the available layout width; do not leave narrow centered content on large monitors.

## Inner Content

Use inner cards for actual data, forms, lists, and detail blocks:

```html
<section class="content-inner-card">
    <div class="profile-card-header grid-card-header grid-result-header">
        <div>
            <h2>...</h2>
        </div>
    </div>
    ...
</section>
```

Standards:

- Use `content-inner-card` for list/detail/form data areas.
- Use `grid-card-header` for inner section headers.
- Use `grid-result-header` for list/table headers.
- Do not put decorative cards inside cards.
- Do not create marketing-style sections for operational HR pages.

## Global List Standard

All GridView-style migrated lists should follow the same table pattern.

Classes:

- `table-scroll`
- `grid-table-wrap`
- `data-table`
- `staff-overview-table` or module-specific table class
- `grid-name` for the main readable name column
- `grid-empty` for empty rows
- `grid-count-footer`
- `grid-pagination`
- `grid-count`

Pagination:

- Default page size is **15 records**.
- Use nearby numbered pages plus `Sebelum` and `Seterus`.
- Preserve current filters/search values in pagination links.
- If a page has more than one table, each table should have its own page query parameter.
  - Example: `pending_page`
  - Example: `history_page`
- The footer count must show the visible range and total:

```text
Papar 1-15 daripada 113 rekod
Papar 16-30 daripada 113 rekod
```

Actions:

- Use `grid-actions-inline` inside action table cells.
- Use `grid-action` for row actions.
- Use Lucide icons inside action links/buttons.
- Use `eye` for view, `pencil` for update, `trash-2` for delete, `plus` for add, `search` for search.
- Use `grid-action-danger` for destructive actions.

Status:

- Use `status-badge` for status cells.
- Use `status-badge-success` for approved/active/success states.
- Use `status-badge-danger` for rejected/returned/inactive/error states.
- Use `status-badge-warning` for pending/waiting states.
- Status badges must be visibly color-filled, not outline-only.
- Keep the final color variant rules after the base `.status-badge` rule in CSS so the fill color is not overridden.
- Standard mapping:
  - `Diluluskan`, `Aktif`, approved, active, verified: `status-badge-success`
  - `Ditolak`, `Dikembalikan`, `Tidak Aktif`, `Bekas Pekerja`, rejected, returned, inactive, former employee: `status-badge-danger`
  - `Menunggu Pengesahan`, `Menunggu Kelulusan`, pending, waiting: `status-badge-warning`

## Search And Filters

Search/filter areas should be compact operational controls, not large feature cards.

Use:

- `content-inner-card`
- `record-form` or module toolbar standard
- `record-form-grid`
- `grid-field`
- `grid-field-wide`
- `record-form-actions`

Standards:

- Search buttons use a Lucide `search` icon.
- Reset buttons use a Lucide `rotate-ccw` icon.
- Search forms use GET.
- Keep filter labels short and clear.
- Search/filter values must persist after submitting and after pagination.

## Forms

Form pages should use one main card and one inner form card.

Standards:

- Use `record-form`.
- Use `record-form-grid`.
- Use `grid-field` and `grid-field-wide`.
- Use `record-form-actions` for bottom actions.
- Center form action groups where the existing form standard does so.
- Save/submit buttons use the green success style, currently `button-link-success`.
- Back/cancel uses `button-link button-link-secondary`.
- Required labels should show `*`.
- Use `field-error` for validation messages.
- For uploads, use the migrated MediaHost service and Yii3 config, not direct Yii2 UAPI setup.

## Detail Pages

Detail pages should use:

- `detail-list` for label/value content.
- `content-inner-card` for each major detail group.
- `grid-action` for file links.
- `file-text` icon for file/view attachment links.

Standards:

- Read-only detail pages must not show write buttons.
- Write detail pages can show a form block below the read-only detail block.
- Missing records should show a safe empty/not-found state or redirect to a safe queue.

## Tabs

Use the global tab standard when a page has natural groups at the same level.

Classes:

- `app-tabs`
- `staff-overview-tabs`
- `data-tabs`
- `app-tab-panel`
- `staff-overview-tab-panel`

Standards:

- Use tabs when a section has multiple equal groups, such as Pendidikan levels.
- Each tab owns its own table/list section.
- Tabs should not be simulated with unrelated cards.
- Preserve tab behavior across user/admin views.
- If only one group exists, render the normal list without tabs.

## Biodata Section Standard

Biodata sections live under:

```text
src/Web/Biodata/Sections/{SectionName}/
src/Shared/Biodata/Sections/{SectionName}Service.php
src/Shared/Biodata/Sections/{SectionName}CrudService.php
```

Standards:

- Each biodata item owns its independent UI templates.
- Each biodata item owns its independent service and CRUD service.
- Avoid shared generic CRUD config for items that are same-level business modules.
- Staff view and admin view are different access modes.
- Staff view must not show delete unless explicitly allowed.
- Admin view can show admin-only CRUD controls where the old Yii2 behavior supports it.
- Admin routes should remain under `/biodata/{section}/adminview` or equivalent local admin route.

## Pengesahan Data Staf Standard

The Human Resource verification module lives under:

```text
src/Web/PengesahanDataStaf/
src/Shared/PengesahanDataStaf/
```

URL convention:

```text
/pengesahan-data-staf/index
/pengesahan-data-staf/akaun-bank
/pengesahan-data-staf/{section}
```

For Akaun Bank:

- Pending queue and history queue use the global list standard.
- Queue lists are paginated at 15 records.
- Use independent page params such as `pending_page` and `history_page`.
- History section title remains `Permohonan yang telah diambil tindakan`.
- Write actions must be POST + CSRF.
- Do not test approve/reject on live rows unless a safe row is selected explicitly.

## Attendance/STARS Toolbar Standard

Attendance/STARS migrated pages use the `attendance-standard-*` toolbar pattern.

Classes:

- `attendance-standard-toolbar`
- `attendance-standard-title`
- `attendance-standard-icon`
- `attendance-standard-filter`
- `attendance-standard-fields`
- `attendance-standard-actions`
- `attendance-standard-count`

Standards:

- Use the toolbar for operational queue/filter/report pages.
- Data content still sits below inside `content-inner-card`.
- Use `data-table`, `status-badge`, `grid-action`, and Lucide icons.
- Do not reintroduce Yii2/Kartik/GridView UI on migrated Yii3 pages.

## Buttons And Icons

Use Lucide icons for actions wherever possible.

Common mapping:

- Back: `arrow-left`
- Search: `search`
- Reset: `rotate-ccw`
- Add: `plus`
- View: `eye`
- Edit: `pencil`
- Delete: `trash-2`
- File: `file-text`
- External/Yii2 handoff: `external-link`
- Approve/accept/save: `check`
- Reject/cancel: `x`

Standards:

- Avoid plain text-only command buttons when a familiar icon exists.
- Button text should be short: `Kembali`, `Cari`, `Reset`, `Tambah`, `Lihat`, `Kemaskini`, `Padam`, `Simpan`.
- Destructive buttons must be visually distinct and should confirm before delete.

## Yii2 Handoffs

When a route is not migrated yet:

- Prefer a clear local Yii3 page with a `Yii2` action link if useful.
- Use `LegacyUrl::to(...)` for Yii2 handoff links.
- Mark Yii2 links with `external-link` and `Yii2`.
- Do not mix Yii2 widgets into Yii3 templates.

## Verification Checklist

Before marking a migrated UI page complete:

- [ ] `php -l` passes for changed PHP files.
- [ ] `php yii list` passes.
- [ ] Unauthenticated route redirects to `/login`.
- [ ] Authenticated read-only smoke returns `200`.
- [ ] Lists show 15 records per page when more than 15 records exist.
- [ ] Pagination preserves filters and independent table page params.
- [ ] Footer shows `Papar X-Y daripada Z rekod`.
- [ ] Action buttons use Lucide icons.
- [ ] Status values use `status-badge`.
- [ ] Large monitor layout uses full content width.
- [ ] Write actions are tested only on safe rows with explicit confirmation.
