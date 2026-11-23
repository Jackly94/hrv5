# HROnline Yii3 Migration Progress

## 2026-11-23

### Biodata Section CRUD Ownership

- Replaced generic Web action delegation with section-owned actions for Pendidikan, Keluarga, Pasport Permit, Bidang Kepakaran, and Vaksinasi.
- Added section CRUD adapter methods for save/delete/key resolution where needed.
- Added shared Web action helper for section redirects, query appending, uploaded files, and client IP handling.
- Added old `/keluarga/delete` compatibility route to match the new `/biodata/keluarga/delete` route.
- Replaced the final `Web\StaffRecordOverview` wrappers for Lesen, Kecacatan, and Kelayakan Profesional.
- Added local Kelayakan Profesional `view.php` and `detail.php` templates.
- Deleted unused `src/Web/StaffRecordOverview` and `src/Web/StaffRecordOverviewCrud` folders.
- Replaced `Web\StaffRecord` delegation with section-owned actions for Bahasa, Anugerah, Pengalaman Kerja, and Sejarah Perubatan.
- Deleted unused `src/Web/StaffRecord`.
- Moved the remaining generic shared Biodata internals out of root `src/Shared` and into `src/Shared/Biodata/Sections`.
- Renamed shared support classes from `StaffRecord*` to Biodata-specific names:
  - `BiodataSimpleRecordService`
  - `BiodataSimpleRecordCrudService`
  - `BiodataOverviewService`
  - `BiodataOverviewCrudService`
- Verified:
  - PHP lint passed for changed section actions, section services, helper, and routes.
  - `php yii list` bootstrapped successfully.
  - Old and new GET routes for list/detail/form pages route to login cleanly when unauthenticated.
  - Keluarga delete compatibility routes are registered; unauthenticated POST smoke is blocked by CSRF as expected.
  - No remaining references to the deleted Web overview namespaces.
  - No remaining references to the deleted `Web\StaffRecord` namespace.
  - No remaining references to root `App\Shared\StaffRecord*` services.

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
- Looks up staff users first in `hronline.tblprcobiodata`, matching Yii2 `Identity::findByUsername()`.
- Falls back to external users in `system_core.tbl_external_user`.
- Requires external-user `access = 1`.
- Preserves legacy password checks:
  - master MD5 hash
  - stored MD5
  - stored SHA-256
- Writes Yii2-compatible `hrv4` session:
  - staff: `__id = staff-<ICNO>`, `__authKey = COOldID`
  - external: `__id = extenal-<user_id>`, `__authKey = <user_id>`
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

Success-path verification:

- Browser could open `http://127.0.0.1:8084/external-login` after starting the Yii3 dev server.
- Staff ICNO `940402125181` with master password `DigitalHRA@2025` now logs in successfully.
- Successful POST returns HTTP 200 with the "Login verified" page.
- Successful POST includes a legacy `Set-Cookie: hrv4=<session-id>; Path=/; HttpOnly; SameSite=Lax` header.
- The Yii2-compatible session stores staff identity as `staff-940402125181`.

Still pending:

- Test success path with a real/test external-user account from `system_core.tbl_external_user`.

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

### External Login Troubleshooting And Fixes

Issue: browser could not open `/external-login`.

- Cause: Yii3 dev server was not running on `127.0.0.1:8084`.
- Fix: started the server with `php yii serve 127.0.0.1 --port=8084`.
- Verification: `GET /external-login` returned HTTP 200 and displayed the form with CSRF field.

Issue: staff ICNO + master password returned "Incorrect username or password."

- Cause: initial Yii3 slice only searched `system_core.tbl_external_user`.
- Yii2 `site/external-login` uses `LoginForm`, which calls `Identity::findByUsername()`.
- Yii2 lookup order is:
  - staff by `hronline.tblprcobiodata.ICNO` where `Status != 6`
  - external user by `system_core.tbl_external_user.username` where `access = 1`
- Fix: updated `ExternalLoginService` to follow the same staff-first, external-second lookup order.
- Staff session identity now uses `staff-<ICNO>`.
- External session identity still preserves the Yii2 typo `extenal-<user_id>`.

Issue: staff ICNO + master password returned "External login is unavailable. Please try again later."

- Cause: Yii3 `SessionMiddleware` disables PHP native session cookies, so the legacy writer failed at `session_set_cookie_params()`.
- Direct CLI service test succeeded, proving the DB lookup and master password were correct.
- Fix:
  - `Yii2SessionWriter::login()` now returns the generated legacy Yii2 session ID.
  - `Action` adds the legacy cookie to the PSR-7 response with `Set-Cookie`.
  - Native `setcookie()` and `session_set_cookie_params()` are no longer used for the Yii2 compatibility cookie.
- Verification:
  - `POST /external-login` with ICNO `940402125181` and master password `DigitalHRA@2025` returned HTTP 200.
  - Response contained "Login verified."
  - Response did not contain "Incorrect username or password."
  - Response did not contain "External login is unavailable."
  - Response included `Set-Cookie: hrv4=<session-id>; Path=/; HttpOnly; SameSite=Lax`.

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

## 2026-11-17

### Second Migration Target

Started the main login route after the external-login pilot.

Yii2 source reviewed:

- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\views\site\login_kinabalu.php`
- `C:\Apache24\htdocs\staff\components\ActiveDirectory.php`

Current Yii2 behavior to preserve first:

- `site/login` checks the `hr_login_method` cookie.
- `hr_login_method=portal` redirects to Portal Kinabalu.
- `hr_login_method=external` redirects to `site/external-login`.
- The visible login page is now a Portal Kinabalu handoff page.

Implemented in Yii3:

- Added `GET /login`.
- Added a Portal Kinabalu handoff template.
- Added cookie-aware redirects:
  - `hr_login_method=portal` -> `https://kinabalu.ums.edu.my/menu`
  - `hr_login_method=external` -> `/external-login`
- Updated external-login success to also set `hr_login_method=external` for two weeks.
- Updated the home page to link to `/login`.

Verification:

- `php -l` passed for the new login files and touched route/template files.
- `php yii list` bootstrapped successfully.
- Yii3 dev server started at `http://127.0.0.1:8084`.
- `GET /login` without `hr_login_method` returned HTTP 200.
- `GET /login` with `hr_login_method=external` returned HTTP 302 to `/external-login`.
- `GET /login` with `hr_login_method=portal` returned HTTP 302 to `https://kinabalu.ums.edu.my/menu`.
- Browser click test in Microsoft Edge passed:
  - `/login` rendered the HROnline Login panel.
  - Clicking `External Login` opened `/external-login`.
  - External Login form showed username, password, and CSRF fields.
  - Clicking `Portal Kinabalu` opened the Kinabalu Portal.
- Browser screenshot review found low-contrast button labels caused by normal link styling overriding button styling.
- Fixed button link text color for `.content a.button-link`.
- Browser success login test passed for staff ICNO `940402125181` using the master-password path.
- Success page showed the Yii2-compatible staff session and display name.
- Browser cookies included `hrv4`, `PHPSESSID`, and `hr_login_method`, all with values.

### Third Migration Target

Started the Yii2 dashboard migration.

Yii2 source reviewed:

- `C:\Apache24\htdocs\staff\controllers\DashboardController.php`
- `C:\Apache24\htdocs\staff\views\dashboard\index-adminlte4.php`
- `C:\Apache24\htdocs\staff\models\system_core\TblDahsboard.php`
- `C:\Apache24\htdocs\staff\models\system_core\TblPendingTask.php`
- `C:\Apache24\htdocs\staff\models\keselamatan\TblStaffKeselamatan.php`

Current Yii2 dashboard behavior to preserve first:

- Staff dashboard route is `dashboard/index`.
- Dashboard requires a logged-in Yii2 user.
- Dashboard reads `system_core.tbl_dashboard`.
- Dashboard shows active pending tasks from `system_core.tbl_pending_task`.
- Dashboard switches attendance label between `STARS` and `SKB` for security staff.
- Dashboard includes shortcut links back to Yii2 modules.

Implemented in Yii3:

- Added `GET /dashboard`.
- Added `GET /dashboard/index` as a compatibility alias.
- Added legacy `hrv4` session reader.
- Added read-only dashboard service.
- Added Yii3 dashboard template with summary tiles, pending tasks, and shortcuts.
- Added `Open Dashboard` link after external-login success.
- Added home page dashboard link.

Intentionally not migrated yet:

- Clock-in and clock-out write actions.
- Pending-task refresh command.
- Vaccination forced redirect.
- Full AdminLTE4 layout/assets.

Verification:

- `php -l` passed for the new dashboard/session/service files and touched templates/routes.
- `php yii list` bootstrapped successfully.
- Unauthenticated `GET /dashboard` returned HTTP 302 to `/login`.
- Browser test in Microsoft Edge with the existing `hrv4` login cookie opened `/dashboard`.
- Dashboard showed staff `JACKLY BIN MOSUNING`.
- Dashboard showed summary tiles for `CUTI`, `MyIDP`, `MyHealth`, and `STARS`.
- Dashboard showed 3 active pending-task cards.
- Dashboard showed shortcut links for Main Menu, Cuti, MyIDP, Klinik Panel, Laporan Kehadiran, and Keluar Pejabat.

### UI Direction Priority

Switched priority to the UI direction recorded in `C:\Apache24\htdocs\staff\AI\tailwind_flowbite_migration.md`.

Decision applied to Yii3 POC:

- Tailwind CSS + Flowbite is the preferred UI direction.
- AdminLTE4 is not the target for new UI work.
- Keep native PHP-rendered pages.
- Favor light canvas, white cards, compact density, brand navy/red accents, and Flowbite-like components.

Implemented in Yii3:

- Replaced the default Yii skeleton chrome with a HROnline app shell.
- Restyled login, external-login, and dashboard screens toward the Tailwind/Flowbite visual direction using the current plain CSS asset.
- Removed the Yii Framework logo/header/footer social chrome from the Yii3 layout.
- Kept this as a no-build first pass; a real Tailwind CLI + Flowbite asset pipeline is still pending.

Verification:

- `php -l` passed for the updated layout and visible templates.
- `php yii list` bootstrapped successfully.
- Browser UI smoke test passed for `/login`, `/external-login`, and Portal Kinabalu click-through.
- Browser login success test still passed after the UI rewrite.
- Browser dashboard test still opened `/dashboard` with the `hrv4` cookie and showed the expected staff summary, pending tasks, and shortcuts.
- Screenshot review passed for the restyled login and dashboard pages.

### Sidebar Theme Alignment

Compared the old staff sidebar in `C:\Apache24\htdocs\staff\views\layouts\side_menu.php`.

Decision applied to Yii3 POC:

- Keep the Tailwind/Flowbite visual direction.
- Restore the old staff app-shell shape with a persistent left sidebar.
- Use Yii3 routes for migrated pages and legacy Yii2 links for modules that are not migrated yet.

Implemented in Yii3:

- Added a fixed left sidebar to the main layout.
- Added sidebar entries for MyWorkspace, Menu, STARS, SKB, Profil Staf, UMS Docs, and Support System.
- Copied the old staff `hronline_logo.png` into Yii3 public assets and placed it at the top of the sidebar.
- Pointed MyWorkspace to the migrated `/dashboard`.
- Pointed non-migrated modules back to the old Yii2 staff URLs for now.
- Added a mobile sidebar toggle so the sidebar is hidden until opened on small screens, with the brand retained in the header only on mobile.

Verification:

- `php -l src\Web\Shared\Layout\Main\layout.php` passed.
- `php yii list` bootstrapped successfully.
- Browser dashboard test passed and confirmed `hasSidebar=true` and `hasSidebarLogo=true`.
- Browser dashboard screenshot showed the persistent left menu, sidebar logo, and aligned dashboard content.

### Fourth Migration Target

Started the Yii2 `biodata/userview` migration.

Yii2 source reviewed:

- `C:\Apache24\htdocs\staff\controllers\BiodataController.php`
- `C:\Apache24\htdocs\staff\views\biodata\view_user.php`
- `C:\Apache24\htdocs\staff\models\hronline\TblPrcobiodata.php`

Current Yii2 behavior to preserve first:

- Route shows the logged-in staff member's own profile.
- Uses the Yii2 identity ICNO to load `hronline.tblprcobiodata`.
- Shows staff photo from BKUMS using SHA1(ICNO).
- Shows profile/service summary fields.
- Shows 16 biodata record-section links.

Implemented in Yii3:

- Added `GET /biodata/userview`.
- Added `BiodataProfileService` as a focused read-only SQL service instead of porting the full Yii2 ActiveRecord model.
- Added a Yii3 profile template with profile photo, staff identity, service summary, and section links.
- Updated the sidebar `Profil Staf` link to the migrated Yii3 route.
- Added route-aware sidebar active highlighting.
- Updated temporary legacy links to the reachable old Yii2 dev server base `http://127.0.0.1:8083`.

