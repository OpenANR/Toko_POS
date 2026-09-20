<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
        $product = Product::all();
        $category = Category::get();
        return view('products.index', compact('product', 'category'));
    }
}
