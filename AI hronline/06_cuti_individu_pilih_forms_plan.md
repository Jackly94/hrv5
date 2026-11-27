# Cuti Individu Pilih Form Migration Plan

## Scope

Analyze the old Yii2 staff flow behind `cuti/individu/pilih` and plan the migration into this Yii3 app at `/cuti/individu/pilih`.

Sources reviewed:

- Old Yii2 controller: `C:\Apache24\htdocs\staff\controllers\cuti\IndividuController.php`
- Old Yii2 views: `C:\Apache24\htdocs\staff\views\cuti\individu\pilih.php`
- Old Yii2 form partials: `C:\Apache24\htdocs\staff\views\cuti\individu\form`
- Old Yii2 model/rules: `C:\Apache24\htdocs\staff\models\cuti\TblRecords.php`
- Yii3 Cuti routes: `config/common/routes.php`
- Yii3 picker/reference implementation: `src/Web/Cuti/Individu/PageAction.php`, `src/Shared/CutiReferenceService.php`
- Live metadata table: `hrm.cuti_leave_type`

## Current Yii3 State

`/cuti/individu/pilih` is local Yii3 and active leave application forms are local Yii3 routes.

- Yii3 loads leave types from `hrm.cuti_leave_type`.
- Yii3 currently filters to the same active IDs used by old `actionPilih`: `1, 2, 20, 21, 28`.
- The picker links route locally for the migrated active forms.
- Yii3 routes for `ck`, `cb`, `cr1`, `cr1-aca`, `cr2`, `ctr`, `cs1`, `cs2`, and `hso` now use dedicated action classes and dedicated direct views.
- The old shared Cuti `FormAction.php` and conditional `form.php` were removed under the maintainer-friendly migration standard.

## Old Picker Behavior

Old `actionPilih()`:

- Creates `TblRecords` with scenario `pilih`.
- Selects visible leave types, currently restricted to `jenis_cuti_id IN (1, 2, 20, 21, 28)`.
- On submit:
  - blocks maternity leave (`28`) for male staff.
  - blocks maternity leave when marital status indicates unmarried / not updated.
  - keeps a research leave (`17`) criteria gate in code, although `17` is not in the current selected ID list.
  - redirects annual leave ID `1` to `cr1_aca` for academic staff with `job_category = 1` and no admin position.
  - otherwise redirects to the DB metadata action and form type.

Live visible leave metadata contains many rows with `jenis_cuti_papar IN (1,2)`. Most have no action/form metadata. The active picker intentionally narrows this to the five IDs above, so the Yii3 migration should first match that behavior before broadening the picker.

## Form Inventory

### Active Current Picker Forms

| ID | Code | Old action | Old form | Current picker? | Key notes |
| --- | --- | --- | --- | --- | --- |
| 1 | CR1 | `cr1` or `cr1_aca` | `_form_cr1`, `_form_cr1_aca` | Yes | Annual leave. Academic/non-admin staff use `cr1_aca`; others use substitute flow. |
| 2 | CR2 | `cr2` | `_form_cr2` | Yes | Overseas annual leave. Requires destination and substitute. |
| 20 | CSakit1 | `cs1` | `_form_cs1` | Yes | Private panel sick leave. Requires medical certificate upload and verifier. 15-day/year cap. |
| 21 | CSakit2 | `cs2` | `_form_cs2` | Yes | Government clinic/hospital sick leave. Requires upload and verifier. 90-day cap, or 75 for selected appointment statuses. |
| 28 | CBSalin | `cb` | `_form_cb` | Yes | Maternity leave. Requires start date, duration choice, sequence dropdowns, remark, supporting file. 360-day total cap. |

### Old Forms Not In Current Picker Filter

| ID examples | Old action | Old form | Migration note |
| --- | --- | --- | --- |
| 18 | `ck` | `_form_ck` | Emergency leave. Code exists and DB metadata exists, but old/current picker filter excludes it. |
| 3, 4, 51, 54, 56, 57 | `ctr` | `_form_ctr` | Shared "Cuti Tanpa Rekod" form for multiple leave types. Many other visible CTR-like rows have no action metadata. |
| 58 | `hso` | `_form_hso` | HSO / COVID absence. DB metadata exists, but current picker excludes it. |
| 17 | `cp` | `_form_cp` | Research leave. Code and criteria gate exist, but current picker excludes it. This should be a later slice because it touches research records and follow-up upload/add-research flow. |