Intentionally not migrated yet:

- Editing biodata details.
- Profile image upload workflow.
- Modal timeline/service tree.
- The 16 deeper record pages such as Alamat, Pendidikan, Keluarga, and Bidang Kepakaran.

Verification:

- `php -l` passed for the new biodata service/action/template and touched layout/dashboard files.
- `php yii list` bootstrapped successfully.
- Unauthenticated `GET /biodata/userview` returned HTTP 302 to `/login`.
- Browser test in Microsoft Edge opened `/biodata/userview` with the existing `hrv4` session.
- Browser test confirmed profile page, staff ICNO, staff display name, sidebar logo, active `Profil Staf` sidebar item, 20 summary fields, and 16 section links.
- Browser screenshot review passed for the migrated profile page.

### Biodata Controller Route Coverage

Expanded the Yii3 biodata migration to cover every public action route from Yii2 `BiodataController`.

Migrated to Yii3 pages/endpoints:

- `GET /biodata/index` — compact staff search/list page.
- `GET /biodata/userview` — current staff profile.
- `GET /biodata/adminview?id=...` — profile view for a selected ICNO.
- `GET /biodata/lihatbiodata` — current staff biodata alias.
- `GET /biodata/lihatbiodatakakitangan?id=...` — staff biodata alias.
- `GET /biodata/ketua-program-view?id=...` — ketua-program profile alias.
- `GET /biodata/view-a-r-p?id=...` — admin reset-password profile alias.
- `GET /biodata/penetapanpengguna?icno=...` — read-only access setting summary.
- `GET|POST /biodata/citylist` — JSON state list endpoint.
- `GET|POST /biodata/peringkatcapaiankedua` — JSON second-access-level endpoint; local DB returns an empty list because `hronline.accesssecondlevel` is not present in this schema.

Added compatibility handoff routes back to the running Yii2 dev server for unsafe or side-effect-heavy actions:

- `generate`
- `kemaskinipenetapanpengguna`
- `resetpassword`
- `change-my-password`
- `logout`
- `admin-carian-staf`
- `confirmtosave`
- `kemaskinikakitangan`
- `kemaskini`
- `padamkakitangan`
- `kemaskinikakitangan-kprogram`
- `sync-to-ad`
- `sync-to-grp`
- `sync-to-grp-non-admin`
- `sync-to-grp-all`
- `grp-inactivate-staff`
- `recreate-ad`

Verification:

- `php -l` passed for all new biodata action/template files and touched route/layout files.
- `php yii list` bootstrapped successfully.
- Browser action test passed for the migrated biodata pages using the existing `hrv4` session.
- Browser action test confirmed staff search returns Jackly records, profile aliases show 20 profile fields, access settings show the selected staff, and `Profil Staf` is active in the sidebar across `/biodata/*`.
- JSON check confirmed `/biodata/citylist?depdrop_parents=MYS` returns 16 state rows.
- Compatibility handoff check confirmed write/sync routes redirect to `http://127.0.0.1:8083/biodata/...`.
- Fixed the `Maklumat Dan Rekod Kakitangan > Biodata` section link to stay inside Yii3 at `/biodata/lihatbiodata` instead of opening the old Yii2 port.
- Browser click test confirmed clicking `Biodata` from `/biodata/userview` lands on `http://127.0.0.1:8084/biodata/lihatbiodata`.
- Split `/biodata/lihatbiodata` and `/biodata/lihatbiodatakakitangan` away from the profile-summary template.
- Added a dedicated `Lihat Biodata` detail page matching Yii2 `views/biodata/lihatbiodata.php` / `lihatbiodatakakitangan.php` with personal biodata fields, back/update actions, and admin-only employment extras for the staff-detail route.
- Browser click test confirmed `Lihat Biodata` renders a detail list and no longer renders the `userview` profile hero.

### Biodata Index GridView Modernization

Refactored `GET /biodata/index` from a compact plain staff list into a Yii2 GridView-style Yii3 page.

Implemented:

- Search panel fields matching the old Yii2 `_search.php`: Kategori Jawatan, Program Pengajaran, Kod Program, Kampus, JFPIU, Status Lantikan, Pendidikan Tertinggi, Status, Jenis Carian, and Carian Data.
- Result header matching the old GridView intent with `Jumlah Carian: filtered / total`.
- Grid columns matching the old Yii2 `GridView::widget`: IC/KP, UMSPER, Nama, Gred, Email, JFPIU, Pendidikan Tertinggi, Kampus, Status, and Tindakan.
- Status badge styling, danger for service status code `06`, success otherwise.
- `Lihat` action links to `/biodata/adminview?id=...`.
- Service-level query normalization, lookup options, filtered count, total count, and display-ready rows.

Verification:

- `php -l` passed for `BiodataProfileService.php`, `IndexAction.php`, and `index.php`.
- `php yii list` bootstrapped successfully.
- Browser user-path test opened `/biodata/index`, filled `Carian Data = Jackly`, clicked `Cari`, and confirmed the GridView returned 2 rows.
- Browser test confirmed all GridView headers render and the first row includes Jackly's ICNO, UMSPER, grade, email, JFPIU, education, campus, status, and `Lihat` action.
- Screenshot captured at `runtime/browser-output/biodata-index-grid-page.png`.

### Biodata Searchable Dropdowns

Replaced plain native dropdown behavior on `/biodata/index` with Tom Select for a Yii3-friendly replacement of the old Yii2 Kartik Select2 search experience.

Implemented:

- Added local Tom Select assets under `assets/main/vendor/tom-select`.
- Registered Tom Select CSS/JS through the Yii3 `MainAsset` bundle.
- Added `assets/main/site.js` to initialize `[data-searchable-select]` fields.
- Marked all biodata GridView filter dropdowns as searchable.
- Added HROnline styling overrides so Tom Select matches the current filter controls.

Verification:

- `php -l` passed for the touched asset bundle and biodata index template.
- `php yii list` bootstrapped successfully.
- Browser GridView test confirmed 9 select elements are enhanced with Tom Select.
- Browser Tom Select test opened the `Kampus` dropdown, typed `Kota`, confirmed the typed text is visible, and confirmed the option list filtered to `Kota Kinabalu`.
- Screenshot captured at `runtime/browser-output/biodata-tom-select-dropdown.png`.

### Biodata Main Card Layout

Adjusted `/biodata/index` to the agreed UI rule that page content should sit inside one main card/surface.

Implemented:

- Moved the `Rekod Peribadi` title/subtitle into the main card.
- Removed separate search/result cards for this page.
- Kept `Carian` and GridView results as internal sections divided by borders inside the same card.

Verification:

- `php -l src/Web/Biodata/index.php` passed.
- Browser GridView test still passed with 2 Jackly rows and Tom Select active.
- Browser Tom Select test still passed for typing `Kota` in the `Kampus` dropdown.
- Screenshot review confirmed `/biodata/index` now renders as one main card from title through table.
- Reduced the global content wrapper gutter so the main card starts closer to the fixed sidebar and uses more of the available workspace width.

### Icon Button And Soft Surface Standard

Added Lucide icons as the standard icon set for migrated Yii3 action buttons.

Implemented on `/biodata/index`:

- Added local Lucide browser asset under `assets/main/vendor/lucide`.
- Registered Lucide before `site.js` in the main asset bundle.
- Initialized Lucide icons from `site.js`.
- Added icons to `Cari`, `Reset`, and row-level `Lihat` actions.
- Softened the main card, action buttons, and row action buttons with a slightly larger radius, lighter border, and gentler shadow.

Standard going forward:

- Use Lucide icons for clear action buttons.
- Keep text + icon for primary commands unless the control is naturally icon-only and has an accessible label.
- Prefer softer card surfaces over hard Bootstrap-style boxes.

Verification:

- `php -l` passed for the touched asset bundle and biodata index template.
- Browser GridView test confirmed the search, reset, and view actions render SVG icons.
- Browser Tom Select test still passed.

### Standard Applied Across Migrated Pages

Applied the agreed UI standard to the visible migrated routes completed so far.

Implemented:

- Added reusable `page-main-card`, `page-card-heading`, `page-card-section`, and `page-section-body` layout classes.
- Converted migrated biodata profile, detail, settings, and dashboard templates to use one main content card with internal sections.
- Converted the home page to the same main-card pattern.
- Kept login and external-login as focused panel cards, but softened them to match the same visual language.
- Added Lucide icons to migrated action controls:
  - Login / external-login actions.
  - Dashboard switch-user and shortcut links.
  - Biodata profile image update, back/update actions, section links, settings handoff, GridView search/reset/view.
- Softened repeated cards on dashboard metrics/tasks/shortcuts while keeping them as valid repeated content cards.

Verification:

- `php -l` passed for all touched migrated templates and the main asset bundle.
- `php yii list` bootstrapped successfully.
- Browser public UI-standard test confirmed `/`, `/login`, and `/external-login` each render one main/panel card and icon-bearing actions.
- Browser Biodata GridView test still passed with 2 Jackly rows, Tom Select active, and action icons rendered.
- Browser Tom Select test still passed for typing `Kota` in the `Kampus` dropdown.
- Browser Biodata action test passed for migrated biodata routes before the shared browser session was later reset to an unauthenticated state by login/navigation tests.

### Biodata Profile Inner Card Trial

Tried the card-inside-card pattern on `/biodata/userview` for `Ringkasan Perkhidmatan`.

Implemented:

- Wrapped the whole `Ringkasan Perkhidmatan` section in one inner card.
- Kept the 20 service fields as a compact `dl/dt/dd` grid inside that one card.
- Added a reusable `content-inner-card` style for this pattern.

Verification:

- `php -l src/Web/Biodata/template.php` passed.
- Browser biodata profile test confirmed 20 service fields, exactly 1 summary inner card, and 16 biodata section links.
- Screenshot review confirmed this matches the intended “section inside one card” pattern.

### Biodata Detail Inner Card Trial

Applied the same card-inside-card pattern to `/biodata/lihatbiodata`.

Implemented:

- Wrapped the whole `Maklumat Biodata` section in one `content-inner-card`.
- Kept the 31 biodata fields as a compact detail list inside that one card.
- Left the page title/actions in the main card heading, matching the agreed page standard.

Verification:

- `php -l src/Web/Biodata/detail.php` passed.
- Browser detail-card test confirmed one main card, one inner card, 31 biodata rows, and action icons rendered.
- Screenshot review confirmed the biodata table is visually grouped as one inner card.

### Inner Card Standard Applied To Existing Migrated Data Sections

Applied the agreed rule that migrated detail/grid data areas should sit inside one inner card.

Implemented:

- Wrapped the `/biodata/index` GridView result header and table in one `content-inner-card`.
- Wrapped the `/biodata/penetapanpengguna` access detail grid in one `content-inner-card`.
- Added small GridView header/scroll safeguards so the result count and wide table stay readable inside the inner card.
- Left dashboard metric/task cards unchanged because they are repeated dashboard cards rather than detail/grid data sections.

Verification:

- `php -l src/Web/Biodata/index.php` passed.
- `php -l src/Web/Biodata/settings.php` passed.
- `php yii list` bootstrapped successfully.
- Browser inner-card standard test confirmed `/biodata/index` has one GridView inner card and `/biodata/penetapanpengguna` has one detail-grid inner card.
- Existing browser GridView user-path test still passed with 2 Jackly rows, Tom Select active, and search/reset/view icons rendered.

### Badan Profesional View Migration

Migrated the Yii2 `badan-profesional/view` listing into Yii3.

Yii2 source reviewed:

- `C:\Apache24\htdocs\staff\controllers\BadanProfesionalController.php`
- `C:\Apache24\htdocs\staff\views\badan-profesional\view.php`
- `C:\Apache24\htdocs\staff\models\hronline\TblBadanProfesional.php`

Implemented:

- Added `GET /badan-profesional/view`.
- Added a read-only `ProfessionalMembershipService` using `hronline.tblprprofassoc` and the same lookup tables as Yii2.
- Added a Yii3 Keahlian page using the agreed main-card + inner-card GridView pattern.
- Updated the `Keahlian` link in `/biodata/userview` to stay in Yii3.
- Kept add/view/edit record actions as handoffs to Yii2 because those workflows still write data and handle uploaded files.

Verification:

- `php -l` passed for the new service, action, template, routes, and touched biodata service.
- `php yii list` bootstrapped successfully.
- Unauthenticated `GET /badan-profesional/view` redirects to `/login`.
- Browser test clicked `Keahlian` from `/biodata/userview` and landed on `http://127.0.0.1:8084/badan-profesional/view`.
- Browser test confirmed one main card, one inner table card, ICNO/staff context, and the Yii2 add-record handoff link.
- Current test staff has no membership records, so the page shows the `Tiada Rekod` empty state.

