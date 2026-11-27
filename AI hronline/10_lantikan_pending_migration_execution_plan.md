# Lantikan Pending Migration Execution Plan

Date: 2026-11-20

Purpose: continue the Yii3 Lantikan migration from the current partial state into safe write-flow migration. This document is the execution plan for the pending items still not migrated after `07_perlantikan_kakitangan_migration_plan.md`, `08_lantikan_staf_baru_migration_plan.md`, and `09_lantikan_type_migration_status.md`.

## Current State

Already migrated into Yii3:

- `/lantikan/index` dashboard.
- `/lantikan/lssu-index` Staf UMS search, read-only.
- `/lantikan/lantikan-belum-selesai`, `/lantikan/view-utama`, and `/lantikan/view-lantikan` verification pages, read-only.
- `/lantikan/lantik-staf-baru` Staf Baru form blueprint, validation, optional AD email `find`, dry-run insert plan, feature-gated core transaction service, advisory-lock UMSPER reservation, and reconciliation page.
- `ActiveDirectoryApiClient` scaffold, disabled by default unless env credentials are present.

Still pending:

- Staf Baru live submit: core `hronline.*` insert transaction is implemented behind `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`, but is not UAT-verified.
- UMSPER reservation guard: live submit implementation uses an advisory lock; dry-run preview still uses `latest COOldIDNo + 1` for non-writing preview.
- AD create / IdP Mata post-commit workflow: adapter exists, orchestration and failure recording do not.
- STARS/security/medical side effects: `attendance.tbl_wp`, `keselamatan.tbl_staff_keselamatan`, and `hrm.myhealth_tblmaxtuntutan` are intentionally blocked.
- PHS, Khas, and SPDP: dedicated direct Yii3 dry-run forms and insert-plan services exist; live transaction services remain pending.
- Staf UMS reappointment write, salary/LPG workflow, and optional tiles are still legacy Yii2.

## Migration Standard To Follow

Lantikan write migration must follow the Yii3 standards already used by Rekod Peribadi and Cuti:

- Keep controller actions thin; put write behavior in `src/Shared/*Service.php`.
- Use `ConnectionInterface::transaction()` for all DB write groups that must succeed or fail together.
- Use `createCommand()->insert/update/delete()` with parameterized input; do not port Yii2 ActiveRecord side effects blindly.
- Validate and normalize before opening a transaction.
- Use existing shared services where applicable: `AuditTrailService`, `RecordStatService`, `BiodataTouchService`, `StaffDateFormatter`, `EmployeeRepository`, and `ActiveDirectoryApiClient`.
- External side effects must run after commit only.
- Feature flags must default to off for live appointment submit and post-commit integrations.
- Legacy Yii2 handoff links stay visible until the matching Yii3 workflow passes UAT.
- Do not write STARS/Cuti-owned side-effect tables until owner approval is recorded.
- Each migrated write workflow should have a maintainer-friendly action and view named for that workflow. Avoid hiding active workflows behind generic route detection once the workflow has its own rules, fields, or transaction plan.
- Shared services may still own validation, transaction planning, and common helper behavior, but page maintainers should be able to locate a workflow's UI entry point from `src/Web/<Module>/<Workflow>Action.php` and its matching view.
- Final migrated forms must use direct, normal form markup in the workflow view. Do not use metadata-driven field loops such as `$form['fields']`, `$renderField($field)`, or generic field-type renderers as the final implementation.
- Metadata-style forms are allowed only as temporary migration scaffolding while source behavior is being discovered. They must be replaced before a workflow is considered maintainable/migrated.
- All migration work must be maintainer-friendly by default: clear route, clear action, clear view, explicit fields, and business rules in named services.
- Fast migration that lowers maintainability is rejected. Shipping quickly is not an acceptable tradeoff if the next maintainer must trace generic metadata, hidden flow detection, or shared templates to understand one workflow.

## Priority Order

