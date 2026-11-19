# Cuti Staff Menu Submenu Migration Plan

Date: 2026-11-19

Scope: Staff Menu inside the Yii3 Cuti top menu. `Apply Leave` is already migrated in the previous slice, so this plan covers the rest of the Staff Menu and the support actions needed for those pages to be complete.

## Menu Inventory

Current Yii3 source: `src/Shared/CutiMenuService.php`

| Staff Menu label | Route | Current Yii3 status | Old Yii2 source |
| --- | --- | --- | --- |
| Apply Leave | `cuti/individu/pilih` | Migrated to local Yii3 forms for active picker leave types | `actionPilih`, active form actions |
| Substitute | `cuti/individu/ganti` | Local read-only list only; approval action still legacy | `actionGanti`, `actionAgree` |
| GCR & CBTH Application | `cuti/individu/apply-gcr` | Local placeholder/list only; write form not migrated | `actionApplyGcr`, `apply-gcr.php`, `GcrApplication` |
| Leave Statement | `cuti/individu/statement` | Local simple list; not old-equivalent yet | `actionStatement`, `statement.php` |
| Leave Entitlement | `cuti/individu/entitlement` | Local simple list; totals not old-equivalent yet | `actionEntitlement`, `entitlement.php`, `Layak` |
| Application List | `cuti/individu/list` | Local list/filter/detail exists; support actions incomplete | `actionList`, `actionLeaveDetail`, `actionSurat`, `actionUpload`, `actionCancelLeave` |
| Substitute List | `cuti/individu/substitute-list` | Local simple list; mostly read-only but columns need parity | `actionSubstituteList`, `substitute-list.php` |
| My GCR & CBTH Application List | `cuti/individu/gcr-list` | Local simple list; status legend/detail parity incomplete | `actionGcrList`, `gcr-list.php` |

## Existing Yii3 Building Blocks

- `CutiMenuService` already normalizes all Staff Menu routes to local Yii3 URLs.
- `PageAction` handles `pilih`, `ganti`, `substitute-list`, `statement`, `entitlement`, `gcr-list`, and `apply-gcr`.
- `ListAction` handles `/cuti/individu/list`.
- `DetailAction` handles `/cuti/individu/leave-detail`.
- `CutiRecordService` supports staff leave lists, substitute lists, detail, year/type filters, and calendar events.
- `CutiEntitlementService` supports entitlement rows and simple calculated balance.
- `CutiGcrService` supports GCR/CBTH application list and open application window lookup.

## Main Gaps

1. `Substitute` and `Substitute List` currently use the same read-only list, but old Yii2 `Substitute` only shows pending `ENTRY` records and includes a POST approve action.
2. `/cuti/individu/agree` still routes to legacy. This is the key missing Staff Menu transaction after Apply Leave.
3. `Apply GCR & CBTH` is not migrated as a real form. Old Yii2 has gating, validation, assignment, entitlement creation, insert, and notification side effects.
4. `Leave Statement` is not old-equivalent. Old Yii2 groups leave records under each entitlement period and calculates cumulative annual-leave use and balance.
5. `Leave Entitlement` is missing old total GCR before/after adjustment totals.
6. `Application List` is mostly present, but old support actions remain incomplete: maternity approval letter, upload after maternity approval, cancel archive, and richer detail/action columns.
7. Notifications and pending task refreshes are not fully wired in Yii3 for Staff Menu actions.

## Old Yii2 Behavior Summary

### Substitute

`actionGanti()`:

- Reads current ICNO from Yii2 session.
- Lists `hrm.cuti_tbl_records` where `ganti_by = current ICNO` and `status = ENTRY`.
- Joins applicant name and leave type.
- Renders an agree button per row.

`actionAgree($id)`:

- Loads the leave record.
- If leave type is not `2` and duration is less than `14`, fills `peraku_by` and `lulus_by` from `hrm.cuti_set_pegawai`.
- Sets `ganti_dt = now`.
- Sets status to `AGREED` if `peraku_by` is present, otherwise `VERIFIED`.
- Writes old Cuti log.
- Notifies either `peraku_by` or `lulus_by`.
- Refreshes pending task command for the substitute user.

### Leave Statement

`actionStatement($year)`:

- Loads entitlement rows for current ICNO and selected entitlement start year.
- Loads leave records for current ICNO and selected leave start year.
- View shows each entitlement period followed by leave records inside that entitlement period.
- Cumulative balance only increments for leave type `1` and `2`.
- Old view has an apparent hardcoded ICNO in the year dropdown query; Yii3 should use the current staff ICNO instead.

### Leave Entitlement

`actionEntitlement()`:

- Lists all entitlement rows for current ICNO.
- Columns: START, END, BCTL, ENTITLEMENT, TOTAL, CBTH, GCR, DISCARDED.
- Adds total GCR before adjustment and after adjustment using old `Layak::getTotalGcr()` and `Layak::getTotalGcrA()`.

