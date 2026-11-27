# Pengesahan Data Staf Migration Plan

Date recorded: 2026-11-23

## Goal

Migrate the Yii2 `PengesahanDataStafController` workflows into a Yii3-owned Human Resource module.

```text
URL:    /pengesahan-data-staf/...
Web:    src/Web/PengesahanDataStaf/...
Shared: src/Shared/PengesahanDataStaf/...
```

Initial implementation focus: **Akaun Gaji** / bank-account change verification and approval.

## Target Convention

### Module URLs

```text
/pengesahan-data-staf/index
/pengesahan-data-staf/akaun-bank
/pengesahan-data-staf/keahlian
/pengesahan-data-staf/keluarga
/pengesahan-data-staf/hubungan-keluarga
```

### Web Layer

```text
src/Web/PengesahanDataStaf/
  IndexAction.php
  index.php

  AkaunBank/
    IndexAction.php
    VerificationAction.php
    VerifyAction.php
    VerificationDetailAction.php
    ApprovalAction.php
    ApproveAction.php
    ApprovalDetailAction.php
    index.php
    queue.php
    detail.php

  Keahlian/
  Keluarga/
  HubunganKeluarga/
```

### Shared Layer

```text
src/Shared/PengesahanDataStaf/
  PengesahanDataStafService.php
  AkaunBankVerificationService.php
  KeahlianVerificationService.php
  KeluargaVerificationService.php
  HubunganKeluargaVerificationService.php
```

## Old Yii2 Source Baseline

Controller:

- `C:\Apache24\htdocs\staff\controllers\PengesahanDataStafController.php`

Views:

- `views/pengesahan-data-staf/index.php`
- `views/pengesahan-data-staf/akaun_gaji/index.php`
- `views/pengesahan-data-staf/akaun_gaji/verification_index.php`
- `views/pengesahan-data-staf/akaun_gaji/_verify.php`
- `views/pengesahan-data-staf/akaun_gaji/_verified.php`
- `views/pengesahan-data-staf/akaun_gaji/approval_index.php`
- `views/pengesahan-data-staf/akaun_gaji/_approve.php`
- `views/pengesahan-data-staf/akaun_gaji/_approved.php`

Models/tables:

- `TblAccountChangeApplication`
- `TblAccountChangeApplicationSearch`
- `TblAkaunQueueTukar`
- `hronline.tbl_akaun_permohonan_tukar`
- `hronline.tbl_akaun_queue_tukar`
- `hronline.tbl_bank_main`

## Akaun Gaji Business Rules

- Staff request is created from Biodata Akaun flow into `hronline.tbl_akaun_permohonan_tukar`.
- Verification queue shows requests where `verify_status IS NULL`.
- Verification history shows requests where `verify_status IN (1, 2)`.
- Verification action:
  - `accept` sets `verify_status = 1`.
  - `reject` sets `verify_status = 2`.
  - always records `verify_status_by`, `verify_status_dt`, and `verify_remarks`.
- Approval queue shows requests where `verify_status = 1 AND approval_status IS NULL`.
- Approval history shows requests where `verify_status = 1 AND approval_status IN (1, 2)`.
- Approval action:
  - `approve` sets `approval_status = 1`.
  - `reject` sets `approval_status = 2`.
  - always records `approval_status_by`, `approval_status_dt`, and `approval_remarks`.
- Approved request creates a row in `hronline.tbl_akaun_queue_tukar`.
- Account activation date follows old `getFirstDayOfNextMonth()` behavior:
  - base date is first day of the month after `application_dt`;
  - if approval date is already later than that base date, use approval date + 1 day.
- Notification side effects from Yii2 are deferred until the Yii3 notification service standard is confirmed.

## Phase 1: Module Shell

- [x] Add routes:
  - [x] `GET /pengesahan-data-staf/index`
  - [x] `GET /pengesahan-data-staf/akaun-bank`
