<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::view('/products', 'products.index');
Route::view('/products/low-stock', 'products.low-stock');
Route::view('/orders/create', 'orders.create');
Route::view('/orders/history', 'orders.history');
