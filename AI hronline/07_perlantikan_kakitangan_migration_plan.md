# Perlantikan Kakitangan Migration Plan

Date: 2026-11-19

## Scope

Migrate the `Perlantikan Kakitangan` submenu under the `Pengurusan Sumber Manusia` card into this Yii3 app.

Current menu source:

- DB category: `system_core.tbl_menu_category.id = 1`, `name = Pengurusan Sumber Manusia`, `icon = users`
- Parent row: `system_core.tbl_menu_side.id = 8`, `label = Sumber Manusia`
- Child row: `system_core.tbl_menu_side.id = 44`, `label = Perlantikan Kakitangan`
- Old Yii2 route: `lantikan/index`
- Current Yii3 behavior after the first execution slice: `/menu` maps `lantikan/index` to local `/lantikan/index`.

Primary Yii3 target:

- First local route: `GET /lantikan/index`
- After the first slice is verified, map `lantikan/index` in `MenuService::LOCAL_ROUTE_MAP` to `/lantikan/index`.

## Coordination Note

Two other agents are working in the same Yii3 workspace on STARS and Cuti.

Coordination rules for Lantikan work:

- Do not edit `src/Shared/AttendanceService.php` or `src/Web/Kehadiran/*` in the first slice.
- Do not edit `src/Shared/Cuti*` or `src/Web/Cuti/*` in the first slice.
- Do not reorganize `config/common/routes.php`; add only a small `/lantikan/*` route block when needed.
- Do not broaden `assets/main/site.css`. Reuse existing page/card/table/button classes first; if custom CSS is needed, use narrowly scoped `.lantikan-*` selectors.
- Shared files likely to conflict:
  - `config/common/routes.php`
  - `src/Shared/MenuService.php`
  - `assets/main/site.css`
- Lantikan-owned files should be new:
  - `src/Shared/LantikanService.php`
  - `src/Shared/LantikanReferenceService.php`
  - `src/Web/Lantikan/*`

Important data-risk note:

- Old Lantikan writes `hronline.tblprcobiodata.statLantikan` and creates `attendance.tbl_wp` rows.
- Cuti access currently depends on staff appointment status.
- STARS owns working-pattern behavior around `attendance.tbl_wp`.
- Therefore, keep the first Yii3 migration read-only or legacy-handoff for writes.

## Yii2 Source Reviewed

Main old module:

- `C:\Apache24\htdocs\staff\controllers\LantikanController.php`
- `C:\Apache24\htdocs\staff\views\lantikan`
- `C:\Apache24\htdocs\staff\models\hronline\Tblprcobiodata.php`
- `C:\Apache24\htdocs\staff\models\hronline\TblprcobiodataSearch.php`
- `C:\Apache24\htdocs\staff\models\hronline\Senarailantikan.php`

Key old Yii2 actions:

- `actionIndex()` - renders `views/lantikan/index.php`.
- `actionIndex1()` - renders the fuller admin dashboard `index_admin.php`.
- `actionLantikStafBaru()` - new staff appointment.
- `actionLantikPhs()` - PHS / contract department appointment.
- `actionLantikKhas()` - special appointment.
- `actionLantikSpdp()` - SPDP appointment.
- `actionLssuIndex()` - former staff search for reappointment.
- `actionLantikSemulaStafUms($id)` - reappoint existing/former UMS staff.
- `actionLantikanLpu()`, `actionLantikPli()`, `actionLantikSambilanakademik()`, `actionPenerbitums()`, `actionLantikanEjobs()` - additional appointment flows.
- `actionLantikanBelumSelesai()`, `actionViewUtama()`, `actionViewLantikan()` - incomplete/verification flow.
- `actionTkpg()`, `actionLpg()`, `actionTklpg()` - salary profile and LPG records.
- `actionRollbackLantikan($icno, $pw)` - legacy rollback helper. Do not migrate this into Yii3 without a separate security review.

## Current Yii2 Behavior

`lantikan/index` is the menu landing page for recruitment/appointment management.

