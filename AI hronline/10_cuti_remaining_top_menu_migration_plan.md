# Cuti Remaining Top Menu Migration Plan

Date: 2026-11-20

## Scope

This plan covers Cuti top-menu sections that are still not migrated into this Yii3 app after the Staff Menu slice.

Current Yii3 state:

- Main Cuti entry opens local Yii3 `/cuti/individu/index`.
- Staff Menu routes are mostly local Yii3.
- Staff support actions for maternity `surat`, upload, cancel, PDF, and selected risky writes still use Yii2 fallback.
- Officer, Supervisor, Admin, Report, Pentadbiran, and Pemantau top-menu routes still use `Web\Cuti\Shared\LegacyRedirectAction`.
- Old Yii2 also has a `Sector HR` top-menu section for `manage/list`; this is not represented in the current Yii3 `CutiMenuService` and must be added as a visible legacy fallback before final cleanup.

Out of scope for this plan:

- Cuti Belajar, Cuti Penyelidikan, and Cuti Sabatikal as standalone modules.
- STARS/Kehadiran implementation work, except using the STARS UI and migration standards as the model.
- Mobile/API Cuti endpoints until the web services are stable.

## Source Files Reviewed

Yii2 source:

- `C:\Apache24\htdocs\staff\views\cuti\_menu.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\PegawaiController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\SupervisorController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\ObserverController.php`
- `C:\Apache24\htdocs\staff\controllers\cuti\AdminController.php`
- `C:\Apache24\htdocs\staff\controllers\ManageController.php`
- `C:\Apache24\htdocs\staff\views\cuti\pegawai`
- `C:\Apache24\htdocs\staff\views\cuti\supervisor`
- `C:\Apache24\htdocs\staff\views\manage`
- `C:\Apache24\htdocs\staff\models\cuti`

Yii3 source:

- `src/Shared/CutiMenuService.php`
- `src/Shared/CutiAccessService.php`
- `src/Web/Cuti/Shared/menu.php`
- `src/Web/Cuti/Shared/LegacyRedirectAction.php`
- `config/common/routes.php`
- `assets/main/site.css`
- `AI hronline/05_cuti_migration_plan.md`
- `AI hronline/08_cuti_staff_menu_migration_plan.md`
- `AI hronline/04_stars_migration_plan.md`
- `AI hronline/progress.md`

## Standards Contract

### Global Yii3 Standard

Follow the agreed Yii3 Tailwind/Flowbite-like no-build CSS standard:

- Use one `page-main-card` per page.
- Use internal `page-card-section` sections, not floating nested page cards.
- Put list/detail data in `content-inner-card` panels.
- Use Lucide icons for action buttons and menu controls.
- Keep compact density, soft borders, small radius, and light shadows.
- Do not introduce Bootstrap/AdminLTE/Gentelella/Kartik/jQuery widget markup.
- Do not show ICNO/UMSPER in card headers unless a page explicitly requires it.
- Mutating actions must be POST-only, CSRF-protected, identity-scoped, and transaction-wrapped.
- Use the global popup/flash standard for success/failure messages rather than old inline Yii2 alert strips.

### STARS Top Menu Standard Applied To Cuti

Use the STARS read-first migration standard for these Cuti top-menu pages:

- Keep the module top menu directly below the page heading.
- Use local Yii3 links only for migrated routes.
- Keep non-migrated links as Yii2 handoffs with a visible `Yii2` badge.
- Migrated list/report pages should start with a compact toolbar:
  - left side: Lucide icon, page title, short context/subtitle
  - right side: filters/actions or a concise count/status block
- Reuse the `attendance-standard-*` toolbar classes unless a future shared `cuti-standard-*` alias is added:
  - `attendance-standard-toolbar`
  - `attendance-standard-title`
  - `attendance-standard-icon`
  - `attendance-standard-filter`
  - `attendance-standard-fields`
  - `attendance-standard-actions`
  - `attendance-standard-count`
- Data goes below the toolbar in responsive `data-table` tables with status badges and compact icon actions.
- Read-only pages come before writes. Approval/reject/delete/edit/PDF/upload side effects remain Yii2 handoffs until the dedicated write slice.

## Current Gap Snapshot

| Top menu | Current Yii3 route status | Main risk | Migration approach |
| --- | --- | --- | --- |
| Staff Menu | Mostly local Yii3 | Remaining PDF/upload/cancel writes | Finish support actions separately; do not block remaining top menus. |
| Officer Menu | All top-menu routes legacy | Approval writes, GCR side effects, pending task refresh | Migrate read queues/lists first, then approval commands. |
| Supervisor Menu | All top-menu routes legacy | Broad staff scope, entitlement edits, manual leave writes, PDFs | Build scope foundation, then read-only reports/calendars, then writes. |
| Admin Menu | All top-menu routes legacy | Access management, open windows, public holidays, absent files, destructive deletes | Read-only/admin dashboards first, then settings writes. |
| Report | All routes legacy | Large date/dept aggregation and export/PDF parity | Migrate after shared report helpers and admin scope are proven. |
| Sector HR | Missing from Yii3 Cuti menu | Uses `ManageController`, `hrm.cuti_tbl_management`, and `system_core.tbl_user_access` | Add legacy menu entry first; migrate list, then add/edit/delete. |
| Pentadbiran | Legacy | HSO report/action behavior inside `PegawaiController::actionReport()` | Migrate read-only list after officer service exists. |
| Pemantau | Legacy | Observer scope and many inherited supervisor write actions | Migrate `my-staff` as read-only using observer scope. |

