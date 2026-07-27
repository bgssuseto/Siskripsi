# Product Requirements Document (PRD)
# Perbaikan Sistem Informasi Seminar Proposal & Skripsi
Versi: 1.0  
Status: Revision Request  
Platform: Web (Laravel)  
Tanggal: Juli 2026

---

# Latar Belakang

Hasil evaluasi terhadap implementasi sistem menunjukkan masih terdapat beberapa masalah pada konsistensi tampilan (Light/Dark Mode), proses pendaftaran Seminar Proposal dan Skripsi, serta alur verifikasi oleh Super Admin dan Koordinator. Selain itu diperlukan peningkatan keamanan registrasi akun melalui verifikasi email institusi.

Dokumen ini menjadi acuan pengembangan lanjutan agar sistem lebih konsisten, mudah digunakan, dan sesuai dengan proses bisnis Program Studi Teknik Informatika.

---

# Tujuan

- Menyempurnakan tampilan UI pada seluruh role.
- Menyesuaikan seluruh komponen agar mendukung Dark Mode dan Light Mode secara konsisten.
- Menyempurnakan proses pendaftaran Sempro dan Skripsi.
- Menambahkan proses verifikasi pendaftaran oleh Super Admin dan Koordinator.
- Meningkatkan keamanan registrasi akun mahasiswa melalui verifikasi email.

---

# Role yang Terlibat

- Mahasiswa
- Dosen
- Koordinator
- Super Admin

---

# Functional Requirements

# A. Role Dosen

## A.1 Highlight Hari Ini pada Kalender

### Deskripsi

Kalender belum memberikan indikator visual terhadap tanggal hari ini sehingga pengguna kesulitan mengetahui posisi tanggal saat membuka kalender.

### Requirement

- Tambahkan highlight khusus pada tanggal hari ini.
- Warna highlight mengikuti tema sistem.
- Pada Light Mode menggunakan warna kuning/oranye lembut.
- Pada Dark Mode menggunakan warna yang tetap kontras.
- Berlaku pada seluruh role yang menggunakan komponen kalender.

### Acceptance Criteria

- Hari ini otomatis diberi highlight.
- Highlight berubah otomatis setiap pergantian hari.
- Berlaku pada seluruh kalender di semua role.

---

## A.2 Konsistensi Dark Mode Kalender

### Deskripsi

Container kalender masih menggunakan warna Light Mode meskipun sistem berada pada Dark Mode.

### Requirement

Perbaiki komponen berikut:

- Background kalender
- Header kalender
- Border kalender
- Tombol Next
- Tombol Previous
- Tombol Today

Semua harus mengikuti tema aktif.

### Acceptance Criteria

- Tidak terdapat komponen kalender yang tetap berwarna putih saat Dark Mode aktif.
- Warna tombol mengikuti design system.

---

## A.3 Perbaikan Card Form Kesediaan Dosen

### Deskripsi

Card Form Kesediaan Menguji masih berwarna putih sehingga kurang terlihat pada Dark Mode.

### Requirement

Sesuaikan:

- Background card
- Border
- Shadow
- Text
- Icon
- Button

Mengikuti Light dan Dark Mode.

### Acceptance Criteria

- Card tampil jelas pada kedua mode.
- Tidak ada background putih saat Dark Mode.

---

# B. Role Super Admin

## B.1 Konsistensi Header Tabel Master Periode

### Requirement

Header tabel Master Periode harus mengikuti mode sistem.

Perbaiki:

- Header Table
- Border
- Text Color
- Hover

### Acceptance Criteria

Header tidak lagi berwarna putih pada Dark Mode.

---

## B.2 Konsistensi DataTables

Berlaku untuk:

- Data Skripsi
- Data Sempro

Perbaiki:

- Header Table
- Body Table
- Pagination
- Search Box
- Sorting
- Empty State
- Form Input
- Form Edit
- Modal
- Select Option
- Date Picker

Semua harus mendukung:

- Light Mode
- Dark Mode

### Acceptance Criteria

Tidak ada lagi komponen putih yang bertabrakan dengan Dark Mode.

---

## B.3 Verifikasi Pendaftaran

Tambahkan proses verifikasi pendaftaran.

Menu:

- Daftar Sempro
- Daftar Skripsi

Data yang ditampilkan:

- Nama Mahasiswa
- NIM
- Judul Skripsi
- Jenis TA
- Pembimbing Utama
- Pembimbing Pendamping
- Nomor WA
- Tanggal Pendaftaran
- Status
- File Persyaratan

Fitur:

- Preview PDF
- Download PDF
- Approve
- Reject
- Catatan Revisi

Status:

- Menunggu Verifikasi
- Disetujui
- Ditolak

Role yang dapat melakukan verifikasi:

- Super Admin
- Koordinator

Jika disetujui:

- Status berubah menjadi **Pendaftaran Berhasil**
- Mahasiswa memperoleh notifikasi pada dashboard.

---

# C. Role Mahasiswa

## C.1 Perubahan Form Pendaftaran Sempro dan Skripsi

### Field

| Field | Ketentuan |
|---------|-----------|
| Nama | Otomatis dari akun |
| NIM | Otomatis dari akun |
| Judul Skripsi | Input |
| Jenis TA | Dropdown (Sidang Skripsi / Jurnal) |
| Dosen Pembimbing Utama | Dropdown dari Master Dosen |
| Dosen Pembimbing Pendamping | Dropdown dari Master Dosen |
| Nomor WA Aktif | Input |
| Tanggal Pendaftaran | Otomatis sesuai tanggal server (Read Only) |
| Upload Berkas | PDF |

