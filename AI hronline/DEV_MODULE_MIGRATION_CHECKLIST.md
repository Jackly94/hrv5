# Dev Module Migration Checklist

Use this checklist for every Yii2 module migrated to Yii3.

## 1. Identify Yii2 Source

- Old route:
- Controller/action:
- Models:
- Search models:
- Views:
- Widgets/assets:
- DB component:
- Tables touched:

## 2. Classify Risk

- Read-only or write:
- Create/update/delete:
- External services:
- File upload:
- Email/queue:
- Audit log:
- Access rules:

## 3. Define Yii3 Target

- New route:
- Action class:
- Input/form object:
- Service/repository:
- Template:
- Redirect behavior:
- Session/auth requirement:

## 4. Preserve Behavior

- Validation rules match Yii2.
- Query filters match Yii2.
- Access control matches Yii2.
- Save/update/delete behavior matches Yii2.
- Logs/audit rows match Yii2.
- User-facing messages match current workflow where important.

## 5. Verify

- `php -l` on changed PHP files.
- Yii3 route loads.
- GET request works.
- POST success path works.
- POST failure path works.
- Yii2 session bridge works.
- Old Yii2 page still works.

## 6. Document Result

- What was migrated:
- What changed:
- What was intentionally not migrated:
- Known risks:
- Next module candidate:
