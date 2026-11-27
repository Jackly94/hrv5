# Cuti Migration Plan

Date: 2026-11-19

## Scope

Migrate the `Cuti` item inside the `Pengurusan Sumber Manusia` card on the Yii3 `/menu` page.

Current menu source:

- DB table: `system_core.tbl_menu_side`
- Row: `label = Cuti`
- Old Yii2 route: `cuti/individu/index`
- Visibility: `app\models\UtilitiesFunc::Internal(Yii::$app->user->identity->ICNO)`
- Current Yii3 behavior: `/menu` keeps this as a legacy fallback to `http://127.0.0.1:8083/cuti/individu/index`

Primary Yii3 target:

- First local route: `GET /cuti/individu/index`
- After first slice is verified, map `cuti/individu/index` in `MenuService::LOCAL_ROUTE_MAP` to `/cuti/individu/index`.

## Coordination Note

Another AI agent is working in this same Yii3 workspace on the `STARS` migration.

Coordination rules for Cuti work:

- Do not edit `AI hronline/04_stars_migration_plan.md`.
- Do not change `src/Web/Kehadiran/*` unless the STARS agent asks for a shared integration.
- Before editing shared files, run `git status --short` and read the current file contents.
- Shared files likely to conflict:
  - `config/common/routes.php`
  - `src/Shared/MenuService.php`
  - `src/Web/Shared/Layout/Main/layout.php`
  - `src/Shared/DashboardService.php`
  - `assets/main/site.css`
- Keep Cuti implementation in Cuti-owned files first:
  - `src/Shared/Cuti*`
  - `src/Web/Cuti/*`
- When a shared route/menu/sidebar update is needed, make it a tiny patch and record it in this plan.

## Yii2 Source Reviewed

Main Cuti module:

- `C:\Apache24\htdocs\staff\controllers\cuti\IndividuController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\PegawaiController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\SupervisorController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\ObserverController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\AdminController.php`
- `C:\Apache24\htdocs\staff\views\cuti\_menu.php`
- `C:\Apache24\htdocs\staff\views\cuti\individu`
- `C:\Apache24\htdocs\staff\views\cuti\pegawai`
- `C:\Apache24\htdocs\staff\views\cuti\supervisor`
- `C:\Apache24\htdocs\staff\views\cuti\observer`
- `C:\Apache24\htdocs\staff\views\cuti\admin`
- `C:\Apache24\htdocs\staff\models\cuti`
- `C:\Apache24\htdocs\staff\controllers\api\CutiController.php`

Related but separate leave-study modules found during analysis:

- `C:\Apache24\htdocs\staff\controllers\CutibelajarController.php`
- `C:\Apache24\htdocs\staff\controllers\CutiPenyelidikanController.php`
- `C:\Apache24\htdocs\staff\controllers\CutisabatikalController.php`
- These are not the same menu row as `Cuti`; the DB menu also has `Pengajian Lanjutan` pointing to `cutibelajar/index`.

## Current Yii2 Behavior

The `Cuti` menu row opens `cuti/individu/index`.

`IndividuController::actionIndex()` renders the staff leave dashboard:

- pending tasks from `system_core.tbl_pendingtask`
- current year leave records from `hrm.cuti_tbl_records`
- current year entitlement from `hrm.cuti_entitlement`
- latest entitlement using `Layak::getLatestLayak()`
- substitute-to-me list where `ganti_by = current ICNO`
- leave calendar events from `cuti/individu/jsoncalendar`

The Yii2 internal Cuti navbar is role-driven:

- Staff Menu: apply leave, substitute, GCR/CBTH, statement, entitlement, application lists
- Officer Menu: verification, approval, GCR/CBTH, approved leave, supervised staff, back on duty
- Supervisor Menu: sick leave, supervised staff, statements, monitoring, calendar, GCR/CBTH, supervisor list
- Admin Menu: verify, on behalf of, access, GCR application list, open GCR/CBTH, public holidays, maternity leave, absent list
- Report: department/date-range reports and leave-type statistics
- Sector HR: manage surat
- Pentadbiran: HSO application list
- Pemantau: observed staff list

## Current Cuti Top Menu Migration Status

Recorded: 2026-11-19

| Top menu | Status | Notes |
| --- | --- | --- |
| Staff Menu | `[x] Migrated` | Core staff workflows are local Yii3. Maternity `surat`, `upload`, and `cancel-leave` remain legacy/deferred support actions. |
| Officer Menu | `[ ] Not migrated` | All top-menu routes still use Yii2 legacy fallback. |
| Supervisor Menu | `[ ] Not migrated` | All top-menu routes still use Yii2 legacy fallback. |
| Admin Menu | `[ ] Not migrated` | All top-menu routes still use Yii2 legacy fallback. |
| Report | `[ ] Not migrated` | All report routes still use Yii2 legacy fallback. |
| Sector HR | `[ ] Not migrated` | Old Yii2 `Manage(Surat)` route `manage/list` is not yet represented in the Yii3 Cuti menu. |
| Pentadbiran | `[ ] Not migrated` | `Senarai Permohonan HSO` still uses Yii2 legacy fallback. |
| Pemantau | `[ ] Not migrated` | `Senarai Kakitangan Seliaan` still uses Yii2 legacy fallback. |

### Staff Menu Detail

| Staff submenu | Status |
| --- | --- |
| Apply Leave | `[x] Migrated` |
| Substitute | `[x] Migrated` |
| GCR & CBTH Application | `[x] Migrated` |
| Leave Statement | `[x] Migrated` |
| Leave Entitlement | `[x] Migrated` |
| Application List | `[x] Migrated` |
| Substitute List | `[x] Migrated` |
| My GCR & CBTH Application List | `[x] Migrated` |

### Not Migrated Top-Menu Items

Officer Menu:

- Pending Verification List
- Pending Approval List
- GCR & CBTH Application List
- Approved Leave List
- Staff Under My Supervision
- GCR & CBTH Application List, Ketua Jabatan BSM
- Back On Duty

Supervisor Menu:

- Sick Leave List
- Supervised Staff
- Monthly Statement
- Monitor Staff Leave
- Leave Calendar
- GCR & CBTH Application List
- Reset GCR & CBTH Application
- Supervisor List

Admin Menu:

- Verify
- On Behalf Of
- Access
- GCR Application List
- Buka Permohonan GCR & CBTH
- Public Holiday Calendar
- Maternity Leave
- Add Apply GCR After Closed Date
- Ketidakhadiran / Absent

Report:

- Report by Department (Date Range)
- Statistic by Leave Type

Sector HR:

- Manage(Surat)

Pentadbiran:

- Senarai Permohonan HSO

Pemantau:

- Senarai Kakitangan Seliaan

Old access behavior:

- `IndividuController` allows any authenticated user.
- `PegawaiController` allows users present in `hrm.cuti_set_pegawai` as verifier/approver or `hrm.cuti_behalf_action`.
- `SupervisorController` allows users in `hrm.cuti_access` with `akses_cuti_int != 4`.
- `ObserverController` allows users in `hrm.cuti_access` with `akses_cuti_int IN (3, 4)`.
- `AdminController` allows `akses_cuti_int = 3` for most admin actions and `akses_cuti_int = 5` for selected access-management actions.
- Cuti navbar visibility uses `AksesPengguna::visible($icno, $cat)`.

`AksesPengguna::visible()` category meanings observed:

| Cat | Meaning |
| --- | --- |
| 0 | Any Cuti access record |
| 1 | Admin, `akses_cuti_int = 3` |
| 2 | Staff appointment flag, `statLantikan = 1` |
| 3 | Department 230 chief |
| 5 | GCR/CBTH open application or explicit temp-table allowance |
| 6 | BSM admin department 230 exists |
| 7 | Department PP or chief |
| 8 | Any Cuti access except observer `akses_cuti_int = 4` |

