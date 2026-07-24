# Product Requirement Document (PRD)
# Revisi dan Penyempurnaan Sistem Penjadwalan Seminar Proposal & Skripsi

| Dokumen | PRD Revisi |
|----------|------------|
| Sistem | Sistem Penjadwalan Seminar Proposal & Skripsi |
| Status | Enhancement / Improvement |
| Versi | 1.1 |
| Tanggal | Juli 2026 |

---

# 1. Latar Belakang

Sistem Penjadwalan Seminar Proposal dan Skripsi saat ini telah berjalan dan digunakan dalam proses administrasi Program Studi. Namun, berdasarkan hasil evaluasi penggunaan oleh Koordinator, Super Admin, Dosen, dan Mahasiswa, masih terdapat beberapa fitur yang memerlukan penyempurnaan baik dari sisi fungsional maupun antarmuka pengguna (UI/UX).

Dokumen ini berisi daftar revisi dan enhancement yang akan diterapkan pada sistem agar proses administrasi, pengisian kesediaan menguji, penjadwalan, serta penyampaian notifikasi menjadi lebih efektif, efisien, dan mudah digunakan.

---

# 2. Tujuan

- Menyempurnakan alur pengisian kesediaan menguji dosen.
- Mempermudah proses plotting jadwal Seminar Proposal dan Skripsi.
- Meningkatkan kualitas tampilan antarmuka pengguna.
- Meningkatkan efektivitas sistem notifikasi.
- Meningkatkan pengalaman pengguna (User Experience).

---

# 3. Ruang Lingkup

Pengembangan hanya mencakup penyempurnaan modul yang sudah tersedia, meliputi:

- Dashboard Dosen
- Dashboard Mahasiswa
- Dashboard Super Admin
- Dashboard Koordinator
- Modul Penjadwalan
- Modul Kesediaan Menguji
- Modul Notifikasi

---

# 4. Daftar Perubahan

---

# A. Role Dosen

## REQ-01
### Otomatis Menampilkan Form Kesediaan Menguji

### Kondisi Saat Ini

Form kesediaan menguji belum muncul secara otomatis setelah masa pendaftaran berakhir.

### Perubahan

Apabila Koordinator atau Super Admin telah membuka gelombang Seminar Proposal atau Skripsi, kemudian masa pendaftaran telah berakhir, maka sistem secara otomatis menampilkan tombol:

> **Isi Kesediaan Menguji**

pada Dashboard seluruh dosen.

### Ketentuan

- Tombol muncul otomatis setelah tanggal penutupan pendaftaran.
- Berlaku untuk Sempro maupun Skripsi.
- Tombol hanya muncul pada role Dosen.
- Tombol muncul di Dashboard.

---

## REQ-02
### Batas Waktu Pengisian

### Perubahan

Form kesediaan hanya dapat diisi selama **6 hari**.

### Ketentuan

Hari ke-1 sampai Hari ke-6

- Tombol aktif.
- Dosen dapat mengubah jawaban.

Hari ke-7

- Tombol otomatis hilang.
- Form tidak dapat diakses.

---

## REQ-03
### Lock Jawaban

### Perubahan

Setelah dosen selesai mengisi kesediaan menguji, sistem menyediakan tombol:

> **Lock Jawaban**

### Ketentuan

Sebelum Lock

- Jawaban masih dapat diubah.

Sesudah Lock

- Jawaban tidak dapat diedit kembali.
- Status berubah menjadi **Locked**.

---

## REQ-04
### Format Jam

### Kondisi Saat Ini

Format waktu masih menggunakan AM / PM.

### Perubahan

Seluruh input waktu menggunakan format 24 jam.

Contoh:

```
08:00
13:00
15:30
```

Bukan

```
08:00 AM
03:30 PM
```

---

## REQ-05
### Pengisian Kesediaan Pada Gelombang Berikutnya

### Perubahan

Apabila terdapat pembukaan gelombang baru Seminar Proposal maupun Skripsi, maka sistem kembali menampilkan tombol pengisian kesediaan menguji.

### Ketentuan

- Berlaku setiap gelombang baru.
- Tidak menggunakan data kesediaan pada gelombang sebelumnya.
- Riwayat tetap tersimpan.

---

## REQ-06
### Penyempurnaan Tampilan Notifikasi

### Kondisi Saat Ini

Tampilan notifikasi masih kurang rapi.

### Perubahan

Melakukan redesign tampilan notifikasi agar lebih informatif dan mudah dibaca.

### Rekomendasi

- Icon berdasarkan jenis notifikasi.
- Badge jumlah notifikasi.
- Warna berdasarkan kategori.
- Timestamp yang jelas.
- Tombol "Lihat Semua".
- Status sudah dibaca / belum dibaca.

