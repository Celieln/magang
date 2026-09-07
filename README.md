# Sistem Magang

Sistem manajemen magang - pendaftaran peserta, LPK, dan export data.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)
![Repo](https://img.shields.io/badge/Status-Aktif-blue)

## Teknologi

**Backend**
- PHP 8.x - server-side scripting dengan **PDO** + **prepared statements**
- Form publik + admin dashboard (CRUD)
- Session-based authentication (bcrypt) & role authorization
- Export data (LPK, bidang, peserta)

**Frontend**
- HTML5, CSS3, JavaScript (ES6+)
- Bootstrap 5 responsive

**Database**
- MySQL 8 / MariaDB - skema ternormalisasi

**Tooling & DevOps**
- Composer
- Git & GitHub
- Laragon/WAMP

## Arsitektur

- **Front-end first** - hanya berisi tampilan depan (public UI)
- Routing & layout modular (includes, pages)
- Keamanan berlapis: prepared statements, input sanitization, password hashing
- Session-based auth dengan bcrypt & role-based access control

## Quick Start

Prasyarat: [Laragon](https://laragon.org) / [XAMPP](https://www.apachefriends.org)

1. Clone repository ke folder laragon/www/ atau htdocs/:

   ```bash
   git clone https://github.com/Celieln/magang.git
   ```

2. Import database (jika tersedia) melalui phpMyAdmin.
3. Konfigurasi koneksi database di folder config/.
4. Jalankan server Apache. Buka http://localhost/magang.

## Struktur Proyek

```
magang/
  includes/    # Komponen yang di-include (header, footer, dll)
  assets/      # CSS, JS, gambar
  *.php        # Halaman tampilan depan
```

## Kontribusi

Kontribusi sangat diterima! Baca [CONTRIBUTING](CONTRIBUTING.md) dan buka [Issues](https://github.com/Celieln/magang/issues) untuk melaporkan bug / request fitur.

## Lisensi

[MIT](LICENSE) (c) [Celieln](https://github.com/Celieln)