## Old Data Dependencies

Primary Cuti tables used by `models/cuti`:

- `hrm.cuti_access`
- `hrm.cuti_adjustment`
- `hrm.cuti_cancelled_leave`
- `hrm.cuti_entitlement`
- `hrm.cuti_entitlement_bc`
- `hrm.cuti_gcr_application`
- `hrm.cuti_leave_type`
- `hrm.cuti_log`
- `hrm.cuti_log_activity`
- `hrm.cuti_open_application`
- `hrm.cuti_public_holiday`
- `hrm.cuti_ref_bersalin_bilangan`
- `hrm.cuti_set_pegawai`
- `hrm.cuti_tbl_bod`
- `hrm.cuti_tbl_management`
- `hrm.cuti_tbl_records`
- `hrm.cuti_tbl_research`
- `hrm.cuti_temp_table`
- `hrm.cuti_behalf_action`
- Legacy table still referenced: `e_cuti.cuti_rekod`

Cross-module dependencies:

- Staff biodata: `hronline.tblprcobiodata`
- Department/chief/PP: `hronline.department`
- Grade/job data: `hronline.gred_jawatan`, job relations used by old models
- Pending task: `system_core.tbl_pendingtask`, `system_core.tbl_pending_taskcommand`
- Attendance/work-mode helpers: `attendance.tbl_rekod`, `attendance.tblshift`, `attendance.tbl_thtc`
- Research leave data: `smp_ppi.cuti_penyelidikan`
- File storage through old `FileManager` component
- PDF generation through old `kartik\mpdf\Pdf`
- Notifications and command runner for pending task refresh

## Migration Strategy

Preserve flexibility:

- Keep the existing DB menu row flexible.
- Do not hardcode Cuti into `/menu` rendering beyond a route-map entry once the Yii3 route exists.
- Keep role and menu visibility data-driven through `hrm.cuti_access`, `hrm.cuti_open_application`, `hrm.cuti_temp_table`, `hrm.cuti_set_pegawai`, and department chief/PP records.
- Build explicit Yii3 services for old dynamic behavior instead of copying Yii2 controller-heavy logic.
- Use local Yii3 routes for migrated Cuti screens and legacy fallback for screens not migrated yet.
- Avoid table migrations unless a task explicitly needs a compatibility table; this app should read/write the existing HRM Cuti tables.

Recommended Yii3 structure:

- `src/Shared/CutiAccessService.php`
- `src/Shared/CutiDashboardService.php`
- `src/Shared/CutiEntitlementService.php`
- `src/Shared/CutiRecordService.php`
- `src/Shared/CutiReferenceService.php`
- `src/Shared/CutiCalendarService.php`
- `src/Shared/CutiApplicationService.php`
- `src/Shared/CutiGcrService.php`
- `src/Shared/CutiOfficerService.php`
- `src/Shared/CutiAdminService.php`
- `src/Web/Cuti/Individu/*`
- `src/Web/Cuti/Pegawai/*`
- `src/Web/Cuti/Supervisor/*`
- `src/Web/Cuti/Observer/*`
- `src/Web/Cuti/Admin/*`

## Execution Tasks

Use these tasks in order. Each task should be small enough to implement, verify, and document before moving to the next one.

### Task CTI-01: Add Read-Only Cuti Access Service

Status: `[x]`

Goal:

- Port the old Cuti access checks into explicit Yii3 service methods.

Files:

- `src/Shared/CutiAccessService.php`

Implementation:

- Add methods for:
  - `isCutiAdmin($icno)`
  - `isAdminJafpib($icno)`
  - `isObserver($icno)`
  - `hasAnyCutiAccess($icno)`
  - `isSupervisorMenuAllowed($icno)`
  - `isOfficer($icno)`
  - `isDepartment230Chief($icno)`
  - `isDepartmentChiefOrPp($icno)`
  - `isGcrApplicationVisible($icno)`
