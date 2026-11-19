# Menu Migration Plan

Date: 2026-11-19

Target old route:

- Yii2: `site/menu`
- Yii3 target: `GET /menu`
- Sidebar target after migration: `Menu` should point to `/menu` instead of `http://127.0.0.1:8083/site/menu`.

## Yii2 Source Reviewed

- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\views\site\menu.php`
- `C:\Apache24\htdocs\staff\views\layouts\side_menu.php`
- `C:\Apache24\htdocs\staff\views\layouts\adminlte4.php`
- `C:\Apache24\htdocs\staff\models\TblMenuCategory.php`
- `C:\Apache24\htdocs\staff\models\system_core\TblMenuSide.php`
- `C:\Apache24\htdocs\staff\models\system_core\TblMenuSideChild.php`
- `C:\Apache24\htdocs\staff\models\system_core\RefIcon.php`

## Current Yii2 Behavior

The old sidebar `Menu` item opens `site/menu`.

`SiteController::actionMenu()` loads:

- active categories from `system_core.tbl_menu_category`
  - selected columns: `id`, `name`, `icon`
  - filter: `status = 1`
  - order: `order ASC`
- active top-level menu items from `system_core.tbl_menu_side`
  - selected columns: `id`, `category_id`, `label`, `url`, `visible`
  - filter: `parent_id IS NULL`, `status = 1`
  - order: `label ASC`
- active submenu rows from `system_core.tbl_menu_side`
  - selected columns: `id`, `parent_id`, `label`, `url`, `visible`
  - filter: `status = 1`
  - order: `label ASC`
  - grouped by `parent_id`

The old view renders:

- one card per menu category
- Font Awesome category icons from `tbl_menu_category.icon`
- top-level rows inside the matching category
- collapsed child lists when a top-level item has `url = NULL`
- external HTTPS links in a new tab
- internal Yii2 route links through `Url::to([$url])`
- row visibility by evaluating the DB `visible` PHP expression with `eval()`

## Current Data Size

DB probe against the old staff database:

- active categories: `7`
- active top-level menu rows: `71`
- active child rows: `41`

Active categories:

| ID | Name | Icon | Top-level rows |
| --- | --- | --- | --- |
| 1 | Pengurusan Sumber Manusia | users | 25 |
| 2 | Kepimpinan & Pengurusan Bakat | book | 8 |
| 3 | Automasi Pejabat | car | 5 |
| 7 | Pusat Jaminan Kualiti | university | 6 |
| 4 | Utiliti | wrench | 16 |
| 6 | Pusat Islam Universiti Malaysia Sabah | university | 3 |
| 5 | Keselamatan | shield | 3 |

## Important Risk

Yii2 uses `eval()` on `system_core.tbl_menu_side.visible`.

Examples found:

- super/admin access via `system_core.tbl_user_access`
- staff status checks against `hronline.tblprcobiodata`
- access level and second-level checks
- Ketua Program checks
- security staff checks against `keselamatan.tbl_staff_keselamatan`
- security access checks against `keselamatan.tbl_akses`
- department chief or PP checks
- hard-coded ICNO allowlists
- several module-specific admin tables

Yii3 must not copy the raw `eval()` behavior. The migration should replace this with explicit, allow-listed visibility rules.

## Yii3 Target Design

Add:

- `src/Shared/MenuService.php`
- `src/Shared/MenuVisibilityService.php`
- `src/Web/Menu/Action.php`
- `src/Web/Menu/template.php`
- route `GET /menu`

Flexibility principle:

- Keep categories, labels, URLs, ordering, parent/child structure, icons, status flags, and future menu additions controlled by the existing DB tables.
- Yii3 must not hardcode the 7 current categories or 112 current rows into source code.
- The only thing Yii3 should harden is how `visible` rules are interpreted, because the old Yii2 view executes DB strings with `eval()`.
- New menu rows added in `system_core.tbl_menu_side` should appear automatically when:
  - `status = 1`
  - the category is active
  - the URL can be normalized
  - the visibility rule is empty or supported by the rule engine
- Unsupported visibility expressions should be visible in a migration/debug report for admins/developers, so the rule engine can be extended without changing the menu rendering structure.

Update:

- `src/Web/Shared/Layout/Main/layout.php`
  - change sidebar `Menu` link from old Yii2 `/site/menu` to Yii3 `/menu`
  - add active-state support for `/menu`
- `src/Shared/DashboardService.php`
  - change `Main Menu` shortcut from old Yii2 `/site/menu` to Yii3 `/menu`
- `assets/main/site.css`
  - add compact menu-card grid styling if existing card/grid styles are not enough

## Execution Tasks

Use these tasks in order. Each task should be small enough to implement, verify, and document before moving to the next one.

### Task MNU-01: Add Read-Only Menu Data Loader

Status: `[x]`

Goal:

- Add a DB-backed service that reads the same menu tables as Yii2 without rendering anything yet.

Files:

- `src/Shared/MenuService.php`

Implementation:

- Add `MenuService`.
- Read active categories from `system_core.tbl_menu_category`.
- Read active top-level menu rows from `system_core.tbl_menu_side`.
- Read active child menu rows from `system_core.tbl_menu_side`.
- Group children by `parent_id`.
- Preserve DB-driven category/order/label/icon/url fields.
- Do not add visibility filtering yet except empty `visible` handling.
- Do not hardcode current category names or menu rows.

Acceptance:

- Service returns all active categories.
- Service returns top-level rows under their matching category.
- Service returns child rows under their matching parent.
- Service output has stable display-ready arrays for the future template.

Verification:

- `php -l src/Shared/MenuService.php`
- `php yii list`
- Temporary CLI/service probe can print category and row counts.

### Task MNU-02: Add URL Normalization

Status: `[x]`

Goal:

- Keep menu URLs flexible while routing already-migrated modules locally in Yii3.

Files:

- `src/Shared/MenuService.php`

Implementation:

- Add a centralized local route map inside the service or a small private method.
- Map known migrated Yii2 routes to Yii3 routes.
- Keep HTTPS URLs external.
- Convert unknown relative Yii2 routes to `http://127.0.0.1:8083/<route>`.
- Include `external` and `legacy` flags in item output.