---

# B. Role Mahasiswa

## REQ-07
### Perbaikan Popup Form Pendaftaran

### Kondisi Saat Ini

Ukuran popup pendaftaran terlalu besar.

### Perubahan

Popup tetap dipertahankan, namun tampilannya dibuat lebih ringkas.

### Ketentuan

- Lebih kecil.
- Tidak memenuhi layar.
- Responsive.
- Jarak antar field lebih proporsional.
- Tetap nyaman digunakan.

---

## REQ-08
### Notifikasi H-1 Seminar Proposal dan Sidang Skripsi

### Perubahan

Sistem memberikan notifikasi otomatis kepada mahasiswa satu hari sebelum jadwal Seminar Proposal maupun Sidang Skripsi.

### Contoh

```
Besok Anda memiliki jadwal Seminar Proposal.

Tanggal :
25 Juli 2026

Jam :
09.00 WIB

Ruangan :
Lab AI
```

### Ketentuan

- Otomatis H-1.
- Muncul pada Dashboard.
- Status dapat ditandai telah dibaca.

---

# C. Role Super Admin & Koordinator

## REQ-09
### Menu Kesediaan Menguji

### Perubahan

Seluruh data kesediaan dosen disimpan dalam menu baru.

```
Penjadwalan
    └── Kesediaan Menguji
```

### Data yang Ditampilkan

- Nama Dosen
- NIDN
- Gelombang
- Jenis Ujian
- Hari Bersedia
- Jam Bersedia
- Status Lock
- Tanggal Pengisian

---

## REQ-10
### Integrasi Dengan Plotting Jadwal

### Kondisi Saat Ini

Super Admin masih harus melihat data kesediaan secara manual.

### Perubahan

Saat melakukan plotting jadwal, sistem otomatis menampilkan data kesediaan dosen sehingga mempermudah proses penjadwalan.

### Informasi yang Ditampilkan

- Hari bersedia
- Jam bersedia
- Status Lock
- Jenis ujian
- Gelombang
- Dosen belum mengisi
- Dosen sudah mengisi

---

## REQ-11
### Filter Data Kesediaan

Untuk mempermudah pencarian data, sistem menyediakan filter berdasarkan:

- Nama Dosen
- Gelombang
- Jenis Ujian
- Hari
- Status Lock

---

# 5. Acceptance Criteria

| No | Requirement | Acceptance Criteria |
|----|-------------|---------------------|
| 1 | Tombol Kesediaan | Muncul otomatis setelah penutupan gelombang |
| 2 | Batas Pengisian | Aktif selama 6 hari, hilang pada hari ke-7 |
| 3 | Lock Jawaban | Jawaban tidak dapat diubah setelah dikunci |
| 4 | Format Jam | Menggunakan format 24 jam |
| 5 | Gelombang Baru | Tombol muncul kembali pada setiap gelombang baru |
| 6 | Notifikasi Dosen | Tampilan lebih rapi dan informatif |
| 7 | Popup Mahasiswa | Lebih kecil dan efisien |
| 8 | Notifikasi H-1 | Muncul otomatis satu hari sebelum sidang |
| 9 | Menu Kesediaan | Data tersimpan pada submenu Penjadwalan |
| 10 | Plotting Jadwal | Menampilkan data kesediaan dosen saat penjadwalan |
| 11 | Filter Data | Dapat difilter berdasarkan beberapa parameter |

---

# 6. Dampak Pengembangan

## Bagi Dosen

- Lebih mudah mengisi kesediaan menguji.
- Tidak perlu mengingat jadwal pengisian karena sistem berjalan otomatis.
- Notifikasi lebih informatif.

## Bagi Mahasiswa

- Form pendaftaran lebih nyaman digunakan.
- Mendapat pengingat sebelum pelaksanaan sidang.

## Bagi Super Admin dan Koordinator

- Proses plotting jadwal lebih cepat.
- Tidak perlu melakukan pengecekan manual kesediaan dosen.
- Data kesediaan lebih mudah dicari dan dikelola.
- Mengurangi potensi bentrok jadwal karena informasi kesediaan tersedia secara langsung saat proses penjadwalan.

---

# 7. Prioritas Pengembangan

| Prioritas | Fitur |
|------------|-------|
| Tinggi | Otomatisasi Form Kesediaan Menguji |
| Tinggi | Integrasi Kesediaan dengan Plotting Jadwal |
| Tinggi | Lock Jawaban |
| Tinggi | Notifikasi H-1 Sidang |
| Sedang | Perbaikan UI Popup Mahasiswa |
| Sedang | Penyempurnaan Tampilan Notifikasi |
| Rendah | Penyempurnaan Visual Dashboard |