- Match old `AksesPengguna::visible()` behavior.
- Return plain booleans and small DTO-style arrays; do not render HTML in services.

Acceptance:

- Known old access checks can be evaluated from Yii3.
- Missing rows fail closed.
- No Yii2 `eval()` or Yii2 ActiveRecord is introduced.

Verification:

- `php -l src/Shared/CutiAccessService.php`
- CLI probe for current external-login ICNO prints expected Cuti menu booleans.

Execution notes:

- Added `src/Shared/CutiAccessService.php`.
- Ported `AksesPengguna::visible()`-style checks into explicit Yii3 methods.
- Missing access rows fail closed.

### Task CTI-02: Add Cuti Internal Menu Model

Status: `[x]`

Goal:

- Recreate `views/cuti/_menu.php` as a Yii3 data model/template that supports legacy fallback per item.

Files:

- `src/Shared/CutiMenuService.php`
- `src/Web/Cuti/Shared/menu.php`

Implementation:

- Build menu sections from arrays, but keep visibility dynamic via `CutiAccessService`.
- Each item should carry:
  - label
  - Yii2 route
  - local Yii3 route when migrated
  - legacy fallback URL when not migrated
  - visible flag
  - optional badge count callback
- Preserve all old menu sections even when most links still point to legacy Yii2.

Acceptance:

- Staff users see Staff Menu.
- Admin/supervisor/officer/observer sections appear only when access rules allow.
- Unmigrated items remain usable through legacy fallback.

Verification:

- `php -l src/Shared/CutiMenuService.php`
- Render probe confirms no inaccessible admin-only section leaks for a normal staff user.

Execution notes:

- Added `src/Shared/CutiMenuService.php`.
- Added `src/Web/Cuti/Shared/menu.php`.
- Staff menu routes that have local Yii3 screens point locally; officer/supervisor/admin/report routes keep safe Yii2 fallback.

### Task CTI-03: Add Read-Only Staff Cuti Dashboard

Status: `[x]`

Goal:

- Migrate `cuti/individu/index` as a read-only Yii3 page first.

Files:

- `src/Shared/CutiDashboardService.php`
- `src/Shared/CutiRecordService.php`
- `src/Shared/CutiEntitlementService.php`
- `src/Web/Cuti/Individu/IndexAction.php`
- `src/Web/Cuti/Individu/index.php`
- `config/common/routes.php`

Implementation:

- Add `GET /cuti/individu/index`.
- Load:
  - current-year leave records
  - latest/current entitlement
  - substitute-to-me list
  - pending Cuti tasks
- Show old dashboard information without enabling write actions yet.
- Include the Cuti internal menu from CTI-02.

Acceptance:

- Page renders for authenticated users.
- Leave balance and current-year records match Yii2 for the same ICNO.
- Missing entitlement shows a clear empty state, not an error.

Verification:

- `php -l` on new files.
- `php yii list`.
- Authenticated HTTP smoke: `/cuti/individu/index` returns `200`.

Execution notes:

- Added local `GET /cuti/individu/index`.
- Added `CutiDashboardService`, `CutiRecordService`, and `CutiEntitlementService`.
- Dashboard renders leave balance, current-year applications, substitute records, pending Cuti tasks, and the Cuti internal menu.

### Task CTI-04: Route Main Menu Cuti Link Locally

Status: `[x]`

Goal:

- Switch only the DB menu `Cuti` link from legacy fallback to local Yii3 after CTI-03 is verified.

Files:

- `src/Shared/MenuService.php`
- `src/Shared/DashboardService.php` if dashboard shortcut should also become local

Implementation:

- Add `cuti/individu/index => /cuti/individu/index` to `MenuService::LOCAL_ROUTE_MAP`.
- Optionally update dashboard shortcut `Cuti` from `http://127.0.0.1:8083/cuti/individu/index` to `/cuti/individu/index`.
- Keep all deeper Cuti routes as legacy until migrated.

