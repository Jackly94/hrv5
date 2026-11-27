# STARS Side Menu Migration Plan

Date: 2026-11-19

## Scope

Migrate the fixed side-menu item `STARS` from the old Yii2 staff app into this Yii3 app.

Current Yii3 side-menu target:

- `src/Web/Shared/Layout/Main/layout.php`
- `STARS` now links to Yii3 `/kehadiran/index` after `STARS-04`

Old Yii2 source:

- `C:\Apache24\htdocs\staff\views\layouts\side_menu.php`
- `STARS` maps to `kehadiran/index`
- Main web controller: `C:\Apache24\htdocs\staff\controllers\KehadiranController.php`
- Admin CRUD controller: `C:\Apache24\htdocs\staff\controllers\KehadiranAdminController.php`
- Mobile/API controller: `C:\Apache24\htdocs\staff\controllers\api\KehadiranController.php`
- Views: `C:\Apache24\htdocs\staff\views\kehadiran`
- Models: `C:\Apache24\htdocs\staff\models\kehadiran`

## Coordination Note

There are other agents working on migrations in this same Yii3 workspace, but in other modules. STARS work should stay inside the attendance boundary unless a task explicitly says otherwise.

Coordination rules:

- Before editing shared files, check the current file state and preserve unrelated changes.
- Shared files likely to conflict:
  - `config/common/routes.php`
  - `src/Web/Shared/Layout/Main/layout.php`
  - `assets/main/site.css`
  - `src/Shared/MenuService.php`
- Avoid broad refactors in shared services while another module migration is active.
- If a STARS task needs a shared helper, keep the change narrowly scoped and record it in this file.
- Run `git status --short` before and after each task so unrelated work by other agents is visible.

## Key Findings

`STARS` is not the DB-backed main `Menu` page. It is a direct module handoff to `kehadiran/index`.

The old web clock-in/clock-out code in `KehadiranController` is currently commented out for UMS Go usage. The visible landing page still renders attendance status, card colour status, work-mode status, current IP/location, pending reminders, and role-based STARS navigation.

The module is large:

- `KehadiranController.php`: about 4,300 lines and 90+ public actions
- `KehadiranAdminController.php`: admin CRUD for `TblRekod` and shift supervisor management
- `controllers/api/KehadiranController.php`: active mobile/API endpoints for clock in/out, working hours, attendance month list, remarks, pending tasks, and staff remark updates
- `models/kehadiran`: 29 attendance models
- `views/kehadiran`: about 100 views/partials/print templates

The STARS internal menu is role based:

- Staff
- Officer
- Head of Department
- Attendance Supervisor
- Administrator
- Reports

Old Yii2 renders this internal STARS navigation as a module-level top menu in `views/kehadiran/_menu.php`. Yii3 should keep that workflow: the fixed app sidebar opens STARS, then a compact STARS top menu exposes the role-based STARS actions.

The old access model is broad at controller level (`roles => ['@']`) and then role visibility/actions are controlled by database checks such as:

- `cuti.AksesPengguna`
- `cuti.Tindakan`
- `hronline.Department.chief`
- direct staff identity checks

## Important Data Dependencies

Primary database:

- Yii2 `staff/config/db.php`
- Database: `attendance`
- Yii3 currently binds `ConnectionInterface` to this same Yii2 `db.php`, so the default Yii3 connection is already attendance-oriented.

Core attendance tables/views seen in old models:

- `attendance.tbl_rekod`
- `attendance.ref_wp`
- `attendance.tbl_wp`
- `attendance.tbl_warnakad`
- `attendance.raw_data_kehadiran`
- `attendance.monthly_rpt`
- `attendance.ref_reason`
- `attendance.tbl_aksesselfhealth`
- `attendance.tbl_location`
- `attendance.tbl_locums`
- `attendance.tbl_overtimes`
- `attendance.tbl_ppv`
- `attendance.tbl_reports`
- `attendance.tbl_selfhealth`
- `attendance.tbl_shifts`
- `attendance.tbl_staffs`
- `attendance.tbl_wfh`
- `attendance.tbl_years`
- `attendance.vw_statistik_wfh`

Unqualified old table names probably resolve through the `attendance` default database:

- `ref_status_thtc`
- `ref_wfh_reason`
- `tbl_logs`
- `tbl_thtc`

Cross-module dependencies:

- `hronline.tblprcobiodata`
- `hronline.department`
- `cuti.akses_pengguna`
- `cuti.tindakan`
- `cuti.set_pegawai`
- `w_letter.tbl_permohonan`
- `system_core.tbl_pending_taskcommand`
- `hrv4.tbl_files` through old Yii2 `db8.php`
- `keselamatan.tbl_warnakad` for shared card-colour reporting paths
- performance modules queried inside some WFH edit/report actions: `lppums`, `elnpt`, `lnpk`

## Migration Decision

Do not port the whole Yii2 controller as-is.

Use the Yii3 style already present in this repo:

- action-per-route under `src/Web/Kehadiran`
- service classes under `src/Shared`
- raw DB/query services instead of Yii2 ActiveRecord copies
- small templates under `src/Web/Kehadiran`
- legacy handoff only for routes not migrated yet

First migrate the side-menu target as a useful Yii3 landing page, then move deeper STARS routes incrementally.

## Route Target

Initial Yii3 route:

- `GET /kehadiran/index`

Sidebar change after Phase 1:

- Change `STARS` from `http://127.0.0.1:8083/kehadiran/index` to `/kehadiran/index`
- Update sidebar active-state handling so `/kehadiran/*` marks `STARS` active

Keep legacy links inside the first Yii3 page for non-migrated STARS sub-features.

## Task Board

- [x] STARS-01 - Route Inventory And Test Fixtures
- [x] STARS-02 - Core Attendance Service
- [x] STARS-03 - Yii3 Landing Page
- [x] STARS-04 - Sidebar Cutover
- [x] STARS-05 - Module Top Menu
- [x] STARS-06 - Staff Read-Only Routes
- [x] STARS-07 - Officer And Supervisor Read Reports
- [x] STARS-07A - Officer Staff Seliaan Boundary
- [x] STARS-07B - Head Of Department Read Routes
- [x] STARS-07C - Administrator Read Routes
- [x] STARS-07D - Reports Menu And Export Decision
- [ ] STARS-08 - Reason And Approval Writes
- [ ] STARS-09 - WFH/WFO And Shift Admin
- [ ] STARS-10 - API Migration Decision
- [ ] STARS-11 - Legacy Cleanup

## Current Top Menu Migration Snapshot

Recorded on 2026-11-19.

Migrated to Yii3:

- Staff: Attendance report, Pending for reason, Card Color report, Card Colour info, Senarai WFH / WFO, Tidak Hadir Tanpa Cuti (THTC), local Guideline static file. Outstation remains external to pilot IICS.
- Officer: Approval (Reason), Card Colour Report, Attendance report, Monthly report, Senarai Tindakan WFH, Senarai Permohonan WFH, Tidak Hadir Tanpa Cuti (THTC).
- Attendance Supervisor: Staff lists, Staff Working hours, Monthly Reports, Yearly Reports, Card Colour Report, Card Colour Performance, Pantau Staf, Shields Status, Staff Shift List, Shift Setup, Manage Supervisor/Staff, Senarai Bulanan WFH, Jadual WFH, Pantau Permohonan WFH, Tidak Hadir Tanpa Cuti (THTC).

Not yet migrated / still legacy Yii2:

- Officer: Staff Seliaan.
- Head of Department: Staff attendance, Jadual WFH, Senarai Tindakan WFH, Senarai Permohonan WFH, Tidak Hadir Tanpa Cuti (THTC).
- Administrator: Staff lists, Statistic (JFPIU), Warning Letters, Raw Data, Senarai Keluar Pejabat, Admin Post (Akad), Pantau Permohonan JAFPIB, Jadual JAFPIB, Statistik WFH, Vaccination Watchlist, Tidak Hadir Tanpa Cuti (THTC).
- Reports: By Month (Range), Monthly Reports, Yearly Reports.

Boundary note:

- Migrated top-menu pages are local Yii3 pages using the attendance UI standard.
- Many migrated pages started read-first/read-only. The first Yii3 write slice now covers individual reason approval, WFH approval/reject, WFH delete, supervisor THTC verification, and HOD THTC approval. Heavy edit/maintenance flows, print/PDF, WFH day edits, shift setup saves, raw attendance CRUD, staff THTC upload, bulk approvals, and full audit parity remain tracked in later write slices.

## Remaining Top Menu Migration Plan

Recorded on 2026-11-20 after comparing `AttendanceService::menuSections()` with the old Yii2 `views/kehadiran/_menu.php`.

Current Yii3 status:

- Staff menu is local except external Outstation and static Guideline.
- Officer menu is local except `Staff Seliaan`, which intentionally still points to `cuti/pegawai/staffonleave`.
- Attendance Supervisor menu is local read-first/read-only.
- Head of Department, Administrator, and Reports menu groups are local for the migrated read pages.
- Remaining intentional top-menu handoffs are Officer `Staff Seliaan`, Admin `Senarai Keluar Pejabat`, Admin `Vaccination Watchlist`, and Reports `By Month (Range)`.
- Local Yii3 pages now handle the first approval/delete write paths. Remaining Yii2 write handoffs are staff THTC upload, staff reason remark submission, WBB officer setup, WFH day edits, shift setup saves, raw attendance CRUD, bulk approvals, PDF/print actions, and full dashboard/audit parity.

### Remaining Top Menu Task Board

Run these tasks one at a time. Mark the task checkbox only after implementation, route/menu wiring, and verification notes are complete.

