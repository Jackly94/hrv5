# AI HROnline Yii3 Migration Docs

This folder is for planning and developer documentation only. Do not place Yii3
application source code, `vendor/`, runtime files, or copied project skeletons here.

## Current Direction

- Keep the existing Yii2 `staff` application running.
- Build Yii3 migration work module by module.
- Use the first migrated module to define the repeatable pattern.
- Start with `external-login`, not the staff Portal Kinabalu / AD login.

## Code Location

The Yii3 application code should live outside this docs folder. The exact target
folder should be agreed before implementation continues.

Current candidate locations:

- Existing POC: `C:\Apache24\htdocs\staff-yii3-poc`
- New hronline Yii3 app folder: to be decided

## Docs

- `01_external_login_migration.md` - first module migration spec.
- `DEV_MODULE_MIGRATION_CHECKLIST.md` - checklist for future modules.