The current `views/lantikan/index.php` tiles are:

| Tile | Route | Status |
| --- | --- | --- |
| Staf Baru | `lantik-staf-baru` | Active |
| PHS | `lantik-phs` | Active |
| Khas | `lantik-khas` | Active |
| Staf UMS | `lssu-index` | Active |
| Sambilan Akademik | `#` | Disabled on this page |
| Lantikan LPU | `#` | Disabled on this page |
| Lantikan SPDP | `lantik-spdp` | Active |

`views/lantikan/index_admin.php` has a larger BSM/admin dashboard with active or historically available tiles for Staf Baru, PHS, Khas, Staf UMS, Lantikan LPU, Lantikan SPDP, and a hard-coded EJOBS tile for selected ICNOs.

Observed access behavior:

- The DB menu row already limits visibility to `system_core.tbl_user_access.access = 1`, `accessLevel = 1`, selected `accessLevel/accessSecondLevel` combinations, and selected JFPIU access.
- `LantikanController::behaviors()` uses `AccessControl`, but its `only` list mostly names biodata/admin actions and does not consistently include the write-heavy Lantikan actions.
- Yii3 should not copy that controller-level gap. New Yii3 actions need explicit authorization through a dedicated service.

## Main Appointment Write Flow

The new-staff, PHS, Khas, and SPDP flows share the same broad shape:

1. Render a large appointment form based on `Tblprcobiodata` scenarios: `baru`, `phs`, `khas`, `spdp`.
2. Support a `Jana Email` submit path through `emailSuggestion()`.
3. Validate official email through `validateEmail()` and old Active Directory email existence checks.
4. Uppercase staff name and set appointment/status defaults.
5. Generate UMSPER:
   - `hronline.umsper` for normal appointments.
   - `hronline.umsperpks` with `(S)` suffix for PHS/Khas/PKS-like appointments.
6. Save `hronline.tblprcobiodata`.
7. Insert dependent appointment records.
8. Insert optional cross-module rows:
   - `attendance.tbl_wp` when working-pattern defaults are needed.
   - `keselamatan.tbl_staff_keselamatan` for security grades.
   - `hrm.myhealth_tblmaxtuntutan` for medical claim balance.
   - `system_core.tbl_notifications` for IT/admin notifications.
9. Trigger external side effects:
   - `Yii::$app->ActiveDirectory->Add($icno)`.
   - `IdpMata::addStaff($icno)` in at least the Staf Baru flow.
10. Redirect to old allocation profile or biodata admin screens.

The legacy code does not wrap the whole operation in one transaction. Yii3 should.

## Active Directory API Analysis

Legacy `Yii::$app->ActiveDirectory` is an HTTP API wrapper, not direct LDAP integration.

Component:

- `C:\Apache24\htdocs\staff\components\ActiveDirectory.php`
- Uses `GuzzleHttp\Client`
- Uses hard-coded API credentials in the component. Yii3 must move these into environment/config and the existing keys should be treated as exposed legacy secrets.

Observed methods:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `Login($username, $password)` | `POST /api/v2/basic/ad/login` | AD login fallback for old login screens |
| `AdEmailExist($param, $type)` | `POST /api/v1/ad/find` | Check whether AD userid/email exists |
| `Add($icno)` | `POST /api/v1/ad/createUser` | Create AD user from `hronline.tblprcobiodata` |
| `Update($userId, $icno)` | `POST /api/v1/ad/updateUser/{userId}` | Sync HR biodata into existing AD user |

Create-user payload assembled from `Tblprcobiodata`:

- `ic_no` = staff ICNO
- `email` = `COEmail`
- `name` = `CONm`
- `dept_code` = department shortname
- `department` = department fullname
- `staff_no` = `COOldID`
- `office_phone_no` = `COOffTelNo`
- `handphone_no` = `COHPhoneNo`
- `title` = gelaran title
- `ad_userid` = email local-part before `@`
- `city` = campus name
- `is_active` = true
- plus API credential fields

