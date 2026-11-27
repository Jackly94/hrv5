# Lantikan PHS Yii3 Migration Plan

Date: 2026-11-28

## Scope

Migrate `lantikan/lantik-phs` / Pekerja Sambilan Harian into Yii3 using the Staf Baru transaction pattern, but keep live writes disabled until source mapping, rollback, and UAT are complete.

## Legacy Sources

- `C:\Apache24\htdocs\staff\controllers\LantikanController.php`
  - `actionLantikPhs()`
  - `ValidateEmail()`
- `C:\Apache24\htdocs\staff\views\lantikan\_lantikphs.php`
- `C:\Apache24\htdocs\staff\models\hronline\Tblprcobiodata.php`
  - scenario `phs`
  - `afterSave()` hidden side effects for lantikan actions
- `C:\Apache24\htdocs\staff\models\hronline\Umsperpks.php`

## Yii2 Behavior Snapshot

PHS shares most biodata and appointment-history inserts with Staf Baru, with these differences:

- UMSPER source is `hronline.umsperpks`, not `hronline.umsper`.
- Generated `COOldID` format is `ymd-#####(S)`.
- `COOUCTelNo` is `1` plus `COOldIDNo`.
- Form does not include Program Pengajaran / `KodProgram`.
- Form does not include `gredJawatan_hakiki`; legacy sets hakiki grade to selected `gredJawatan`.
- `statLantikan` is limited to `6` and `7`.
- Derived values for PHS statuses:
  - `ConfirmStatusCd = 8`
  - `PsnStatusCd = 04`
  - `PsnAthyCd = 99`
- `hronline.tblrscoprobtnperiod` is not expected for PHS because `statLantikan` is limited to `6` and `7`.

## Core Transaction Candidate

PHS live submit should insert these 16 core tables in one transaction:

- `hronline.tblprcobiodata`
- `hronline.umsperpks`
- `hronline.tblrscosandangan`
- `hronline.tblrscoapmtstatus`
- `hronline.tblrscoservstatus`
- `hronline.tblpreduach`
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
- IT/admin notifications to hard-coded legacy admins.
- Access-management email request follow-up:
  - `hronline.tbl_pap_senaraistaf`
  - notifications to active `hronline.tbl_pap_akses` users with `pap_ja_id = 4`
- `Yii::$app->IICS->SyncStaff($icno)`.
- Allocation profile handoff.
- `attendance.tbl_wp` when `ApmtTypeCd = 2`:
  - `wp_id = 40`
  - `remark = PELAJAR LATIHAN INDUSTRI`
  - `start_date = date('Y-m-d')`
  - `status = APPROVED`

## Yii3 Progress

- Added `LantikanPhsService` dry-run/source-of-truth plan.
- Yii3 PHS field options now restrict `statLantikan` to status `6` and `7`.
- Yii3 PHS field blueprint no longer shows `gredJawatan_hakiki`.
- `/lantikan/index` now opens the local Yii3 PHS dry-run/scaffold from the PHS tile.
- `/lantikan/lantik-phs` now uses dedicated `src/Web/Lantikan/PhsFormAction.php` and direct `src/Web/Lantikan/phs-form.php` files instead of the generic Lantikan `FormAction`/`form.php` pair.
- `phs-form.php` uses explicit field calls for PHS and no longer builds the form from `$form['fields']`, `$fieldsByName`, section metadata, or `$renderField()`.
- Live PHS submit remains disabled; `LantikanService::submitAppointment()` still returns the fallback error for non-Staf-Baru flows.

## Open Tasks

- [ ] Add feature flag for PHS live submit, separate from Staf Baru.
- [ ] Add `umsperpks` advisory lock, separate from `hronline.umsper.reserve`.
- [ ] Implement PHS transaction service.
- [ ] Add PHS reconciliation page/status.
- [ ] Decide durable handling for AD create, notifications, access-management follow-up, IICS sync, and allocation handoff.
- [ ] Get STARS owner approval before enabling `attendance.tbl_wp`.
- [ ] Run forced rollback, duplicate prevention, and approved dummy/UAT submit.
