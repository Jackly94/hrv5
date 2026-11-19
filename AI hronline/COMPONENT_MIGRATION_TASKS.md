# Yii2 Component To Yii3 Service Migration Tasks

Purpose: migrate reusable Yii2 `components/` behavior into focused Yii3 services, then use those services in every migrated module.

Rule for this track:

- Do not recreate Yii2 global style such as `Yii::$app->MP` or `Yii::$app->FileManager`.
- Use constructor-injected Yii3 services.
- Keep behavior compatible with Yii2 first, then modernize safely.
- Every completed task must have lint, boot check, and where relevant browser/test verification.
- Redirect result messages must use the shared popup in the main layout. Success, update, and delete popups are centered near the top of the screen, can be manually closed, and auto-hide after 5 seconds. Validation summary and technical error popups stay manually closed only so users can screenshot them. Field validation details still stay inline near the form field.

## Phase 0: Mapping And Boundaries

- [x] Agree migration priority: `MaklumatPeribadi`, `UAPI`, `AccessManager`, `DateHelper`, `Metadata`, `EmployeeMaster`.
- [x] Identify existing Yii3 partial replacements:
  - `MediaHostFileService`
  - record services with repeated audit/touch/date code
  - `LegacySessionReader`
  - `BiodataProfileService`
- [x] Create component-to-service mapping table with Yii2 source, Yii3 target, risk, and first consumer.
- [x] Mark which Yii2 component methods are actually used by Rekod Peribadi modules.
- [x] Decide naming convention for shared services and repositories.
- [x] Browser check: confirm current migrated Rekod Peribadi pages still open before implementation starts.

Phase 0 result, recorded 2026-11-17:

| Yii2 source | Rekod Peribadi usage found | Yii3 target | Risk | First consumer / phase |
| --- | --- | --- | --- | --- |
| `MaklumatPeribadi::Tarikh()` | Date display in biodata, anugerah, badan profesional, jawatan terdahulu, keluarga, kecacatan, sejarah perubatan, professional cert, and staff profile models. | `StaffDateFormatter`, optional `MalayDateFormatter` later. | Low-medium: display fallback must match old UI for empty dates and `0000-00-00`. | Phase 1, starting with existing read/list services. |
| `DateHelper` | Not directly used by current Rekod Peribadi controllers, but overlaps with date/month/day formatting. | `StaffDateFormatter` for staff-record display; broader working-day helper later only when needed. | Low for display; higher if working-day calculation is migrated. | Phase 1. |
| `MaklumatPeribadi::BiodataLastUpdate()` | Called by Rekod Peribadi Yii2 models after create/update/delete, including alamat, akaun, anugerah, bahasa, bidang kepakaran, kecacatan, keluarga, sejarah perubatan, badan profesional, and professional cert. | `BiodataTouchService`. | Medium: write side effects must keep old `tblprcobiodata` timestamp/user fields compatible. | Phase 2, starting with services that already duplicate this logic. |
| Yii2 model audit/stat patterns | Existing write models insert/update `hronline.tbl_stat` and `hronline.updatestatus` around save/delete flows. | `AuditTrailService` and `RecordStatService`. | High: must preserve activity codes and old approval/status expectations. | Phase 2, then every write-flow migration. |
| `UAPI` / `Yii::$app->FileManager::UploadFile()` | Uploads in pendidikan, badan profesional, lesen, akaun, kecacatan, keluarga, bidang kepakaran, vaksinasi, kelayakan profesional. | `MediaHostFileService::uploadDocument()`. | Medium-high: live uploads touch external MediaHost. Browser tests must avoid real upload unless approved. | Phase 3; already partially used by Keahlian, Lesen, Kecacatan. |
| `UAPI` / `DeleteFile()` | Deletes/replaces uploaded files in pendidikan, badan profesional, lesen, akaun, kecacatan, keluarga, bidang kepakaran, kelayakan profesional. | `MediaHostFileService::deleteDocument()`. | Medium-high: live delete is destructive. | Phase 3; Kecacatan already uses delete support. |
| `UAPI` / `DisplayFile()`, `DisplayFilev2()`, `NameFile()`, `Download()`, `DownloadF()` | File labels, view links, inline download/view actions, especially pendidikan and attachment-heavy modules. | `MediaHostFileService::viewUrl()`, `downloadUrl()`, `fileName()`, `fileInfo()`. | Medium: links must work while missing/deleted hashes render cleanly. | Phase 3. |
| `AccessManager::Allowed()` | Used in almost every Rekod Peribadi controller `behaviors()` access rule. | `AuthorizationService::canAccessController()` plus route middleware later. | High: must avoid exposing other staff records. | Phase 5. |
| `MaklumatPeribadi::isKetuaProgram()`, `isInSameProgram()` | Biodata admin/search/profile access for Ketua Program. | `AuthorizationService` and `EmployeeRepository` program lookup helper. | High: affects cross-staff visibility. | Phase 5/6. |
| `MaklumatPeribadi::IsFPSKPP()` | Kelayakan Profesional FPSK approval visibility. | `AuthorizationService::isFpskPp()` or module-specific policy method. | Medium: role/department rule must match old source. | Phase 5, before full Kelayakan Profesional writes. |
| `MaklumatPeribadi::validateProof()` | Kelayakan Profesional proof-file validation. | `MediaHostFileService` validation helper or local form validator. | Low-medium: mostly file/hash validation behavior. | Phase 3 or Kelayakan Profesional module migration. |
| `MaklumatPeribadi::HaveVaccineRecord()` | Vaksinasi form branching. | `VaccinationRecordService` or `StaffRecordOverviewService` method. | Medium: affects first-time vaccine update flow. | Future Vaksinasi write migration. |
| `MaklumatPeribadi::isAllowedClockin()` | Only diagnostic/test action found in Vaksinasi controller. | Do not migrate now. Revisit under attendance/STARS. | Low for Rekod Peribadi. | Out of current scope. |
| `EmployeeMaster` / `Yii::$app->GRP` | Biodata staff sync/admin actions: register, inactive, update department, update position, payroll. Not normal self-service Rekod Peribadi CRUD. | `GrpEmployeeGateway` for external calls; `EmployeeRepository` for local lookup. | High: external GRP integration has side effects. | Phase 6, but only when admin sync flows are migrated. |
| `Metadata` / `Yii::$app->metadata` | Old component scans controllers/actions for access metadata; no direct Rekod Peribadi self-service data usage found. | `RouteMetadataService` later if admin access-management needs it. | Low for current Rekod Peribadi; do not mix with dropdown reference data. | Defer until access-management migration. |
| Repeated dropdown SQL from controllers/services | Current Yii3 and old Yii2 forms repeat option queries for country/state/city, education, license, disability, professional body, membership type, etc. | `ReferenceDataService`. | Medium: dependent dropdowns and Tom Select output shape must stay consistent. | Phase 4. |

Component naming convention:

- Use service names for business operations: `BiodataTouchService`, `AuditTrailService`, `RecordStatService`, `ReferenceDataService`.
- Use repository names for read-only entity lookup: `EmployeeRepository`.
- Use gateway names for external systems: `GrpEmployeeGateway`, future `MediaHostGateway` only if `MediaHostFileService` grows too large.
- Use formatter names for pure formatting: `StaffDateFormatter`, optional `MalayDateFormatter`.
- Use policy/authorization names for access decisions: `AuthorizationService` first, route middleware later.
- Keep Yii3 dependencies constructor-injected; do not recreate Yii2 global aliases such as `Yii::$app->MP`.

Browser baseline:

- Tested through Edge CDP as the logged-in normal staff user.
- Confirmed migrated Rekod Peribadi pages open on `http://127.0.0.1:8084`.
- Confirmed no `8083` links or form actions inside current Rekod Peribadi content cards during the baseline audit.
- Pages checked: `/biodata/userview`, `/biodata/lihatbiodata`, `/biodata/penetapanpengguna`, `/alamat/view`, `/pendidikan/view`, `/bahasa/view`, `/badan-profesional/view`, `/anugerah/view`, `/pasport-permit/view`, `/lesen/view`, `/akaun/senarai-akaun`, `/kecacatan/view`, `/pengalamankerja/view`, `/sejarahperubatan/view`, `/keluarga/view`, `/bidangkepakaran/view`, `/vaksinasi/index`, `/kelayakan-profesional/view`.