### Application List

`actionList($year, $jenis_cuti_id)`:

- Builds year and leave-type filters from staff records.
- Shows staff leave records for selected year.
- Shows detail modal link.
- Shows maternity approval letter and upload link only when `jenis_cuti_id = 28` and `status = APPROVED`.
- Cancel action is not exposed as a normal action in the shown view, but `actionCancelLeave($id)` exists and archives then deletes a leave record.

### Substitute List

`actionSubstituteList()`:

- Lists current-year records where current ICNO is `ganti_by`.
- Read-only columns: substitute for, leave start, leave end, duration, remark, status.

### GCR & CBTH Application List

`actionGcrList()`:

- Lists `hrm.cuti_gcr_application` rows for current ICNO ordered by request date descending.
- Columns: apply date, GCR applied, CBTH applied, status.
- Includes status legend: `ENTRY`, `CHECKED`, `VERIFIED`, `APPROVED`.

### Apply GCR & CBTH

`actionApplyGcr()`:

- Requires active/permanent staff.
- Requires open GCR/CBTH window unless the staff is explicitly allowed by temp table.
- Blocks if an active GCR application already exists.
- Blocks if active annual leave application for the open year is not approved/rejected.
- Blocks if attendance absent records are locked for GCR or not fully resolved.
- Requires current entitlement matching the open year end date or start date.
- Selects leave verifier from `hrm.cuti_access` by department; Department `230` has special verifier query.
- Calculates max GCR and CBTH using old `GcrApplication`/`Layak` rules.
- User picks `semakan_by`, enters `gcr_applied`, auto-calculates `cbth_applied`, sees disposal/lupus value, and checks agreement.
- On POST, inserts `hrm.cuti_gcr_application`, sets department and approver chain, creates next-year entitlement if missing, notifies verifier, and redirects to GCR list.

## Migration Approach

Use three levels of migration:

1. Read parity: display equivalent rows, filters, totals, and legends in Yii3.
2. Transaction parity: move POST actions such as substitute agreement and GCR/CBTH application into Yii3 with authorization and DB transaction handling.
3. Side-effect parity: notifications, pending task refresh, PDF/letter generation, uploads, and cancellation archive.

Do not migrate support actions as silent links to legacy unless explicitly marked temporary. If a page is local, visible actions on that page should either be local and complete or clearly hidden until migrated.

## Recommended Execution Order

1. Finish read-only Staff Menu parity: statement, entitlement, substitute list, GCR list, application list.
2. Migrate substitute approval (`agree`) because it is small and directly linked from Staff Menu.
3. Migrate GCR/CBTH application form as a separate service and POST flow.
4. Migrate support actions from Application List: maternity letter, upload, cancel.
5. Add notification/pending-task side effects and end-to-end browser tests.

## Execution Task Checklist

### CTI-STAFF-01 - Baseline Staff Menu Audit

- [x] Inventory all Staff Menu submenu routes from `CutiMenuService`.
- [x] Map each submenu to old Yii2 controller actions and views.
- [x] Identify which routes are already local Yii3 and which still use legacy redirects.
- [x] Unauthenticated route smoke confirms protected Staff Menu pages redirect to login instead of erroring.
- [ ] Authenticated browser smoke all Staff Menu submenu links.
- [ ] Acceptance: a test session can open every Staff Menu submenu without broken local pages.

### CTI-STAFF-02 - Staff Menu Route Policy

- [x] Keep `Apply Leave` local and unchanged from the previous migration slice.
- [x] Decide route ownership for the current slice: core Staff Menu pages local; PDF/upload/cancel remain deferred support actions.
- [x] Add route/action ownership notes for support actions: `agree`, `surat`, `upload`, `cancel-leave`.
- [x] Acceptance: every Staff Menu link has an explicit local/legacy/deferred status.

### CTI-STAFF-03 - Substitute Pending List GET

- [x] Split `/cuti/individu/ganti` from the generic substitute list behavior.
- [x] Query only `ganti_by = current ICNO` and `status = ENTRY`.
- [x] Join applicant name and leave type label.
- [x] Render old-equivalent columns: applicant, leave date, leave type, remark, action.
- [ ] Acceptance: `/cuti/individu/ganti` only shows actionable pending substitute approvals.

### CTI-STAFF-04 - Substitute Agree POST

- [x] Replace legacy redirect for `/cuti/individu/agree`.
- [x] Authorize that the current ICNO is the record `ganti_by`.
- [x] Require current status `ENTRY`.
- [x] Apply old assignment rule for non-CR2 and duration `< 14`.
- [x] Set `ganti_dt`, status `AGREED` or `VERIFIED`, and save inside a transaction.
- [x] Insert Cuti log equivalent.
- [x] Add notification hook to the next assignee.
- [x] Refresh or queue pending task update for substitute user.
- [ ] Acceptance: safe test agreement moves a record from substitute pending list to the next approval stage.