- [x] STARS-07A-01 - Analyze Officer `Staff Seliaan` Boundary
- [x] STARS-07A-02 - Implement Or Confirm Handoff For Officer `Staff Seliaan`
- [x] STARS-07B-01 - Head Of Department Scope Foundation
- [x] STARS-07B-02 - Head Of Department Page Shell And Routes
- [x] STARS-07B-03 - Migrate HOD `Staff attendance`
- [x] STARS-07B-04 - Migrate HOD `Jadual WFH`
- [x] STARS-07B-05 - Validate HOD WFH Queue Links
- [x] STARS-07B-06 - Migrate HOD `THTC` List
- [x] STARS-07B-07 - HOD Menu Cutover And Verification
- [x] STARS-07C-01 - Administrator Guard And Page Shell
- [x] STARS-07C-02 - Migrate Admin `Staff lists`
- [x] STARS-07C-03 - Migrate Admin `Warning Letters`
- [x] STARS-07C-04 - Migrate Admin `Statistik WFH`
- [x] STARS-07C-05 - Migrate Admin `THTC` BPSM Overview
- [x] STARS-07C-06 - Migrate Admin `Statistic (JFPIU)`
- [x] STARS-07C-07 - Migrate Admin WFH Monitor And Schedule
- [x] STARS-07C-08 - Migrate Admin `Admin Post (Akad)`
- [x] STARS-07C-09 - Decide Admin SQL Server `Senarai Keluar Pejabat`
- [x] STARS-07C-10 - Decide Admin `Vaccination Watchlist` Boundary
- [x] STARS-07C-11 - Admin Menu Cutover And Verification
- [x] STARS-07D-01 - Reports Export/PDF Decision
- [x] STARS-07D-02 - Reports Monthly/Yearly Local Cutover
- [x] STARS-07D-03 - Reports `By Month (Range)` Handling
- [x] STARS-07D-04 - Main Menu Route Map And Legacy Badge Cleanup

Execution notes:

- Officer `Staff Seliaan` stays an intentional Yii2 handoff because it belongs to the Cuti/Registrar boundary.
- HOD `Staff attendance`, `Jadual WFH`, and `THTC` now have local Yii3 read pages. Existing HOD WFH queue/application links use the already migrated Yii3 Officer pages; individual approval/reject/delete actions now use Yii3 first-slice write routes.
- Admin `Staff lists`, `Statistic (JFPIU)`, `Warning Letters`, `Raw Data`, `Admin Post (Akad)`, WFH/JAFPIB monitor and schedule, `Statistik WFH`, and BPSM `THTC` overview now have local Yii3 read pages.
- Admin `Senarai Keluar Pejabat` stays Yii2 until the Yii3 SQL Server driver is available for `db3`.
- Admin `Vaccination Watchlist` stays Yii2 because it is a Vaksinasi module boundary.
- Reports `Monthly Reports` and `Yearly Reports` now point local; `By Month (Range)` stays Yii2 until export/PDF policy is approved.
- `MenuService::LOCAL_ROUTE_MAP` includes migrated STARS routes so DB-backed main-menu links open Yii3 where the route is local.

Recommended execution order:

1. `STARS-07A` first, because it is the only remaining Officer item and may stay as a cross-module handoff.
2. `STARS-07B` next, because HOD pages can reuse Officer/Supervisor read patterns with lower write risk.
3. `STARS-07C` in two batches: low-risk admin read pages first, heavier WFH/admin-post pages second.
4. `STARS-07D` last, because report/export decisions affect links beyond the STARS top menu.

### STARS-07A - Officer Staff Seliaan Boundary

Goal:

- Decide and execute the correct path for Officer `Staff Seliaan`, currently the only Officer top-menu item still using Yii2.

Yii2 source mapping:

| Menu item | Current Yii3 menu URL | Yii2 source | Yii3 migration target |
|---|---|---|---|
| Staff Seliaan | `http://127.0.0.1:8083/cuti/pegawai/staffonleave` | `cuti/PegawaiController::actionStaffonleave()`, view `cuti/pegawai/staffonleave.php` | Either keep as an intentional Cuti/Registrar handoff, or migrate as a small Yii3 read-only staff-on-leave page after the Cuti owner/API boundary is approved. |

Tasks:

- [x] STARS-07A-01 - Analyze Officer `Staff Seliaan` Boundary
  - Confirm whether this belongs inside STARS or the Cuti migration.
  - Identify data source/API dependency and owner.
  - Record whether Yii3 should migrate the page or keep the handoff.
- [x] STARS-07A-02 - Implement Or Confirm Handoff For Officer `Staff Seliaan`
  - If staying legacy, document it as intentional and leave the `Yii2` badge.
  - If migrating, add a read-only Yii3 route and update the Officer menu item to `localItem()`.
  - Verify Officer access and row counts for the selected Officer identity.

Acceptance:

- The Officer menu has no ambiguous remaining item.
- If migrated, the local page is read-only and matches Yii2 row counts.
- If not migrated, the plan states the external module/API reason clearly.

### STARS-07B - Head Of Department Read Routes

Goal:

- Move the Head of Department top-menu group to local Yii3 read pages. Approval/delete review actions are covered by the first `STARS-08` Yii3 write slice.

Yii2 source mapping:

| Menu item | Current Yii3 menu URL | Yii2 source | Yii3 migration target |
|---|---|---|---|
| Staff attendance | `http://127.0.0.1:8083/kehadiran/kj-pantau-kehadiran` | `KehadiranController::actionKjPantauKehadiran()`, view `kj-pantau-kehadiran.php` | Add local `/kehadiran/kj-pantau-kehadiran`; department-chief scope is active staff where `DeptId` matches the chief's department. Reuse the daily attendance monitor presentation from Officer/Supervisor pages. |
| Jadual WFH | `http://127.0.0.1:8083/kehadiran/wfh-jadual-kj` | `KehadiranController::actionWfhJadualKj()`, view `wfh-jadual-kj.php` | Add local `/kehadiran/wfh-jadual-kj`; reuse the WFH schedule grid with department-chief staff scope. Keep print/export legacy. |
| Senarai Tindakan WFH | `http://127.0.0.1:8083/kehadiran/wfh-lulus` | `KehadiranController::actionWfhLulus()`, view `wfh-lulus.php` | Validate the existing Yii3 `/kehadiran/wfh-lulus` with a department-chief identity. If row counts match Yii2, change the HOD menu item to local. Individual approval/reject is local Yii3 in the first `STARS-08` write slice. |
| Senarai Permohonan WFH | `http://127.0.0.1:8083/kehadiran/pantau-permohonan-wfh-pegawai` | `KehadiranController::actionPantauPermohonanWfhPegawai()`, view `pantau-permohonan-wfh.php` | Validate the existing Yii3 `/kehadiran/pantau-permohonan-wfh-pegawai` with a department-chief identity. If row counts match Yii2, change the HOD menu item to local. Delete is local Yii3 and POST/CSRF protected in the first `STARS-08` write slice. |
| Tidak Hadir Tanpa Cuti (THTC) | `http://127.0.0.1:8083/kehadiran/list-kj` | `KehadiranController::actionListKj()`, view `absent-list-kj.php`, `TblThtcSearch::search()` | Add local `/kehadiran/list-kj`; read-only list of THTC rows awaiting HOD approval. Individual `approve-thtc` review is local Yii3 in the first `STARS-08` write slice. |

Dependencies:

- `hronline.department` for `chief`, `isActive`, and department identity.
- `hronline.tblprcobiodata` for active department staff.
- `attendance.tbl_rekod` for attendance monitor rows.
- `attendance.tbl_wfh` for WFH queues/schedules.
- `attendance.tbl_thtc` and related THTC joins for `list-kj`.

Implementation plan:

- [x] STARS-07B-01 - Head Of Department Scope Foundation
  - Add department-chief scope helpers to `AttendanceService`: current chief department, active department staff, and HOD authorization guard.
  - Verify selected HOD identity resolves to the same department used by Yii2.
- [x] STARS-07B-02 - Head Of Department Page Shell And Routes
  - Add `HeadDepartmentAction` or equivalent focused action.
  - Add local routes for `/kehadiran/kj-pantau-kehadiran`, `/kehadiran/wfh-jadual-kj`, and `/kehadiran/list-kj`.
  - Add a reusable HOD template following `attendance-standard-*`.
- [x] STARS-07B-03 - Migrate HOD `Staff attendance`
  - Implement read-only department daily attendance monitor.
  - Reuse Officer/Supervisor daily attendance presentation where possible.
  - Keep detail/edit/print links as Yii2 handoffs.
- [x] STARS-07B-04 - Migrate HOD `Jadual WFH`
  - Implement read-only department WFH schedule grid.
  - Keep print/export legacy.
- [x] STARS-07B-05 - Validate HOD WFH Queue Links
  - Smoke existing Yii3 `/kehadiran/wfh-lulus` and `/kehadiran/pantau-permohonan-wfh-pegawai` using a HOD identity.
  - If row counts match Yii2, convert HOD menu links to local.
  - If not, add HOD-specific service scope before cutover.
- [x] STARS-07B-06 - Migrate HOD `THTC` List
  - Implement read-only `/kehadiran/list-kj`.
  - Wire `approve-thtc` to the local Yii3 review route from the first `STARS-08` write slice.
- [x] STARS-07B-07 - HOD Menu Cutover And Verification
  - Update `menuSections()` so completed HOD pages use `localItem()`.
  - Run syntax checks and authenticated smoke for every HOD route.
  - Record row-count comparison and completion notes.

Acceptance:

- A non-chief cannot access HOD pages.
- A department chief sees only active staff in their own department.
- Yii3 row counts match Yii2 for selected HOD identities.
- No HOD route mutates attendance, WFH, or THTC records.

### STARS-07C - Administrator Read Routes

Goal:

- Move administrator top-menu pages that can be safely represented as read-only Yii3 pages while preserving Yii2 for CRUD, exports, and cross-driver gaps.

Yii2 source mapping:

| Menu item | Current Yii3 menu URL | Yii2 source | Yii3 migration target |
|---|---|---|---|
| Staff lists | `http://127.0.0.1:8083/kehadiran/staff-all` | `KehadiranController::actionStaffAll()`, view `staff-all.php`, `TblprcobiodataSearch` | Add local `/kehadiran/staff-all`; active staff directory with department filters. Read-only first. |
| Statistic (JFPIU) | `http://127.0.0.1:8083/kehadiran/admin-mth-rpt` | `KehadiranController::actionAdminMthRpt()`, view `rpt.php` | Replace with local admin-scoped monthly summary, likely reusing supervisor monthly report with administrator-wide department options. |
| Warning Letters | `http://127.0.0.1:8083/kehadiran/color-rpt` | `KehadiranController::actionColorRpt()`, view `color-rpt.php`, `attendance.tbl_warnakad` | Add local `/kehadiran/color-rpt`; month/year/color filters. Keep print route legacy. |
| Raw Data | `http://127.0.0.1:8083/attendance/index` | `AttendanceController::actionIndex()`, view `attendance/index.php`, `TblRekodSearch` | Add read-only local `/attendance/index` or `/kehadiran/raw-data`. Create/update/delete stays legacy until admin write policy is approved. |
| Senarai Keluar Pejabat | `http://127.0.0.1:8083/kehadiran/senarai_keluar_pejabat` | `KehadiranController::actionSenarai_keluar_pejabat()`, view `senarai_keluar_pejabat.php`, model `KeluarPejabat` on `db3` SQL Server view `vEAttendance` | Defer until Yii3 SQL Server driver is available. Keep Yii2 handoff for now. |
| Admin Post (Akad) | `http://127.0.0.1:8083/kehadiran/admin-post-list` | `KehadiranController::actionAdminPostList()`, view `admin-post-list.php`, `Tblrscoadminpost`, `Adminposition` | Add local read-only list after staff/admin filters are settled. No edits in first slice. |
| Pantau Permohonan JAFPIB | `http://127.0.0.1:8083/kehadiran/pantau-permohonan-wfh-admin` | `KehadiranController::actionPantauPermohonanWfhAdmin()`, view `pantau-permohonan-wfh.php`, `TblWfh::search()` | Add local admin WFH application monitor using unrestricted/admin-constrained WFH query. Delete is local Yii3 and POST/CSRF protected in the first `STARS-08` write slice. |
| Jadual JAFPIB | `http://127.0.0.1:8083/kehadiran/wfh-jadual-admin` | `KehadiranController::actionWfhJadualAdmin()`, view `wfh-jadual-admin.php` | Add local WFH schedule grid with department filter. Reuse supervisor schedule UI where possible. |
| Statistik WFH | `http://127.0.0.1:8083/kehadiran/statistik-wfh` | `KehadiranController::actionStatistikWfh()`, view `statistik-wfh.php`, `attendance.vw_statistik_wfh` | Add local `/kehadiran/statistik-wfh`; read from `attendance.vw_statistik_wfh`, default to today when no filter is supplied. Export remains legacy until report/export decision. |
| Vaccination Watchlist | `http://127.0.0.1:8083/vaksinasi/watch-list` | `VaksinasiController` / vaccination module | Keep legacy until the Vaksinasi module owner decides whether it belongs in STARS or Biodata/medical migration scope. |
| Tidak Hadir Tanpa Cuti (THTC) | `http://127.0.0.1:8083/attendance/absent-list-bpsm` | `AttendanceController::actionAbsentListBpsm()`, view `absent-list-bpsm.php`, `TblThtcSearch::searchbpsm()` | Add local `/attendance/absent-list-bpsm`; BPSM/admin THTC overview. THTC approval/write actions remain Yii2. |

Implementation plan:

- [x] STARS-07C-01 - Administrator Guard And Page Shell
  - Add explicit administrator guard based on the existing `hasAccess($icno, 3)` role flag.
  - Add `AdminAction` plus `admin-page.php`, or extend `SupervisorAction` only for truly shared pages.
  - Confirm ordinary staff, Officer, Supervisor, and HOD users are denied.
- [x] STARS-07C-02 - Migrate Admin `Staff lists`
  - Add local `/kehadiran/staff-all`.
  - Implement active staff directory with department filters.
  - Keep the page read-only.
- [x] STARS-07C-03 - Migrate Admin `Warning Letters`
  - Add local `/kehadiran/color-rpt`.
  - Implement month/year/color filters from `attendance.tbl_warnakad`.
  - Keep print route legacy.
- [x] STARS-07C-04 - Migrate Admin `Statistik WFH`
  - Add local `/kehadiran/statistik-wfh`.
  - Read from `attendance.vw_statistik_wfh`.
  - Default to today when no filter is supplied.
  - Keep export legacy until `STARS-07D`.
- [x] STARS-07C-05 - Migrate Admin `THTC` BPSM Overview
  - Add local `/attendance/absent-list-bpsm`.
  - Implement read-only BPSM/admin THTC overview.
  - Keep approval/write actions on Yii2.
- [x] STARS-07C-06 - Migrate Admin `Statistic (JFPIU)`
  - Add local `/kehadiran/admin-mth-rpt`.
  - Reuse monthly report logic with administrator-wide department options.
  - Verify admin row counts against Yii2.
- [x] STARS-07C-07 - Migrate Admin WFH Monitor And Schedule
  - Add local `/kehadiran/pantau-permohonan-wfh-admin`.
  - Add local `/kehadiran/wfh-jadual-admin`.
  - Wire delete to the local Yii3 POST/CSRF route from the first `STARS-08` write slice.
  - Keep edit-day/print actions as Yii2 handoffs.
- [x] STARS-07C-08 - Migrate Admin `Admin Post (Akad)`
  - Add local `/kehadiran/admin-post-list`.
  - Implement read-only academic admin-post list.
  - Defer edits and cross-module mutations.
- [x] STARS-07C-09 - Decide Admin SQL Server `Senarai Keluar Pejabat`
  - Confirm Yii3 SQL Server driver availability for `db3`.
  - If unavailable, keep `/kehadiran/senarai_keluar_pejabat` as an intentional Yii2 handoff.
  - If available, add a read-only local page from `vEAttendance`.
- [x] STARS-07C-10 - Decide Admin `Vaccination Watchlist` Boundary
  - Confirm whether `vaksinasi/watch-list` belongs to STARS or Vaksinasi/Biodata/medical scope.
  - Keep legacy unless ownership is approved.
- [x] STARS-07C-11 - Admin Menu Cutover And Verification
  - Update `menuSections()` completed admin items from `legacyItem()` to `localItem()`.
  - Run syntax checks and authenticated admin smoke for completed routes.
  - Record row-count comparisons and deferred handoffs.

Acceptance:

- Ordinary staff, officers, and HOD users cannot access administrator pages.
- Admin Yii3 row counts match Yii2 for selected admin identities.
- Admin pages are read-only: no create/update/delete, no bulk approval, no export mutation.
- SQL Server-dependent pages remain clearly marked as Yii2 handoffs until the driver gap is closed.

### STARS-07D - Reports Menu And Export Decision

Goal:

- Convert the Reports top-menu group without prematurely porting mPDF/PDF generation.

Yii2 source mapping:

| Menu item | Current Yii3 menu URL | Yii2 source | Yii3 migration target |
|---|---|---|---|
| By Month (Range) | `http://127.0.0.1:8083/kehadiran/range-month` | `KehadiranController::actionRangeMonth()`, view `range-month.php`, redirects to `gen-yearly-pdf-rpt` | Keep legacy until the PDF/export standard is decided, or add a local read-only report-parameter page that links to Yii2 PDF generation. |
| Monthly Reports | `http://127.0.0.1:8083/kehadiran/sup-mth-rpt` | `KehadiranController::actionSupMthRpt()` | The page already exists locally under Attendance Supervisor. For admin `Reports`, validate admin scope then change this menu item to local `/kehadiran/sup-mth-rpt`. |
| Yearly Reports | `http://127.0.0.1:8083/kehadiran/sup-year-rpt` | `KehadiranController::actionSupYearRpt()` | The page already exists locally under Attendance Supervisor. For admin `Reports`, validate admin scope then change this menu item to local `/kehadiran/sup-year-rpt`. |

Implementation plan:

- [x] STARS-07D-01 - Reports Export/PDF Decision
  - Decide whether Yii3 report exports should be HTML-first, CSV-first, or PDF-compatible with old mPDF output.
  - Record the decision before moving PDF/report generator links.
- [x] STARS-07D-02 - Reports Monthly/Yearly Local Cutover
  - Validate admin access to local `/kehadiran/sup-mth-rpt` and `/kehadiran/sup-year-rpt`.
  - Convert Reports `Monthly Reports` and `Yearly Reports` menu items to local links.
- [x] STARS-07D-03 - Reports `By Month (Range)` Handling
  - Keep Yii2 if old `gen-yearly-pdf-rpt` remains authoritative.
  - Or add a local read-only report-parameter page that clearly hands PDF generation to Yii2.
- [x] STARS-07D-04 - Main Menu Route Map And Legacy Badge Cleanup
  - Update `MenuService::LOCAL_ROUTE_MAP` for migrated STARS routes when DB-backed main menu should also route locally.
  - Remove `Yii2` badges only from links that are genuinely local.
  - Record intentionally legacy report/export links.

Acceptance:

- Admin Reports links that point local reuse existing read pages safely.
- `By Month (Range)` remains functional through Yii2 while PDF/export policy is unresolved.
- No broken report link appears in the STARS top menu.

## Executable Tasks

### STARS-01 - Route Inventory And Test Fixtures

- [x] Completion

Goal:

- Freeze a route/action inventory before implementation.

Work:

- Record old route names, view files, table writes, and role requirements from `KehadiranController.php`.
- Pick one staff test identity, one supervisor/officer identity, and one admin identity from current data.
- Capture expected old Yii2 output for:
  - `kehadiran/index`
  - `kehadiran/laporan-kehadiran`
  - `kehadiran/tindakan_ketidakpatuhan`
  - `kehadiran/staf-color-list`
  - `kehadiran/wfh-list`

Acceptance:

- The plan has concrete test identities and expected visible cards/menu sections.
- No Yii3 behavior changed.

Verification:

- Browser old Yii2 routes.
- Database read checks for selected identities.

Done when:

- [x] Route/action inventory is recorded.
- [x] Staff, officer/supervisor, department chief, and admin test identities are chosen.
- [x] Old Yii2 source baseline is captured for the selected routes.
- [x] No Yii3 behavior changed during this inventory task.

Execution notes:

- Route inventory source: `KehadiranController.php`, `KehadiranAdminController.php`, and `controllers/api/KehadiranController.php`.
- Main old route: `kehadiran/index`.
- First staff read routes kept as legacy links in the Yii3 landing page:
  - `kehadiran/laporan-kehadiran`
  - `kehadiran/tindakan_ketidakpatuhan`
  - `kehadiran/staf-color-list`
  - `kehadiran/wfh-list`
  - `kehadiran/absent-list`
- Test identities selected from local data:
  - Staff: `940402125181` / Jackly Bin Mosuning
  - Staff with recent card-colour data: `000101121793` / Dennium Victer
  - Officer/supervisor: `011013120428` / Nur Shahiratul Shahida Binti A Roslim (`hrm.cuti_access.akses_cuti_int = 2`)
  - Department chief: `881214495321` / Massalinri Bin Mohd Tahir (`hronline.department.chief`, department `KESELAMATAN`)
  - Admin/supervisor candidate: `710330125524` / Zaleha Abdul Ghani (`hrm.cuti_access.akses_cuti_int = 2`, multi-campus access found; admin-specific identity still to confirm before write/admin phases)