Definition of done:

- We can point to a table that says where each Yii2 component method will live in Yii3.

## Phase 1: Date And Formatting Foundation

Yii2 source:

- `components/DateHelper.php`
- `components/MaklumatPeribadi.php::Tarikh()`

Yii3 targets:

- `src/Shared/StaffDateFormatter.php`
- optional later: `src/Shared/MalayDateFormatter.php`

Tasks:

- [x] Inventory date helper methods from Yii2.
- [x] Identify date formatting already duplicated in Yii3 services.
- [x] Implement `StaffDateFormatter`.
- [x] Support common formats:
  - DB date to `d/m/Y`
  - empty date fallback
  - `0000-00-00` fallback
  - Malay month label if needed
- [x] Replace duplicated `formatDate()` in:
  - `AddressRecordService`
  - `LicenseRecordService`
  - `DisabilityRecordService`
  - `StaffRecordOverviewService`
  - `StaffRecordSectionService`
- [x] Add focused tests or browser-safe verification for pages using replaced date formatting.
- [x] Browser test as normal user:
  - open `/biodata/userview`
  - open `/biodata/lihatbiodata`
  - open `/alamat/view`
  - open `/lesen/view`
  - open `/kecacatan/view`
  - verify date labels/fallbacks display correctly and layout does not break.
- [x] Run `php -l` and `php yii list`.

Phase 1 result, recorded 2026-11-17:

- Added `src/Shared/StaffDateFormatter.php`.
- Refactored date display/form-date validation in `BiodataProfileService`, `LicenseRecordService`, `DisabilityRecordService`, `StaffRecordOverviewService`, `StaffRecordSectionService`, and `ProfessionalMembershipService`.
- Browser verification passed for Biodata, Alamat, Lesen, Kecacatan, Keahlian, remaining overview pages, and simple staff-record sections.

Definition of done:

- No migrated Rekod Peribadi service keeps a local ad hoc date formatter unless there is a clear special case.

## Phase 2: Biodata Touch And Audit Trail

Yii2 source:

- `components/MaklumatPeribadi.php::BiodataLastUpdate()`
- model `beforeSave()`, `afterSave()`, `beforeDelete()` audit patterns
- `hronline.updatestatus`
- `hronline.tbl_stat`

Yii3 targets:

- `src/Shared/BiodataTouchService.php`
- `src/Shared/AuditTrailService.php`
- optional: `src/Shared/RecordStatService.php`

Tasks:

- [x] Document exact Yii2 audit fields and activity codes:
  - create = `0`
  - update = `1`
  - delete = `2`
- [x] Implement `BiodataTouchService`.
- [x] Implement `AuditTrailService::logCreate()`.
- [x] Implement `AuditTrailService::logUpdate()`.
- [x] Implement `AuditTrailService::logDelete()`.
- [x] Implement `RecordStatService::insertPending()`.
- [x] Implement `RecordStatService::upsertUpdated()`.
- [x] Implement `RecordStatService::deleteForRecord()`.
- [x] Refactor `AddressRecordService` to use the shared services.
- [x] Refactor `LicenseRecordService` to use the shared services.
- [x] Refactor `DisabilityRecordService` to use the shared services.
- [x] Verify no behavior regression on:
  - `/alamat/view`
  - `/lesen/view`
  - `/kecacatan/view`
- [x] Browser test as normal user:
  - open each affected detail page
  - click add/edit buttons where available
  - submit only validation-safe forms unless user approves live data writes
  - verify successful flows update `Papar ... rekod` and return to the correct Yii3 page.
- [x] Run lint and Yii boot check.

Phase 2 result, recorded 2026-11-17:

- Added `BiodataTouchService`, `AuditTrailService`, and `RecordStatService`.
- `Alamat`, `Lesen`, and `Kecacatan` write services now delegate audit/stat/biodata-touch side effects to shared services.
- Browser validation-safe tests passed; no live create/delete was performed.