## Field And Workflow Summary

### `cr1` - Cuti Rehat Dalam Negara

Fields:

- `full_date` date range, mapped to `start_date` and `end_date`
- `ganti_by` substitute staff
- `remark`
- read-only display of verifier/approver from `hrm.cuti_set_pegawai`

Workflow:

- scenario `cr1`
- validates date overlap and entitlement through `TblRecords::checkDate`
- requires `ganti_by`
- calculates `tempoh` using shift and public-holiday deduction rules
- default DB status remains `ENTRY` for normal applications
- if `tempoh >= 14`, sets `lulus_by` to `Tindakan` ID 8 and status `BSMCHECK`
- notifies substitute first

### `cr1_aca` - Academic Annual Leave Variant

Fields:

- `full_date`
- `remark`
- read-only verifier/approver display
- no substitute field

Workflow:

- scenario `cr1_aca`
- selected only when staff is academic job category 1 without admin position
- sets `peraku_by` from `hrm.cuti_set_pegawai`
- sets status to `AGREED` when verifier exists, otherwise `VERIFIED`
- if `tempoh >= 14`, sends to BSM check and VC/Tindakan approver
- notifies verifier or approver directly

### `cr2` - Cuti Rehat Luar Negara

Fields:

- `full_date`
- `destination`
- `ganti_by`
- `remark`
- read-only verifier display

Workflow:

- scenario `cr2`
- requires `destination` and `ganti_by`
- determines verifier/approver based on whether applicant is department chief
- normal approval path uses department chief and `hrm.cuti_tbl_management` level 3
- notifies substitute first

### `cs1` - Private Panel Sick Leave

Fields:

- `full_date`
- `file`
- `semakan_by` verifier selected from `hrm.cuti_access` for applicant department
- `remark`
- UI-only acknowledgement checkbox

Workflow:

- scenario `cs1`
- requires `file` and `semakan_by`
- validates overlap and annual cap through `checkCss`
- cap is 15 days/year for type 20
- sets approver from `hrm.cuti_set_pegawai`
- uploads to MediaHost category `05`, description `Medical Certificate`
- notifies selected verifier

### `cs2` - Government Sick Leave

Fields:

- same visible fields as `cs1`

Workflow:

- scenario `cs2`
- validates overlap and annual cap through `checkCsk`
- cap is 90 days/year, reduced to 75 for staff with `statLantikan IN (3, 6, 7)`
- uploads to MediaHost category `04`, description `Medical Certificate`
- save only proceeds when upload succeeds

### `hso` - Kebenaran Tidak Hadir Bekerja

Fields:

- `full_date`
- `file`
- `semakan_by`
- `remark`
- read-only approver display
- UI-only acknowledgement checkbox

Workflow:

- scenario `hso`
- current model rules require `semakan_by`; the controller still uploads a file, but `file` is not explicitly required by the `hso` rule set
- reuses `checkCsk` validation in `TblRecords`
- sets approver from `hrm.cuti_set_pegawai`
- uploads to MediaHost category `05`, description `Medical Certificate`

### `cb` - Cuti Bersalin

Fields:

- `start_date`
- `arr1` duration choice
- `cb_cuti_bersalin_bilangan_id`
- `cb_kelahiran_anak_bilangan_id`
- `remark`
- `file`

Workflow:

- scenario `cb`
- computes `end_date` from `start_date + arr1 - 1`
- computes `full_date` as `dd/mm/YYYY to dd/mm/YYYY`
- sets status `BSMCHECK`
- sets `semakan_by` from active `hrm.cuti_tbl_management` action `cb`
- sets `peraku_by` to applicant department chief
- sets `lulus_by` to Department 230 chief
- validates total maternity usage <= 360 days
- validates maternity sequence references using `hrm.cuti_ref_bersalin_bilangan`
- uploads to MediaHost category `04`, description `Cuti Bersalin`

### `ctr` - Cuti Tanpa Rekod Shared Form

Fields:

- `full_date`
- `type` switch, 0 = Dalam Negara, 1 = Luar Negara
- `remark`
- `file`

Workflow:

- scenario `ctr`
- old rules also require `arr`, `file1`, and `file2` for scenario `ctr`, but `_form_ctr` only exposes `type`, `remark`, and `file`; this mismatch must be preserved or intentionally corrected after testing
- sets `peraku_by` to department PP
- sets `lulus_by` to Department 230 chief
- uploads to MediaHost category `04`, description `Maklumat Cuti Kakitangan/Cuti Tanpa Rekod`

### `ck` - Cuti Kecemasan

Fields:

- `full_date`
- `arr[]` emergency reasons
- `others`
- `file`

Workflow:

- old action uses scenario `ctr`, not a dedicated `ck` scenario
- builds `remark` from selected reasons plus optional `others`
- sets status `BSMCHECK`
- sets `semakan_by` from active management row action `cb`
- sets `peraku_by` to department chief
- sets `lulus_by` to Department 230 chief
- uploads to MediaHost category `04`, description `Cuti Kecemasan`

### `cp` - Research Leave

Fields:

- `full_date`
- `ganti_by`
- `remark`
- `type`
- `destination`
- `research_id`
- read-only verifier/approver display

Workflow:

- scenario `cp`
- validates max 90 days and overlap through `checkCp`
- current picker excludes this type, so keep this in a later research-leave slice

## Shared Data And Rules To Port

Create explicit Yii3 services instead of copying ActiveRecord scenarios:

- `CutiApplicationCatalogService`
  - reads `hrm.cuti_leave_type`
  - preserves active picker filter first
  - maps Yii2 action/form metadata to local Yii3 routes
- `CutiApplicationFormDataService`
  - staff substitute list
  - department/chief/PP lookup
  - `hrm.cuti_set_pegawai`
  - `hrm.cuti_tbl_management`
  - sick leave verifier list from `hrm.cuti_access`
  - maternity reference dropdowns
- `CutiApplicationValidationService`
  - parse `full_date` into canonical dates
  - date overlap checks against `hrm.cuti_tbl_records`
  - entitlement/balance checks from `hrm.cuti_entitlement`
  - GCR/CBTH blocking checks
  - sick leave caps
  - maternity 360-day and sequence validation
  - shift staff detection
  - public holiday deduction using `hrm.cuti_public_holiday`
- `CutiApplicationWorkflowService`
  - assigns `semakan_by`, `peraku_by`, `lulus_by`, `status`
  - keeps status transitions compatible with old pending-task screens
- `CutiApplicationService`
  - validates request data
  - uploads files when needed
  - inserts into `hrm.cuti_tbl_records`
  - writes `hrm.cuti_log`
  - triggers or mirrors pending-task/notification side effects

`MediaHostFileService` already exists in Yii3, but it currently hardcodes upload category `04`. Cuti needs category/description support because sick leave uses category `05` in some flows.

## Migration Phases

### Phase 1 - Local GET Forms, No Writes

Goal: Replace legacy redirects for GET only while keeping POST legacy-safe.

Tasks:

- Add `CutiApplicationAction` or individual actions for active picker forms.
- Add templates under `src/Web/Cuti/Individu/form`.
- Render CR1, CR1-ACA, CR2, CS1, CS2, and CB fields from old forms.
- Keep CK, CTR, HSO, CP as legacy because they are outside the current picker.
- Keep POST routes pointing to legacy or return a controlled disabled message until write service is ready.

Acceptance:

- `/cuti/individu/pilih` opens local Yii3 forms for active IDs.
- Local form fields match old required fields.
- No writes occur in Yii3 yet.

### Phase 2 - Core Non-Upload Writes

Goal: Migrate the safest creation flows first.

Tasks:

- Implement create flow for `cr1`, `cr1_aca`, and `cr2`.
- Use transactions around insert + log.
- Preserve statuses and assignee decisions.
- Keep substitute agreement flow legacy until `agree` is migrated.

Acceptance:

- New records in `hrm.cuti_tbl_records` match old rows for the same test input.
- Pending substitute/verifier targets match old controller output.

### Phase 3 - File Upload Foundation

Goal: Make upload-dependent leave types safe.

Tasks:

- Extend `MediaHostFileService` to accept Cuti category and description.
- Add file validation matching old limit/extensions: max 6 MB, `jpeg,jpg,png,pdf`.
- Add Cuti upload error handling that prevents DB writes when required upload fails.

Acceptance:

- Test upload returns a valid `file_hashcode`.
- Failed upload does not insert a leave record.

