# Lantikan Staf Baru Yii3 Migration Plan

Date: 2026-11-19

## Scope

Migrate old Yii2 `LantikanController::actionLantikStafBaru()` into the Yii3 app under:

- Current Yii3 route: `GET|POST /lantikan/lantik-staf-baru`
- Old Yii2 route: `lantikan/lantik-staf-baru`
- Current Yii3 behavior: dry-run remains the default. A feature-gated Yii3 core transaction path exists behind `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`, but it has not been signed off with approved dummy/UAT data.

This plan is for the real `Staf Baru` submit flow. It does not cover PHS, Khas, SPDP, Staf UMS reappointment, salary profile, or LPG writes except where shared services can be reused later.

## Old Yii2 Sources Reviewed

Primary source:

- `C:\Apache24\htdocs\staff\controllers\LantikanController.php`
  - `actionLantikStafBaru()` lines around 242-557
  - `ValidateEmail()` lines around 854-881
  - `EmailSuggestion()` lines around 883+
- `C:\Apache24\htdocs\staff\views\lantikan\_lantikstafbaru.php`
- `C:\Apache24\htdocs\staff\models\hronline\Tblprcobiodata.php`
  - scenario `baru`
- `C:\Apache24\htdocs\staff\components\ActiveDirectory.php`
- `C:\Apache24\htdocs\staff\components\MaklumatPeribadi.php`
  - `getVC()`

## Current Yii3 Starting Point

Already exists:

- `src/Web/Lantikan/StafBaruFormAction.php`
- `src/Web/Lantikan/staf-baru-form.php`
- `src/Shared/LantikanService.php`
- `src/Shared/LantikanReferenceService.php`
- `src/Shared/ActiveDirectoryApiClient.php`
- `src/Shared/LantikanStafBaruService.php`
- `src/Shared/LantikanStafBaruTransactionService.php`
- `/lantikan/lantik-staf-baru/result` reconciliation page

Important current limitation:

- The Yii3 write path is still disabled by default and should only be used for controlled UAT with `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`.
- Live submit requires AD email existence validation before the core DB transaction.
- Core `hronline.*` insert rows and UMSPER reservation are implemented, but successful dummy creation, forced rollback, and concurrency verification are still pending before task completion can be checked.
- AD create, IdP Mata, notifications, allocation-profile replacement, and cross-module side effects remain post-commit/manual or disabled.

## Old Form Field Coverage

Yii2 `baru` required fields:

- `ICNO`
- `TitleCd`
- `CONm`
- `ReligionCd`
- `RaceCd`
- `EthnicCd`
- `BloodTypeCd`
- `COBirthPlaceCd`
- `GenderCd`
- `MrtlStatusCd`
- `COBirthCertNo`
- `COBirthDt`
- `COBirthCountryCd`
- `NatCd`
- `NatStatusCd`
- `COBumiStatus`
- `COEmail`
- `COEmail2`
- `COHPhoneNo`
- `HighestEduLevelCd`
- `ConfermentDt`
- `ArmyPoliceCd`
- `statLantikan`
- `startDateLantik`
- `endDateLantik`
- `gredJawatan`
- `statSandangan`
- `ApmtTypeCd`
- `DeptId`
- `campus_id`
- `NegeriAsalIbu`
- `NegeriAsalBapa`
- `NegaraAsalCd`
- `NegeriAsalCd`

Yii2 form fields that are currently missing or incomplete in the Yii3 blueprint:

- `COBirthPlaceCd`
- `NegaraAsalCd`
- `NegeriAsalCd`
- `NegeriAsalIbu`
- `NegeriAsalBapa`
- `isSabahan`
- `COHPhoneStatus`

Yii3 should add these before live submit is enabled.

## Old Submit Flow

When submit is not `Jana Email`, Yii2:

1. Loads `Tblprcobiodata` with scenario `baru`.
2. Validates model rules.
3. Uppercases `CONm`.
4. Sets derived biodata values:
   - `startDateSandangan = startDateLantik`
   - `endDateSandangan = endDateLantik`
   - `Status = '1'`
   - `startDateStatus = startDateLantik`
   - `COOPass = '0f8ce4a6a430a9e48cae55ea9c0f84fd'`
   - `DeptId_hakiki = DeptId`
   - `campus_id_hakiki = campus_id`
   - `gredJawatan_2 = gredJawatan`
5. Validates official email:
   - local part length must be <= 20
   - domain must be `ums.edu.my`
   - AD `find` must report the email does not exist