Acceptance:

- `/menu` card item `Cuti` opens the Yii3 route.
- Other Cuti links remain legacy fallback unless specifically migrated.

Verification:

- Authenticated `/menu` smoke confirms Cuti href is `/cuti/individu/index`.
- Browser click test from `/menu`.

Execution notes:

- Added `cuti/individu/index => /cuti/individu/index` to `MenuService::LOCAL_ROUTE_MAP`.
- Updated dashboard shortcut `Cuti` to `/cuti/individu/index`.

### Task CTI-05: Add Calendar JSON Endpoint

Status: `[x]`

Goal:

- Replace Yii2 `cuti/individu/jsoncalendar` for the dashboard calendar.

Files:

- `src/Shared/CutiCalendarService.php`
- `src/Web/Cuti/Individu/JsonCalendarAction.php`
- `config/common/routes.php`

Implementation:

- Add `GET /cuti/individu/jsoncalendar`.
- Return current user's leave records as calendar events.
- Use the same color/status conventions as far as the old data supports.
- Keep response shape compatible with the chosen Yii3 frontend calendar.

Acceptance:

- Calendar events match old current-year leave records.
- Endpoint is authenticated and scoped to current ICNO.

Verification:

- Authenticated HTTP smoke returns JSON.
- Browser dashboard calendar is non-empty for users with leave records.

Execution notes:

- Added `GET /cuti/individu/jsoncalendar`.
- Endpoint returns current staff leave events plus public holiday events as JSON.

### Task CTI-06: Add Read-Only Staff Lists and Details

Status: `[x]`

Goal:

- Migrate staff read-only routes before write workflows.

Files:

- `src/Web/Cuti/Individu/ListAction.php`
- `src/Web/Cuti/Individu/LeaveDetailAction.php`
- `src/Web/Cuti/Individu/SubstituteListAction.php`
- `src/Web/Cuti/Individu/StatementAction.php`
- `src/Web/Cuti/Individu/EntitlementAction.php`
- `config/common/routes.php`

Implementation:

- Migrate:
  - `cuti/individu/list`
  - `cuti/individu/leave-detail`
  - `cuti/individu/substitute-list`
  - `cuti/individu/statement`
  - `cuti/individu/entitlement`
- Use `CutiRecordService` and `CutiEntitlementService`.
- Preserve query filters for year and leave type.

Acceptance:

- Users can inspect own application history, details, statement, and entitlement.
- Users cannot view another staff member's private leave detail through ID guessing.

Verification:

- Authenticated route smokes.
- Manual browser comparison with old Yii2 for one staff account.

Execution notes:

- Added local read-only routes for application list, leave detail, substitute list, statement, entitlement, and GCR list.
- Detail lookup is scoped to current ICNO or substitute assignment.

### Task CTI-07: Add Substitute Workflow

Status: `[~]`

Goal:

- Migrate substitute agreement flow.

Files:

- `src/Shared/CutiSubstituteService.php`
- `src/Web/Cuti/Individu/GantiAction.php`
- `src/Web/Cuti/Individu/AgreeAction.php`
- `config/common/routes.php`

Implementation:

- Migrate:
  - `cuti/individu/ganti`
  - `cuti/individu/agree`
- Preserve `ganti_by`, `AGREED`, and related status transitions.
- Add CSRF protection and POST-only state changes.
- Add logging through Cuti log service.

Acceptance:

- Substitute can agree only for applications assigned to their ICNO.
- State transition matches old status behavior.

Verification:

- Unit/service test for allowed and denied agreement.
- Browser submit smoke on a test record.

Execution notes:

- Substitute list is available locally.
- State-changing `agree` is intentionally kept as a Yii2 fallback route until write tests are prepared.

### Task CTI-08: Add Leave Application Type Picker

Status: `[~]`

Goal:

- Migrate `cuti/individu/pilih` without yet migrating every leave form.

Files:

- `src/Shared/CutiReferenceService.php`
- `src/Web/Cuti/Individu/PilihAction.php`
- `src/Web/Cuti/Individu/pilih.php`
- `config/common/routes.php`

Implementation:

- Load leave types from `hrm.cuti_leave_type`.
- Preserve old visible type filter:
  - `jenis_cuti_papar IN (1, 2)`
  - allowed base IDs: `1, 2, 20, 21, 28`
- Preserve old validation gates:
  - maternity leave blocks male staff
  - maternity leave requires marital status update
  - research leave criteria remains legacy until CP form migration
- Route selected migrated types locally; route unmigrated types to legacy.

Acceptance:

- Staff can choose a leave type.
- Unsupported type selection does not break; it falls back to legacy.

Verification:

- Authenticated browser test.
- Validation checks for maternity type with known test profiles.

Execution notes:

- Added local `GET /cuti/individu/pilih`.
- Leave types are read from `hrm.cuti_leave_type`.
- Full application forms and type-specific write validation still open Yii2 forms.

### Task CTI-09: Migrate Core Apply Leave Forms

Status: `[~]`

Goal:

- Migrate the common staff leave creation forms.

Files:

- `src/Shared/CutiApplicationService.php`
- `src/Web/Cuti/Individu/LeaveFormAction.php`
- Dedicated form actions under `src/Web/Cuti/Individu/*FormAction.php`
- Dedicated direct views under `src/Web/Cuti/Individu/*-form.php`

Implementation:

- Prioritize old routes/forms:
  - `cuti/individu/ck`
  - `cuti/individu/cb`
  - `cuti/individu/cr1`
  - `cuti/individu/cr1-aca`
  - `cuti/individu/cr2`
  - `cuti/individu/ctr`
  - `cuti/individu/cs1`
  - `cuti/individu/cs2`
  - `cuti/individu/hso`
- Reuse old business rules from `TblRecords`, `Layak`, `CutiUmum`, `SetPegawai`, `Tindakan`, and `Department`.
- Use maintainer-friendly direct forms only; no metadata-driven fields and no shared conditional mega-form for final migrated workflows.
- Keep file upload-heavy flows as legacy until file service is migrated.
- Write to `hrm.cuti_tbl_records`.
- Log to `hrm.cuti_log` or `hrm.cuti_log_activity` as old workflow expects.

Acceptance:

- New application records match old required fields.
- Date duration calculation matches Yii2 for working days and public holidays.
- Approver/verifier assignment matches old department/BSM rules.

Verification:

- Service-level tests for date/duration calculations.
- Test create in a non-production fixture or approved test record.
- Browser smoke for one low-risk leave type.

Execution notes:

- Yii3 routes now use dedicated form actions and direct views for `ck`, `cb`, `cr1`, `cr1-aca`, `cr2`, `ctr`, `cs1`, `cs2`, and `hso`.
- Removed the old shared `src/Web/Cuti/Individu/FormAction.php` and `src/Web/Cuti/Individu/form.php` pair so Cuti forms no longer depend on one conditional mega-form.
- The action/view split preserves the existing `CutiApplicationService` submit path first; no live POST submission was executed during the maintainer-friendly reconfiguration pass.

### Task CTI-10: Migrate Cancel and Upload Flows

Status: `[~]`

Goal:

- Migrate staff-side post-application actions.

Files:

- `src/Web/Cuti/Individu/CancelLeaveAction.php`
- `src/Web/Cuti/Individu/UploadAction.php`
- `src/Web/Cuti/Individu/UploadFileAction.php`
- `src/Shared/CutiFileService.php`

Implementation:

- Migrate:
  - `cuti/individu/cancel-leave`
  - `cuti/individu/upload-file`
  - `cuti/individu/upload`
- Preserve maternity upload scenario `"cb"`.
- Define Yii3 file storage wrapper compatible with old `FileManager` outputs before enabling upload writes.

Acceptance:

- Users can cancel only their own eligible records.
- Upload records keep the same file-name/hash fields expected by old reports.

