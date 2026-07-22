---

## FR-009 Manajemen Menu Berdasarkan Hak Akses

Menu yang ditampilkan pada Role Dosen **tidak ditentukan secara statis (hardcode)** di dalam aplikasi.

Seluruh menu yang dapat diakses oleh Role Dosen harus mengikuti konfigurasi hak akses (permission) yang diberikan oleh **Super Admin**.

### Ketentuan

- Super Admin dapat menentukan menu apa saja yang dapat diakses oleh Role Dosen.
- Sistem hanya menampilkan menu yang memiliki permission aktif.
- Apabila suatu menu tidak diberikan permission, maka menu tersebut tidak ditampilkan pada Sidebar maupun tidak dapat diakses melalui URL secara langsung.
- Perubahan hak akses oleh Super Admin harus langsung berlaku tanpa memerlukan perubahan kode aplikasi.

### Contoh

Role Dosen memiliki permission:

- Dashboard
- Jadwal
- Kalender
- Profil

Maka Sidebar akan menjadi:

```
Dashboard

Jadwal
├── Seminar Proposal
└── Sidang Skripsi

Kalender

Profil
```

Apabila Super Admin mencabut permission **Kalender**, maka Sidebar berubah menjadi:

```
Dashboard

Jadwal
├── Seminar Proposal
└── Sidang Skripsi

Profil
```

Menu **Kalender** tidak boleh muncul dan tidak dapat diakses melalui URL.

---

## BR-005 Dynamic Menu Permission

Sistem wajib menghasilkan navigasi berdasarkan data permission yang dimiliki oleh Role Dosen.

Menu tidak boleh dibuat secara hardcode pada frontend maupun backend.

Seluruh perubahan menu mengikuti konfigurasi yang dilakukan oleh Super Admin.

---

## NFR-004 Dynamic Authorization

Sistem harus menerapkan **Role-Based Access Control (RBAC)** dengan permission yang bersifat dinamis.

Setiap request ke halaman maupun endpoint API wajib melakukan pengecekan terhadap permission yang dimiliki pengguna sebelum memberikan akses.

Apabila pengguna tidak memiliki permission terhadap suatu menu, maka sistem harus mengembalikan respons **403 Forbidden** atau mengarahkan ke halaman **Unauthorized**.