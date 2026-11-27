# Biodata Sections Restructure Plan

Date recorded: 2026-11-23

## Goal

Move the "Maklumat Dan Rekod Kakitangan" modules toward a consistent Biodata section structure:

```text
URL:    /biodata/{section}/...
Web:    src/Web/Biodata/Sections/{Section}/...
Shared: src/Shared/Biodata/Sections/{Section}Service.php
```

Keep old Yii2-compatible and current Yii3 URLs working during the migration.

## Execution Status

Last execution pass: 2026-11-23

- [x] New `/biodata/{section}/...` URL aliases added for all "Maklumat Dan Rekod Kakitangan" sections.
- [x] Biodata profile user/admin tiles now generate the new section URLs.
- [x] Old top-level routes remain registered as compatibility aliases.
- [x] Shared URL helper added at `src/Shared/Biodata/Support/BiodataSectionUrlService.php`.
- [x] Keluarga and Pendidikan now have Web wrapper folders under `src/Web/Biodata/Sections/`.
- [x] Keluarga and Pendidikan now have Shared adapter services under `src/Shared/Biodata/Sections/`.
- [x] Menu compatibility map now sends old menu rows to the new Biodata section URLs.
- [x] Admin `?icno=...` context is preserved for shared overview sections, shared simple sections, Alamat, and Keahlian.
- [x] Syntax lint passed for changed PHP files.
- [x] Smoke-tested new URLs redirect cleanly to login when unauthenticated.
- [x] Section-owned Web CRUD action classes now exist for every `/biodata/{section}/...` item.
- [x] Phase 4 modules are routed through their own Biodata section folders.
- [x] Akaun is now physically extracted from the generic shared overview/CRUD services.
- [x] Akaun now owns its local UI templates: `view.php`, `detail.php`, and `form.php`.
- [x] Biodata sections from Alamat through Vaksinasi now own local UI templates where they have pages.
- [x] Pendidikan, Keluarga, Pasport Permit, Bidang Kepakaran, and Vaksinasi now own their Web view/detail/form/delete action classes under `src/Web/Biodata/Sections/`.
- [x] Old generic Web overview folders removed:
  - [x] `src/Web/StaffRecordOverview`
  - [x] `src/Web/StaffRecordOverviewCrud`
- [x] Old generic simple-record Web folder removed:
  - [x] `src/Web/StaffRecord`
- [x] Old root shared staff-record services removed from `src/Shared`:
  - [x] `StaffRecordSectionService`
  - [x] `StaffRecordSectionCrudService`
  - [x] `StaffRecordOverviewService`
  - [x] `StaffRecordOverviewCrudService`
- [x] Shared Biodata-only support services now live under `src/Shared/Biodata/Sections/`:
  - [x] `BiodataSimpleRecordService`
  - [x] `BiodataSimpleRecordCrudService`
  - [x] `BiodataOverviewService`
  - [x] `BiodataOverviewCrudService`
- [ ] Full physical extraction of every list/save/delete query into one service per section is still pending.
- [ ] Full create/update/delete browser verification while logged in is still pending.

## Target Convention

### Web Layer

```text
src/Web/Biodata/
  IndexAction.php
  ProfileAction.php
  DetailAction.php
  template.php

  Sections/
    Alamat/
      ViewAction.php
      DetailAction.php
      FormAction.php
      DeleteAction.php
      view.php
      detail.php
      form.php

    Keluarga/
      ViewAction.php
      DetailAction.php
      FormAction.php
      DeleteAction.php
      view.php
      detail.php
      form.php

    Pendidikan/
      ViewAction.php
      DetailAction.php
      FormAction.php
      DeleteAction.php
      view.php
      detail.php
      form.php
```

Example route:

```php
Route::get('/biodata/keluarga/view')
    ->action(Web\Biodata\Sections\Keluarga\ViewAction::class);
```

Expected local URL:

```text
http://127.0.0.1/hronline/biodata/keluarga/view
```

Admin URL convention:

```text
http://127.0.0.1/hronline/biodata/keluarga/adminview?icno=681114015611
```

### Shared Layer

Shared services should follow data/business ownership, not URL ownership:

```text
src/Shared/Biodata/
  BiodataProfileService.php
  BiodataUpdateService.php

  Sections/
    KeluargaService.php
    KeluargaCrudService.php
    PendidikanService.php
    PendidikanCrudService.php
    AlamatService.php
    AkaunService.php
    VaksinasiService.php

  Support/
    BiodataSectionUrlService.php
    BiodataSectionAccessService.php
```

Principle:

```text
Web follows page structure.
Shared follows business/data ownership.
```

Additional rule requested during execution:

```text
Every Biodata section item must own its CRUD classes.
Shared helpers may exist, but the route/action ownership cannot remain in one generic all-sections CRUD controller.
```

UI ownership standard:

```text
Every Biodata section item that has a page must own its local UI template files under
src/Web/Biodata/Sections/{Section}/, usually:

- view.php
- detail.php
- form.php
```

## Migration Phases

### Phase 1: Foundation

- [x] Create shared Biodata section URL helper.
- [x] Standardize user/admin section URL generation.
- [x] Standardize query-safe redirects:
  - [x] append `?` only when there is no query string
  - [x] append `&` when query string already exists
  - [x] tolerate malformed legacy URLs such as `?icno=...?...`
- [x] Add new `/biodata/{section}/...` route aliases.
- [x] Keep old routes working during transition:
  - [x] `/keluarga/view`
  - [x] `/keluarga/adminview`
  - [x] `/pendidikan/view`
  - [x] other current section URLs
- [x] Update Biodata profile menu builder to support both old and new URL modes.

### Phase 2: Pilot Module - Keluarga

- [x] Create `src/Web/Biodata/Sections/Keluarga/`.
- [x] Create or move Keluarga web files:
  - [x] `ViewAction.php`
  - [x] `DetailAction.php`
  - [x] `FormAction.php`
  - [x] `DeleteAction.php`
  - [x] `view.php`
  - [x] `detail.php`
  - [x] `form.php`
- [x] Create `src/Shared/Biodata/Sections/KeluargaService.php`.
- [x] Create `src/Shared/Biodata/Sections/KeluargaCrudService.php`.
- [ ] Extract Keluarga list/detail logic from `StaffRecordOverviewService`.
- [ ] Extract Keluarga CRUD config and save/delete handling from `StaffRecordOverviewCrudService`.
- [x] Restore Keluarga Yii2 field parity baseline in dedicated Yii3 detail/form:
  - full detail query for old `tblprfmy` fields and reference labels
  - expanded form fields for identity, status, marriage, work, address, OKU, and health flags
  - disease/allergy read tables from `tblprfmy_disease`
- [x] Record Keluarga implementation changes from 2026-11-27:
  - `KeluargaService.php` now uses a dedicated Yii2-parity detail query for `tblprfmy`, reference labels, OKU verification fields, attachments, and disease/allergy read tables.
  - `KeluargaCrudService.php` now exposes the expanded Yii2-family form field set with section markers for basic identity, status, marriage, employment, address/contact, OKU, and health information.
  - `BiodataSectionCrudSupport.php` now skips non-input section markers during defaults, edit values, normalization, validation, record building, and file-field handling; it also loads the extra Keluarga reference option lists.
  - `src/Web/Biodata/Sections/Keluarga/form.php` now renders form section headings.
  - `src/Web/Biodata/Sections/Keluarga/detail.php` now renders disease/allergy tables and uses the Keluarga-only compact detail-list class.
  - `assets/main/site.css` now styles the Keluarga detail list as a two-column desktop grid with single-column responsive fallback.
- [ ] Restore exact Yii2 Keluarga behavior still outside field parity:
  - UMS/non-UMS chooser and staff-address prefill
  - disease/allergy add/edit/delete row actions
  - relation verification side effects in `pdstblkeluarga`
- [x] Add new Keluarga routes:
  - [x] `/biodata/keluarga/view`
  - [x] `/biodata/keluarga/adminview`
  - [x] `/biodata/keluarga/lihatkeluarga`
  - [x] `/biodata/keluarga/ums-nonums`
  - [x] `/biodata/keluarga/update`
  - [x] `/biodata/keluarga/delete`
- [x] Keep old Keluarga routes as aliases or redirects:
  - [x] `/keluarga/view`
  - [x] `/keluarga/adminview`
  - [x] `/keluarga/lihatkeluarga`
  - [x] `/keluarga/ums-nonums`
  - [x] `/keluarga/update`
  - [x] `/keluarga/delete`