- [x] Add `src/Web/PengesahanDataStaf/IndexAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/index.php`.
- [x] Add `src/Shared/PengesahanDataStaf/PengesahanDataStafService.php`.
- [x] Render module tiles for:
  - [x] Akaun Bank
  - [x] Keahlian
  - [x] Keluarga
  - [x] Hubungan Keluarga
- [x] Keep non-focused modules as disabled or placeholder local cards until migrated.
- [x] Update `Pengurusan Sumber Manusia > Sumber Manusia > Pengesahan Data Staf` menu mapping to Yii3 route.

Completion:

- [x] `/pengesahan-data-staf/index` opens as a Yii3 page.
- [x] Akaun Bank tile links to `/pengesahan-data-staf/akaun-bank`.
- [x] Non-focused tiles do not accidentally write data or link to unfinished Yii3 pages.
- [x] `php -l` and `php yii list` pass.

## Phase 2: Akaun Bank Read Queues

- [x] Add `src/Shared/PengesahanDataStaf/AkaunBankVerificationService.php`.
- [x] Add queue query methods:
  - [x] `pendingVerification()`
  - [x] `verificationHistory()`
  - [x] `pendingApproval()`
  - [x] `approvalHistory()`
- [x] Join bank label from `hronline.tbl_bank_main`.
- [x] Join verifier/approver staff names where needed.
- [x] Add optional ICNO/name search filter matching old Yii2 `icno` filter intent.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/IndexAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/VerificationAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/ApprovalAction.php`.
- [x] Add templates:
  - [x] `index.php`
  - [x] `queue.php`
- [x] Use Yii3 table/page standards:
  - [x] main page card
  - [x] inner content cards
  - [x] Lucide icon action buttons
  - [x] consistent search/filter block
  - [x] bottom-right record counts

Target routes:

```text
GET /pengesahan-data-staf/akaun-bank
GET /pengesahan-data-staf/akaun-bank/verification
GET /pengesahan-data-staf/akaun-bank/approval
```

Completion:

- [x] Verification queue shows only `verify_status IS NULL`.
- [x] Verification history shows `verify_status IN (1, 2)`.
- [x] Approval queue shows `verify_status = 1 AND approval_status IS NULL`.
- [x] Approval history shows `verify_status = 1 AND approval_status IN (1, 2)`.
- [x] Status labels match the staff request status convention.
- [x] File attachments open through `MediaHostFileService`.
- [x] `php -l` and `php yii list` pass.
- [x] Unauthenticated route smoke redirects to login.

## Phase 3: Akaun Bank Detail Pages

- [x] Add detail service method for request by `id`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/VerifyAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/VerificationDetailAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/ApproveAction.php`.
- [x] Add `src/Web/PengesahanDataStaf/AkaunBank/ApprovalDetailAction.php`.
- [x] Add shared `detail.php` template with mode-specific actions.
- [x] Verification detail displays:
  - [x] no akaun
  - [x] bank
  - [x] nama pemilik
  - [x] alasan
  - [x] IC pemilik
  - [x] IC/KP kakitangan
  - [x] attachment
  - [x] catatan form
  - [x] Tolak / Sahkan buttons
- [x] Approval detail displays verification context:
  - [x] verifier
  - [x] verify date
  - [x] verify remarks
  - [x] catatan form
  - [x] Tolak / Terima buttons
- [x] History detail pages are read-only.

Target routes:

```text
GET,POST /pengesahan-data-staf/akaun-bank/verify?id=...
GET      /pengesahan-data-staf/akaun-bank/verify-detail?id=...
GET,POST /pengesahan-data-staf/akaun-bank/approve?id=...
GET      /pengesahan-data-staf/akaun-bank/approve-detail?id=...
```

Completion:

- [x] Pending verification rows open write detail.
- [x] Verification history rows open read-only detail.
- [x] Pending approval rows open write detail.
- [x] Approval history rows open read-only detail.
- [x] Invalid/missing `id` returns a safe not-found state or redirects to queue.
- [x] `php -l` and `php yii list` pass.

