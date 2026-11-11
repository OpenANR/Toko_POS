# LAPORAN PRAKTIKUM

## ACARA 35–36: LARAVEL PRINT MENGGUNAKAN LIBRARY MIKE42 ESC/POS

**Mata Kuliah:** Workshop Sistem Informasi Web Framework
**Program Studi:** D4 Teknik Informatika — Politeknik Negeri Jember

**Disusun oleh:**

| Identitas | Keterangan |
| --- | --- |
| Nama | ................................ |
| NIM | ................................ |
| Golongan | ................................ |
| Tugas Minggu Ke- | 11 (Acara 35–36) |

---

## 1. PENDAHULUAN

### 1.1 Latar Belakang

Sistem Point of Sale (POS) tidak cukup hanya mencatat transaksi, tetapi juga perlu
menghasilkan bukti transaksi berupa struk. Pada praktikum ini dikembangkan fitur
pencetakan struk otomatis pada aplikasi POS **Toko Kopi Kita** berbasis Laravel.
Pencetakan dilakukan sepenuhnya di sisi backend PHP menggunakan library
**Mike42 ESC/POS PHP** (`mike42/escpos-php`), bukan melalui fitur `window.print()`
pada browser.

Library Mike42 adalah library open-source PHP untuk berkomunikasi dengan printer
thermal melalui protokol **ESC/POS** (*Epson Standard Code for Point of Sale*).
Protokol ini didukung oleh banyak printer struk seperti Epson, XPrinter, dan
POS-58. Library ini mampu mencetak teks, mengatur perataan dan ketebalan huruf,
mencetak barcode/QR Code, serta memotong kertas secara otomatis.

### 1.2 Tujuan Praktikum

1. Menjelaskan konsep dasar Laravel Print.
2. Mengimplementasikan konfigurasi Laravel Print.
3. Memahami proses pencetakan struk dari aplikasi Laravel ke printer.
4. Mengimplementasikan library Mike42 / ESC/POS pada sistem POS.

### 1.3 Alat dan Bahan

1. PC/Laptop (RAM minimal 4 GB, disarankan 8 GB).
2. Windows 10/Linux.
3. PHP, Composer, dan Framework Laravel.
4. Library `mike42/escpos-php`.
5. Printer thermal USB/LAN (opsional; dapat disimulasikan dengan printer PDF).
6. Kertas A4/Folio dan pulpen.

---

## 2. HASIL PRAKTIK

### 2.1 Instalasi Library

Library dipasang melalui Composer dengan perintah:

```bash
composer require mike42/escpos-php
```

Perintah tersebut menambahkan dependensi ke `composer.json`, memperbarui
`composer.lock`, dan menjalankan `package:discover`. Hasil instalasi ditunjukkan
pada gambar **1.png**.

### 2.2 Konfigurasi Printer

Konfigurasi dibuat pada `config/printing.php` dan dapat diubah melalui `.env`
agar kode tidak perlu diedit saat berpindah printer:

```env
PRINT_DRIVER=file
PRINT_PRINTER_NAME=POS-58
PRINT_STORE_NAME="Kopi Kita"
PRINT_STORE_ADDRESS="Jl. Raya Kampus No. 123"
PRINT_PAPER_WIDTH=32
```

Driver yang didukung:

| Driver | Keterangan |
| --- | --- |
| `file` | Simulasi tanpa printer fisik, hasil ditulis ke `storage/logs/struk.txt` |
| `windows` | `WindowsPrintConnector`, memakai nama printer di Control Panel |
| `cups` | `CupsPrintConnector`, untuk printer yang terpasang di Linux/CUPS |
| `network` | `NetworkPrintConnector`, printer LAN melalui `IP:port` (default 9100) |

Lebar kertas `32` karakter digunakan untuk printer 58 mm, sedangkan `48`
karakter untuk printer 80 mm.

### 2.3 Implementasi Service Pencetakan

Logika cetak dipisahkan ke dalam `app/Services/ReceiptPrinter.php` agar dapat
digunakan ulang oleh checkout maupun cetak ulang. Method utama:

- `print(Transaction $transaction)` — mengirim struk ke printer dan mengembalikan
  status `printed`, teks `receipt`, dan `error`.
- `render(Transaction $transaction)` — menyusun teks struk yang sama untuk
  preview di browser.
- `makeConnector()` — memilih konektor sesuai `PRINT_DRIVER`.
- `twoColumns()` — meratakan teks kiri dan nominal kanan sesuai lebar kertas.

Potongan implementasi utama:

```php
$printer = new Printer($this->makeConnector());

// Header toko (rata tengah, huruf lebar ganda)
$printer->setJustification(Printer::JUSTIFY_CENTER);
$printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
$printer->text($storeName."\n");
$printer->selectPrintMode();

// Informasi transaksi
$printer->setJustification(Printer::JUSTIFY_LEFT);
$printer->text('No  : '.$transaction->invoice_number."\n");
$printer->text('Tgl: '.$this->printedAt($transaction)."\n");

// Daftar item
foreach ($this->items($transaction) as $item) {
    $printer->text($item['name']."\n");
    $printer->text($this->twoColumns(
        sprintf('  %dx %s', $item['quantity'], $this->money($item['price'])),
        $this->money($item['subtotal'])
    )."\n");
}

// Ringkasan pembayaran
$printer->text($this->twoColumns('Subtotal', 'Rp '.$this->money($transaction->subtotal))."\n");
$printer->text($this->twoColumns('Diskon', '-Rp '.$this->money($transaction->discount))."\n");
$printer->setEmphasis(true);
$printer->text($this->twoColumns('Total Bayar', 'Rp '.$this->money($transaction->total_price))."\n");
$printer->setEmphasis(false);

$printer->cut();
$printer->close();
```