1. Verify Staf Baru core write engine with approved dummy/UAT data while keeping the feature flag off by default.
2. Verify UMSPER advisory lock behavior with a live/concurrency test.
3. Add post-commit result handling for AD create, IdP Mata, notifications, and allocation-profile handoff.
4. Complete the STARS/security/medical coordination checkpoint.
5. Use `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1` only for controlled UAT submit.
6. Keep the reconciliation page current with verified table rules.
7. After UAT, migrate SPDP, PHS, and Khas using the shared transaction engine.
8. Then migrate Staf UMS reappointment writes, salary/LPG, and optional appointment tiles.

## Task Board

Run these tasks one at a time. Mark a box only after implementation, verification, and status documentation are complete.

### Staf Baru Live Submit Foundation

- [x] `LANTIKAN-W01` - Extract reusable Staf Baru insert-plan builder.
  - Goal: make the dry-run plan executable without duplicating validation/derived-value logic.
  - Acceptance: dry-run output stays unchanged; no DB writes are introduced.
- [ ] `LANTIKAN-W02` - Build core Staf Baru transaction service.
  - Goal: insert core `hronline.*` appointment rows atomically.
  - Acceptance: successful dummy/test submit creates all core rows once; forced mid-transaction failure leaves no partial rows.
  - 2026-11-20: Service implementation added behind feature flag; unchecked until AD-enabled dummy/UAT write verifies success and rollback.
- [ ] `LANTIKAN-W03` - Add UMSPER advisory lock for live submit.
  - Goal: protect `hronline.umsper.COOldIDNo` reservation from concurrent duplicates.
  - Acceptance: live submit recomputes UMSPER under `GET_LOCK`; lock timeout returns a clean error and no partial rows.
  - 2026-11-20: Advisory lock implementation added in transaction service; unchecked until live/concurrency verification is run.
  - 2026-11-27: Live validation path no longer builds an insert plan before acquiring the advisory lock; live UMSPER generation remains inside the locked transaction.
- [x] `LANTIKAN-W04` - Add feature flag plumbing without enabling writes by default.
  - Goal: introduce `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1` as the only way to expose live submit.
  - Acceptance: flag off keeps current dry-run behavior and Yii2 fallback; unauthorized POST stays blocked.
- [ ] `LANTIKAN-W05` - Wire feature-gated live submit into the dedicated Staf Baru action.
  - Goal: let authorized HR users submit Staf Baru through Yii3 only when the flag is on.
  - Acceptance: validation errors render with no writes; successful submit returns a created `ICNO`/`COOldID`.
  - 2026-11-20: FormAction wiring added; unchecked until authenticated feature-flagged POST is tested.

### Post-Create And Reconciliation

- [x] `LANTIKAN-W06` - Add Staf Baru reconciliation page.
  - Goal: show table-by-table creation status after live submit.
  - Acceptance: page checks all core tables, shows skipped side effects, and links to legacy allocation profile.
  - 2026-11-27: Probation-period table is shown as not applicable when `statLantikan != 1`.
- [ ] `LANTIKAN-W07` - Add post-commit notification handling.
  - Goal: move IT/admin notification creation after the core appointment commit.
  - Acceptance: notifications are not written if the core transaction fails.
- [ ] `LANTIKAN-W08` - Add AD create post-commit workflow.
  - Goal: call `ActiveDirectoryApiClient::createUser()` after commit when AD is enabled.
  - Acceptance: AD disabled creates a pending/manual status; AD failure is visible and does not rollback HR records.
- [ ] `LANTIKAN-W09` - Decide and document IdP Mata integration.
  - Goal: identify the Yii3-safe endpoint or keep the step as manual/legacy.
  - Acceptance: failure behavior is documented; no hidden synchronous IdP call is added.

### Cross-Module Side Effects

- [ ] `LANTIKAN-W10` - STARS working-pattern coordination.
  - Goal: confirm whether Yii3 Lantikan may write `attendance.tbl_wp`.
  - Acceptance: owner decision records `wp_id`, `start_date`, `status`, and feature flag behavior.
- [ ] `LANTIKAN-W11` - Security staff coordination.
  - Goal: confirm `keselamatan.tbl_staff_keselamatan` insert rules.
  - Acceptance: grade list and `added_by`/campus fields are confirmed before writes are enabled.
