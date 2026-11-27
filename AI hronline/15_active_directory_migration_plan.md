# Active Directory Yii3 Migration Plan

Date: 2026-11-27

## Scope

Migrate old Yii2 `staff/components/ActiveDirectory.php` into a Yii3 shared adapter that can be reused by Lantikan, login, and future biodata sync flows.

This document covers the AD component/API adapter only. It does not enable production AD create/update side effects inside Lantikan yet.

## Old Yii2 Component

Source:

- `C:\Apache24\htdocs\staff\components\ActiveDirectory.php`

Observed methods:

| Yii2 method | Endpoint | Yii3 method | Status |
| --- | --- | --- | --- |
| `Login($username, $password)` | `POST /api/v2/basic/ad/login` | `ActiveDirectoryApiClient::login()` | Migrated adapter |
| `AdEmailExist($param, $type)` | `POST /api/v1/ad/find` | `ActiveDirectoryApiClient::find()` | Migrated adapter |
| `Add($icno)` | `POST /api/v1/ad/createUser` | `ActiveDirectoryApiClient::createUserForIcno()` | Migrated adapter, not orchestrated |
| `Update($userId, $icno)` | `POST /api/v1/ad/updateUser/{userId}` | `ActiveDirectoryApiClient::updateUserForIcno()` | Migrated adapter, not orchestrated |

## Yii3 Implementation

File:

- `src/Shared/ActiveDirectoryApiClient.php`

Implemented:

- Env-based credentials only:
  - `HRONLINE_AD_ENABLE=1`
  - `HRONLINE_AD_PUBLIC_KEY`
  - `HRONLINE_AD_SECRET_KEY`
- Optional test/dummy base URL:
  - `HRONLINE_AD_BASE_URL`
- Multipart POST request builder matching the legacy API style.
- AD login endpoint support.
- AD find endpoint support for email/user validation.
- ICNO-based AD create/update payload builders using:
  - `hronline.tblprcobiodata`
  - `hronline.department`
  - `hronline.title`
  - `hronline.campus`
- Non-2xx HTTP responses are treated as failed AD requests even if the body is not JSON.

## Payload Mapping

Create-user payload:

- `ic_no` = `tblprcobiodata.ICNO`
- `email` = `tblprcobiodata.COEmail`
- `name` = `tblprcobiodata.CONm`
- `dept_code` = `department.shortname`
- `department` = `department.fullname`
- `staff_no` = `tblprcobiodata.COOldID`
- `office_phone_no` = `tblprcobiodata.COOffTelNo`
- `handphone_no` = `tblprcobiodata.COHPhoneNo`
- `title` = `title.Title`
- `ad_userid` = email local part before `@`
- `city` = `campus.campus_name`
- `is_active` = true

Update-user payload is the same except `ad_userid` is not sent; the AD user id is part of the endpoint URL.

## Current Consumers

- Staf Baru validation uses `find()` when AD is enabled.
- Staf Baru immediate email check uses the same `find()` path after local DB/email-format validation.
- Lantikan post-commit AD create is still pending and must not run inside the DB transaction.

## Guardrails

- Do not hard-code AD credentials in Yii3.
- Do not call AD create/update inside a DB transaction.
- Do not expose old debug/test create/update routes publicly.
- Do not enable AD create/update in production until durable result/failure tracking exists.
- AD disabled must be clear and recoverable in local/test.

## Task Board

- [x] `AD-01` - Move credentials out of source code into env variables.
- [x] `AD-02` - Migrate `find` / email existence adapter.
- [x] `AD-03` - Migrate AD login adapter.
- [x] `AD-04` - Migrate create-user payload builder from ICNO.
- [x] `AD-05` - Migrate update-user payload builder from ICNO.
- [x] `AD-06` - Harden HTTP error handling for non-2xx responses.
- [ ] `AD-07` - Add controlled AD API smoke test with approved non-production credentials.
- [ ] `AD-08` - Add durable post-commit result/failure tracking for AD create/update.
- [ ] `AD-09` - Wire Staf Baru AD create after DB commit, behind explicit flag and retry guard.
- [ ] `AD-10` - Decide whether staff login should use `login()` in Yii3 or remain external-login/legacy for now.

## Verification

Completed locally:

- `php -l src/Shared/ActiveDirectoryApiClient.php`
- `php yii list`

Not executed:

- No live AD API request was sent.
- No AD user was created or updated.
- No production workflow was enabled.
