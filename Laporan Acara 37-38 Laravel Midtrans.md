# LAPORAN PRAKTIKUM

## ACARA 37–38: INTEGRASI PAYMENT GATEWAY MIDTRANS PADA LARAVEL 12

**Mata Kuliah:** Workshop Sistem Informasi Web Framework  
**Program Studi:** D4 Teknik Informatika — Politeknik Negeri Jember  

**Disusun oleh:**

| Identitas | Keterangan |
| --- | --- |
| Nama | ................................ |
| NIM | ................................ |
| Golongan | ................................ |
| Tugas Minggu Ke- | 11 (Acara 37–38) |

---

## 1. PENDAHULUAN

### 1.1 Latar Belakang

Integrasi sistem pembayaran elektronik (*payment gateway*) merupakan komponen vital dalam pengembangan aplikasi kasir modern (*Point of Sale*) maupun *e-commerce*. Sistem pembayaran digital memungkinkan transaksi non-tunai yang cepat, otomatis, dan minim kesalahan pencatatan kasir.

Pada praktikum Acara 37 dan 38 ini, dilakukan implementasi integrasi layanan **Midtrans Payment Gateway** ke dalam aplikasi **Toko POS (Kopi Kita)** berbasis **Laravel 12**. Midtrans dipilih karena menyediakan infrastruktur pembayaran yang lengkap untuk pasar Indonesia, mencakup QRIS (GoPay, ShopeePay), Transfer Bank / Virtual Account (BCA, Mandiri, BNI, BRI, Permata), serta Kartu Kredit/Debit. 

Implementasi memanfaatkan library resmi `midtrans/midtrans-php` versi 2.6 dengan integrasi frontend menggunakan **Midtrans Snap Modal**. Pada sisi backend Laravel 12, diterapkan arsitektur *Service Layer* (`MidtransPayment`), penanganan *Webhook / Notification Callback* secara asinkron dengan verifikasi keamanan *Signature Key* (SHA512), serta pengecualian verifikasi token CSRF melalui struktur modern `bootstrap/app.php`.

### 1.2 Tujuan Praktikum

1. Menjelaskan konsep dasar dan alur kerja *Payment Gateway* Midtrans.
2. Mengimplementasikan instalasi SDK Midtrans dan konfigurasi kredensial Sandbox pada Laravel 12.
3. Memahami proses transaksi *Snap Token*, rendering antarmuka pembayaran Midtrans Snap, serta penerimaan *HTTP Notification/Webhook*.
4. Mengimplementasikan validasi keamanan *Signature Key* dan sinkronisasi status pembayaran ke database aplikasi POS.

### 1.3 Alat dan Bahan

1. PC/Laptop (OS Linux / Windows 10, RAM minimal 8 GB).
2. PHP 8.2+ dan Framework Laravel 12.
3. Composer & Node.js/Vite.
4. Akun Midtrans Sandbox (Server Key & Client Key).
5. Database MySQL / SQLite.
6. Web Browser (Google Chrome / Firefox).

---

## 2. HASIL PRAKTIK

### 2.1 Instalasi Library Midtrans PHP SDK

Integrasi diawali dengan menginstal SDK resmi Midtrans untuk PHP melalui Composer:

```bash
composer require midtrans/midtrans-php
```

Instalasi berhasil memasang paket `midtrans/midtrans-php` versi 2.6.2 beserta autoloader dependensinya ke dalam file `composer.json` dan `composer.lock` (Gambar **1.png**).

### 2.2 Konfigurasi Environment dan File Konfigurasi

Kredensial API Sandbox dari dashboard Midtrans didaftarkan pada file `.env`:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-1jVtJ14y0t~igw5byDD1YBY_
MIDTRANS_CLIENT_KEY=SB-Mid-client-rZpGxod0sLUI6JR_
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_REDIRECT=true
MIDTRANS_SANITIZE=true
MIDTRANS_3DS=true
```

(Gambar **2.png**)

Selanjutnya dibuat file `config/midtrans.php` agar konfigurasi dapat diakses secara terstruktur di seluruh ekosistem Laravel:

```php
<?php

