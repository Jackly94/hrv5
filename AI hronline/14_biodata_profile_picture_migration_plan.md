# Biodata Profile Picture Migration Plan

Date recorded: 2026-11-27

## Goal

Migrate the Yii2 `GambarProfileController` workflow into Yii3 so the `Kemaskini Gambar` button on `/biodata/userview` opens a real profile-picture request flow instead of the current placeholder.

```text
Button: /biodata/userview -> Kemaskini Gambar
URL:    /gambar-profile/...
Web:    src/Web/GambarProfile/...
Shared: src/Shared/GambarProfile/...
```

Initial implementation focus:

- Staff profile-picture request list, upload form, and own request detail.
- BPSM admin approval/rejection queue.
- BKUMS profile-photo publishing isolated behind a Yii3 service and config.

## Current Yii3 State

- `/biodata/userview` renders the staff profile page from `src/Web/Biodata/ProfileAction.php`.
- The `Kemaskini Gambar` button is rendered in `src/Web/Biodata/template.php`.
- The button links to `/gambar-profile/semakan`.
- `/gambar-profile/semakan` is currently routed to `src/Web/RecordAction/Action.php`, a placeholder screen.
- Profile photos are displayed from BKUMS public storage:

```text
https://bkums.ums.edu.my/public/picstf/{SHA1(ICNO)}.jpeg
```

## Target Convention

### Module URLs

Staff:

```text
GET      /gambar-profile/semakan
GET,POST /gambar-profile/gambar
GET      /gambar-profile/lihat-permohonan?id=...
```

BPSM admin approval:

```text
GET      /gambar-profile/senarai
GET,POST /gambar-profile/semakan-gambar?id=...
GET      /gambar-profile/rekod-permohonan
```

Admin / counter direct upload:

```text
GET,POST /gambar-profile/cari-kakitangan
GET,POST /gambar-profile/muatnaik-gambar-kakitangan?id=...
GET      /gambar-profile/admin-senarai-penukaran
GET      /gambar-profile/admin-view-penukaran?id=...
```

Admin access management:

```text
GET,POST /gambar-profile/tambah-admin
GET      /gambar-profile/senarai-admin
GET      /gambar-profile/lihat-admin?id=...
GET,POST /gambar-profile/kemaskini-admin?id=...
```

### Web Layer

```text
src/Web/GambarProfile/
  Staff/
    RequestListAction.php
    UploadAction.php
    DetailAction.php
    request-list.php
    upload.php
    detail.php

  Admin/
    PendingListAction.php
    ReviewAction.php
    HistoryAction.php
    StaffSearchAction.php
    CounterUploadAction.php
    CounterHistoryAction.php
    CounterDetailAction.php
    AdminListAction.php
    AdminFormAction.php
    AdminDetailAction.php
    pending-list.php
    review.php
    history.php
    staff-search.php
    counter-upload.php
    counter-history.php
    counter-detail.php
    admin-list.php
    admin-form.php
    admin-detail.php
```

### Shared Layer

```text
src/Shared/GambarProfile/
  ProfilePictureService.php
  ProfilePictureAdminAccessService.php
  BkumsProfilePhotoService.php
```

## Old Yii2 Source Baseline

Controller:

- `C:\Apache24\htdocs\staff\controllers\GambarProfileController.php`

Models:

- `C:\Apache24\htdocs\staff\models\gambar\TblGambar.php`
- `C:\Apache24\htdocs\staff\models\gambar\TblGambarAdmin.php`
- `C:\Apache24\htdocs\staff\models\gambar\TblGambarLogs.php`

Views:

- `views/gambar-profile/semakan.php`
- `views/gambar-profile/_formGambar.php`
- `views/gambar-profile/view_permohonan.php`
- `views/gambar-profile/view_list_update_gambar.php`
- `views/gambar-profile/form_semakan_gambar.php`
- `views/gambar-profile/view_semakan_gambar.php`
- `views/gambar-profile/view_rekod_permohonan.php`
- `views/gambar-profile/carian_gambar_staff.php`
- `views/gambar-profile/admin/_formGambar.php`
- `views/gambar-profile/rekod_muatnaik_gambar.php`
- `views/gambar-profile/view_penukaran.php`
- `views/gambar-profile/admin/senarai_admin.php`
- `views/gambar-profile/admin/_tambah_admin_bpsm.php`
- `views/gambar-profile/admin/lihat_admin.php`

