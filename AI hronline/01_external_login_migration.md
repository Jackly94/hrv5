# External Login Migration - Yii2 to Yii3

## Goal

Migrate the Yii2 `site/external-login` flow into Yii3 first, before touching
staff Portal Kinabalu or Active Directory login.

This is the best first login slice because it is smaller than staff login:

- Uses `LoginForm`.
- Checks `system_core.tbl_external_user`.
- Allows only users with `access = 1`.
- Uses legacy password hashes.
- Creates a Yii2-compatible session.
- Writes a login record to `system_core.tbl_logs`.

## Yii2 Source

Main files:

- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\models\LoginForm.php`
- `C:\Apache24\htdocs\staff\models\Identity.php`
- `C:\Apache24\htdocs\staff\models\system_core\ExternalUser.php`
- `C:\Apache24\htdocs\staff\models\system_core\logs.php`
- `C:\Apache24\htdocs\staff\views\site\external_login.php`

Yii2 action:

- `SiteController::actionExternalLogin()`

Yii2 identity detail to preserve:

- External identity type is spelled `extenal`, not `external`.
- Yii2 session identity should therefore be `extenal-<user_id>`.

## Yii3 Target Behavior

Route:

- `GET /external-login` shows the external login form.
- `POST /external-login` validates username/password.

On success:

- Read user from `system_core.tbl_external_user`.
- Require `access = 1`.
- Validate password using existing legacy rules:
  - master MD5 hash check
  - stored `md5(password)`
  - stored `sha256(password)`
- Write PHP session cookie named `hrv4`.
- Store Yii2-compatible session keys:
  - `__id = extenal-<user_id>`
  - `__authKey = <user_id>`
  - `__expire = time() + 1800`
- Insert login row into `system_core.tbl_logs`.
- Show a verification success screen with links to session probe and old Yii2 return URL.

On failure:

- Show validation errors.
- Do not reveal whether username or password was wrong.

## Safety Notes

- Do not copy credentials into Yii3 docs or source.
- Reuse existing Yii2 DB config by reading config locally.
- Keep CSRF enabled for the form.
- Do not implement staff AD / Portal Kinabalu login in this slice.
- Do not add hard-coded test users.

## Open Decision

Where should the Yii3 app code live?

Do not put it in `AI hronline`; this folder is docs only.