Definition of done:

- New write-flow migrations no longer need to hand-write audit/stat/biodata update logic.

## Phase 3: MediaHost / FileManager Replacement

Yii2 source:

- `components/UAPI.php`
- old usage: `Yii::$app->FileManager`

Yii3 target:

- expand `src/Shared/MediaHostFileService.php`

Already partially done:

- [x] Upload support exists.
- [x] Delete support exists.

Tasks:

- [x] Map Yii2 `UploadFile()` options to Yii3 method signatures.
- [x] Add `viewUrl(string $hash): string`.
- [x] Add `downloadUrl(string $hash): string` if used.
- [x] Add `fileInfo(string $hash): array`.
- [x] Add `fileName(string $hash): string`.
- [x] Add graceful fallback for missing/deleted hash.
- [x] Replace raw MediaHost URL creation in detail templates/services.
- [x] Refactor existing services to use file helper methods:
  - `LicenseRecordService`
  - `DisabilityRecordService`
  - `ProfessionalMembershipService`
- [x] Verify detail pages show file links consistently.
- [x] Avoid real upload/delete browser tests unless user approves live data changes.
- [x] Browser test as normal user:
  - open detail pages with existing attachments
  - click view/download links
  - verify missing attachment fallback looks clean
  - confirm no file links redirect to port `8083`.

Definition of done:

- Yii3 code does not need to know MediaHost endpoint URLs directly outside `MediaHostFileService`.

Phase 3 result, recorded 2026-11-17:

- Expanded `MediaHostFileService` with URL/info/name helpers.
- Removed direct MediaHost view URL creation from `DisabilityRecordService`.
- Kept upload/delete tests validation-safe; no real upload/delete was executed.

Rekod Peribadi MediaHost display mapping, recorded 2026-11-17:

| Rekod Peribadi item | Yii2/UAPI hash column | Yii3 display target | Yii3 helper |
| --- | --- | --- | --- |
| Pendidikan | `tblpreduach.filename` | `/pendidikan/lihatpendidikan` | `MediaHostFileService::viewUrl()` |
| Lesen | `tblprlic.filename` | `/lesen/lihatlesen` | `MediaHostFileService::viewUrl()` |
| Kecacatan | `tblprdisability.filename` | `/kecacatan/lihatkecacatan` | `MediaHostFileService::viewUrl()` |
| Akaun | `tbl_akaun.attachment` | `/akaun/lihat` | `MediaHostFileService::viewUrl()` |
| Bidang Kepakaran | `tblprcobidangkepakaran.filename` | `/bidangkepakaran/lihatbidangkepakaran` | `MediaHostFileService::viewUrl()` |
| Keluarga OKU | `tblprfmy.dis_filename_kad_oku`, `dis_filename_sijil_lahir`, `dis_filename_kad_temujanji`, `dis_filename_pengesahan_dr` | `/keluarga/lihatkeluarga` | `MediaHostFileService::viewUrl()` |
| Vaksinasi | `tblstatusvaksinasi.sijil_digital`, `lampiran` | `/vaksinasi/index` status detail/update path | `MediaHostFileService::viewUrl()` |
| Kelayakan Profesional | `tblprprofesionalcert.proof` | `/kelayakan-profesional/view-cc` | `MediaHostFileService::viewUrl()` |
| Badan Profesional | `tblprprofassoc.filename` | `/badan-profesional/view` and future detail/update migration | `MediaHostFileService::viewUrl()`, upload/delete helpers |

Implementation note:

- Overview detail pages carry attachment metadata in service rows and render `Lihat Fail` buttons through the shared detail template.
- List pages do not call `fileInfo()` or `fileName()` so grid loading stays fast and does not depend on live MediaHost responses.
- Upload/delete remains validation-safe in browser tests unless the user explicitly approves live file mutation.

## Phase 4: Reference Data / Metadata

Yii2 source:

- `components/Metadata.php`
- repeated dropdown SQL from old controllers/models

Yii3 target:

- `src/Shared/ReferenceDataService.php`

Tasks:

- [x] Inventory dropdown/reference tables used in Rekod Peribadi.
- [x] Implement generic option query helper.
- [x] Add named methods for common references:
  - countries
  - states by country
  - cities by state
  - education levels
  - license types/classes
  - disability types/causes
  - address types
  - professional bodies
  - membership types
- [x] Replace duplicated option queries in existing services.
- [x] Keep Tom Select output format standard: `list<array{id: string, name: string}>`.
- [x] Verify forms still render searchable dropdowns.
- [x] Browser test as normal user:
  - open every affected add/edit form
  - type inside Tom Select dropdowns
  - verify typed text remains visible
  - verify dropdown options use normal font weight, not bold
  - verify validation submit stays on Yii3 and preserves selected values.

Definition of done:

- New forms can get dropdown options from one service instead of writing repeated SQL.

Phase 4 result, recorded 2026-11-17:

- Added `ReferenceDataService`.
- Refactored reference dropdowns in `AddressRecordService`, `LicenseRecordService`, `DisabilityRecordService`, and `ProfessionalMembershipService`.
- Browser tests confirmed Tom Select still renders and validation-safe submits stay on Yii3.

## Phase 5: Access And Authorization

Yii2 source:

- `components/AccessManager.php`
- `components/AccessBehavior.php`
- controller `behaviors()` access rules

Yii3 target:

- `src/Shared/AuthorizationService.php`
- optional later: Yii middleware

Tasks:

- [x] Map old access levels and second access levels.
- [x] Document current staff session identity shape.
- [x] Implement `AuthorizationService::canAccessController()`.
- [x] Implement record ownership helper for Rekod Peribadi.
- [ ] Replace scattered ownership checks in actions with service calls.
- [x] Add admin/staff boundary rules.
- [ ] Verify unauthorized access returns redirect or 403 consistently.
- [ ] Browser test:
  - login as normal staff user
  - open allowed Rekod Peribadi pages and confirm access works
  - try a blocked/invalid staff record URL and confirm redirect or 403 is consistent
  - confirm no unauthorized route exposes another staff record.

Definition of done:

- New controllers/actions do not manually duplicate access-level logic.

Phase 5 partial result, recorded 2026-11-17:

- Added `AuthorizationService` with controller-role check, own-record check, Ketua Program same-program check, and FPSK PP helper.
- Controller/action wiring and unauthorized browser tests remain open because changing access boundaries needs route-by-route review.

## Phase 6: Employee / Staff Repository

Yii2 source:

- `components/EmployeeMaster.php`
- parts of `components/MaklumatPeribadi.php`

Yii3 target:

- `src/Shared/EmployeeRepository.php`
- `src/Shared/EmploymentProfileService.php`

Tasks:

- [x] Inventory methods used by dashboard/profile/rekod modules.
- [x] Create basic staff lookup methods:
  - by ICNO
  - by identity id
  - display name
  - staff number
  - department/campus
- [x] Move repeated staff lookup SQL into repository.
- [ ] Refactor `BiodataProfileService` in small pieces.
- [x] Refactor record services that only need staff name/ICNO.
- [x] Browser test as normal user:
  - open `/dashboard`
  - open `/biodata/userview`
  - open `/biodata/lihatbiodata`
  - verify name, ICNO, staff number, department, and summary cards match expected staff data.

Definition of done:

- Most migrated modules fetch staff identity/profile data from one repository/service.

Phase 6 partial result, recorded 2026-11-17:

- Added `EmployeeRepository`.
- Refactored Alamat, Lesen, Kecacatan, overview, simple section, and Keahlian services to use centralized staff lookup.
- `BiodataProfileService` remains open because its staff/profile SQL is much broader than simple identity lookup.

## Phase 7: Apply To Future Module Migrations

Tasks:

- [x] Before migrating each remaining Rekod Peribadi write flow, use:
  - `StaffDateFormatter`
  - `AuditTrailService`
  - `BiodataTouchService`
  - `RecordStatService`
  - `MediaHostFileService`
  - `ReferenceDataService`
  - `AuthorizationService`
