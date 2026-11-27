# Lantikan SPDP Yii3 Migration Plan

Date: 2026-11-28

## Scope

Migrate `lantikan/lantik-spdp` / Lantikan Staf Skim SPDP UMS into Yii3 using the maintainer-friendly migration standard:

- dedicated action
- dedicated direct form view
- no metadata-style final form
- dry-run only until live transaction, post-commit, rollback, and UAT checks are approved

## Yii2 Source

- `C:\Apache24\htdocs\staff\controllers\LantikanController.php`
- `actionLantikSpdp()`
- `C:\Apache24\htdocs\staff\views\lantikan\_lantikspdp.php`
- `Tblprcobiodata` scenario `spdp`

## Legacy Behavior Summary

- Uses `hronline.umsper`, not `hronline.umsperpks`.
- Generated `COOldID` format is `ymd-#####`.
- `COOUCTelNo = '1' . COOldIDNo`.
- `statLantikan` options are restricted to `2` and `3`.
- `gredJawatan` and `gredJawatan_hakiki` options are restricted to `hronline.gredjawatan.scheme_type = 'SPDP'`.
- `ServTypeCd = 'SPDP'`.
- No `KodProgram` field.
- Core appointment history tables follow the Staf Baru pattern, except probation period is not expected for SPDP status `2`/`3`.

## Side Effects

Keep these disabled until owner/UAT approval:

- AD create user after commit.
- IT/admin notification after commit.
- Allocation profile handoff after commit.
- `attendance.tbl_wp` when `ApmtTypeCd = 2`.
- `keselamatan.tbl_staff_keselamatan` when `ApmtTypeCd = 2` and grade is KP below 41.

## Yii3 Progress

- Added `src/Shared/LantikanSpdpService.php` for SPDP-specific dry-run validation and insert-plan preview.
- Added SPDP-specific reference filters:
  - `appointmentStatusesSpdp` for status `2`/`3`.
  - `jobGradesSpdp` for `scheme_type = 'SPDP'`.
- Added direct maintainer-friendly form:
  - `src/Web/Lantikan/SpdpFormAction.php`
  - `src/Web/Lantikan/spdp-form.php`
- `/lantikan/lantik-spdp` now opens the local Yii3 SPDP dry-run page.
- `/lantikan/index` SPDP tile now opens local Yii3 `/lantikan/lantik-spdp`.
- Live SPDP submit remains disabled; Yii2 fallback remains available from the form.

## Open Tasks

- [ ] Add SPDP feature flag for live submit.
- [ ] Add SPDP transaction service with advisory-lock protected `hronline.umsper` reservation.
- [ ] Add SPDP reconciliation page/status.
- [ ] Add post-commit outcome recording for AD create, notifications, and allocation profile handoff.
- [ ] Confirm STARS/security side-effect ownership before enabling side-effect writes.
- [ ] Run approved dummy/UAT write, rollback, and concurrency checks before cutover.
