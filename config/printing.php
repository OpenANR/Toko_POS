<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konektor Printer (Mike42 ESC/POS)
    |--------------------------------------------------------------------------
    |
    | Pilihan driver:
    | - file    : simulasi tanpa printer fisik (menulis ke file .txt)
    | - windows : WindowsPrintConnector (nama printer di Control Panel)
    | - cups    : CupsPrintConnector (printer terpasang di Linux/CUPS)
    | - network : NetworkPrintConnector (printer LAN via IP:port)
    |
    */

    'driver' => env('PRINT_DRIVER', 'file'),

    'printer_name' => env('PRINT_PRINTER_NAME', 'POS-58'),

    'file_path' => env('PRINT_FILE_PATH', storage_path('logs/struk.txt')),

    'network_host' => env('PRINT_NETWORK_HOST', '192.168.1.100'),

    'network_port' => (int) env('PRINT_NETWORK_PORT', 9100),

    /*
    |--------------------------------------------------------------------------
    | Identitas Toko
    |--------------------------------------------------------------------------
    */

    'store_name' => env('PRINT_STORE_NAME', 'Kopi Kita'),

    'store_address' => env('PRINT_STORE_ADDRESS', 'Jl. Raya Kampus No. 123'),

    /*
    |--------------------------------------------------------------------------
    | Lebar Kertas
    |--------------------------------------------------------------------------
    |
    | 32 karakter untuk printer 58mm, 48 karakter untuk printer 80mm.
    |
    */

    'paper_width' => (int) env('PRINT_PAPER_WIDTH', 32),

];
