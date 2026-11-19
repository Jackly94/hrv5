# Keperluan Baharu: PHP 8.5.12 + Yii3 — Ringkasan & Keputusan Diperlukan

Dokumen ini menilai keperluan upgrade baharu yang diminta: **PHP 8.5.12** dan **framework Yii3**,
berdasarkan keadaan sebenar kod pangkalan (lihat `AI/UPGRADE_PROGRESS.md` untuk status kerja
PHP 8.4 / AdminLTE 4 yang sedang berjalan — kerja itu TIDAK digugurkan oleh dokumen ini).

## TL;DR

- **PHP 8.5.12** — ini satu naik taraf *enjin* yang berpatutan, sama jenis kerja dengan laluan
  PHP 8.4 yang sudah berjaya dilakukan (`AI/AI_Tasks_Upgrade_Yii2_Corrected.md`). Lihat
  `AI_Tasks_PHP85.md` di folder ini untuk tugasan sebenar — **boleh terus dilaksanakan**.
- **Yii3** — ini **bukan** naik taraf versi (bukan seperti Yii 2.0.53 → 2.0.55). Ia satu framework
  yang berlainan sepenuhnya dari segi seni bina. **Tiada laluan migrasi automatik/mekanikal wujud.**
  Lihat `Audit_Kebergantungan_Yii3.md` untuk bukti terperinci. POC kecil sebelah-menyebelah
  **sudah dibina** pada 2026-11-13 di `C:\Apache24\htdocs\staff-yii3-poc\`; ia menguji laluan
  sesi Yii2 + bacaan `db2`, bukan migrasi aplikasi.

## Kenapa Yii3 bukan "upgrade versi" biasa

Yii2 dan Yii3 berkongsi nama jenama sahaja. Perbezaan seni bina teras:

| Aspek | Yii2 (kod sedia ada) | Yii3 |
|---|---|---|
| Struktur | Monolith (`yii2-app-basic`), `Yii::$app` singleton global | Kumpulan pakej PSR berasingan (`yiisoft/*`), tiada singleton global |
| DI Container | Tersirat (service locator `Yii::$app->get()`) | Eksplisit, PSR-11 (`yiisoft/di`), kena *wire* setiap servis |
| Routing/Request | `yii\web\Controller`, aksi kaedah, `$_REQUEST` biasa dipakai | PSR-7/PSR-15 middleware stack sepenuhnya |
| ActiveRecord | Terbina-dalam (`yii\db\ActiveRecord`), semua model aplikasi ini warisi ini | Tiada dalam teras; `yiisoft/active-record` wujud tetapi API/konvensyen berbeza, bukan drop-in |
| Asset Bundles | `AppAsset`, `$depends`, `$css`, `$js` — corak yang dipakai meluas dalam `assets/`, `widgets/` | Tiada sistem AssetBundle yang sama; pengurusan aset berasingan sepenuhnya |
| Widget pihak ketiga | Ekosistem besar (Kartik-v, Gentelella, DynamicForm — lihat audit) | Hampir tiada widget Yii2 serasi; kebanyakan tidak mempunyai versi Yii3 langsung |
| Identity/Access | `yii\web\IdentityInterface`, `AccessControl` filter | Pendekatan PSR-15 middleware + pakej `yiisoft/auth`, konvensyen berbeza |
| Alat migrasi rasmi | — | **Tiada.** Pasukan Yiisoft sendiri menyifatkan Yii2→Yii3 sebagai projek semula (rewrite), bukan migrasi berperingkat seperti Symfony/Laravel major version. |

## Kesan ke atas kod pangkalan ini secara khusus

Aplikasi ini bukan Yii2 "basic" yang ringkas — ia bergantung berat pada corak yang **semuanya**
tidak wujud di Yii3:

- **~30 pakej `kartik-v/*`** (grid, date-range, select2, mpdf export, builder, dsb.) — lihat
  `Audit_Kebergantungan_Yii3.md`. Tiada satu pun ada versi Yii3.
- **`yiister/yii2-gentelella`** (tema semasa) dan calon gantian `AdminLTE 4` (kerja sedang
  berjalan, lihat `AI/adminlte4_pilot.md`) — kedua-dua dibina atas andaian `yii\web\AssetBundle`
  dan `yii\bootstrap\Widget`. Tiada bersamaan di Yii3.
- **Setiap model dalam `models/`** (merentasi 19 komponen pangkalan data — `db` hingga `db19`,
  MySQL + SQL Server) warisi `yii\db\ActiveRecord`. Ini kena ditulis semula, bukan ditukar.
- **`AccessControl` + `Identity` + `LoginForm`** (AD, Microsoft OAuth, Portal Kinabalu, HR
  password) — seluruh lapisan auth kena ditulis semula atas abstraksi PSR-15 yang berbeza.
- **Komponen aplikasi tersuai** (`ActiveDirectory`, `MicrosoftOAuth`, `EmployeeMaster`/`GRP`,
  `MaklumatPeribadi`/`MP`, `UAPI`/`FileManager`, `AccessManager`, `IICS`, `Zakat`) semua
  bergantung pada `Yii::$app->get()` service-locator — corak yang tiada di Yii3.
- Tidak termasuk kerja AdminLTE 4 yang sedang berjalan (~90 halaman lagi) — jika Yii3 dipilih,
  kerja tema itu turut terbuang kerana lapisan view/controller bawahnya berubah sepenuhnya.

Anggaran kasar: migrasi Yii3 bermaksud menulis semula hampir **setiap controller, model, view,
dan widget** dalam aplikasi ini (~98 laluan menu aktif, 19 sambungan pangkalan data, beberapa
integrasi luaran langsung). Ini satu projek berasingan berbulan-bulan hingga tahunan dengan
pasukan khusus — bukan tugasan yang sesuai untuk senarai "AI Tasks" seperti pendekatan PHP 8.4.

## Pilihan yang perlu diputuskan oleh manusia

1. **Teruskan PHP 8.5 sahaja sekarang, tangguh Yii3 tanpa tarikh** — risiko paling rendah,
   manfaat serupa (keselamatan/prestasi enjin PHP) tanpa kos rewrite. *Disyorkan.*
2. **Mulakan kajian/proof-of-concept Yii3 secara berasingan** (contoh: satu modul kecil/baharu
   dibina di Yii3 bersebelahan aplikasi Yii2 sedia ada, bukan menukar yang sedia ada) — untuk
   menilai kos sebenar tanpa menggugat sistem produksi semasa. **Status 2026-11-13: POC asas
   sudah dibina dan disahkan secara CLI/HTTP; ujian browser sebenar dengan sesi Yii2 aktif
   masih perlu dibuat** — lihat `02_Skop_POC.md`.
3. **Komited penuh kepada rewrite Yii3** — perlu bajet, garis masa, dan pasukan khusus
   diluluskan secara rasmi dahulu; dokumen perancangan terperinci (bukan "AI Tasks") perlu
   ditulis selepas keputusan ini, bukan sebelum.
4. **Tunggu laluan migrasi rasmi matang** (jika Yiisoft menerbitkan panduan/alat migrasi
   berperingkat pada masa depan) — pantau sahaja buat masa ini.

**Tindakan disyorkan selepas POC asas:** Jalankan ujian browser sebenar: log masuk ke Yii2,
buka `http://localhost:8083/session-probe`, kemudian `http://localhost:8083/biodata-me`.
Jika cookie `hrv4` tidak dihantar, sajikan POC melalui alias/vhost Apache pada host yang sama
dengan aplikasi Yii2 dan ulang ujian. Jangan anggap POC ini sebagai lampu hijau rewrite penuh;
keputusan strategik masih perlu dibuat selepas dapatan sesi sebenar direkod.