Update-user payload is similar but does not send `ad_userid`; the target user id is in the URL.

Find-user payload:

- `userid`
- `type`, usually `EMAIL` in Lantikan validation
- plus API credential fields

Legacy behavior in Lantikan:

- Staf Baru calls `ActiveDirectory->Add($model->ICNO)` after DB inserts, then `IdpMata::addStaff($model->ICNO)`.
- SPDP, PHS, Khas, LPU, PLI, and other flows also call `ActiveDirectory->Add(...)` after successful DB writes.
- Failure only sets a flash message in most flows; it does not roll back appointment records.
- `validateEmail()` uses `AdEmailExist($email, 'email')` to reject already-used official email addresses.

Migration risks:

- The old component catches selected HTTP error responses but can return `null` when there is a network failure without a response.
- External API calls are not transactional with HR database writes.
- AD create is not idempotency-safe in the old code unless the API itself handles duplicates.
- Old debug actions expose direct AD test/create/update behavior and should not be migrated as public Yii3 routes.
- API credentials must not be hard-coded in Yii3.

Yii3 recommendation:

- Split AD into its own `ActiveDirectoryApiClient`.
- Use it first only for `find` / email validation.
- Queue or run `createUser` only after the Lantikan DB transaction commits.
- Store failed create/update attempts in an admin-visible table or notification flow.
- Keep AD create disabled in local/test unless explicitly configured.

## Data Dependencies

Primary staff and appointment tables:

- `hronline.tblprcobiodata`
- `hronline.umsper`
- `hronline.umsperpks`
- `hronline.tblrscosandangan`
- `hronline.tblrscoapmtstatus`
- `hronline.tblrscoservstatus`
- `hronline.tblpreduach`
- `hronline.tblpenempatan`
- `hronline.tblrscoaptathy`
- `hronline.tblrscoservload`
- `hronline.tblrscoconfirmstatus`
- `hronline.tblrscoprobtnperiod`
- `hronline.tblrscopsnstatus`
- `hronline.tblrscopsnathy`
- `hronline.tblrscosaltype`
- `hronline.tblrscoservtype`
- `hronline.tblrscosalmovemth`
- `hronline.tblrscofileno`
- `hronline.tbl_lantikan_belum_selesai`

Secondary/cross-module tables:

- `attendance.tbl_wp`
- `keselamatan.tbl_staff_keselamatan`
- `hrm.myhealth_tblmaxtuntutan`
- `system_core.tbl_notifications`
- `hronline.tbl_staff_salary`
- `hrm.gaji_tblrscolpg`
- `hronline.senarailantikan`

Reference data used by the forms includes:

- titles, religions, races, ethnic groups, blood types, genders, marital statuses
- countries, states, citizenship statuses
- education levels
- appointment statuses
- job grades
- sandangan statuses
- appointment types
- departments
- campuses
- teaching programs
- pay-pattern references

## Yii3 Migration Strategy

Use a staged migration. The old module mutates too many shared tables to port in one step.

Recommended Yii3 structure:

- `src/Shared/LantikanService.php`
  - dashboard card data
  - access checks
  - legacy handoff URLs
- `src/Shared/LantikanReferenceService.php`
  - option lists for forms
  - email suggestion helper
- `src/Shared/LantikanAppointmentService.php`
  - later write transaction for new appointments
  - no STARS/Cuti writes until coordinated
- `src/Shared/LantikanReappointmentService.php`
  - later Staf UMS search/reappointment logic
- `src/Web/Lantikan/IndexAction.php`
- `src/Web/Lantikan/template.php`
- Later: `src/Web/Lantikan/FormAction.php`, `src/Web/Lantikan/form.php`

Keep non-migrated write actions as legacy links until each write path has a reviewed Yii3 service.

## Task Board

Run one task at a time. Mark the box only after implementation and verification are complete.

Quick status matrix:

- See `AI hronline/09_lantikan_type_migration_status.md` for the current migrated/partial/legacy status of each Lantikan type and workflow.

