# 💎 Midnight Finance - Private Wealth (Backend)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

Sistem inti dari **Midnight Finance**, sebuah aplikasi manajemen kekayaan pribadi yang dirancang untuk presisi dan kecepatan. Dibangun menggunakan **Laravel 11** dengan arsitektur API yang aman dan efisien.

### 🚀 Fitur Utama
* **Secure Authentication:** Menggunakan Laravel Sanctum untuk keamanan token.
* **Financial Management:** CRUD Akun Keuangan, Kategori, dan Transaksi.
* **Wealth Analytics:** Logika *Zero-Filling* untuk visualisasi arus kas yang akurat.
* **Automated Balance Sync:** Sinkronisasi saldo otomatis setiap ada transaksi masuk/keluar.

### 🛠️ Cara Install (Local)
1. Clone repo ini.
2. Jalankan `composer install`.
3. Copy `.env.example` ke `.env` dan atur database.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate --seed`.
6. Jalankan `php artisan serve`.

---
Developed by **[Dzayong](https://github.com/Dzayong)** 💸