## Phase 4: Verification Write Action

- [x] Implement `verify()` command in `AkaunBankVerificationService`.
- [x] Require POST and CSRF.
- [x] Validate selected row still has `verify_status IS NULL` before writing.
- [x] Require non-empty remarks for both accept and reject.
- [x] On accept:
  - [x] set `verify_status = 1`
  - [x] set `verify_status_by = current identity`
  - [x] set `verify_status_dt = current date`
  - [x] set `verify_remarks`
- [x] On reject:
  - [x] set `verify_status = 2`
  - [x] set `verify_status_by = current identity`
  - [x] set `verify_status_dt = current date`
  - [x] set `verify_remarks`
- [x] Wrap write in transaction.
- [x] Redirect back to verification queue with a success/error signal.

Completion:

- [x] GET request does not mutate data.
- [ ] POST accept updates only pending verification rows.
- [ ] POST reject updates only pending verification rows.
- [x] Already-actioned rows cannot be re-verified accidentally.
- [x] `php -l` and `php yii list` pass.
- [ ] Browser validation test can be done without changing live data, or a safe test row is selected explicitly.

## Phase 5: Approval Write Action

- [x] Implement `approve()` command in `AkaunBankVerificationService`.
- [x] Require POST and CSRF.
- [x] Validate selected row has `verify_status = 1`.
- [x] Validate selected row has `approval_status IS NULL`.
- [x] Require non-empty remarks for both approve and reject.
- [x] On approve:
  - [x] set `approval_status = 1`
  - [x] set `approval_status_by = current identity`
  - [x] set `approval_status_dt = current date`
  - [x] set `approval_remarks`
  - [x] insert queue row into `hronline.tbl_akaun_queue_tukar`
  - [x] set `tarikh_kuatkuasa` using old next-effective-date rule
- [x] On reject:
  - [x] set `approval_status = 2`
  - [x] set `approval_status_by = current identity`
  - [x] set `approval_status_dt = current date`
  - [x] set `approval_remarks`
  - [x] do not insert queue row
- [x] Wrap application update and queue insert in one transaction.
- [x] Redirect back to approval queue with a success/error signal.

Completion:

- [x] GET request does not mutate data.
- [ ] POST approve updates request and inserts queue row atomically.
- [ ] POST reject updates request without queue insert.
- [x] Already-actioned rows cannot be re-approved accidentally.
- [x] Effective date calculation matches old Yii2 controller behavior.
- [x] `php -l` and `php yii list` pass.

## Phase 6: Menu Cutover And Verification

- [x] Update DB-backed or local menu mapping so Pengesahan Data Staf opens Yii3.
- [ ] Confirm `/menu` link under `Pengurusan Sumber Manusia > Sumber Manusia` opens `/pengesahan-data-staf/index`.
- [x] Confirm Akaun Bank tile opens `/pengesahan-data-staf/akaun-bank`.
- [x] Confirm all old Yii2 Akaun Bank routes have Yii3 equivalents or intentional fallbacks.
- [x] Record browser screenshots or smoke notes.

Completion:

- [x] Route/action/template syntax checks pass.
- [x] `php yii list` passes.
- [x] Unauthenticated HTTP smokes redirect to login.
- [x] Authenticated read-only browser check passes for index, Akaun Bank landing, verification queue, and approval queue.
- [ ] Write actions are only tested on safe rows with explicit confirmation.

Smoke notes:

- 2026-11-23: `php -l` passed for all new `src/Web/PengesahanDataStaf` files, `src/Shared/PengesahanDataStaf` services, `config/common/routes.php`, and `src/Shared/MenuService.php`.
- 2026-11-23: `php yii list` passed.
- 2026-11-23: unauthenticated `curl -I` requests for index, Akaun Bank landing, verification queue, and approval queue returned `302 Location: /hronline/login`.
- 2026-11-23: authenticated read-only `curl` smoke using a temporary Yii2-compatible session returned `200` for index, Akaun Bank landing, verification queue, and approval queue. No write action was executed.