- [ ] `LANTIKAN-W12` - Medical claim coordination.
  - Goal: confirm `hrm.myhealth_tblmaxtuntutan` insert rules.
  - Acceptance: marital-status amount rule and appointment-status skip rule are confirmed before writes are enabled.
- [ ] `LANTIKAN-W13` - Implement approved side-effect writes behind flags.
  - Goal: add only the side effects approved in `W10` to `W12`.
  - Acceptance: disabled side effects remain visible as skipped/pending on reconciliation.

## Blocker Register

Resolve these before checking the related task boxes or enabling wider cutover.

| Blocker ID | Blocks | Status | Owner / Decision Needed | Notes |
| --- | --- | --- | --- | --- |
| `LANTIKAN-B01` | `W02`, `W05`, `W14`, `W15` | Open | HR/Lantikan owner approves dummy/UAT staff data for live Yii3 write test. | No live appointment rows were inserted during implementation. Need approved ICNO/name/email/test values and cleanup/rollback expectations. |
| `LANTIKAN-B02` | `W02`, `W03` | Open | Technical verification of forced failure and concurrency. | Core transaction and advisory lock code exists, but rollback and parallel-submit behavior must be tested with approved dummy data before marking complete. |
| `LANTIKAN-B03` | `W05`, `W08`, `W14` | Open | AD credentials and AD test policy. | Live submit currently requires AD email existence check. Need `HRONLINE_AD_ENABLE=1`, credentials, test email policy, and expected handling for AD API/network failure. |
| `LANTIKAN-B04` | `W08` | Open | Decision on AD create retry/audit storage. | Adapter exists, but create-user post-commit orchestration and durable failure tracking are not enabled yet. |
| `LANTIKAN-B05` | `W09` | Open | IdP Mata integration owner identifies Yii3-safe endpoint or manual fallback. | Legacy calls `IdpMata::addStaff($icno)`. Yii3 must not add a hidden synchronous call until endpoint/auth/failure behavior are known. |
| `LANTIKAN-B06` | `W10`, `W13` | Open | STARS owner confirms `attendance.tbl_wp` insert rule. | Need decision on `wp_id`, `start_date` legacy `date('Y-m-d')` vs `startDateLantik`, status, and whether Yii3 Lantikan owns this write. |
| `LANTIKAN-B07` | `W11`, `W13` | Open | Security owner confirms `keselamatan.tbl_staff_keselamatan` insert rule. | Need approved grade list, `added_by`, campus handling, and whether insert should be automatic or manual. |
| `LANTIKAN-B08` | `W12`, `W13` | Open | Medical/clinic owner confirms `hrm.myhealth_tblmaxtuntutan` insert rule. | Need approval for RM1000/RM2000 marital-status rule and skip rule for `statLantikan IN (6,7)`. |
| `LANTIKAN-B09` | `W07`, `W08`, `W09`, `W13` | Open | Durable post-commit status storage decision. | Current reconciliation page can show pending/disabled status, but a persistent job/result table or approved notification pattern is needed before retries and audit visibility are complete. |
| `LANTIKAN-B10` | `W06`, `W15` | Open | Allocation profile migration/cutover decision. | Result page links to legacy `allocation-profile/create-profile`; Yii3 replacement is not migrated. |
| `LANTIKAN-B11` | `W16`-`W23` | Open | Source mapping/UAT for SPDP, PHS, Khas, and Staf UMS reappointment. | These should not be live-migrated until Staf Baru write pattern is proven and each workflow's legacy inserts are mapped. |
| `LANTIKAN-B12` | `W24`-`W27` | Open | Business owner confirms optional/deferred workflows. | Salary/LPG and optional tiles need explicit migrate/legacy/disable decisions; hard-coded ICNO exceptions from Yii2 must not be copied. |
| `LANTIKAN-B13` | `W15`, `W28` | Open | Production cutover/UAT sign-off. | Yii2 fallback must remain until Staf Baru UAT covers permanent, contract, academic, non-academic, security grade, married/unmarried medical, AD disabled, and AD failure cases. |

### Cutover And UAT