- Local DB caveat: `attendance.tbl_rekod` currently has `0` rows, so the landing page must tolerate no current attendance record.
- Old Yii2 HTTP baseline caveat: `http://127.0.0.1:8083/kehadiran/index` was not reachable during execution, so source and database baselines were used.

### STARS-02 - Core Attendance Service

- [x] Completion

Goal:

- Recreate the read-only data needed for the STARS landing page.

Files:

- Add `src/Shared/AttendanceService.php`
- Add focused value methods instead of Yii2 AR clones.

Service methods:

- `landingForIdentity(string $identityId): ?array`
- `currentWorkingPattern(string $icno): ?array`
- `currentRecord(string $icno, string $date): ?array`
- `cardColor(string $icno, int $month, int $year): string`
- `pendingCounts(string $icno): array`
- `workMode(string $icno, string $date): string`
- `roleFlags(string $icno): array`

Notes:

- Port logic from old `TblWp::curr_wp`, `RefWp::totalHours`, `TblRekod::current`, `TblWarnaKad::warnaMonth`, `TblWarnaKad::WarnaKadSemasa`, and pending-count helpers.
- Keep write methods out of this service initially.

Acceptance:

- Service returns the landing page summary for a valid staff session.
- Missing/invalid identity returns `null`.
- No clock-in/clock-out write behavior is introduced.

Verification:

- Add focused PHPUnit/service tests if the project test harness supports DB-backed tests.
- At minimum, run `php -l` and a small authenticated HTTP smoke after route wiring.

Done when:

- [x] `AttendanceService` exists.
- [x] Landing summary methods return data for a valid staff session.
- [x] Missing/invalid identity returns `null`.
- [x] No clock-in/clock-out write behavior exists in the service.
- [x] Syntax checks pass.

Execution notes:

- Added `src/Shared/AttendanceService.php`.
- Service is read-only and covers:
  - staff identity/session summary
  - current working pattern
  - current attendance record with empty-record fallback
  - card-colour summary
  - WFH/WFO mode
  - pending reason/approval/WBB counts
  - STARS role menu sections
- It intentionally does not expose web clock-in/out or mobile/API behavior.

### STARS-03 - Yii3 Landing Page

- [x] Completion

Goal:

- Add a real Yii3 replacement for old `kehadiran/index`.

Files:

- Add `src/Web/Kehadiran/IndexAction.php`
- Add `src/Web/Kehadiran/index.php`
- Add route in `config/common/routes.php`

Page content:

- Attendance notice: UMS Go SuperApp attendance recording from 1 October 2024
- Hours completed percentage/progress
- Card colour status
- Non-compliance status
- Working hours
- IP address
- Location status placeholder
- Staff/role menu sections
- Pending reason/approval counters

Design:

- Match existing Yii3 page-card style, not old Gentelella/AdminLTE markup.
- Use lucide icons in compact controls.
- Use local links only for migrated STARS routes.
- Use legacy links with a `Yii2` badge for routes not migrated yet.

Acceptance:

- Authenticated `/kehadiran/index` returns 200.
- Unauthenticated `/kehadiran/index` redirects to `/login`.
- The page has no Yii2 `x_panel`, Bootstrap 3, jQuery, SweetAlert, or old widget dependencies.

Verification:

- `php -l` new files.
- `php yii list`.
- Browser smoke `/kehadiran/index`.

Done when:

- [x] `GET /kehadiran/index` is routed.
- [x] Authenticated request returns 200.
- [x] Unauthenticated request redirects to `/login`.
- [x] Page uses Yii3 styling and has no old Yii2 widget dependencies.
- [x] Internal non-migrated links still hand off to Yii2.

Execution notes:

- Added `src/Web/Kehadiran/IndexAction.php`.
- Added `src/Web/Kehadiran/index.php`.
- Added `/kehadiran/index` route in `config/common/routes.php`.
- Added attendance landing styles in `assets/main/site.css`.
- Authenticated smoke used session identity `staff-940402125181`.
- Smoke result: HTTP 200, STARS heading present, UMS Go notice present, legacy `Yii2` badges present, no old `x_panel`, SweetAlert, Bootstrap 3, jQuery call, or old clock form marker.

### STARS-04 - Sidebar Cutover

- [x] Completion

Goal:

- Make the side-menu `STARS` item open Yii3.

Files:

- Update `src/Web/Shared/Layout/Main/layout.php`

Work:

- Change the STARS href to `/kehadiran/index`.
- Add active-state alias for `currentPath.startsWith('/kehadiran/')`.
- Keep old Yii2 handoffs for SKB and Support System.

Acceptance:

- Clicking STARS opens Yii3 `/kehadiran/index`.
- STARS is highlighted for `/kehadiran/index`.
- Legacy STARS sub-links in the landing page still work where not migrated.

Verification:

- Browser click from dashboard side-menu.

Done when:

- [x] Sidebar STARS href is `/kehadiran/index`.
- [x] `/kehadiran/*` marks STARS active.
- [x] SKB and Support System remain legacy handoffs.
- [x] HTTP smoke confirms Yii3 STARS route opens authenticated.

Execution notes:

- Updated `src/Web/Shared/Layout/Main/layout.php`.
- `STARS` now points to `/kehadiran/index`.
- Active-state JavaScript treats `/kehadiran/*` as STARS.

### STARS-05 - Module Top Menu

- [x] Completion

Goal:

- Restore the old STARS module-level top menu in Yii3 without bringing back Yii2 Bootstrap/Gentelella widgets.

Old source:

- `C:\Apache24\htdocs\staff\views\kehadiran\_menu.php`

Files:

- Update `src/Web/Kehadiran/index.php`
- Update `assets/main/site.css`
- Optionally extract a partial such as `src/Web/Kehadiran/_topMenu.php` if the markup is reused by later STARS pages.

Menu structure:

- Staff
- Officer
- Head of Department
- Attendance Supervisor
- Administrator
- Reports

Design:

- Follow the agreed Tailwind/Flowbite direction currently represented by the Yii3 no-build CSS layer:
  - one `page-main-card`
  - internal `page-card-section` sections
  - one `content-inner-card` for detail/list data areas
  - soft Flowbite-like borders, radius, shadows, and Lucide icons
  - no AdminLTE, Bootstrap, Gentelella, Kartik, or jQuery widget markup
- Place the STARS top menu directly below the STARS page heading and before the summary cards.
- Use horizontal tabs or a compact segmented row on desktop.
- On smaller screens, allow wrapping or switch to stacked disclosure groups.
- Use lucide icons.
- Show pending badges beside `Staff` and `Officer` sections when counts are non-zero.
- Keep all non-migrated routes as legacy Yii2 handoffs with `Yii2` badges.
- Do not use old Yii2 `NavBar`, `Nav::widget`, Bootstrap dropdown markup, jQuery, or `fa-*` icons.

Acceptance:

- `/kehadiran/index` shows a visible STARS top menu.
- Top menu role visibility matches the same role flags already used by `AttendanceService`.
- Top menu links match the old `_menu.php` route list, with Yii2 handoffs for unmigrated routes.
- The existing landing summary remains below the top menu.
- No text overlaps or overflows on desktop and mobile widths.

Verification:

- `php -l src/Web/Kehadiran/index.php`
- Authenticated `/kehadiran/index` smoke.
- Browser or HTTP content check for all expected top-level menu labels.
- Mobile-width visual smoke if browser tooling is available.

Done when:

- [x] STARS top menu is rendered on `/kehadiran/index`.
- [x] Staff, Officer, Head of Department, Attendance Supervisor, Administrator, and Reports sections follow role visibility.
- [x] Non-migrated links still hand off to Yii2.
- [x] Landing content remains accessible below the top menu.
- [x] Syntax and smoke checks pass.

Execution notes:

- Updated `src/Web/Kehadiran/index.php`.
- Added the STARS module top menu directly below the page heading and before the UMS Go notice and summary cards.
- Reused the `AttendanceService` `menuSections` visibility model so the top menu follows the same role flags as the landing menu data.
- Kept non-migrated route links as legacy Yii2 handoffs with `Yii2` badges.
- Removed the duplicate lower menu block from the landing page so STARS navigation now appears as the expected module-level top menu.
- Aligned the page with the agreed Tailwind/Flowbite-style Yii3 standard:
  - top menu is an internal main-card section, not a separate card surface
  - detail/card-colour data sits in one `content-inner-card`
  - staff ICNO is not shown in the main card heading
- Added responsive dropdown/stacked styles in `assets/main/site.css`.
- `php -l src/Web/Kehadiran/index.php` passed.
- Authenticated `/kehadiran/index` smoke for `staff-940402125181` returned HTTP 200 and confirmed the top menu, `Staff`, `Officer`, UMS Go notice, and `Yii2` badges.
- Smoke confirmed no old Yii2 `x_panel`, SweetAlert, Bootstrap 3, or jQuery call markers.

## Staff Top Menu Analysis

Source menu:

- Yii3 current menu data: `src/Shared/AttendanceService.php`, `menuSections()`, section key `staff`
- Yii3 current rendered menu: `src/Web/Kehadiran/index.php`
- Yii2 old menu source: `C:\Apache24\htdocs\staff\views\kehadiran\_menu.php`
- Yii2 controller source: `C:\Apache24\htdocs\staff\controllers\KehadiranController.php`
- Yii2 core models: `TblRekod`, `TblRekodSearch`, `TblWarnaKad`, `TblWfh`, `TblWp`, `RefWp`, `TblThtc`

Current Yii3 Staff menu items and Yii2 mapping:

| Menu item | Current Yii3 URL | Old Yii2 source | Behavior | Yii3 migration target |
|---|---|---|---|---|
| Attendance report | `http://127.0.0.1:8083/kehadiran/laporan-kehadiran` | `actionLaporanKehadiran()`, view `laporan-kehadiran.php` | GET filters by `tahun` and `bulan`; shows staff biodata, card colour, monthly summary, daily rows, WBB, in/out time, non-compliance, leave/outstation/WFH, hours, remark status, map links, detail modal, and print link. | Migrate as read-only `GET /kehadiran/laporan-kehadiran`; add detail modal later as `GET /kehadiran/detailrekod`. Keep print/PDF legacy until report data is stable. |
| Pending for reason | `http://127.0.0.1:8083/kehadiran/tindakan_ketidakpatuhan` | `actionTindakan_ketidakpatuhan()`, view `tindakan_ketidakpatuhan.php` | Lists `attendance.tbl_rekod` rows for current staff where any non-compliance flag is true and `remark_status IS NULL`; each row links to write route `kehadiran/remark`. | Split into two phases: read-only list in `STARS-06`; `remark` form/write in `STARS-08`. |
| Card Color report | `http://127.0.0.1:8083/kehadiran/staf-color-list` | `actionStafColorList()`, view `staf-color-list.php`, `TblWarnaKad` | GET filters by year; lists `attendance.tbl_warnakad`; summaries YELLOW/GREEN/RED totals and yearly performance indicator. Old view has Kartik GridView/export, but Yii3 should use local table/export policy. | Migrate as read-only `GET /kehadiran/staf-color-list`; no Kartik/Bootstrap widgets. Optional export can be a later CSV/PDF task. |
| Card Colour info | `http://127.0.0.1:8083/kehadiran/kad_warna` | `actionKad_warna()`, view `kad_warna.php` | Ajax/modal image only: `@web/images/kad_warna.png`. | Easiest local migration. Add `GET /kehadiran/kad-warna` or render image inline/modal; copy old `web/images/kad_warna.png` into Yii3 `public/images`. |
| Outstation | `https://pilot-iics.ums.edu.my/app/travel-request` | external link in `_menu.php` | Opens external IICS travel request app. | Keep external. No Yii3 migration required unless UMS wants a proxy/SSO handoff. |
| Guideline | `/files/garis_panduan_kehadiran.pdf` | old `@web/files/garis_panduan_kehadiran.pdf` | Static PDF link. Old file exists at `C:\Apache24\htdocs\staff\web\files\garis_panduan_kehadiran.pdf`. | Copy PDF into Yii3 `public/files`; keep local link. Current Yii3 public copy is missing. |
| Senarai WFH / WFO | `http://127.0.0.1:8083/kehadiran/wfh-list` | `actionWfhList()`, view `wfh-list.php`, `TblWfh` | Lists current staff `attendance.tbl_wfh` rows with `YEAR(start_date) >= 2022`, descending start date; shows date range, duration, remark, entry date/time, status. | Migrate as read-only `GET /kehadiran/wfh-list`. Application/approval/edit flows stay legacy until `STARS-09`. |
| Tidak Hadir Tanpa Cuti (THTC) | `http://127.0.0.1:8083/kehadiran/absent-list` | `actionAbsentList()`, view `absent-list.php`, `TblRekodSearch::searchRecord()` | Shows current staff absent records for hard-coded year `2025`, `absent = 1`, `remark_status = REJECTED`, excluding records covered by leave; includes THTC status, supporting doc, verifier/chief remarks, and action link to `remark-thtc` when not submitted or returned. | Migrate read-only list in `STARS-06`; move `remark-thtc` upload/write workflow to `STARS-08`. Revisit hard-coded `2025` before implementation. |

Staff menu data and query dependencies:

- `attendance.tbl_rekod`: daily records, in/out time, non-compliance flags, remark fields, coordinates, THTC base records.
- `attendance.tbl_warnakad`: card-colour report and current card colour.
- `attendance.tbl_wfh`: WFH/WFO date ranges and statuses.
- `attendance.tbl_wp` and `attendance.ref_wp`: WBB labels for daily report rows.
- `attendance.tbl_thtc`: THTC submission/status details through `TblThtc` relation.
- `attendance.tbl_years`: old year dropdown values.
- `hronline.tblprcobiodata`, `hronline.department`, `gredjawatan`: staff biodata and department data.
- `e_cuti.cuti_umum`, leave records, outstation records, and WFH records are used by old `TblRekod::DisplayCuti()` to explain daily report rows.
- `cuti.set_pegawai` and `cuti.akses_pengguna` become required only when moving write flows (`remark`, `remark-thtc`) out of Yii2.

Recommended Staff menu migration order:

1. Copy static assets first: `kad_warna.png` and `garis_panduan_kehadiran.pdf`.
2. Implement `kad-warna` info locally because it is static and low risk.
3. Implement `wfh-list` locally because it is simple read-only table data.
4. Implement `staf-color-list` locally because the data is already partly represented by `AttendanceService::cardColor()`.
5. Implement `tindakan_ketidakpatuhan` as a read-only pending-reason list, but keep the `Remark` button pointing to Yii2 until `STARS-08`.
6. Implement `absent-list` as read-only THTC list, but keep `remark-thtc` on Yii2 until `STARS-08`.
7. Implement `laporan-kehadiran` after shared daily-row helpers are ready because it has the widest dependency surface.

Implementation notes for Yii3:

- Add focused read models to `AttendanceService` or a dedicated `AttendanceStaffService` if `AttendanceService` becomes too broad.
- Keep GET pages read-only in `STARS-06`; do not migrate writes, uploads, approval, notification, dashboard pending-task updates, or PDF generation in the same slice.
- Replace Yii2 ActiveDataProvider/GridView/Kartik widgets with local array queries and existing Yii3 table/card styles.
- Preserve top menu placement and styling from `/kehadiran/index`; new STARS pages should reuse the same top-menu data so users can move between Staff routes without returning to the landing page.
- When a route is migrated, change only that menu item from legacy URL to local `/kehadiran/...` and remove its `Yii2` badge.
- The old `external_out` flag is included in pending reason counts and status labels; keep it in Yii3 queries.
- The old THTC staff list uses a hard-coded `2025`; decide whether Yii3 should preserve that exactly for parity or introduce a year filter with default current year.
- Current asset gap: Yii3 does not yet have `public/images/kad_warna.png` or `public/files/garis_panduan_kehadiran.pdf`.

Attendance UI standard:

- Attendance/STARS pages should use the shared `attendance-standard-*` toolbar pattern.
- Every migrated attendance page should start with a compact toolbar:
  - left side: Lucide icon, page title, short context/subtitle
  - right side: filters/actions or a concise count/status block
- Use `attendance-standard-toolbar`, `attendance-standard-title`, `attendance-standard-icon`, `attendance-standard-filter`, `attendance-standard-fields`, `attendance-standard-actions`, and `attendance-standard-count`.
- Use semantic icon variants such as `attendance-icon-info`, `attendance-icon-success`, `attendance-icon-warning`, and `attendance-icon-danger`.
- Data content should sit below the toolbar inside `content-inner-card` panels with responsive `data-table` tables, status badges, and compact icon actions.
- Avoid old Yii2 visual patterns in migrated attendance pages: no `x_panel`, Bootstrap 3 panel/toolbox markup, Kartik GridView controls, or page-local ad hoc headers.

## Officer Top Menu Analysis

Source menu:

- Yii3 current menu data: `src/Shared/AttendanceService.php`, `menuSections()`, section key `officer`
- Yii2 old menu source: `C:\Apache24\htdocs\staff\views\kehadiran\_menu.php`
- Yii2 attendance controller source: `C:\Apache24\htdocs\staff\controllers\KehadiranController.php`
- Yii2 Cuti officer source for `Staff Seliaan`: `C:\Apache24\htdocs\staff\controllers\cuti\PegawaiController.php`

Current Yii3 Officer menu items and Yii2 mapping:

| Menu item | Current Yii3 URL | Old Yii2 source | Behavior | Yii3 migration target |
|---|---|---|---|---|
| Approval (Reason) | `http://127.0.0.1:8083/kehadiran/senarai_tindakan` | `actionSenarai_tindakan()`, view `senarai_tindakan.php`, `TblRekod` | Lists `attendance.tbl_rekod` rows assigned to the officer with `app_by = current/delegated icno` and `remark_status = ENTRY`; old page supports bulk checkbox approval by POST, notification writes, and individual `kehadiran/pengesahan` action. | Yii3 read list plus local Yii3 individual `pengesahan` review. Bulk approve remains in `STARS-08` follow-up with transaction/audit verification. |
| Card Colour Report | `http://127.0.0.1:8083/kehadiran/staff-color-card` | `actionStaffColorCard()`, view `staff-color-card.php`, `SetPegawai`, `TblWarnaKad::WarnaKadSemasa()` | Lists supervised staff from `hrm.cuti_set_pegawai` where current ICNO is `peraku_icno` or `pelulus_icno`; shows current month card non-compliance, accepted, rejected, and card colour. | Good early read-only route. Add `GET /kehadiran/staff-color-card`; use `attendance-standard-toolbar`, current month/year context, and local table. |
| Attendance report | `http://127.0.0.1:8083/kehadiran/pantau-kehadiran` | `actionPantauKehadiran()`, view `pantau-kehadiran.php`, `SetPegawai`, `TblRekod`, `TblWp` | Date-filtered daily attendance monitor for supervised staff; shows WBB, in/out time, leave/outstation/WFH, non-compliance status, map links, detail modal, staff attendance report link, and staff-history modal. | Read-only route in `STARS-07`; reuse Staff daily-row helpers and `attendance-standard-filter` date picker. Keep detail, report print, and staff history as Yii2 handoffs until their local pages exist. |
| Monthly report | `http://127.0.0.1:8083/kehadiran/monthly-summary` | `actionMonthlySummary()`, view `monthly-summary.php`, `SetPegawai`, `TblRekod` | Month-filtered supervised-staff summary: working hours, completed working days, late in, early out, incomplete, absent, external. Old code defaults current year and selected/current month. | Read-only route in `STARS-07`; use local month/year filters and summary table. Consider adding explicit year filter instead of hidden current-year behavior. |
| Senarai Tindakan WFH | `http://127.0.0.1:8083/kehadiran/wfh-lulus` | `actionWfhLulus()`, view `wfh-lulus.php`, `TblWfh` | Lists WFH applications where `app_by = current icno` and `status = ENTRY`; old page supports bulk approve by POST and single approve/reject through `wfh-lulus-tindakan`. | Yii3 read list plus local Yii3 single approve/reject route. Bulk approval remains deferred. |
| Senarai Permohonan WFH | `http://127.0.0.1:8083/kehadiran/pantau-permohonan-wfh-pegawai` | `actionPantauPermohonanWfhPegawai()`, view `pantau-permohonan-wfh.php`, `TblWfh::searchAsPegawai()` | Lists up to 1000 WFH applications with `YEAR(start_date) > 2021`, `app_by = current icno`, excluding remarks like `ADDED BY PENYELIA`; supports filters/export/Kartik GridView and delete action `delete-wfh`. | Read monitor route with local Yii3 delete action protected by POST/CSRF from migrated pages. |
| Staff Seliaan | `http://127.0.0.1:8083/cuti/pegawai/staffonleave` | `cuti\PegawaiController::actionStaffonleave()`, view `cuti/pegawai/staffonleave.php`, `TblRecords::TeamList()` | Uses external/internal Registrar API `https://registrar.ums.edu.my/staff/web/api/dashboard/team?icno=...`; shows team name, designation, service status, time in/out, and leave/outstation text. | Treat as cross-module dependency. Either keep Yii2 handoff initially, or create a Yii3 proxy/service only after confirming API availability/auth and failure behavior. Do not block core STARS Officer migration on this. |
| Tidak Hadir Tanpa Cuti (THTC) | `http://127.0.0.1:8083/kehadiran/absent-list-kp` | `actionAbsentListKp()`, view `absent-list-kp.php`, `TblThtcSearch::searchkp()` | Department PP-only page: validates current staff is `department.pp`; lists `attendance.tbl_thtc` joined to records and biodata for current department, excluding status `6`; shows absent date/day, staff name, absent remark, THTC remark, leave marker, supporting doc, THTC status, requested date. | Read list with department PP access check and local Yii3 THTC review actions where applicable. |