## Next Deferred Item: Keahlian Verification Migration

Date planned: 2026-11-27

### Old Yii2 Source Baseline

Controller actions:

- `actionPdsKeahlian()`
- `actionPdsKeahlianIndividu($id)`

Views:

- `views/pengesahan-data-staf/keahlian/index.php`
- `views/pengesahan-data-staf/keahlian/individu_index.php`

Models/tables:

- `TblBadanProfesional`
- `TblBadanProfesionalSearch`
- `Tblprcobiodata`
- `hronline.tblprprofassoc`
- `hronline.tblprcobiodata`
- `hronline.professionalassociation`
- `hronline.membershiptype`
- `hronline.professional_association_level`

Existing Yii3 Biodata services to reuse where useful:

- `src/Shared/ProfessionalMembershipService.php`
- `src/Shared/Biodata/Sections/KeahlianService.php`
- `src/Shared/Biodata/Sections/KeahlianCrudService.php`

### Keahlian Business Rules

- Keahlian records live in `hronline.tblprprofassoc`.
- Unverified records are rows where `isVerified IS NULL OR isVerified != 1`.
- Main verification queue is staff-grouped by `ICNO`, matching old `groupBy(['ICNO'])`.
- Search options:
  - `jenis_carian = 1`: exact `ICNO`.
  - `jenis_carian = 2`: staff name search against `tblprcobiodata.CONm`.
- Main queue bulk action posts selected staff `ICNO` values.
- For each selected `ICNO`, all currently unverified Keahlian rows for that staff member are marked verified.
- Verification write sets:
  - `isVerified = 1`
  - `verify_date = current datetime`
  - `verify_icno = current identity`
- No old reject flow exists for Keahlian; migration should provide only `Sahkan`.
- No old remarks/catatan field is used by the Keahlian verification action.
- No old notification side effect exists for Keahlian verification.
- Staff detail screen shows staff profile context, then unverified Keahlian rows for that `ICNO`.
- Old individual screen appears row-selective but posts `ICNO`, so selected rows still verify all unverified rows for that staff member. Yii3 should make this behavior explicit, or intentionally correct it to post `profId` after confirming parity requirements.

## Phase 7: Keahlian Read Queue

- [x] Add routes:
  - [x] `GET /pengesahan-data-staf/keahlian`
  - [x] `GET /pengesahan-data-staf/keahlian/staf?icno=...`
- [x] Add `src/Shared/PengesahanDataStaf/KeahlianVerificationService.php`.
- [x] Add service query methods:
  - [x] `pendingStaffGroups()`
  - [x] `pendingForStaff()`
  - [x] `staffContext()`
- [x] Main queue query:
  - [x] select unverified `hronline.tblprprofassoc` rows
  - [x] group by `ICNO`
  - [x] join staff name from `hronline.tblprcobiodata`
  - [x] include unverified count per staff
  - [x] support exact ICNO search
  - [x] support staff name search
- [x] Staff detail query:
  - [x] select unverified rows for one `ICNO`
  - [x] join professional body label
  - [x] join membership type label
  - [x] join professional association level label where useful
  - [x] expose MediaHost attachment URL through existing file service logic where possible
- [x] Add web actions:
  - [x] `src/Web/PengesahanDataStaf/Keahlian/IndexAction.php`
  - [x] `src/Web/PengesahanDataStaf/Keahlian/StaffAction.php`
- [x] Add templates:
  - [x] `src/Web/PengesahanDataStaf/Keahlian/index.php`
  - [x] `src/Web/PengesahanDataStaf/Keahlian/staff.php`