- [ ] `LANTIKAN-W14` - Live Staf Baru UAT package.
  - Goal: prepare guided UAT cases for permanent, contract, academic, non-academic, security grade, married/unmarried medical balance, AD disabled, and AD failure.
  - Acceptance: UAT cases are recorded with expected rows and rollback checks.
- [ ] `LANTIKAN-W15` - Staf Baru production cutover.
  - Goal: make Yii3 Staf Baru the default dashboard action after UAT.
  - Acceptance: Yii2 fallback remains available temporarily; migration status is updated.

### Remaining Appointment Types

- [ ] `LANTIKAN-W16` - SPDP source mapping.
  - Goal: map Yii2 `actionLantikSpdp()` fields, defaults, UMSPER behavior, and side effects.
  - Acceptance: source map is documented before any live SPDP writes.
- [ ] `LANTIKAN-W17` - SPDP Yii3 transaction.
  - Goal: migrate SPDP write flow using the approved Staf Baru pattern.
  - Acceptance: feature-gated submit, rollback, reconciliation, and post-commit behavior pass.
- [ ] `LANTIKAN-W18` - PHS source mapping.
  - Goal: map Yii2 `actionLantikPhs()` fields, `umsperpks`, `(S)` suffix, and side effects.
  - Acceptance: source map is documented before any live PHS writes.
- [ ] `LANTIKAN-W19` - PHS Yii3 transaction.
  - Goal: migrate PHS write flow using the approved Staf Baru pattern.
  - Acceptance: feature-gated submit, rollback, reconciliation, and post-commit behavior pass.
- [ ] `LANTIKAN-W20` - Khas source mapping.
  - Goal: map Yii2 `actionLantikKhas()` and identify duplicated legacy insert risks.
  - Acceptance: duplicate-risk decision is documented before any live Khas writes.
- [ ] `LANTIKAN-W21` - Khas Yii3 transaction.
  - Goal: migrate Khas write flow using the approved Staf Baru pattern.
  - Acceptance: no duplicate history rows are created; rollback and reconciliation pass.
- [ ] `LANTIKAN-W22` - Staf UMS reappointment write mapping.
  - Goal: map `actionLantikSemulaStafUms($id)` as an update/history workflow.
  - Acceptance: audited update plan exists before implementation.
- [ ] `LANTIKAN-W23` - Staf UMS reappointment Yii3 transaction.
  - Goal: migrate the reappointment write flow.
  - Acceptance: existing staff row updates, new history rows, and rollback checks pass.

### Deferred Legacy Workflows

- [ ] `LANTIKAN-W24` - Salary profile and LPG source mapping.
  - Goal: map `tkpg`, `lpg`, and `tklpg` workflows and salary tables.
  - Acceptance: authorization, audit, and transaction boundaries are documented.
- [ ] `LANTIKAN-W25` - Salary profile and LPG Yii3 migration.
  - Goal: migrate salary/LPG workflows after mapping approval.
  - Acceptance: write actions are authorized, transactional, and verified.
- [ ] `LANTIKAN-W26` - Optional tiles business decision.
  - Goal: decide whether Sambilan Akademik, LPU, PLI, Penerbit UMS, PPPI, and EJOBS should exist in Yii3.
  - Acceptance: each tile is marked migrate, keep legacy, or remove/disabled.
- [ ] `LANTIKAN-W27` - Optional tiles implementation.
  - Goal: migrate only approved optional workflows.
  - Acceptance: no hard-coded ICNO exception is copied into Yii3; explicit authorization is used.
- [ ] `LANTIKAN-W28` - Final Lantikan legacy cleanup.
  - Goal: remove or downgrade Yii2 fallbacks only after matching Yii3 workflows pass UAT.
  - Acceptance: dashboard, menu mapping, status docs, and progress docs all match production behavior.

## Phase 1: Staf Baru Core Transaction

Task ids: `STAFBARU-06`, preparation for `STAFBARU-07`.

Target files:

- `src/Shared/LantikanStafBaruTransactionService.php`
- `src/Shared/LantikanStafBaruService.php`
- `src/Shared/LantikanService.php`
- `src/Web/Lantikan/StafBaruFormAction.php`