Acceptance:

- `site/menu` maps to `/menu`.
- Rekod Peribadi routes map to local Yii3 routes.
- Non-migrated routes remain usable through old Yii2 port `8083`.
- The template will not need to know route-mapping rules.

Verification:

- `php -l src/Shared/MenuService.php`
- Service probe confirms at least one local URL and one legacy URL.

### Task MNU-03: Add Conservative Visibility Service

Status: `[x]`

Goal:

- Replace Yii2 `eval()` with safe, explicit visibility handling.

Files:

- `src/Shared/MenuVisibilityService.php`
- optional later split:
  - `src/Shared/MenuUserContext.php`
  - `src/Shared/MenuVisibilityRule.php`

Implementation:

- Add `MenuVisibilityService`.
- Empty `visible` expression returns visible.
- Unsupported non-empty expressions return hidden.
- Return unsupported expressions in debug/service metadata for migration audit.
- No `eval()`, `assert()`, dynamic PHP execution, or string-to-code execution.

Acceptance:

- Empty-rule rows remain visible.
- Unsupported-rule rows are hidden by default.
- Unsupported rules can be counted/listed for follow-up.

Verification:

- `php -l src/Shared/MenuVisibilityService.php`
- `php yii list`
- `rg -n "eval\\(|assert\\(" src` confirms no new dynamic execution.

### Task MNU-04: Add First Visibility Rule Families

Status: `[x]`

Goal:

- Restore useful menu flexibility for common Yii2 `visible` rules without exposing admin-only rows.

Files:

- `src/Shared/MenuVisibilityService.php`
- `src/Shared/AuthorizationService.php` if a reusable helper is missing

Implementation:

- Support staff-exists checks against `hronline.tblprcobiodata`.
- Support `system_core.tbl_user_access` checks for `icno` and `access`.
- Support `accessLevel` and `accessSecondLevel` checks from `tblprcobiodata`.
- Support Ketua Program through `AuthorizationService::isKetuaProgram()`.
- Support simple hard-coded ICNO allowlists.
- Keep all unknown module-specific rules hidden.

Acceptance:

- Normal staff menu rows gated by staff existence can appear.
- Admin-only rows stay hidden unless the explicit rule says the current user qualifies.
- Unsupported expression count decreases but does not need to be zero yet.

Verification:

- `php -l` on changed files.
- `php yii list`.
- Service probe as test staff shows expected normal staff sections but not broad admin-only sections.

### Task MNU-05: Add `/menu` Route And Action

Status: `[x]`

Goal:

- Add the Yii3 page endpoint while keeping the current sidebar link unchanged until the page is verified.

Files:

- `src/Web/Menu/Action.php`
- `src/Web/Menu/template.php`
- `config/common/routes.php`

Implementation:

- Add `GET /menu`.
- Require legacy Yii2-compatible session through `LegacySessionReader`.
- Redirect unauthenticated users to `/login`.
- Render basic page using `MenuService` output.
- Return 200 for authenticated users.

Acceptance:

- `/menu` exists.
- Unauthenticated request redirects to `/login`.
- Authenticated request renders a real Yii3 page.
- Page does not depend on old Yii2 view files.

Verification:

- `php -l src/Web/Menu/Action.php`
- `php -l src/Web/Menu/template.php`
- `php -l config/common/routes.php`
- `php yii list`
- HTTP smoke:
  - unauthenticated `/menu` -> 302 `/login`
  - authenticated `/menu` -> 200

### Task MNU-06: Build Menu UI

Status: `[x]`

Goal:

- Render the DB-backed menu in the current Yii3 visual style.

Files:

- `src/Web/Menu/template.php`
- `assets/main/site.css`

Implementation:

- Render category cards from service output.
- Render direct links and collapsible parent items.
- Use native `details/summary` for first-pass collapsible children.
- Show legacy/external links clearly but compactly.
- Avoid card-inside-card nesting except category cards as repeated items.
- Keep mobile layout one column and desktop layout two columns.

Acceptance:

- All visible categories render.
- Parent rows with children are expandable.
- Local Yii3 links stay on port `8084`.
- Legacy links point to port `8083`.
- HTTPS links open externally.

Verification:

- Browser smoke for `/menu`.
- Screenshot review desktop and mobile.
- Check no text overlap and no broken layout.

### Task MNU-07: Switch Sidebar And Dashboard Shortcuts

Status: `[x]`

Goal:

- Move user navigation from old Yii2 `site/menu` to Yii3 `/menu`.

Files:

- `src/Web/Shared/Layout/Main/layout.php`
- `src/Shared/DashboardService.php`

Implementation:

- Change sidebar `Menu` link to `/menu`.
- Add `/menu` active-state highlighting.
- Change dashboard `Main Menu` shortcut to `/menu`.
- Leave non-migrated sidebar entries such as STARS/SKB/Support System as old Yii2 handoffs.

Acceptance:

- Sidebar `Menu` opens Yii3 `/menu`.
- Dashboard `Main Menu` shortcut opens Yii3 `/menu`.
- Active highlight works on `/menu`.

Verification:

- `php -l` on changed PHP files.
- Browser click sidebar `Menu`.
- Browser click dashboard `Main Menu`.

### Task MNU-08: Visibility Parity Audit

Status: `[x]`

Goal:

- Compare what Yii2 shows and what Yii3 shows for the current test user.

Files:

- `AI hronline/03_menu_migration_plan.md`
- `AI hronline/progress.md`
- optional temporary probe scripts should not be committed unless useful long-term

Implementation:

- Capture visible Yii2 rows for the test user.
- Capture visible Yii3 rows for the same user.
- List hidden unsupported expressions.
- Decide which additional rule families are needed.

Acceptance:

- Differences are documented.
- Unsupported expressions are known and intentionally hidden.
- No high-risk admin rows appear unexpectedly.

Verification:

- Browser comparison or DB/service probe.
- Update this plan with findings.

Result, 2026-11-19:

- Authenticated Yii3 `/menu?debug=1` reports `23` unsupported hidden visibility rules for the current test user after first-pass rule support.
- Examples remaining unsupported:
  - `Pementoran`
  - `Allocation Profile`
  - `Kad Staff`
  - `Pelawat`
  - `Rekod Kompetensi`
  - `Saman (Keselamatan)`
  - `Tindakan Tatatertib`
- These rows remain hidden by design until their module-specific admin/access tables are mapped explicitly.

### Task MNU-09: Add Remaining Visibility Rules As Needed

Status: `[x]`

Goal:

- Add more safe rule handlers only where the parity audit shows they are needed.

Files:

- `src/Shared/MenuVisibilityService.php`
- possibly `AuthorizationService.php` or small dedicated query helpers

Implementation candidates:

- security staff rule
- security access rule
- department chief/PP rule
- selected module admin table checks
- special access tables used by specific menu families

Acceptance:

- Rule support expands without adding raw eval.
- Unsupported count drops for important menu rows.
- Access remains conservative.

Verification:

- `php -l`
- `php yii list`
- authenticated `/menu` smoke
- re-run parity audit

Result, 2026-11-19:

- Added first-pass support for staff profile rules, `TblUserAccess`, access level/second-level checks, Ketua Program, security staff, security access, department leader checks, internal staff checks, and simple ICNO allowlists.
- Fixed the staff-profile parser so old Yii2 expressions ending in `->exists` after trimming are still recognized safely.
- Did not add generic module-table execution. Remaining unsupported module-specific expressions are intentionally hidden.

### Task MNU-10: Final Browser Polish And Documentation

Status: `[x]`

Goal:

- Make `/menu` feel like a finished Yii3 page and record the migration boundary.

Files:

- `assets/main/site.css`
- `src/Web/Menu/template.php`
- `AI hronline/progress.md`
- `AI hronline/03_menu_migration_plan.md`

Implementation:

- Add optional search/filter if category cards feel too dense.
- Add category item counts if useful.
- Add migration/debug notes only where useful for development, not noisy for users.
- Document remaining unsupported visibility expressions and legacy handoffs.

Acceptance:

- Desktop and mobile layouts are clean.
- No content-card links point to old Yii2 unless intentionally marked as legacy.
- User-facing page has no developer explanatory clutter.

Verification:

- Browser screenshot review desktop/mobile.
- `php yii list`
- Final route/link smoke.

Result, 2026-11-19:

- Screenshot tooling was not available in this session.
- Completed live HTTP verification instead:
  - unauthenticated `/menu` redirects to `/login`
  - authenticated `/menu` returns HTTP 200
  - local migrated links are present
  - legacy `8083` links are present
  - collapsible menu sections render with native `details`
  - developer debug output is hidden by default and available with `?debug=1`

## MenuService Shape

`MenuService::menuForIdentity(string $identityId): ?array`

Responsibilities:

- read logged-in ICNO from `staff-<ICNO>`
- load active categories
- load active top-level menu rows
- load active child rows
- group children by parent
- apply visibility filtering through `MenuVisibilityService`
- normalize URLs:
  - HTTPS stays external
  - migrated Yii3 routes map to local Yii3 URLs
  - non-migrated old Yii2 routes map to `http://127.0.0.1:8083/<route>`
- return display-ready category cards

Suggested return shape:

```php
[
    'categories' => [
        [
            'id' => '1',
            'name' => 'Pengurusan Sumber Manusia',
            'icon' => 'users',
            'items' => [
                [
                    'label' => 'Rekod Peribadi Kakitangan',
                    'url' => null,
                    'external' => false,
                    'children' => [
                        ['label' => 'Biodata', 'url' => '/biodata/index', 'external' => false],
                    ],
                ],
            ],
        ],
    ],
]
```

## Visibility Strategy

First implementation should support the known rule families explicitly:

- empty `visible`: show row
- `TblUserAccess` admin checks:
  - `system_core.tbl_user_access.icno = :icno`
  - `access IN (...)`
- current staff exists and `Status != 6`
- `accessLevel` / `accessSecondLevel` checks from `tblprcobiodata`
- Ketua Program:
  - reuse `AuthorizationService::isKetuaProgram()`
- same-program behavior later only if a menu rule actually needs target staff context
- security staff:
  - `keselamatan.tbl_staff_keselamatan.staff_icno = :icno`
  - `isActive = 1`
  - `isExcluded = 0` where applicable
- security access:
  - `keselamatan.tbl_akses.icno = :icno`
  - `isActive = 1`
- department chief or PP:
  - `hronline.department.chief = :icno OR pp = :icno`
- hard-coded ICNO allowlists:
  - parse only simple `in_array(Yii::$app->user->getId(), [...])` or direct ICNO equality patterns

Rows with unsupported `visible` expressions should be hidden by default and logged in the page data for audit/debug, not shown accidentally.

To preserve flexibility, `MenuVisibilityService` should be rule-extensible instead of one giant hardcoded switch. Suggested shape:

```php
interface MenuVisibilityRule
{
    public function supports(string $expression): bool;
    public function allows(string $expression, MenuUserContext $user): bool;
}
```

Initial rule classes can cover the old expression families:

- `AlwaysVisibleRule`
- `StaffExistsRule`
- `UserAccessRule`
- `AccessLevelRule`
- `KetuaProgramRule`
- `SecurityStaffRule`
- `DepartmentLeaderRule`
- `IcnoAllowlistRule`

This keeps old DB-driven flexibility while avoiding raw PHP execution from the database.

## URL Migration Rules

Known local Yii3 route mappings should be preferred:

| Yii2 route | Yii3 route |
| --- | --- |
| `dashboard/index` | `/dashboard` |
| `site/menu` | `/menu` |
| `biodata/userview` | `/biodata/userview` |
| `biodata/index` | `/biodata/index` |
| `biodata/lihatbiodata` | `/biodata/lihatbiodata` |
| `alamat/view` | `/alamat/view` |
| `pendidikan/view` | `/pendidikan/view` |
| `bahasa/view` | `/bahasa/view` |
| `badan-profesional/view` | `/badan-profesional/view` |
| `anugerah/view` | `/anugerah/view` |
| `pasport-permit/view` | `/pasport-permit/view` |
| `lesen/view` | `/lesen/view` |
| `akaun/senarai-akaun` | `/akaun/senarai-akaun` |
| `kecacatan/view` | `/kecacatan/view` |
| `pengalamankerja/view` | `/pengalamankerja/view` |
| `sejarahperubatan/view` | `/sejarahperubatan/view` |
| `keluarga/view` | `/keluarga/view` |
| `bidangkepakaran/view` | `/bidangkepakaran/view` |
| `vaksinasi/index` | `/vaksinasi/index` |
| `kelayakan-profesional/view` | `/kelayakan-profesional/view` |

Everything else should remain a legacy handoff:

```text
http://127.0.0.1:8083/<old-route>
```

This keeps the menu useful before every downstream module is migrated.

To preserve URL flexibility, route mapping should live in one small data structure or config method, not in the template. Unknown relative URLs are still valid and should be handed to Yii2 automatically:

```php
private const LOCAL_ROUTE_MAP = [
    'dashboard/index' => '/dashboard',
    'site/menu' => '/menu',
    // migrated routes...
];
```

When a module is migrated later, only this map should need a new entry.

## UI Plan

Use the current Yii3 Tailwind/Flowbite-like visual direction:

- full page main card
- heading: `Menu`
- compact category cards in a two-column desktop grid
- one-column grid on mobile
- icon at category title using lucide where possible
- top-level item rows with:
  - label
  - chevron if collapsible
  - external-link indicator for legacy/external URLs
- no nested card-inside-card styling beyond actual category cards
- collapsible submenus with native `details/summary` or small JS using existing `site.js`

Prefer `details/summary` for first pass because it avoids extra custom JS.

## Migration Phases

### Phase 1: Read-only Page

- Add `GET /menu`
- Add `MenuService`
- Add `MenuVisibilityService` with conservative supported visibility checks
- Render category cards
- Map known migrated routes to Yii3
- Route unknown old routes to Yii2 port `8083`
- Update sidebar `Menu` and dashboard shortcut to `/menu`

Verification:

- unauthenticated `/menu` redirects to `/login`
- authenticated `/menu` returns HTTP 200
- category count matches visible filtered result
- page includes migrated links for Rekod Peribadi routes
- page includes legacy links for non-migrated modules
- no raw `eval()`

### Phase 2: Visibility Parity Audit

- Compare visible menu rows for the current test staff between Yii2 and Yii3
- Record unsupported `visible` expressions
- Add explicit handlers only for needed expression families
- Keep unsupported rows hidden until reviewed
- Add a developer-only unsupported-rule list in service output or logs so new DB menu rules can be mapped without changing page markup

Verification:

- current test staff sees expected normal staff menu rows
- admin-only rows are not exposed to normal users
- rows gated by security staff or special admin tables are handled only after direct source mapping

### Phase 3: UX Polish

- Add search/filter field if the menu feels too dense
- Add category item counts
- Add "migrated" vs "legacy" visual markers if helpful for migration testing
- Browser screenshot review at desktop and mobile widths

## Definition Of Done

- Sidebar `Menu` opens Yii3 `/menu`
- `/menu` uses the Yii2 DB menu tables directly
- no `eval()` exists in Yii3 menu code
- unsupported visibility expressions do not expose rows
- migrated routes stay on port `8084`
- non-migrated routes intentionally hand off to port `8083`
- PHP lint passes
- `php yii list` boots
- authenticated browser smoke test passes
- migration notes updated in `progress.md`