### CTI-STAFF-05 - Substitute List Read Parity

- [x] Keep `/cuti/individu/substitute-list` read-only.
- [x] Match old columns: substitute for, leave start, leave end, duration, remark, status.
- [x] Add year filter defaulting to current year, using substitute records for year options.
- [ ] Acceptance: current-year substitute history matches old Yii2 for a test staff member.

### CTI-STAFF-06 - Leave Statement Service

- [x] Add entitlement-year options based on current staff ICNO, not the old hardcoded view query.
- [x] Add per-entitlement leave-record lookup equivalent to old `TblRecords::PenyataCuti`.
- [x] Calculate cumulative annual leave usage for leave types `1` and `2`.
- [x] Calculate running balance per entitlement period.
- [ ] Acceptance: statement rows and balances match old Yii2 for one staff/year.

### CTI-STAFF-07 - Leave Statement View

- [x] Replace generic `simple-list` rendering with a dedicated statement template.
- [x] Add standard Yii3 filter bar for year.
- [x] Render entitlement summary per period.
- [x] Render leave rows under each entitlement summary.
- [ ] Acceptance: `/cuti/individu/statement` visually follows Cuti standard and preserves old data meaning.

### CTI-STAFF-08 - Leave Entitlement Totals

- [x] Add old-equivalent total GCR before adjustment calculation.
- [x] Add old-equivalent total GCR after adjustment calculation.
- [x] Confirm current Yii3 mapping uses `layak_gcr` and `layak_selaras` totals from `hrm.cuti_entitlement`.
- [ ] Acceptance: `/cuti/individu/entitlement` totals match Yii2 for a test ICNO.

### CTI-STAFF-09 - Leave Entitlement View

- [x] Replace generic `simple-list` rendering with a dedicated entitlement template.
- [x] Include old info note about temporary/contract appointments not counted as first appointment.
- [x] Render columns: start, end, BCTL, entitlement, total, CBTH, GCR, discarded.
- [x] Render total GCR before/after adjustment rows.
- [ ] Acceptance: entitlement page is old-equivalent and uses current Yii3 Cuti styling.

### CTI-STAFF-10 - Application List Parity

- [x] Confirm current year/type filters match old behavior.
- [x] Fix labels if needed: old view has a likely filter-name mismatch in one dropdown.
- [ ] Add row numbering if desired for parity.
- [x] Keep local detail action for each row.
- [x] Add status/action guidance equivalent through detail, status badge, letter, and upload columns.
- [ ] Acceptance: `/cuti/individu/list` matches old list content for one test staff/year/type.

### CTI-STAFF-11 - Application Detail Parity

- [x] Compare current `DetailAction`/`detail.php` fields against old `leave-detail.php`.
- [x] Include old-visible fields plus Yii3 support fields: staff name, staff no, position, leave type, date, duration, remark, destination, substitute, verifier, certifier, approver, status, uploaded file references.
- [ ] Acceptance: detail popup/page shows no less information than old Yii2.

### CTI-STAFF-12 - Maternity Letter Support

- [x] Decide `surat` remains a legacy fallback in this slice because local PDF support is not available yet.
- [x] Inventory old letter templates: `cb-letter`, `cb-letter-2021`, `cb-letter-2026`.
- [ ] Port version-selection rule including `isCbLetter2026Flow()`.
- [ ] Port PDF rendering or choose an equivalent Yii3 PDF strategy.
- [x] Show letter button only when `jenis_cuti_id = 28` and status `APPROVED`.
- [ ] Acceptance: approved maternity leave opens/downloads the same letter content as old Yii2.

### CTI-STAFF-13 - Post-Approval Upload Support

- [x] Decide `upload-file` and `upload` remain legacy/deferred support in this slice.
- [x] Analyze old `upload-file.php` and `upload.php` fields and MediaHost categories.
- [ ] Port upload validation and upload service calls.
- [x] Show upload action only for approved maternity rows as old list did.
- [ ] Acceptance: safe upload updates the expected record/document hash without breaking existing uploaded files.

### CTI-STAFF-14 - Cancel Leave Support

- [x] Decide user-facing cancel remains deferred because it archives then deletes live leave records.
- [ ] Port archive insert into old cancellation table equivalent before deleting the record.
- [ ] Authorize current user owns the leave record.
- [ ] Define which statuses can be cancelled locally.
- [ ] Add transaction around archive and delete.
- [ ] Acceptance: safe cancellation archives all expected fields and removes the active leave record.

### CTI-STAFF-15 - GCR List Parity

