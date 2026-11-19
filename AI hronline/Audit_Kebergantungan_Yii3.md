# Audit Kebergantungan: Apa Yang Patah Jika Yii3 Dipilih

Senarai ini daripada `composer.json` sebenar projek (semak semula jika fail itu berubah).
Tujuan: tunjukkan secara konkrit kenapa migrasi Yii3 bukan kerja "tukar nama pakej".

## 1. Pakej rasmi `yiisoft/yii2-*` — semua kena diganti, bukan ditukar versi

| Pakej Yii2 semasa | Status di Yii3 |
|---|---|
| `yiisoft/yii2` `~2.0.14` | Diganti oleh set pakej `yiisoft/*` berasingan (`yiisoft/app`, `yiisoft/di`, `yiisoft/router`, dsb.) — bukan `yiisoft/yii3` tunggal |
| `yiisoft/yii2-authclient` | Pakej Yii3 berlainan API (`yiisoft/auth-*`), kena tulis semula flow AD/Microsoft OAuth/Portal Kinabalu |
| `yiisoft/yii2-bootstrap`, `yiisoft/yii2-bootstrap5` | Tiada widget Bootstrap terbina-dalam yang sama di Yii3 |
| `yiisoft/yii2-debug`, `yiisoft/yii2-gii` | Gii (penjana kod) tiada bersamaan langsung di Yii3 |
| `yiisoft/yii2-faker` | Perlu disulih dengan pustaka `fakerphp/faker` terus (bukan wrapper Yii) |
| `yiisoft/yii2-httpclient` | Disulih oleh klien PSR-18 (contoh Guzzle) + `yiisoft/http` |
| `yiisoft/yii2-imagine` | Perlu wiring manual pustaka `imagine/imagine` tanpa wrapper |
| `yiisoft/yii2-queue` | `yiisoft/queue` wujud tetapi API/konfigurasi berbeza — queue DB (`system_core.tbl_queue`) kena disemak semula |
| `yiisoft/yii2-redis` | Disulih klien Redis PSR-16/langsung |
| `yiisoft/yii2-swiftmailer` | SwiftMailer sendiri sudah *deprecated* huluan; Yii3 guna `yiisoft/mailer` + Symfony Mailer |

## 2. Ekosistem Kartik-v (24 pakej dalam `composer.json`) — SIFAR bersamaan Yii3

```
kartik-v/yii2-bootstrap5-dropdown   kartik-v/yii2-builder          kartik-v/yii2-checkbox-x
kartik-v/yii2-datecontrol           kartik-v/yii2-date-range       kartik-v/yii2-detail-view
kartik-v/yii2-dialog                kartik-v/yii2-dynagrid         kartik-v/yii2-editable
kartik-v/yii2-editors               kartik-v/yii2-export           kartik-v/yii2-field-range
kartik-v/yii2-grid                  kartik-v/yii2-mpdf             kartik-v/yii2-number
kartik-v/yii2-password              kartik-v/yii2-popover-x        kartik-v/yii2-sortable-input
kartik-v/yii2-tabs-x                kartik-v/yii2-widget-datetimepicker
kartik-v/yii2-widget-fileinput      kartik-v/yii2-widget-growl     kartik-v/yii2-widget-rating
kartik-v/yii2-widgets                kartik-v/yii2-widget-select2  kartik-v/yii2-widget-spinner
kartik-v/yii2-widget-switchinput    kartik-v/yii2-widget-timepicker
```

Semua ini bergantung pada `yii\base\Widget` dan `yii\web\AssetBundle` — kedua-dua kelas tidak
wujud di Yii3. Repositori `kartik-v` tidak pernah menerbitkan versi Yii3. Setiap GridView,
date-picker, select2, export-PDF (mpdf), builder-form yang dipakai merentasi `views/` kena
ditulis semula menggunakan HTML/JS manual atau pustaka JS gantian (contoh DataTables terus,
Choices.js, dsb.) — bukan tukar `use` statement.

## 3. Tema & widget tambahan

- **`yiister/yii2-gentelella` `~1.0`** (tema semasa) — pembalut Yii2 lapuk (2015), bergantung
  keras pada `yiisoft/yii2-bootstrap`. Tiada versi Yii3.
- **`wbraganca/yii2-dynamicform` `dev-master`** — digunakan untuk borang dinamik berulang
  (biasa dalam borang HR bertingkat). Tiada bersamaan Yii3; perlu disulih JS tersuai.