Component side effects:

- `C:\Apache24\htdocs\staff\components\UAPI.php`
  - `UploadFile()`
  - `DisplayFile()`
  - `NameFile()`
  - `UploadImage()`
- `C:\Apache24\htdocs\staff\components\MaklumatPeribadi.php`
  - `UploadPicture()`

## Database Baseline

Main request table:

```text
keselamatan.utils_tbl_gambar
```

Important request columns observed from Yii2 model:

```text
id
ICNO
entry_dt
gambar
gambar_lama
attachment
app_by
app_dt
app_status
catatan
catatan_admin
last_updated
year
updated_by
updated_dt
effective_date
status
status_label
curr_process
type
pic_path
dir_path
admin_icno
admin_dt
```

Admin access table:

```text
keselamatan.utils_tbl_gambar_admin
```

Important admin columns:

```text
id
icno
access_type      -- 1 = BPSM, 2 = Keselamatan
is_active        -- 1 = active, 0 = inactive
add_by
add_date
```

Audit log table:

```text
keselamatan.utils_tbl_gambar_logs
```

Important log columns:

```text
id
usern
COUpadteDate
COUpdateIP
COUpdateComp
COUpdateCompUser
idval
```

## Business Rules

### Staff Upload Request

- Staff opens `/gambar-profile/semakan` from the `Kemaskini Gambar` button.
- Staff can view all own online requests where `ICNO = current staff ICNO` and `type = 1`.
- Staff can submit a new profile-picture request through `/gambar-profile/gambar`.
- Upload must be JPEG only.
- Upload size must not exceed 2MB.
- Uploaded image is stored in MediaHost:

```text
category: 01
directory: Maklumat Rekod Kakitangan/gambar_profile
```

- New request row values:

```text
ICNO = current staff ICNO
entry_dt = current datetime
gambar = MediaHost hashcode
year = current year
status_label = 1
curr_process = 1
type = 1
status = NULL
```

- Preserve old uniqueness behavior:
  - a staff member can have only one pending request where `status IS NULL`.

### Staff Detail

- Staff can only view requests where `ICNO = current staff ICNO`.
- Detail page shows:
  - current BKUMS profile photo;
  - submitted MediaHost image;
  - staff biodata summary;
  - request status;
  - request date;
  - admin action date;
  - admin remarks or rejection notes where present.

### BPSM Admin Approval Queue

- `/gambar-profile/senarai` shows pending online requests:

```text
type = 1 AND status IS NULL
```

- BPSM admin access requires:

```text
keselamatan.utils_tbl_gambar_admin.icno = current staff ICNO
AND is_active = 1
AND access_type = 1
```

- Pending rows open a write review page.
- Actioned rows open a read-only review page.

### Approval Action

Approval input values follow old Yii2:

```text
app_status = DILULUSKAN
app_status = TIDAK DILULUSKAN
```

On rejection:

```text
app_by = current admin ICNO
app_dt = current datetime
status_label = 3
curr_process = 0
status = 2
catatan = admin remarks
```

On approval:

1. Try to preserve the current BKUMS profile photo by uploading it to MediaHost.
2. Store the old image MediaHost hash in `gambar_lama` when available.
3. Publish the new MediaHost image to BKUMS ImageDepot.
4. Update request:

```text
app_by = current admin ICNO
app_dt = current datetime
status = 1
status_label = 4
curr_process = 0
catatan = admin remarks
```

- Application update and publish bookkeeping must be treated as one logical action.
- If BKUMS publish fails, the request must not be marked approved.
- Notification side effects may be deferred until the Yii3 notification standard is confirmed.
- Pending-task refresh may be deferred, but must be tracked.

### Admin / Counter Direct Upload

- Admin can search a staff member, then upload a profile photo directly.
- Access requires active admin with `access_type IN (1, 2)`.
- Direct upload creates a request row with:

```text
type = 2
status = 1
status_label = 1 initially in Yii2 upload path
curr_process = 1
```

- Yii2 then publishes to BKUMS immediately through `UploadImage()`.
- Migration should normalize the final successful row to a clear published status and document any intentional parity difference.
- Old code checks `hasPhoto()` before direct upload; this must be confirmed because the current flag logic appears to block upload when BKUMS already has a photo.

### Admin Management