- [x] Enable the Keahlian tile in `PengesahanDataStafService`.
- [x] Use existing Pengesahan Data Staf UI standards:
  - [x] main page card
  - [x] inner content cards
  - [x] Lucide icon action buttons
  - [x] consistent search/filter block
  - [x] bottom-right record counts
  - [x] stable checkbox layout for bulk verify

Target routes:

```text
GET /pengesahan-data-staf/keahlian
GET /pengesahan-data-staf/keahlian/staf?icno=...
```

Completion:

- [x] Keahlian tile links to `/pengesahan-data-staf/keahlian`.
- [x] Main queue shows only staff with unverified Keahlian rows.
- [x] Main queue groups rows by staff `ICNO`.
- [x] ICNO search matches the old exact ICNO behavior.
- [x] Name search matches the old staff name LIKE behavior.
- [x] Staff detail page shows only unverified Keahlian rows for that staff member.
- [x] Staff detail page includes staff context: ICNO, name, department, and service status where available.
- [x] Attachments open through the Yii3 MediaHost file URL service.
- [x] `php -l` and `php yii list` pass.
- [x] Unauthenticated route smoke redirects to login.

## Phase 8: Keahlian Verification Write Action

- [x] Add route:
  - [x] `POST /pengesahan-data-staf/keahlian/verify`
- [x] Add `src/Web/PengesahanDataStaf/Keahlian/VerifyAction.php`.
- [x] Implement `verifyStaffGroups()` in `KeahlianVerificationService`.
- [x] Require POST and CSRF.
- [x] Accept selected staff `ICNO` values from the main queue.
- [x] Validate every selected staff still has unverified Keahlian rows before writing.
- [x] Update only rows where `isVerified IS NULL OR isVerified != 1`.
- [x] Set `isVerified = 1`.
- [x] Set `verify_date = current datetime`.
- [x] Set `verify_icno = current identity`.
- [x] Wrap all selected staff updates in one transaction.
- [x] Redirect back to Keahlian queue with success/error signal.

Target route:

```text
POST /pengesahan-data-staf/keahlian/verify
```

Completion:

- [x] GET request does not mutate data.
- [x] POST with no selection returns a clear safe error.
- [x] POST verifies only currently unverified rows for selected staff.
- [x] Already verified rows are not rewritten.
- [x] Multi-staff selection writes atomically.
- [x] `php -l` and `php yii list` pass.
- [ ] Write action is only tested on safe rows with explicit confirmation.

## Phase 9: Keahlian Staff Detail Verification Decision

- [x] Decide whether Yii3 staff detail verification preserves old bulk-by-ICNO behavior or corrects it to row-by-`profId` behavior.
- [ ] If preserving old behavior:
  - [ ] staff detail form posts selected `ICNO`
  - [ ] UI copy/action wording makes it clear all pending Keahlian rows for the staff member will be verified
  - [ ] service reuses `verifyStaffGroups()`
- [x] If correcting to row-level behavior:
  - [x] staff detail form posts selected `profId` values
  - [x] add `verifyRowsForStaff()`
  - [x] validate each selected `profId` belongs to the requested `ICNO`
  - [x] update only selected unverified rows
- [x] Keep either path transaction-safe and CSRF-protected.

Completion:

- [x] The selected behavior is documented in this plan before implementation is marked complete.
- [x] Staff detail verification cannot update another staff member's rows.
- [x] Staff detail verification cannot rewrite already verified rows.
- [ ] Browser validation test uses safe rows only with explicit confirmation.

## Phase 10: Keahlian Cutover And Verification

- [x] Confirm old Yii2 route equivalents:
  - [x] old `pds-keahlian` maps to `/pengesahan-data-staf/keahlian`
  - [x] old `pds-keahlian-individu&id=...` maps to `/pengesahan-data-staf/keahlian/staf?icno=...`
