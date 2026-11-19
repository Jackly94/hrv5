# AI Execution Tasks: PHP 8.5.12 Compatibility Pass

Dokumen ini **tidak** bergantung pada keputusan Yii3 (lihat `00_Ringkasan_Keputusan.md`) — ini
laluan naik taraf enjin PHP semata-mata, sama jenis kerja dengan `AI/AI_Tasks_Upgrade_Yii2_Corrected.md`
yang telah berjaya membawa aplikasi ini daripada PHP lama ke 8.4.25. Boleh dilaksanakan serta-merta.

## Apa yang sudah diketahui (jangan andaikan semula)

- **Enjin PHP semasa:** `8.5.12` terpasang dan **sedang hidup** (2026-11-13) — `php_old/`
  (8.4.25) dikekalkan di disk sebagai fallback, tidak dibuang. Apache (`httpd.exe`, dijalankan
  terus sebagai proses — **tiada servis Windows didaftarkan** untuk Apache di persekitaran ini,
  jadi ia tidak start semula secara automatik selepas reboot melainkan diuruskan secara manual
  atau servis didaftarkan) mengesahkan `HTTP 200` di `localhost/`.
- **Teras Yii2 semasa:** `yiisoft/yii2` `~2.0.14` di `composer.json` tetapi versi terpasang
  sebenar `2.0.55` (lihat `AI/AI_Tasks_Upgrade_Yii2_Corrected.md`) — **sahkan semula** versi
  terpasang sebenar (`composer show yiisoft/yii2`) sebelum mula, jangan percaya nombor constraint.
- PHP 8.5 adalah naik taraf *minor* berbanding 8.4 (bukan seperti 7→8), jadi jangkaan amaran
  *breaking* adalah rendah, tetapi beberapa fungsi/ciri lapuk PHP 8.4 mungkin dibuang terus di 8.5.

## Keperluan baharu direkodkan: pemacu SQL Server (`sqlsrv`/`pdo_sqlsrv`) untuk PHP 8.5

**Keperluan ini wujud kerana aplikasi bergantung pada 7 komponen pangkalan data SQL Server
(`db3`-`db6`, `db9`, `db10`, `db12`, `db14` — lihat `CLAUDE.md`).** Pemacu lama (`php_sqlsrv_84_ts_x64.dll`
/ `php_pdo_sqlsrv_84_ts_x64.dll`, Microsoft Drivers 5.x) ditetapkan khusus kepada binaan PHP 8.4
— tidak serasi terus dengan 8.5, dan tiada fail `_85_` di `php_old/ext/`.

**Status: Selesai dipasang (2026-11-13).**
- Punca: Microsoft Drivers for PHP for SQL Server **5.13.3** (dikeluarkan 2026-08-07) —
  menambah sokongan PHP 8.4 **dan 8.5** sejak versi 5.13.0 (2026-02-27), pakej Windows dimuat
  turun daripada pautan rasmi Microsoft (`go.microsoft.com/fwlink/?LinkId=2375152`).
- Fail dipasang: `C:\php\ext\php_sqlsrv_85_ts_x64.dll`, `C:\php\ext\php_pdo_sqlsrv_85_ts_x64.dll`
  (padanan tepat binaan ZTS x64 PHP 8.5.12 yang terpasang).
- `C:\php\php.ini` dikemas kini (dua baris `extension=` ditambah selepas bahagian `pdo_pgsql`,
  mengikut konvensyen fail sama di `php_old/php.ini`).
- Disahkan selepas Apache dimulakan semula: `php -m` menyenaraikan `sqlsrv` dan `pdo_sqlsrv`,
  `HTTP 200` di `localhost/` kekal berfungsi.
- **Belum disahkan**: sambungan sebenar ke mana-mana pangkalan data SQL Server (`db3`-`db6`,
  `db9`, `db10`, `db12`, `db14`). Memuatkan sambungan PHP sahaja **tidak** mengesahkan lapisan
  ODBC — isu sedia ada "pemacu ODBC disekat oleh Windows registry quirk" (direkodkan untuk
  PHP 8.4 di `AI/UPGRADE_PROGRESS.md` Effort 1) adalah lapisan berasingan di bawah sambungan ini
  dan **masih perlu disahkan semula** di bawah PHP 8.5 — jangan andaikan ia automatik terselesai
  kerana sambungan `sqlsrv` kini wujud.
  - **Kemas kini 2026-11-13 (sesi lain):** pemacu ODBC Driver 18 for SQL Server kini **dipasang**
    (lihat `upgradeChanges.md` "Next requirement" — isu registry `LaunchConditions` diselesaikan
    melalui skrip elevated sekali-guna). Tetapi lapisan baharu ditemui: ODBC Driver 18 default
    kepada sambungan TLS tersulit dengan pengesahan sijil ketat (Driver 17 tidak), jadi `db4`
    kini gagal pada peringkat SSL handshake (`SQLSTATE[08001]`), bukan lagi "pemacu hilang".
    Pembetulan dicadangkan (`TrustServerCertificate=yes` pada DSN) **belum diaplikasikan** —
    lihat fail tersebut untuk langkah sambung semula.

