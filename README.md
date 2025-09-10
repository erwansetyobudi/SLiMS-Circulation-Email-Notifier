## 👤 Author

* **Name**: Erwan Setyo Budi
* **Email**: [erwans818@gmail.com](mailto:erwans818@gmail.com)

---

## 🔖 Plugin Information

* **Plugin Name**: Circulation-Email-Notifier
* **Author**: Erwan Setyo Budi
---

# Circulation Email Notifier for SLiMS

Plugin ini menambahkan fitur **notifikasi email otomatis** setiap kali transaksi sirkulasi di SLiMS selesai (**pinjam, pengembalian, perpanjangan**).
Email akan dikirim ke alamat email anggota sesuai data `member_email`.

---

## 📋 Syarat Penggunaan

Sebelum menggunakan plugin ini, pastikan konfigurasi email sudah benar:

1. **Aktifkan IMAP dan POP3** pada akun email yang digunakan sebagai pengirim.
2. **Buat sandi aplikasi** (App Password) khusus pada akun Google Anda melalui tautan: [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
3. **Setting Email di SLiMS**:

   * Login sebagai **Super Admin** SLiMS.
   * Buka menu **Sistem → Pengaturan Surel**.
   * Masukkan konfigurasi SMTP sesuai email Anda.
   * Lakukan **uji kirim email** hingga berhasil, karena plugin ini akan menggunakan konfigurasi yang sama.

---

## ✨ Fitur

* Kirim email otomatis saat klik **Finish Transaction**.
* Isi email berupa ringkasan transaksi (pinjaman baru, pengembalian, perpanjangan).
* Template email mudah dikustomisasi (`transactionMail.php`).
* Mendukung **pengaturan mail resmi SLiMS** (`System → Mail Setting`).
* Fallback ke **PHPMailer** jika facade `SLiMS\Mail` tidak tersedia.

---

## 📂 Struktur Plugin

```
plugins/SLiMS-Circulation-Email-Notifier/
│
├── circ_email_notify.plugin.php   # File registrasi plugin
├── index.php                      # Hook & logika utama
├── transactionMail.php            # Template email transaksi
└── debug.log                      # (otomatis) log debug pengiriman email
```

---

## ⚙️ Instalasi

1. Copy folder `circ_email_notify` ke dalam direktori `plugins/` SLiMS:

   ```
   slims/
   └── plugins/
       └── circ_email_notify/
           ├── circ_email_notify.plugin.php
           ├── index.php
           └── transactionMail.php
   ```

2. Login ke SLiMS sebagai admin → buka **System → Plugins**.
   Pastikan plugin **Circulation Email Notifier** muncul dan aktif.

3. Pastikan **Mail Setting** sudah dikonfigurasi dan berhasil diuji.

---

## ▶️ Penggunaan

1. Lakukan transaksi sirkulasi seperti biasa (scan barcode anggota → pinjam / kembalikan buku).
2. Klik tombol **Finish Transaction**.
3. Anggota akan otomatis menerima email berisi detail transaksi.

---

## 🛠️ Debugging

* Jika email tidak terkirim, cek file `plugins/circ_email_notify/debug.log`.
* Log akan mencatat:

  * Status hook (`HOOK FIRED`)
  * Data transaksi (loan/return/extend)
  * Hasil pengiriman email (`Mail sent OK` atau error detail)

Contoh log sukses:

```
[2025-09-10 12:55:32] HOOK FIRED type=array
[2025-09-10 12:55:32] Payload shape: raw receipt array
[2025-09-10 12:55:32] Blocks: loan=1 return=0 extend=0
[2025-09-10 12:55:32] Member email: user@example.com
[2025-09-10 12:55:32] Mail sent OK via \SLiMS\Mail
```

---

## 🖼️ Kustomisasi Email

* Edit `transactionMail.php` untuk mengubah tampilan email.
* Anda bisa menambahkan:

  * Logo perpustakaan
  * Footer khusus (misalnya link ke website)
  * Gaya CSS tambahan

---

## 📜 Lisensi

GPL v3 — sama dengan SLiMS.

---

## Screen Shoot
<img width="1346" height="619" alt="image" src="https://github.com/user-attachments/assets/d4eb98d4-c36a-49b3-aa10-ddffa1c792cf" />
<img width="1350" height="683" alt="image" src="https://github.com/user-attachments/assets/3b821f15-3954-4515-aef1-61a58780115d" />