## Route Inventory And Target Classification

### Officer Menu

| Label | Yii2 route | First Yii3 target | Classify |
| --- | --- | --- | --- |
| Pending Verification List | `cuti/pegawai/senarai-peraku` | `GET /cuti/pegawai/senarai-peraku` | Read queue first; `peraku`/`reject-peraku` stay Yii2 until write slice. |
| Pending Approval List | `cuti/pegawai/senarai-lulus` | `GET /cuti/pegawai/senarai-lulus` | Read queue first; `lulus`/`reject` stay Yii2 until write slice. |
| GCR & CBTH Application List | `cuti/pegawai/gcr-list-checked` | `GET /cuti/pegawai/gcr-list-checked` | Read queue first; `gcr-verify` stays Yii2. |
| Approved Leave List | `cuti/pegawai/list` | `GET /cuti/pegawai/list` | Read-only report/list. |
| Staff Under My Supervision | `cuti/pegawai/staff-leave` | `GET /cuti/pegawai/staff-leave` | Read-only staff leave view. |
| GCR & CBTH Application List (Ketua Jabatan BSM) | `cuti/pegawai/sort` | `GET /cuti/pegawai/sort` | Read queue for Department 230 chief; verify action stays Yii2. |
| Back On Duty | `cuti/pegawai/bod-list` | `GET /cuti/pegawai/bod-list` | Read list first; BOD POST stays Yii2. |

Officer dependencies:

- `hrm.cuti_set_pegawai`
- `hrm.cuti_behalf_action`
- `hrm.cuti_tbl_records`
- `hrm.cuti_gcr_application`
- `hrm.cuti_tbl_bod`
- `hronline.tblprcobiodata`
- `hronline.department`
- Cuti logs, notifications, and pending task refresh for write flows

### Supervisor Menu

| Label | Yii2 route | First Yii3 target | Classify |
| --- | --- | --- | --- |
| Sick Leave List | `cuti/supervisor/sick-leave-list` | `GET /cuti/supervisor/sick-leave-list` | Read queue first; sick-leave approval stays Yii2. |
| Supervised Staff | `cuti/supervisor/supervised-staff` | `GET /cuti/supervisor/supervised-staff` | Read-only staff scope foundation. |
| Monthly Statement | `cuti/supervisor/leave-statement-report` | `GET /cuti/supervisor/leave-statement-report` | Read-only report; print/PDF legacy. |
| Monitor Staff Leave | `cuti/supervisor/leave-monitoring` | `GET /cuti/supervisor/leave-monitoring` | Read-only monitor. |
| Leave Calendar | `cuti/supervisor/calender` | `GET /cuti/supervisor/calender` | Read-only month grid/calendar; print legacy. |
| GCR & CBTH Application List | `cuti/supervisor/gcr-list` | `GET /cuti/supervisor/gcr-list` | Read queue first; verify stays Yii2. |
| Reset GCR & CBTH Application | `cuti/supervisor/gcr-list-checked` | `GET /cuti/supervisor/gcr-list-checked` | Read queue first; reset stays Yii2. |
| Supervisor List | `cuti/admin/access` | Keep with Admin access slice | Shared admin-maintenance route. |

Supervisor dependencies:

- `hrm.cuti_access` with `akses_cuti_int != 4`, `akses_jspiu_id`, and `akses_kampus_id`
- `hrm.cuti_tbl_records`
- `hrm.cuti_entitlement`
- `hrm.cuti_gcr_application`
- `hrm.cuti_public_holiday`
- `hronline.tblprcobiodata`
- `hronline.department`
- PDF templates for statements/calendars/leave forms

### Admin Menu And Report

| Label | Yii2 route | First Yii3 target | Classify |
| --- | --- | --- | --- |
| Verify | `cuti/admin/cr2-list` | `GET /cuti/admin/cr2-list` | Read queue first; verify/reject writes later. |
| On Behalf Of | `cuti/admin/behalf-list` | `GET /cuti/admin/behalf-list` | Read first; add/update access later. |
| Access | `cuti/admin/access` | `GET /cuti/admin/access` | Read first; access CRUD later. |
| GCR Application List | `cuti/admin/gcr-application-list` | `GET /cuti/admin/gcr-application-list` | Read/admin list first; update/delete later. |
| Buka Permohonan GCR & CBTH | `cuti/admin/open-application` | `GET /cuti/admin/open-application` | Read first; open-window writes later. |
| Public Holiday Calendar | `cuti/admin/ph-list` | `GET /cuti/admin/ph-list` | Read calendar first; add/update/copy/delete later. |
| Maternity Leave | `cuti/admin/cb-list` | `GET /cuti/admin/cb-list` | Read list first; PDF/upload/write later. |
| Add Apply GCR After Closed Date | `cuti/admin/admin-index` | `GET /cuti/admin/admin-index` | Read allowlist first; add/update/delete later. |
| Ketidakhadiran (Absent) | `cuti/admin/staff-list` | `GET /cuti/admin/staff-list` | Read staff list first; absent report/upload/amnesty later. |
| Report by Department (Date Range) | `cuti/admin/report` | `GET /cuti/admin/report` | Read report; export/PDF later. |
| Statistic by Leave Type | `cuti/admin/report-leave-list` | `GET /cuti/admin/report-leave-list` | Read aggregation; export later. |