6. Calculates appointment defaults:
   - `statLantikan IN (6, 7)` gives `ConfirmStatusCd = 8`, `PsnStatusCd = 04`, `PsnAthyCd = 99`
   - `statLantikan = 1` gives `ConfirmStatusCd = 2`, `PsnStatusCd = 03`, `PsnAthyCd = 99`
   - other statuses give `ConfirmStatusCd = 8`, `PsnStatusCd = 04`, `PsnAthyCd = 99`
7. Generates UMSPER:
   - Reads latest `hronline.umsper.COOldIDNo`
   - Adds 1 and pads to 5 digits
   - Builds `COOldIDDt` as `ymd` from `startDateStatus`
   - Builds `COOldID = COOldIDDt . '-' . COOldIDNo`
   - Sets `COOUCTelNo = '1' . COOldIDNo`
8. Saves `hronline.tblprcobiodata`.
9. Inserts dependent HR records.
10. If `ApmtTypeCd == 2`, conditionally inserts cross-module records:
    - `keselamatan.tbl_staff_keselamatan` for security job grades
    - `attendance.tbl_wp`
    - `hrm.myhealth_tblmaxtuntutan`
11. Sends IT/admin notifications.
12. Calls AD create.
13. Calls `IdpMata::addStaff($icno)`.
14. Redirects to `allocation-profile/create-profile?icno=...`.

## Transaction Boundary

Yii2 only deletes `tblprcobiodata` if the `umsper` insert fails, then continues to use many `save(false)` calls. Yii3 must not copy this behavior.

Yii3 transaction should include:

- `hronline.tblprcobiodata`
- `hronline.umsper`
- `hronline.tblrscosandangan`
- `hronline.tblrscoapmtstatus`
- `hronline.tblrscoservstatus`
- `hronline.tblpreduach`
- `hronline.tblpenempatan`
- `hronline.tblrscoaptathy`
- `hronline.tblrscoservload`
- `hronline.tblrscoconfirmstatus`
- `hronline.tblrscoprobtnperiod` when `statLantikan = 1`
- `hronline.tblrscopsnstatus`
- `hronline.tblrscopsnathy`
- `hronline.tblrscosaltype`
- `hronline.tblrscoservtype`
- `hronline.tblrscosalmovemth`
- `hronline.tblrscofileno`

Live Yii3 submit table count:

- Inserts 16 core `hronline.*` tables for non-probation appointment statuses.
- Inserts 17 core `hronline.*` tables when `statLantikan = 1`, because `hronline.tblrscoprobtnperiod` is included.
- The transaction insert loop skips any planned core table whose row is `null`; currently only `hronline.tblrscoprobtnperiod` is conditional.

Post-commit side effects:

- AD create user
- IdP Mata sync
- `system_core.tbl_notifications`
- allocation profile redirect

Cross-module writes need coordination before enabling:

- `attendance.tbl_wp` - STARS owner
- `keselamatan.tbl_staff_keselamatan` - security module owner
- `hrm.myhealth_tblmaxtuntutan` - clinic/medical owner

## UMSPER Generation Risk

The old code uses `latest COOldIDNo + 1`. The DB indexes observed locally:

- `hronline.umsper` has a primary key on `Id`.
- No unique index was observed on `COOldIDNo` or `COOldID`.
- `hronline.tblprcobiodata.ICNO` is the primary key.

Yii3 should protect UMSPER reservation with an application-level lock, for example:

- Acquire MySQL advisory lock `GET_LOCK('hronline.umsper.reserve', 10)`.
- Read latest `COOldIDNo`.
- Insert into `hronline.umsper` and `tblprcobiodata` in one DB transaction.
- Release lock in `finally`.
- If insertion fails, rollback everything and do not call AD/IdP.

Longer term, add a DB-backed sequence table or a unique constraint after checking legacy data quality.

## Insert Map

### Biodata

Table: `hronline.tblprcobiodata`

Direct from form:

- `ICNO`, `TitleCd`, `CONm`, `ReligionCd`, `RaceCd`, `EthnicCd`, `BloodTypeCd`
- `GenderCd`, `MrtlStatusCd`, `COBirthCertNo`, `COBirthDt`, `COBirthCountryCd`, `COBirthPlaceCd`
- `NegaraAsalCd`, `NegeriAsalCd`, `NegeriAsalIbu`, `NegeriAsalBapa`
- `NatCd`, `NatStatusCd`, `COBumiStatus`, `isSabahan`
- `COEmail`, `COEmail2`, `COHPhoneNo`, `COHPhoneNo2`, `COHPhoneStatus`
- `HighestEduLevelCd`, `ConfermentDt`, `ArmyPoliceCd`
- `KodProgram`, `statLantikan`, `startDateLantik`, `endDateLantik`
- `gredJawatan`, `gredJawatan_hakiki`, `statSandangan`, `ApmtTypeCd`
- `noWaran`, `DeptId`, `campus_id`