- BPSM admin can add and manage profile-picture admins.
- `access_type = 1` admin can view/manage all admin rows.
- Non-BPSM admin is limited to own `access_type` in the old list behavior.
- `tambah-admin` defaults:

```text
access_type = adminType(current user)
is_active = 1
add_by = current user
add_date = current date
```

## External Services

### MediaHost

Existing Yii3 service:

- `src/Shared/MediaHostFileService.php`

Needed additions or usage:

- upload image with category `01`;
- view submitted image by hash;
- download submitted image by hash for BKUMS publish;
- fetch file name by hash.

### BKUMS ImageDepot

Legacy endpoint:

```text
https://bkums.ums.edu.my/imagedepot/web/photo/upload
```

Legacy header:

```text
X-API-KEY: <hardcoded in Yii2>
```

Yii3 requirement:

- Add `BkumsProfilePhotoService`.
- Move endpoint and API key into ignored local config or environment-backed config.
- Do not hardcode the API key in source.
- Service should:
  - download MediaHost file to a temp file;
  - resize if width is greater than 1000px, matching old behavior;
  - publish multipart field `photo`;
  - send `icno`;
  - return structured success/error;
  - clean temp files.

## Phase 1: Staff Read Shell

- [x] Add routes:
  - [x] `GET /gambar-profile/semakan`
  - [x] `GET /gambar-profile/lihat-permohonan`
- [x] Add `src/Shared/GambarProfile/ProfilePictureService.php`.
- [x] Add request history query:
  - [x] `ICNO = current staff ICNO`
  - [x] `type = 1`
  - [x] newest first by `entry_dt`.
- [x] Join staff biodata for display name and summary fields.
- [x] Add `src/Web/GambarProfile/Staff/RequestListAction.php`.
- [x] Add `src/Web/GambarProfile/Staff/DetailAction.php`.
- [x] Add templates:
  - [x] `request-list.php`
  - [x] `detail.php`
- [x] Replace `/gambar-profile/semakan` route placeholder with real staff list action.

Completion:

- [ ] `Kemaskini Gambar` button opens a Yii3 request-history page.
- [ ] Staff sees only own requests.
- [ ] Staff detail blocks other staff request IDs.
- [ ] Current BKUMS photo and submitted MediaHost image render.
- [ ] Empty state renders cleanly.
- [x] `php -l` and `php yii list` pass.
- [x] Unauthenticated route smoke redirects to login.

## Phase 2: Staff Upload Request

- [x] Add route:
  - [x] `GET,POST /gambar-profile/gambar`
- [x] Add `src/Web/GambarProfile/Staff/UploadAction.php`.
- [x] Add `upload.php` template.
- [x] Migrate staff picture-spec guide PDF and upload-page guide section.
- [x] Add selected-image preview before submit.
- [x] Add file validation:
  - [x] required file;
  - [x] JPEG only;
  - [x] max 2MB;
  - [x] image-readable check.
- [x] Reuse `MediaHostFileService` for upload:
  - [x] category `01`;
  - [x] directory `Maklumat Rekod Kakitangan/gambar_profile`.
- [x] Insert request row into `keselamatan.utils_tbl_gambar`.
- [x] Enforce one pending request per staff.
- [x] Use CSRF token.
- [x] Redirect back to `/gambar-profile/semakan` with success/error signal.

Completion:

- [ ] GET upload page renders.
- [ ] POST without file is rejected.
- [ ] Non-JPEG file is rejected.
- [ ] File over 2MB is rejected.
- [ ] Pending duplicate is rejected.
- [ ] Safe upload test creates exactly one pending row.
- [ ] Uploaded image opens through MediaHost view URL.
- [x] `php -l` and `php yii list` pass.

## Phase 3: Admin Approval Read

- [x] Add `ProfilePictureAdminAccessService`.
- [x] Add admin access checks:
  - [x] BPSM review: active `access_type = 1`;
  - [x] counter/admin flows: active `access_type IN (1, 2)`.
- [x] Add routes:
  - [x] `GET /gambar-profile/senarai`
  - [x] `GET /gambar-profile/rekod-permohonan`
  - [x] `GET /gambar-profile/semakan-gambar`
- [x] Add actions:
  - [x] `Admin/PendingListAction.php`
  - [x] `Admin/HistoryAction.php`
  - [x] `Admin/ReviewAction.php`