### Phase 4 - Sick Leave And HSO

Goal: Migrate `cs1`, `cs2`, and optionally `hso`.

Tasks:

- Port sick verifier query from `hrm.cuti_access`.
- Port `checkCss` and `checkCsk` caps.
- Decide whether to intentionally require file for `hso`; document the decision because Yii2 behavior is inconsistent.

Acceptance:

- Cap and overlap errors match Yii2 messages/behavior closely enough for staff workflows.
- Records notify the selected verifier.

### Phase 5 - Maternity Leave

Goal: Migrate `cb` after upload and validation foundation.

Tasks:

- Port gender/marital gates from picker into service validation too.
- Port start-date-to-end-date calculation.
- Port maternity reference dropdowns and sequence validation.
- Preserve status `BSMCHECK` and Department 230 approval path.

Acceptance:

- Male/unmarried profile gates block as old Yii2 did.
- Sequence validation blocks repeated maternity/child sequence rows.
- 360-day cap is enforced.

### Phase 6 - Broaden Beyond Current Picker

Goal: Add old forms excluded from current picker only after active picker types are stable.

Order:

1. `ctr`, because several live leave types share it.
2. `ck`, because it reuses the `ctr` scenario but has different visible fields.
3. `hso`, if not already done in Phase 4.
4. `cp`, because research leave has separate criteria and follow-up flows.

Acceptance:

- Picker broadening is deliberate and matches stakeholder expectation.
- Rows with no DB action/form metadata remain hidden or route to legacy, not broken local pages.

## Verification Plan

- Unit/service tests:
  - `full_date` parse and formatting.
  - overlap detection.
  - annual entitlement balance.
  - public holiday deduction by campus.
  - shift staff inclusive-day calculation.
  - sick leave caps.
  - maternity sequence and 360-day cap.
- Database comparison:
  - create one approved test fixture per migrated form in a non-production/test dataset.
  - compare inserted Yii3 row fields with old Yii2 row fields.
- Browser smoke:
  - authenticated `/cuti/individu/pilih`
  - each local GET form
  - one CR1 submit in a safe test account
  - one upload form after upload service is enabled
- Regression:
  - `/cuti/individu/index`, `/cuti/individu/list`, pending substitute list, and officer pending lists still show migrated records.

## Recommended Next Slice

Start with Phase 1 plus `cr1`/`cr1_aca` read-only GET forms.

Reason:

- These are on the active picker.
- They expose the central date/entitlement rules without file upload risk.
- `cr1_aca` reveals the academic staff branch early.
- Actual writes can remain disabled until Phase 2 service tests are ready.

## Execution Task Checklist

Status note:

- Checked boxes mean implemented, source-reviewed, or non-writing CLI/HTTP probed in this Yii3 codebase.
- Unchecked boxes are still pending because they require an authenticated browser session, a confirmed safe test staff account, or live POST submissions that would create real `hrm.cuti_tbl_records` rows.
- POST routes were implemented in the same execution slice, so earlier read-only route tasks are marked as superseded where applicable.

### CTI-PILIH-01 - Baseline Current Picker

- [ ] Confirm `/cuti/individu/pilih` loads locally in Yii3 for an authenticated staff session.
- [x] Confirm displayed leave types are restricted to IDs `1, 2, 20, 21, 28` through the Yii3 catalog service probe.
- [x] Confirm old Yii2 picker behavior for maternity gates and CR1 academic redirect from source.
- [ ] Record one known academic non-admin test ICNO and one normal staff test ICNO.
- [x] Acceptance: baseline notes identify the exact old behavior to preserve before route changes.

### CTI-PILIH-02 - Application Catalog Service

- [x] Add a Yii3 catalog method that returns active picker leave types from `hrm.cuti_leave_type`.
- [x] Include DB metadata: `jenis_cuti_id`, label, action, form type, and local/legacy route target.
- [x] Preserve current active filter: `jenis_cuti_id IN (1, 2, 20, 21, 28)`.
- [x] Hide or legacy-route rows without action/form metadata.
- [x] Acceptance: service output matches the old active picker list and is covered by a simple service test or CLI probe.

### CTI-PILIH-03 - Local Route Mapping For Active Forms

