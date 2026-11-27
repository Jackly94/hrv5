# Migration Standard Revisit Audit

Date recorded: 2026-11-28

This audit records whether migrated modules currently follow the maintainer-friendly migration standard:

- direct normal form markup for final migrated forms
- no metadata-style field loops as final implementation
- no high-level programming style that hides workflow behavior
- no over-engineered mini-framework style for simple workflows; normal explicit Yii3/PHP code is preferred
- clear route, action, view, field names, validation path, and named service rules
- fast migration with low maintainability is rejected

## Current Assessment

| Module / Area | Status | Revisit rule |
| --- | --- | --- |
| Lantikan Staf Baru, PHS, Khas, SPDP | Pass | Keep dedicated action/view pairs. Do not restore generic `FormAction` or metadata-rendered forms. |
| Cuti staff leave application forms: `ck`, `cb`, `cr1`, `cr1-aca`, `cr2`, `ctr`, `cs1`, `cs2`, `hso` | Pass at action/view layer | Keep dedicated action/view pairs and direct form markup. Do not restore shared conditional `form.php`. |
| Cuti application service layer | Partial | Existing shared `CutiApplicationService` is acceptable temporarily because Cuti writes live records. When revisiting a leave type, move that type's validation/workflow rules toward named type-specific services without changing live write behavior blindly. |
| Cuti read/list/admin/officer pages | Partial | Multiplexer actions such as `PageAction`, `OfficerAction`, and `AdminAction` are acceptable only as temporary read/list migration structure. When a page gets write behavior or meaningful page-specific rules, split it into a named action/view. |
| Pengesahan Data Staf | Mostly pass | Named actions are generally clear. Generic detail field rendering may remain lower priority, but any revisited write screen should keep explicit action/service behavior. |
| Biodata main form and Biodata section forms | Out of standard | Replace `$form['fields']` loops and service `FIELDS`/`CONFIG` form metadata with direct maintainer-friendly forms when revisiting each Biodata form. This is the highest cleanup priority. |
| Gambar Profile upload forms | Out of standard, small | Replace upload `$form['fields']` loops with direct file input markup in staff/admin upload views. This is a low-risk cleanup candidate. |
| Kehadiran/STARS broad actions and `WriteAction` | Out of standard for write workflows | Split write workflows such as reason approval, WFH approval/delete, and THTC verify/approve into named actions/views/services when revisited. Read-only multiplexers can be cleaned later, but new write work must not add more slug-based branching. |

## Cleanup Priority

1. Biodata forms, because metadata-style form rendering is widespread.
2. Kehadiran/STARS `WriteAction`, because one action hides multiple write workflows.
3. Gambar Profile upload forms, because they are small and easy to convert.
4. Cuti service split by leave type, after a safe live-write test plan exists.
5. Cuti and Kehadiran read/list multiplexer actions, after higher-risk write forms are clean.

## Already Cleaned During This Standard Pass

- Lantikan generic `FormAction.php` and `form.php` were removed.
- Lantikan Staf Baru, PHS, Khas, and SPDP now use dedicated action/view pairs.
- Cuti individual leave forms now use dedicated action/view pairs and direct form markup.
- Cuti old shared `src/Web/Cuti/Individu/FormAction.php` and conditional `src/Web/Cuti/Individu/form.php` were removed.