Admin dependencies:

- `hrm.cuti_access`
- `hrm.cuti_behalf_action`
- `hrm.cuti_open_application`
- `hrm.cuti_public_holiday`
- `hrm.cuti_temp_table`
- `hrm.cuti_tbl_records`
- `hrm.cuti_tbl_bod`
- `hrm.cuti_tbl_management`
- `attendance.tbl_rekod` and absent/THTC data for absence-related flows
- old FileManager/PDF paths for maternity and absence documents

### Sector HR, Pentadbiran, Pemantau

| Top menu | Yii2 route | First Yii3 target | Classify |
| --- | --- | --- | --- |
| Sector HR / Manage(Surat) | `manage/list` | Add legacy menu entry; later `GET /manage/list` or Cuti-owned route | Missing from Yii3 menu; list uses `TblManagement`, access uses `system_core.tbl_user_access`. |
| Pentadbiran / Senarai Permohonan HSO | `cuti/pegawai/report` | `GET /cuti/pegawai/report` | Read HSO list/report first; write/PDF side effects later. |
| Pemantau / Senarai Kakitangan Seliaan | `cuti/observer/my-staff` | `GET /cuti/observer/my-staff` | Read-only observer staff scope first. |

## Proposed Yii3 Structure

Services:

- `src/Shared/CutiOfficerService.php`
- `src/Shared/CutiSupervisorService.php`
- `src/Shared/CutiObserverService.php`
- `src/Shared/CutiAdminService.php`
- `src/Shared/CutiReportService.php`
- `src/Shared/CutiSectorHrService.php`
- `src/Shared/CutiWorkflowCommandService.php`
- `src/Shared/CutiPdfService.php` later, after the PDF standard is chosen
- `src/Shared/CutiFileService.php` later, after FileManager compatibility is confirmed

Web actions/templates:

- `src/Web/Cuti/Pegawai/*`
- `src/Web/Cuti/Supervisor/*`
- `src/Web/Cuti/Observer/*`
- `src/Web/Cuti/Admin/*`
- `src/Web/Cuti/Report/*` if admin reports grow too large for Admin
- `src/Web/Cuti/Manage/*` or `src/Web/Cuti/SectorHr/*` for `Manage(Surat)`, after route ownership is confirmed

Routing policy:

- Add local route mappings to `CutiMenuService::LOCAL_ROUTES` only after the route is implemented and smoke-tested.
- Keep all non-migrated links as `LegacyUrl::to(...)`.
- Mutating routes should use `Route::post(...)` where possible; only use `methods(['GET','POST'])` for true forms, with POST doing the mutation.

## Task Board

Use this board task-by-task. A task is complete only when its implementation, verification, and documentation boxes are all checked.

### Execution Ledger - Yii3 Read-Only Top Menu Pass

- [x] `CTI-TOP-002` Sector HR menu visibility and `Manage(Surat)` route restored.
- [x] `CTI-TOP-003` Shared Cuti page shell pattern applied to new read-only pages.
- [x] `CTI-TOP-004` Officer read-only service foundation added.
- [x] `CTI-TOP-005` through `CTI-TOP-011` Officer top-menu read pages routed to Yii3.
- [x] `CTI-TOP-012` Supervisor read-only service foundation added.
- [x] `CTI-TOP-013`, `CTI-TOP-015` through `CTI-TOP-019` Supervisor top-menu read pages routed to Yii3.
- [x] `CTI-TOP-014` Pemantau/Observer staff list routed to Yii3.
- [x] `CTI-TOP-020` Admin read-only service foundation added.
- [x] `CTI-TOP-021` through `CTI-TOP-031` Admin and Report top-menu read pages routed to Yii3.
- [x] `CTI-TOP-032` Pentadbiran HSO read page routed to Yii3.
- [x] `CTI-TOP-033` Sector HR `Manage(Surat)` read page routed to Yii3 at `/manage/list`.
- [x] Syntax checks passed for changed PHP files.
- [x] `php yii list` passed after route wiring.
- [x] Unauthenticated HTTP smokes redirect to `/hronline/login` for migrated route groups.
- [ ] Authenticated role-matrix smokes still required for Officer, Supervisor, Observer, Admin, Pentadbiran, and Sector HR identities.
- [ ] Yii2 row-count/key-field comparisons still required before removing legacy action handoffs.
- [ ] Write-command tasks `CTI-TOP-034` through `CTI-TOP-036` remain deferred until safe fixtures are identified.
- [ ] Cross-cutting PDF/file/notification parity task `CTI-TOP-037` remains deferred.