### Searchable GridView Pagination Standard

Set the standard for migrated searchable GridView-style pages.

Decision:

- Searchable GridView/list pages show 15 records per page.
- Pagination sits in the GridView inner-card footer.
- Record range/count sits at the bottom-right of the same footer.
- Pagination links must preserve current search filters.
- Search form submissions reset naturally to page 1.

Implemented on `/biodata/index`:

- Changed the staff search query from a fixed 50-row cap to 15 rows per page.
- Added `page`, `pageCount`, and `offset` handling to the search result payload.
- Added pagination controls with Previous/Next and nearby page numbers.
- Changed the footer count from `Papar N rekod` to `Papar first-last daripada total rekod`.

Verification:

- `php -l src/Shared/BiodataProfileService.php` passed.
- `php -l src/Web/Biodata/index.php` passed.
- `php yii list` bootstrapped successfully.
- Existing browser GridView search test still passed for the Jackly search.
- Browser pagination test confirmed page 1 shows 15 rows and `Papar 1-15 daripada 6378 rekod`.
- Browser pagination test confirmed page 2 shows 15 rows and `Papar 16-30 daripada 6378 rekod`.
- Screenshot review confirmed pagination appears bottom-left and the record range appears bottom-right.

### Staff Record Section Batch Migration

Migrated the first batch of additional `Maklumat Dan Rekod Kakitangan` buttons from `/biodata/userview`.

Yii2 controllers involved:

- `BahasaController`
- `AnugerahController`
- `PengalamankerjaController`
- `SejarahperubatanController`

Implemented in Yii3:

- Added `GET /bahasa/view`.
- Added `GET /anugerah/view`.
- Added `GET /pengalamankerja/view`.
- Added `GET /sejarahperubatan/view`.
- Added a shared read-only `StaffRecordSectionService` for these simple staff-owned list sections.
- Added a shared `StaffRecord\ViewAction` and `StaffRecord\view.php` template using the current main-card + inner-card table standard.
- Updated `/biodata/userview` links for Bahasa, Anugerah, Pengalaman Kerja, and Sejarah Perubatan to stay in Yii3 on port `8084`.
- Kept `Tambah`, `Lihat`, and `Kemaskini` record actions as handoffs to Yii2 on port `8083` because those workflows still write data.

Verification:

- `php -l` passed for the new service, action, template, routes, and touched biodata service.
- `php yii list` bootstrapped successfully.
- Browser test confirmed all four buttons from `/biodata/userview` now link to Yii3 routes without opening a new tab.
- Browser test confirmed each migrated page renders one main card, one inner table card, staff context, Lucide icons, and the bottom-right `Papar ... rekod` count.
- Browser test confirmed legacy add/view/update actions still point to the old Yii2 app at `http://127.0.0.1:8083`.
- Screenshots captured:
  - `runtime/browser-output/staff-record-bahasa.png`
  - `runtime/browser-output/staff-record-anugerah.png`
  - `runtime/browser-output/staff-record-pengalamankerja.png`
  - `runtime/browser-output/staff-record-sejarahperubatan.png`

### Badan Profesional Add Flow Migration

Started migrating the write flow behind `Tambah Keahlian` from `badan-profesional/view`.

Yii2 source reviewed:

- `C:\Apache24\htdocs\staff\controllers\BadanProfesionalController.php`
- `C:\Apache24\htdocs\staff\views\badan-profesional\tambahbadanprofesional.php`
- `C:\Apache24\htdocs\staff\views\badan-profesional\_form.php`
- `C:\Apache24\htdocs\staff\models\hronline\TblBadanProfesional.php`
- `C:\Apache24\htdocs\staff\components\UAPI.php`

Implemented in Yii3:

- Added `GET|POST /badan-profesional/tambahbadanprofesional`.
- Updated the `Tambah Keahlian` button to stay in Yii3 instead of opening Yii2.
- Added a Yii3 add form with the old fields:
  - Nama Organisasi
  - Taraf Keahlian
  - Jawatan
  - Peringkat
  - No. Keahlian
  - Tarikh Mula Menyertai
  - Tarikh Tamat Menyertai
  - Yuran Dikenakan
  - URL
  - Status Keahlian
  - Keahlian Berkaitan Perubatan
  - Muat Naik Dokumen
- Added searchable Tom Select dropdowns for organisasi, taraf keahlian, and peringkat.
- Added server-side validation, including PDF-only upload and 6.0 MB limit.
- Added MediaHost upload support and Yii2-compatible inserts into:
  - `hronline.tblprprofassoc`
  - `hronline.tbl_stat`
  - `hronline.updatestatus`

Verification:

- `php -l` passed for the new add action, add template, MediaHost service, membership service, view template, and routes.
- `php yii list` bootstrapped successfully.
- Browser test confirmed `Tambah Keahlian` links to `/badan-profesional/tambahbadanprofesional` on port `8084`.
- Browser test confirmed the add form renders with one main card, one inner card, three Tom Select dropdowns, PDF file input, save icon, and no visible ICNO/UMSPER.
- Browser empty-submit test confirmed validation errors render on the Yii3 form and no record is created.
- Updated the form action standard: `Kembali` + submit button groups are centered, and submit/save buttons use the green success style.
- Full successful save with a real PDF was not executed during automated testing to avoid creating a live staff record.
- Screenshots captured:
  - `runtime/browser-output/badan-profesional-add-form.png`
  - `runtime/browser-output/badan-profesional-add-validation.png`

### Maklumat Dan Rekod Kakitangan UI Standard

Decision:

- The current standard is enough for future `Maklumat Dan Rekod Kakitangan` item migrations.
- Do not migrate every item in this section immediately.
- Migrate item-by-item only when that item becomes the next target.

Standard for each migrated item:

- Profile section button stays in Yii3 on port `8084`.
- Non-migrated profile section buttons continue to hand off to Yii2 on port `8083`.
- Listing/detail pages use one `page-main-card`.
- Data areas use one `content-inner-card`.
- Staff name may appear in the page heading context, but ICNO and `UMSPER` are not shown in card headers.
- Table/list count appears bottom-right as `Papar ... rekod`.
- Action buttons use Lucide icons.
- Form pages use the same main-card + inner-card pattern.
- `Kembali` + submit button groups are centered.
- Submit/save/hantar buttons use the green success style.
- Dropdown labels remain bold, but dropdown selected values/search text/options are normal weight.
- Searchable dropdowns use Tom Select.
- Write actions should preserve Yii2-compatible database side effects such as `tbl_stat` and `updatestatus` when fully migrated.

### Alamat Migration

Migrated the first pending `Maklumat Dan Rekod Kakitangan` item fully into Yii3.

Implemented:

- Updated the `Alamat` button in `biodata/userview` to stay in Yii3 at `/alamat/view`.
- Added local Yii3 routes:
  - `GET /alamat/view`
  - `GET /alamat/lihatalamat`
  - `GET|POST /alamat/tambahalamat`
  - `GET|POST /alamat/update`
  - `POST /alamat/delete`
  - `GET|POST /alamat/statelist`
  - `GET|POST /alamat/citylist`
- Added list, detail, add/update, delete, and dependent dropdown JSON actions.
- Added a Yii3 address service for `hronline.tblpraddr`, including owner checks by logged-in ICNO.
- Added Yii2-compatible side effects for address writes:
  - `hronline.tbl_stat`
  - `hronline.updatestatus`
  - `hronline.tblprcobiodata` last-update fields
- Added Tom Select fields for address type, country, state, and city.
- Added dependent Country -> State -> City dropdown refresh.
- Added the old `Alamat Tetap = Alamat Semasa` copy flow through `/alamat/tambahalamat?samadengan=01&tambah=03`.
- Applied the current UI standard: main card, inner card, bottom-right `Papar ... rekod`, Lucide icons, green save button, centered form actions, normal-weight dropdown content, and no ICNO/UMSPER in card headers.

Verification:

- `php -l` passed for all new Alamat actions/templates plus updated routes/profile service.
- `php yii list` bootstrapped successfully.
- Browser test confirmed the `Alamat` section button is local (`/alamat/view`) with no new tab.
- Browser test confirmed list page renders 2 records, local detail/update/delete actions, bottom-right `Papar 2 rekod`, icons, and no visible ICNO/UMSPER header text.
- Browser test confirmed detail page renders 9 fields and local update action.
- Browser test confirmed update form renders searchable dropdowns and preserves address type as disabled plus hidden input.
- Browser test confirmed add form has 4 Tom Select dropdowns, green save button, centered actions, copy-address link, and dependent Malaysia -> State -> City loading.
- Empty-submit validation was tested in browser without creating a live staff address record.
- Screenshots captured:
  - `runtime/browser-output/alamat-view.png`
  - `runtime/browser-output/alamat-detail.png`
  - `runtime/browser-output/alamat-form-validation.png`

### Remaining Maklumat Dan Rekod Kakitangan Overview Migration

Moved the rest of the `Maklumat Dan Rekod Kakitangan` section buttons into Yii3 list/detail pages.

Migrated local Yii3 overview/detail shells for:

- `Pendidikan`
- `Paspot & Permit Kerja`
- `Lesen`
- `Akaun`
- `Kecacatan`
- `Keluarga`
- `Bidang Kepakaran`
- `Program Vaksinasi`
- `Kelayakan Profesional`

Implemented:

- Added `StaffRecordOverviewService` as a shared Yii3 read/list service for the remaining modules.
- Added shared Yii3 overview and detail actions/templates.
- Added local routes:
  - `GET /pendidikan/view`
  - `GET /pendidikan/lihatpendidikan`
  - `GET /pasport-permit/view`
  - `GET /pasport-permit/lihatpasport`
  - `GET /pasport-permit/lihatpermitkerja`
  - `GET /lesen/view`
  - `GET /lesen/lihatlesen`
  - `GET /akaun/senarai-akaun`
  - `GET /akaun/lihat`
  - `GET /kecacatan/view`
  - `GET /kecacatan/lihatkecacatan`
  - `GET /keluarga/view`
  - `GET /keluarga/lihatkeluarga`
  - `GET /bidangkepakaran/view`
  - `GET /bidangkepakaran/lihatbidangkepakaran`
  - `GET /vaksinasi/index`
  - `GET /kelayakan-profesional/view`
  - `GET /kelayakan-profesional/view-cc`
- Updated `biodata/userview` section URLs so these nine buttons stay on port `8084` and no longer open Yii2 on port `8083`.
- Applied the current UI standard to the overview pages:
  - one main card
  - one inner card per list/detail area
  - Lucide action icons
  - bottom-right `Papar ... rekod`
  - no ICNO/UMSPER text in card headers

Current boundary:

- Read/list/detail display is now local Yii3 for these remaining sections.
- Add/update/delete flows for these complex modules still hand off to Yii2, except modules already fully migrated earlier.
- The next migration depth should be write flows item-by-item, starting with the simplest remaining module before Pendidikan/Keluarga/Akaun because those are larger subsystems.

Verification:

- `php -l` passed for the new overview service/actions/templates and updated routes/profile service.
- `php yii list` bootstrapped successfully.
- Browser test confirmed all nine remaining profile-section buttons now point to local Yii3 URLs with no `target="_blank"`.
- Browser test confirmed each overview page renders one main card, expected inner cards, table(s), icons, and bottom-right count footer.
- Browser test confirmed local detail pages for records that exist in the test profile: Pendidikan, Lesen, Akaun, and Keluarga.
- Screenshots captured:
  - `runtime/browser-output/overview-pendidikan.png`
  - `runtime/browser-output/overview-pasport-permit.png`
  - `runtime/browser-output/overview-lesen.png`
  - `runtime/browser-output/overview-akaun.png`
  - `runtime/browser-output/overview-kecacatan.png`
  - `runtime/browser-output/overview-keluarga.png`
  - `runtime/browser-output/overview-bidangkepakaran.png`
  - `runtime/browser-output/overview-vaksinasi.png`
  - `runtime/browser-output/overview-kelayakan-profesional.png`

### Lesen Write Flow Migration

Migrated `Lesen` from the old Yii2 staff module into Yii3 for user-owned staff records.

Implemented:

- Added local Yii3 `Lesen` service/actions/templates for:
  - `GET /lesen/lihatlesen`
  - `GET|POST /lesen/tambahlesen`
  - `GET|POST /lesen/update`
- Updated `Lesen` overview add/update/detail links to stay on port `8084`.
- Added full card-based detail UI with no ICNO/UMSPER header text.
- Added add/update form with Tom Select searchable dropdowns, centered action buttons, green save button, Lucide icons, upload validation, and old audit/stat/biodata update side effects.
- Updated dropdown/Tom Select content weight to normal (`400`) as the standard for migrated forms.

Verification:

- `php -l` passed for the new `Lesen` files, shared service, routes, and overview changes.
- `php yii list` bootstrapped successfully.
- Browser test confirmed local list/detail/update/add URLs, no legacy `8083` links inside the content card, bottom-right `Papar 2 rekod`, no visible ICNO/UMSPER text, 2 Tom Select dropdowns, green centered save button, and empty-submit validation without creating a live record.
- Screenshots captured:
  - `runtime/browser-output/lesen-view-write-flow.png`
  - `runtime/browser-output/lesen-detail-write-flow.png`
  - `runtime/browser-output/lesen-update-write-flow.png`
  - `runtime/browser-output/lesen-add-write-flow.png`
  - `runtime/browser-output/lesen-add-validation-write-flow.png`

### Kecacatan Write Flow Migration

Migrated `Kecacatan` from the old Yii2 staff module into Yii3 for user-owned staff records.

Implemented:

- Added local Yii3 `Kecacatan` service/actions/templates for:
  - `GET /kecacatan/lihatkecacatan`
  - `GET|POST /kecacatan/tambahkecacatan`
  - `GET|POST /kecacatan/update`
  - `POST /kecacatan/delete`
- Updated `Kecacatan` overview add/update/detail/delete links to stay on port `8084`.
- Updated shared overview rendering so local delete URLs use CSRF POST forms, while legacy delete handoffs remain external until their modules are migrated.
- Added detail UI, add/update form, Tom Select dropdowns, centered green save button, PDF upload validation, MediaHost delete support, and old audit/stat/biodata update side effects.

Verification:

- `php -l` passed for the new `Kecacatan` files, MediaHost service, shared overview view, overview service, and routes.
- `php yii list` bootstrapped successfully.
- Browser test confirmed the no-record list state, local add URL, no legacy `8083` links inside the content card, bottom-right `Papar 0 rekod`, 2 Tom Select dropdowns, green centered save button, and empty-submit validation without creating a live record.
- The current test profile has no Kecacatan rows, so existing-record detail/update/delete browser paths were not exercised.
- Screenshots captured:
  - `runtime/browser-output/kecacatan-view-write-flow.png`
  - `runtime/browser-output/kecacatan-add-write-flow.png`
  - `runtime/browser-output/kecacatan-add-validation-write-flow.png`

### Rekod Peribadi Legacy Link Sweep

Removed remaining Yii2 `8083` redirects from Rekod Peribadi content-card actions.

Implemented:

- Added `RecordAction\Action` as a local Yii3 action landing page for modules whose full write/save/delete flow has not been migrated yet.
- Added local Yii3 detail pages for generic staff record sections:
  - `Bahasa`
  - `Anugerah`
  - `Pengalaman Kerja`
  - `Sejarah Perubatan`
- Converted content-card add/update/detail buttons from Yii2 legacy URLs to local Yii3 URLs for:
  - `Biodata`
  - `Pendidikan`
  - `Bahasa`
  - `Anugerah`
  - `Paspot & Permit Kerja`
  - `Akaun`
  - `Pengalaman Kerja`
  - `Sejarah Perubatan`
  - `Keluarga`
  - `Bidang Kepakaran`
  - `Vaksinasi`
  - `Kelayakan Profesional`
- Converted profile-level buttons to local Yii3:
  - `Kemaskini Gambar`
  - `Kemaskini Biodata`
  - `Kemaskini Tetapan`

Current boundary:

- All Rekod Peribadi content buttons now open Yii3 routes and no longer open port `8083`.
- Modules already fully migrated keep their real Yii3 CRUD behavior (`Alamat`, `Lesen`, `Kecacatan`, and created Keahlian add flow).
- Larger modules that are not fully migrated yet now have Yii3 action landing pages first; their actual save/delete persistence should still be completed item-by-item.

Verification:

- `php -l` passed for changed PHP files.
- `php yii list` bootstrapped successfully.
- Browser legacy-link audit confirmed zero `8083` links/forms inside Rekod Peribadi content cards.
- Browser action-click test opened converted buttons and confirmed Yii3 main cards, no not-found state, and no `8083` markup.

### Yii2 Component Migration Phase 0

Executed Phase 0 mapping and boundary work for component-to-service migration.

Implemented:

- Added the component migration tracker at `AI hronline/COMPONENT_MIGRATION_TASKS.md`.
- Completed the Phase 0 component-to-service mapping table.
- Marked which Yii2 component methods are actively involved in Rekod Peribadi modules.
- Set the Yii3 naming convention:
  - services for business operations
  - repositories for read-only entity lookup
  - gateways for external systems
  - formatters for pure display/date formatting
  - authorization/policy services for access decisions
- Clarified that old Yii2 `Metadata` is a controller/action scanner, while dropdown/reference data should be handled separately by `ReferenceDataService`.

Phase 0 mapping outcome:

- `MaklumatPeribadi::Tarikh()` and overlapping `DateHelper` display behavior map to `StaffDateFormatter`.
- `MaklumatPeribadi::BiodataLastUpdate()` maps to `BiodataTouchService`.
- Yii2 model audit/stat write patterns map to `AuditTrailService` and `RecordStatService`.
- `UAPI` / `Yii::$app->FileManager` maps to `MediaHostFileService`.
- `AccessManager::Allowed()` and selected `MaklumatPeribadi` access helpers map to `AuthorizationService`.
- `EmployeeMaster` / `Yii::$app->GRP` should become an external `GrpEmployeeGateway` for admin sync flows, not normal staff self-service CRUD.
- Repeated dropdown SQL maps to `ReferenceDataService`.

Verification:

- Browser Phase 0 baseline audit ran through Edge CDP as the logged-in normal staff user.
- Confirmed current migrated Rekod Peribadi pages open on `http://127.0.0.1:8084`.
- Confirmed zero `8083` links/forms inside the audited Rekod Peribadi content cards.
- Pages checked included Biodata, Alamat, Pendidikan, Bahasa, Keahlian, Anugerah, Paspot & Permit Kerja, Lesen, Akaun, Kecacatan, Pengalaman Kerja, Sejarah Perubatan, Keluarga, Bidang Kepakaran, Vaksinasi, and Kelayakan Profesional.

### Yii2 Component Migration Foundation Phases

Executed the component migration foundation after Phase 0.

Implemented:

- Added `StaffDateFormatter` to replace ad hoc date formatting and handle:
  - `d/m/Y` display
  - `Y-m-d` form values
  - empty and `0000-00-00` fallback
  - Malay month display when needed
- Added `BiodataTouchService`, `AuditTrailService`, and `RecordStatService`.
- Added `ReferenceDataService` for shared dropdown/Tom Select option queries.
- Added `AuthorizationService` for controller access, own-record checks, Ketua Program same-program checks, and FPSK PP checks.
- Added `EmployeeRepository` for basic staff lookup by ICNO/identity, display name, staff number, department, and campus.
- Expanded `MediaHostFileService` with:
  - `viewUrl()`
  - `downloadUrl()`
  - `fileInfo()`
  - `fileName()`
  - missing/deleted hash fallbacks

Refactored existing services:

- `AddressRecordService` now delegates audit/stat/biodata-touch/reference-data/staff-lookup work to shared services.
- `LicenseRecordService` now delegates date/reference/audit/stat/biodata-touch/staff-lookup work to shared services.
- `DisabilityRecordService` now delegates date/reference/audit/stat/biodata-touch/staff-lookup work to shared services and no longer builds the MediaHost view URL directly.
- `ProfessionalMembershipService` now delegates date/reference/audit/stat/staff-lookup work to shared services.
- `StaffRecordOverviewService` and `StaffRecordSectionService` now use shared date formatting and staff lookup.
- `BiodataProfileService` now uses shared date formatting; deeper profile repository extraction remains open.

Current boundary:

- Phase 1, Phase 2, Phase 3, and Phase 4 are completed for the current migrated Rekod Peribadi services.
- Phase 5 is partially implemented: `AuthorizationService` exists, but controller/action wiring and unauthorized browser tests remain open.
- Phase 6 is partially implemented: `EmployeeRepository` exists and is used by record services, but `BiodataProfileService` still has broader profile SQL that should be extracted carefully later.
- Phase 7 is now a migration rule for future write flows; the actual remaining forms still need to be migrated one module at a time.

Verification:

- `php -l` passed for all changed shared service files.
- `php yii list` bootstrapped successfully.
- Browser login was refreshed through the external-login flow for staff `940402125181`.
- Browser legacy-link audit confirmed migrated Rekod Peribadi pages still open on `8084` and content cards have zero `8083` links/forms.
- Browser tests passed for:
  - Alamat list/detail/update/add validation-safe flow
  - Badan Profesional add validation-safe flow
  - Lesen list/detail/update/add validation-safe flow
  - Kecacatan list/add validation-safe flow
  - Remaining overview pages
  - Bahasa/Anugerah/Pengalaman Kerja/Sejarah Perubatan sections
  - Dashboard and Biodata userview
- No live record create/delete or real upload/delete test was performed.

### Rekod Peribadi MediaHost File Display Mapping

Mapped Yii2 `UAPI` / `Yii::$app->FileManager` display usage into Yii3 `MediaHostFileService` for migrated Rekod Peribadi items.

Implemented:

- `StaffRecordOverviewService` now injects `MediaHostFileService`.
- `lihatpendidikan` now reads `tblpreduach.filename` and renders a `Lihat Fail` MediaHost button when the Yii2 hash exists.
- Overview-driven detail pages now support attachment metadata for:
  - pendidikan: `filename`
  - akaun: `attachment`
  - kecacatan: `filename`
  - keluarga OKU: `dis_filename_kad_oku`, `dis_filename_sijil_lahir`, `dis_filename_kad_temujanji`, `dis_filename_pengesahan_dr`
  - bidang kepakaran: `filename`
  - vaksinasi: `sijil_digital`, `lampiran`
  - kelayakan profesional: `proof`
- `lesen/lihatlesen` now exposes `fileUrl` from `MediaHostFileService::viewUrl()` and renders the file as a `Lihat Fail` button.
- Recorded the full file-column mapping in `AI hronline/COMPONENT_MIGRATION_TASKS.md`.

Verification:

- `php -l` passed for changed service/template files.
- `php yii list` bootstrapped successfully.
- Browser test through Edge CDP opened `http://127.0.0.1:8084/pendidikan/lihatpendidikan?id=11392`.
- Confirmed `lihatpendidikan` renders `Lihat Fail` with MediaHost URL `https://mediahost.ums.edu.my/api/v1/viewFile/aedb8cf7a232595996891f95bcf0bc52`.
- Simulated a mouse click on `Lihat Fail`; Edge opened the MediaHost view URL in a new tab.
- The only `8083` references found on that page were global side-menu links (`Menu`, `STARS`, `SKB`, `Support System`), not Rekod Peribadi content-card/file actions.

### Rekod Peribadi CRUD Migration Batch 1

Started converting remaining Yii3 placeholder action pages into real add/update/delete flows.

Implemented real Yii3 CRUD for simple non-file sections:

- `Bahasa`
- `Anugerah`
- `Pengalaman Kerja`
- `Sejarah Perubatan`

Implementation details:

- Added `StaffRecordSectionCrudService`.
- Added shared `StaffRecord\FormAction`, `StaffRecord\DeleteAction`, and reusable form template.
- Swapped the four module add/update routes away from `RecordAction`.
- Added POST delete routes with CSRF forms.
- Added shared side effects for these sections:
  - `AuditTrailService`
  - `RecordStatService`
  - `BiodataTouchService`
  - `ReferenceDataService`
  - `StaffDateFormatter`
- Kept the existing UI standard: one main card, inner form card, centered actions, green save button, Tom Select dropdowns, and icon buttons.

Verification:

- `php -l` passed for all changed files.
- `php yii list` bootstrapped successfully.
- Browser test through Edge CDP created, updated, and deleted one dummy record for each migrated section:
  - Bahasa dummy `id=10305`
  - Anugerah dummy `awdId=15873`
  - Pengalaman Kerja dummy `id=8862`
  - Sejarah Perubatan dummy `id=382`
- Post-test DB check confirmed zero dummy leftovers for all four sections.

### Keahlian CRUD Route Wiring

Extended `Badan Profesional / Keahlian` beyond the previous add-only migration.

Implemented:

- `/badan-profesional/lihatbadanprofesional` now renders a real Yii3 detail card.
- `/badan-profesional/update` now renders and saves through the Yii3 form.
- `/badan-profesional/delete` now uses a CSRF POST action.
- `ProfessionalMembershipService` now supports:
  - detail display with MediaHost `Lihat Fail`
  - edit values
  - shared create/update save path
  - optional file replacement on update
  - MediaHost delete before DB delete
  - audit/stat side effects
- Keahlian list now shows updated/deleted success alerts and a delete icon button.

Verification:

- `php -l` passed for Keahlian service/actions/templates and route file.
- `php yii list` bootstrapped successfully.
- Current logged-in test staff has no existing `tblprprofassoc` Keahlian rows, so existing-record browser detail/update paths could not be exercised without creating a live dummy membership.
- Live dummy Keahlian CRUD test remains open because it requires uploading a PDF to MediaHost, updating that same dummy, then deleting it and the MediaHost file.

### Shared Popup Standard

Decision:

- Redirect result messages use a shared popup region in the main layout, centered near the top of the screen.
- Success/update/delete popups can be manually closed with `X` and auto-hide after 5 seconds.
- Validation summary and technical error popups remain manual-close only so users can screenshot them.
- Popups animate in, then animate out when manually closed or when the success timeout completes.
- Technical error popups must also be persistent so users can screenshot the message.
- Field-level validation remains inline inside forms.
- Delete confirmation remains a separate confirmation before the destructive action.

Implementation:

- Added the shared toast region to `src/Web/Shared/Layout/Main/layout.php`.
- Added shared popup behavior in `assets/main/site.js` for `?created=1`, `?updated=1`, `?deleted=1`, and `?error=1`.
- Added popup styling in `assets/main/site.css`.
- Removed old inline success strips from migrated list pages so they use the global popup standard:
  - `Alamat`
  - `Keahlian`
  - shared `StaffRecord` sections such as Bahasa, Anugerah, Pengalaman Kerja, and Sejarah Perubatan.

Verification:

- `php -l` passed for the updated layout and migrated list templates.
- `php yii list` bootstrapped successfully.
- Browser test through Edge CDP opened `/alamat/view?created=1`.
- Confirmed one success popup renders centered near the top with close button.
- Confirmed no old inline success strip remains on the migrated list content card.
- Confirmed the popup remains visible before the 5-second timeout and auto-hides after 5 seconds.
- Confirmed manual close still works before the 5-second auto-hide.
- Confirmed popup entry and close animations are active; close animation removes the popup after the fade-out completes.

### Kelayakan Profesional CRUD Route Wiring

Fixed the placeholder action behind `Kelayakan Profesional` write actions.

Implemented:

- `/kelayakan-profesional/add-cl-cert` now renders and submits a real Yii3 form.
- `/kelayakan-profesional/update-cc` now renders and submits through the same Yii3 form.
- `/kelayakan-profesional/delete-cc` now uses a CSRF POST action.
- Added `ProfessionalCertificateService` for:
  - clinical certificate type dropdowns
  - professional certificate field/category dropdowns
  - create/update validation
  - MediaHost proof upload
  - MediaHost proof delete before DB delete
  - audit/stat/biodata-touch side effects
- Added the Yii3 Kelayakan Profesional form using the current standards:
  - one page main card
  - one inner content card
  - searchable Tom Select dropdowns
  - centered `Kembali` and green `Simpan`
  - file validation for PDF/JPG/JPEG/PNG up to 6 MB
  - `Other/Lain-lain` type reveals the `Jenis Lain-lain` field.

Verification:

- `php -l` passed for the new service/actions/template and updated route/reference files.
- `php yii list` bootstrapped successfully.
- Browser test through Edge CDP opened `/kelayakan-profesional/view`, clicked `Tambah Sijil`, and landed on `/kelayakan-profesional/add-cl-cert`.
- Confirmed the real Yii3 form rendered and the old `RecordAction` placeholder text was absent.
- Confirmed certificate type and field dropdown options loaded.
- Confirmed selecting `Other/Lain-lain` reveals `Jenis Lain-lain`.
- Submitted an empty form and confirmed inline validation errors render on the form.
- Live dummy create/update/delete with MediaHost upload remains open; do only with an explicit dummy file test.

### Pendidikan Upload Error Fix

Fixed the internal server error seen when submitting `/pendidikan/tambahpendidikan` with `dummy-2MB.pdf`.

Root cause:

- PHP 8.5 reports `curl_close()` as deprecated.
- The MediaHost upload path called `curl_close()` after the cURL request.
- In the web error handler, that deprecation could surface as an internal server error before the form had a chance to show the upload failure inline.

Implementation:

- Removed deprecated `curl_close()` calls from `MediaHostFileService`.
- Kept the existing MediaHost failure handling so connection/upload problems return as field-level validation errors.

Verification:

- Direct MediaHost upload service check returned a normal failure result instead of a PHP fatal:
  - `Failed to connect to mediahost.ums.edu.my:443 ... Could not connect to server`
- Browser test through Edge CDP opened `/pendidikan/tambahpendidikan`, selected `C:\Users\jack9\Downloads\dummy-2MB.pdf`, submitted the form, and confirmed:
  - the page stayed on `Tambah Pendidikan`
  - no Yii error page appeared
  - the file field displayed the MediaHost connection error inline.
- `php -l src/Shared/MediaHostFileService.php` passed.
- `php yii list` bootstrapped successfully.

Follow-up from old Yii2 UAPI review:

- Old Yii2 uses `components/UAPI.php` as `Yii::$app->FileManager`.
- The Yii2 UAPI setup uses the same MediaHost upload/delete/view/info endpoints, credential pair, uploader id, category `04`, accessibility `01`, overwrite `0`, and multipart upload style that Yii3 now mirrors.
- The MediaHost connection failure is therefore an environment/network reachability issue, not a Yii3 credential mismatch.
- Aligned Yii3 migrated directory strings with old Yii2 for file-backed Rekod Peribadi items:
  - Pendidikan: `Maklumat Rekod Kakitangan/Pendidikan`
  - Bidang Kepakaran: `Maklumat Rekod Kakitangan/Bidang Kepakaran`
  - Keluarga OKU documents: `Maklumat Rekod Kakitangan/keluarga/oku`

### Remaining Rekod Peribadi Placeholder CRUD Sweep

Rewired the remaining Rekod Peribadi item CRUD routes away from `RecordAction`.

Implemented shared CRUD wiring for:

- Pendidikan
- Paspot
- Permit Kerja
- Akaun
- Keluarga
- Bidang Kepakaran
- Program Vaksinasi

Implementation details:

- Added `StaffRecordOverviewCrudService` as the shared service for remaining overview-driven record forms.
- Added `StaffRecordOverviewCrud\FormAction` and `StaffRecordOverviewCrud\DeleteAction`.
- Added reusable `StaffRecordOverviewCrud/form.php`.
- Added reference dropdown helpers for:
  - institutions
  - majors
  - passport types
  - expertise clusters
  - relationships
  - vaccine types
  - banks
- Added local delete routes for Pendidikan and Keluarga so overview delete buttons can stay in Yii3.
- Updated overview row URLs so Vaksinasi update links carry the row id instead of opening a generic placeholder.
- Preserved the shared standards:
  - page main card
  - inner content card
  - Tom Select searchable dropdowns
  - centered `Kembali` and green `Simpan`
  - inline validation
  - MediaHost upload/delete hooks for configured file fields
  - audit/stat/biodata-touch side effects
  - redirect popup flags after create/update/delete.

Verification:

- `php -l` passed for the new shared service/actions/template plus updated route/reference/overview files.
- `php yii list` bootstrapped successfully.
- Route scan confirmed the audited Rekod Peribadi item CRUD routes are no longer wired to `RecordAction`.
- Browser test through Edge CDP opened these routes and confirmed real forms render with no placeholder text:
  - `/pendidikan/tambahpendidikan`
  - `/pasport-permit/tambahpasport`
  - `/pasport-permit/tambahpermitkerja`
  - `/akaun/permohonan-penukaran-akaun-gaji`
  - `/keluarga/ums-nonums`
  - `/bidangkepakaran/tambahbidangkepakaran`
  - `/vaksinasi/view-st-vaksinasi`
  - `/vaksinasi/update-st-vaksinasi`
- Empty-submit browser checks confirmed inline validation remains on each form.
- Live dummy add/update/delete tests remain open for these modules, especially file-backed flows that touch MediaHost.

### Biodata Update Route Wiring

Moved the Biodata update placeholder routes into real Yii3 forms.

Implemented:

- Added `BiodataUpdateService` for focused `hronline.tblprcobiodata` update handling.
- Added `Biodata\FormAction` and `Biodata\form.php`.
- Rewired:
  - `GET|POST /biodata/kemaskini`
  - `GET|POST /biodata/kemaskinikakitangan`
- Preserved the old route behavior:
  - self-update returns to `/biodata/lihatbiodata`
  - selected-staff update returns to `/biodata/adminview?id=...`
  - disabled old Yii2 identity fields are displayed but not overwritten by POST
- Added biodata reference dropdown helpers for titles, religion, race, ethnicity, blood type, gender, marital status, nationality status, uniform status, teaching program, administrative position, job grade, and department.
- Save path uses shared services:
  - `AuthorizationService`
  - `AuditTrailService`
  - `RecordStatService`
  - `BiodataTouchService`
  - `StaffDateFormatter`
  - `ReferenceDataService`
- Added checkbox styling for the shared record form layout.

Verification:

- `php -l` passed for:
  - `src/Shared/BiodataUpdateService.php`
  - `src/Web/Biodata/FormAction.php`
  - `src/Web/Biodata/form.php`
  - `src/Shared/ReferenceDataService.php`
  - `config/common/routes.php`
- `php yii list` bootstrapped successfully.
- Yii3 dev server started at `http://127.0.0.1:8084`.
- Authenticated browser-style HTTP check confirmed `/biodata/kemaskini` renders a real Yii3 form with CSRF, Tom Select fields, and no `RecordAction` placeholder.
- Empty-submit validation check confirmed the form stays on HTTP 200 and shows inline `diperlukan` errors.
- Authenticated selected-staff check confirmed `/biodata/kemaskinikakitangan?id=940402125181` renders the admin form fields and returns to `/biodata/adminview?id=940402125181`.

Not done:

- No live biodata value was changed and saved in the database.
- `/biodata/kemaskinipenetapanpengguna` and `/gambar-profile/semakan` still use the local `RecordAction` placeholder.

### Main Menu Analysis And Migration Plan

Analyzed the old Yii2 sidebar `Menu` target, `site/menu`, for Yii3 migration planning.

Yii2 source reviewed:

- `controllers/SiteController.php`
- `views/site/menu.php`
- `views/layouts/side_menu.php`
- `views/layouts/adminlte4.php`
- `models/TblMenuCategory.php`
- `models/system_core/TblMenuSide.php`
- `models/system_core/TblMenuSideChild.php`
- `models/system_core/RefIcon.php`

Findings:

- The old `Menu` page is a dynamic DB-backed menu, separate from the smaller fixed sidebar.
- It reads active rows from:
  - `system_core.tbl_menu_category`
  - `system_core.tbl_menu_side`
- Current active data size:
  - 7 categories
  - 71 top-level menu rows
  - 41 child rows
- The old view renders category cards with collapsible child menu lists.
- Internal old Yii2 routes are generated through `Url::to([$url])`.
- External HTTPS links open in a new tab.
- The old view evaluates DB `visible` PHP expressions with `eval()`.

Migration decision:

- Do not port Yii2 `eval()` behavior into Yii3.
- Add a Yii3 `MenuVisibilityService` with explicit allow-listed visibility checks.
- Preserve DB-driven menu flexibility: categories, labels, URLs, parent/child structure, icons, status flags, and future rows should continue to come from `system_core.tbl_menu_category` and `system_core.tbl_menu_side`.
- Keep route mapping centralized so newly migrated Yii3 modules can be switched from Yii2 handoff to local Yii3 links without changing templates.
- Unsupported visibility expressions must be hidden by default until mapped.
- Prefer local Yii3 URLs for migrated routes and legacy `http://127.0.0.1:8083/...` handoffs for non-migrated modules.

Plan artifact added:

- `AI hronline/03_menu_migration_plan.md`

Follow-up:

- Split `03_menu_migration_plan.md` into executable tasks `MNU-01` through `MNU-10`.
- Each task now has status, goal, files, implementation notes, acceptance criteria, and verification steps.

### Main Menu Migration Execution

Executed the menu migration task plan.

Implemented:

- Added `src/Shared/MenuService.php`.
- Added `src/Shared/MenuVisibilityService.php`.
- Added `src/Web/Menu/Action.php`.
- Added `src/Web/Menu/template.php`.
- Added `GET /menu`.
- Updated the sidebar `Menu` link from old Yii2 `http://127.0.0.1:8083/site/menu` to Yii3 `/menu`.
- Updated dashboard `Main Menu` shortcut to Yii3 `/menu`.
- Added menu page CSS for category cards, collapsible sections, local links, legacy badges, and mobile layout.

Behavior:

- Menu remains DB-driven from:
  - `system_core.tbl_menu_category`
  - `system_core.tbl_menu_side`