- [x] `LANTIKAN-01` - Local read-only landing page.
- [x] `LANTIKAN-02` - Authorization service.
- [x] `LANTIKAN-03` - Reference data and form blueprint.
- [x] `LANTIKAN-04` - Menu cutover from Yii2 fallback to Yii3.
- [x] `LANTIKAN-05` - Staf UMS search, read-only.
- [x] `LANTIKAN-06` - New staff transaction design.
- [ ] `LANTIKAN-07` - STARS/Cuti side-effect coordination checkpoint.
- [ ] `LANTIKAN-08` - Enable Staf Baru Yii3 submit.
- [ ] `LANTIKAN-09` - Port SPDP appointment flow.
- [ ] `LANTIKAN-10` - Port PHS appointment flow.
- [ ] `LANTIKAN-11` - Port Khas appointment flow.
- [ ] `LANTIKAN-12` - Port Staf UMS reappointment write flow.
- [x] `LANTIKAN-13` - Incomplete appointment verification pages.
- [ ] `LANTIKAN-14` - Salary profile and LPG workflow.
- [ ] `LANTIKAN-15` - Remaining optional appointment tiles.
- [ ] `LANTIKAN-16` - Active Directory API adapter and post-commit workflow.

Execution status on 2026-11-19:

- Implemented local Lantikan routes, service, reference metadata, read-only search, incomplete verification, dry-run appointment forms, and Yii2 handoff routes.
- Added `ActiveDirectoryApiClient` with disabled-by-default `find`, `createUser`, and `updateUser` methods using environment credentials only. Live AD creation is not enabled because no Yii3 appointment write transaction is enabled yet.
- Verified syntax for new/changed Lantikan files and route/menu files.
- Verified `/hronline/lantikan/index` redirects unauthenticated users to `/hronline/login`.
- Verified Lantikan DB references, Staf UMS search, incomplete list query, dashboard data, form blueprint, and dry-run validation through read-only CLI smoke tests.
- Did not touch STARS-owned `src/Shared/AttendanceService.php` / `src/Web/Kehadiran/*` or Cuti-owned `src/Shared/Cuti*` / `src/Web/Cuti/*`.

## Execution Tasks

### [x] LANTIKAN-01: Local Read-Only Landing Page

Goal:

- Create a Yii3 `/lantikan/index` page without changing the DB menu cutover yet.

Files to add/change:

- Add `src/Shared/LantikanService.php`.
- Add `src/Web/Lantikan/IndexAction.php`.
- Add `src/Web/Lantikan/template.php`.
- Add `GET /lantikan/index` in `config/common/routes.php`.

Implementation notes:

- Render the old tile labels from `views/lantikan/index.php`.
- Keep active write-heavy tiles as legacy links:
  - `lantikan/lantik-staf-baru`
  - `lantikan/lantik-phs`
  - `lantikan/lantik-khas`
  - `lantikan/lssu-index`
  - `lantikan/lantik-spdp`
- Keep disabled old tiles visibly disabled or linked to legacy `#` equivalent.
- Reuse existing page classes. Avoid CSS changes.

Acceptance checks:

- `/lantikan/index` renders for an authenticated authorized user.
- No `src/Web/Kehadiran/*`, `src/Shared/AttendanceService.php`, `src/Web/Cuti/*`, or `src/Shared/Cuti*` files are touched.
- `php vendor/bin/php-cs-fixer fix --dry-run` or the repo's equivalent formatter check passes if available.

### [x] LANTIKAN-02: Authorization Service

Goal:

- Enforce direct-route access for local `/lantikan/*` pages.

Files to add/change:

- Update `src/Shared/LantikanService.php` or add `src/Shared/LantikanAccessService.php`.
- Update `src/Web/Lantikan/IndexAction.php`.

Rules to implement:

- Allow `system_core.tbl_user_access.access = 1`.
- Allow `accessLevel = 1`.
- Allow `accessLevel = 2` with `accessSecondLevel IN (1, 3)`.
- Allow `accessLevel = 3` with `accessSecondLevel IN (8, 9)`.

