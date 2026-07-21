<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\HomeLoginController;
use App\Http\Controllers\PosPrintController;
use App\Http\Controllers\PosSyncController;
use App\Http\Controllers\ProductLabelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeLoginController::class, 'show'])->name('home');
Route::post('/login', [HomeLoginController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('home.login');

Route::middleware('auth')->group(function (): void {
    Route::get('/products/{product}/labels', ProductLabelController::class)
        ->name('products.labels');
    Route::get('/backups/{backup}/download', BackupDownloadController::class)
        ->name('backups.download');
    Route::post('/pos/sync', PosSyncController::class)->name('pos.sync');
    Route::get('/pos/receipts/{document}', [PosPrintController::class, 'receipt'])->name('pos.print');
    Route::get('/pos/kitchen/{document}', [PosPrintController::class, 'kitchen'])->name('pos.kitchen');
    Route::get('/pos/customer-display', [PosPrintController::class, 'customerDisplay'])->name('pos.customer-display');
});

Route::get('/pos-service-worker.js', fn () => response()
    ->view('pos.service-worker')
    ->header('Content-Type', 'application/javascript')
    ->header('Service-Worker-Allowed', '/'))
    ->name('pos.service-worker');
