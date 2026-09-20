<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
Route::get('/product', [ProductController::class, 'index'])->name('product.index');
Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