Implemented foundation:

- Reuse `LantikanStafBaruService::dryRun()` validation and insert-plan logic as the source of truth.
- Convert the insert plan into executable insert rows for:
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
  - `hronline.tblrscoprobtnperiod` only when `statLantikan = 1`
  - `hronline.tblrscopsnstatus`
  - `hronline.tblrscopsnathy`
  - `hronline.tblrscosaltype`
  - `hronline.tblrscoservtype`
  - `hronline.tblrscosalmovemth`
  - `hronline.tblrscofileno`
- Include legacy default values already captured in the plan, including `COOPass`, `Status`, `DeptId_hakiki`, `campus_id_hakiki`, `gredJawatan_2`, `date_update`, placement letter defaults, and VC authority lookup.
- Add audit/stat/touch behavior for `hronline.tblprcobiodata` if accepted by the Lantikan owner. If the legacy flow did not create `tbl_stat`, record that decision explicitly before adding new approval semantics.
- Return a result array containing:
  - `success`
  - `values`
  - `errors`
  - `icno`
  - `coOldId`
  - `postCommitTasks`
  - `skippedSideEffects`

Acceptance checks:

- Forced failure after any middle insert rolls back every core `hronline.*` row for the new `ICNO`.
- Successful test insert creates all core rows listed above exactly once.
- No AD, IdP, notification, allocation, `attendance`, `keselamatan`, or `hrm.myhealth` write runs inside the transaction.
- PHP syntax passes for all touched files.

Current continuation:

- The service and form wiring exist behind `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`.
- Keep `STAFBARU-06` unchecked until the forced-failure rollback and successful approved dummy/UAT insert are verified.

## Phase 2: UMSPER Reservation Guard

Task id: `STAFBARU-07`.

Implemented foundation:

- Acquire MySQL advisory lock before reading latest `hronline.umsper.COOldIDNo`:
  - `SELECT GET_LOCK('hronline.umsper.reserve', 10)`
- Reserve and insert `hronline.umsper` within the same transaction that inserts biodata and history rows.
- Release lock in `finally`:
  - `SELECT RELEASE_LOCK('hronline.umsper.reserve')`
- If lock cannot be acquired, return a user-facing error: `Terdapat proses lantikan lain yang sedang berjalan. Sila cuba sebentar lagi.`

Acceptance checks:

- Lock timeout creates no partial appointment.
- Two parallel live-submit attempts cannot receive the same `COOldIDNo`.
- Dry-run may keep preview behavior, but live submit must recompute the UMSPER under lock.

Current continuation:

- Live submit now skips insert-plan generation during pre-lock validation and recomputes the UMSPER inside the locked transaction.
- Keep `STAFBARU-07` unchecked until lock timeout and parallel live-submit behavior are verified with approved UAT data.

## Phase 3: Post-Commit Workflow

Task ids: `STAFBARU-08`, `STAFBARU-09`, part of `LANTIKAN-16`.

Implementation plan:

- Add `LantikanPostCommitService` only if the orchestration would make `LantikanStafBaruTransactionService` too large.
- After DB commit:
  - call `ActiveDirectoryApiClient::createUser()` only when `HRONLINE_AD_ENABLE=1` and credentials exist.
  - keep IdP Mata disabled until source endpoint and failure behavior are confirmed.
  - create IT/admin notifications only after the core appointment exists.
  - redirect or link to legacy `allocation-profile/create-profile?icno=...` until allocation profile is migrated.
- Record failed post-commit work in a durable admin-visible way. Preferred first step:
  - create `system_core.tbl_notifications` entries for configured admin recipients, and
  - show the status on the Yii3 reconciliation page.
- Do not roll back committed appointment records because AD or IdP fails.

Acceptance checks:

- Refreshing the browser after success does not repeat AD create.
- AD disabled produces a clear pending/manual status, not a fatal submit failure.
- Failed AD create is visible to HR/admin users.

## Phase 4: Side-Effect Coordination Checkpoint

Task ids: `LANTIKAN-07`, `STAFBARU-10`.

Blocked writes:

- `attendance.tbl_wp` belongs to STARS.
- `keselamatan.tbl_staff_keselamatan` belongs to security.
- `hrm.myhealth_tblmaxtuntutan` belongs to clinic/medical.

Required decisions:

- For `attendance.tbl_wp`, confirm whether `start_date` should remain legacy `date('Y-m-d')` or use `startDateLantik`.
- For `keselamatan.tbl_staff_keselamatan`, confirm the security grade list and whether `added_by = LANTIKAN PERTAMA` remains accepted.
- For medical balance, confirm the marital-status rule: RM1000 default, RM2000 for `MrtlStatusCd IN (2,3,4,5)`, skipped for `statLantikan IN (6,7)`.

Acceptance checks:

- Agreement is recorded before enabling these writes.
- Feature flag keeps all cross-module writes off until confirmed.
- Reconciliation page lists these side effects as skipped/pending when they are disabled.

## Phase 5: Feature-Gated Submit

Task ids: `STAFBARU-11`, `STAFBARU-12`, `STAFBARU-13`.

Implementation plan:

- Add env flag `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1`.
- With the flag off:
  - `/lantikan/lantik-staf-baru` remains dry-run.
  - Yii2 fallback button remains visible.
- With the flag on:
  - POST can execute the core transaction for authorized HR users.
  - UI labels must clearly indicate live submit, not dry-run.
  - CSRF and direct-route authorization must be enforced.
- Add `/lantikan/lantik-staf-baru/result?icno=...` or reuse `/lantikan/view-lantikan?id=...` with richer status:
  - core table checks
  - AD status
  - IdP status
  - skipped side effects
  - allocation-profile handoff

Acceptance checks:

- Unauthorized POST is blocked.
- Validation errors render without writes.
- Successful submit creates one appointment and lands on reconciliation.
- Legacy Yii2 fallback remains available during UAT.

## Phase 6: Reuse Pattern For Remaining Lantikan Types

After Staf Baru UAT:

- `LANTIKAN-09` SPDP:
  - map SPDP-specific fields/defaults.
  - decide whether to use normal `umsper` or `umsperpks` behavior based on the legacy source.
  - keep AD/IdP post-commit pattern identical.
- `LANTIKAN-10` PHS:
  - use `hronline.umsperpks` and `(S)` suffix behavior.
  - do not enable `attendance.tbl_wp` until checkpoint is signed off.
- `LANTIKAN-11` Khas:
  - use the same transaction engine but check duplicated legacy insert risks before porting.
  - avoid duplicate `tblrscoapmtstatus`, `tblrscoservstatus`, and `tblrscofileno` rows.
- `LANTIKAN-12` Staf UMS reappointment:
  - treat as an update/history workflow, not a clone of new-staff creation.
  - require audit trail and rollback analysis before implementation.

Lower-priority legacy items:

- `LANTIKAN-14` salary profile and LPG workflow.
- `LANTIKAN-15` optional tiles: Sambilan Akademik, LPU, PLI, Penerbit UMS, PPPI, EJOBS.
- `rollback-lantikan` remains intentionally not migrated without a separate security review.

## Verification Plan

Minimum checks for each implementation slice:

- `php -l` on every changed PHP file.
- `php yii list` boots.
- `GET /lantikan/lantik-staf-baru` works with feature flag off.
- POST dry-run still performs no writes.
- Feature-flagged POST success path tested only with approved dummy/test data.
- Forced DB failure proves rollback.
- Yii2 fallback route still works while UAT is open.
- `AI hronline/09_lantikan_type_migration_status.md` and `AI hronline/progress.md` are updated after each completed slice.

## Immediate Next Patch

The next code patch should focus on post-commit status handling, not the already implemented core transaction service:

- Add a durable place to record post-commit outcomes or follow-up tasks for AD create, IdP Mata, and notifications.
- Keep AD create, IdP Mata, STARS, security, medical, and allocation-profile replacement disabled unless each owner decision and retry/audit behavior is recorded.
- Keep `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1` limited to controlled UAT until `STAFBARU-06` and `STAFBARU-07` are verified.

