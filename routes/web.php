<?php

use Illuminate\Support\Facades\Route;
use MarianDumitru\Netopay\Http\Controllers\NetopiaWebhookController;

Route::post('/ipn', [NetopiaWebhookController::class, 'ipn'])->name('netopia.ipn');
Route::get('/return', [NetopiaWebhookController::class, 'return'])->name('netopia.return');
Route::post('/return', [NetopiaWebhookController::class, 'return'])->name('netopia.return.post');
