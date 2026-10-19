# 📘 DOKUMENTASI TEKNIS & OPERASIONAL SISTEM IMPACT WARRIOR

Dokumentasi ini menyajikan referensi teknis mendalam mengenai arsitektur, basis data, algoritma penilaian, integrasi eksternal, dan petunjuk operasional sistem **IMPACT WARRIOR** berbasis Laravel.

---

## DAFTAR ISI
1. [Arsitektur Sistem](#1-arsitektur-sistem)
2. [Spesifikasi Basis Data](#2-spesifikasi-basis-data)
3. [Alur Data & Mekanisme Sinkronisasi](#3-alur-data--mekanisme-sinkronisasi)
4. [Logika Bisnis & Perhitungan Skor](#4-logika-bisnis--perhitungan-skor)
5. [Integrasi Google Apps Script (Webhook)](#5-integrasi-google-apps-script-webhook)
6. [Arsitektur Image Proxy & Caching Google Drive](#6-arsitektur-image-proxy--caching-google-drive)
7. [Referensi API & Rute Sistem](#7-referensi-api--rute-sistem)
8. [Panduan Pemeliharaan & Troubleshooting](#8-panduan-pemeliharaan--troubleshooting)
9. [Deployment & Kontainerisasi](#9-deployment--kontainerisasi)

---

## 1. Arsitektur Sistem

Sistem IMPACT WARRIOR dirancang menggunakan pola **Model-View-Controller (MVC)** di atas framework **Laravel 11**. Sistem ini memadukan kemudahan entri data lapangan melalui form publik tanpa otentikasi rumit dengan performa dashboard lokal yang cepat dan andal.

```
+-----------------------------------------------------------------------------------+
|                               LAPISAN PENGUMPULAN DATA                            |
|  +--------------------+         +--------------------+         +---------------+  |
|  | Karyawan Lapangan  | ======> |  Google Forms      | ======> | Google Sheets |  |
|  +--------------------+         +---------+----------+         +-------+-------+  |
+-------------------------------------------|----------------------------|----------+
                                            | onFormSubmit               | Export CSV
                                            v (Webhook)                  v Stream
+-----------------------------------------------------------------------------------+
|                               BACKEND LARAVEL                                     |
|  +------------------------+      +-----------------------+     +---------------+  |
|  | /api/webhook/google-form| ---> | GoogleSheetSyncService | <---+ Cron/Auto-Sync |  |
|  +------------------------+      +-----------+-----------+     +---------------+  |
|                                              |                                    |
|                                              v RFC-4180 Parsing & Deduplikasi     |
|                                  +-----------+-----------+                        |
|                                  |   SafetyReport Model  |                        |
|                                  | (Hitung Skor & Relasi)|                        |
|                                  +-----------+-----------+                        |
|                                              |                                    |
|                                              v                                    |
|                                  +-----------+-----------+                        |
|                                  | Database (SQLite/SQL) |                        |
|                                  +-----------------------+                        |
|                                              ^                                    |
|                                              | Agregasi & Sorting                 |
|                                  +-----------+-----------+                        |
|                                  | SafetyWarriorController|                       |
|                                  +-----------+-----------+                        |
|                                              |                                    |
+----------------------------------------------|------------------------------------+
                                               v
+-----------------------------------------------------------------------------------+
|                             ANTARMUKA PENGGUNA (UI/UX)                            |
|   +-----------------------+   +----------------------+   +---------------------+  |
|   | Dashboard & Klasemen  |   | Galeri Bukti Temuan  |   |  Laporan Cetak PDF  |  |
|   +-----------------------+   +----------------------+   +---------------------+  |
+-----------------------------------------------------------------------------------+
```

---

## 2. Spesifikasi Basis Data

Sistem menggunakan dua tabel utama: `safety_reports` untuk menyimpan transaksi laporan dan `app_settings` untuk konfigurasi dinamis.

### A. Tabel `safety_reports`
Menyimpan seluruh baris laporan temuan atau aktivitas K3 & CSR.

| Nama Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Auto-increment identifier. |
| `sheet_row_id` | `VARCHAR(32)` | MD5 hash kombinasi (tanggal + pelapor + deskripsi) untuk identifikasi unik dan deduplikasi. |
| `reported_at` | `DATETIME` | Waktu laporan diinput pada Google Form. Diindeks untuk filtering cepat. |
| `nrp` | `VARCHAR(50)` | Nomor Registrasi Pokok karyawan (identitas primer klasemen). |
| `reporter_name` | `VARCHAR(255)` | Nama lengkap karyawan pelapor. |
| `department` | `VARCHAR(100)` | Departemen kerja (Service, Produksi, Maintenance, dll). |
| `activity_indicator` | `VARCHAR(255)` | Label teks indikator kegiatan sebagaimana tercatat di respons Google Form. |
| `indicator_category`| `VARCHAR(50)` | Kategori indikator (`EHS` atau `CSR`). |
| `has_unsafe_condition`| `BOOLEAN` | True jika temuan memuat Kondisi Tidak Aman (+1 poin). |
| `has_unsafe_act` | `BOOLEAN` | True jika temuan memuat Tindakan Tidak Aman / Near Miss (+2 poin). |
| `has_suggestion` | `BOOLEAN` | True jika memberikan usulan/rekomendasi perbaikan (+3 poin). |
| `has_action` | `BOOLEAN` | True jika terdapat aksi perbaikan langsung (+3 poin). |
| `has_direct_action`| `BOOLEAN` | Penanganan langsung di tempat (+3 poin). |
| `has_ehs_activity` | `BOOLEAN` | True jika berpartisipasi dalam agenda EHS (+2 poin). |
| `has_csr_activity` | `BOOLEAN` | True jika berpartisipasi dalam aksi CSR lingkungan (+2 poin). |
| `has_speaker_activity`| `BOOLEAN` | True jika bertindak sebagai pemateri/narasumber EHS/CSR (+4 poin). |
| `points` | `INTEGER` | Total akumulasi skor dari baris laporan ini. |
| `description` | `TEXT` | Uraian temuan atau penjelasan kegiatan yang dilaporkan. |
| `photo_url` | `TEXT` | URL tautan Google Drive berkas foto bukti. |
| `location` | `VARCHAR(255)` | Lokasi spesifik kejadian / temuan. |
| `raw_payload` | `JSON` | Payload mentah baris spreadsheet untuk audit trail. |
| `created_at` | `TIMESTAMP` | Timestamp pembuatan record lokal. |
| `updated_at` | `TIMESTAMP` | Timestamp modifikasi record lokal. |

### B. Tabel `app_settings`
Struktur *Key-Value Store* sederhana untuk mengelola konfigurasi aplikasi tanpa harus mengubah berkas `.env` di lingkungan server.

| Nama Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT (PK)` | Auto-increment identifier. |
| `key` | `VARCHAR(255)` | Kunci konfigurasi (unik), contoh: `google_sheet_id`, `allowed_users`, `last_sync_at`. |
| `value` | `TEXT` | Nilai konfigurasi. |
| `created_at` / `updated_at` | `TIMESTAMP` | Waktu modifikasi konfigurasi. |

---

## 3. Alur Data & Mekanisme Sinkronisasi

### A. Endpoint Ekspor CSV Google Sheets
Aplikasi mengakses data Google Sheets secara langsung melalui tautan export publik:
```
https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/export?format=csv
```
Pendekatan ini memiliki sejumlah keunggulan utama:
1. **Zero-Credentials**: Tidak membutuhkan *Service Account JSON* atau OAuth token Google Cloud yang rentan kedaluwarsa.
2. **Kinerja Tinggi**: Mengunduh payload terkompresi berukuran sangat kecil dalam format CSV murni.
3. **Resiliensi**: Jika koneksi terputus, database lokal tetap menyajikan data sebelumnya tanpa mengalami *downtime*.

### B. Pemrosesan CSV yang Patuh RFC-4180
Seringkali deskripsi temuan karyawan memuat baris baru (*line-break / enter*). Apabila CSV dibaca menggunakan pembacaan per baris standar (`explode("\n")`), teks multi-baris akan terpotong dan menghasilkan rekaman korup.

Service `GoogleSheetSyncService` mengatasi hal ini dengan memanfaatkan memori stream PHP:
```php
$stream = fopen('php://temp', 'r+');
fwrite($stream, $csvString);
rewind($stream);

while (($cols = fgetcsv($stream)) !== false) {
    // Membaca baris secara akurat mematuhi aturan kutip ganda RFC-4180
}
```

### C. Sinkronisasi Dua Arah (Database Mirroring & Self-Cleaning)
Setiap siklus sinkronisasi:
1. Memetakan baris Google Sheet ke model basis data.
2. Menghasilkan ID unik `sheet_row_id` berbasis `MD5(reported_at + reporter_name + description)`.
3. Menjalankan operasi `SafetyReport::updateOrCreate()`.
4. **Mirroring Cleanup**: Mengumpulkan semua `sheet_row_id` yang masih ada di Google Sheet, kemudian menjalankan query pembersihan otomatis:
   ```php
   SafetyReport::whereNotNull('sheet_row_id')
       ->whereNotIn('sheet_row_id', $activeSheetRowIds)
       ->delete();
   ```
   Hal ini menjamin bahwa jika administrator menghapus baris laporan yang tidak valid di Google Sheet, database lokal akan otomatis terhapus dan skor klasemen langsung menyesuaikan.

---

## 4. Logika Bisnis & Perhitungan Skor

### A. Indikator dan Aturan Poin (Skor Baku IMPACT WARRIOR)

Metrik pembobotan ditetapkan untuk mendorong pelaporan proaktif (*Leading Indicators*) daripada sekadar reaktif (*Lagging Indicators*):

| No | Indikator Kegiatan / Temuan | Bobot Poin | Kategori | Penjelasan Operasional |
| :---: | :--- | :---: | :---: | :--- |
| 1 | **Kondisi Tidak Aman (Unsafe Condition)** | `+1` | EHS | Mengidentifikasi lingkungan fisik atau perkakas kerja yang berpotensi menimbulkan bahaya. |
| 2 | **Tindakan / Pelaporan Near Miss (Unsafe Act)** | `+2` | EHS | Melaporkan perilaku kerja yang berisiko atau insiden nyaris celaka sebelum berujung kecelakaan fatal. |
| 3 | **Rekomendasi / Usulan Perbaikan (Saran)** | `+3` | EHS | Memberikan masukan konstruktif atas tata kelola atau rekayasa pengendalian risiko. |
| 4 | **Penanganan Langsung (Direct Action)** | `+3` | EHS | Mengambil inisiatif langsung untuk mengamankan atau merapikan bahaya saat itu juga di lapangan. |
| 5 | **Mengikuti Kegiatan EHS** | `+2` | EHS | Kehadiran aktif dalam kampanye K3, pelatihan K3, simulasi tanggap darurat, atau briefing keselamatan. |
| 6 | **Mengikuti Kegiatan CSR** | `+2` | CSR | Partisipasi dalam agenda pelestarian lingkungan atau kegiatan sosial kemasyarakatan. |
| 7 | **Menjadi Narasumber / Pemateri EHS & CSR** | `+4` | EHS / CSR | Menjadi pembicara dalam Tool Box Meeting, safety talk, atau instruktur pelatihan internal. |

*Catatan: Apabila satu baris laporan mencakup tindakan tidak aman sekaligus saran perbaikan dan penanganan langsung, seluruh poin terkait akan diakumulasikan secara kumulatif.*

### B. Aturan Siklus Bulanan Berkelanjutan (Tanggal 25 s.d. 24 Tanpa Jeda/Cut-off)
Klasemen bulanan dihitung secara berkelanjutan untuk memastikan setiap hari kalender tercatat tanpa adanya jeda cut-off:
- **Tanggal Mulai**: Tanggal 25 bulan sebelumnya pukul 00:00:00 (WITA).
- **Tanggal Selesai**: Tanggal 24 bulan berjalan pukul 23:59:59 (WITA).
- **Kesinambungan Periode**: Begitu memasuki tanggal 25 pukul 00:00:00 WITA, sistem secara otomatis memulai siklus penilaian untuk bulan berikutnya, sehingga seluruh laporan karyawan tetap berkontribusi langsung pada klasemen tanpa ada hari yang terabaikan.

### C. Normalisasi Identitas (NRP vs Nama)
Untuk mencegah fragmentasi skor akibat perbedaan penulisan nama karyawan (misal: "Rizki A.", "Rizki Pratama", "RIZKI PRATAMA"), sistem memprioritaskan kolom **NRP (Nomor Registrasi Pokok)** sebagai kunci pengelompokan (*Group By Key*):
```php
SafetyReport::select(
    DB::raw('COALESCE(nrp, reporter_name) as employee_key'),
    DB::raw('MAX(nrp) as nrp'),
    DB::raw('MAX(reporter_name) as reporter_name'),
    DB::raw('MAX(department) as department'),
    DB::raw('SUM(points) as total_points'),
    DB::raw('COUNT(*) as total_reports'),
    ...
)->whereBetween('reported_at', [$startDate, $endDate])
 ->groupBy(DB::raw('COALESCE(nrp, reporter_name)'));
```

---

## 5. Integrasi Google Apps Script (Webhook)

Untuk memfasilitasi sinkronisasi seketika (*real-time update*) sesaat setelah karyawan mengirim form, gunakan skrip berikut pada editor **Extensions > Apps Script** di Google Sheets penampung respons form:

```javascript
/**
 * Google Apps Script - Real-Time Webhook Trigger
 * Pasang pemicu (trigger) pada fungsi 'onFormSubmit' dengan event: 'On form submit'
 */
function onFormSubmit(e) {
  var webhookUrl = "https://domain-anda.com/api/webhook/google-form";
  
  var payload = {
    event: "form_response_submitted",
    timestamp: new Date().toISOString(),
    auth_token: "SAFETY_WARRIOR_SECRET_TOKEN" // Opsional jika diproteksi
  };

  var options = {
    method: "post",
    contentType: "application/json",
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };

  try {
    var response = UrlFetchApp.fetch(webhookUrl, options);
    Logger.log("Webhook triggered. Status code: " + response.getResponseCode());
  } catch (err) {
    Logger.log("Error triggering webhook: " + err.toString());
  }
}
```

### Prosedur Pemasangan Trigger:
1. Di Google Sheets, klik **Extensions > Apps Script**.
2. Tempel kode di atas dan ubah URL target sesuai domain server dashboard Anda.
3. Klik ikon jam di bilah kiri (**Triggers / Pemicu**).
4. Klik **Add Trigger**, pilih fungsi `onFormSubmit`, *Event Source*: **From spreadsheet**, *Event Type*: **On form submit**.
5. Simpan dan setujui izin otorisasi skrip.

---

## 6. Arsitektur Image Proxy & Caching Google Drive

File foto yang diunggah karyawan melalui Google Form tersimpan di Google Drive dalam format tautan:
`https://drive.google.com/open?id=1AbCdEfGhIjKlMnOp...`

### Kendala Teknis:
1. Tautan langsung Google Drive sering diblokir oleh kebijakan *Cross-Origin Resource Sharing (CORS)* browser jika disematkan pada tag `<img>`.
2. Pengambilan berulang-ulang ke server Google Drive berisiko terkena *HTTP 429 Too Many Requests (Rate Limiting)*.
3. Folder Drive yang belum dipublikasikan akan menghasilkan gambar rusak (*broken image*).

### Solusi Controller `PhotoProxyController`:
1. **Thumbnail Resolver**: Mengonversi ID berkas menjadi URL thumbnail resmi Google:
   `https://drive.google.com/thumbnail?id={FILE_ID}&sz=w1000`
2. **Local Disk Caching**: Foto yang pertama kali diambil disimpan di path lokal:
   `storage/app/public/drive_cache/{FILE_ID}.jpg`
   Permintaan berikutnya disajikan langsung dari disk lokal dengan *HTTP Header Cache-Control: max-age=86400* (24 jam), menghasilkan waktu respons di bawah 10ms.
3. **Graceful SVG Fallback**: Jika foto tidak dapat diakses (folder Drive masih berstatus privat), sistem mengembalikan grafik SVG peringatan industrial yang rapi dan memandu pengguna untuk mengubah hak akses folder menjadi "Siapa saja yang memiliki link".

---

## 7. Referensi API & Rute Sistem

| Rute / URL | Metode | Controller & Method | Deskripsi |
| :--- | :---: | :--- | :--- |
| `/` | `GET` | `SafetyWarriorController@index` | Menampilkan dashboard utama (podium, klasemen, statistik, galeri foto). |
| `/sync` | `POST` | `SafetyWarriorController@sync` | Memicu sinkronisasi manual data spreadsheet via Ajax/Form. |
| `/settings` | `GET` | `SafetyWarriorController@settings` | Halaman konfigurasi ID spreadsheet, pengujian koneksi, dan whitelist nama. |
| `/settings` | `POST` | `SafetyWarriorController@updateSettings` | Menyimpan perubahan konfigurasi sistem. |
| `/print-pdf` | `GET` | `SafetyWarriorController@printPdf` | Menyajikan halaman cetak bersih (*clean printable layout*) untuk cetak PDF/Fisik. |
| `/drive-image/{id}` | `GET` | `PhotoProxyController@show` | Proxy resolver gambar thumbnail Google Drive ber-cache lokal. |
| `/api/auto-sync` | `GET` | `SafetyWarriorController@sync` | Endpoint pemicu sinkronisasi terjadwal (cron job / uptime monitor). |
| `/api/webhook/google-form` | `POST` | `SafetyWarriorController@webhook` | Menerima notifikasi instan dari Google Apps Script saat form disubmit. |

---

## 8. Panduan Pemeliharaan & Troubleshooting

### A. Indikator Status Merah / Kuning pada Menu Pengaturan
- **Status Merah (Akses Dibatasi / 401)**:
  - *Penyebab*: Google Sheet disetel privat (hanya pengguna tertentu).
  - *Solusi*: Buka file Google Sheet > klik **Bagikan** > ubah *Akses umum* menjadi **"Siapa saja yang memiliki link"** sebagai **Pelihat**.
- **Status Kuning (Data Kosong)**:
  - *Penyebab*: ID Sheet benar, namun sheet respons baru dibuat dan belum ada karyawan yang mengisi form selain baris judul kolom.

### B. Foto Bukti di Galeri Menampilkan Icon Kunci Gembok
- *Penyebab*: Berkas foto yang diunggah ke Google Drive berada di dalam folder yang hak aksesnya masih dibatasi.
- *Solusi*: Buka Google Drive dari akun pembuat form, klik kanan folder respons foto, pilih **Bagikan**, ubah akses menjadi **"Siapa saja yang memiliki link"**.

### C. Mencetak Laporan Bulanan ke PDF
- Buka menu **CETAK / PDF** dari dashboard.
- Tekan `Ctrl + P` (atau `Cmd + P` di Mac), pilih opsi tujuan pencetak **"Save as PDF / Simpan sebagai PDF"**.
- Lembar dokumen otomatis terformat rapi sesuai ukuran kertas A4 dengan tabel zebra dan ringkasan akumulasi seluruh karyawan.

---

## 9. Deployment & Kontainerisasi

Aplikasi siap dideploy ke berbagai platform hosting cloud modern.

### Menggunakan Docker:
```bash
# Build image docker
docker build -t impact-warrior:latest .

# Jalankan kontainer
docker run -d -p 8080:8080 \
  -e APP_KEY=base64:... \
  -e APP_ENV=production \
  -v impact_data:/var/www/html/database \
  impact-warrior:latest
```

### Konfigurasi Cron Job di Server (crontab):
Tambahkan entri berikut agar sistem melakukan sinkronisasi otomatis setiap 15 menit sekali:
```cron
*/15 * * * * cd /path/to/SafetyWarrior && php artisan schedule:run >> /dev/null 2>&1
```
Atau memanggil endpoint web secara langsung:
```cron
*/15 * * * * curl -s https://domain-anda.com/api/auto-sync > /dev/null
```
