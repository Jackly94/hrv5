# Lantikan Type Migration Status

Date: 2026-11-19

Purpose: quick continuation record for the Lantikan module. Use this to decide the next Yii3 migration task without rereading the full old Yii2 audit.

## Current Redirect Baseline

- Global Yii2 fallback base is `https://registrar.ums.edu.my/staff/web`.
- Local Yii3 routes remain local when explicitly mapped or linked.
- Non-migrated Lantikan routes should hand off to live Yii2 through `LegacyUrl`.

## Status Definitions

- `Migrated`: user-facing path opens Yii3 by default.
- `Partial`: Yii3 page/service exists, but live production write flow is not enabled.
- `Legacy`: user-facing path still opens live Yii2.
- `Disabled`: visible tile exists but is not clickable/enabled in the Yii3 Lantikan dashboard.

## Lantikan Types And Workflows

| Workflow / Type | Old Yii2 route | Current Yii3 route/status | Migration status | Notes / next task |
| --- | --- | --- | --- | --- |
| Lantikan dashboard | `lantikan/index` | `/lantikan/index` | Migrated | Local Yii3 landing page is the default from menu. |
| Staf Baru | `lantikan/lantik-staf-baru` | `/lantikan/lantik-staf-baru` plus `/lantikan/lantik-staf-baru/result` | Partial | Yii3 form, validation, AD email find check, insert-plan dry-run, feature-flagged transaction service, UMSPER advisory lock implementation, and reconciliation page exist. Live submit remains off by default and still needs AD-enabled dummy/UAT write verification before cutover. |
| PHS | `lantikan/lantik-phs` | User-facing tile opens live Yii2. Local generic dry-run route exists if opened directly. | Legacy / Partial scaffold | Needs full Yii2 source mapping and transaction service after Staf Baru pattern is proven. Main task: `LANTIKAN-10`. |
| Khas | `lantikan/lantik-khas` | User-facing tile opens live Yii2. Local generic dry-run route exists if opened directly. | Legacy / Partial scaffold | Needs full Yii2 source mapping and transaction service. Main task: `LANTIKAN-11`. |
| SPDP | `lantikan/lantik-spdp` | User-facing tile opens live Yii2. Local generic dry-run route exists if opened directly. | Legacy / Partial scaffold | Needs SPDP-specific defaults/fields and transaction service. Main task: `LANTIKAN-09`. |
| Staf UMS search | `lantikan/lssu-index` | `/lantikan/lssu-index` | Migrated read-only | Search/list is local Yii3. The actual reappointment write still links to live Yii2. |
| Staf UMS reappointment write | `lantikan/lantik-semula-staf-ums?id=...` | Live Yii2 link from search rows | Legacy | Main task: `LANTIKAN-12`. |
| Lantikan Belum Selesai | `lantikan/lantikan-belum-selesai` | `/lantikan/lantikan-belum-selesai` | Migrated read-only | Local Yii3 list reads `hronline.tbl_lantikan_belum_selesai`. |
| Verification: View Utama | `lantikan/view-utama` | `/lantikan/view-utama` | Migrated read-only | Local Yii3 table-completion verification. No repair/rollback exposed. |
| Verification: View Lantikan | `lantikan/view-lantikan` | `/lantikan/view-lantikan` | Migrated read-only | Local Yii3 table-completion verification. No repair/rollback exposed. |
| Salary profile TKPG | `lantikan/tkpg` | Live Yii2 redirect | Legacy | Salary/LPG workflow task: `LANTIKAN-14`. |
| Salary profile LPG | `lantikan/lpg` | Live Yii2 redirect | Legacy | Salary/LPG workflow task: `LANTIKAN-14`. |
| Salary profile TKLPG | `lantikan/tklpg` | Live Yii2 redirect | Legacy | Salary/LPG workflow task: `LANTIKAN-14`. |
| Sambilan Akademik | `lantikan/lantik-sambilanakademik` | Tile disabled; route redirects to live Yii2 if opened directly | Disabled / Legacy | Confirm whether business still wants this tile. Main task: `LANTIKAN-15`. |
| Lantikan LPU | `lantikan/lantikan-lpu` | Tile disabled; route redirects to live Yii2 if opened directly | Disabled / Legacy | Confirm whether business still wants this tile. Main task: `LANTIKAN-15`. |
| PLI | `lantikan/lantik-pli` | Live Yii2 redirect | Legacy | Not shown as active tile in current Yii3 dashboard. Main task: `LANTIKAN-15`. |
| Penerbit UMS | `lantikan/penerbitums` | Live Yii2 redirect | Legacy | Not shown as active tile in current Yii3 dashboard. Main task: `LANTIKAN-15`. |
| EJOBS | `lantikan/lantikan-ejobs` | Live Yii2 redirect | Legacy | Hard-coded/exception access in old Yii2 must be reviewed before migration. Main task: `LANTIKAN-15`. |
| Rollback Lantikan | `lantikan/rollback-lantikan` | Not exposed in Yii3 | Not migrated intentionally | Do not migrate without separate security review. |
| AD email validation | AD `find` API | `ActiveDirectoryApiClient::find()` used by Staf Baru validation/dry-run when enabled | Partial | Create/update is not enabled for live appointment flow. |
| AD create user | AD `createUser` API | Adapter method exists, no post-commit production workflow yet | Not enabled | Continue `LANTIKAN-16` and `STAFBARU-08`. |

## Recommended Next Order

- Detailed continuation plan: `AI hronline/10_lantikan_pending_migration_execution_plan.md`.
- Open blocker register: `AI hronline/10_lantikan_pending_migration_execution_plan.md#blocker-register`.
- [ ] Verify Staf Baru live core transaction with approved dummy/UAT data: `STAFBARU-06`.
- [ ] Verify UMSPER advisory lock under live/concurrency test: `STAFBARU-07`.
- [ ] Add AD create post-commit handling: `STAFBARU-08`.
- [ ] Resolve IdP and cross-module side effects: `STAFBARU-09` and `STAFBARU-10`.
- [ ] Enable Staf Baru submit behind feature flag: `STAFBARU-11`.
- [ ] After Staf Baru UAT, port SPDP, PHS, and Khas using the same transaction pattern.

## Guardrails

- Do not enable writes to `attendance.tbl_wp`, `keselamatan.tbl_staff_keselamatan`, or `hrm.myhealth_tblmaxtuntutan` until the owner modules agree.
- Do not call AD create/update inside an open DB transaction.
- Keep Yii2 fallback links visible until the matching Yii3 live submit path has passed UAT.