- [x] Add intentional fallback routes if old links are still emitted by menu/search pages.
- [x] Confirm `/pengesahan-data-staf/index` Keahlian tile opens Yii3.
- [x] Confirm read-only browser flow:
  - [x] index
  - [x] Keahlian queue
  - [x] staff detail
- [x] Confirm unauthenticated HTTP smoke redirects to login.
- [x] Confirm authenticated read-only smoke returns `200`.
- [x] Record smoke notes below.

Completion:

- [x] Route/action/template syntax checks pass.
- [x] `php yii list` passes.
- [x] Keahlian queue browser check passes.
- [x] Keahlian staff detail browser check passes.
- [ ] Write actions are only tested on safe rows with explicit confirmation.

Smoke notes:

- 2026-11-27: `php -l` passed for new Keahlian service/action/template files, updated routes, and updated `PengesahanDataStafService`.
- 2026-11-27: `php yii list` passed.
- 2026-11-27: started Yii dev server on `127.0.0.1:8084` for route smokes.
- 2026-11-27: unauthenticated `curl -I` requests for `/pengesahan-data-staf/keahlian`, `/pengesahan-data-staf/keahlian/staf?icno=681114015611`, and `/pengesahan-data-staf/pds-keahlian` returned `302 Location: /hronlinev5/login`.
- 2026-11-27: authenticated read-only `curl` smoke using a temporary Yii2-compatible `hrv4` session returned `200` for `/pengesahan-data-staf/index`, `/pengesahan-data-staf/keahlian`, `/pengesahan-data-staf/keahlian?jenis_carian=1&carian_data=681114015611`, `/pengesahan-data-staf/keahlian/staf?icno=681114015611`, and `/pengesahan-data-staf/pds-keahlian`.
- 2026-11-27: authenticated `GET /pengesahan-data-staf/keahlian/verify` returned `405 Allow: POST`.
- 2026-11-27: headless Edge CDP browser smoke passed for `/pengesahan-data-staf/index`, `/pengesahan-data-staf/keahlian`, and `/pengesahan-data-staf/keahlian/staf?icno=681114015611`; confirmed enabled Keahlian link, queue rendering, staff detail rendering, and CSRF fields.
- 2026-11-27: no write POST was executed against live Keahlian data.

## Phase 11: Keluarga OKU Verification Migration

- [x] Add routes:
  - [x] `GET /pengesahan-data-staf/keluarga`
  - [x] `POST /pengesahan-data-staf/keluarga/verify`
  - [x] old `GET,POST /pengesahan-data-staf/pds-keluarga` alias
- [x] Add shared `src/Shared/PengesahanDataStaf/KeluargaVerificationService.php`.
- [x] Add web actions:
  - [x] `src/Web/PengesahanDataStaf/Keluarga/IndexAction.php`
  - [x] `src/Web/PengesahanDataStaf/Keluarga/VerifyAction.php`
- [x] Add shared `src/Web/PengesahanDataStaf/family-queue.php` template.
- [x] Queue reads `hronline.tblprfmy` where `FmyDisabilityStatus = 1` and `status_pengesahan IS NULL OR status_pengesahan = 0`.
- [x] Search supports exact staff/family IC and family-name LIKE.
- [x] Write action sets:
  - [x] `status_pengesahan = 1` for accept
  - [x] `status_pengesahan = 2` for reject
  - [x] `catatan = remarks`
- [x] Require POST, CSRF, selected rows, valid action, and non-empty remarks.
- [x] Keep updates transaction-safe and only update rows still pending.
- [x] Enable the Keluarga tile in `PengesahanDataStafService`.

Completion:

- [x] `php -l` passes for service/actions/template/routes.
- [x] `php yii list` passes.
- [x] Unauthenticated route smoke redirects to login.
- [x] Authenticated read-only route smoke returns `200`.
- [x] `GET /pengesahan-data-staf/keluarga/verify` returns `405 Allow: POST`.
- [ ] Write action is only tested on safe rows with explicit confirmation.

## Phase 12: Hubungan Keluarga Verification Migration