---

## Validasi

Nama:

- Tidak dapat diedit.

NIM:

- Tidak dapat diedit.

Tanggal:

- Tidak dapat diedit.
- Mengambil tanggal server.

---

## Upload Berkas

Hanya terdapat satu upload file.

Ketentuan:

- PDF
- Maksimal 4 MB
- Preview PDF
- Replace File

Jika mahasiswa mengunggah ulang:

- File lama otomatis dihapus.
- File baru menggantikan file sebelumnya.

Database hanya menyimpan:

```
file_persyaratan
```

Field upload lama dihapus.

---

# C.2 Persyaratan Sidang Skripsi

Tambahkan panel informasi pada halaman pendaftaran.

## Syarat dan Ketentuan Sidang Skripsi

1. Scan bukti surat pendaftaran skripsi yang sudah ditandatangani Ir. Alvin Rainaldy Hakim, S.Kom., M.Kom.
2. Scan transkrip nilai resmi dari BAAK.
3. Tidak terdapat nilai E.
4. Nilai D maksimal 14 SKS.
5. Nilai Metodologi Penelitian minimal BC.
6. Nilai Pancasila minimal BC.
7. Nilai Kewarganegaraan minimal BC.
8. Nilai Bahasa Indonesia minimal C.
9. Nilai Pendidikan Agama minimal C.
10. Scan KRS semester berjalan.
11. Total SKS minimal 138 SKS.
12. Surat pengumpulan proposal.
13. Hasil Turnitin maksimal 25%.
14. Halaman persetujuan yang telah ditandatangani.
15. Lampiran ACC pada buku bimbingan.

Semua dokumen digabung menjadi satu file PDF.

Format nama file:

```
NIM_NAMA_SKRIPSI.pdf
```

---

# C.3 Persyaratan Seminar Proposal

Tambahkan panel informasi.

## Persyaratan Akademik

- Minimal 125 SKS.
- Metodologi Penelitian minimal BC.
- PKL/KP lulus.
- KKL lulus.
- KKN lulus.
- Mata kuliah umum minimal C.
- IPK minimal 2.50.
- Nilai D maksimal 14 SKS.

## Pembayaran

Nominal:

Rp200.000

Rekening BSI

```
7318709593

Ahmad Abdul Chamid
dan
Alvin R
```

## Dokumen Wajib

- Persetujuan Judul
- Bukti Pembayaran
- Transkrip
- KRS Sempro

Semua dijadikan satu file PDF.

---

## C.4 Validasi Upload

Validasi sistem:

- Hanya PDF.
- Maksimal 4 MB.
- Preview PDF.
- Replace file jika upload ulang.
- Menampilkan pesan validasi apabila ukuran melebihi batas.

---

# D. Registrasi Mahasiswa

## D.1 Verifikasi Email

Tambahkan aktivasi akun melalui email.

Alur:

1. Mahasiswa registrasi.
2. Sistem mengirim email verifikasi.
3. Mahasiswa klik link aktivasi.
4. Akun aktif.

Sebelum verifikasi:

- Tidak dapat login.

---

## D.2 Validasi Domain Email

Hanya menerima email:

```
@std.umk.ac.id
```

Contoh valid:

```
202251001@std.umk.ac.id
```

Contoh tidak valid:

```
@gmail.com
@yahoo.com
@hotmail.com
@student.umk.ac.id
```

Pesan validasi:

> Email harus menggunakan domain @std.umk.ac.id.

---

# Non Functional Requirements

## UI/UX

- Seluruh komponen mendukung Light Mode.
- Seluruh komponen mendukung Dark Mode.
- Tidak ada elemen dengan warna tetap (hardcoded) yang menyebabkan ketidaksesuaian tema.
- Menggunakan warna dari Design System aplikasi.

---

## Keamanan

- Verifikasi email wajib sebelum akun aktif.
- Validasi MIME Type PDF.
- Validasi ukuran file maksimal 4 MB.
- Proteksi upload file terhadap ekstensi selain PDF.
- Penyimpanan file menggunakan nama unik untuk menghindari konflik.

---

## Performa

- Preview PDF kurang dari 2 detik.
- Upload file maksimal 4 MB selesai tanpa timeout pada koneksi normal.
- Pergantian Light/Dark Mode berlangsung secara instan tanpa reload halaman.

---

# Acceptance Criteria

| No | Requirement | Status |
|-----|------------|--------|
| 1 | Kalender memiliki highlight hari ini | ✅ |
| 2 | Kalender mendukung Light & Dark Mode | ✅ |
| 3 | Card Kesediaan Dosen mengikuti tema | ✅ |
| 4 | Header Master Periode mengikuti tema | ✅ |
| 5 | DataTables mendukung Light & Dark Mode | ✅ |
| 6 | Form Input/Edit mengikuti tema | ✅ |
| 7 | Super Admin dapat preview dan verifikasi pendaftaran | ✅ |
| 8 | Mahasiswa mengisi form pendaftaran lengkap | ✅ |
| 9 | Upload hanya 1 file PDF maksimal 4 MB | ✅ |
| 10 | Upload ulang menimpa file lama | ✅ |
| 11 | Informasi syarat Sempro dan Skripsi tampil pada halaman pendaftaran | ✅ |
| 12 | Registrasi menggunakan email @std.umk.ac.id | ✅ |
| 13 | Akun aktif setelah verifikasi email | ✅ |