Jika printer gagal dihubungi, kesalahan dicatat melalui `Log::error()` dan
transaksi **tetap dianggap berhasil**. Dengan demikian, kegagalan printer tidak
membatalkan transaksi yang sudah tersimpan.

### 2.4 Implementasi Controller dan Route

`ReceiptController` menyediakan struk dummy untuk pengujian library:

```php
Route::get('/print-receipt', [ReceiptController::class, 'print']);
```

`PosController@checkout` menyimpan transaksi terlebih dahulu, kemudian memanggil
`ReceiptPrinter->print()`. `PosController@reprint` digunakan untuk mencetak ulang.
Route yang digunakan:

```php
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
Route::post('/pos/receipt/{transaction}/print', [PosController::class, 'reprint'])
    ->name('pos.receipt.reprint');
```

Pada halaman `/pos`, setelah transaksi berhasil muncul modal **Struk Pembayaran**
berisi preview struk dan tombol **Cetak Ulang ke Printer**. Preview berasal dari
teks yang sama dengan yang dikirim ke printer, sehingga tampilannya konsisten
(gambar **4.png**).

### 2.5 Pengujian

Pengujian dilakukan dengan dua cara:

1. **Manual:** membuka `http://localhost:8000/pos`, melakukan checkout tunai,
   lalu memastikan modal struk menampilkan status *"Struk berhasil dikirim ke
   printer."* Hasil cetak simulasi dapat dilihat pada `storage/logs/struk.txt`.
2. **Otomatis:** menjalankan `php artisan test --filter=ReceiptAndDiscountTest`.

Hasil pengujian otomatis:

```text
PASS  Tests\Feature\ReceiptAndDiscountTest
✓ pos page is accessible
✓ print receipt route prints with discount
✓ pos checkout with discount and struk printing
✓ pos page does not use browser printing
✓ reprint route prints receipt from backend

Tests: 5 passed (29 assertions)
```

Contoh struk yang dihasilkan:

```text
Kopi Kita
Jl. Raya Kampus No. 123
--------------------------------
No  : INV-MDMR4KX9
Tgl: 11/11/2026 12:23:13
Metode: Tunai
--------------------------------
Arabika Gayo Premium 250g
  1x 85.000               85.000
Arabika Kintamani 250g
  1x 90.000               90.000
--------------------------------
Subtotal              Rp 175.000
Diskon                -Rp 35.000
Total Bayar           Rp 140.000
Uang Bayar            Rp 200.000
Kembalian              Rp 60.000
--------------------------------
Terima Kasih Atas Kunjungan Anda!
```

### 2.6 Pembahasan

1. **Pencetakan di backend** membuat struk tetap tercetak walaupun browser
   menutup halaman, dan menghindari dialog cetak browser.
2. **Abstraksi konektor** memungkinkan aplikasi yang sama berjalan dengan printer
   USB Windows, CUPS Linux, printer jaringan, atau simulasi file hanya dengan
   mengubah `.env`.
3. **Format dua kolom** (`twoColumns`) menghitung lebar karakter sehingga harga
   tetap rata kanan pada kertas 58 mm maupun 80 mm.
4. **Penanganan error** bersifat *non-blocking*: transaksi dan pengurangan stok
   sudah tersimpan sebelum pencetakan, sehingga kegagalan printer tidak
   merusak data penjualan.
5. **Diskon** dihitung sebelum total bayar dan dicetak secara eksplisit, sesuai
   ketentuan studi kasus Acara 36.

---

## 3. KESIMPULAN

Library **Mike42 ESC/POS PHP** berhasil diimplementasikan pada aplikasi POS
Toko Kopi Kita untuk mencetak struk secara otomatis dari backend Laravel.
Pencetakan mencakup nama toko, tanggal dan waktu, daftar item beserta kuantitas
dan harga, subtotal, diskon, total pembayaran, uang bayar, kembalian, serta
pemotongan kertas otomatis. Library ini mendukung berbagai jenis koneksi
(Windows, CUPS/Linux, jaringan, dan file) sehingga fleksibel digunakan dengan
printer thermal nyata maupun simulasi printer PDF.

Pemisahan logika cetak ke dalam service `ReceiptPrinter`, konfigurasi melalui
`config/printing.php` dan `.env`, serta penanganan error yang tidak membatalkan
transaksi membuat implementasi lebih rapi, mudah diuji, dan aman digunakan.
Dengan demikian, tujuan pembelajaran Acara 35–36 — memahami konsep, konfigurasi,
proses, dan implementasi Laravel Print menggunakan Mike42/ESC-POS — telah
tercapai.

---

## 4. DAFTAR PUSTAKA

1. Politeknik Negeri Jember. *BKPM – Workshop Sistem Informasi Web Framework,
   Acara 35–36: Laravel Print*. Jember: D4 Teknik Informatika, 2026.
2. Mike42. *escpos-php: PHP library for printing to ESC/POS-compatible thermal
   and dot-matrix printers*. https://github.com/mike42/escpos-php
3. Laravel. *Laravel Documentation*. https://laravel.com/docs
4. Epson. *ESC/POS Command Reference*. https://download4.epson.biz/sec_pubs/pos/reference_en/

---

## Lampiran

| Gambar | Keterangan |
| --- | --- |
| `Tugas Library POS Print Laravel/1.png` | Proses instalasi `mike42/escpos-php` |
| `Tugas Library POS Print Laravel/2.png` | Implementasi `ReceiptPrinter` |
| `Tugas Library POS Print Laravel/3.png` | Implementasi `ReceiptController` |
| `Tugas Library POS Print Laravel/4.png` | Hasil cetak struk pada halaman POS |
| `Tugas Library POS Print Laravel/5.png` | Route POS dan cetak ulang struk |
