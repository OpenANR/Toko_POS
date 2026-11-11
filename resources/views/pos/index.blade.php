<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aplikasi Kasir POS - Sistem Cetak Struk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Kasir POS - Kopi Kita</h3>
                <small class="text-muted">Sistem Pencetakan Struk Otomatis (Mike42 ESC/POS)</small>
            </div>
            <div>
                <a href="{{ url('/product') }}" class="btn btn-outline-secondary btn-sm me-2">Kelola Produk</a>
                <a href="{{ url('/print-receipt') }}" target="_blank" class="btn btn-outline-info btn-sm">Test Struk Dummy</a>
            </div>
        </div>

        <div class="row">
            <!-- Daftar Produk -->
            <div class="col-md-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Daftar Produk</h5>
                        <span class="badge bg-light text-primary">{{ count($products) }} Produk Tersedia</span>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            @forelse ($products as $product)
                                <div class="col">
                                    <div class="card h-100 text-center p-2 shadow-sm">
                                        <div class="card-body d-flex flex-column justify-content-between">
                                            <div>
                                                <h6 class="card-title fw-bold mb-1">{{ $product->name }}</h6>
                                                <p class="text-muted small mb-1">SKU: {{ $product->sku }}</p>
                                                <p class="fw-bold text-success fs-6 mb-1">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </p>
                                                <small class="d-block mb-2 text-muted">Stok: {{ $product->stock }}</small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary w-100"
                                                onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, {{ $product->stock }})">
                                                + Tambah ke Keranjang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-4 text-muted">
                                    Tidak ada produk yang memiliki stok tersedia.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keranjang & Bayar -->
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Keranjang Belanja</h5>
                        <button class="btn btn-sm btn-outline-light" onclick="clearCart()">Kosongkan</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Produk</th>
                                        <th width="85" class="text-center">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                        <th width="30"></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-table">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-3">

                        <!-- Subtotal -->
                        <div class="d-flex justify-content-between fs-6 text-muted mb-2">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal" class="fw-semibold">Rp 0</span>
                        </div>

                        <!-- Input Diskon -->
                        <div class="mb-3">
                            <label for="discount_amount" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span>Diskon (Rp):</span>
                                <span class="badge bg-warning text-dark" id="discount-badge">0%</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="discount_amount" value="0" min="0" placeholder="0" oninput="calculateTotals()">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Pilih Diskon
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header">Diskon Persen (%)</h6></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountPercent(0)">0% (Tanpa Diskon)</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountPercent(5)">5%</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountPercent(10)">10%</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountPercent(20)">20%</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountPercent(50)">50%</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Diskon Nominal (Rp)</h6></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountNominal(2000)">Rp 2.000</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountNominal(5000)">Rp 5.000</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountNominal(10000)">Rp 10.000</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountNominal(20000)">Rp 20.000</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="setDiscountNominal(50000)">Rp 50.000</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Total Pembayaran -->
                        <div class="d-flex justify-content-between fs-4 fw-bold mb-3 text-success p-2 bg-light rounded border">
                            <span>Total Pembayaran:</span>
                            <span id="grand-total">Rp 0</span>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Metode Pembayaran</label>
                            <div class="btn-group w-100" role="group" aria-label="Metode pembayaran">
                                <input type="radio" class="btn-check" name="payment_method" id="payment-cash" value="cash" checked onchange="updatePaymentMethod()">
                                <label class="btn btn-outline-primary" for="payment-cash">Tunai</label>

                                <input type="radio" class="btn-check" name="payment_method" id="payment-midtrans" value="midtrans" onchange="updatePaymentMethod()">
                                <label class="btn btn-outline-primary" for="payment-midtrans">
                                    Midtrans (QRIS / E-Wallet / VA)
                                    @if (! config('midtrans.isProduction'))
                                        <span class="badge bg-warning text-dark ms-1">Sandbox</span>
                                    @endif
                                </label>
                            </div>
                        </div>

                        <!-- Input Uang Bayar (khusus tunai) -->
                        <div id="cash-payment-fields">
                            <div class="mb-3">
                                <label for="pay_amount" class="form-label fw-bold">Jumlah Uang Bayar (Rp):</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="pay_amount" placeholder="0" oninput="calculateTotals()">
                                </div>
                                <!-- Shortcut tombol uang pas / nominal umum -->
                                <div class="d-flex gap-1 mt-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExactPay()">Uang Pas</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPay(10000)">+10rb</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPay(20000)">+20rb</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPay(50000)">+50rb</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPay(100000)">+100rb</button>
                                </div>
                            </div>

                            <!-- Kembalian -->
                            <div class="d-flex justify-content-between fs-6 fw-semibold mb-3 text-secondary p-2 bg-light rounded">
                                <span>Kembalian:</span>
                                <span id="change-amount">Rp 0</span>
                            </div>
                        </div>

                        <!-- Info pembayaran Midtrans -->
                        <div id="midtrans-payment-note" class="alert alert-info d-none">
                            <small>
                                Pembayaran diproses melalui Midtrans Snap. Setelah tombol ditekan,
                                pilih metode pembayaran (QRIS, e-wallet, virtual account, dll).
                            </small>
                            @if (! config('midtrans.isProduction'))
                                <hr class="my-2">
                                <small class="text-danger fw-semibold d-block">
                                    Mode sandbox: JANGAN scan QR dengan aplikasi e-wallet/bank asli.
                                    Gunakan simulator Midtrans agar tidak ada saldo yang terpotong.
                                </small>
                            @endif
                        </div>

                        <button id="checkout-btn" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" onclick="processCheckout()">
                            Bayar & Cetak Struk
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Struk (preview hasil cetak backend Mike42) -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Struk Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="print-status" class="alert d-none mb-2" role="alert"></div>
                    <div class="bg-light border rounded p-2">
                        <pre id="receipt-content" class="font-monospace small mb-0" style="white-space: pre;"></pre>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Struk dicetak otomatis dari backend PHP menggunakan library Mike42 ESC/POS.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="reprint-btn" onclick="reprintReceipt()">Cetak Ulang ke Printer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ config('midtrans.isProduction') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('midtrans.clientKey') }}"></script>
    <script>
        let cart = [];

        function addToCart(id, name, price, stock) {
            let item = cart.find(i => i.product_id === id);
            if (item) {
                if (item.quantity + 1 > stock) {
                    alert('Stok tidak mencukupi!');
                    return;
                }
                item.quantity++;
            } else {
                cart.push({
                    product_id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    stock: stock
                });
            }
            renderCart();
        }

        function updateQty(id, qty) {
            let item = cart.find(i => i.product_id === id);
            if (item) {
                let newQty = parseInt(qty) || 0;
                if (newQty > item.stock) {
                    alert('Stok tidak mencukupi!');
                    renderCart();
                    return;
                }
                if (newQty <= 0) {
                    removeFromCart(id);
                } else {
                    item.quantity = newQty;
                    renderCart();
                }
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(i => i.product_id !== id);
            renderCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm('Yakin ingin mengosongkan keranjang?')) {
                cart = [];
                renderCart();
            }
        }

        function getSubtotal() {
            return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        }

        function getDiscount() {
            let val = parseFloat(document.getElementById('discount_amount').value) || 0;
            return Math.max(0, val);
        }

        function setDiscountPercent(percent) {
            let subtotal = getSubtotal();
            let disc = Math.round((subtotal * percent) / 100);
            document.getElementById('discount_amount').value = disc;
            calculateTotals();
        }

        function setDiscountNominal(nominal) {
            document.getElementById('discount_amount').value = nominal;
            calculateTotals();
        }

        function setExactPay() {
            let subtotal = getSubtotal();
            let discount = getDiscount();
            let total = Math.max(0, subtotal - discount);
            document.getElementById('pay_amount').value = total;
            calculateTotals();
        }

        function addPay(nominal) {
            let current = parseFloat(document.getElementById('pay_amount').value) || 0;
            document.getElementById('pay_amount').value = current + nominal;
            calculateTotals();
        }

        function renderCart() {
            let tbody = document.getElementById('cart-table');
            tbody.innerHTML = '';

            if (cart.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Keranjang kosong</td></tr>';
            } else {
                cart.forEach(item => {
                    let subtotal = item.price * item.quantity;
                    tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="fw-semibold">${escapeHtml(item.name)}</div>
                            <small class="text-muted">@ ${rupiah(item.price)}</small>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-center" value="${item.quantity}" min="1" max="${item.stock}" onchange="updateQty(${item.product_id}, this.value)">
                        </td>
                        <td class="text-end fw-semibold">${rupiah(subtotal)}</td>
                        <td class="text-center">
                            <button class="btn btn-outline-danger btn-sm p-0 px-1" title="Hapus" onclick="removeFromCart(${item.product_id})">&times;</button>
                        </td>
                    </tr>
                    `;
                });
            }

            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = getSubtotal();
            let discountInput = document.getElementById('discount_amount');
            let discount = parseFloat(discountInput.value) || 0;

            if (discount < 0) {
                discount = 0;
                discountInput.value = 0;
            }

            if (subtotal > 0 && discount > subtotal) {
                discount = subtotal;
                discountInput.value = subtotal;
            }

            let totalPembayaran = Math.max(0, subtotal - discount);
            let pay = parseFloat(document.getElementById('pay_amount').value) || 0;
            let change = pay - totalPembayaran;

            // Update label persen diskon
            let percent = subtotal > 0 ? Math.round((discount / subtotal) * 100) : 0;
            document.getElementById('discount-badge').innerText = percent + '%';

            document.getElementById('cart-subtotal').innerText = rupiah(subtotal);
            document.getElementById('grand-total').innerText = rupiah(totalPembayaran);
            document.getElementById('change-amount').innerText = rupiah(change > 0 ? change : 0);
        }

        function getPaymentMethod() {
            return document.querySelector('input[name="payment_method"]:checked').value;
        }

        function updatePaymentMethod() {
            let method = getPaymentMethod();
            let cashFields = document.getElementById('cash-payment-fields');
            let note = document.getElementById('midtrans-payment-note');
            let button = document.getElementById('checkout-btn');

            if (method === 'midtrans') {
                cashFields.classList.add('d-none');
                note.classList.remove('d-none');
                button.textContent = 'Bayar dengan Midtrans';
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            } else {
                cashFields.classList.remove('d-none');
                note.classList.add('d-none');
                button.textContent = 'Bayar & Cetak Struk';
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');
            }
        }

        function resetCart() {
            cart = [];
            document.getElementById('pay_amount').value = '';
            document.getElementById('discount_amount').value = '0';
            renderCart();
        }

        function processCheckout() {
            let subtotal = getSubtotal();
            let discount = getDiscount();
            let totalPembayaran = Math.max(0, subtotal - discount);
            let payAmount = parseFloat(document.getElementById('pay_amount').value) || 0;
            let paymentMethod = getPaymentMethod();

            if (cart.length === 0) {
                alert('Keranjang masih kosong! Silakan pilih produk terlebih dahulu.');
                return;
            }

            if (paymentMethod === 'cash' && payAmount < totalPembayaran) {
                alert('Uang pembayaran kurang! Total pembayaran adalah ' + rupiah(totalPembayaran));
                return;
            }

            fetch('/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        items: cart,
                        discount: discount,
                        pay_amount: paymentMethod === 'cash' ? payAmount : totalPembayaran,
                        payment_method: paymentMethod
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Gagal: ' + data.message);
                        return;
                    }

                    if (data.payment_method === 'midtrans') {
                        payWithMidtrans(data);
                    } else {
                        resetCart();
                        showReceipt(data);
                    }
                })
                .catch(err => alert('Terjadi kesalahan koneksi!'));
        }

        function payWithMidtrans(payload) {
            if (typeof window.snap === 'undefined') {
                alert('Snap.js Midtrans gagal dimuat. Periksa koneksi internet dan Client Key.');
                return;
            }

            window.snap.pay(payload.snap_token, {
                onSuccess: function(result) {
                    printMidtransReceipt(payload.data.id);
                },
                onPending: function(result) {
                    alert('Menunggu pembayaran. Selesaikan pembayaran sesuai instruksi Midtrans.');
                    resetCart();
                    location.reload();
                },
                onError: function(result) {
                    alert('Pembayaran gagal! Silakan coba lagi.');
                    cancelMidtransPayment(payload.data.id);
                },
                onClose: function() {
                    // Popup ditutup sebelum lunas: transaksi tetap pending,
                    // stok dikembalikan otomatis saat Midtrans mengirim expire.
                }
            });
        }

        // Setelah Snap melaporkan sukses, struk tetap dicetak dari backend Mike42.
        function printMidtransReceipt(transactionId) {
            currentTransactionId = transactionId;

            fetch('/pos/receipt/' + transactionId + '/print', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(payload => {
                    resetCart();
                    showReceipt(payload, transactionId);
                })
                .catch(err => {
                    alert('Pembayaran berhasil, tetapi struk gagal dimuat.');
                    location.reload();
                });
        }

        function cancelMidtransPayment(transactionId) {
            fetch('/pos/payment/' + transactionId + '/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .finally(() => location.reload());
        }

        function rupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function(char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [char];
            });
        }

        let currentTransactionId = null;

        // Struk dirender oleh backend PHP (ReceiptPrinter), bukan oleh JavaScript.
        function showReceipt(payload, transactionId = null) {
            let transaction = payload.data;
            currentTransactionId = transactionId || (transaction ? transaction.id : currentTransactionId);

            document.getElementById('receipt-content').textContent = payload.receipt || 'Struk tidak tersedia.';
            setPrintStatus(payload.printed, payload.print_error);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();
        }

        function setPrintStatus(printed, error) {
            let status = document.getElementById('print-status');
            status.classList.remove('d-none', 'alert-success', 'alert-warning', 'alert-danger');

            if (printed) {
                status.classList.add('alert', 'alert-success');
                status.textContent = 'Struk berhasil dikirim ke printer.';
            } else {
                status.classList.add('alert', 'alert-warning');
                status.textContent = 'Transaksi tersimpan, tetapi struk gagal dicetak.' + (error ? ' (' + error + ')' : '');
            }
        }

        // Cetak ulang tetap diproses backend memakai Mike42 (bukan cetak browser).
        function reprintReceipt() {
            if (!currentTransactionId) {
                alert('Transaksi tidak ditemukan.');
                return;
            }

            let button = document.getElementById('reprint-btn');
            button.disabled = true;
            button.textContent = 'Mencetak...';

            fetch('/pos/receipt/' + currentTransactionId + '/print', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.receipt) {
                        document.getElementById('receipt-content').textContent = data.receipt;
                    }
                    setPrintStatus(data.printed, data.print_error);
                })
                .catch(err => setPrintStatus(false, 'Koneksi ke server gagal.'))
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Cetak Ulang ke Printer';
                });
        }

        // Setelah struk ditutup, reload halaman agar stok produk ikut diperbarui
        document.getElementById('receiptModal').addEventListener('hidden.bs.modal', function() {
            location.reload();
        });

        // Inisialisasi awal
        updatePaymentMethod();
        renderCart();
    </script>
</body>

</html>