Derived:

- `CONm = strtoupper(CONm)`
- `COOldID = generated UMSPER`
- `COOUCTelNo = '1' . generated number`
- `COOPass = legacy default hash`
- `Status = '1'`
- `startDateStatus = startDateLantik`
- `startDateSandangan = startDateLantik`
- `endDateSandangan = endDateLantik`
- `DeptId_hakiki = DeptId`
- `campus_id_hakiki = campus_id`
- `gredJawatan_2 = gredJawatan`
- `last_update`, `last_updater` should be set by Yii3 if accepted by current audit standard.

### UMSPER

Table: `hronline.umsper`

- `ICNO`
- `JobId = gredJawatan`
- `DeptId`
- `campus_id`
- `COOldID`
- `StartDate = startDateLantik`
- `COOldIDDt`
- `COOldIDNo`

### HR History Tables

- `hronline.tblrscosandangan`: `ICNO`, `gredjawatan`, `sandangan_id`, `ApmtTypeCd`, `start_date`
- `hronline.tblrscoapmtstatus`: `ICNO`, `ApmtStatusCd`, `ApmtStatusStDt`, `ApmtStatusEndDt`
- `hronline.tblrscoservstatus`: `ICNO`, `ServStatusCd = 1`, `ServStatusDtl = 1`, `ServStatusStDt`, `sebab_berhenti = '-'`
- `hronline.tblpreduach`: education defaults with `InstCd = 999`, `MajorCd = 0000`, `SponsorshipCd = 0000`, `OverallGrade = PASS`, `AcrtdEduAch = 1`, `Bon = 0`
- `hronline.tblpenempatan`: original placement with `reason_id = 1`, `remark = Penempatan asal`
- `hronline.tblrscoaptathy`: `AptAthyCd = 387`, `ICNOHeadServ = current VC ICNO`, `AptAthyStDt`
- `hronline.tblrscoservload`: `ServLoadCd = 1`
- `hronline.tblrscoconfirmstatus`: derived `ConfirmStatusCd`
- `hronline.tblrscoprobtnperiod`: only for `statLantikan = 1`, with 36 month max, 12 month min
- `hronline.tblrscopsnstatus`: derived `PsnStatusCd`, `PsnStatusNo = '-'`, `PsnIncomeTaxNo = '-'`, `PsnEpfNo = '-'`
- `hronline.tblrscopsnathy`: derived `PsnAthyCd`
- `hronline.tblrscosaltype`: `SalTypeCd = 01`, `SalStatus = 1`
- `hronline.tblrscoservtype`: `ServTypeCd = SSPA`
- `hronline.tblrscosalmovemth`: move month based on start month: Jan/Apr/Jul/Oct
- `hronline.tblrscofileno`: `COFileNo = COOldID`

## Task Board

Run one task at a time. Mark the box only after implementation and verification are complete.

- [x] `STAFBARU-01` - Complete Yii3 Staf Baru field blueprint.
- [x] `STAFBARU-02` - Add missing reference lookups and dependent state/city option support.
- [x] `STAFBARU-03` - Add server-side validation service.
- [x] `STAFBARU-04` - Add AD email existence validation only.
- [x] `STAFBARU-05` - Add insert-plan dry-run with generated UMSPER preview.
- [ ] `STAFBARU-06` - Build transaction service for core `hronline.*` inserts.
- [ ] `STAFBARU-07` - Add advisory lock/retry guard for UMSPER reservation.
- [ ] `STAFBARU-08` - Add post-commit task/result handling for AD create.
- [ ] `STAFBARU-09` - Decide IdP Mata Yii3 integration or legacy/manual handoff.
- [ ] `STAFBARU-10` - Coordinate STARS/security/medical side-effect writes.
- [ ] `STAFBARU-11` - Enable submit behind explicit feature flag.
- [ ] `STAFBARU-12` - Add verification/reconciliation page after create.
- [ ] `STAFBARU-13` - Switch from feature flag to normal production flow after UAT.

Execution status on 2026-11-19:

- Implemented `src/Shared/LantikanStafBaruService.php` for normalization, server-side validation, optional AD email existence checking, generated UMSPER preview, and dry-run insert planning.
- Implemented `src/Shared/LantikanStafBaruTransactionService.php` for feature-gated core `hronline.*` inserts, advisory-lock protected live UMSPER reservation, and reconciliation checks.
- Updated `src/Shared/LantikanReferenceService.php` so the Staf Baru form includes the missing Yii2 `baru` fields and reference options.
- Earlier generic `src/Web/Lantikan/form.php` rendering was replaced by dedicated direct workflow views.
- Updated `src/Web/Lantikan/StafBaruFormAction.php` and `/lantikan/lantik-staf-baru/result` so controlled live submit can redirect to a table-by-table reconciliation page when the feature flag is enabled.
- Split Staf Baru into dedicated `src/Web/Lantikan/StafBaruFormAction.php` and `src/Web/Lantikan/staf-baru-form.php` files so Staf Baru UI changes no longer depend on the generic Lantikan form action/view.
- Reconfigured `src/Web/Lantikan/staf-baru-form.php` to direct normal form markup with explicit field calls. It no longer builds the form from `$form['fields']`, `$fieldsByName`, section metadata, or `$renderField()`.
- Updated `src/Shared/LantikanService.php` so the Staf Baru tile opens local Yii3 `/lantikan/lantik-staf-baru`; the form still shows a Yii2 fallback link.
- Live inserts remain disabled by default. No approved dummy/UAT `hronline.*`, AD create, IdP, STARS, security, or medical writes were executed.

Continuation note on 2026-11-27:

- Live preparation now avoids generating a UMSPER insert plan before the advisory lock; live UMSPER generation happens inside the locked transaction path.
- The reconciliation page now marks `hronline.tblrscoprobtnperiod` as `Tidak berkaitan` when `statLantikan != 1`, matching the Yii2 insert rule.

## Execution Tasks

### [x] STAFBARU-01: Complete Yii3 Staf Baru Field Blueprint

Goal:

- Make the Yii3 form metadata match the old Yii2 `baru` scenario before enabling writes.

Files:

- `src/Shared/LantikanReferenceService.php`
- `src/Web/Lantikan/staf-baru-form.php`

Add fields:

- `COBirthPlaceCd`
- `NegaraAsalCd`
- `NegeriAsalCd`
- `NegeriAsalIbu`
- `NegeriAsalBapa`
- `isSabahan`
- `COHPhoneStatus`

Acceptance checks:

- `GET /lantikan/lantik-staf-baru` renders all required Yii2 `baru` fields.
- Dry-run still inserts/updates no records.
- PHP syntax passes.

### [x] STAFBARU-02: Missing Reference Lookups

Goal:

- Add lookup data needed by the complete form.

Lookups:

- States by country for `COBirthPlaceCd` and `NegeriAsalCd`.
- States for `NegeriAsalIbu` and `NegeriAsalBapa`.
- Sabah status static options.
- Job grade list filtered like Yii2: `scheme_type != 'SPDP'`.

Acceptance checks:

- Options load from DB without errors.
- Empty/missing dependent options fail gracefully.

### [x] STAFBARU-03: Server-Side Validation Service

Goal:

- Move form validation out of `LantikanService::dryRunAppointment()` into a Staf Baru validator that can be reused by dry-run and live submit.

Rules:

- Required fields match Yii2 `baru` scenario.
- `ICNO` must be unique in `hronline.tblprcobiodata`.
- `COEmail` must be syntactically valid and unique in `hronline.tblprcobiodata`.
- `COEmail` max length 41.
- `COEmail` local part max length 20.
- `COEmail` domain must be `@ums.edu.my`.
- Dates must be valid `Y-m-d`.
- `endDateLantik` must not be before `startDateLantik`.
- Reference ids must exist in their reference tables.

Acceptance checks:

- Invalid data returns field errors and no DB writes.
- Existing ICNO and existing `COEmail` are rejected before any insert transaction starts.

### [x] STAFBARU-04: AD Email Existence Validation

Goal:

- Use the existing Yii3 `ActiveDirectoryApiClient::find()` to check official email existence only.

Rules:

- If AD API is disabled, the validator should return a clear blocked result for live submit.
- Dry-run can show "AD disabled" without failing local preview.
- Do not call AD create/update here.

Acceptance checks:

- `find` is the only AD endpoint used in validation.
- No credentials are committed.
- Network/API failure produces a recoverable validation error.

### [x] STAFBARU-05: Insert-Plan Dry Run

Goal:

- Generate a complete insert plan without executing it.

Files:

- Add `src/Shared/LantikanStafBaruService.php` or `src/Shared/LantikanAppointmentService.php`.

Plan output:

- Generated `COOldID` preview.
- Derived biodata values.
- Derived confirmation/pension/probation/salary movement values.
- List of rows to be inserted by table.
- Post-commit side effects that would run.