Officer data and query dependencies:

- `hrm.cuti_set_pegawai`: supervised-staff scope via `pemohon_icno`, `peraku_icno`, `pelulus_icno`.
- `attendance.tbl_rekod`: reason approval, daily monitor, monthly summary, non-compliance flags, remark status, map coordinates.
- `attendance.tbl_warnakad`: supervised staff card-colour report.
- `attendance.tbl_wfh`: WFH approval queue and WFH application monitor.
- `attendance.tbl_thtc`: THTC department monitor.
- `hronline.tblprcobiodata`, `hronline.department`, `hronline.gredjawatan`: staff names, active status, department, position, department PP guard.
- `hrm.cuti_tindakan` delegation: old `actionSenarai_tindakan()` uses `Tindakan::penerimaTindakan()`/`icno_pemberi_kuasa` behavior for approval identity; Yii3 must preserve this for approval counts and lists.
- `TblRecords::TeamList()` external Registrar API for `Staff Seliaan`; this is not a direct DB query and needs a separate reliability decision.

Recommended Officer migration order:

1. Build shared Officer scope helpers:
   - supervised staff via `hrm.cuti_set_pegawai` (`peraku_icno` or `pelulus_icno`)
   - delegated approval identity for `Approval (Reason)`
   - department PP guard for `absent-list-kp`
2. Read-only `staff-color-card`; low risk and small output.
3. Read-only `monthly-summary`; uses existing Staff summary count helpers.
4. Read-only `pantau-kehadiran`; widest read-only surface, but reuses daily-row helpers from Staff attendance report.
5. Read-only `pantau-permohonan-wfh-pegawai`; keep delete/export legacy.
6. Read-only `wfh-lulus`; keep approve/reject and bulk approval legacy until writes are intentionally migrated.
7. Read-only `senarai_tindakan`; keep individual and bulk reason approval writes legacy until `STARS-08`.
8. Read-only `absent-list-kp`; implement only after department PP guard is tested.
9. Keep `cuti/pegawai/staffonleave` as Yii2 handoff until API proxy behavior is decided, or migrate as a separate Cuti/Attendance bridge.

Officer UI standard:

- All Yii3 Officer attendance pages must use the `attendance-standard-*` toolbar standard.
- Pages with filters use `attendance-standard-filter`, `attendance-standard-fields`, and `attendance-standard-actions`.
- Pages with queues/counts use `attendance-standard-count`.
- Approval/write queues should show clear status badges and local Yii3 action buttons for migrated write routes.
- Do not reintroduce Yii2/Kartik/GridView UI patterns; use local `data-table`, `status-badge`, `grid-action`, and Lucide icons.

Officer write flow status:

- `kehadiran/pengesahan` is local Yii3.
- `kehadiran/wfh-lulus-tindakan` is local Yii3.
- `kehadiran/delete-wfh` is local Yii3 and POST/CSRF protected from migrated monitor pages.
- `kehadiran/verify-thtc-sv` and `kehadiran/approve-thtc` are local Yii3 review forms.
- Bulk approval POST in `senarai_tindakan`, bulk WFH approval POST in `wfh-lulus`, staff upload/write flows, WBB setup, and full audit/dashboard parity remain deferred.

## Attendance Supervisor Top Menu Analysis

Source menu:

- Yii3 current menu data: `src/Shared/AttendanceService.php`, `menuSections()`, section key `attendanceSupervisor`
- Yii2 old menu source: `C:\Apache24\htdocs\staff\views\kehadiran\_menu.php`
- Yii2 attendance controller source: `C:\Apache24\htdocs\staff\controllers\KehadiranController.php`
- Yii2 attendance-admin controller source: `C:\Apache24\htdocs\staff\controllers\KehadiranAdminController.php`

Current Yii3 Attendance Supervisor menu items and Yii2 mapping:

| Menu item | Current Yii3 URL | Old Yii2 source | Behavior | Yii3 migration target |
|---|---|---|---|---|
| Staff lists | `http://127.0.0.1:8083/kehadiran/senarai_kakitangan` | `actionSenarai_kakitangan()`, view `senarai_kakitangan.php`, `TblprcobiodataSearch`, `AksesPengguna::CheckAccess()` | Searchable staff list for users with `hrm.cuti_access`; old page exposes WBB, attendance, WBB-list, and Peraku/Lulus actions. | Local read-only list with local attendance report button. WBB setup and Peraku/Lulus setup remain follow-up workflow debt. |
| Staff Working hours | `http://127.0.0.1:8083/kehadiran/staff-wbb` | `actionStaffWbb()`, view `staffWbb.php`, `AksesPengguna`, `SetPegawai`, `TblWp` | Lists active staff in access-scoped departments/campuses; shows current WP, peraku, pelulus, and action buttons for WBB list and set-peg. | Local staff list now opens local `wbb-list` and `set-peg` pages. WBB add/edit/delete writes to `attendance.tbl_wp`; Peraku/Lulus setup writes to `hrm.cuti_set_pegawai`. |
| Monthly Reports | `http://127.0.0.1:8083/kehadiran/sup-mth-rpt` | `actionSupMthRpt()`, view `sup-mth-rpt.php`, `attendance.monthly_rpt` | Month/year report using `attendance.monthly_rpt` filtered by access departments, with optional admin department filter. | High-value read-only route; use attendance standard toolbar, month/year/department filters, table totals. |
| Yearly Reports | `http://127.0.0.1:8083/kehadiran/sup-year-rpt` | `actionSupYearRpt()`, view `sup-year-rpt.php`, `TblRekod::totalWorkingDays()`, `TblRekod::totalComplianceStatus()` | Year report over access-scoped active staff; counts working days and rejected non-compliance by type. | Read-only route after monthly report; can reuse supervisor staff scope and count helpers. |
| Card Colour Report | `http://127.0.0.1:8083/kehadiran/senarai-warna-kad` | `actionSenaraiWarnaKad()`, view `senarai-warna-kad.php`, `TblWarnaKad`, `keselamatan\TblWarnaKad` for dept `2` | Month/year card colour report for access-scoped staff; admin can filter department; print route exists. | Read-only route; preserve dept/campus scope and defer print. Dept `2` security table choice needs schema check before implementation. |
| Card Colour Performance | `http://127.0.0.1:8083/kehadiran/sup-year-performance-rpt` | `actionSupYearPerformanceRpt()`, view `sup-year-perfomance-rpt.php`, `TblWarnaKad::totalByCardColor()`, `prestasiWarnaKad()` | Yearly staff performance by total yellow/green/red cards and performance label. | Read-only route; use local annual card aggregation and existing card performance rule. |
| Pantau Staf | `http://127.0.0.1:8083/kehadiran/pantau-staf` | `actionPantauStaf()`, view `pantau-staf.php`, `AksesPengguna::kakitanganSeliaan()`, `TblRekod` | Lists supervised staff attendance remarks with `remark_status = ENTRY`; old action column links to `kemaskini-peraku`. | Read-only queue with Yii2 handoff for `kemaskini-peraku`; belongs early because it mirrors Officer approval queue style. |
| Shields Status | `http://127.0.0.1:8083/kehadiran/shield-status-list` | `actionShieldStatusList()`, view `shield-status-list.php`, `AksesPengguna::kakitanganSeliaan()`, staff `shields` relation | Shows supervised staff shield colour, answered time, and expiry; empty state says not filled. | Read-only route, but needs shield table/relation mapping before implementation. |
| Staff Shift List | `http://127.0.0.1:8083/kehadiran/staff-shift-list` | `actionStaffShiftList()`, view `staff-shift-list.php`, `attendance.tbl_staffs` | Lets supervisor add/remove shift staff; also lists current shift staff. | Split. First read-only current shift staff list with Yii2 handoffs. Add/remove writes move to shift write slice. |
| Shift Setup | `http://127.0.0.1:8083/kehadiran/shift-setup` | `actionShiftSetup()`, view `shift-setup.php`, `attendance.tbl_staffs`, `attendance.tbl_shift`, `ref_wp` | Month grid of shift assignments for supervisor shift staff, with print and create/update modal actions. | Read-only grid first. Create/update/print remain Yii2 handoffs until shift write/PDF slice. |
| Manage Supervisor/Staff | `http://127.0.0.1:8083/kehadiran-admin/manage-shift-supervisor` | `KehadiranAdminController::actionManageShiftSupervisor()`, view `manage-shift-supervisor.php`, `Tblstaff::search()` | Admin-only list/search across shift supervisors and their staff. | Keep legacy initially, or migrate under admin/supervisor hybrid after admin guard and filters are standardized. |
| Senarai Bulanan WFH | `http://127.0.0.1:8083/kehadiran/wfh-by-mth` | `actionWfhByMth()`, view `wfh-by-mth.php`, `AksesPengguna::kakitanganSeliaan()`, `TblWfh::wfhByDay()` | Month/day WFH vs WFO totals and percentages, with edit-day action. | Monthly summary is local. Edit-day now opens local `/kehadiran/wfh-edit-day` with direct add/remove WFH forms scoped to supervisor staff. |
| Jadual WFH | `http://127.0.0.1:8083/kehadiran/wfh-jadual` | `actionWfhJadual()`, view `wfh-jadual.php`, `AksesPengguna::kakitanganSeliaan()`, `TblWfh::isWfh()` | Month grid per staff with H/O markers and WFH percentage per day; print route exists. | Lightweight staff list first with name/grade/department, staff search, department dropdown, pagination, and `Tindakan` drill-down. Only the selected staff row loads the full monthly WFH grid. |
| Pantau Permohonan WFH | `http://127.0.0.1:8083/kehadiran/pantau-permohonan-wfh` | `actionPantauPermohonanWfh()`, view `pantau-permohonan-wfh.php`, `TblWfh::search()` | Lists WFH applications for staff under supervisor scope, excluding `ADDED BY PENYELIA`, with status/name/remark filters and delete action. | Read monitor route with local Yii3 delete action protected by POST/CSRF from migrated pages. |
| Tidak Hadir Tanpa Cuti (THTC) | `http://127.0.0.1:8083/kehadiran/absent-list-sv` | `actionAbsentListSv()`, view `absent-list-sv.php`, `TblThtcSearch::search()` | Supervisor verification list where `icno_verifier` matches current supervisor, excluding status `6`; write action is `verify-thtc-sv`. | Read list plus local Yii3 supervisor verification review form. |

