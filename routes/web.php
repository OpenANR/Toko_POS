<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
Route::get('/product', [ProductController::class, 'index'])->name('product.index');
Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');

Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
Route::post('/pos/payment/{transaction}/cancel', [PosController::class, 'cancelPayment'])->name('pos.payment.cancel');
Route::post('/pos/receipt/{transaction}/print', [PosController::class, 'reprint'])->name('pos.receipt.reprint');

// Callback/webhook dari server Midtrans (dikecualikan dari CSRF).
Route::post('/midtrans/notification', [MidtransController::class, 'notification'])->name('midtrans.notification');
Route::get('/print-receipt', [ReceiptController::class, 'print']);