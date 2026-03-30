# TOKO FHIKA — POS & Manajemen Stok

**Aplikasi Point of Sale dan Manajemen Stok** untuk toko grosir/warung kelontong.  
Dibangun dengan **PHP Native + MySQL + Vanilla JS**.

---

## ⚡ Quick Start

```bash
# 1. Salin folder ke htdocs XAMPP
C:\xampp\htdocs\toko_fhika\

# 2. Import database
# phpMyAdmin → Import → database/toko_fhika.sql

# 3. Edit DB credentials di koneksi.php (jika bukan root/kosong)

# 4. Buka browser
http://localhost/toko_fhika/
```

## 🔐 Login Demo

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Kasir | `kasir` | `password` |

## 📁 File Utama

| File | Fungsi |
|------|--------|
| `koneksi.php` | Konfigurasi DB & helper functions |
| `index.php` | Katalog merk (halaman kasir) |
| `produk.php` | Produk per merk + keranjang |
| `checkout.php` | API transaksi (AJAX) |
| `admin/dashboard.php` | Dashboard admin |
| `admin/produk.php` | CRUD produk |
| `admin/stok.php` | Manajemen stok |
| `admin/transaksi.php` | Laporan transaksi |

## 🛠 Tech Stack

- **Backend**: PHP 8.x (Native/Procedural)
- **Database**: MySQL dengan MySQLi + Prepared Statements
- **Frontend**: HTML5 + Vanilla JavaScript (localStorage cart)
- **CSS**: Custom Design System (tanpa framework)
- **Icons**: Bootstrap Icons via CDN
- **Charts**: Chart.js via CDN
- **Fonts**: Google Fonts (Inter)

## 🔒 Keamanan

- Password hashing: bcrypt
- SQL injection: Prepared Statements
- XSS: `htmlspecialchars()` di semua output
- Race condition checkout: `SELECT ... FOR UPDATE`
- Upload security: `.htaccess` blokir PHP di `assets/uploads/`
