<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() {
        $supplier = Supplier::all();
        return view('supplier.index', compact('supplier'));
    }
}