- [x] Add routes:
  - [x] `GET /pengesahan-data-staf/hubungan-keluarga`
  - [x] `POST /pengesahan-data-staf/hubungan-keluarga/verify`
  - [x] old `GET,POST /pengesahan-data-staf/pds-keluarga-hubungan` alias
- [x] Reuse `KeluargaVerificationService`.
- [x] Add web actions:
  - [x] `src/Web/PengesahanDataStaf/HubunganKeluarga/IndexAction.php`
  - [x] `src/Web/PengesahanDataStaf/HubunganKeluarga/VerifyAction.php`
- [x] Reuse shared `family-queue.php` template.
- [x] Queue reads `hronline.pdstblkeluarga` joined to `hronline.tblprfmy` where `verification_type = 1` and `verification_status != 1`.
- [x] Search supports exact staff/family IC and family-name LIKE.
- [x] Write action sets:
  - [x] `verification_status = 1` for accept
  - [x] `verification_status = 2` for reject
  - [x] `verification_remark = remarks`
- [x] Require POST, CSRF, selected rows, valid action, and non-empty remarks.
- [x] Keep updates transaction-safe and only update rows still not verified.
- [x] Enable the Hubungan Keluarga tile in `PengesahanDataStafService`.

Completion:

- [x] `php -l` passes for service/actions/template/routes.
- [x] `php yii list` passes.
- [x] Unauthenticated route smoke redirects to login.
- [x] Authenticated read-only route smoke returns `200`.
- [x] `GET /pengesahan-data-staf/hubungan-keluarga/verify` returns `405 Allow: POST`.
- [ ] Write action is only tested on safe rows with explicit confirmation.

Smoke notes:

- 2026-11-27: `php -l` passed for `KeluargaVerificationService`, Keluarga actions, Hubungan Keluarga actions, shared family queue template, updated routes, and updated `PengesahanDataStafService`.
- 2026-11-27: `php yii list` passed.
- 2026-11-27: unauthenticated `curl -I` requests for `/pengesahan-data-staf/keluarga` and `/pengesahan-data-staf/hubungan-keluarga` returned `302 Location: /hronlinev5/login`.
- 2026-11-27: authenticated read-only `curl` smoke using a temporary Yii2-compatible `hrv4` session returned `200` for `/pengesahan-data-staf/keluarga`, `/pengesahan-data-staf/hubungan-keluarga`, `/pengesahan-data-staf/pds-keluarga`, and `/pengesahan-data-staf/pds-keluarga-hubungan`.
- 2026-11-27: authenticated `GET` requests to `/pengesahan-data-staf/keluarga/verify` and `/pengesahan-data-staf/hubungan-keluarga/verify` returned `405 Allow: POST`.
- 2026-11-27: `/pengesahan-data-staf/index` renders live Keluarga and Hubungan Keluarga tile links.
- 2026-11-27: no write POST was executed against live family data.

## Phase 13: Role/Permission Parity Audit

- [x] Audited old Yii2 `PengesahanDataStafController::behaviors()` access rule.
- [x] Confirmed old controller uses `Yii::$app->AccessManager->Allowed(Yii::$app->controller->id)` for every action.
- [x] Confirmed old `AccessManager` checks:
  - [x] `system_core.roles.controller_id`
  - [x] `system_core.user_role`
  - [x] `system_core.role_perm`
  - [x] `system_core.permissions.perm_desc`
  - [x] explicit action permission or `*`
