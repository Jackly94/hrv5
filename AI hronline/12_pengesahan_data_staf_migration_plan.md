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

## Deferred After Akaun Gaji

- [ ] Keahlian verification migration.
- [ ] Keluarga OKU verification migration.
- [ ] Hubungan Keluarga verification migration.
- [ ] Notification service replacement for old `Yii::$app->MP->SendNotification()`.
- [ ] Role/permission parity audit against old `AccessManager->Allowed()`.