- [x] Replace generic `simple-list` rendering with a dedicated GCR list template.
- [x] Match old columns: apply date, GCR applied, CBTH applied, status.
- [x] Add status legend for `ENTRY`, `CHECKED`, `VERIFIED`, `APPROVED`.
- [x] Keep open-window note.
- [ ] Acceptance: `/cuti/individu/gcr-list` matches old Yii2 list and legend.

### CTI-STAFF-16 - GCR Eligibility Service

- [x] Port open-window lookup from `hrm.cuti_open_application`.
- [x] Port active existing application check.
- [x] Port permanent/active staff gates.
- [x] Port unresolved annual leave application check for the open year.
- [x] Port attendance absent lock check against attendance records and `TblThtc` equivalent tables.
- [x] Port temp allowlist behavior.
- [x] Port entitlement lookup by open year end/start date.
- [ ] Acceptance: eligibility service returns old-equivalent block reasons for known scenarios.

### CTI-STAFF-17 - GCR Calculation Service

- [x] Port max GCR calculation from `GcrApplication::getGcr`.
- [x] Port max CBTH calculation from `GcrApplication::getCbth`.
- [x] Port total accumulated GCR cap of `180`.
- [x] Port `checkGcr` and `checkCbth` validation rules.
- [x] Port automatic CBTH/lupus calculation used by old JavaScript.
- [ ] Acceptance: calculation fixtures match old Yii2 for 20/25/30/35-day entitlement cases.

### CTI-STAFF-18 - GCR Form GET

- [x] Replace placeholder `/cuti/individu/apply-gcr` page with a local form.
- [x] Render verifier dropdown from old `hrm.cuti_access` rules.
- [x] Render readonly max GCR, max CBTH, leave balance, auto-calculated CBTH, and lupus fields.
- [x] Render agreement checkbox with old warning text.
- [ ] Acceptance: eligible staff sees an old-equivalent Yii3 form; ineligible staff sees a clear block message.

### CTI-STAFF-19 - GCR Form POST

- [x] Validate verifier, GCR, CBTH, and agreement.
- [x] Set `pemohon_icno`, `gcr_year`, `mohon_dt`, and `dept_id`.
- [x] Assign `peraku_by` using department-chief rule.
- [x] Assign `lulus_by` to Department `230` chief.
- [x] Create next-year entitlement if missing, using the current entitlement mapping pending old `Layak::getRate` confirmation.
- [x] Insert `hrm.cuti_gcr_application` in a transaction.
- [x] Notify selected verifier.
- [x] Redirect to `/cuti/individu/gcr-list?created=1`.
- [ ] Acceptance: safe test GCR/CBTH application creates one row matching old field behavior.

### CTI-STAFF-20 - Notification And Pending Task Side Effects

- [x] Inventory old notification writes for substitute and GCR.
- [x] Decide local notification target as `system_core.tbl_notifications`, matching old Yii2 model storage.
- [x] Port pending task refresh for substitute agreement.
- [x] Add no-op fallback if notification transport is unavailable in local dev.
- [ ] Acceptance: side effects are either working or explicitly logged as deferred without failing the main transaction.

### CTI-STAFF-21 - End-To-End Regression

- [x] Smoke every Staff Menu submenu route unauthenticated; all protected routes redirect to `/hronline/login`.
- [ ] Smoke every Staff Menu submenu in an authenticated Yii3 session.
- [ ] Compare read-only pages with old Yii2 for one normal staff account.
- [ ] Agree one safe substitute record.
- [ ] Submit one safe GCR/CBTH application.
- [ ] Verify dashboard counts and pending lists update after actions.
- [ ] Verify legacy fallback links are removed or clearly marked for deferred support actions.
- [ ] Acceptance: Staff Menu can be used end to end without opening old Yii2 for migrated workflows.

## Execution Notes

- Implemented local Yii3 pages/actions for Substitute, Substitute List, Leave Statement, Leave Entitlement, GCR list, and Apply GCR/CBTH.
- Implemented local substitute agreement and GCR/CBTH submit services, but live POST acceptance remains unchecked until a safe staff session/record is confirmed.
- Maternity letter and upload links are visible only for approved maternity rows and intentionally remain legacy fallbacks in this slice.
- Cancel leave, PDF generation, and upload migration are deferred support tasks.
- Verified PHP syntax for changed services/actions/views and autoload for the new action/service classes.
- Verified unauthenticated HTTP smoke for Staff Menu routes returns clean login redirects instead of route/action errors.

## Defer Or Separate

- CP research leave is not part of the Staff Menu submenu migration unless it becomes visible through Apply Leave.
- Officer, Supervisor, Admin, Report, Pentadbiran, and Pemantau menu sections are out of scope for this plan.
- PDF generation can be a separate implementation spike if Yii3 does not already have a PDF package available.
- Live POST testing should wait for a confirmed safe staff account/session because substitute agreement and GCR submit mutate real workflow tables.