- [ ] Convert placeholder Yii3 action landing pages into real forms one module at a time.
- [x] Convert simple non-file sections into real Yii3 CRUD:
  - `Bahasa`
  - `Anugerah`
  - `Pengalaman Kerja`
  - `Sejarah Perubatan`
- [x] Browser test simple section CRUD with dummy records:
  - create dummy
  - update the same dummy
  - delete the same dummy
  - confirm no dummy leftovers in DB
- [ ] After each module, update this task file and `progress.md`.
- [ ] Browser test after each module:
  - open the module from `/biodata/userview`
  - click all inner action buttons
  - verify no action goes to port `8083`
  - test validation-safe submit
  - test return/back button placement and green submit standard
  - verify grid/list pagination shows 15 records and `Papar ... rekod` sits bottom-right.

Remaining larger flows to finish after service foundation:

- [x] Biodata update route and service wiring
  - 2026-11-19: `/biodata/kemaskini` and `/biodata/kemaskinikakitangan` now render real Yii3 forms through `BiodataUpdateService` and `Biodata\FormAction`.
  - 2026-11-19: Validation-safe browser tests passed for authenticated self-update GET, selected-staff GET, and empty-submit validation. No live biodata change/save test was performed.
- [x] Pendidikan add/update/delete route and service wiring
- [ ] Pendidikan live dummy MediaHost upload/update/delete browser test
  - 2026-11-17: Upload error path browser-tested with `dummy-2MB.pdf`; no internal server error after removing PHP 8.5-deprecated `curl_close()` calls. MediaHost was unreachable from this environment, so the live success/update/delete test remains open.
  - 2026-11-17: Compared against old Yii2 `components/UAPI.php` / `Yii::$app->FileManager`; endpoint and credentials match. Updated Yii3 Pendidikan upload directory to the old Yii2 path casing: `Maklumat Rekod Kakitangan/Pendidikan`.
- [x] Bahasa add/update/delete
- [x] Anugerah add/update/delete
- [x] Paspot add/update/delete route and service wiring
- [ ] Paspot live dummy MediaHost upload/update/delete browser test
- [x] Permit Kerja add/update/delete route and service wiring
- [ ] Permit Kerja live dummy MediaHost upload/update/delete browser test
- [x] Akaun change request/update route and service wiring
- [ ] Akaun live dummy MediaHost upload/update/delete browser test
- [x] Pengalaman Kerja add/update/delete
- [x] Sejarah Perubatan add/update/delete
- [x] Keluarga add/update/delete route and service wiring
- [ ] Keluarga live dummy MediaHost upload/update/delete browser test
  - 2026-11-17: Updated Yii3 family file directory to match old Yii2 OKU document path: `Maklumat Rekod Kakitangan/keluarga/oku`.
- [x] Bidang Kepakaran add/update/delete route and service wiring
- [ ] Bidang Kepakaran live dummy MediaHost upload/update/delete browser test
  - 2026-11-17: Updated Yii3 expertise file directory to match old Yii2 path casing: `Maklumat Rekod Kakitangan/Bidang Kepakaran`.
- [x] Vaksinasi update flows route and service wiring
- [ ] Vaksinasi live dummy update browser test
- [x] Kelayakan Profesional add/update/delete route and service wiring
- [ ] Kelayakan Profesional live dummy MediaHost upload/update/delete browser test
- [x] Keahlian detail/update/delete route and service wiring
- [ ] Keahlian live dummy MediaHost upload/update/delete browser test

Definition of done:

- Placeholder action pages are gone, replaced by real Yii3 forms/actions with the shared component services.

Phase 7 status, recorded 2026-11-17:

- Foundation rule is now enforced for future work: new write-flow migrations must use the shared formatter/audit/touch/stat/file/reference/authorization services.
- Actual remaining write flows stay open and should be migrated one module at a time.

## Verification Checklist For Every Completed Component

- [x] `php -l` on changed files.
- [x] `php yii list` boots.
- [x] Browser smoke test for impacted Rekod Peribadi pages.
- [x] No `8083` links introduced in content cards.
- [x] No live data write/delete test unless explicitly intended.
- [x] `progress.md` updated with boundary and verification notes.
- [x] This file updated by checking completed boxes.