- [x] Replace legacy redirect on `/cuti/individu/cr1`.
- [x] Replace legacy redirect on `/cuti/individu/cr1-aca`.
- [x] Superseded by POST migration: both routes now use migrated Yii3 validation/write handling.
- [x] Add route/action plumbing for active forms and direct legacy form routes.
- [ ] Acceptance: active CR1 links can open local Yii3 pages in an authenticated browser session.

### CTI-PILIH-04 - Shared Form Data Service

- [x] Add lookup for current staff profile, department, department chief, and department PP.
- [x] Add lookup for `hrm.cuti_set_pegawai` by applicant ICNO.
- [x] Add active staff list for substitute dropdown, excluding the applicant.
- [x] Add academic non-admin detector used by the CR1/CR1-ACA branch.
- [x] Acceptance: service returns all data needed to render CR1 and CR1-ACA without inline SQL in templates.

### CTI-PILIH-05 - CR1 Read-Only GET Form

- [x] Add Yii3 template for `cr1` with date range, substitute, remark, verifier display, and approver display.
- [x] Match old labels closely enough for staff recognition.
- [x] Add Back button to `/cuti/individu/pilih`.
- [x] Superseded by POST migration: submit now uses migrated Yii3 validation/write handling.
- [ ] Acceptance: normal staff opening annual leave sees the local CR1 form in an authenticated browser session.

### CTI-PILIH-06 - CR1-ACA Read-Only GET Form

- [x] Add Yii3 template for `cr1-aca` with date range, remark, verifier display, and approver display.
- [x] Do not show substitute field for this academic variant.
- [x] Ensure picker/route decision sends academic non-admin staff to this page.
- [x] Superseded by POST migration: submit now uses migrated Yii3 validation/write handling.
- [ ] Acceptance: academic non-admin staff opening annual leave sees the local CR1-ACA form.

### CTI-PILIH-07 - Date And Duration Utility

- [x] Add parser for old `full_date` format.
- [x] Add formatter for stored `full_date` format: `dd/mm/YYYY to dd/mm/YYYY`.
- [x] Add inclusive and weekday-style day counters to mirror old `totalDaysInc`, `totalDays`, and `totalDaysEx` behavior.
- [x] Add public holiday deduction check using `hrm.cuti_public_holiday` and staff campus.
- [x] Add shift staff detector using the old shift tables.
- [ ] Acceptance: utility tests cover normal staff, shift staff, public holiday deduction, and invalid date input.

### CTI-PILIH-08 - Annual Leave Validation Service

- [x] Port overlap detection for `hrm.cuti_tbl_records`.
- [x] Port current entitlement/balance validation using `hrm.cuti_entitlement`.
- [x] Port GCR/CBTH blocking check.
- [x] Port current-year boundary rule for annual leave.
- [x] Port 14-day branch rule that sends long leave to BSM/VC path.
- [ ] Acceptance: validation returns old-equivalent errors for overlap, no balance, no entitlement, GCR block, and long leave.

### CTI-PILIH-09 - Cuti Application Write Service Skeleton

- [x] Add `CutiApplicationService` with transaction wrapper.
- [x] Add insert method for `hrm.cuti_tbl_records`; superseded by full POST migration instead of feature-disabled skeleton.
- [x] Add `hrm.cuti_log` writer matching old `Log()` behavior.
- [ ] Add no-op/stub notification hook so later tasks can wire notifications without changing service shape.
- [x] Acceptance: service class autoloads and can be instantiated/probed without exposing test writes.

### CTI-PILIH-10 - CR1 POST Migration

- [x] Enable POST for `/cuti/individu/cr1`.
- [x] Validate date range, substitute, and remark.
- [x] Compute `tempoh` using the shared utility.
- [x] Assign `peraku_by`, `lulus_by`, and status following old `actionCr1` including the long-leave BSM path.
- [x] Insert record and log inside a transaction.
- [x] Redirect to `/cuti/individu/index` with success query marker and inline validation errors.
- [ ] Acceptance: a safe test CR1 application creates a row matching old Yii2 field behavior.

### CTI-PILIH-11 - CR1-ACA POST Migration

- [x] Enable POST for `/cuti/individu/cr1-aca`.
- [x] Validate date range and remark.
- [x] Compute `tempoh` using the academic/no-substitute path.
- [x] Assign `peraku_by`, `lulus_by`, and status following old `actionCr1_aca`.
- [x] Insert record and log inside a transaction.
- [ ] Acceptance: a safe test CR1-ACA application creates a row matching old Yii2 field behavior.