- [x] Update Biodata user profile Keluarga tile to `/biodata/keluarga/view`.
- [x] Update Biodata admin profile Keluarga tile to `/biodata/keluarga/adminview?icno=...`.
- [ ] Verify user Keluarga list/detail/add/update/delete.
- [ ] Verify admin Keluarga list/detail/add/update/delete.

### Phase 3: Second Module - Pendidikan

- [x] Create `src/Web/Biodata/Sections/Pendidikan/`.
- [x] Create `src/Shared/Biodata/Sections/PendidikanService.php`.
- [x] Create `src/Shared/Biodata/Sections/PendidikanCrudService.php`.
- [x] Move or recreate Pendidikan view/detail/form/delete actions.
- [x] Preserve current tabs UI using the global `app-tabs` standard.
- [x] Add new Pendidikan routes under `/biodata/pendidikan/...`.
- [x] Keep old `/pendidikan/...` routes working.
- [x] Update user/admin Biodata section links.
- [ ] Verify user/admin list, tabs, detail, add/update/delete.

### Phase 4: Migrate Shared Overview Modules

- [x] Move Akaun to Biodata section structure.
- [x] Extract Akaun list/detail logic from `StaffRecordOverviewService`.
- [x] Extract Akaun form/save logic from `StaffRecordOverviewCrudService`.
- [x] Extract Akaun `view.php`, `detail.php`, and `form.php` from shared templates.
- [x] Route old `/akaun/...` aliases through the Akaun-owned Biodata section actions.
- [x] Split Akaun staff change-request flow from admin live-account CRUD.
- [x] Add `AkaunRequestService` for `hronline.tbl_akaun_permohonan_tukar`.
- [x] Add staff request routes:
  - [x] `/biodata/akaun/permohonan-penukaran-akaun-gaji`
  - [x] `/biodata/akaun/mohon-tukar-akaun`
  - [x] `/biodata/akaun/lihat-acc`
- [x] Add explicit admin live-account CRUD routes:
  - [x] `/biodata/akaun/admin-tambah`
  - [x] `/biodata/akaun/admin-kemaskini`
- [x] Remove staff `Kemaskini` links from live Akaun rows.
- [x] Guard admin live-account form routes so an admin target `icno` is required.
- [x] Move Bidang Kepakaran to Biodata section structure.
- [x] Move Vaksinasi to Biodata section structure.
- [x] Move Kelayakan Profesional to Biodata section structure.
- [x] Move Paspot & Permit Kerja to Biodata section structure.
- [x] Replace final Web overview wrappers for Lesen, Kecacatan, and Kelayakan Profesional.
- [x] Give Kelayakan Profesional local `view.php` and `detail.php` templates.
- [x] Replace generic Web action delegation with section-owned action classes for:
  - [x] Pendidikan
  - [x] Keluarga
  - [x] Pasport Permit
  - [x] Bidang Kepakaran
  - [x] Vaksinasi
- [x] Replace generic `Web/StaffRecord` delegation with section-owned action classes for:
  - [x] Bahasa
  - [x] Anugerah
  - [x] Pengalaman Kerja
  - [x] Sejarah Perubatan
- [x] Decide whether Lesen remains module-specific or moves fully under Biodata sections.
- [ ] Remove migrated module branches from `StaffRecordOverviewService`.
- [ ] Remove migrated module config from `StaffRecordOverviewCrudService`.

### UI Template Ownership Pass

- [x] Add section-local UI templates for Alamat.
- [x] Add section-local UI templates for Pendidikan.
- [x] Add section-local UI templates for Bahasa.
- [x] Add section-local UI templates for Keahlian.
- [x] Add section-local UI templates for Anugerah.
- [x] Add section-local UI templates for Paspot & Permit Kerja.
- [x] Add section-local UI templates for Lesen.
- [x] Add section-local UI templates for Akaun.
- [x] Add section-local UI templates for Kecacatan.
- [x] Add section-local UI templates for Pengalaman Kerja.
- [x] Add section-local UI templates for Sejarah Perubatan.
- [x] Add section-local UI templates for Keluarga.
- [x] Add section-local UI templates for Bidang Kepakaran.
- [x] Add section-local UI templates for Vaksinasi.
- [x] Route shared/top-level actions to section-local templates for `/biodata/{section}/...` paths.

### Phase 5: Migrate Existing Standalone Modules

