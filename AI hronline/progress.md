# HROnline Yii3 Migration Progress

## 2026-11-16

### Workspace Decision

- `C:\Apache24\htdocs\hronline` is the Yii3 application root.
- `C:\Apache24\htdocs\hronline\AI hronline` is documentation only.
- `AI hronline` must contain Markdown/dev docs only, not Yii3 source code, `vendor/`, runtime files, or copied skeletons.

### Correction Made

- A Yii3 POC skeleton was briefly copied into `AI hronline` by mistake.
- That copied app content was removed.
- `AI hronline` now contains Markdown docs only.

Current docs:

- `README.md`
- `01_external_login_migration.md`
- `DEV_MODULE_MIGRATION_CHECKLIST.md`
- `progress.md`

### First Migration Target

Focus changed from general login to `external-login` first.

Reason:

- Yii2 staff login includes Portal Kinabalu and Active Directory complexity.
- Yii2 external login is smaller and better for proving the first Yii3 auth/session bridge.

Yii2 source files reviewed:

- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\models\LoginForm.php`
- `C:\Apache24\htdocs\staff\models\Identity.php`
- `C:\Apache24\htdocs\staff\models\system_core\ExternalUser.php`
- `C:\Apache24\htdocs\staff\models\system_core\logs.php`
- `C:\Apache24\htdocs\staff\views\site\external_login.php`

Important compatibility note:

- Yii2 external identity type is spelled `extenal`, not `external`.
- Yii3 must preserve `extenal-<user_id>` in the Yii2-compatible session.

### Yii3 Root Status

Confirmed `C:\Apache24\htdocs\hronline` is already a Yii3 app:

- `composer.json`
- `public/index.php`
- `src`
- `config`
- `vendor`
- `.git`

Git status could not be read because Git reported dubious repository ownership.

### Dependency Added

Ran:

```powershell
composer require yiisoft/db-mysql --no-interaction
```

Result:

- Added `yiisoft/db`
- Added `yiisoft/db-mysql`
- Updated `composer.json`
- Updated `composer.lock`

### External Login Implementation Started

Added Yii3 files:

- `src/Shared/DbConnectionFactory.php`
- `src/Shared/ExternalLoginResult.php`
- `src/Shared/Yii2SessionWriter.php`
- `src/Shared/ExternalLoginService.php`
- `src/Web/ExternalLogin/Action.php`
- `src/Web/ExternalLogin/template.php`

Implemented so far:

- Reads existing Yii2 DB config file.
- Looks up external users in `system_core.tbl_external_user`.
- Requires `access = 1`.
- Preserves legacy password checks:
  - master MD5 hash
  - stored MD5
  - stored SHA-256
- Writes Yii2-compatible `hrv4` session:
  - `__id = extenal-<user_id>`
  - `__authKey = <user_id>`
  - `__expire = time() + 1800`
- Inserts login audit row into `system_core.tbl_logs`.
- Adds Yii3 external-login page template with CSRF field.

### Not Finished Yet

Completed after initial implementation:

- Wire `/external-login` route.
- Wire Yii3 DI for DB connection.
- Add CSS for the external login page.
- Run PHP syntax checks.
- Run Yii route/server smoke checks.
- Test GET `/external-login`.
- Test POST failure path.

Verification results:

- `php -l` passed for all changed PHP files.
- `php yii list` bootstrapped successfully.
- Yii3 dev server started at `http://127.0.0.1:8084`.
- `GET /external-login` returned HTTP 200.
- External-login page includes form and CSRF token.
- Safe fake-credential `POST /external-login` returned HTTP 200 with expected invalid-login message.
- `composer validate --no-check-publish` passed; Composer still prints Git dubious ownership warnings.

Still pending:

- Test POST success path with a real/test external user.

### Files Changed In Yii3 App Root

Dependency files:

- `composer.json`
- `composer.lock`

Config/files:

- `config/common/routes.php`
- `config/common/di/application.php`
- `config/common/application.php`
- `src/Web/HomePage/template.php`
- `assets/main/site.css`

New external-login files:

- `src/Shared/DbConnectionFactory.php`
- `src/Shared/ExternalLoginResult.php`
- `src/Shared/Yii2SessionWriter.php`
- `src/Shared/ExternalLoginService.php`
- `src/Web/ExternalLogin/Action.php`
- `src/Web/ExternalLogin/template.php`

### Running Dev Server

Current local Yii3 dev server:

```text
http://127.0.0.1:8084
```

External-login URL:

```text
http://127.0.0.1:8084/external-login
```

### Known Issues / Notes

- Git status cannot run until `C:\Apache24\htdocs\hronline` is added as a safe Git directory for the current Windows user.
- Success-path login was not tested yet because no external-user test credential was provided.
- The Yii2-compatible session intentionally uses `extenal-<user_id>` to match the existing Yii2 typo.