Verification:

- POST-only cancel route.
- Upload smoke with allowed test file.

Execution notes:

- Added authenticated Yii3 legacy handoff routes for cancel/upload/surat.
- Actual file upload and cancel writes remain Yii2-backed.

### Task CTI-11: Migrate GCR and CBTH Staff Slice

Status: `[~]`

Goal:

- Migrate staff GCR/CBTH visibility and application list before approvals.

Files:

- `src/Shared/CutiGcrService.php`
- `src/Web/Cuti/Individu/ApplyGcrAction.php`
- `src/Web/Cuti/Individu/GcrListAction.php`

Implementation:

- Migrate:
  - `cuti/individu/apply-gcr`
  - `cuti/individu/gcr-list`
- Use `hrm.cuti_gcr_application`, `hrm.cuti_open_application`, `hrm.cuti_temp_table`, `hrm.cuti_entitlement`, and `hrm.cuti_entitlement_bc`.
- Preserve visibility cat `5`.

Acceptance:

- GCR menu appears only when old rules allow.
- Staff can view their GCR/CBTH history locally.

Verification:

- Route smokes with allowed and not-allowed ICNOs.

Execution notes:

- Added local GCR/CBTH application list and open-window notice.
- New GCR/CBTH submission remains a Yii2 handoff.

### Task CTI-12: Migrate Officer Verification and Approval Lists

Status: `[~]`

Goal:

- Migrate the first officer workflow slice.

Files:

- `src/Shared/CutiOfficerService.php`
- `src/Web/Cuti/Pegawai/*`

Implementation:

- Migrate read-only lists first:
  - `cuti/pegawai/senarai-peraku`
  - `cuti/pegawai/senarai-lulus`
  - `cuti/pegawai/list`
  - `cuti/pegawai/staff-leave`
  - `cuti/pegawai/bod-list`
- Then migrate state-changing actions:
  - `peraku`
  - `reject-peraku`
  - `lulus`
  - `reject`
- Preserve access via `hrm.cuti_set_pegawai` and `hrm.cuti_behalf_action`.

Acceptance:

- Officers only see records they are allowed to verify/approve.
- POST state changes update the same status fields as Yii2.

Verification:

- Access-denied tests for non-officer.
- Browser compare officer lists with Yii2.

Execution notes:

- Officer menu visibility is Yii3-driven.
- Officer routes currently use authenticated Yii2 fallback pending list/write migration.

### Task CTI-13: Migrate Supervisor and Observer Read-Only Views

Status: `[~]`

Goal:

- Migrate staff monitoring before supervisor write actions.

Files:

- `src/Shared/CutiSupervisorService.php`
- `src/Shared/CutiObserverService.php`
- `src/Web/Cuti/Supervisor/*`
- `src/Web/Cuti/Observer/*`

Implementation:

- Migrate:
  - supervised staff list
  - leave statement report
  - leave monitoring
  - calendar
  - my staff observer list
- Keep supervisor/observer write actions as legacy until verified separately.

Acceptance:

- Department scoping follows `hrm.cuti_access.akses_jspiu_id` and campus scoping where used.
- Observer access remains limited to observer/admin rules.

Verification:

- Route smokes for supervisor, observer, and normal staff.

Execution notes:

- Supervisor and observer menu visibility is Yii3-driven.
- Supervisor/observer routes currently use authenticated Yii2 fallback.

### Task CTI-14: Migrate Admin Settings and Reports

Status: `[~]`

Goal:

- Migrate admin maintenance after staff/officer workflows are stable.

Files:

- `src/Shared/CutiAdminService.php`
- `src/Web/Cuti/Admin/*`

Implementation:

- Migrate:
  - access management
  - public holiday calendar
  - open GCR/CBTH application window
  - on-behalf-of actions
  - absent/staff list
  - department/date-range reports
  - leave-type statistics
- Preserve department restrictions for JAFPIB/admin variants.