- [x] Route `/biodata/alamat/...` and old `/alamat/...` through `src/Web/Biodata/Sections/Alamat`.
- [x] Route `/biodata/keahlian/...` and old `/badan-profesional/...` through `src/Web/Biodata/Sections/Keahlian`.
- [x] Retire old `src/Web/Alamat` compatibility module.
- [x] Retire old `src/Web/BadanProfesional` compatibility module.
- [x] Retire old `src/Web/Kecacatan` compatibility module.
- [x] Retire old `src/Web/Lesen` compatibility module.
- [x] Retire old `src/Web/KelayakanProfesional` compatibility module.
- [x] Move Bahasa to Biodata section structure.
- [x] Move Anugerah to Biodata section structure.
- [x] Move Pengalaman Kerja to Biodata section structure.
- [x] Move Sejarah Perubatan to Biodata section structure.
- [ ] Keep old public URLs as aliases or redirects.
- [x] Update all Biodata section links to `/biodata/{section}/...`.

### Phase 6: Cleanup

- [ ] Delete or retire `StaffRecordOverviewService` once empty.
- [ ] Delete or retire `StaffRecordOverviewCrudService` once empty.
- [ ] Delete or retire `StaffRecordSectionService` once empty.
- [ ] Delete or retire `StaffRecordSectionCrudService` once empty.
- [x] Audit generic service usage before deletion.
- [x] Confirm generic shared services are not empty yet and must remain for compatibility.
- [x] Route all active `/biodata/{section}/...` entrypoints through Biodata section action classes.
- [x] Route old top-level section compatibility URLs through Biodata section action classes.
- [ ] Ensure all section URLs use the shared URL helper.
- [x] Ensure all admin/user redirects use the query-safe helper.
- [x] Search for old hardcoded section paths and replace where appropriate.
- [x] Update migration notes and developer documentation.

### Biodata Workflow Standard

- [x] Canonical section URLs use `/biodata/{section}/...`.
- [x] Old top-level section URLs are compatibility aliases only.
- [x] Section-owned Web action classes live under `src/Web/Biodata/Sections/{Section}`.
- [x] Section-owned shared services live under `src/Shared/Biodata/Sections`.
- [x] Section-owned templates live under `src/Web/Biodata/Sections/{Section}`.
- [x] Staff request/approval workflows are not mixed into live-record CRUD.
- [x] If staff cannot directly change the live table, create a dedicated request service and request actions.
- [x] Admin live-record CRUD routes must be explicit admin routes and preserve selected staff context using `?icno=...`.
- [x] Staff-facing lists must not show update/delete actions for records that are controlled by admin verification.
- [x] MediaHost uploads use migrated Yii3 `MediaHostFileService`, not direct Yii2 UAPI usage.

### Akaun Change Request Completion

- [x] Staff `Penukaran Akaun` opens the request list.
- [x] Staff `Mohon Baru` creates a request in `hronline.tbl_akaun_permohonan_tukar`.
- [x] Request form captures `no_akaun`, `kod_bank`, `alasan`, `nama_pemilik`, `ic_pemilik`, and required attachment.
- [x] Request save derives `kod_swift` from `hronline.tbl_bank_main`.
- [x] Request status display covers waiting verification, returned, waiting approval, approved, and rejected.
- [x] Request detail page is read-only.
- [x] Live `hronline.tbl_akaun` create/update remains admin-only in route convention.
- [ ] Admin verification and approval screens migrated from Yii2 `pengesahan-data-staf/akaun_gaji`.
- [ ] Authenticated safe-data browser test submits a dummy change request.

### Phase 7: Verification

- [ ] Browser test user Biodata profile.
- [ ] Browser test admin Biodata profile.
- [ ] Click every "Maklumat Dan Rekod Kakitangan" tile as user.
- [ ] Click every "Maklumat Dan Rekod Kakitangan" tile as admin with `?icno=...`.
- [ ] Test create/update/delete for each migrated module.
- [ ] Test malformed legacy URLs do not break staff lookup.
- [ ] Confirm old URLs still work or redirect intentionally.
- [x] Confirm new URLs display as `/hronline/biodata/{section}/...`.

## Recommended First Execution Task

- [x] Task 1: Add Biodata section URL helper and new Keluarga route aliases, without moving files yet.

This creates the new URL shape safely before extracting Keluarga into its own Web and Shared structure.
