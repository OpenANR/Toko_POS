<?php

namespace App\Services;

use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class ReceiptPrinter
{
    /**
     * Cetak struk transaksi melalui library Mike42 ESC/POS.
     *
     * Pencetakan dilakukan sepenuhnya di backend PHP. Jika printer gagal
     * dihubungi, transaksi tetap dianggap berhasil dan error hanya dicatat.
     *
     * @return array{printed: bool, receipt: string, error: ?string}
     */
    public function print(Transaction $transaction, ?string $storeName = null): array
    {
        $storeName = $storeName ?? $this->storeName();
        $receipt = $this->render($transaction, $storeName);
        $printer = null;

        try {
            $printer = new Printer($this->makeConnector());

            // Header toko
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text($storeName."\n");
            $printer->selectPrintMode();
            $printer->text($this->storeAddress()."\n");
            $printer->text($this->separator()."\n");

            // Informasi transaksi
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text('No  : '.$transaction->invoice_number."\n");
            $printer->text('Tgl: '.$this->printedAt($transaction)."\n");
            $printer->text('Metode: '.$this->paymentMethod($transaction)."\n");
            $printer->text($this->separator()."\n");

            // Daftar item
            foreach ($this->items($transaction) as $item) {
                $printer->text($item['name']."\n");
                $printer->text($this->twoColumns(
                    sprintf('  %dx %s', $item['quantity'], $this->money($item['price'])),
                    $this->money($item['subtotal'])
                )."\n");
            }

            $printer->text($this->separator()."\n");

            // Ringkasan pembayaran
            $printer->text($this->twoColumns('Subtotal', 'Rp '.$this->money($transaction->subtotal))."\n");
            $printer->text($this->twoColumns('Diskon', '-Rp '.$this->money($transaction->discount))."\n");

            $printer->setEmphasis(true);
            $printer->text($this->twoColumns('Total Bayar', 'Rp '.$this->money($transaction->total_price))."\n");
            $printer->setEmphasis(false);

            $printer->text($this->twoColumns('Uang Bayar', 'Rp '.$this->money($transaction->pay_amount))."\n");
            $printer->text($this->twoColumns('Kembalian', 'Rp '.$this->money($transaction->change_amount))."\n");
            $printer->text($this->separator()."\n");

            // Footer
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Terima Kasih Atas Kunjungan Anda!\n\n\n");

            $printer->cut();
            $printer->close();
            $printer = null;

            return [
                'printed' => true,
                'receipt' => $receipt,
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Gagal mencetak struk: '.$e->getMessage());

            return [
                'printed' => false,
                'receipt' => $receipt,
                'error' => $e->getMessage(),
            ];
        } finally {
            if ($printer !== null) {
                try {
                    $printer->close();
                } catch (Exception $e) {
                    Log::warning('Gagal menutup koneksi printer: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Bentuk struk dalam teks biasa (dipakai untuk preview di halaman POS).
     *
     * Formatting dilakukan di PHP, bukan JavaScript, sehingga tampilan struk
     * sama dengan data yang dikirim ke printer.
     */
    public function render(Transaction $transaction, ?string $storeName = null): string
    {
        $storeName = $storeName ?? $this->storeName();

        $lines = [
            $storeName,
            $this->storeAddress(),
            $this->separator(),
            'No  : '.$transaction->invoice_number,
            'Tgl: '.$this->printedAt($transaction),
            'Metode: '.$this->paymentMethod($transaction),
            $this->separator(),
        ];

        foreach ($this->items($transaction) as $item) {
            $lines[] = $item['name'];
            $lines[] = $this->twoColumns(
                sprintf('  %dx %s', $item['quantity'], $this->money($item['price'])),
                $this->money($item['subtotal'])
            );
        }

        $lines[] = $this->separator();
        $lines[] = $this->twoColumns('Subtotal', 'Rp '.$this->money($transaction->subtotal));
        $lines[] = $this->twoColumns('Diskon', '-Rp '.$this->money($transaction->discount));
        $lines[] = $this->twoColumns('Total Bayar', 'Rp '.$this->money($transaction->total_price));
        $lines[] = $this->twoColumns('Uang Bayar', 'Rp '.$this->money($transaction->pay_amount));
        $lines[] = $this->twoColumns('Kembalian', 'Rp '.$this->money($transaction->change_amount));
        $lines[] = $this->separator();
        $lines[] = 'Terima Kasih Atas Kunjungan Anda!';

        return implode("\n", $lines)."\n";
    }

    /**
     * Buat konektor sesuai konfigurasi di config/printing.php.
     */
    private function makeConnector(): PrintConnector
    {
        $driver = strtolower((string) config('printing.driver', 'file'));
        $printerName = (string) config('printing.printer_name', 'POS-58');

        return match ($driver) {
            'windows' => new WindowsPrintConnector($printerName),
            'cups' => new CupsPrintConnector($printerName),
            'network' => new NetworkPrintConnector(
                (string) config('printing.network_host'),
                (int) config('printing.network_port', 9100)
            ),
            default => $this->makeFileConnector(),
        };
    }

    /**
     * FilePrintConnector untuk simulasi printer (mis. printer PDF / tanpa printer fisik).
     */
    private function makeFileConnector(): FilePrintConnector
    {
        $path = (string) config('printing.file_path', storage_path('logs/struk.txt'));
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return new FilePrintConnector($path);
    }

    /**
     * @return array<int, array{name: string, quantity: int, price: float, subtotal: float}>
     */
    private function items(Transaction $transaction): array
    {
        $items = [];

        foreach ((array) $transaction->items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $items[] = [
                'name' => (string) ($item['name'] ?? '-'),
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => (float) ($item['subtotal'] ?? ($price * $quantity)),
            ];
        }

        return $items;
    }

    private function printedAt(Transaction $transaction): string
    {
        return ($transaction->created_at ?? now())->format('d/m/Y H:i:s');
    }

    private function paymentMethod(Transaction $transaction): string
    {
        return $transaction->payment_method === 'midtrans' ? 'Midtrans' : 'Tunai';
    }

    private function storeName(): string
    {
        return (string) config('printing.store_name', 'Kopi Kita');
    }

    private function storeAddress(): string
    {
        return (string) config('printing.store_address', 'Jl. Raya Kampus No. 123');
    }

    private function separator(): string
    {
        return str_repeat('-', $this->paperWidth());
    }

    private function paperWidth(): int
    {
        return max(20, (int) config('printing.paper_width', 32));
    }

    private function money(float|int|string|null $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    /**
     * Rata kiri + rata kanan sesuai lebar kertas.
     */
    private function twoColumns(string $left, string $right): string
    {
        $width = $this->paperWidth();
        $rightWidth = mb_strwidth($right);
        $leftWidth = max(1, $width - $rightWidth);

        $left = mb_strwidth($left) > $leftWidth
            ? mb_substr($left, 0, $leftWidth)
            : $left.str_repeat(' ', max(0, $leftWidth - mb_strwidth($left)));

        return $left.$right;
    }
}