- **AdminLTE 4 (kerja sedang berjalan, lihat `AI/adminlte4_pilot.md`)** — pilot ini dibina atas
  `yii\web\AssetBundle` + layout Yii2 (`views/layouts/`). Jika Yii3 dipilih, kerja menukar ~90
  halaman lagi ke AdminLTE 4 jadi sia-sia kerana lapisan controller/view bawahnya ditulis semula.

## 4. Lapisan data — setiap model, bukan konfigurasi

- Semua fail dalam `models/` dan subfolder domain (`models/hronline/`, `models/keselamatan/`,
  dsb.) warisi `yii\db\ActiveRecord`. Yii3 **tiada ActiveRecord dalam teras**; pakej
  `yiisoft/active-record` komuniti wujud tetapi:
  - API relasi, *scope*, dan *behavior* berbeza daripada Yii2.
  - Tidak semestinya menyokong kesemua 19 komponen pangkalan data campuran MySQL + SQL Server
    (`db` hingga `db19` — lihat `CLAUDE.md` bahagian "Database Components") tanpa konfigurasi
    semula penuh setiap sambungan.
- Search model (`*Search.php`) yang membina `ActiveDataProvider` + `ActiveQuery` kena ditulis
  semula sepenuhnya mengikut corak query Yii3.

**Dapatan POC 2026-11-13:** untuk satu bacaan ringkas `Tblprcobiodata`, pendekatan paling kecil
ialah menggunakan `yiisoft/db-mysql` + SQL eksplisit, bukan `yii\db\ActiveRecord`. Ini berjaya
untuk satu halaman baca-sahaja (`/biodata-me`) tetapi mengesahkan jurang API: sambungan DB perlu
diwiring secara eksplisit melalui DI, relasi `jawatan`/`department` perlu diterjemah kepada join
SQL manual, dan tiada `ActiveDataProvider`/search-model Yii2 yang boleh dibawa masuk terus.

## 5. Auth, access control, dan komponen aplikasi tersuai

- `models/Identity.php` (`yii\web\IdentityInterface`), `models/LoginForm.php`, dan setiap
  `AccessControl` filter dalam controllers (`matchCallback` domain-specific) dibina atas
  `yii\web\User` + sesi Yii2 — tiada bersamaan langsung; Yii3 guna middleware PSR-15 untuk auth.
- Komponen tersuai yang didaftar dalam `config/web.php` dan dipanggil merentasi aplikasi
  (`Yii::$app->ActiveDirectory`, `->MicrosoftOAuth`, `->GRP`, `->MP`, `->FileManager`,
  `->AccessManager`, `->IICS`, `->Zakat`) semua bergantung pada corak *service locator*
  `Yii::$app->get()` — corak ini **tiada** di Yii3 (DI eksplisit sahaja). Setiap pemanggilan
  `Yii::$app->X` merentasi `controllers/`, `models/`, `views/`, `widgets/` kena dikesan dan
  ditulis semula menggunakan suntikan dependency.

**Dapatan POC 2026-11-13:** route `/session-probe` menunjukkan strategi paling kecil untuk modul
sebelah-menyebelah ialah membaca session PHP Yii2 `hrv4` secara read-only dan mengambil `__id`.
Ini belum menggantikan auth Yii2; ia hanya jambatan session untuk POC. Ujian browser sebenar
dengan login Yii2 aktif masih perlu menentukan sama ada cookie `hrv4` sampai ke aplikasi Yii3
apabila disajikan pada host/port semasa.

## 6. Anggaran skop (kasar, untuk tujuan keputusan sahaja)

- ~98 laluan menu aktif (lihat `upgradeChanges.md` / `AI/UPGRADE_PROGRESS.md`) — setiap satu
  controller + view + (biasanya) model/search-model berkaitan kena ditulis semula.
- 19 komponen pangkalan data merentasi MySQL + SQL Server kena disambung semula di bawah
  abstraksi Yii3.
- 24 pakej Kartik-v + 1 tema + 1 dynamic-form library tanpa bersamaan — semua widget borang/
  jadual/laporan dalam aplikasi perlu disulih secara manual.
- Integrasi luaran langsung (AD, Microsoft OAuth, IICS, Zakat, UAPI/FileManager) — setiap satu
  perlu disahkan semula berfungsi di bawah seni bina baharu.

Ini bukan anggaran hari/jam — ia memerlukan sesi skop berasingan (dengan input pemilik produk
dan pasukan infra DB) sebelum sebarang anggaran masa/kos yang bermakna boleh diberi.