| Task | Status | Area | Output |
| --- | --- | --- | --- |
| CTI-TOP-001 | `[ ]` | Baseline | Remaining route inventory and test identities confirmed. |
| CTI-TOP-002 | `[ ]` | Menu | Missing `Sector HR` menu restored as Yii2 fallback. |
| CTI-TOP-003 | `[ ]` | UI | Shared Cuti top-menu page shell ready. |
| CTI-TOP-004 | `[ ]` | Officer | Officer service foundation. |
| CTI-TOP-005 | `[ ]` | Officer | Pending Verification read page. |
| CTI-TOP-006 | `[ ]` | Officer | Pending Approval read page. |
| CTI-TOP-007 | `[ ]` | Officer | Officer GCR/CBTH checked-list read page. |
| CTI-TOP-008 | `[ ]` | Officer | Approved Leave List read page. |
| CTI-TOP-009 | `[ ]` | Officer | Staff Under My Supervision read page. |
| CTI-TOP-010 | `[ ]` | Officer | Department 230 BSM GCR sort read page. |
| CTI-TOP-011 | `[ ]` | Officer | Back On Duty read page. |
| CTI-TOP-012 | `[ ]` | Supervisor | Supervisor scope foundation. |
| CTI-TOP-013 | `[ ]` | Supervisor | Supervised Staff read page. |
| CTI-TOP-014 | `[ ]` | Pemantau | Observer `my-staff` read page. |
| CTI-TOP-015 | `[ ]` | Supervisor | Sick Leave List read page. |
| CTI-TOP-016 | `[ ]` | Supervisor | Monthly Statement report page. |
| CTI-TOP-017 | `[ ]` | Supervisor | Monitor Staff Leave page. |
| CTI-TOP-018 | `[ ]` | Supervisor | Leave Calendar page. |
| CTI-TOP-019 | `[ ]` | Supervisor | Supervisor GCR/CBTH list page. |
| CTI-TOP-020 | `[ ]` | Admin | Admin service foundation. |
| CTI-TOP-021 | `[ ]` | Admin | CR2 Verify read page. |
| CTI-TOP-022 | `[ ]` | Admin | On Behalf Of read page. |
| CTI-TOP-023 | `[ ]` | Admin | Access read page. |
| CTI-TOP-024 | `[ ]` | Admin | Admin GCR Application read page. |
| CTI-TOP-025 | `[ ]` | Admin | Open GCR/CBTH application read page. |
| CTI-TOP-026 | `[ ]` | Admin | Public Holiday Calendar read page. |
| CTI-TOP-027 | `[ ]` | Admin | Maternity Leave read page. |
| CTI-TOP-028 | `[ ]` | Admin | GCR after closed date allowlist read page. |
| CTI-TOP-029 | `[ ]` | Admin | Ketidakhadiran staff read page. |
| CTI-TOP-030 | `[ ]` | Report | Department date-range report. |
| CTI-TOP-031 | `[ ]` | Report | Leave-type statistic report. |
| CTI-TOP-032 | `[ ]` | Pentadbiran | HSO application list/report. |
| CTI-TOP-033 | `[ ]` | Sector HR | Manage(Surat) read page. |
| CTI-TOP-034 | `[ ]` | Officer Writes | Officer approval command foundation and first safe write. |
| CTI-TOP-035 | `[ ]` | Supervisor Writes | Supervisor command foundation and first safe write. |
| CTI-TOP-036 | `[ ]` | Admin Writes | Admin settings command foundation and first safe write. |
| CTI-TOP-037 | `[ ]` | Side Effects | PDF/file/notification/pending-task parity layer. |
| CTI-TOP-038 | `[ ]` | Cleanup | Legacy cleanup and final status update. |

## Executable Tasks

### CTI-TOP-001 - Baseline Route Inventory And Test Identities

Status: `[ ]`

Goal:

- Freeze the exact Yii2 route behavior and pick identities before changing code.

Work:

- Confirm every remaining top-menu route from `views/cuti/_menu.php`.
- Record Yii2 action, view, tables, access rule, and write side effects for each route.
- Pick test identities for normal staff, officer, supervisor, admin, department PP/chief, observer, and Sector HR.
- Add row-count SQL probes for the first Officer, Supervisor, Admin, Report, Observer, and Sector HR pages.

Completion:

- [ ] Route inventory updated in this plan.
- [ ] Test identities recorded.
- [ ] Old Yii2 source mappings recorded.
- [ ] Initial DB row-count probes recorded.
- [ ] No Yii3 behavior changed.

Verification:

- [ ] `git status --short` recorded before/after.
- [ ] At least one old Yii2 source/action path is listed for every remaining menu item.

### CTI-TOP-002 - Restore Missing Sector HR Menu Fallback

Status: `[ ]`

Goal:

- Make Yii3 Cuti menu complete before deeper migration.

Files:

- `src/Shared/CutiAccessService.php`
- `src/Shared/CutiMenuService.php`
- `config/common/routes.php` if a local legacy redirect route is needed

Work:

- Add `CutiAccessService::isSectorHrUser($icno)` matching old `ManageController` access through `system_core.tbl_user_access`.
- Add `Sector HR` section with `Manage(Surat)` route `manage/list`.
- Keep it as Yii2 fallback until a local Manage page exists.

Completion:

- [ ] `Sector HR` appears for users in `system_core.tbl_user_access`.
- [ ] `Sector HR` is hidden for ordinary staff.
- [ ] `Manage(Surat)` opens Yii2 legacy fallback.
- [ ] No other Cuti menu visibility changes unexpectedly.

Verification:

- [ ] `php -l src/Shared/CutiAccessService.php`
- [ ] `php -l src/Shared/CutiMenuService.php`
- [ ] Authenticated menu render smoke for Sector HR and non-Sector HR identities.

### CTI-TOP-003 - Shared Cuti Top-Menu Page Shell

Status: `[ ]`

Goal:

- Create the standard shell used by new non-Staff Cuti pages.

Files:

- `src/Web/Cuti/Shared/*`
- `assets/main/site.css` only if existing classes are insufficient

Work:

- Add or reuse a Cuti page template pattern with heading, back action, Cuti top menu, toolbar, and inner data panel.
- Use the STARS `attendance-standard-*` toolbar pattern for filters/counts.
- Keep legacy badges for unmigrated actions.

Completion:

- [ ] One reusable shell/partial or documented template pattern exists.
- [ ] Page structure follows one `page-main-card`.
- [ ] Data areas use `content-inner-card`.
- [ ] Module menu stays directly below the page heading.
- [ ] No Bootstrap/AdminLTE/Gentelella/Kartik/jQuery widget markup.

Verification:

- [ ] Browser or HTTP smoke of one existing Cuti page still renders.
- [ ] Visual check confirms no text overlap at desktop/mobile widths.

### CTI-TOP-004 - Officer Service Foundation

Status: `[ ]`

Goal:

- Add shared read-only officer scope methods before any Officer pages.

Files:

- `src/Shared/CutiOfficerService.php`

Work:

- Add officer authorization from `hrm.cuti_set_pegawai` and `hrm.cuti_behalf_action`.
- Add helpers for verifier/approver scoped leave records.
- Add Department 230 chief helper for BSM GCR routes.
- Return arrays suitable for templates; no HTML in service.

Completion:

- [ ] Officer authorization method added.
- [ ] Pending verification query added.
- [ ] Pending approval query added.
- [ ] Approved leave list query added.
- [ ] Department 230/BSM scope query added.
- [ ] No write methods added.

Verification:

- [ ] `php -l src/Shared/CutiOfficerService.php`
- [ ] CLI/service probe for chosen officer returns expected non-error arrays.
- [ ] Ordinary staff probe fails closed.

### CTI-TOP-005 - Officer Pending Verification Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/senarai-peraku`

Files:

- `src/Web/Cuti/Pegawai/*`
- `config/common/routes.php`
- `src/Shared/CutiMenuService.php`

Work:

- Render records waiting for current officer verification.
- Include applicant, leave type, dates, duration, substitute/verifier/approver context, status, and Yii2 handoff action.
- Keep `peraku` and `reject-peraku` as Yii2 handoffs.

Completion:

- [ ] Route added.
- [ ] Action added.
- [ ] Template added.
- [ ] Menu route switches local after smoke passes.
- [ ] Yii2 badge removed only for the migrated list link.
- [ ] Write actions remain Yii2 handoffs.

Verification:

- [ ] `php -l` changed PHP files.
- [ ] `php yii list`
- [ ] Unauthenticated request redirects to login.
- [ ] Officer authenticated request returns 200.
- [ ] Ordinary staff is denied or sees a clean empty/unauthorized state.
- [ ] Row count/key fields compared with Yii2.

### CTI-TOP-006 - Officer Pending Approval Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/senarai-lulus`

Work:

- Same structure as CTI-TOP-005, but approval-scoped records.
- Keep `lulus` and `reject` as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Local menu mapping added after smoke passes.
- [ ] Approval/reject actions remain Yii2 handoffs.
- [ ] Row count compared with Yii2.

Verification:

- [ ] `php -l` changed PHP files.
- [ ] `php yii list`
- [ ] Authenticated officer smoke returns 200.
- [ ] Unauthorized smoke fails closed.

### CTI-TOP-007 - Officer GCR/CBTH Checked List Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/gcr-list-checked`

Work:

- Render GCR/CBTH applications assigned to the officer.
- Show GCR year, applicant, applied GCR/CBTH, verifier/approver fields, status, request date, and Yii2 verify handoff.

Completion:

- [ ] Route/action/template added.
- [ ] GCR officer query added.
- [ ] `gcr-verify` remains Yii2 handoff.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Officer smoke returns 200.
- [ ] Yii2 row count/key fields compared.

### CTI-TOP-008 - Officer Approved Leave List Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/list`

Work:

- Render approved leave list scoped to officer access.
- Preserve old year/type/staff filters where present.
- Keep detail/letter/PDF links as Yii2 handoffs unless already local and safe.

Completion:

- [ ] Route/action/template added.
- [ ] Filters implemented.
- [ ] Detail/PDF support actions classified.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Officer smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-009 - Officer Staff Under My Supervision Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/staff-leave`

Work:

- Render supervised staff list based on `hrm.cuti_set_pegawai`.
- Link per-staff leave history to local route only if implemented; otherwise Yii2 handoff.

Completion:

- [ ] Route/action/template added.
- [ ] Staff scope query added.
- [ ] Per-staff links classified local/legacy.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Officer smoke returns 200.
- [ ] Scope compared with Yii2.

### CTI-TOP-010 - Officer Department 230 BSM GCR Sort Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/sort`

Work:

- Guard with Department 230 chief/admin-equivalent rule.
- Render old BSM GCR queue/read list.
- Keep BSM verify actions as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Department 230 guard added.
- [ ] Unauthorized officer fails closed.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Department 230 identity smoke returns 200.
- [ ] Non-BSM officer denied.

### CTI-TOP-011 - Officer Back On Duty Read Page

Status: `[ ]`

Route:

- `GET /cuti/pegawai/bod-list`

Work:

- Render BOD list scoped to officer.
- Keep BOD update/write actions as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] BOD query added.
- [ ] Write actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Officer smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-012 - Supervisor Scope Foundation

Status: `[ ]`

Files:

- `src/Shared/CutiSupervisorService.php`

Work:

- Add access row lookup from `hrm.cuti_access`.
- Add department/campus scope helpers.
- Add active staff scope equivalent to old supervisor logic.
- Add supervisor/admin guard methods.

Completion:

- [ ] Supervisor authorization method added.
- [ ] Department scope method added.
- [ ] Campus scope method added.
- [ ] Active scoped staff query added.
- [ ] No write methods added.

Verification:

- [ ] `php -l src/Shared/CutiSupervisorService.php`
- [ ] Service probe for supervisor identity returns expected staff scope.
- [ ] Ordinary staff fails closed.

### CTI-TOP-013 - Supervisor Supervised Staff Read Page

Status: `[ ]`

Route:

- `GET /cuti/supervisor/supervised-staff`

Work:

- Render scoped staff list with filters.
- Keep set-leave, staff-leave, entitlement edits, and manual application actions as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Filters implemented.
- [ ] Write/action links are Yii2 handoffs.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200.
- [ ] Scope compared with Yii2.

### CTI-TOP-014 - Pemantau Observer My Staff Read Page

Status: `[ ]`

Route:

- `GET /cuti/observer/my-staff`

Files:

- `src/Shared/CutiObserverService.php`
- `src/Web/Cuti/Observer/*`

Work:

- Add observer access guard for `akses_cuti_int IN (3,4)`.
- Render observed staff list.
- Keep observer write/inherited supervisor actions as Yii2 handoffs.

Completion:

- [ ] Observer service added.
- [ ] Route/action/template added.
- [ ] Observer menu mapping updated after smoke passes.
- [ ] No write actions exposed locally.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Observer smoke returns 200.
- [ ] Ordinary staff denied.

### CTI-TOP-015 - Supervisor Sick Leave List Read Page

Status: `[ ]`

Route:

- `GET /cuti/supervisor/sick-leave-list`

Work:

- Render sick leave queue scoped to supervisor.
- Keep `peraku`, `cs1`, and detail write actions as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Sick leave query added.
- [ ] Write actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-016 - Supervisor Monthly Statement Report

Status: `[ ]`

Route:

- `GET /cuti/supervisor/leave-statement-report`

Work:

- Render month/year/department scoped statement report.
- Keep print/PDF routes as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Month/year/department filters implemented.
- [ ] Print/PDF handoff retained.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200.
- [ ] Report totals compared with Yii2.

### CTI-TOP-017 - Supervisor Monitor Staff Leave

Status: `[ ]`

Route:

- `GET /cuti/supervisor/leave-monitoring`

Work:

- Render leave monitoring view for scoped staff.
- Preserve filters and status labels.
- Keep detail/edit actions as Yii2 handoffs unless already local and safe.

Completion:

- [ ] Route/action/template added.
- [ ] Monitoring query added.
- [ ] Filters implemented.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-018 - Supervisor Leave Calendar

Status: `[ ]`

Route:

- `GET /cuti/supervisor/calender`

Work:

- Render calendar/month grid for scoped staff.
- Keep print/export/load-month actions as Yii2 handoffs until calendar side effects are mapped.

Completion:

- [ ] Route/action/template added.
- [ ] Month/year/department filters implemented.
- [ ] Calendar grid renders responsively.
- [ ] Print/export handoff retained.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200.
- [ ] Mobile/desktop visual check passes.

### CTI-TOP-019 - Supervisor GCR/CBTH Lists

Status: `[ ]`

Routes:

- `GET /cuti/supervisor/gcr-list`
- `GET /cuti/supervisor/gcr-list-checked`

Work:

- Render supervisor GCR list and reset/checked list as read pages.
- Keep `sv-gcr-verify` and `reset-gcr` as Yii2 handoffs.

Completion:

- [ ] Routes/actions/templates added.
- [ ] GCR list queries added.
- [ ] Verify/reset actions remain Yii2.
- [ ] Menu mappings updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Supervisor smoke returns 200 for both routes.
- [ ] Yii2 comparison completed.

### CTI-TOP-020 - Admin Service Foundation

Status: `[ ]`

Files:

- `src/Shared/CutiAdminService.php`

Work:

- Add admin guard matching `akses_cuti_int = 3`.
- Add JAFPIB/special admin guard matching `akses_cuti_int = 5` where needed.
- Add read query helpers for admin pages.
- Keep writes out of this task.

Completion:

- [ ] Admin guard added.
- [ ] Special admin guard added.
- [ ] Read helper stubs/queries added for first admin pages.
- [ ] Ordinary staff fails closed.

Verification:

- [ ] `php -l src/Shared/CutiAdminService.php`
- [ ] Service probe for admin identity returns expected access.

### CTI-TOP-021 - Admin CR2 Verify Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/cr2-list`

Work:

- Render CR2 verification queue.
- Keep verify/reject/detail writes as Yii2 handoffs.

Completion:

- [ ] Route/action/template added.
- [ ] Admin guard applied.
- [ ] Write actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Non-admin denied.

### CTI-TOP-022 - Admin On Behalf Of Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/behalf-list`

Completion:

- [ ] Route/action/template added.
- [ ] Behalf-action query added.
- [ ] Add/update/delete remain Yii2 handoffs.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-023 - Admin Access Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/access`

Completion:

- [ ] Route/action/template added.
- [ ] Access list query added.
- [ ] Supervisor list usage preserved.
- [ ] Add/update/delete access actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin/supervisor smoke behavior confirmed.
- [ ] Unauthorized access denied.

### CTI-TOP-024 - Admin GCR Application Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/gcr-application-list`

Completion:

- [ ] Route/action/template added.
- [ ] GCR application filters implemented.
- [ ] Update/delete actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-025 - Admin Open GCR/CBTH Application Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/open-application`

Completion:

- [ ] Route/action/template added.
- [ ] Open-window list query added.
- [ ] Add/update/delete remain Yii2 handoffs.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-026 - Admin Public Holiday Calendar Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/ph-list`

Completion:

- [ ] Route/action/template added.
- [ ] Year filter implemented.
- [ ] Public holiday rows match Yii2.
- [ ] Copy/update/delete/recount remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-027 - Admin Maternity Leave Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/cb-list`

Completion:

- [ ] Route/action/template added.
- [ ] Maternity leave query added.
- [ ] Surat/PDF/upload/write actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-028 - Admin GCR After Closed Date Allowlist Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/admin-index`

Completion:

- [ ] Route/action/template added.
- [ ] Temp-table allowlist query added.
- [ ] Add/update/delete remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-029 - Admin Ketidakhadiran Staff Read Page

Status: `[ ]`

Route:

- `GET /cuti/admin/staff-list`

Completion:

- [ ] Route/action/template added.
- [ ] Staff/absent query added.
- [ ] Absent report/upload/amnesty actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 comparison completed.

### CTI-TOP-030 - Admin Department Date-Range Report

Status: `[ ]`

Route:

- `GET /cuti/admin/report`

Files:

- `src/Shared/CutiReportService.php`

Completion:

- [ ] Report service added or extended.
- [ ] Route/action/template added.
- [ ] Department/date filters implemented.
- [ ] Export/PDF remains Yii2 handoff.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Report totals compared with Yii2.

### CTI-TOP-031 - Admin Leave-Type Statistic Report

Status: `[ ]`

Route:

- `GET /cuti/admin/report-leave-list`

Completion:

- [ ] Route/action/template added.
- [ ] Year/department/type filters implemented.
- [ ] Leave-type totals match Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Admin smoke returns 200.
- [ ] Yii2 totals comparison completed.

### CTI-TOP-032 - Pentadbiran HSO Application List

Status: `[ ]`

Route:

- `GET /cuti/pegawai/report`

Completion:

- [ ] Department PP/chief guard implemented.
- [ ] Route/action/template added.
- [ ] HSO list/report query added.
- [ ] Write/PDF actions remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Department PP/chief smoke returns 200.
- [ ] Ordinary staff denied.
- [ ] Yii2 comparison completed.

### CTI-TOP-033 - Sector HR Manage(Surat) Read Page

Status: `[ ]`

Route:

- Preferred: `GET /cuti/manage/list`
- Legacy-compatible option: `GET /manage/list`

Files:

- `src/Shared/CutiSectorHrService.php`
- `src/Web/Cuti/Manage/*` or `src/Web/Cuti/SectorHr/*`

Completion:

- [ ] Route ownership decided.
- [ ] Sector HR service added.
- [ ] Read-only management list added from `hrm.cuti_tbl_management`.
- [ ] Add/edit/delete remain Yii2.
- [ ] Menu mapping updated after smoke passes.