Attendance Supervisor data and query dependencies:

- `hrm.cuti_access`: supervisor/admin access scope by `akses_cuti_icno`, `akses_cuti_int`, `akses_jspiu_id`, and `akses_kampus_id`.
- `AksesPengguna::kakitanganSeliaan()`: core Yii2 scope for access-scoped active staff.
- `hronline.tblprcobiodata`, `department`, `gredjawatan`: staff list/filter/detail context.
- `attendance.monthly_rpt`: supervisor monthly report source.
- `attendance.tbl_rekod`: yearly reports, `Pantau Staf`, WBB/current attendance links, and THTC record joins.
- `attendance.tbl_warnakad`: card colour monthly and yearly performance reports.
- `attendance.tbl_wfh`: WFH monthly totals, schedules, and application monitor.
- `attendance.tbl_staffs`, `attendance.tbl_shift`, `attendance.ref_wp`: shift staff list and shift setup grid.
- Shield status relation/table needs a schema mapping pass before local implementation.
- `attendance.tbl_thtc`: supervisor THTC verification list.

Recommended Attendance Supervisor migration order:

1. Build shared supervisor access-scope helpers:
   - access rows by `akses_cuti_icno`
   - department/campus arrays
   - active staff list equivalent to `AksesPengguna::kakitanganSeliaan()`
   - admin flag via `akses_cuti_int = 3`
   - optional department filter constrained by admin access
2. Read-only `senarai_kakitangan`; proves scope and filters.
3. Read-only `staff-wbb`; reuses staff scope and existing WBB/current WP helpers.
4. Read-only `sup-mth-rpt`; direct `attendance.monthly_rpt` query, high value.
5. Read-only `sup-year-rpt`; yearly compliance summary over scoped staff.
6. Read-only `senarai-warna-kad`; month/year card colour report.
7. Read-only `sup-year-performance-rpt`; annual card colour performance.
8. Read-only `pantau-staf`; pending remark queue with `kemaskini-peraku` Yii2 handoff.
9. Read-only WFH supervisor monitor:
   - `pantau-permohonan-wfh`
   - `wfh-by-mth`
   - `wfh-jadual`
10. Read-only shift monitor:
   - `staff-shift-list`
   - `shift-setup`
11. Read-only `absent-list-sv`; keep verify/write in Yii2.
12. Defer `shield-status-list` until the shield relation/table is mapped.
13. Defer `kehadiran-admin/manage-shift-supervisor` until admin guard and global shift-management policy are set.

Attendance Supervisor task-by-task execution plan:

1. Supervisor scope foundation:
   - Add shared service methods for access departments/campuses, active scoped staff, admin flag, and department options.
   - Acceptance: a supervisor identity returns the same staff scope as Yii2 `AksesPengguna::kakitanganSeliaan()`.
2. Supervisor page shell:
   - Add a dedicated Supervisor action/template or extend attendance routing with a supervisor mode.
   - Reuse `attendance-standard-*`, `data-table`, status badges, compact filters, and Yii2 handoff actions.
3. Staff lists:
   - Migrate `senarai_kakitangan` read-only with name/grade/department filters.
   - Local attendance report is available at `/kehadiran/supervisor-staff-attendance-report?icno=...`.
   - WBB setup and Peraku/Lulus setup now open local Yii3 pages.
4. Staff Working hours:
   - Migrate `staff-wbb` with peraku, pelulus, current WP, local WBB maintenance, and local Peraku/Lulus setup.
5. Monthly Reports:
   - Migrate `sup-mth-rpt` with year/month/department filters and totals from `attendance.monthly_rpt`.
6. Yearly Reports:
   - Migrate `sup-year-rpt` with yearly totals per staff.
7. Card Colour Reports:
   - Migrate `senarai-warna-kad`.
   - Migrate `sup-year-performance-rpt`.
   - Keep print/export legacy first.
8. Pantau Staf:
   - Migrate read-only pending `remark_status = ENTRY` list for scoped staff.
   - Handoff `kemaskini-peraku` to Yii2.
9. WFH Supervisor:
   - Migrate `pantau-permohonan-wfh` read-only.
   - Migrate `wfh-by-mth` read-only daily totals.
   - Migrate `wfh-jadual` read-only H/O grid.
   - Edit-day is local; print actions remain deferred.
10. Shift Supervisor:
   - Migrate `staff-shift-list` read-only.
   - Migrate `shift-setup` read-only monthly grid.
   - Handoff add/remove/create/update/print actions.
11. THTC Supervisor:
   - Migrate `absent-list-sv` read-only.
   - Wire `verify-thtc-sv` to the local Yii3 review route from the first `STARS-08` write slice.
12. Deferred routes:
   - `shield-status-list`: wait for shield table/relation mapping.
   - `kehadiran-admin/manage-shift-supervisor`: wait for admin/shift-management slice.

Supervisor write flows deferred:

- `kehadiran/kemaskini-peraku`
- `kehadiran/staff-shift-list` POST add staff
- `kehadiran/remove-staff`
- `kehadiran/create-shift`
- `kehadiran/update-shift`
- print/PDF/export routes until the reporting standard is set

Recommended first execution slice:

1. Supervisor scope foundation.
2. `senarai_kakitangan`.
3. `staff-wbb`.
4. `sup-mth-rpt`.

This proves the access model and the supervisor report style with lower risk before WFH/shift/THTC workflows.

2026-11-28 cleanup note:

- Replaced the `senarai_kakitangan` row-level `Kehadiran` Yii2 handoff with a local Yii3 supervisor staff attendance report.
- Added dedicated maintainer-friendly files:
  - `src/Web/Kehadiran/SupervisorStaffAttendanceReportAction.php`
  - `src/Web/Kehadiran/supervisor-staff-attendance-report.php`
- Added `AttendanceService::supervisorStaffAttendanceReport()` with supervisor-scope authorization before reading the target staff report.
- Route added: `/kehadiran/supervisor-staff-attendance-report`.
- Remaining visible Supervisor Yii2 row actions after this slice were: `staff-wbb` WBB/set-peg, `pantau-staf` kemaskini-peraku, shield live status, shift remove/create/update, and WFH edit-day. Later 2026-11-28 slices removed the `staff-wbb` and WFH edit-day handoffs.

2026-11-28 staff-wbb legacy button cleanup:

- Removed the visible Yii2 handoffs from `/kehadiran/staff-wbb`.
- Added local direct routes:
  - `/kehadiran/wbb-list`
  - `/kehadiran/del-wbb`
  - `/kehadiran/set-peg`
- Added maintainer-friendly direct files:
  - `src/Web/Kehadiran/WbbListAction.php`
  - `src/Web/Kehadiran/WbbDeleteAction.php`
  - `src/Web/Kehadiran/SetPegAction.php`
  - `src/Web/Kehadiran/wbb-list.php`
  - `src/Web/Kehadiran/set-peg.php`
- Local WBB maintenance writes only `attendance.tbl_wp`.
- Local Peraku/Lulus setup writes only `hrm.cuti_set_pegawai`.
- This slice follows the migration standard: direct named actions/views, no metadata form loops, and no over-engineered high-level workflow abstraction.

2026-11-28 WFH schedule load cleanup:

- Changed `/kehadiran/wfh-jadual` so the first screen no longer renders the full month grid for every staff member.
- The page now lists staff name, grade, and department only, with staff search, department dropdown filter, pagination, and a `Tindakan` link.
- Clicking `Tindakan` reloads the same route with the selected staff ICNO and then renders the full monthly WFH grid for that staff member only.
- `AttendanceService::supervisorWfhSchedule()` now skips `wfhDaysFor()` until a selected staff ICNO is present.

2026-11-28 WFH edit-day cleanup:

- Replaced the `/kehadiran/wfh-by-mth` `Edit Day` Yii2 handoff with local `/kehadiran/wfh-edit-day`.
- Added dedicated maintainer-friendly files:
  - `src/Web/Kehadiran/WfhEditDayAction.php`
  - `src/Web/Kehadiran/wfh-edit-day.php`
- Added `AttendanceService::supervisorWfhDay()` and `saveSupervisorWfhDay()` for the old edit-day behavior:
  - add selected scoped staff to `attendance.tbl_wfh` as one-day `APPROVED` WFH records with remark `ADDED BY PENYELIA`
  - remove selected scoped WFH records to change them to WFO
- No live POST/write was executed during verification.

### STARS-06 - Staff Read-Only Routes

- [x] Completion

Goal:

- Migrate the low-risk staff-facing read routes before any approval/write flows.

Candidate routes:

- `/kehadiran/laporan-kehadiran`
- `/kehadiran/tindakan_ketidakpatuhan`
- `/kehadiran/staf-color-list`
- `/kehadiran/wfh-list`
- `/kehadiran/absent-list`
- `/kehadiran/rekod-warna-kad`
- `/kehadiran/senarai-kehadiran`

Work:

- Add query methods to `AttendanceService`.
- Create Yii3 action/template coverage for the selected staff routes.
- Keep links from landing page routed locally as each route is completed.

Acceptance:

- Staff can see the same rows/counts as Yii2 for selected test identity.
- No update/delete/approval POST is included in this phase.