Acceptance checks:

- Authorized HR users can open `/lantikan/index`.
- Non-authorized authenticated users are redirected or receive the same denial pattern used by nearby Yii3 pages.
- Access does not rely only on `/menu` visibility.

### [x] LANTIKAN-03: Reference Data and Form Blueprint

Goal:

- Prepare form metadata without enabling Yii3 writes.

Files to add/change:

- Add `src/Shared/LantikanReferenceService.php`.
- Add form config methods/classes under `src/Shared/Lantikan*`.
- Optional: add a read-only preview route only if useful.

Implementation notes:

- Build option queries for titles, religions, races, ethnic groups, blood types, genders, marital statuses, countries, states, citizenship statuses, education levels, appointment statuses, job grades, sandangan statuses, appointment types, departments, campuses, and teaching programs.
- Define form field configs for Staf Baru, PHS, Khas, and SPDP.
- Port `emailSuggestion()` only as a pure helper. Do not call Active Directory.

Acceptance checks:

- Reference queries return non-empty option lists in the local database.
- No appointment data is inserted or updated.
- No Active Directory or IdP side effect is called.

### [x] LANTIKAN-04: Menu Cutover From Yii2 Fallback To Yii3

Goal:

- Make `/menu > Pengurusan Sumber Manusia > Sumber Manusia > Perlantikan Kakitangan` open Yii3.

Files to change:

- `src/Shared/MenuService.php`

Implementation notes:

- Add only this route map entry:
  - `lantikan/index` => `/lantikan/index`
- Do not modify menu rendering or DB rows.

Acceptance checks:

- DB row `system_core.tbl_menu_side.id = 44` can remain `url = lantikan/index`.
- `/menu` now links the submenu to `/lantikan/index`.
- Other Sumber Manusia submenu items still use their existing local or Yii2 fallback behavior.

### [x] LANTIKAN-05: Staf UMS Search Read-Only

Goal:

- Port `lssu-index` search as read-only.

Files to add/change:

- Add route `GET /lantikan/lssu-index`.
- Add action/template under `src/Web/Lantikan/*`.
- Add query method to `LantikanService` or a dedicated search service.

Source behavior:

- Old source: `TblprcobiodataSearch::carianLantikan()`.
- Base filter: `hronline.tblprcobiodata.Status = '06'`.
- Filters: ICNO/name/UMSPER, department, appointment status, staff status, education, and job category.
- For `accessLevel = 3` with second-level 8/9, limit by current user's department.

Acceptance checks:

- Search renders results without mutating data.
- Row actions link to legacy `lantikan/lantik-semula-staf-ums&id=...` until the write flow is migrated.

### [x] LANTIKAN-06: New Staff Transaction Design

Goal:

- Design the Yii3 service for `actionLantikStafBaru()` without enabling production submit.

Files to add/change:

- Add `src/Shared/LantikanAppointmentService.php`.
- Add tests or a dry-run method if the project test pattern allows it.

Implementation notes:

- The service must use one DB transaction for `hronline.*` inserts.
- Do not use race-prone "last UMSPER + 1" without locking.
- Defer external side effects until after commit:
  - Active Directory
  - IdP Mata
  - notifications
  - allocation profile redirect
  - medical/security/attendance inserts
- Document rollback or compensation for each post-commit side effect.

Acceptance checks:

- Service can validate and produce a proposed insert plan/dry run.
- No live submit route is enabled yet.
- Risk review notes are recorded in this plan or `AI hronline/progress.md`.

### [ ] LANTIKAN-07: STARS/Cuti Side-Effect Coordination Checkpoint

Goal:

- Confirm cross-module write boundaries before enabling any Yii3 appointment submission.

Coordination required:

- STARS agent confirms how Yii3 Lantikan should create or avoid `attendance.tbl_wp`.
- Cuti agent confirms expected effects of new `statLantikan` values on Cuti access and leave eligibility.

Acceptance checks:

- Agreement is recorded in this plan.
- Any shared tables requiring deferred writes are listed.
- No Yii3 submit route writes `attendance.tbl_wp` before this is complete.

### [ ] LANTIKAN-08: Enable Staf Baru Yii3 Submit

Goal:

- Turn the Staf Baru form from blueprint/dry-run into an actual Yii3 create flow.
- Detailed task plan: `AI hronline/08_lantikan_staf_baru_migration_plan.md`.

Files to add/change:

- Add route for `GET|POST /lantikan/lantik-staf-baru`.
- Add form action/template under `src/Web/Lantikan/*`.
- Update `LantikanAppointmentService`.

Acceptance checks:

- Validation errors render without partial inserts.
- Successful submission inserts the same core `hronline.*` records as Yii2.
- Failure rolls back all transaction-scoped inserts.
- Side effects happen only after commit or remain legacy/manual.

### [ ] LANTIKAN-09: Port SPDP Appointment Flow

Goal:

- Port `actionLantikSpdp()` after Staf Baru is proven.

Acceptance checks:

- SPDP-specific defaults and required fields match Yii2 scenario `spdp`.
- Submission uses the shared transaction service.
- Legacy SPDP route remains available until Yii3 flow is verified.

### [ ] LANTIKAN-10: Port PHS Appointment Flow

Goal:

- Port `actionLantikPhs()`.

Acceptance checks:

- Uses `hronline.umsperpks`.
- Generates `(S)` UMSPER correctly.
- Handles PHS-specific `attendance.tbl_wp` behavior only after `LANTIKAN-07`.

### [ ] LANTIKAN-11: Port Khas Appointment Flow

Goal:

- Port `actionLantikKhas()` and clean duplicated legacy inserts.

Acceptance checks:

- No duplicate `tblrscoapmtstatus`, `tblrscoservstatus`, or `tblrscofileno` rows are created accidentally.
- Uses `hronline.umsperpks` and `(S)` UMSPER where required.
- Transaction behavior matches the approved Staf Baru service pattern.

### [ ] LANTIKAN-12: Port Staf UMS Reappointment Write Flow

Goal:

- Port `actionLantikSemulaStafUms($id)`.

Acceptance checks:

- Existing staff row updates are audited and transactional.
- New UMSPER/UMSPERPKS logic matches old staff status rules.
- Conditional history inserts match Yii2 behavior.
- Redirect goes to the correct migrated or legacy staff detail page.

### [x] LANTIKAN-13: Incomplete Appointment Verification Pages

Goal:

- Port read-only verification screens.

Routes:

- `GET /lantikan/lantikan-belum-selesai`
- `GET /lantikan/view-utama`
- `GET /lantikan/view-lantikan`

Acceptance checks:

- `hronline.tbl_lantikan_belum_selesai` list renders.
- Table-completion verification mirrors old `verify.php`.
- No repair/rollback action is exposed in Yii3.

### [ ] LANTIKAN-14: Salary Profile and LPG Workflow

Goal:

- Port salary profile pages separately from appointment creation.

Legacy actions:

- `actionTkpg()`
- `actionLpg()`
- `actionTklpg()`

Tables:

- `hronline.tbl_staff_salary`
- `hrm.gaji_tblrscolpg`

Acceptance checks:

- Salary/LPG writes are authorized separately.
- Any old debug `var_dump()`/`die` behavior is removed.
- Writes are transactional and audited where possible.

### [ ] LANTIKAN-15: Remaining Optional Appointment Tiles

Goal:

- Decide and port remaining tiles only if still active business workflows.

Candidates:

- LPU
- PLI
- Sambilan Akademik
- Penerbit UMS
- PPPI
- EJOBS

Acceptance checks:

- Business owner confirms each tile should exist in Yii3.
- Disabled legacy tiles are not accidentally enabled.
- Hard-coded ICNO exceptions are replaced with explicit authorization rules.

### [ ] LANTIKAN-16: Active Directory API Adapter and Post-Commit Workflow

Goal:

- Replace direct Yii2 `Yii::$app->ActiveDirectory` calls with a Yii3-safe adapter.

