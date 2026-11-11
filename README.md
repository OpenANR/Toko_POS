<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Pencetakan Struk (Mike42 ESC/POS)

Aplikasi POS pada halaman `/pos` mencetak struk **sepenuhnya dari backend PHP**
menggunakan library `mike42/escpos-php`. Browser hanya menampilkan preview hasil
cetak; tidak ada lagi pencetakan melalui `window.print()`.

Alur cetak:

1. Kasir menekan **Bayar & Cetak Struk**.
2. `PosController@checkout` menyimpan transaksi, lalu memanggil
   `App\Services\ReceiptPrinter` untuk mencetak struk.
3. Teks struk yang sama dikirim balik ke browser sebagai preview.
4. Tombol **Cetak Ulang ke Printer** memanggil `PosController@reprint`
   (`POST /pos/receipt/{transaction}/print`).

Konfigurasi konektor printer ada di `config/printing.php` dan dapat diatur
melalui `.env`:

| Variabel | Keterangan |
| --- | --- |
| `PRINT_DRIVER` | `file`, `windows`, `cups`, atau `network` |
| `PRINT_PRINTER_NAME` | Nama printer (untuk driver `windows`/`cups`) |
| `PRINT_FILE_PATH` | Lokasi file simulasi (driver `file`), default `storage/logs/struk.txt` |
| `PRINT_NETWORK_HOST` / `PRINT_NETWORK_PORT` | Alamat printer LAN (driver `network`) |
| `PRINT_STORE_NAME` | Nama toko pada struk |
| `PRINT_STORE_ADDRESS` | Alamat toko pada struk |
| `PRINT_PAPER_WIDTH` | Lebar kertas: `32` (58mm) atau `48` (80mm) |

Secara default driver `file` dipakai sehingga aplikasi tetap berjalan tanpa
printer fisik — hasil cetak dapat dilihat di `storage/logs/struk.txt`. Untuk
printer thermal nyata, ubah `PRINT_DRIVER` sesuai sistem operasi
(`windows` atau `cups`).

## Payment Gateway Midtrans

Halaman `/pos` mendukung dua metode pembayaran: **Tunai** dan **Midtrans**
(QRIS, e-wallet, virtual account, kartu, dan gerai retail). Integrasi mengikuti
materi BKPM acara 37–38.

Konfigurasi ada di `.env` dan `config/midtrans.php`:

| Variabel | Keterangan |
| --- | --- |
| `MIDTRANS_SERVER_KEY` | Server Key dari dashboard Midtrans (sandbox diawali `SB-Mid-server-`) |
| `MIDTRANS_CLIENT_KEY` | Client Key dari dashboard Midtrans (sandbox diawali `SB-Mid-client-`) |
| `MIDTRANS_IS_PRODUCTION` | `false` untuk sandbox, `true` untuk produksi |
| `MIDTRANS_SANITIZE` | Sanitasi data pelanggan |
| `MIDTRANS_3DS` | Aktifkan 3D Secure untuk kartu kredit/debit |

Alur pembayaran Midtrans:

1. Kasir memilih metode **Midtrans** lalu menekan **Bayar dengan Midtrans**.
2. `PosController@checkout` menyimpan transaksi dengan status `pending` dan
   memanggil `App\Services\MidtransPayment` untuk membuat Snap token.
3. Browser membuka popup Snap (`snap.js`) menggunakan Client Key.
4. Setelah pembayaran, Midtrans mengirim webhook ke
   `POST /midtrans/notification` (`MidtransController@notification`).
5. Webhook memverifikasi `signature_key`, mengambil status terbaru dari API
   Midtrans, lalu mengubah `payment_status` menjadi `paid`, `failed`, atau
   `expired`. Stok dikembalikan otomatis jika pembayaran gagal/kedaluwarsa.

Agar webhook bisa dipanggil Midtrans saat pengembangan lokal, buka tunnel
publik (misalnya `ngrok http 8000`) lalu isi **Payment Notification URL** di
dashboard Midtrans dengan `https://<domain-tunnel>/midtrans/notification`.

> **Peringatan sandbox:** jangan pernah scan QR/VA transaksi sandbox memakai
> aplikasi e-wallet atau bank asli. Referensi sandbox bisa saja mengarah ke
> akun produksi dan dana asli berisiko tidak kembali. Gunakan simulator resmi
> Midtrans: <https://simulator.sandbox.midtrans.com/> (untuk QRIS, salin URL
> gambar QR lalu tempel di simulator QRIS).

Status pembayaran tersimpan di kolom `transactions.payment_status`
(`pending`, `paid`, `failed`, `expired`) dan `transactions.payment_method`
(`cash`, `midtrans`).

### Pengujian

```bash
composer test
```

Test struk memeriksa format struk (nama toko, tanggal, item, subtotal, diskon,
total, kembalian) dan mencetak ulang melalui backend.