- [x] Add templates:
  - [x] `pending-list.php`
  - [x] `history.php`
  - [x] `review.php`
- [x] Pending list query:

```text
type = 1 AND status IS NULL
```

- [x] History list query:

```text
type = 1 AND status IN (1, 2)
```

Completion:

- [x] Non-admin cannot open admin routes.
- [x] BPSM admin can open pending and history routes.
- [x] Pending row opens write review page.
- [x] History row opens read-only review page.
- [x] Current BKUMS photo and requested MediaHost photo are displayed side by side.
- [x] `php -l` and `php yii list` pass.

## Phase 4: BKUMS Publish Service

- [x] Add config keys for BKUMS profile photo publishing:
  - [x] endpoint;
  - [x] API key;
  - [x] timeout.
- [x] Add `src/Shared/GambarProfile/BkumsProfilePhotoService.php`.
- [x] Add method to backup existing BKUMS photo to MediaHost.
- [x] Add method to publish a MediaHost hash to BKUMS.
- [x] Download MediaHost image to temp file.
- [x] Resize image if width is greater than 1000px.
- [x] Submit multipart request to BKUMS endpoint:

```text
field: photo
data:  icno
header: X-API-KEY
```

- [x] Return structured result:

```text
success: bool
message: string
payload?: array
```

- [x] Clean temp files in all paths.
- [x] Add a dry-run/test-mode option if practical.

Completion:

- [x] Missing BKUMS config fails safely.
- [x] Invalid MediaHost hash fails safely.
- [x] Failed BKUMS response does not mark a request approved.
- [ ] Safe dummy publish test is documented before live cutover.

## Phase 5: Admin Approval Write

- [x] Extend `Admin/ReviewAction.php` for POST.
- [x] Require POST and CSRF.
- [x] Require active BPSM admin.
- [x] Validate selected row is still pending:

```text
id = :id
AND type = 1
AND status IS NULL
```

- [x] Require approval status.
- [x] Require remarks for rejection.
- [x] On reject:
  - [x] set `app_by`;
  - [x] set `app_dt`;
  - [x] set `status_label = 3`;
  - [x] set `curr_process = 0`;
  - [x] set `status = 2`;
  - [x] save admin remarks.
- [x] On approve:
  - [x] backup old BKUMS photo when available;
  - [x] publish new photo to BKUMS;
  - [x] set `gambar_lama` when backup succeeds;
  - [x] set `app_by`;
  - [x] set `app_dt`;
  - [x] set `status = 1`;
  - [x] set `status_label = 4`;
  - [x] set `curr_process = 0`;
  - [x] save admin remarks.
- [x] Wrap DB updates in transaction.
- [x] Do not commit approved status when BKUMS publish fails.

Completion:

- [x] GET request does not mutate data.
- [x] Already-actioned rows cannot be approved/rejected again.
- [x] Reject test updates only the selected safe row.
- [ ] Approve test publishes only after explicit safe dummy confirmation.
- [x] Failure path leaves request pending or error-marked according to chosen policy.
- [x] `php -l` and `php yii list` pass.

## Phase 6: Admin / Counter Direct Upload

- [x] Add routes:
  - [x] `GET,POST /gambar-profile/cari-kakitangan`
  - [x] `GET,POST /gambar-profile/muatnaik-gambar-kakitangan`
  - [x] `GET /gambar-profile/admin-senarai-penukaran`
  - [x] `GET /gambar-profile/admin-view-penukaran`
- [x] Add actions:
  - [x] `Admin/StaffSearchAction.php`
  - [x] `Admin/CounterUploadAction.php`
  - [x] `Admin/CounterHistoryAction.php`
  - [x] `Admin/CounterDetailAction.php`
- [x] Add templates:
  - [x] `staff-search.php`
  - [x] `counter-upload.php`
  - [x] `counter-history.php`
  - [x] `counter-detail.php`
- [x] Confirm old `hasPhoto()` behavior before porting exactly.
- [x] Upload direct photo through MediaHost.
- [x] Publish direct photo to BKUMS.
- [x] Save `type = 2` request row.
- [x] Write audit row to `keselamatan.utils_tbl_gambar_logs`.

Implementation notes:

- Old `hasPhoto()` returns true when `https://bkums.ums.edu.my/public/picstf/{SHA1(ICNO)}.jpeg` can be opened. The Yii3 direct upload preserves the legacy gate and blocks counter upload when a BKUMS photo already exists.
- Yii2 initially used `status_label = 1` in the direct upload path, then published immediately. Yii3 normalizes a successful direct upload to `status = 1`, `status_label = 5`, `curr_process = 0`, and records the approving/uploading admin fields.

Completion:

- [ ] Active access type 1 or 2 admin can open counter flow.
- [ ] Non-admin is blocked.
- [ ] Staff search handles missing ICNO cleanly.
- [ ] Direct upload requires explicit safe dummy test before live use.
- [ ] Direct upload history shows `type = 2` rows.
- [x] `php -l` and `php yii list` pass.

## Phase 7: Admin Access Management

- [x] Add routes:
  - [x] `GET,POST /gambar-profile/tambah-admin`
  - [x] `GET /gambar-profile/senarai-admin`
  - [x] `GET /gambar-profile/lihat-admin`
  - [x] `GET,POST /gambar-profile/kemaskini-admin`
- [x] Add actions:
  - [x] `Admin/AdminListAction.php`
  - [x] `Admin/AdminFormAction.php`
  - [x] `Admin/AdminDetailAction.php`
- [x] Add templates:
  - [x] `admin-list.php`
  - [x] `admin-form.php`
  - [x] `admin-detail.php`
- [x] Port admin type rule:
  - [x] access type 1 can manage all rows;
  - [x] other access types see only own `access_type`.
- [x] On create/update set:

```text
add_by = current admin ICNO
add_date = current date
```

Implementation notes:

- Access management routes require an active profile-picture admin with `access_type IN (1, 2)`.
- BPSM admin (`access_type = 1`) can create/update BPSM or Keselamatan admins.
- Non-BPSM admin is forced to their own access type and cannot submit `access_type = 1`.
- Create/update prevents duplicate rows for the same `icno + access_type`.

Completion:

- [ ] BPSM admin can list and edit admins.
- [ ] Non-BPSM admin cannot escalate to BPSM.
- [ ] Inactive admin cannot access protected admin routes.
- [x] `php -l` and `php yii list` pass.

## Phase 8: Menu Cutover And Verification

- [x] Update route mapping so `/gambar-profile/semakan` is no longer placeholder.
- [ ] Map admin menu routes locally where appropriate:
  - [x] `gambar-profile/senarai`
  - [x] `gambar-profile/rekod-permohonan`
  - [x] `gambar-profile/cari-kakitangan`
  - [x] `gambar-profile/admin-senarai-penukaran`
  - [x] `gambar-profile/tambah-admin`
  - [x] `gambar-profile/senarai-admin`
- [ ] Confirm `/biodata/userview` button opens real Yii3 request list.
- [ ] Confirm old Yii2 staff routes have Yii3 equivalents.
- [ ] Confirm old Yii2 admin routes have Yii3 equivalents or intentional legacy fallback.
- [ ] Add browser smoke notes.

Completion:

- [ ] Staff read pages open authenticated.
- [ ] Admin read pages open for authorized admin.
- [ ] Unauthorized users are blocked.
- [ ] Safe upload write test is complete.
- [ ] Safe approval/rejection write test is complete.
- [ ] Safe BKUMS publish test is explicitly confirmed before production cutover.
- [ ] `php -l` and `php yii list` pass.

## Cutover Blockers

- [ ] BKUMS ImageDepot endpoint and API key moved to ignored config.
- [ ] Safe dummy ICNO and dummy JPEG confirmed.
- [ ] MediaHost upload credentials confirmed in Yii3.
- [ ] BKUMS publish dry-run or controlled live test completed.
- [ ] Pending-task refresh decision recorded.
- [ ] Notification replacement decision recorded.
- [ ] Admin access parity audited against Yii2 `AccessControl`.

## Deferred

- [ ] Notification side effects for staff approval/rejection messages.
- [ ] Pending-task refresh for profile-picture admins.
- [ ] Full audit-log parity for every admin action.
- [ ] Image crop/preview enhancements beyond basic upload validation.
- [ ] Reconcile old `status_label = 2` meaning if historical data uses it.
- [ ] Decide whether old duplicate/pending rule should be stricter per `type` or match Yii2 exactly by `ICNO + status`.

## Smoke Notes