- **Ditemui & dibetulkan semasa TUGASAN 1 (2026-11-13): 6 sambungan PHP hilang secara tidak
  disengajakan dalam `php.ini` 8.5 yang baharu**, berbanding `php_old/php.ini` (8.4) yang masih
  mengaktifkannya — nampak seperti `php.ini` 8.5 dimulakan daripada templat lalai dan tidak semua
  sambungan sedia-ada dipindah semula. DLL sedia ada untuk kesemuanya di `C:\php\ext\`, jadi
  dipulihkan terus (padanan keadaan 8.4 yang sudah berfungsi, bukan ciri baharu):
  - `openssl` — **kesan serta-merta**: `composer` gagal terus dengan ralat "openssl extension is
    required for SSL/TLS" sebelum pembetulan. Mana-mana integrasi HTTPS aplikasi (IICS, Microsoft
    OAuth, dll.) turut akan terjejas.
  - `zip` — digunakan oleh `phpoffice/phpspreadsheet`/`mk-j/php_xlsxwriter` (eksport Excel).
  - `intl`, `mysqli`, `odbc`, `pdo_odbc` — tiada panggilan terus `mysqli_*`/`odbc_connect` ditemui
    dalam `controllers/`, `models/`, `components/`, `commands/` (aplikasi guna PDO sepenuhnya),
    tetapi dipulihkan untuk padanan persekitaran 8.4 yang disahkan berfungsi; `intl` relevan
    kepada format Yii2 (`Yii::$app->formatter`).
  - Disahkan selepas pembetulan: `php -m` menyenaraikan kesemua enam, tiada amaran permulaan,
    `composer show`/`composer check-platform-reqs` kini berfungsi.
  - **Apache belum dimulakan semula** untuk memuatkan `php.ini` yang dikemas kini pada SAPI web —
    proses `httpd.exe` tidak sedang berjalan langsung semasa pembetulan ini dibuat. Perlu mulakan
    Apache (atau mulakan semula jika ia dimulakan sebelum membaca ini) sebelum menguji laluan web.

## TUGASAN 1: Audit Keserasian Rangka Kerja & Pakej

- **Fail Sasaran:** `composer.json`, `composer.lock`
- **Kenapa:** Yii2 teras dan kebanyakan pakej `kartik-v/*`/`yiisoft/yii2-*` yang dipakai aplikasi
  ini (lihat `Audit_Kebergantungan_Yii3.md` untuk senarai penuh) ditulis untuk PHP 7/8.0-8.4.
  Perlu disahkan setiap pakej mengisytiharkan sokongan PHP 8.5 di `composer.json` masing-masing
  sebelum menukar constraint enjin — bukan andaikan ia automatik serasi.
- **Prompt untuk AI:**
  ```text
  Task: Audit framework/package compatibility with PHP 8.5 before changing any engine
  constraint.
  1. Confirm actual installed PHP engine version (php -v) and actual installed yiisoft/yii2
     version (composer show yiisoft/yii2) — do not trust composer.json's version range alone.
  2. Run `composer outdated` and separately check the Yii2 framework's own changelog/issue
     tracker for explicit PHP 8.5 support statements.
  3. For each package in composer.json requiring "dev-master" or "@dev" (already catalogued in
     AI/composer_dev_pins_audit.md), check whether its current installed commit/branch contains
     any PHP 8.5-incompatible code (deprecated/removed functions, removed extensions).
  4. Do NOT change the "php" constraint in composer.json yet. Just report: which packages are
     confirmed PHP 8.5-safe, which are unconfirmed, and which show known issues.
  ```

- **Keputusan (2026-11-13):**
  - Enjin disahkan: `php -v` → `8.5.12`. Teras Yii2 disahkan: `composer show yiisoft/yii2` →
    `2.0.55` (padan dengan nota sedia ada — jangan percaya `~2.0.14` di `composer.json`).
  - `composer check-platform-reqs` dijalankan terhadap **seluruh** pokok pakej dalam `vendor/` —
    cara paling berwibawa untuk tugasan ini, kerana Composer sendiri menyemak keperluan
    `php`/sambungan setiap pakej yang dipasang terhadap platform sebenar, bukan hanya pakej utama.
  - **Satu-satunya kegagalan dalam SELURUH pokok pakej:** `phpoffice/phpspreadsheet` (1.30.6,
    dipasang melalui kekangan `^1.9` di `composer.json`) mengisytiharkan
    `"php": ">=7.4.0 <8.5.0"` secara eksplisit — ia menolak PHP 8.5 secara nyata (bukan sekadar
    tidak teruji). Disahkan melalui `cat vendor/phpoffice/phpspreadsheet/composer.json`.
    - Versi utama lebih baharu (disahkan melalui Packagist: versi stabil terkini `5.11.0`)
      menggunakan kekangan `"php": "^8.2"` (iaitu `>=8.2.0 <9.0.0`, **termasuk** 8.5) — jadi
      isu ini telah dibetulkan di hiliran projek, tetapi memerlukan naik taraf versi utama
      (1.x → 5.x), bukan sekadar tukar kekangan enjin. Naik taraf major phpspreadsheet
      berkemungkinan membawa perubahan API (breaking changes) dan **bukan skop TUGASAN 1** —
      lapor sahaja, jangan laksanakan di sini.
    - `mk-j/php_xlsxwriter` turut muncul dalam amaran `composer outdated` sebagai tidak boleh
      dinilai, tetapi sebabnya adalah `ext-zip` hilang (telah dibetulkan di atas), bukan isu
      PHP 8.5 pada pakej itu sendiri — selepas `zip` diaktifkan semula, tiada amaran lagi
      untuk pakej ini.
  - **28 pakej `dev-master`/`@dev`** (katalog penuh di `AI/composer_dev_pins_audit.md`) semuanya
    **LULUS** `check-platform-reqs` pada komited semasa — tiada isu PHP 8.5 ditemui pada mana-mana
    pakej Kartik-v/lain yang dipasang. Audit pin-ke-versi-tag sedia ada (fail di atas) tidak
    berkaitan terus dengan PHP 8.5 — ia tentang kestabilan versi, bukan keserasian enjin.
  - **Kesimpulan:** selamat untuk teruskan menggunakan PHP 8.5.12 secara langsung dengan kod
    sedia ada **kecuali** fungsi eksport yang bergantung pada `phpoffice/phpspreadsheet` —
    pakej itu berjalan buat masa ini (kod sudah dipasang di `vendor/`, Composer tidak menjalankan
    semakan platform semasa permintaan HTTP biasa), tetapi sebarang `composer install`/`update`
    akan baru pada platform 8.5.12 akan **gagal** pada pakej ini melainkan ia dinaik taraf
    atau kekangan `php` di `composer.json` induk ditukar untuk pengecualian (tidak disyorkan).
  - **TIDAK dilaksanakan (mengikut arahan tugasan):** kekangan `"php"` di `composer.json`
    (baki `>=8.4`) tidak ditukar.

## TUGASAN 2: Kenal Pasti Fungsi/Ciri Yang Dibuang/Lapuk di PHP 8.5

- **Fail Sasaran:** `controllers/`, `models/`, `components/`, `commands/`, `widgets/`
- **Kenapa:** Setiap naik taraf major/minor PHP membuang ciri yang sebelum ini hanya *deprecated*.
  Laluan PHP 8.4 lepas telah mendedahkan dan membetulkan beberapa (parameter opsyenal sebelum
  wajib, panggilan method statik/bukan-statik tidak konsisten — lihat `upgradeChanges.md`).
  Perlu semakan khusus untuk apa yang baharu di 8.5 berbanding 8.4.
- **Prompt untuk AI:**
  ```text
  Task: Scan the codebase for usage of PHP functions/features that are deprecated-as-of-8.4 and
  now REMOVED (fatal, not just a warning) in 8.5, based on the official PHP 8.5 migration guide.
  1. Read the official PHP 8.5 UPGRADING notes (not PHP 8.4 — those changes are already handled).
  2. Grep controllers/, models/, components/, commands/, widgets/ for any function/syntax
     pattern flagged as removed in 8.5.
  3. For each hit, report file:line, the exact removed feature, and a minimal-diff fix that
     preserves existing behavior (do not refactor surrounding code).
  4. Do not touch vendor/, runtime/, or web/assets/.
  5. Report findings only in this pass — do not apply fixes without confirming scope first if
     more than ~10 files are affected.
  ```

## TUGASAN 3: `php -l` Sweep + Rector Dry-Run Semula

- **Fail Sasaran:** Terminal, `rector.php`
- **Kenapa:** `rector.php` sudah wujud (lihat `AI/rector_dry_run_summary.md`, dry-run PHP 8.4
  sahaja, 480 fail dikenal pasti, **belum diaplikasikan**). Sebelum menambah lapisan PHP 8.5 di
  atas kerja yang belum disiapkan, perlu laluan asas bersih dahulu.
- **Prompt untuk AI:**
  ```text
  Task: Re-baseline the existing Rector setup for PHP 8.5, without losing the PHP 8.4 dry-run
  findings already catalogued in AI/rector_dry_run_summary.md.
  1. Confirm clean git status first; stop and ask if there are uncommitted changes.
  2. Run `php -l` across controllers/, models/, components/, commands/, widgets/ under the
     actual PHP 8.5 binary once it's installed locally — report any parse errors (there should
     be none if PHP 8.4 code was already clean, but confirm rather than assume).
  3. Check installed Rector version for a PHP 8.5-specific level set. If none exists yet
     (likely, given how new 8.5 is), stay on the highest available level set and note that
     8.5-specific rules need manual coverage per Task 2 above.
  4. Re-run `vendor/bin/rector process --dry-run` and diff the file count/rule list against the
     480-file PHP 8.4 baseline — report only what's NEW due to the PHP 8.5 target, not a
     re-explanation of the existing baseline.
  5. Do NOT run the non-dry-run process.
  ```

## TUGASAN 4: Uji Laluan Kritikal Selepas Naik Taraf Enjin Sebenar

- **Fail Sasaran:** Persekitaran tempatan/staging sahaja (bukan kod)
- **Kenapa:** Laluan PHP 8.4 lepas mendedahkan masalah (crash, memory exhaustion, SQL mode
  incompatibility — lihat `AI/UPGRADE_PROGRESS.md` Effort 1) hanya semasa ujian menu sebenar,
  bukan semasa `php -l`. Jangkaan sama untuk 8.5 — ini bukan kerja yang boleh disahkan oleh
  analisis statik sahaja.
- **Prompt untuk AI (perlu akses persekitaran berjalan, bukan semakan kod sahaja):**
  ```text
  Task: Once the local/staging environment actually runs PHP 8.5.12, walk the same set of
  active side-menu routes previously tested for PHP 8.4 (see upgradeChanges.md for that list)
  and log any new fatal errors, warnings-as-fatal, or behavior changes specific to 8.5.
  Do not re-fix PHP 8.4-era issues already resolved — only report NEW breakage.
  Do not run mailers, queue jobs, sync commands, or write-heavy console commands during this
  pass (per CLAUDE.md) — read-only route walking only, flag anything needing a write-path test
  for separate confirmation.
  ```

## Status

- [x] **Prasyarat:** PHP 8.5.12 terpasang, pemacu `sqlsrv`/`pdo_sqlsrv` 5.13.3 dipasang dan disahkan dimuatkan (2026-11-13). ODBC Driver 18 kini dipasang (sesi lain, lihat `upgradeChanges.md`), tetapi sambungan sebenar ke SQL Server masih disekat oleh isu SSL trust baharu — **belum disahkan selesai**. Apache **tidak sedang berjalan** semasa kemas kini terakhir (`httpd.exe` tiada proses) — mulakan sebelum sebarang ujian web.
- [x] **TUGASAN 1 — selesai (2026-11-13).** Dapatan utama: hanya SATU isu keserasian PHP 8.5 dalam seluruh pokok pakej (`phpoffice/phpspreadsheet` 1.30.6 menolak `<8.5.0` secara eksplisit; naik taraf ke 5.x akan membetulkan tetapi ini naik taraf major berasingan). Bonus: ditemui & dibetulkan 6 sambungan PHP yang tertinggal dalam `php.ini` 8.5 (`openssl`, `zip`, `intl`, `mysqli`, `odbc`, `pdo_odbc`) — lihat bahagian Prasyarat di atas untuk butiran penuh. Kekangan `php` di `composer.json` **tidak** ditukar (mengikut arahan tugasan).
- [ ] TUGASAN 2 — belum dimulakan
- [ ] TUGASAN 3 — belum dimulakan
- [ ] TUGASAN 4 — belum dimulakan (sahkan sambungan ODBC/SQL Server dahulu, dan mulakan Apache, sebelum laluan menu yang menyentuh `db3`-`db6`/`db9`/`db10`/`db12`/`db14`)

Kemas kini status di sini selepas setiap tugasan selesai, mengikut gaya `AI/AI_Tasks_Upgrade_Yii2_Corrected.md`.
