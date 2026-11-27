# Lantikan Khas Yii3 Migration Plan

Date: 2026-11-28

## Scope

Migrate `lantikan/lantik-khas` into Yii3 using the maintainer-friendly Lantikan standard: dedicated action, dedicated direct view, named service rules, dry-run first, and no metadata-style form rendering.

## Legacy Sources

- `C:\Apache24\htdocs\staff\controllers\LantikanController.php`
  - `actionLantikKhas()`
  - `ValidateEmail()`
- `C:\Apache24\htdocs\staff\views\lantikan\_lantikkhas.php`
- `C:\Apache24\htdocs\staff\models\hronline\Tblprcobiodata.php`
  - scenario `khas`
- `C:\Apache24\htdocs\staff\models\hronline\Umsperpks.php`

## Yii2 Behavior Snapshot

- UMSPER source is `hronline.umsperpks`, with generated `COOldID` format `ymd-#####(S)`.
- Form does not include Program Pengajaran / `KodProgram`.
- Form does not include `gredJawatan_hakiki`; legacy derives the hakiki grade from selected `gredJawatan`.
- `statLantikan` is limited to appointment status `2`.
- `gredJawatan` is limited to `hronline.gredjawatan.isKhas = 1`.
- `ConfirmStatusCd = 8`, `PsnStatusCd = 04`, `PsnAthyCd = 99`.
- `ServTypeCd = SSM`.
- Legacy duplicates selected history inserts; Yii3 live submit must not copy duplicate insert behavior.

## Core Transaction Candidate

Khas live submit should insert these 15 unique core tables in one transaction:

- `hronline.tblprcobiodata`
- `hronline.umsperpks`
- `hronline.tblrscosandangan`
- `hronline.tblrscoapmtstatus`
- `hronline.tblrscoservstatus`
- `hronline.tblpenempatan`
- `hronline.tblrscoaptathy`
- `hronline.tblrscoservload`
- `hronline.tblrscoconfirmstatus`
- `hronline.tblrscopsnstatus`
- `hronline.tblrscopsnathy`
- `hronline.tblrscosaltype`
- `hronline.tblrscoservtype`
- `hronline.tblrscosalmovemth`
- `hronline.tblrscofileno`

## Side Effects To Keep Out Of Core Transaction

- AD create user.
- IT/admin notifications.
- Access-management email request follow-up.
- Allocation profile handoff.
- `attendance.tbl_wp` when `ApmtTypeCd = 2`.
- `keselamatan.tbl_staff_keselamatan` for configured security grades when `ApmtTypeCd = 2`.
- `hrm.myhealth_tblmaxtuntutan` when `ApmtTypeCd = 2` and status is not PHS-like.

## Yii3 Progress

- Added `LantikanKhasService` dry-run/source-of-truth plan.
- Yii3 Khas options restrict `statLantikan` to status `2`.
- Yii3 Khas options restrict `gredJawatan` to `hronline.gredjawatan.isKhas = 1`.
- `/lantikan/index` now opens local Yii3 `/lantikan/lantik-khas` from the Khas tile.
- `/lantikan/lantik-khas` now uses dedicated `src/Web/Lantikan/KhasFormAction.php` and direct `src/Web/Lantikan/khas-form.php`.
- Removed the generic Lantikan `FormAction.php` and `form.php`.
- Live Khas submit remains disabled; `LantikanService::submitAppointment()` still returns the fallback error for non-Staf-Baru flows.

## Open Tasks

- [ ] Add feature flag for Khas live submit.
- [ ] Add `umsperpks` advisory lock shared safely with PHS/Khas.
- [ ] Implement Khas transaction service with duplicate legacy insert risks removed.
- [ ] Add Khas reconciliation page/status.
- [ ] Decide durable handling for AD create, notifications, access-management follow-up, and allocation handoff.
- [ ] Get STARS, security, and medical owner approval before enabling cross-module side effects.
- [ ] Run forced rollback, duplicate prevention, and approved dummy/UAT submit.