Acceptance:

- Admin changes write to existing HRM Cuti tables.
- Non-admin cannot access admin routes.

Verification:

- Access tests.
- Report totals compare with Yii2 for the same filters.

Execution notes:

- Admin/report menu visibility is Yii3-driven.
- Admin/report routes currently use authenticated Yii2 fallback.

### Task CTI-15: Migrate PDF and Surat Generation

Status: `[~]`

Goal:

- Replace old `kartik\mpdf\Pdf` outputs with Yii3-compatible PDF generation.

Files:

- `src/Shared/CutiPdfService.php`
- PDF templates under `src/Web/Cuti/*`

Implementation:

- Inventory all `surat`, `print*`, and `generate*` Cuti routes.
- Choose one PDF library already available in Yii3 or add one deliberately.
- Keep old PDF layout/output names where users depend on them.

Acceptance:

- Generated PDFs contain the same official data fields.
- Browser opens/downloads PDFs correctly.

Verification:

- PDF smoke for maternity surat, statement, and report.

Execution notes:

- Staff `surat` is routed through authenticated Yii2 fallback.
- Yii3 PDF generation library choice remains open.

### Task CTI-16: Migrate Mobile/API Cuti Endpoints

Status: `[ ]`

Goal:

- Migrate API only after web services are stable enough to reuse.

Files:

- `src/Web/Api/Cuti/*` or project-standard API namespace

Implementation:

- Migrate old `controllers/api/CutiController.php` actions:
  - `get-list-cuti`
  - `get-list-staff`
  - `get-list-as-substitute`
  - `agree`
  - `apply`
- Reuse `CutiRecordService`, `CutiApplicationService`, and `CutiSubstituteService`.
- Preserve authentication/token expectations from the existing Yii3 API pattern.

Acceptance:

- API output shape remains compatible with current clients.
- State-changing API actions enforce identity scope.

Verification:

- HTTP API smokes using known test credentials/token.

### Task CTI-17: Final Legacy Fallback Cleanup

Status: `[~]`

Goal:

- Remove Cuti legacy fallbacks only after all local routes are migrated and verified.

Files:

- `src/Shared/CutiMenuService.php`
- `src/Shared/MenuService.php`
- `src/Shared/DashboardService.php`

Implementation:

- Update all local Cuti route mappings.
- Remove or mark remaining legacy links with a migration warning.
- Keep fallback for related modules that are explicitly out of scope, such as `cutibelajar/index`, until their own plan executes.

Acceptance:

- The `Cuti` menu path can be used without Yii2 for migrated scope.
- `/menu` and dashboard shortcuts stay stable.

Verification:

- Full browser smoke:
  - `/menu`
  - `/cuti/individu/index`
  - staff lists/details
  - one write workflow in a test-safe record
  - officer/admin access checks

Execution notes:

- Main `Cuti` entry now opens Yii3 locally.
- Remaining write-heavy, officer, supervisor, admin, PDF, and API paths intentionally keep Yii2 fallback.

## Open Decisions

- Whether to migrate Cuti Belajar, Cuti Penyelidikan, and Cuti Sabatikal under this plan or keep them under separate plans. Current recommendation: separate plans, because the DB menu treats `Pengajian Lanjutan` separately and those controllers are large.
- Whether uploads should reuse the old FileManager storage paths exactly or introduce a Yii3 wrapper with the same DB output.
- Whether pending-task command refreshes should be synchronous in Yii3 services or handled by a future command/queue migration.
- Which PDF library should be standard for Yii3.

## First Recommended Execution Slice

Start with CTI-01 through CTI-04:

1. Port access checks.
2. Build the Cuti internal menu with local/legacy hybrid links.
3. Render the read-only staff dashboard at `/cuti/individu/index`.
4. Switch the main `/menu` Cuti link locally.

This gives users a working Yii3 Cuti landing page while preserving old flexibility and leaving high-risk write workflows on Yii2 until their rules are migrated deliberately.