Acceptance checks:

- Plan matches the old Yii2 insert map.
- No records are inserted.
- Useful for UAT sign-off before live submit.

### [ ] STAFBARU-06: Core HR Transaction Service

Goal:

- Insert core `hronline.*` records atomically.

Transaction includes:

- Biodata
- UMSPER
- All HR history tables listed in "Transaction Boundary"

Rules:

- Any insert failure rolls back all transaction records.
- AD, IdP, notifications, attendance, medical, and security writes do not run inside this first core transaction.

Acceptance checks:

- Forced insert failure leaves no partial `ICNO` records.
- Successful transaction can be verified through `/lantikan/view-lantikan?id=...`.

### [ ] STAFBARU-07: UMSPER Reservation Guard

Goal:

- Prevent duplicate or skipped UMSPER generation during concurrent appointments.

Implementation:

- Use MySQL advisory lock around UMSPER number generation and insert.
- Add retry or clear error message when lock cannot be acquired.
- Release lock in `finally`.

Acceptance checks:

- Parallel dry-run/live tests cannot generate duplicate `COOldID`.
- Lock timeout does not leave a half-created appointment.

### [ ] STAFBARU-08: AD Create Post-Commit Handling

Goal:

- Create AD account only after the HR transaction commits.

Rules:

- Call `ActiveDirectoryApiClient::createUser()` only after commit.
- If AD create fails, keep HR appointment complete but record an admin-visible follow-up item.
- Do not rollback committed HR records because an external API failed.

Acceptance checks:

- AD disabled in local/test by default.
- Failed AD create is visible to admin users.
- No duplicate AD create occurs on browser refresh.

### [ ] STAFBARU-09: IdP Mata Integration Decision

Goal:

- Decide whether Yii3 should call IdP directly or hand off to legacy/manual queue.

Acceptance checks:

- Source endpoint/service for `IdpMata::addStaff($icno)` is identified.
- Failure behavior is documented.
- No hidden synchronous side effect is added without owner approval.

### [ ] STAFBARU-10: Cross-Module Side-Effect Coordination

Goal:

- Confirm ownership before Yii3 writes to STARS/security/medical tables.

Blocked writes until confirmed:

- `attendance.tbl_wp`
- `keselamatan.tbl_staff_keselamatan`
- `hrm.myhealth_tblmaxtuntutan`

Acceptance checks:

- STARS owner confirms `attendance.tbl_wp` behavior.
- Security owner confirms `tbl_staff_keselamatan` behavior.
- Medical/clinic owner confirms `myhealth_tblmaxtuntutan` behavior.
- Feature flag keeps these off until confirmed.

### [ ] STAFBARU-11: Enable Submit Behind Feature Flag

Goal:

- Allow controlled Yii3 submit without exposing it broadly.

Suggested flag:

- `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`

Behavior:

- Without flag: page remains dry-run and Yii2 handoff remains visible.
- With flag: authorized HR users can submit Yii3 create.

Acceptance checks:

- Flag off by default.
- Submit button clearly uses Yii3 when flag is enabled.
- Unauthorized direct POST is blocked.

### [ ] STAFBARU-12: Verification/Reconciliation Page

Goal:

- After a create, show a table-by-table status page.

Checks:

- All core `hronline.*` rows exist.
- AD status is recorded.
- IdP status is recorded or clearly pending.
- Cross-module writes are recorded as skipped/pending until enabled.

Acceptance checks:

- User can confirm created staff without opening old Yii2.
- Admin can see what still needs manual action.

### [ ] STAFBARU-13: Production Cutover After UAT

Goal:

- Make Yii3 Staf Baru the default path from `/lantikan/index`.

Acceptance checks:

- UAT sign-off covers fixed-term, permanent, contract, academic, non-academic, security grade, married/unmarried clinic balance, and failed AD cases.
- Legacy Yii2 link remains available temporarily for rollback.
- Monitoring/audit confirms no partial appointments for the cutover window.

## Open Questions

- Should `attendance.tbl_wp.start_date` keep Yii2 behavior of `date('Y-m-d')`, or should Yii3 use `startDateLantik`?
- Should `system_core.tbl_notifications` be inserted inside the DB transaction or treated as post-commit?
- Should the VC lookup continue to use `Status != 6 AND gredJawatan = 2`, or should it be configurable?
- Should Yii3 keep the legacy default password hash, or should first-login/reset policy be changed?
- Should allocation profile remain a legacy redirect after create, or should allocation profile be migrated first?
- Should AD disabled block live submit, or allow HR appointment with manual AD follow-up?