Current execution status:

- Adapter scaffold implemented in `src/Shared/ActiveDirectoryApiClient.php`.
- Local/test behavior is disabled by default unless `HRONLINE_AD_ENABLE=1`, `HRONLINE_AD_PUBLIC_KEY`, and `HRONLINE_AD_SECRET_KEY` are present.
- Post-commit create/update workflow is still pending because Yii3 appointment writes are intentionally not enabled yet.
- Admin-visible failed AD creation tracking is still pending.

Legacy source:

- `C:\Apache24\htdocs\staff\components\ActiveDirectory.php`
- Wired as `ActiveDirectory` in both `C:\Apache24\htdocs\staff\config\web.php` and `console.php`.
- Called by `LantikanController` after appointment inserts.
- Also used by `BiodataController::actionSyncToAd()` and `actionRecreateAd()`.

API endpoints observed:

- `POST https://api.ums.edu.my/api/v1/ad/find`
- `POST https://api.ums.edu.my/api/v1/ad/createUser`
- `POST https://api.ums.edu.my/api/v1/ad/updateUser/{userId}`
- `POST https://api.ums.edu.my/api/v2/basic/ad/login`

Implementation notes:

- Store API credentials in environment/config, not source code.
- Do not port the old debug routes:
  - `lantikan/test-ad`
  - `lantikan/create-ad`
  - `lantikan/update-ad`
- Create a Yii3 service such as `ActiveDirectoryApiClient`.
- Add clear response DTOs:
  - `success`
  - `message`
  - `errors`
  - raw response for audit/debug only when safe.
- Add timeout and network-error handling for cases where the API returns no HTTP response.
- Call `createUser` only after the staff appointment DB transaction commits.
- Decide whether failed AD create should:
  - leave the appointment completed with a visible warning/task, or
  - trigger a manual compensation workflow.

Acceptance checks:

- Email existence check can call the `find` endpoint without creating/updating AD users.
- Create/update calls are disabled in tests and local dry-run mode unless explicitly enabled.
- API credentials are not committed.
- Failed AD creation is recorded and visible to admin users.
- Yii3 appointment transaction never partially commits because an external AD API call timed out after DB writes.

## First Safe Implementation Patch

Smallest useful first patch:

- Add `src/Shared/LantikanService.php`.
- Add `src/Web/Lantikan/IndexAction.php`.
- Add `src/Web/Lantikan/template.php`.
- Add `GET /lantikan/index` in `config/common/routes.php`.
- Add `lantikan/index => /lantikan/index` in `src/Shared/MenuService.php`.

Expected behavior:

- `/menu` card `Pengurusan Sumber Manusia > Sumber Manusia > Perlantikan Kakitangan` opens Yii3 `/lantikan/index`.
- Yii3 `/lantikan/index` shows the appointment dashboard.
- Write-heavy tiles remain legacy handoffs until their service migrations are explicitly implemented.

## Verification Plan

- Query menu DB row and confirm `Perlantikan Kakitangan` still points to `lantikan/index`.
- Visit `/lantikan/index` with no legacy session; confirm redirect/login behavior is consistent with other Yii3 pages.
- Visit as an authorized HR user; confirm tile labels match old `views/lantikan/index.php`.
- Confirm non-authorized users cannot open the page directly.
- Confirm legacy links still open old Yii2 for write flows.
- Run `git status --short` before and after shared-file patches.

## Open Questions

- Confirm whether the business label should remain `Perlantikan Kakitangan` or be corrected to `Pelantikan Kakitangan`.
- Confirm whether the Yii3 first screen should match `index.php` or the fuller `index_admin.php` for BSM users.
- Confirm which disabled old tiles are still expected: Sambilan Akademik, LPU on normal index, Penerbit UMS, PPPI, PLI.
- Confirm whether Yii3 can call Active Directory and IdP Mata services directly, or whether those should remain Yii2-only.
- Confirm who owns the allocation profile route before replacing the legacy redirect.