Verification:

- Compare Yii2 vs Yii3 row counts and key field values.
- Browser smoke each route.

Done when:

- [x] Staff report routes selected for this task are listed.
- [x] Each selected route has Yii3 action/template coverage.
- [x] Yii3 browser smoke passes for the chosen staff identity.
- [x] No update/delete/approval POST is included.

Implementation notes:

- Implemented Staff routes in Yii3:
  - `/kehadiran/laporan-kehadiran`
  - `/kehadiran/tindakan_ketidakpatuhan`
  - `/kehadiran/staf-color-list`
  - `/kehadiran/kad-warna`
  - `/kehadiran/kad_warna`
  - `/kehadiran/wfh-list`
  - `/kehadiran/absent-list`
- Copied required static assets into Yii3 public paths:
  - `public/images/kad_warna.png`
  - `public/files/garis_panduan_kehadiran.pdf`
- Updated the Staff top menu so migrated Staff links are local Yii3 links and no longer show `Yii2` badges.
- Kept write/upload routes as Yii2 handoffs:
  - `kehadiran/remark`
  - `kehadiran/remark-thtc`
  - old detail/report print URLs.
- Verification on `2026-11-19`:
  - `php -l` passed for changed PHP files.
  - `php yii list` passed.
  - Authenticated browser smoke returned HTTP 200 for every migrated Staff route using `staff-940402125181`.
  - Static `/images/kad_warna.png` and `/files/garis_panduan_kehadiran.pdf` returned HTTP 200.

### STARS-07 - Officer And Supervisor Read Reports

- [x] Completion

Goal:

- Move Officer menu pages that are read-heavy or can be safely presented as read-only before migrating approval writes.

Candidate routes:

- `/kehadiran/staff-color-card`
- `/kehadiran/pantau-kehadiran`
- `/kehadiran/monthly-summary`
- `/kehadiran/pantau-permohonan-wfh-pegawai`
- `/kehadiran/wfh-lulus`
- `/kehadiran/senarai_tindakan`
- `/kehadiran/absent-list-kp`

Keep as legacy handoff until API decision:

- `/cuti/pegawai/staffonleave`

Acceptance:

- Access is allowed only for matching Officer/department PP role scope.
- Supervised staff scope matches Yii2 `hrm.cuti_set_pegawai` behavior.
- Approval/write pages are read-only in Yii3 and clearly hand off write actions to Yii2.
- Every migrated page follows the `attendance-standard-*` UI standard.

Verification:

- Role matrix checks for ordinary staff, Officer, department PP, delegated approver.
- Yii2 vs Yii3 row counts for selected Officer identity.
- Yii2 vs Yii3 key field comparison for selected rows.

Done when:

- [x] Officer report/queue routes selected for this task are listed.
- [x] Each selected route has a Yii3 action/template.
- [x] Access is denied for ordinary staff where appropriate.
- [x] Officer and department PP scopes match Yii2 for chosen identities.
- [x] Write actions remain legacy handoffs or are explicitly deferred.

Implemented on 2026-11-19:

- Added `src/Web/Kehadiran/OfficerAction.php` and `src/Web/Kehadiran/officer-page.php`.
- Added local routes for `/kehadiran/staff-color-card`, `/kehadiran/pantau-kehadiran`, `/kehadiran/monthly-summary`, `/kehadiran/pantau-permohonan-wfh-pegawai`, `/kehadiran/wfh-lulus`, `/kehadiran/senarai_tindakan`, and `/kehadiran/absent-list-kp`.
- Updated the Officer menu to local Yii3 links for migrated read pages.
- Kept `/cuti/pegawai/staffonleave` as a Yii2 handoff because it depends on the Registrar team API.
- Migrated the first individual approval/delete actions to local Yii3 routes; heavier bulk/write/export routes remain deferred.
- Verification:
  - `php -l` passed for changed PHP files.
  - Service smoke for Officer ICNO `790608125733` returned 61 supervised staff rows across card, monthly, and daily reports.
  - KP/THTC service smoke for PP ICNO `780123125699` returned authorized `yes` and 802 department rows.
  - Apache unauthenticated smoke redirects all new routes to `/hronline/login` cleanly.
  - Authenticated Apache smoke using Officer ICNO `790608125733` returned HTTP 200 for all new Officer routes and confirmed the Attendance UI standard toolbar renders.

Remaining read-report slices:

- Head of Department: tracked in `STARS-07B`.
- Administrator: tracked in `STARS-07C`.
- Reports/export behavior: tracked in `STARS-07D`.

### STARS-08 - Reason And Approval Writes

- [ ] Completion

Goal:

- Migrate write flows after read services are proven.

Candidate routes:

- `/kehadiran/remark`
- `/kehadiran/senarai_tindakan`
- `/kehadiran/pengesahan`
- `/kehadiran/remark-thtc`
- `/kehadiran/verify-thtc-sv`
- `/kehadiran/approve-thtc`
- `/kehadiran/kemaskini-peraku`
- `/kehadiran/kemaskini-warna-kad`

Work:

- Introduce explicit command methods with transactions.
- Port notification/log side effects deliberately.
- Add CSRF-protected POST routes.

Acceptance:

- Each write is scoped to the correct identity and role.
- Every status transition matches old allowed states.
- No GET route mutates data.

Verification:

- Empty-submit validation.
- Happy path on test/dummy rows only.
- Audit/log side-effect checks.

Done when:

- [x] First write slice routes are POST/CSRF protected where data changes.
- [x] First write slice status transitions match old allowed states.
- [x] First write slice authorization is scoped to the current identity and role.
- [ ] Notification/log side effects are verified on test/dummy rows.
- [x] No first-slice GET route mutates data.

Execution notes:

- Added `src/Web/Kehadiran/WriteAction.php` and `src/Web/Kehadiran/write-page.php`.
- Added local Yii3 write routes for `/kehadiran/pengesahan`, `/kehadiran/wfh-lulus-tindakan`, `/kehadiran/delete-wfh`, `/kehadiran/verify-thtc-sv`, and `/kehadiran/approve-thtc`.
- Added AttendanceService command methods for individual reason approval, WFH approval/reject, WFH delete, supervisor THTC verification, and HOD THTC approval.
- Wrapped first-slice update/delete commands in the project transaction pattern.
- Migrated visible WFH delete buttons to POST forms with CSRF tokens.
- Kept notification/pending-task refresh best-effort; dummy-row verification is still required before marking full `STARS-08` complete.

### STARS-09 - WFH/WFO And Shift Admin

- [ ] Completion

Goal:

- Migrate the heavier scheduling and admin-maintenance flows.

Candidate areas:

- WFH approval/peraku/lulus flows
- WFH monthly list and jadual pages
- WFH day editing
- PPV day editing
- shift setup
- staff shift list
- manage supervisor/staff

Risks:

- Multiple delete/save loops in old code.
- Cross-module performance queries.
- PDF/print routes.
- Role scope is easy to accidentally broaden.

Acceptance:

- Staff scope and status updates match Yii2.
- Bulk update/delete actions are POST-only and CSRF-protected.

Verification:

- Test supervisor and admin identities.
- Dummy records only for destructive flows.

Done when:

- [ ] Selected WFH/WFO/shift routes are listed.
- [ ] Bulk update/delete actions are POST/CSRF protected.
- [ ] Staff scope matches Yii2 for supervisor and admin identities.
- [ ] Destructive flows are verified only on dummy records.

### STARS-10 - API Migration Decision

- [ ] Completion

Goal:

- Decide whether the old mobile/API attendance endpoints move into this Yii3 app.

Old active API endpoints:

- `clock-in`
- `clock-out`
- `get-info`
- `get-working-hours`
- `get-attendance-list-month`
- `delete-record`
- `remark-attendance`
- `reason-list`
- `get-pending-remark-list`
- `get-list-staff-remark`
- `update-remark`

Decision needed:

- Keep the mobile API on old Yii2 until the web STARS module is stable, or migrate it as a separate API namespace with the same auth semantics as `HRApiAuth`.

Recommendation:

- Do not include API migration in the first sidebar cutover.

Done when:

- [ ] Decision is recorded: keep API on Yii2 or migrate to Yii3.
- [ ] If migrating, API auth strategy is documented.
- [ ] If staying on Yii2, the Yii3 UI keeps any needed API assumptions out of scope.

### STARS-11 - Legacy Cleanup

- [ ] Completion

Goal:

- Remove the need for the Yii2 STARS handoff only after the core slices are local.

Work:

- Update `MenuService::LOCAL_ROUTE_MAP` for migrated `kehadiran/*` routes.
- Remove legacy `Yii2` badges from migrated STARS links.
- Document intentionally unmigrated endpoints.

Acceptance:

- Sidebar STARS and DB-backed Menu STARS links both open Yii3 routes.
- No broken STARS route links remain in the Yii3 UI.

Done when:

- [ ] `MenuService::LOCAL_ROUTE_MAP` includes migrated `kehadiran/*` routes.
- [ ] Migrated STARS links no longer show legacy `Yii2` badges.
- [ ] Intentionally unmigrated endpoints are documented.
- [ ] Sidebar and DB-backed Menu both open Yii3 for migrated STARS routes.

## First Implementation Slice

Recommended first executable slice:

1. Add `AttendanceService::landingForIdentity()`.
2. Add `GET /kehadiran/index`.
3. Add Yii3 STARS landing template.
4. Change sidebar STARS href to `/kehadiran/index`.
5. Leave all internal STARS menu actions as legacy links.

This gives immediate user-visible migration value while avoiding high-risk attendance writes.

## Open Questions

- Which staff identities should be used for staff, officer, supervisor, department chief, and admin browser comparisons?
- Should Yii3 expose the STARS mobile/API endpoints, or should those remain on Yii2 until a separate API migration phase?
- Are PDF/print outputs required to match the old exact layout, or only the data/content?
- Is the old `hrv4.tbl_files` dependency still used by active STARS pages, or only legacy upload flows?

## Verification Checklist

- `php -l` on every new/changed PHP file.
- `php yii list` after route wiring.
- Unauthenticated `/kehadiran/index` redirects to `/login`.
- Authenticated `/kehadiran/index` returns 200.
- Current staff sees expected attendance/card-colour summary.
- Role menu sections match old Yii2 for chosen test identities.
- Sidebar `STARS` click opens Yii3 and highlights the side-menu item.
- Non-migrated STARS sub-links still open old Yii2 successfully.
