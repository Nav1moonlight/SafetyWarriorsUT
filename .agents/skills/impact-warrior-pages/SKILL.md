---
name: impact-warrior-pages
description: >-
  Pengetahuan mendalam tentang setiap halaman (view/page) aplikasi Impact Warrior (Safety Warrior Laravel).
  Gunakan skill ini ketika user meminta penjelasan detail setiap halaman web, mengisi konten slide
  presentasi berdasarkan fitur project, membuat dokumentasi UI/UX, atau menjelaskan komponen dan
  fungsi tiap seksi halaman berdasarkan kode project ini.
---

# Dokumentasi Halaman-Halaman Aplikasi Impact Warrior

Aplikasi ini adalah sistem monitoring K3, EHS, dan CSR berbasis gamifikasi yang dibangun dengan Laravel.
Semua halaman menggunakan tema Industrial (Dark & Light Mode) dengan font Barlow Condensed, IBM Plex Mono, dan IBM Plex Sans.

---

## Daftar Halaman

| Halaman | Route | File View |
|---|---|---|
| Dashboard / Klasemen | `/` | `resources/views/dashboard.blade.php` |
| Pengaturan & Sinkronisasi | `/settings` | `resources/views/settings.blade.php` |
| Cetak / Print PDF | `/print-pdf` | `resources/views/print_pdf.blade.php` |
| Layout Utama (Global) | *(induk semua halaman)* | `resources/views/layouts/app.blade.php` |

---

## 1. Halaman Dashboard — Klasemen IMPACT WARRIOR (`/`)

**Fungsi utama**: Halaman utama yang menampilkan leaderboard/klasemen karyawan berdasarkan akumulasi poin K3 & CSR secara real-time pada periode bulanan yang dipilih.

### A. Header Periode & Controls
- Menampilkan **status periode**: badge `PERIODE AKTIF` (hijau) atau `ARSIP PERIODE` (abu-abu)
- Rentang tanggal siklus bulanan: tanggal **25 bulan sebelumnya s.d. 24 bulan berjalan** (berkesinambungan tanpa jeda)
- Judul besar: `KLASEMEN [BULAN] [TAHUN]`
- Dropdown filter **Bulan & Tahun** (auto-submit saat berubah)
- Tombol **CETAK / PDF** → menuju halaman `print_pdf` dengan parameter filter yang sama

### B. Strip KPI (8 Metrik Horizontal)
Menampilkan angka agregat periode yang dipilih dengan animasi count-up saat halaman dimuat:

| KPI | Warna | Bobot Poin |
|---|---|---|
| Total Poin | Kuning (aksen) | Akumulasi semua poin |
| Total Laporan | Putih | Jumlah total temuan/kegiatan |
| Kondisi Tidak Aman | Oranye | +1 poin |
| Tindakan / Near Miss | Merah | +2 poin |
| Saran / Rekomendasi | Kuning Muda | +3 poin |
| Action / Penanganan Langsung | Hijau (#2ECC71) | +3 poin |
| Pemateri / Narasumber Edukasi | Ungu (#9333EA) | +4 poin |
| Kegiatan EHS & CSR | Teal | +2 poin |

### C. Podium Top 3 — Hero Section
- Hanya tampil saat sorting berdasarkan skor (bukan abjad)
- **Juara 1**: kartu dengan border kuning/emas (2px), ukuran teks lebih besar, NRP badge kuning
- **Juara 2**: border abu zinc di bagian atas
- **Juara 3**: border coklat (#B45309) di bagian atas
- Tiap kartu menampilkan: Nama, NRP, Departemen, Rincian indikator (dot warna sesuai jenis), Total Poin (animasi countup)

### D. Tabel Klasemen Lengkap
- **Kolom**: RANK | NRP | KARYAWAN & DEPARTEMEN | RINCIAN INDIKATOR TEMUAN/KEGIATAN | LAPORAN | TOTAL POIN
- Filter departemen (dropdown) & sorting (Skor Tertinggi / Abjad A-Z / Abjad Z-A)
- Setiap baris **dapat diklik** → membuka modal riwayat laporan lengkap karyawan tersebut (Alpine.js)
- Progress bar transparan di belakang setiap baris (proporsional terhadap poin Juara 1)
- NRP ditampilkan sebagai badge dengan aksen warna
- Indikator per-jenis ditampilkan sebagai pill warna kecil dengan tooltip

### E. Galeri Dokumentasi Temuan & Kegiatan
- Grid foto responsif (1-3 kolom) dari laporan yang memiliki lampiran foto Google Drive
- Tiap kartu galeri:
  - **Thumbnail foto** dimuat via proxy lokal `/drive-image/{id}` (disk caching, fallback SVG jika privat)
  - Overlay label kategori di pojok kiri atas: EHS/CSR + jenis (KONDISI / TINDAKAN / SARAN / ACTION / PEMATERI / KEGIATAN)
  - Badge poin di pojok kanan atas (`+X POIN`)
  - Nama pelapor, NRP, departemen, deskripsi (clamp 2 baris), timestamp WITA
- Klik kartu → membuka **modal lightbox** dengan foto full + detail lengkap
- Filter urutan galeri: Terbaru / Nama A-Z / Departemen A-Z

### F. Statistik Distribusi Poin Per Departemen
- Daftar seluruh departemen aktif pada periode yang dipilih
- Horizontal bar chart sederhana: persentase kontribusi poin tiap departemen terhadap total keseluruhan
- Format: `NAMA DEPT (X TEMUAN/KEGIATAN) — Y POIN (Z%)`

---

## 2. Halaman Settings — Sumber Data & Sinkronisasi (`/settings`)

**Fungsi utama**: Konfigurasi koneksi ke Google Sheets dan panduan operasional mandiri untuk admin/klien.

### A. Banner Status Koneksi Sheet (3 Kondisi)
- 🟢 **HIJAU** — `TERHUBUNG (DATA TERSEDIA)`: Sheet berhasil dibaca dan ada data laporan
- 🟡 **KUNING** — `TERHUBUNG (DATA BELUM TERSEDIA)`: Akses berhasil tapi sheet masih kosong/hanya header
- 🔴 **MERAH** — `GAGAL TERHUBUNG`: ID salah atau akses dibatasi (belum diset ke publik)
- Menampilkan: Sheet ID aktif saat ini + tanggal & jam terakhir sinkronisasi berhasil

### B. Form Konfigurasi ID Spreadsheet
- Input teks: menerima **Sheet ID** (kode acak antara `/d/` dan `/edit` di URL) atau **URL penuh** spreadsheet (sistem ekstrak ID otomatis)
- Tautan "BUKA SHEET ↗" untuk membuka spreadsheet aktif langsung di tab baru
- Dua tombol aksi:
  - **SIMPAN & SINKRONKAN SEKARANG** → Menyimpan ID + langsung trigger sinkronisasi data ke database
  - **SIMPAN ID SAJA** → Hanya menyimpan ID tanpa sinkronisasi

### C. Panduan Koneksi & Maintenance (3 Step Card)
Dirancang agar klien dapat melakukan setup/maintenance secara mandiri:
1. **IZIN AKSES SHEET**: Bagikan Google Spreadsheet ke "Siapa saja yang memiliki link" sebagai Pelihat (Viewer)
2. **IZIN FOTO BUKTI (DRIVE)**: Bagikan folder Google Drive tempat foto form diupload ke akses publik Pelihat agar gambar bisa tampil di galeri
3. **FORMAT GFORM OTOMATIS**: Daftar kolom yang dideteksi otomatis: Timestamp, NRP, Nama Karyawan, Departemen, Jenis Temuan

### D. Troubleshooting Cepat
- *"Data di web belum muncul setelah submit?"* → Klik tombol SINKRON di navbar, atau periksa Sheet ID
- *"Baris di sheet sudah dihapus tapi masih ada di web?"* → Klik "Simpan & Sinkronkan Sekarang" untuk self-cleaning otomatis

---

## 3. Halaman Cetak / Print PDF (`/print-pdf`)

**Fungsi utama**: Laporan resmi format cetak hitam-putih, siap dicetak ke printer fisik atau disimpan sebagai PDF via fungsi cetak browser. **Tidak menggunakan `layouts/app.blade.php`** (standalone full HTML).

### Konten Halaman Cetak
- Header institusi: logo, judul "LAPORAN AKUMULASI KLASEMEN IMPACT WARRIOR — [BULAN] [TAHUN]"
- Rentang tanggal periode siklus yang transparan
- Filter departemen (jika ada filter aktif, ditampilkan di header)
- **Tabel Klasemen Lengkap** dengan kolom:
  - No | NRP | Nama Karyawan | Departemen | Kondisi | Tindakan | Saran | Action | Pemateri | EHS | CSR | Total Laporan | **Total Poin**
- Footer: keterangan sistem, timestamp cetak
- Tombol "Cetak / Print" (disembunyikan saat halaman benar-benar dicetak via `@media print`)

### Spesifikasi Teknis Print
- Latar putih/krem (`#F3F1EB`), aksen kuning (`#D49B00`) untuk header
- `print-color-adjust: exact` agar warna dan border tetap muncul di hasil cetak
- Elemen `.no-print` otomatis disembunyikan saat print
- Font: Barlow Condensed (heading), IBM Plex Mono (data tabel), IBM Plex Sans (teks umum)
- Hazard stripe dekoratif (diagonal kuning-hitam) di header sebagai elemen industrial branding

---

## 4. Layout Utama — `layouts/app.blade.php`

**Fungsi**: Template induk semua halaman selain `print_pdf`. Menyediakan struktur global, navbar, Dark/Light Mode, dan state Alpine.js bersama.

### Komponen Global

#### Navbar Industrial
- Logo & nama aplikasi "IMPACT WARRIOR"
- Navigasi: **Dashboard** (`/`) dan **Settings** (`/settings`)
- Tombol **SINKRON** (ikon refresh):
  - Trigger sinkronisasi Google Sheet secara on-demand via Ajax (`/sync`) tanpa reload halaman
  - Menampilkan spinner saat proses berlangsung
  - Muncul notifikasi singkat setelah selesai (berhasil/gagal)

#### Dark / Light Mode Toggle
- Menggunakan **CSS custom properties** (`--bg`, `--surface`, `--border`, `--text-primary`, `--text-secondary`, `--accent`) sehingga seluruh tema dapat berubah dengan satu toggle
- Preferensi disimpan di `localStorage`

#### Alpine.js State Management (Global)
State yang dikelola di level layout dan digunakan lintas halaman:
- `employeeModal` (bool) + `activeEmployee` (object) → Modal riwayat laporan karyawan
- `photoModal` (bool) + `activePhoto`, `activeCaption`, `activeReporter`, `activeNrp`, `activeDept`, `activeDate`, `activePoints`, `activeIndicator`, `activeCategory` → Modal lightbox foto

#### Modal Riwayat Karyawan
- Muncul saat baris di tabel klasemen diklik
- Menampilkan seluruh riwayat laporan karyawan tersebut: tanggal, jenis temuan, poin, deskripsi, foto thumbnail
- Judul: nama + NRP + departemen + total poin

#### Modal Lightbox Foto
- Muncul saat kartu di galeri diklik
- Full-preview foto dengan semua metadata: nama pelapor, NRP, departemen, jenis indikator, kategori, poin, deskripsi, timestamp

---

## Sistem Sinkronisasi & Auto-Scheduler

### Mekanisme Sinkronisasi
- **Otomatis saat halaman dibuka**: dipicu jika data di database berumur >3 menit
- **On-demand manual**: tombol SINKRON di navbar (Ajax, tanpa reload)
- **Background scheduler**: `php artisan schedule:work` menjalankan `safety:sync-sheet` setiap 5 menit
- **Webhook Google Apps Script**: endpoint `/api/webhook/google-form` menerima trigger langsung saat form baru disubmit

### GoogleSheetSyncService
- Membaca CSV publik dari Google Sheets via HTTP
- **RFC-4180 Compliant CSV Stream Parser**: menangani deskripsi multi-baris (enter di dalam cell) tanpa memotong data
- **Database Mirroring**: baris yang dihapus dari sheet otomatis dihapus dari database lokal (self-cleaning)
- **NRP sebagai Primary Key Identitas**: mencegah poin terpecah karena typo nama atau mutasi departemen

### Photo Proxy & Caching (`PhotoProxyController`)
- Route `/drive-image/{id}` mengambil gambar dari Google Drive
- Gambar di-cache di disk lokal (`storage/app/public/`) untuk loading instan dan hemat bandwidth
- Fallback SVG elegan jika file Google Drive masih bersifat privat

---

## Panduan Mengisi Konten Slide Presentasi

Saat diminta mengisi/menulis konten slide presentasi berdasarkan project ini:

1. **Identifikasi slide** yang sedang dibahas (nama/judul slide)
2. **Cocokkan** dengan halaman atau fitur yang relevan dari dokumentasi di atas
3. **Gunakan bahasa non-teknis** untuk slide yang ditujukan untuk manajemen/klien, atau **bahasa teknis** untuk slide developer
4. **Sertakan poin-poin kunci**: tujuan fitur, cara kerja singkat, manfaat nyata bagi user
5. **Cantumkan angka/detail spesifik** jika ada (contoh: "7 jenis indikator", "sinkronisasi otomatis setiap 5 menit", "NRP sebagai primary key")
