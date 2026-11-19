# Skop POC Yii3 (Pilihan 2 daripada `00_Ringkasan_Keputusan.md`)

Dokumen ini mengekod skop satu proof-of-concept (POC) Yii3 yang **berasingan** daripada aplikasi
Yii2 sedia ada — bukan menukar/menukar ganti mana-mana bahagian aplikasi semasa. POC ≠ migrasi —
tujuannya mengukur kos/risiko sebenar, bukan menghasilkan ciri produksi.

**Status kemas kini 2026-11-13:** POC asas sudah dibina di `C:\Apache24\htdocs\staff-yii3-poc\`
dan diasingkan daripada repo Yii2. Projek itu kini mempunyai git repo sendiri, skeleton Yii3
berjalan, route `/session-probe`, route `/biodata-me`, serta sambungan baca-sahaja ke `db2`
melalui `yiisoft/db-mysql`.

## Lokasi: folder berasingan sepenuhnya, bukan di dalam `staff/`

- Projek baharu: `C:\Apache24\htdocs\staff-yii3-poc\` — **composer.json dan `vendor/`
  sendiri**, tidak berkongsi dengan `staff/vendor/`. Sebab: pakej `yiisoft/*` dan `yii2/*`
  mempunyai keperluan PSR/versi berlainan sepenuhnya; mencampurkan dalam satu `composer.json`
  berisiko konflik versi yang tidak berkaitan dengan soalan sebenar POC ini.
- Disajikan sebagai **vhost Apache berasingan atau direktori berasingan di bawah domain yang
  sama** (bukan subfolder di dalam docroot `staff/`) — domain yang sama (bukan port/domain
  berlainan) adalah **penting** untuk satu soalan teknikal utama di bawah (perkongsian cookie
  sesi). Port berlainan pada domain yang sama masih berkongsi cookie; domain berlainan tidak.
- Tidak menyentuh, tidak meniru struktur folder `controllers/`, `models/`, `views/` aplikasi
  sedia ada — POC ini sengaja kecil dan berasingan, bukan cermin struktur sedia ada.

## Ciri dipilih untuk POC 1

Satu halaman **baca-sahaja**: staf log masuk (melalui aplikasi Yii2 sedia ada, seperti biasa)
boleh buka satu URL Yii3 dan lihat ringkasan biodata sendiri (nama, jawatan, jabatan) — setara
`Tblprcobiodata::find()->where(['ICNO' => ...])->one()` yang sedia digunakan meluas dalam kod
sedia ada (cth. `controllers/CalendarController.php:36`).

**Kenapa ciri ini:**
- Guna `db2` (hronline, MySQL tempatan) — bukan mana-mana 17 komponen pangkalan data rangkaian
  UMS yang jauh/SQL Server, supaya dapatan POC tidak bercampur dengan isu ODBC/rangkaian
  berasingan yang sudah direkod di `AI/UPGRADE_PROGRESS.md`.
- Baca satu rekod daripada satu jadual sudah cukup untuk menguji soalan sebenar (lihat bawah) —
  tidak perlu ciri kompleks untuk dapatkan jawapan.
- Sudah difahami sepenuhnya dalam bentuk Yii2 (banyak contoh sedia ada untuk rujuk/banding).

## Soalan teknikal utama yang POC perlu jawab

Ini sebab POC ini wujud — bukan untuk hasilkan ciri, tetapi jawapan kepada 3 soalan:

1. **Boleh kongsi sesi log masuk dengan aplikasi Yii2 sedia ada, atau setiap modul Yii3 perlu
   log masuk kali kedua?** Ini soalan paling kritikal untuk kebolehgunaan sebenar pendekatan
   "sebelah-menyebelah". Keadaan semasa: `config/web.php` guna `yii\web\Session` biasa (bukan
   `DbSession`), nama cookie `hrv4`, storan fail PHP lalai. Secara teori, kod PHP biasa (bukan
   Yii2) di bawah domain yang sama boleh `session_name('hrv4'); session_start();` dan baca data
   sesi yang sama — **tetapi ini belum disahkan sebenar** dengan struktur data sesi Yii2 yang
   sebenar (cth. bagaimana `Yii::$app->user->getId()` disimpan dalam `$_SESSION`). POC perlu
   cuba ini secara literal dan lapor sama ada ia berfungsi, separa berfungsi, atau tidak langsung.
2. **Berapa besar jurang API antara `yii\db\ActiveRecord` dan `yiisoft/active-record`
   (atau `yiisoft/db` tanpa AR terus)?** `00_Ringkasan_Keputusan.md` menyatakan ini "bukan
   drop-in" tetapi tidak mengukur sebenar. Tulis semula `Tblprcobiodata` yang setara (hanya
   lajur yang perlu) di Yii3 dan nilai: berapa baris kod, adakah query-builder/relasi serupa
   cukup rapat untuk "terjemah terus", atau perlu pendekatan lain sepenuhnya.
3. **Berapa banyak kerja wiring DI/bootstrap diperlukan untuk aplikasi paling minimum
   berfungsi?** (routing, config, container PSR-11). Ini kos "overhead asas" yang akan ditanggung
   oleh *setiap* modul Yii3 akan datang, tidak kira sekecil mana — penting untuk anggaran kos
   sebenar migrasi penuh.

## Luar skop POC 1 (sengaja ditangguhkan)

- **Widget Kartik-v / gantian Bootstrap** — sudah diselidik berasingan dalam kerja tema
  (`AI/adminlte4_pilot.md`, `AI/tailwind_flowbite_migration.md`); tidak perlu diulang di sini.
- **Laluan tulis (write path)** — POC 1 baca-sahaja sepenuhnya; tiada risiko kerosakan data.
- **Pangkalan data SQL Server / rangkaian jauh** — lihat sebab di atas (ciri dipilih).
- **Active Directory / Microsoft OAuth / Portal Kinabalu** — lapisan auth penuh, bukan skop
  POC 1 (POC 1 hanya *membaca* sesi sedia ada, tidak mencipta laluan log masuk baharu).
- **Komponen aplikasi tersuai** (`ActiveDirectory`, `MP`, `GRP`, `UAPI`, dll.) — tidak disentuh.

## Output POC setakat 2026-11-13

- Yii3 skeleton berjalan di PHP `8.5.12`; `php yii list` dan route HTTP disahkan berfungsi.
- Route `/session-probe` membaca cookie Yii2 `hrv4` secara manual (`session_name('hrv4')`,
  `session_id(...)`, `session_start(read_and_close)`), dan memaparkan status selamat sahaja:
  cookie hadir/tidak, session boleh dibaca/tidak, `__id`, kewujudan `__authKey`, expiry, dan
  senarai key — **bukan dump penuh session**.
- Route `/biodata-me` guna session reader yang sama, ambil identity `staff-<ICNO>`/`<ICNO>`,
  kemudian baca ringkasan biodata daripada `hronline.tblprcobiodata` dengan join read-only ke
  `hronline.gredjawatan` dan `hronline.department`.
- `yiisoft/db-mysql` telah ditambah ke POC. Sambungan `db2` diwiring secara eksplisit melalui DI
  (`Yiisoft\Db\Connection\ConnectionInterface`) dan membaca konfigurasi Yii2 `config/db2.php`
  tanpa menyalin credential ke repo POC.
- Pengesahan CLI tanpa mendedahkan data peribadi:
  - `DB_OK` untuk `SELECT COUNT(*) FROM hronline.tblprcobiodata`.
  - `BIODATA_OK` untuk reader biodata menggunakan satu identity ujian tempatan.
- Pengesahan HTTP:
  - `http://127.0.0.1:8083/session-probe` → `HTTP 200`.
  - `http://127.0.0.1:8083/biodata-me` → `HTTP 200`.

## Rekod perubahan POC setakat 2026-11-13

Lokasi POC: `C:\Apache24\htdocs\staff-yii3-poc\`. Projek ini mempunyai `.git` sendiri dan
`vendor/` sendiri; ia tidak dimasukkan ke repo Yii2 `staff`.

Fail/folder utama yang ditambah atau diubah dalam POC:

- `composer.json`, `composer.lock` - tambah `yiisoft/db-mysql` supaya Yii3 boleh baca MySQL
  melalui API `yiisoft/db`.
- `config/common/routes.php` - tambah route `GET /session-probe` dan `GET /biodata-me`.
- `config/common/di/application.php` - wire `Yiisoft\Db\Connection\ConnectionInterface` kepada
  factory `db2` POC.
- `src/Shared/Db2ConnectionFactory.php` - bina sambungan Yii3 DB daripada `staff/config/db2.php`
  sedia ada, termasuk arahan `SET SESSION sql_mode` yang sepadan dengan Yii2.
- `src/Shared/Yii2SessionReader.php` - pembaca session Yii2 `hrv4` secara read-only; tidak dump
  keseluruhan session.
- `src/Shared/HronlineBiodataReader.php` - query read-only ke `hronline.tblprcobiodata`,
  `hronline.gredjawatan`, dan `hronline.department`.
- `src/Web/SessionProbe/Action.php` dan `src/Web/SessionProbe/template.php` - halaman probe
  session.
- `src/Web/BiodataMe/Action.php` dan `src/Web/BiodataMe/template.php` - halaman ringkasan
  biodata staf semasa.

Arahan/verifikasi yang sudah dijalankan:

- `git init` dalam `C:\Apache24\htdocs\staff-yii3-poc\`.
- `composer require yiisoft/db-mysql --no-interaction`.
- `php -l` untuk fail PHP POC yang ditambah/diubah.
- `php yii list`.
- `composer check-platform-reqs`.
- `php yii serve 127.0.0.1 --port=8083`.
- HTTP smoke test untuk `/session-probe` dan `/biodata-me`.
- Query DB baca-sahaja: `DB_OK` dan `BIODATA_OK` tanpa memaparkan data peribadi.

## Output akhir yang masih perlu direkod

Bukan "ciri siap", tetapi satu **laporan kos/risiko** menjawab 3 soalan di atas secara konkrit,
untuk membolehkan keputusan termaklum antara 4 pilihan dalam `00_Ringkasan_Keputusan.md` —
khususnya sama ada Pilihan 3 (komited penuh rewrite) berpatutan dari segi kos sebenar yang kini
diukur, bukan anggaran kasar.

Yang masih perlu diuji oleh manusia dalam browser sebenar: log masuk ke aplikasi Yii2 dahulu,
kemudian buka `http://localhost:8083/session-probe` dan `http://localhost:8083/biodata-me`
jika Yii2 juga dilayari melalui `localhost`. Gunakan host yang sama dengan Yii2 kerana browser
cookie adalah host-specific. Jika cookie domain/path Yii2 tidak dihantar ke port POC ini, POC
perlu disajikan di host/path Apache yang sama domain dengan aplikasi Yii2, seperti dinyatakan
dalam bahagian lokasi.

## Usaha sebenar POC asas setakat 2026-11-13

- Skeleton Yii3, dependency composer, route minimum, dan server dev berjaya diwujudkan dalam
  projek berasingan.
- Ciri baca-satu-rekod tidak menggunakan ActiveRecord; ia menggunakan `yiisoft/db-mysql` dan SQL
  eksplisit. Ini lebih kecil untuk POC tetapi bukan terjemahan terus daripada model Yii2.
- Kos sebenar yang sudah kelihatan: setiap modul Yii3 perlu wiring DI/service sendiri, route
  sendiri, pembaca session/auth sendiri, dan query/DTO sendiri. Ini menyokong kesimpulan bahawa
  Yii3 ialah projek semula berperingkat, bukan naik taraf mekanikal.

## Langkah seterusnya

Langkah seterusnya ialah ujian browser sebenar dengan sesi Yii2 aktif. Jika route POC tidak
menerima cookie `hrv4`, sediakan alias/vhost Apache untuk POC pada domain yang sama dan ulang
ujian session. Selepas itu, rekod keputusan akhir: perkongsian sesi berfungsi, separa berfungsi,
atau gagal.