- Migrated known routes map to local Yii3 URLs.
- Unknown relative routes hand off to old Yii2 at `http://127.0.0.1:8083/...`.
- HTTPS links remain external.
- Yii2 `eval()` behavior was not ported.
- Visibility is handled by safe explicit rules.
- Unsupported visibility expressions are hidden by default.
- Unsupported visibility audit appears only with `/menu?debug=1`.

Visibility support added:

- empty `visible` rules
- staff profile exists/status rules
- `system_core.tbl_user_access`
- `accessLevel` / `accessSecondLevel`
- Ketua Program
- internal staff checks
- security staff
- security access
- department chief/PP
- simple hard-coded ICNO allowlists

Current visibility audit:

- `/menu?debug=1` reports 23 unsupported hidden rules for the current test user.
- Remaining examples include Pementoran, Allocation Profile, Kad Staff, Pelawat, Rekod Kompetensi, Saman Keselamatan, and Tindakan Tatatertib.
- These remain hidden until their module-specific admin/access tables are explicitly mapped.

Verification:

- `php -l` passed for:
  - `src/Shared/MenuVisibilityService.php`
  - `src/Shared/MenuService.php`
  - `src/Web/Menu/Action.php`
  - `src/Web/Menu/template.php`
  - `config/common/routes.php`
  - `src/Web/Shared/Layout/Main/layout.php`
  - `src/Shared/DashboardService.php`
- `php yii list` bootstrapped successfully.
- Code scan confirmed no `eval()` or `assert()` in `src`.
- Unauthenticated `/menu` returns HTTP 302 to `/login`.
- Authenticated `/menu` returns HTTP 200.
- Authenticated `/menu` renders local Yii3 links, legacy `8083` handoffs, and collapsible sections.
- Dashboard and sidebar navigation now point to `/menu`.

Not done:

- Browser screenshot tooling was not available in this session, so screenshot review was not performed.
- Full Yii2-vs-Yii3 visibility parity is intentionally incomplete for unsupported module-specific rules.
### STARS Side Menu Migration Slice

Executed the first STARS migration slice from `AI hronline/04_stars_migration_plan.md`.

Implemented:

- Added read-only `AttendanceService` for the STARS landing summary.
- Added Yii3 route/action/template for `GET /kehadiran/index`.
- Changed the sidebar `STARS` link from old Yii2 `8083/kehadiran/index` to Yii3 `/kehadiran/index`.
- Added `/kehadiran/*` active-state handling for the sidebar.
- Added STARS landing page CSS.
- Kept all deeper STARS menu links as legacy Yii2 handoffs with `Yii2` badges.

Verification:

- `php -l` passed for:
  - `src/Shared/AttendanceService.php`
  - `src/Web/Kehadiran/IndexAction.php`
  - `src/Web/Kehadiran/index.php`
- `php yii list` bootstrapped successfully.
- Unauthenticated `/kehadiran/index` returned HTTP 302 to `/login`.
- Authenticated `/kehadiran/index` returned HTTP 200 for `staff-940402125181`.
- Authenticated smoke confirmed STARS heading, UMS Go notice, Jackly staff session, active sidebar logic, and legacy Yii2 badges.
- Page smoke confirmed no old Yii2 `x_panel`, SweetAlert, Bootstrap 3, jQuery call, or old clock-in form marker.

Caveat:

- Old Yii2 `http://127.0.0.1:8083/kehadiran/index` was not reachable during this run, so old-page browser comparison is deferred. Source and database baselines were recorded in the STARS plan.

### STARS Module Top Menu

Executed `STARS-05 - Module Top Menu` from `AI hronline/04_stars_migration_plan.md`.

Implemented:

- Moved STARS navigation into a visible module-level top menu directly below the STARS page heading.
- Reused `AttendanceService` menu sections so role visibility stays centralized.
- Kept non-migrated STARS links as legacy Yii2 handoffs with `Yii2` badges.
- Removed the duplicate lower landing-page menu.
- Aligned the STARS page with the agreed Tailwind/Flowbite-style Yii3 standard:
  - one main card
  - internal page sections
  - one `content-inner-card` for attendance detail/card-colour data
  - no ICNO in the main card heading
- Added responsive desktop dropdown and mobile stacked menu styles using the current no-build CSS layer.

Verification:

- `php -l src/Web/Kehadiran/index.php` passed.
- `php -l src/Shared/AttendanceService.php` passed.
- Authenticated `/kehadiran/index` returned HTTP 200 for `staff-940402125181`.
- Smoke confirmed the top menu, `content-inner-card`, `Staff`, `Officer`, UMS Go notice, and `Yii2` badges render.
- Smoke confirmed the main card heading no longer includes the staff ICNO.
- Smoke confirmed no old Yii2 `x_panel`, Bootstrap 3, or jQuery call markers.

### Cuti Menu Migration Slice

Executed the Cuti migration plan from `AI hronline/05_cuti_migration_plan.md` as a Yii3 hybrid slice.

Implemented:

- Added Cuti access, menu, dashboard, record, entitlement, reference, and GCR read services.
- Added local Yii3 route/action/template for `GET /cuti/individu/index`.
- Added local staff read-only routes:
  - `/cuti/individu/list`
  - `/cuti/individu/leave-detail`
  - `/cuti/individu/pilih`
  - `/cuti/individu/statement`
  - `/cuti/individu/entitlement`
  - `/cuti/individu/substitute-list`
  - `/cuti/individu/gcr-list`
  - `/cuti/individu/jsoncalendar`
- Added Cuti internal menu rendering with old role visibility mapped through Yii3 services.
- Switched `/menu` DB-backed Cuti link to local `/cuti/individu/index`.
- Switched dashboard shortcut `Cuti` to local `/cuti/individu/index`.
- Added authenticated legacy fallback routes for write-heavy staff, officer, supervisor, observer, admin, report, upload, cancel, and surat paths.
- Added Cuti UI styles.

Verification:

- `php -l` passed for all new Cuti service, action, and template files.
- `php -l` passed for touched shared files:
  - `config/common/routes.php`
  - `src/Shared/MenuService.php`
  - `src/Shared/DashboardService.php`
- `php yii list` bootstrapped successfully.
- Authenticated HTTP smokes returned `200` for:
  - `/cuti/individu/index`
  - `/cuti/individu/list`
  - `/cuti/individu/pilih`
  - `/cuti/individu/statement`
  - `/cuti/individu/entitlement`
  - `/cuti/individu/substitute-list`
  - `/cuti/individu/gcr-list`
  - `/cuti/individu/jsoncalendar`
  - `/menu`
- Authenticated `/menu` smoke confirmed Cuti now links to `/cuti/individu/index`.
- Authenticated fallback smokes returned `302` to Yii2 for officer/supervisor/admin/form routes.
- Code scan found no `eval()` or `assert()` in new Cuti code.

Not done:

- Write workflows for apply leave, substitute agreement, upload, cancel, officer approval, admin settings, PDF generation, and mobile/API remain Yii2-backed fallbacks until test-safe write fixtures and PDF/API standards are confirmed.

### Cuti Module Top Menu

Aligned the Cuti module navigation with the STARS top-menu pattern.

Implemented:

- Changed `src/Web/Cuti/Shared/menu.php` from stacked section cards to module-level dropdown groups using the same `attendance-top-menu` structure used by STARS.
- Kept Cuti role visibility centralized in `CutiMenuService`.
- Kept non-migrated Cuti routes marked with `Yii2` badges.
- Updated `src/Web/Cuti/Individu/index.php` heading to show staff name without ICNO, matching the current STARS module heading style.
- Because local Cuti pages include the shared menu partial, the top menu appears on the landing page and local subpages.

Verification:

- `php -l src/Web/Cuti/Shared/menu.php` passed.
- `php -l src/Web/Cuti/Individu/index.php` passed.
- `php yii list` bootstrapped successfully.
- Authenticated `/cuti/individu/index` returned HTTP 200.
- Authenticated `/cuti/individu/list` returned HTTP 200.
- Smoke confirmed the Cuti top menu, dropdown groups, Staff Menu, and `Yii2` badges render.
- Smoke confirmed the Cuti heading no longer includes the staff ICNO.

### Cuti Top Menu Migration Status

Recorded the current Cuti top-menu migration status in `AI hronline/05_cuti_migration_plan.md`.

Current status:

- Staff Menu: migrated for the core local Yii3 staff workflows.
- Officer Menu: not migrated; still Yii2 fallback.
- Supervisor Menu: not migrated; still Yii2 fallback.
- Admin Menu: not migrated; still Yii2 fallback.
- Report: not migrated; still Yii2 fallback.
- Pentadbiran: not migrated; still Yii2 fallback.
- Pemantau: not migrated; still Yii2 fallback.

Staff Menu migrated submenus:

- Apply Leave
- Substitute
- GCR & CBTH Application
- Leave Statement
- Leave Entitlement
- Application List
- Substitute List
- My GCR & CBTH Application List

Staff Menu caveat:

- Maternity `surat`, `upload`, and `cancel-leave` support actions remain legacy/deferred.

### Workspace Rest Point

Recorded before pausing.

STARS status:

- `AI hronline/04_stars_migration_plan.md` is the active STARS task board.
- Completed STARS tasks:
  - `STARS-01 - Route Inventory And Test Fixtures`
  - `STARS-02 - Core Attendance Service`
  - `STARS-03 - Yii3 Landing Page`
  - `STARS-04 - Sidebar Cutover`
  - `STARS-05 - Module Top Menu`
  - `STARS-06 - Staff Read-Only Routes`
- Next STARS task is `STARS-07 - Officer And Supervisor Read Reports`.
- Current Yii3 STARS route is `/kehadiran/index`.
- Sidebar `STARS` opens `/kehadiran/index`.
- Non-migrated STARS links remain Yii2 handoffs with `Yii2` badges.

UI requirement to preserve:

- Follow the agreed Tailwind/Flowbite direction.
- Current Yii3 implementation uses the no-build CSS layer as the Tailwind/Flowbite-style bridge.
- Use one `page-main-card` per page.
- Use internal `page-card-section` sections.
- Use one `content-inner-card` for detail/list data areas.
- Use Lucide icons for actions and menu controls.
- Keep soft Flowbite-like borders, radius, shadows, and compact density.
- Do not introduce Bootstrap/AdminLTE/Gentelella/Kartik/jQuery widget markup.
- Do not show ICNO/UMSPER in card headers unless explicitly required by the target page standard.

Shared-workspace note:

- Other agents are working in this workspace on other modules, including Cuti.
- Keep STARS edits inside `src/Web/Kehadiran/*`, `src/Shared/AttendanceService.php`, STARS-related route/layout/CSS changes, and `AI hronline/04_stars_migration_plan.md` unless a task explicitly needs a shared integration.
- Do not revert unrelated dirty worktree changes.

Last STARS verification:

- `php -l src/Web/Kehadiran/index.php` passed.
- `php -l src/Shared/AttendanceService.php` passed.
- Authenticated `/kehadiran/index` returned HTTP 200 for `staff-940402125181`.
- Smoke confirmed the STARS top menu, `content-inner-card`, `Staff`, `Officer`, UMS Go notice, and `Yii2` badges render.
- Smoke confirmed no old Yii2 `x_panel`, Bootstrap 3, or jQuery call markers.

### STARS Staff Top Menu Analysis

Analyzed the `Staff` dropdown on `http://localhost/hronline/kehadiran/index` and mapped every item back to the old Yii2 staff app.

Updated:

- `AI hronline/04_stars_migration_plan.md`

Captured:

- Staff menu source mapping from Yii3 `AttendanceService::menuSections()` to Yii2 `views/kehadiran/_menu.php`.
- Yii2 controller/view/model sources for:
  - `kehadiran/laporan-kehadiran`
  - `kehadiran/tindakan_ketidakpatuhan`
  - `kehadiran/staf-color-list`
  - `kehadiran/kad_warna`
  - `kehadiran/wfh-list`
  - `kehadiran/absent-list`
  - external Outstation link
  - static Guideline PDF link
- Recommended migration order:
  - static assets
  - card colour info
  - WFH/WFO list
  - card colour list
  - pending reason read list
  - THTC read list
  - full attendance report last
- Write flows are explicitly deferred:
  - `kehadiran/remark`
  - `kehadiran/remark-thtc`
  - notification/dashboard pending-task side effects
  - file uploads
  - approval/status transitions

Asset gaps found:

- Old `C:\Apache24\htdocs\staff\web\images\kad_warna.png` exists, but Yii3 `public/images/kad_warna.png` is missing.
- Old `C:\Apache24\htdocs\staff\web\files\garis_panduan_kehadiran.pdf` exists, but Yii3 `public/files/garis_panduan_kehadiran.pdf` is missing.

### STARS Staff Read-Only Routes Executed

Executed `STARS-06 - Staff Read-Only Routes`.

Implemented:

- Local Yii3 Staff routes for attendance report, pending reason list, card color report, card colour info, WFH/WFO list, and THTC absent list.
- Shared Staff action/template coverage under `src/Web/Kehadiran`.
- `AttendanceService` read queries for the migrated Staff pages.
- Local Staff menu links for completed routes.
- Static assets copied to `public/images/kad_warna.png` and `public/files/garis_panduan_kehadiran.pdf`.
- `.htaccess` static rewrite updated for `/files/...`.

Deferred intentionally:

- `kehadiran/remark`
- `kehadiran/remark-thtc`
- upload, approval, notification, and status-transition writes
- exact Yii2 PDF/print output generation

Verification:

- `php -l` passed for changed PHP files.
- `php yii list` passed.
- Authenticated smoke via `http://localhost/hronline/...` returned HTTP 200 for all migrated Staff routes using `staff-940402125181`.
- `/hronline/images/kad_warna.png` and `/hronline/files/garis_panduan_kehadiran.pdf` returned HTTP 200.

### Attendance UI Standard

Set the refined Staff-page toolbar and content treatment as the Attendance/STARS UI standard.

Standard classes:

- `attendance-standard-toolbar`
- `attendance-standard-title`
- `attendance-standard-icon`
- `attendance-standard-filter`
- `attendance-standard-fields`
- `attendance-standard-actions`
- `attendance-standard-count`
- icon variants: `attendance-icon-info`, `attendance-icon-success`, `attendance-icon-warning`, `attendance-icon-danger`

Applied the standard to all currently migrated Staff menu pages and documented it in `AI hronline/04_stars_migration_plan.md`.

### STARS Officer Top Menu Analysis

Analyzed the `Officer` dropdown on `http://localhost/hronline/kehadiran/index` and mapped every item back to the old Yii2 staff app.

Updated:

- `AI hronline/04_stars_migration_plan.md`

Captured:

- Officer menu source mapping from Yii3 `AttendanceService::menuSections()` to Yii2 `views/kehadiran/_menu.php`.
- Yii2 controller/view/model sources for:
  - `kehadiran/senarai_tindakan`
  - `kehadiran/staff-color-card`
  - `kehadiran/pantau-kehadiran`
  - `kehadiran/monthly-summary`
  - `kehadiran/wfh-lulus`
  - `kehadiran/pantau-permohonan-wfh-pegawai`
  - `cuti/pegawai/staffonleave`
  - `kehadiran/absent-list-kp`
- Recommended migration order:
  - Officer scope helpers first
  - card colour report
  - monthly summary
  - attendance monitor
  - WFH application monitor
  - WFH approval queue read-only
  - reason approval queue read-only
  - THTC department monitor
  - keep `Staff Seliaan` as Yii2 handoff until Registrar API proxy decision
- Write-flow decision:
  - Individual `kehadiran/pengesahan`, `kehadiran/wfh-lulus-tindakan`, `kehadiran/delete-wfh`, and THTC verify/approval actions were moved into Yii3 in the first approval/delete write slice.
  - Bulk approval POST flows and full notification/audit/dashboard pending-task verification remain deferred.

UI decision:

- Officer Yii3 pages must follow the `attendance-standard-*` toolbar/content standard.

### STARS Officer Read-First Migration

Executed the Officer top-menu migration as read-first Yii3 pages using the Attendance UI standard.

Implemented:

- Local routes for:
  - `/kehadiran/senarai_tindakan`
  - `/kehadiran/staff-color-card`
  - `/kehadiran/pantau-kehadiran`
  - `/kehadiran/monthly-summary`
  - `/kehadiran/wfh-lulus`
  - `/kehadiran/pantau-permohonan-wfh-pegawai`
  - `/kehadiran/absent-list-kp`
- New `src/Web/Kehadiran/OfficerAction.php`.
- New `src/Web/Kehadiran/officer-page.php`.
- Officer service methods in `src/Shared/AttendanceService.php` for supervised staff, card colour report, monthly summary, daily attendance monitor, WFH application monitor, WFH approval queue, reason approval queue, and KP/THTC department list.
- Officer menu links now point locally for migrated read pages.
- `Staff Seliaan` remains a Yii2 legacy handoff because it depends on the Registrar team API.
- First individual approval/delete actions now route locally from the Yii3 tables; heavier bulk/write/export actions remain Yii2 or deferred.

Verification:

- `php -l` passed for `AttendanceService.php`, `OfficerAction.php`, and `officer-page.php`.
- Real-data service smoke with Officer ICNO `790608125733` returned:
  - supervised staff: 61
  - card report rows: 61
  - monthly summary rows: 61
  - daily attendance rows: 61
  - WFH monitor/approval queues: query-safe with zero current rows
  - reason approval queue: query-safe with zero current rows
- KP/THTC guard smoke with PP ICNO `780123125699` returned authorized `yes` and 802 department rows.
- Apache route smoke without session redirects all new Officer routes to `/hronline/login` cleanly.
- Authenticated Apache smoke using Officer ICNO `790608125733` returned HTTP 200 for all new Officer routes and confirmed `attendance-standard-toolbar` renders on each page.

### STARS Attendance Supervisor Top Menu Analysis

Analyzed the `Attendance Supervisor` dropdown on `http://localhost/hronline/kehadiran/index` and mapped every item back to the old Yii2 staff app.

Updated:

- `AI hronline/04_stars_migration_plan.md`

Captured:

- Supervisor menu source mapping from Yii3 `AttendanceService::menuSections()` to Yii2 `views/kehadiran/_menu.php`.
- Yii2 controller/view/model sources for:
  - `kehadiran/senarai_kakitangan`
  - `kehadiran/staff-wbb`
  - `kehadiran/sup-mth-rpt`
  - `kehadiran/sup-year-rpt`
  - `kehadiran/senarai-warna-kad`
  - `kehadiran/sup-year-performance-rpt`
  - `kehadiran/pantau-staf`
  - `kehadiran/shield-status-list`
  - `kehadiran/staff-shift-list`
  - `kehadiran/shift-setup`
  - `kehadiran-admin/manage-shift-supervisor`
  - `kehadiran/wfh-by-mth`
  - `kehadiran/wfh-jadual`
  - `kehadiran/pantau-permohonan-wfh`
  - `kehadiran/absent-list-sv`
- Recommended migration order:
  - supervisor access-scope helpers first
  - staff lists
  - staff working hours
  - monthly/yearly reports
  - card colour reports/performance
  - pending staff monitor
  - WFH monitor/schedule pages
  - shift list/setup read-only pages
  - THTC supervisor list
  - defer shield status until table/relation mapping
  - defer admin manage-shift-supervisor until admin/shift-management slice
- Write flows still deferred after the first approval/delete slice:
  - `set-peg`
  - `wbb-list`
  - `kemaskini-peraku`
  - WFH day edit and bulk actions
  - shift add/remove/create/update
  - print/PDF/export routes

Recommended first execution slice:

- Supervisor scope foundation.
- `/kehadiran/senarai_kakitangan`.
- `/kehadiran/staff-wbb`.
- `/kehadiran/sup-mth-rpt`.

### STARS Top Menu Migration Snapshot

Recorded current top-menu migration status on 2026-11-19.

Migrated to Yii3:

- Staff:
  - Attendance report
  - Pending for reason
  - Card Color report
  - Card Colour info
  - Senarai WFH / WFO
  - Tidak Hadir Tanpa Cuti (THTC)
  - Guideline is served locally/static
  - Outstation remains external to pilot IICS, not Yii2
- Officer:
  - Approval (Reason)
  - Card Colour Report
  - Attendance report
  - Monthly report
  - Senarai Tindakan WFH
  - Senarai Permohonan WFH
  - Tidak Hadir Tanpa Cuti (THTC)
- Attendance Supervisor:
  - Staff lists
  - Staff Working hours
  - Monthly Reports
  - Yearly Reports
  - Card Colour Report
  - Card Colour Performance
  - Pantau Staf
  - Shields Status
  - Staff Shift List
  - Shift Setup
  - Manage Supervisor/Staff
  - Senarai Bulanan WFH
  - Jadual WFH
  - Pantau Permohonan WFH
  - Tidak Hadir Tanpa Cuti (THTC)

Not yet migrated / still legacy Yii2:

- Officer:
  - Staff Seliaan
- Head of Department:
  - Staff attendance
  - Jadual WFH
  - Senarai Tindakan WFH
  - Senarai Permohonan WFH
  - Tidak Hadir Tanpa Cuti (THTC)
- Administrator:
  - Staff lists
  - Statistic (JFPIU)
  - Warning Letters
  - Raw Data
  - Senarai Keluar Pejabat
  - Admin Post (Akad)
  - Pantau Permohonan JAFPIB
  - Jadual JAFPIB
  - Statistik WFH
  - Vaccination Watchlist
  - Tidak Hadir Tanpa Cuti (THTC)
- Reports:
  - By Month (Range)
  - Monthly Reports
  - Yearly Reports

Current migration boundary:

- Migrated STARS top-menu pages are local Yii3 pages and follow the attendance UI standard.
- Many migrated pages started read-first/read-only.
- First approval/delete write paths have been moved to Yii3. Remaining heavier write/export paths are staff THTC upload, staff reason remark submission, WBB setup, WFH day edits, shift setup saves, raw attendance CRUD, bulk approvals, PDF/print actions, and full audit/dashboard parity.

### Yii3 DB Configuration Migration

Recorded on 2026-11-19.

- Added Yii3-owned DB config at `config/common/db-connections.php`.
- `config/common/db.php` now returns the primary `db` connection config from that registry.
- `config/common/di/application.php` now binds:
  - `DbConnectionRegistry` for named Yii2-compatible DB connections.
  - `ConnectionInterface` to the primary `db` connection.
- Migrated Yii2 DB connection names into Yii3 config lookup:
  - `db`, `db2`, `db3`, `db4`, `db5`, `db6`, `db7`, `db8`, `db9`, `db10`, `db11`, `db12`, `db13`, `db14`, `db15`, `db16`, `db17`, `db18`, `db19`.
- Env var pattern:
  - `HRONLINE_DB_DSN`, `HRONLINE_DB_USERNAME`, `HRONLINE_DB_PASSWORD`
  - `HRONLINE_DB2_DSN`, `HRONLINE_DB2_USERNAME`, `HRONLINE_DB2_PASSWORD`
  - and so on for `DB3` through `DB19`.
- Local fallback remains enabled temporarily: if env vars are not set, Yii3 reads the matching old Yii2 `../staff/config/db*.php`.
- MySQL configs can be instantiated now. SQL Server configs are present but not instantiated yet because the Yii3 app currently only has `yiisoft/db-mysql`; SQL Server support needs the matching Yii DB package/driver before using `db3`, `db4`, `db5`, `db6`, `db9`, `db10`, `db12`, or `db14`.

### Lantikan Staf Baru Write Foundation

Recorded on 2026-11-20.

- Added `AI hronline/10_lantikan_pending_migration_execution_plan.md` as the task-by-task Lantikan pending migration plan.
- Added `LantikanStafBaruTransactionService` for the feature-gated Staf Baru Yii3 write path.
- Added reusable Staf Baru insert-plan preparation in `LantikanStafBaruService`.
- Added MySQL advisory-lock handling for live UMSPER reservation using `GET_LOCK('hronline.umsper.reserve', 10)`.
- Added `HRONLINE_LANTIKAN_STAF_BARU_WRITE=1` gating for live Staf Baru submit. The flag is off by default.
- Added `/lantikan/lantik-staf-baru/result` reconciliation page for core table checks and pending post-commit status.
- Kept AD create, IdP Mata, notifications, allocation profile write replacement, `attendance.tbl_wp`, `keselamatan.tbl_staff_keselamatan`, and `hrm.myhealth_tblmaxtuntutan` out of the DB transaction.
- Verification run:
  - `php -l` passed for changed Lantikan PHP files and routes.
  - `php yii list` booted successfully.
  - Unauthenticated HTTP smoke for `/hronline/lantikan/lantik-staf-baru/result?icno=TEST` returned redirect to `/hronline/login`.
- Not yet completed:
  - No live dummy appointment was inserted in this slice.
  - `STAFBARU-06` and `STAFBARU-07` remain unchecked until approved AD-enabled dummy/UAT write and rollback/concurrency checks are run.
  - Open blockers are recorded in `AI hronline/10_lantikan_pending_migration_execution_plan.md#blocker-register`.

### STARS Remaining Top Menu Migration Plan

Recorded on 2026-11-20.

Updated `AI hronline/04_stars_migration_plan.md` with a concrete migration plan for STARS top-menu items that still show Yii2 handoffs.

Current remaining intentional top-menu handoffs after execution:

- Officer: `Staff Seliaan` remains Yii2 because it belongs to `cuti/pegawai/staffonleave` and depends on the Cuti/Registrar API boundary.
- Administrator: `Senarai Keluar Pejabat` remains Yii2 until the Yii3 SQL Server driver is available for `db3`.
- Administrator: `Vaccination Watchlist` remains Yii2 because it is a Vaksinasi module boundary.
- Reports: monthly/yearly report links are local; `By Month (Range)` remains tied to old PDF generation until export policy is approved.

New plan slices:

- `STARS-07A - Officer Staff Seliaan Boundary`
- `STARS-07B - Head Of Department Read Routes`
- `STARS-07C - Administrator Read Routes`
- `STARS-07D - Reports Menu And Export Decision`

Important boundary:

- The first approval/delete write routes are now in Yii3. Remaining heavy write/export work still excludes WFH day edits, shift setup saves, raw attendance CRUD, bulk approvals, PDF generation, staff uploads, and full dashboard/audit parity.

### STARS Remaining Top Menu Task Board

Recorded on 2026-11-20.

Expanded `AI hronline/04_stars_migration_plan.md` into runnable checkbox tasks.

Recommended task order:

- [x] `STARS-07A-01` Analyze Officer `Staff Seliaan` boundary.
- [x] `STARS-07A-02` Implement a local read page or confirm intentional Yii2 handoff for `Staff Seliaan`.
- [x] `STARS-07B-01` Build HOD scope helpers.
- [x] `STARS-07B-02` Add HOD page shell and routes.
- [x] `STARS-07B-03` Migrate HOD `Staff attendance`.
- [x] `STARS-07B-04` Migrate HOD `Jadual WFH`.
- [x] `STARS-07B-05` Validate HOD WFH queue links.
- [x] `STARS-07B-06` Migrate HOD `THTC` list.
- [x] `STARS-07B-07` Cut over and verify HOD menu links.
- [x] `STARS-07C-01` Add admin guard and page shell.
- [x] `STARS-07C-02` Migrate admin `Staff lists`.
- [x] `STARS-07C-03` Migrate admin `Warning Letters`.
- [x] `STARS-07C-04` Migrate admin `Statistik WFH`.
- [x] `STARS-07C-05` Migrate admin `THTC` BPSM overview.
- [x] `STARS-07C-06` Migrate admin `Statistic (JFPIU)`.
- [x] `STARS-07C-07` Migrate admin WFH monitor and schedule.
- [x] `STARS-07C-08` Migrate admin `Admin Post (Akad)`.
- [x] `STARS-07C-09` Decide SQL Server `Senarai Keluar Pejabat`.
- [x] `STARS-07C-10` Decide `Vaccination Watchlist` boundary.
- [x] `STARS-07C-11` Cut over and verify admin menu links.
- [x] `STARS-07D-01` Decide reports export/PDF policy.
- [x] `STARS-07D-02` Cut over monthly/yearly report links.
- [x] `STARS-07D-03` Handle `By Month (Range)`.
- [x] `STARS-07D-04` Update DB-backed main menu route map and clean legacy badges.

Execution completed:

- Added Yii3 HOD read routes for attendance monitor, WFH schedule, and THTC list.
- Added Yii3 admin read routes for staff directory, monthly statistic, warning letters, raw attendance, WFH/JAFPIB monitor and schedule, WFH statistics, admin post list, and BPSM THTC overview.
- Cut migrated HOD/Admin/Reports links to local STARS menu items and updated DB-backed main-menu route normalization.
- Kept Officer `Staff Seliaan`, Admin `Senarai Keluar Pejabat`, Admin `Vaccination Watchlist`, and Reports `By Month (Range)` as documented Yii2 handoffs.
- Verification passed: PHP syntax checks for changed STARS PHP files, `php yii list`, and unauthenticated HTTP smokes for all new HOD/Admin routes landing on login.

### STARS Approval/Delete Write Slice

Recorded on 2026-11-20.

- Added local Yii3 write action/template for:
  - `/kehadiran/pengesahan`
  - `/kehadiran/wfh-lulus-tindakan`
  - `/kehadiran/delete-wfh`
  - `/kehadiran/verify-thtc-sv`
  - `/kehadiran/approve-thtc`
- Added AttendanceService command methods for individual reason approval, WFH approval/reject, WFH delete, supervisor THTC verification, and HOD THTC approval.
- Wrapped first-slice update/delete commands in the project transaction pattern.
- Replaced migrated WFH delete links with POST/CSRF forms.
- Updated action buttons for migrated approval/review flows to local Yii3 pages.
- Verification passed:
  - `php -l` for changed STARS write/action/template files.
  - `php yii list`.
  - Unauthenticated HTTP smokes for the five new write routes all resolve to login.
- Still requires authenticated dummy-row verification before full write-flow completion.
### Biodata Sections Restructure Plan

Recorded a task-by-task plan for moving "Maklumat Dan Rekod Kakitangan" modules toward `/biodata/{section}/...` URLs, `src/Web/Biodata/Sections/{Section}` web ownership, and `src/Shared/Biodata/Sections` service ownership.

- New plan file: `AI hronline/11_biodata_sections_restructure_plan.md`
- Recommended first execution task: add the Biodata section URL helper and new Keluarga route aliases without moving files yet.

Execution pass on 2026-11-23:

- Added `BiodataSectionUrlService` and switched Biodata user/admin section tiles to new `/biodata/{section}/...` URLs.
- Added route aliases for all Biodata staff-record sections while keeping old top-level routes registered.
- Added Keluarga and Pendidikan Web wrappers under `src/Web/Biodata/Sections/*` and Shared adapters under `src/Shared/Biodata/Sections/*`.
- Updated shared overview/simple-section services, menu route normalization, Alamat, and Keahlian flows to preserve admin `?icno=...` context under the new URL convention.
- Verification passed: PHP syntax checks for changed PHP files and unauthenticated HTTP smoke tests for representative new routes landing on login.
- Remaining work: full physical extraction out of the legacy shared services and authenticated CRUD browser verification.

Follow-up execution:

- Added section-owned Web CRUD action classes for the remaining Biodata section items, including Phase 4 modules and the simple staff-record sections.
- Routed Phase 4 modules through their own Biodata folders:
  - Paspot & Permit Kerja
  - Lesen
  - Akaun
  - Bidang Kepakaran
  - Vaksinasi
  - Kelayakan Profesional
- Decided Lesen should move under the Biodata section convention instead of remaining module-specific.
- Added Phase 4 Shared section adapters under `src/Shared/Biodata/Sections`.
- Preserved admin `?icno=...` behavior for Lesen and Kelayakan Profesional forms/deletes under the new routes.
- Verification passed: PHP syntax checks for new/changed section classes, `php yii list`, and unauthenticated HTTP smokes for representative Phase 4 routes.
- Architectural note: route/action ownership is now per section, but deep DB/list/save/delete internals still need to be extracted from the old shared services before `StaffRecordOverviewService` and `StaffRecordOverviewCrudService` can be retired.

Follow-up execution:

- Fully extracted Akaun as the first independent CRUD pattern under `src/Web/Biodata/Sections/Akaun` and `src/Shared/Biodata/Sections`.
- Akaun list/detail now uses `AkaunService` directly instead of `StaffRecordOverviewService`.
- Akaun create/update now uses `AkaunCrudService` directly instead of `StaffRecordOverviewCrudService`.
- Akaun now owns local UI templates for list, detail, and form under `src/Web/Biodata/Sections/Akaun`.
- Old `/akaun/...` routes now resolve to the same Akaun-owned section actions as `/biodata/akaun/...`.
- Removed the Akaun branch/config from the generic shared overview/CRUD services.
- Verification passed: PHP syntax checks, `php yii list`, and unauthenticated HTTP smokes for `/biodata/akaun/senarai-akaun`, `/biodata/akaun/admin-senarai-akaun?icno=681114015611`, and `/akaun/senarai-akaun`.

Follow-up execution:

- Standardized Biodata section UI ownership from Alamat through Vaksinasi.
- Added local `view.php`, `detail.php`, and/or `form.php` templates under each section folder where that section has those pages.
- Added `SectionTemplatePath` so `/biodata/{section}/...` actions render section-local templates while old top-level compatibility URLs can keep their old templates.
- Corrected the local Kecacatan templates/actions to use `/biodata/kecacatan/...` URLs and preserve admin `?icno=...` context.
- Verification passed: PHP syntax checks across patched actions and 112 section template/action files.

Phase 6 cleanup start:

- Audited remaining generic `StaffRecordOverview*` and `StaffRecordSection*` usage.
- Confirmed the generic services cannot be deleted yet because several section adapters still delegate list/detail/save/delete internals to them.
- Cleaned active `/biodata/...` routes for Alamat, Keahlian, and Kecacatan so they now enter `src/Web/Biodata/Sections/{Section}` action classes instead of old top-level modules.
- Left old top-level routes in place as compatibility aliases.

Follow-up cleanup:

- Moved standalone Web action logic into Biodata section folders for Alamat, Keahlian, Kecacatan, Lesen, and Kelayakan Profesional.
- Repointed old top-level compatibility routes to `src/Web/Biodata/Sections/{Section}` action classes.
- Deleted old standalone Web folders:
  - `src/Web/Alamat`
  - `src/Web/BadanProfesional`
  - `src/Web/Kecacatan`
  - `src/Web/Lesen`
  - `src/Web/KelayakanProfesional`
- Confirmed no route references remain to old standalone folders or generic staff-record Web actions.
- Verification passed: PHP syntax checks, `php yii list`, and unauthenticated HTTP smokes for old and new section URLs.

### Akaun Bank Change Request Standard

Recorded on 2026-11-23.

Business rule confirmed from old Yii2 Staff:

- Staff cannot directly add or update salary bank account rows in `hronline.tbl_akaun`.
- Staff must submit a bank-account change request for admin verification and approval.
- The staff button `Penukaran Akaun` must open the request list, not the live account CRUD form.
- Live salary-bank account CRUD is admin-owned.

Yii3 implementation:

- Staff list route remains `/biodata/akaun/senarai-akaun`.
- Staff `Penukaran Akaun` now opens `/biodata/akaun/permohonan-penukaran-akaun-gaji`.
- Staff new request route is `/biodata/akaun/mohon-tukar-akaun`.
- Staff request detail route is `/biodata/akaun/lihat-acc`.
- Request rows are saved to `hronline.tbl_akaun_permohonan_tukar`.
- Request upload uses `MediaHostFileService` with `Maklumat Rekod Kakitangan/akaun`.
- Bank SWIFT code is derived from `hronline.tbl_bank_main.swift_code`.
- Staff live-account rows no longer expose `Kemaskini`.
- Admin live-account routes are explicit:
  - `/biodata/akaun/admin-tambah`
  - `/biodata/akaun/admin-kemaskini`
- Admin live-account routes require an admin target `icno` query before rendering the form.

Files added or changed:

- Added `src/Shared/Biodata/Sections/AkaunRequestService.php`.
- Added `src/Web/Biodata/Sections/Akaun/RequestListAction.php`.
- Added `src/Web/Biodata/Sections/Akaun/RequestFormAction.php`.
- Added `src/Web/Biodata/Sections/Akaun/RequestDetailAction.php`.
- Added `src/Web/Biodata/Sections/Akaun/request-list.php`.
- Updated `src/Shared/Biodata/Sections/AkaunService.php` so staff and admin account actions differ.
- Updated `src/Shared/Biodata/Sections/AkaunCrudService.php` so live account CRUD uses admin route names.
- Updated `src/Web/Biodata/Sections/Akaun/FormAction.php` with an admin-route target guard.
- Updated `src/Web/Biodata/Sections/Akaun/form.php` to support page title, section title, and submit label overrides for the request form.
- Updated `config/common/routes.php` with request-list/new-request/request-detail/admin CRUD route separation.

Standard going forward:

- Biodata sections that have an approval workflow must separate staff request services from admin live-record CRUD services.
- Staff request pages use `RequestListAction`, `RequestFormAction`, `RequestDetailAction`, and a `{Section}RequestService` where the request table differs from the live table.
- Admin live-record CRUD uses explicit admin route names and must preserve target staff context using `?icno=...`.
- Staff-facing list/detail pages must not expose update/delete buttons for records that require admin verification.
- Compatibility top-level routes may exist, but canonical routes remain under `/biodata/{section}/...`.

Verification:

- `php -l` passed for changed Akaun service/action/template files.
- `php -l config/common/routes.php` passed.
- `php yii list` boots.
- Unauthenticated HTTP smoke checks for Akaun routes redirect to `/hronline/login`, confirming route registration.
- Browser opened `/hronline/biodata/akaun/senarai-akaun` for authenticated manual verification.

Still pending:

- Migrate the admin verification and approval screens from old Yii2 `PengesahanDataStafController` / `views/pengesahan-data-staf/akaun_gaji`.
- Authenticated dummy request submission test with safe data and upload.