Verification:

- [ ] Syntax and route checks pass.
- [ ] Sector HR smoke returns 200.
- [ ] Non-Sector HR denied.
- [ ] Yii2 comparison completed.

### CTI-TOP-034 - Officer Approval Command Foundation

Status: `[ ]`

Goal:

- Migrate the first low-risk officer write only after CTI-TOP-005 through CTI-TOP-011 are proven.

Candidate first write:

- `POST /cuti/pegawai/peraku`

Completion:

- [ ] `CutiWorkflowCommandService` added or extended.
- [ ] POST-only route added.
- [ ] CSRF protection confirmed.
- [ ] Officer scope authorization enforced.
- [ ] Transaction wraps status/log/notification changes.
- [ ] Safe test record identified before execution.
- [ ] Yii2 status transition matched.

Verification:

- [ ] Empty-submit validation checked.
- [ ] Unauthorized POST denied.
- [ ] Safe happy-path record updates expected fields.
- [ ] Logs/notifications/pending task side effects checked or explicitly deferred.

### CTI-TOP-035 - Supervisor Command Foundation

Status: `[ ]`

Goal:

- Migrate one low-risk supervisor write after supervisor read pages are stable.

Candidate first write:

- supervisor GCR verify or sick leave verification, depending on safest available fixture

Completion:

- [ ] POST-only route added.
- [ ] Supervisor scope authorization enforced.
- [ ] Transaction wraps status/log/notification changes.
- [ ] Safe test record identified.
- [ ] Yii2 transition matched.

Verification:

- [ ] Unauthorized POST denied.
- [ ] Safe happy path checked.
- [ ] Side effects checked or explicitly deferred.

### CTI-TOP-036 - Admin Settings Command Foundation

Status: `[ ]`

Goal:

- Migrate one low-risk admin setting write after admin read pages are stable.

Candidate first write:

- open-application update or public-holiday update, depending on safest fixture

Completion:

- [ ] POST-only route added.
- [ ] Admin authorization enforced.
- [ ] Transaction wraps write/log changes.
- [ ] Safe test record identified.
- [ ] Yii2 save behavior matched.

Verification:

- [ ] Unauthorized POST denied.
- [ ] Empty-submit validation checked.
- [ ] Safe happy path checked.
- [ ] Destructive actions remain deferred.

### CTI-TOP-037 - PDF, File, Notification, And Pending Task Parity Layer

Status: `[ ]`

Goal:

- Standardize cross-cutting side effects before migrating the remaining heavy writes.

Completion:

- [ ] Yii3 PDF strategy selected.
- [ ] `CutiPdfService` added if needed.
- [ ] FileManager-compatible file wrapper decided.
- [ ] `CutiFileService` added if needed.
- [ ] Notification helper added or existing helper reused.
- [ ] Pending task refresh wrapper added.
- [ ] Each migrated write declares side effects explicitly.

Verification:

- [ ] One PDF smoke if PDF generation is added.
- [ ] One file smoke if file upload is added.
- [ ] Notification/pending-task side effect smoke for one safe write.

### CTI-TOP-038 - Legacy Cleanup And Final Status

Status: `[ ]`

Goal:

- Remove Yii2 handoffs only for routes fully migrated and verified.

Completion:

- [ ] `CutiMenuService::LOCAL_ROUTES` contains all migrated routes.
- [ ] Migrated links no longer show `Yii2` badge.
- [ ] Remaining legacy links are intentional and documented.
- [ ] `AI hronline/05_cuti_migration_plan.md` top-menu status table updated.
- [ ] This plan's task board updated with completed statuses.

Verification:

- [ ] Full `/cuti/individu/index` menu smoke.
- [ ] All migrated top-menu routes return 200 for allowed identities.
- [ ] Unauthorized matrix still fails closed.
- [ ] No broken Cuti links in rendered menu.

## Recommended First Execution Order

Run these first, one task per implementation turn:

1. CTI-TOP-001 - Baseline Route Inventory And Test Identities
2. CTI-TOP-002 - Restore Missing Sector HR Menu Fallback
3. CTI-TOP-003 - Shared Cuti Top-Menu Page Shell
4. CTI-TOP-004 - Officer Service Foundation
5. CTI-TOP-005 - Officer Pending Verification Read Page

After CTI-TOP-005 is stable, continue Officer read pages in order through CTI-TOP-011 before moving to write commands.

## Verification Checklist

For every migrated slice:

- `git status --short` before and after edits.
- `php -l` for changed PHP files.
- `php yii list` after route changes.
- Unauthenticated route smoke redirects to login.
- Authenticated route smoke returns 200 for an allowed identity.
- Unauthorized identity gets a clean denied/empty state, not leaked data.
- Yii2-vs-Yii3 row-count and key-field comparison for at least one test identity.
- Browser check confirms:
  - one main card
  - module top menu directly below heading
  - toolbar uses the STARS standard
  - data is inside `content-inner-card`
  - no old Yii2 widget markers
  - no text overlap on desktop/mobile widths
  - legacy actions show `Yii2` handoff badges