- [x] Corrected Yii3 `AuthorizationService::canAccessController()` table names to match legacy `system_core.roles` and `system_core.user_role`.
- [x] Added Yii3 action-level permission check via `AuthorizationService::canAccessControllerAction()`.
- [x] Added shared Pengesahan Data Staf authorization gate in `ActionSupport`.
- [x] Mapped Yii3 routes to legacy action permissions:
  - [x] `/pengesahan-data-staf/index` -> `index`
  - [x] `/pengesahan-data-staf/akaun-bank` -> `pds-akaun-bank`
  - [x] `/pengesahan-data-staf/akaun-bank/verification` -> `acc-bank-verification`
  - [x] `/pengesahan-data-staf/akaun-bank/verify` -> `acc-bank-verify`
  - [x] `/pengesahan-data-staf/akaun-bank/verify-detail` -> `acc-bank-verify-details`
  - [x] `/pengesahan-data-staf/akaun-bank/approval` -> `acc-bank-approval`
  - [x] `/pengesahan-data-staf/akaun-bank/approve` -> `acc-bank-approve`
  - [x] `/pengesahan-data-staf/akaun-bank/approve-detail` -> `acc-bank-approve-details`
  - [x] `/pengesahan-data-staf/keahlian` -> `pds-keahlian`
  - [x] `/pengesahan-data-staf/keahlian/staf` -> `pds-keahlian-individu`
  - [x] `/pengesahan-data-staf/keahlian/verify` -> `pds-keahlian` or `pds-keahlian-individu` by posted mode
  - [x] `/pengesahan-data-staf/keluarga` and verify -> `pds-keluarga`
  - [x] `/pengesahan-data-staf/hubungan-keluarga` and verify -> `pds-keluarga-hubungan`

Completion:

- [x] Unauthenticated access still redirects to login.
- [x] Keahlian-only role can open Keahlian queue.
- [x] Keahlian-only role is blocked from Keluarga queue.
- [x] Keluarga role can open Keluarga queue.
- [x] Keluarga role is blocked from Hubungan Keluarga because no non-admin legacy `pds-keluarga-hubungan` permission exists in `system_core.role_perm`.
- [x] Admin `*` role can open Hubungan Keluarga.
- [x] Akaun Bank role can open Akaun Bank landing, verification queue, and approval queue.
- [x] `php -l` and `php yii list` pass.
- [ ] Write actions are only tested on safe rows with explicit confirmation.

Smoke notes:

- 2026-11-27: `php -l` passed for `AuthorizationService`, `ActionSupport`, and all Pengesahan Data Staf action files touched by the role audit.
- 2026-11-27: `php yii list` passed.
- 2026-11-27: unauthenticated `GET /pengesahan-data-staf/index` returned `302 Location: /hronlinev5/login`.
- 2026-11-27: temporary Yii2-compatible session for Keahlian role user `760210125453` returned `200` for `/pengesahan-data-staf/index` and `/pengesahan-data-staf/keahlian`, and `403` for `/pengesahan-data-staf/keluarga`.
- 2026-11-27: temporary Yii2-compatible session for Keluarga role user `800505125358` returned `200` for `/pengesahan-data-staf/keluarga`, and `403` for `/pengesahan-data-staf/hubungan-keluarga`.
- 2026-11-27: temporary Yii2-compatible session for admin/Akaun Bank user `861216495116` returned `200` for `/pengesahan-data-staf/akaun-bank`, `/pengesahan-data-staf/akaun-bank/verification`, `/pengesahan-data-staf/akaun-bank/approval`, and `/pengesahan-data-staf/hubungan-keluarga`.
- 2026-11-27: no write POST was executed against live Pengesahan Data Staf data.

## Deferred After Akaun Gaji

- [x] Keahlian verification migration. Implemented in Phase 7 through Phase 10 above; live write validation remains pending safe-row confirmation.
- [x] Keluarga OKU verification migration. Implemented in Phase 11 above; live write validation remains pending safe-row confirmation.
- [x] Hubungan Keluarga verification migration. Implemented in Phase 12 above; live write validation remains pending safe-row confirmation.
- [ ] Notification service replacement for old `Yii::$app->MP->SendNotification()`.
- [x] Role/permission parity audit against old `AccessManager->Allowed()`. Implemented in Phase 13 above; live write validation remains pending safe-row confirmation.

