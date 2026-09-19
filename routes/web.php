<?php

use App\Http\Controllers\{AuthController, CheckoutController, ContactController, StoreController, TopupController, UserDashboardController};
use App\Http\Controllers\Admin\{AccountController, CategoryController, DashboardController, GachaBoxController, TopupController as AdminTopupController};
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\CatalogController::class, 'home'])->name('shop.index');
Route::get('/categories', [\App\Http\Controllers\CatalogController::class, 'index'])->name('catalog.index');
Route::get('/categories/{category}', [\App\Http\Controllers\CatalogController::class, 'index'])->name('catalog.category');
Route::get('/products/{product}', [\App\Http\Controllers\CatalogController::class, 'show'])->name('products.show')->middleware(\App\Http\Middleware\EnsureServiceCatalogEnabled::class);
Route::get('/accounts', [StoreController::class, 'index'])->name('accounts.index');
Route::get('/accounts/{account}', [StoreController::class, 'show'])->name('accounts.show');
Route::get('/gacha', [StoreController::class, 'gacha'])->name('gacha.index');
Route::get('/gacha/{box}', [StoreController::class, 'gachaShow'])->name('gacha.show');
Route::get('/news', [StoreController::class, 'news'])->name('news.index');
Route::get('/news/{news:slug}', [StoreController::class, 'newsShow'])->name('news.show');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');
Route::get('/contact/guest/{message}', [ContactController::class, 'show'])->middleware('signed')->name('contact.guest.show');
Route::post('/contact/guest/{message}/reply', [ContactController::class, 'reply'])->middleware(['signed','throttle:10,1'])->name('contact.guest.reply');
Route::middleware(['auth','auth.session'])->group(function () {
    Route::get('/contact/threads', [ContactController::class, 'index'])->name('contact.index');
    Route::get('/contact/threads/{message}', [ContactController::class, 'show'])->name('contact.show');
    Route::post('/contact/threads/{message}/reply', [ContactController::class, 'reply'])->middleware('throttle:10,1')->name('contact.reply');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
});

Route::middleware(['auth','auth.session'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logoutForm'])->name('logout.form');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth','auth.session'])->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/collection', [\App\Http\Controllers\CollectionController::class, 'index'])->name('user.collection');
    Route::get('/account/password', [\App\Http\Controllers\PasswordController::class,'edit'])->name('password.edit');
    Route::put('/account/password', [\App\Http\Controllers\PasswordController::class,'update'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/open', [\App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/products/{product}/orders', [\App\Http\Controllers\ServiceOrderController::class, 'store'])->name('orders.store')->middleware(\App\Http\Middleware\EnsureServiceCatalogEnabled::class);
    Route::get('/orders', [\App\Http\Controllers\ServiceOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\ServiceOrderController::class, 'show'])->name('orders.show');
    Route::post('/accounts/{account}/buy', [CheckoutController::class, 'buy'])->name('accounts.buy');
    Route::post('/gacha/{box}/spin', [CheckoutController::class, 'spin'])->middleware('throttle:30,1')->name('gacha.spin');
    Route::get('/purchases/{id}', [CheckoutController::class, 'purchase'])->name('purchases.show');
    Route::get('/wallet', [TopupController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topups', [TopupController::class, 'store'])->middleware('throttle:5,1')->name('wallet.topups.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('reports/sales', [\App\Http\Controllers\Admin\SalesReportController::class,'index'])->name('reports.sales');
    Route::get('reports/sales/export', [\App\Http\Controllers\Admin\SalesReportController::class,'export'])->name('reports.sales.export');
    Route::get('contacts', [\App\Http\Controllers\Admin\ContactInboxController::class,'index'])->name('contacts.index');
    Route::get('contacts/{message}', [\App\Http\Controllers\Admin\ContactInboxController::class,'show'])->name('contacts.show');
    Route::patch('contacts/{message}', [\App\Http\Controllers\Admin\ContactInboxController::class,'update'])->name('contacts.update');
    Route::post('contacts/{message}/reply', [\App\Http\Controllers\Admin\ContactInboxController::class,'reply'])->middleware('throttle:30,1')->name('contacts.reply');
    Route::get('settings', [\App\Http\Controllers\Admin\StoreSettingController::class,'edit'])->name('settings.edit');
    Route::put('settings', [\App\Http\Controllers\Admin\StoreSettingController::class,'update'])->name('settings.update');
    Route::put('settings/contact', [\App\Http\Controllers\Admin\StoreSettingController::class,'updateContact'])->name('settings.contact.update');
    Route::post('settings/banners', [\App\Http\Controllers\Admin\StoreSettingController::class,'storeBanner'])->name('settings.banners.store');
    Route::put('settings/banners/{banner}', [\App\Http\Controllers\Admin\StoreSettingController::class,'updateBanner'])->name('settings.banners.update');
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class)->except(['show','destroy'])->middleware(\App\Http\Middleware\EnsureServiceCatalogEnabled::class);
    Route::get('service-orders', [\App\Http\Controllers\Admin\ProductController::class,'orders'])->name('service-orders.index')->middleware(\App\Http\Middleware\EnsureServiceCatalogEnabled::class);
    Route::put('service-orders/{order}', [\App\Http\Controllers\Admin\ProductController::class,'updateOrder'])->name('service-orders.update')->middleware(\App\Http\Middleware\EnsureServiceCatalogEnabled::class);
    Route::resource('accounts', AccountController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('gacha', GachaBoxController::class);
    Route::get('gacha/{gacha}/items', [GachaBoxController::class, 'items'])->name('gacha.items');
    Route::post('gacha/{gacha}/items', [GachaBoxController::class, 'addItem'])->name('gacha.items.add');
    Route::delete('gacha/{gacha}/items/{item}', [GachaBoxController::class, 'deleteItem'])->name('gacha.items.delete');
    Route::put('gacha/{gacha}/items/rates', [GachaBoxController::class, 'updateDropRates'])->name('gacha.items.rates');
    Route::get('topups', [AdminTopupController::class, 'index'])->name('topups.index');
    Route::get('topups/history', [AdminTopupController::class, 'history'])->name('topups.history');
    Route::post('topups/{topup}/approve', [AdminTopupController::class, 'approve'])->name('topups.approve');
    Route::post('topups/{topup}/reject', [AdminTopupController::class, 'reject'])->name('topups.reject');
    Route::get('topups/{topup}/slip', [AdminTopupController::class, 'slip'])->name('topups.slip');
});