- 2026-11-27: analysis confirmed `/biodata/userview` button links to `/gambar-profile/semakan`.
- 2026-11-27: analysis confirmed `/gambar-profile/semakan` still uses `RecordAction` placeholder.
- 2026-11-27: Yii2 baseline traced through `GambarProfileController`, `TblGambar`, `TblGambarAdmin`, `TblGambarLogs`, `UAPI`, and `MaklumatPeribadi`.
- 2026-11-27: phase 1 and phase 2 staff Yii3 implementation added for request list, detail, and upload request.
- 2026-11-27: `php -l` passed for new profile-picture PHP files and `config/common/routes.php`.
- 2026-11-27: `php yii list` passed.
- 2026-11-27: unauthenticated HTTP smoke for `/hronlinev5/gambar-profile/semakan`, `/hronlinev5/gambar-profile/gambar`, and `/hronlinev5/gambar-profile/lihat-permohonan?id=1` returns `302` to `/hronlinev5/login`.
- 2026-11-27: live authenticated upload POST was not executed, so real MediaHost upload and row creation remain pending verification.
- 2026-11-27: migrated legacy `PanduanMuatNaikGambarStaff.pdf` from `staff/web/uploads-gambar` to Yii3 `public/files` and restored the staff upload-page guide as a popup.
- 2026-11-27: added client-side selected-image preview to the staff upload page before submit.
- 2026-11-27: authenticated smoke verified staff list, upload guide popup/PDF, `Semak Gambar` preview hooks, and detail comparison image markup using a temporary request row; dummy row, temp sessions, and temp test helpers were removed after the test.
- 2026-11-27: phase 3 admin read implementation added for BPSM pending list, history list, and review comparison pages.
- 2026-11-27: phase 3 smoke used real rows `2415` pending and `2414` history; BPSM session returned `200` for pending/history/review pages, counter-only and normal staff sessions returned `403`, pending review showed `Tindakan BPSM`, and history review stayed read-only. Temporary session helper, sessions, and curl artifacts were removed after testing.
- 2026-11-27: mapped main-menu `Pengurusan Gambar Staf` route `gambar-profile/senarai` to Yii3 `/gambar-profile/senarai`; generated BPSM menu item now reports `external=false` and `legacy=false`. `Muatnaik Gambar Kakitangan Baharu` remains a Yii2 fallback until Phase 6.
- 2026-11-27: phase 4 and phase 5 added BKUMS publish service/config, BPSM approval/rejection POST, active CSRF form, and safe failure handling when BKUMS config is missing.
- 2026-11-27: phase 5 safe write smoke inserted temporary rows `2417` and `2418`; reject updated only row `2417`, repeated action was blocked, missing BKUMS config left row `2418` pending, and both dummy rows were deleted. Cleanup query for `codex_phase5%` returned `0`.
- 2026-11-27: authenticated BPSM HTTP smoke rendered `/gambar-profile/semakan-gambar?id=2415` with active Phase 5 form and CSRF token; POST without CSRF returned `422`. Temporary session helper, session, cookies, and curl artifacts were removed after testing.
- 2026-11-27: phase 6 Yii3 implementation added counter/admin staff search, direct upload form, direct upload history, direct upload detail, routes, menu mappings, MediaHost/BKUMS direct publish path, normalized `type = 2` request status, and audit-log insert. `php -l` passed for new/changed Phase 6 PHP files and `php yii list` passed. Live direct upload/publish was not executed pending explicit safe dummy ICNO/JPEG confirmation.
- 2026-11-27: phase 7 Yii3 implementation added profile-picture admin access list, create/update form, detail page, routes, menu mappings, scoped BPSM/non-BPSM management rules, duplicate `icno + access_type` guard, and active-admin route protection. `php -l` passed for new/changed Phase 7 PHP files and `php yii list` passed. Live create/update smoke was not executed to avoid changing admin access rows without explicit safe test data.
- 2026-11-27: replaced raw GambarProfile admin `403` responses with shared Yii3 access-denied rendering via `src/Web/Shared/AccessDeniedResponse.php` and `src/Web/Shared/access-denied.php`. Unauthorized GambarProfile admin routes now keep HTTP status `403` while rendering the standard app shell, centered message `Anda tidak mempunyai akses untuk paparan ini.`, a soft access-not-granted illustration at `public/images/access-denied-soft.svg`, and a `/menu` back action. `php -l` passed for the shared access-denied template/response and touched GambarProfile admin actions; `php yii list` passed.