### CTI-PILIH-12 - Substitute Agreement Dependency Check

- [ ] Confirm migrated CR1 records appear in Yii3 `/cuti/individu/substitute-list` for `ganti_by`.
- [ ] Confirm old Yii2 `/cuti/individu/ganti` can still process agreement for migrated records.
- [ ] Confirm officer pending lists see records after substitute agreement.
- [ ] Acceptance: migrating CR1 creation does not strand records outside the existing approval workflow.

### CTI-PILIH-13 - CR2 GET Form

- [x] Add local GET route/action for `/cuti/individu/cr2`.
- [x] Add template with date range, destination, substitute, remark, and verifier display.
- [x] Use old department-chief branch to display intended verifier/approver.
- [x] Superseded by POST migration: submit now uses migrated Yii3 validation/write handling.
- [ ] Acceptance: CR2 opens locally and displays all required fields in an authenticated browser session.

### CTI-PILIH-14 - CR2 POST Migration

- [x] Port CR2 verifier/approver assignment for department chief and non-chief applicants.
- [x] Validate `destination`, `ganti_by`, date range, and remark.
- [x] Compute `tempoh` using shared date utility.
- [x] Insert record and log inside a transaction.
- [ ] Acceptance: a safe CR2 application creates a row matching old Yii2 field behavior.

### CTI-PILIH-15 - MediaHost Upload Extension

- [x] Update `MediaHostFileService` to accept category and description parameters.
- [x] Preserve existing callers by providing defaults.
- [x] Add Cuti upload validation: `jpeg,jpg,png,pdf`, max 6 MB.
- [x] Add upload failure handling that blocks DB insert for required upload forms.
- [ ] Acceptance: existing non-Cuti uploads still work and Cuti can upload with category `04` or `05`.

### CTI-PILIH-16 - Sick Leave Shared Form Data

- [x] Add sick verifier lookup from `hrm.cuti_access` for applicant department.
- [x] Preserve fallback query when no grade `< 41` verifier exists.
- [x] Add selected verifier display/list data for CS1, CS2, and HSO.
- [ ] Acceptance: verifier list matches old Yii2 for at least one test department.

### CTI-PILIH-17 - CS1 GET And POST

- [x] Add local GET form for `/cuti/individu/cs1`.
- [x] Add fields: date range, medical certificate file, verifier, remark, acknowledgement checkbox.
- [x] Port 15-day cap and overlap validation.
- [x] Upload file with category `05`, description `Medical Certificate`.
- [ ] Insert record, log, and notify selected verifier. Record/log is implemented; notification side effect is still pending.
- [ ] Acceptance: CS1 safe test record matches old field/status behavior.

### CTI-PILIH-18 - CS2 GET And POST

- [x] Add local GET form for `/cuti/individu/cs2`.
- [x] Add fields matching old CS2 form.
- [x] Port 90-day cap and 75-day appointment-status cap.
- [x] Upload file with category `04`, description `Medical Certificate`.
- [x] Insert record only after upload succeeds.
- [ ] Acceptance: CS2 safe test record matches old field/status behavior.

### CTI-PILIH-19 - Maternity Reference Data

- [x] Add reference lookup for `hrm.cuti_ref_bersalin_bilangan`.
- [x] Add maternity total-used calculation from non-rejected type `28` records.
- [x] Add sequence validation equivalent to `validateCbBilanganSequence`.
- [ ] Acceptance: reference dropdowns and sequence errors match old Yii2 behavior.

### CTI-PILIH-20 - CB GET And POST

- [x] Add local GET form for `/cuti/individu/cb`.
- [x] Add fields: start date, duration, maternity sequence dropdowns, child sequence dropdown, remark, file.
- [x] Enforce gender and marital gates in both picker and submit service.
- [x] Compute end date and `full_date` from start date + duration.
- [x] Assign `status = BSMCHECK`, `semakan_by`, `peraku_by`, and `lulus_by` as old Yii2 did.
- [x] Upload file with category `04`, description `Cuti Bersalin`.
- [ ] Acceptance: CB safe test record matches old field/status behavior and blocks invalid profiles.

### CTI-PILIH-21 - HSO Decision And Migration

