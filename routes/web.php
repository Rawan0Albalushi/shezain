<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\AuthController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\ContactController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\LocaleController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\PaymentWebhookController;
use App\Http\Controllers\Store\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', LocaleController::class)->name('locale');
Route::get('/', HomeController::class)->name('home');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/checkout/return/{order:public_id}', [CheckoutController::class, 'returned'])->name('checkout.return');
Route::get('/checkout/cancel/{order:public_id}', [CheckoutController::class, 'cancelled'])->name('checkout.cancel');
Route::post('/checkout/{order:public_id}/pay', [CheckoutController::class, 'pay'])->middleware('throttle:checkout')->name('checkout.pay');

Route::post('/payments/thawani/webhook', PaymentWebhookController::class)->name('thawani.webhook');

Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('contact.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'registerStore'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/visibility', [AdminProductController::class, 'visibility'])->name('products.visibility');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/reports', ReportController::class)->name('reports');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::get('/shipping', [ShippingController::class, 'edit'])->name('shipping.edit');
    Route::get('/shipping/{rate}', [ShippingController::class, 'show'])->name('shipping.show');
    Route::put('/shipping', [ShippingController::class, 'update'])->name('shipping.update');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::patch('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
});
