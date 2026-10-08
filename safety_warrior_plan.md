# Rencana Implementasi: Dashboard "Safety Warrior" Berbasis Laravel (Integrasi Google Form & Google Sheets)

## Deskripsi & Alur Sistem

Sistem ini dirancang untuk menampilkan Dashboard dan Leaderboard pemantauan K3 secara otomatis dari respons **Google Form** yang tersimpan di **Google Sheets**.

```mermaid
flowchart LR
    A["Karyawan Lapangan"] -->|Mengisi Form & Foto| B["Google Form (GForm)"]
    B -->|Tersimpan Otomatis| C["Google Sheets (Spreadsheet)"]
    C -->|Sync Data (CSV / Webhook / API)| D["Aplikasi Laravel (Safety Warrior)"]
    D --> E["🏆 Leaderboard Bulanan (Cutoff tgl 1 - 25)"]
    D --> F["🥇 Trophy Spotlight (Safety Warrior of the Month)"]
    D --> G["📸 Galeri Foto Bukti Temuan (Google Drive/Upload)"]
    D --> H["📊 Statistik Departemen & Rekap Poin"]
```

---

## Spesifikasi Teknis & Integrasi Google Sheets

### 1. Struktur Kolom Google Sheets (Response Form)
Dashboard Laravel akan memetakan kolom dari Google Sheets:
- `Timestamp` / Tanggal Laporan
- `Nama Karyawan`
- `Departemen` (Service, Produksi, Maintenance, HSE, Logistik, dll.)
- `Jenis Temuan / Aktivitas` (Checkbox / Multi-select di GForm):
  - **Temuan Tindakan Tidak Aman** = `+5 poin`
  - **Temuan Kondisi Tidak Aman** = `+3 poin`
  - **Memberikan Masukan Pengendalian/Perbaikan** = `+3 poin`
  - **Action Perbaikan** = `+4 poin`
- `Keterangan / Penjelasan Temuan`
- `Foto Bukti Temuan` (Link Google Drive dari GForm upload file)

### 2. Mekanisme Sinkronisasi Google Sheet ke Laravel
- **Metode Sinkronisasi**: Menggunakan **Google Sheets "Publish to Web (CSV)"** atau **Export URL** yang sangat mudah disetup tanpa perlu API credentials rumit.
- **Tombol Sync Instan**: Tersedia tombol *"🔄 Tarik Data Terbaru"* di dashboard untuk memperbarui data kapan saja secara instan.
- **Cache & Storage**: Data disinkronkan ke SQLite / database lokal Laravel agar performa dashboard sangat cepat dan tetap bisa dibuka offline/tanpa lag.
- **Google Drive Image Converter**: Helper otomatis untuk mengonversi link Google Drive (`https://drive.google.com/open?id=...` atau `https://drive.google.com/file/d/.../view`) menjadi direct image preview yang bisa langsung tampil di galeri foto dashboard.

---

## Komponen & Fitur Dashboard Laravel

### 1. Leaderboard & Trophy Spotlight ("Safety Warrior of the Month")
- **Peringkat #1 Spotlight**: Menampilkan piala bergilir virtual, foto/avatar, nama pemenang, departemen, dan total poin bulan ini.
- **Tabel Klasemen (Top Ranks)**:
  - Urutan karyawan berdasarkan akumulasi poin dalam rentang tanggal **1 s.d. 25** bulan berjalan.
  - Rincian jumlah temuan Tindakan Tidak Aman (x5), Kondisi Tidak Aman (x3), Masukan (x3), Action (x4).
  - Badge prestasi dan status tren posisi.

### 2. Filter & Selector Periode (Bulan & Tahun)
- Selector bulan (misal: November 2026, Oktober 2026).
- Otomatis menghitung transaksi yang masuk di antara **tanggal 1 jam 00:00 s.d. tanggal 25 jam 23:59** untuk penentuan pemenang bulan tersebut.
- Arsip riwayat pemenang bulan-bulan sebelumnya ("Hall of Fame").

### 3. Galeri Foto Temuan & Activity Feed
- Card interaktif menampilkan foto temuan K3, nama pelapor, departemen, jenis temuan, dan keterangan.
- Popup modal preview gambar zoom untuk inspeksi detail foto temuan.

### 4. Statistik & Analitik K3
- **Distribusi Temuan per Departemen**: Departemen mana yang paling aktif dan departemen mana yang banyak temuan.
- **Rasio Temuan**: Persentase Unsafe Act vs Unsafe Condition vs Action Perbaikan.

### 5. Halaman Konfigurasi / Pengaturan Google Sheet
- Input URL Google Sheet langsung dari halaman web dashboard atau `.env`.
- Dokumentasi panduan cara menghubungkan Google Form ke Google Sheet.
- Mock / Sample data bawaan (Rizki Dept Service, Adi, Didi, dll.) yang langsung siap dicoba.

---

## Rencana Tahapan Eksekusi

1. **Setup Project Laravel**:
   - Buat project Laravel di workspace (`SafetyWarrior`).
   - Konfigurasi Tailwind CSS / modern UI template (Clean Dark/Light Modern Industrial Safety theme).
2. **Implementasi Service Google Sheets Sync**:
   - Buat `GoogleSheetSyncService` di Laravel untuk membaca CSV/Sheet, parsing data, kalkulasi poin (+5, +3, +3, +4), dan filter tanggal 1-25.
   - Buat helper konversi link foto Google Drive.
3. **Pembangunan Controller & Views (Blade + Tailwind + Alpine.js/Chart.js)**:
   - Dashboard View: Podium Juara, Leaderboard Table, Galeri Foto, Grafik Statistik.
   - Settings / Sync View: Input URL Google Sheet & status koneksi.
4. **Verifikasi & Testing**:
   - Uji coba sinkronisasi sheet dengan berbagai format input.
   - Validasi logika cut-off tanggal 25 & kalkulasi total skor.
   - Pastikan visualisasi foto dan dashboard berjalan mulus di mobile & desktop.