- [x] Decide whether HSO should be added to the picker now or kept hidden until requested.
- [x] Document whether file should be required, because old model/controller behavior is inconsistent.
- [x] Add direct GET/POST form with date range, required file, verifier, remark, approver display.
- [x] Acceptance: HSO is kept hidden from the active picker but migrated as a direct local route with documented stricter file validation.

### CTI-PILIH-22 - CTR Decision And Migration

- [x] Decide which `ctr`-mapped leave IDs should become visible locally.
- [x] Resolve old mismatch where scenario `ctr` requires `arr`, `file1`, and `file2`, but `_form_ctr` does not show them.
- [x] Add shared CTR form with visible-field validation and file upload.
- [x] Acceptance: CTR is kept hidden from the active picker but migrated as a direct local route without inheriting hidden required-field bugs.

### CTI-PILIH-23 - CK Decision And Migration

- [x] Decide whether emergency leave ID `18` should be restored to local picker.
- [x] Port emergency reason checkbox list and `others` remark composition.
- [x] Upload file with category `04`, description `Cuti Kecemasan`.
- [x] Acceptance: CK is kept hidden from the active picker but migrated as a direct local route with old remark/status behavior.

### CTI-PILIH-24 - CP Research Leave Decision

- [x] Keep CP out of current picker until research leave criteria and follow-up flows are scheduled.
- [ ] Inventory `actionCp`, `add-research`, `upload`, and research tables before implementation.
- [x] Acceptance: CP remains legacy/deferred with a separate plan instead of partial migration.

### CTI-PILIH-25 - End-To-End Regression

- [x] Smoke `/cuti/individu/pilih` unauthenticated guard: route responds and redirects to login.
- [x] Smoke one migrated GET form unauthenticated guard: route responds and redirects to login.
- [ ] Submit one safe record for each migrated POST form.
- [ ] Confirm records appear in `/cuti/individu/index` and `/cuti/individu/list`.
- [ ] Confirm pending/substitute/officer lists still detect migrated records.
- [ ] Confirm no unmigrated type opens a broken local page.
- [ ] Acceptance: active picker can be used end to end for all migrated leave types.

## Execution Notes

- Implemented Yii3 local routes for `cr1`, `cr1-aca`, `cr2`, `cs1`, `cs2`, `cb`, plus direct local routes for `ck`, `ctr`, and `hso`.
- Implemented `CutiApplicationService` for picker catalog, form data, validation, upload coordination, transactional record insert, and `hrm.cuti_log` insert.
- Extended `MediaHostFileService` so Cuti uploads can pass category and description while existing callers keep defaults.
- Reconfigured Cuti forms to follow the direct maintainer-friendly migration standard:
  - `ck` uses `CutiKecemasanFormAction` and `cuti-kecemasan-form.php`.
  - `cb` uses `CutiBersalinFormAction` and `cuti-bersalin-form.php`.
  - `cr1` uses `CutiRehatDalamNegaraFormAction` and `cuti-rehat-dalam-negara-form.php`.
  - `cr1-aca` uses `CutiRehatDalamNegaraAkademikFormAction` and `cuti-rehat-dalam-negara-akademik-form.php`.
  - `cr2` uses `CutiRehatLuarNegaraFormAction` and `cuti-rehat-luar-negara-form.php`.
  - `ctr` uses `CutiTanpaRekodFormAction` and `cuti-tanpa-rekod-form.php`.
  - `cs1` uses `CutiSakitFormAction` and `cuti-sakit-form.php`.
  - `cs2` uses `CutiSakitLanjutanFormAction` and `cuti-sakit-lanjutan-form.php`.
  - `hso` uses `HsoFormAction` and `hso-form.php`.
- Removed `src/Web/Cuti/Individu/FormAction.php` and `src/Web/Cuti/Individu/form.php`; there is no longer one conditional mega-form for these write workflows.
- Verified PHP syntax for the changed Cuti route/action/template/service files and routes config.
- Verified unauthenticated HTTP guard for `/cuti/individu/pilih` and `/cuti/individu/cr1?id=1`.
- Verified catalog service probe returns 5 active picker links: `28`, `1`, `2`, `20`, `21`.
- Did not perform live POST submissions, because that would create real leave rows without a confirmed safe test account/session.
- Notification/dashboard runner side effects are still pending; migrated submits currently write the leave record and log only.