return [
    'serverKey' => env('MIDTRANS_SERVER_KEY'),
    'clientKey' => env('MIDTRANS_CLIENT_KEY'),
    'isProduction' => env('MIDTRANS_IS_PRODUCTION', false),
    'isSanitized' => env('MIDTRANS_SANITIZE', true),
    'is3ds' => env('MIDTRANS_3DS', true),
];
```

(Gambar **3.png**)

### 2.3 Implementasi Service Layer (`MidtransPayment`)

Logika konfigurasi SDK dan pembuatan *Snap Token* dienkapsulasi ke dalam service `app/Services/MidtransPayment.php`:

```php
namespace App\Services;

use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransPayment
{
    public function configure(): void
    {
        Config::$serverKey = (string) config('midtrans.serverKey');
        Config::$isProduction = (bool) config('midtrans.isProduction');
        Config::$isSanitized = (bool) config('midtrans.isSanitized');
        Config::$is3ds = (bool) config('midtrans.is3ds');
    }

    public function createSnapToken(Transaction $transaction): string
    {
        $this->configure();

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->invoice_number,
                'gross_amount' => (int) round((float) $transaction->total_price),
            ],
            'customer_details' => [
                'first_name' => 'Pelanggan Toko',
                'email' => 'pelanggan@example.com',
            ],
            'callbacks' => [
                'finish' => route('pos.index'),
            ],
        ];

        return (string) Snap::getSnapToken($params);
    }
}
```

### 2.4 Implementasi Webhook dan Keamanan Controller (`MidtransController`)

Ketika pembayaran diselesaikan oleh pelanggan di sisi Midtrans, Midtrans mengirimkan HTTP POST secara asinkron ke server backend Laravel. Endpoint ini ditangani oleh `MidtransController@notification`:

```php
public function notification(Request $request): JsonResponse
{
    $payload = $request->all();

    // 1. Validasi Keamanan Signature Key (SHA-512)
    if (! $this->isValidSignature($payload)) {
        Log::warning('Notifikasi Midtrans ditolak: signature tidak valid.');
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    $this->midtrans->configure();
    $notif = new Notification;

    $orderId = $notif->order_id ?? ($payload['order_id'] ?? null);
    $transactionStatus = (string) ($notif->transaction_status ?? ($payload['transaction_status'] ?? ''));
    $fraudStatus = (string) ($notif->fraud_status ?? ($payload['fraud_status'] ?? ''));

    $transaction = Transaction::where('invoice_number', $orderId)->first();
    if (! $transaction) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    $newStatus = $this->mapStatus($transactionStatus, $fraudStatus);

    // 2. Pembaruan Status Transaksi & Rollback Stok jika Batal/Kedaluwarsa
    DB::transaction(function () use ($transaction, $newStatus) {
        $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();
        $oldStatus = $locked->payment_status;

        $attributes = ['payment_status' => $newStatus];
        if ($newStatus === 'paid' && $locked->paid_at === null) {
            $attributes['paid_at'] = now();
        }
        $locked->update($attributes);

        if (in_array($newStatus, ['failed', 'expired'], true) && $oldStatus === 'pending') {
            $locked->restoreStock();
        }
    });

    return response()->json(['message' => 'Notification handled']);
}
```

(Gambar **4.png**)

Perhitungan *Signature Key* diverifikasi dengan SHA-512 sesuai standar Midtrans:

$$\text{Signature} = \text{SHA512}(\text{order\_id} + \text{status\_code} + \text{gross\_amount} + \text{server\_key})$$

### 2.5 Routing dan Pengecualian CSRF pada Laravel 12

Rute aplikasi didaftarkan pada `routes/web.php` (Gambar **5.png**):

```php
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
Route::post('/pos/payment/{transaction}/cancel', [PosController::class, 'cancelPayment'])->name('pos.payment.cancel');
Route::post('/pos/receipt/{transaction}/print', [PosController::class, 'reprint'])->name('pos.receipt.reprint');
Route::post('/midtrans/notification', [MidtransController::class, 'notification'])->name('midtrans.notification');
```

> [!IMPORTANT]
> **Pengecualian CSRF pada Laravel 12:**  
> Karena request notifikasi webhook berasal langsung dari server Midtrans (*machine-to-machine*) tanpa membawa session browser, endpoint `/midtrans/notification` harus dikecualikan dari verifikasi token CSRF. Pada Laravel 12, konfigurasi dilakukan di file `bootstrap/app.php`:
> ```php
> ->withMiddleware(function (Middleware $middleware): void {
>     $middleware->validateCsrfTokens(except: [
>         'midtrans/notification',
>     ]);
> })
> ```

### 2.6 Alur Transaksi dan Tampilan Output Antarmuka

1. **Pembuatan Pesanan & Snap Popup:** Saat kasir memilih metode "Midtrans" dan menekan tombol checkout, sistem menyimpan transaksi berstatus `pending` dan meminta *Snap Token* ke API Midtrans. Antarmuka memunculkan pop-up Midtrans Snap yang memuat total tagihan (Rp 65.000) dan opsi pembayaran QRIS, Virtual Account, dan Kartu Kredit (Gambar **6.png**).
2. **Generasi QRIS Dinamis:** Memilih metode QRIS menampilkan QR Code standar nasional (NMID/GPN) yang siap di-scan (Gambar **7.png**).
3. **Pembayaran Berhasil:** Setelah disimulasikan melalui Midtrans Payment Simulator, status transaksi langsung berubah menjadi sukses ("Payment successful") pada jendela Snap (Gambar **8.png**).
4. **Pencetakan Struk POS Otomatis:** Setelah pembayaran terverifikasi, aplikasi kasir menampilkan modal struk pembayaran dengan label metode *Midtrans* dan secara otomatis mencetak struk kasir melalui backend Mike42 ESC/POS (Gambar **9.png**).
5. **Verifikasi Dashboard Midtrans:** Transaksi dengan nomor invoice `#INV-QXQZWRGE` terverifikasi tercatat pada dashboard Midtrans Sandbox dengan status **Settlement** senilai Rp 65.000 via channel QRIS (Gambar **10.png**).

### 2.7 Pengujian Otomatis (Feature Test)

Pengujian fungsionalitas integrasi dilakukan secara otomatis menggunakan PHPUnit / Pest:

```bash
php artisan test --filter=MidtransPaymentTest
```

Hasil pengujian menunjukkan seluruh skenario berjalan sukses (*100% PASS*):

```text
PASS  Tests\Feature\MidtransPaymentTest
✓ midtrans checkout creates pending transaction and snap token   0.37s  
✓ cancel pending midtrans payment restores stock                 0.02s  
✓ notification with invalid signature is rejected                0.03s  

Tests:    3 passed (16 assertions)
Duration: 0.52s
```

---

## 3. PEMBAHASAN

1. **Pemisahan Logika Menggunakan Service Layer:** Penempatan konfigurasi Midtrans ke dalam `MidtransPayment` service mencegah duplikasi kode konfigurasi di berbagai controller, mempermudah pengujian *unit testing / mocking*, serta menjaga kepatuhan terhadap prinsip *Single Responsibility Principle* (SRP).
2. **Penanganan Asinkron Melalui Webhook:** Status pembayaran tidak hanya mengandalkan respon callback frontend browser yang rentan ditutup oleh pelanggan, melainkan dipastikan lewat HTTP webhook server-ke-server dari Midtrans.
3. **Pemberian Proteksi Keamanan:** Validasi *Signature Key* dengan algoritma SHA-512 memastikan bahwa notifikasi webhook yang masuk benar-benar berasal dari Midtrans dan nominal transaksi tidak dimanipulasi di tengah jalan.
4. **Integritas Stok & Transaksi:** Transaksi POS menggunakan status `pending` saat pembayaran daring dibuat. Jika pembayaran dibatalkan atau kedaluwarsa, stok yang sempat terkunci otomatis dikembalikan (*restoreStock*).
5. **Kesesuaian dengan Laravel 12:** Konfigurasi pengecualian CSRF yang ditempatkan pada `bootstrap/app.php` mengikuti standar arsitektur minimalis Laravel 12 terbaru.

---

## 4. KESIMPULAN

Integrasi Payment Gateway Midtrans pada aplikasi kasir Toko POS berbasis Laravel 12 telah berhasil diimplementasikan secara menyeluruh. Alur sistem berjalan mulai dari pembuatan transaksi di sistem kasir, pembuatan *Snap Token*, rendering antarmuka pembayaran pop-up Snap dengan berbagai metode (QRIS, VA, Kartu Kredit), penanganan webhook callback dengan validasi SHA-512 *Signature Key*, hingga pembaruan status transaksi menjadi *Settlement* (*Paid*) dan pencetakan struk bukti pembayaran. 

Penerapan Service Layer dan pengecualian CSRF pada `bootstrap/app.php` di Laravel 12 menghasilkan struktur kode yang modular, aman, andal, dan siap diterapkan dalam skala produksi.

---

## 5. DAFTAR PUSTAKA

1. Politeknik Negeri Jember. *BKPM – Workshop Sistem Informasi Web Framework, Acara 37–38: Payment Gateway Midtrans*. Jember: D4 Teknik Informatika, 2026.
2. Midtrans. *Midtrans Technical Documentation & Snap API Reference*. https://docs.midtrans.com/
3. Midtrans. *Midtrans PHP Library (Official)*. https://github.com/Midtrans/midtrans-php
4. Laravel. *Laravel 12.x Documentation: Routing, Middleware, and HTTP Tests*. https://laravel.com/docs

---

## LAMPIRAN: HASIL OUTPUT PRAKTIKUM

| No. | Gambar | Langkah Kerja / Keterangan Output |
| :---: | :--- | :--- |
| **1** | `Tugas Konfigurasi Midtrans/1.png` | **Instalasi Midtrans PHP SDK:** Menjalankan perintah `composer require midtrans/midtrans-php` untuk mengunduh dan mendaftarkan library resmi Midtrans ke dependensi proyek Laravel. |
| **2** | `Tugas Konfigurasi Midtrans/2.png` | **Konfigurasi Environment (.env):** Menambahkan kredensial akun Midtrans Sandbox (`MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, mode non-produksi, sanitasi, dan 3DS). |
| **3** | `Tugas Konfigurasi Midtrans/3.png` | **File Konfigurasi config/midtrans.php:** Mendaftarkan array konfigurasi aplikasi untuk membaca parameter kredensial Midtrans dari variabel environment `.env`. |
| **4** | `Tugas Konfigurasi Midtrans/4.png` | **Implementasi Webhook (MidtransController.php):** Method `notification()` untuk menerima callback HTTP POST dari Midtrans, memverifikasi SHA-512 signature key, dan memperbarui status pesanan menjadi lunas (*paid*). |
| **5** | `Tugas Konfigurasi Midtrans/5.png` | **Routing Aplikasi (routes/web.php):** Mendaftarkan endpoint POS kasir dan endpoint publik webhook callback `POST /midtrans/notification`. |
| **6** | `Tugas Konfigurasi Midtrans/6.png` | **Antarmuka Midtrans Snap Pop-up:** Tampilan form pembayaran popup saat kasir memilih checkout via Midtrans, menampilkan opsi GoPay QRIS, Virtual Account, dan Kartu Kredit. |
| **7** | `Tugas Konfigurasi Midtrans/7.png` | **Pembayaran QRIS:** Tampilan QR Code dinamis QRIS senilai Rp 65.000 untuk invoice `#INV-QXQZWRGE` pada antarmuka Snap. |
| **8** | `Tugas Konfigurasi Midtrans/8.png` | **Status Pembayaran Sukses:** Notifikasi *"Payment successful"* pada popup Snap setelah pembayaran berhasil diverifikasi melalui simulator. |
| **9** | `Tugas Konfigurasi Midtrans/9.png` | **Modal Struk Pembayaran POS:** Tampilan struk kasir dengan keterangan metode "Midtrans" dan status *"Struk berhasil dikirim ke printer"* setelah transaksi diselesaikan. |
| **10** | `Tugas Konfigurasi Midtrans/10.png` | **Verifikasi Dashboard Midtrans:** Halaman Transactions pada Dashboard Midtrans Sandbox yang menunjukkan transaksi `#INV-QXQZWRGE` berhasil tercatat dengan status **Settlement**. |
