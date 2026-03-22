<?php

use App\Http\Controllers\Api\SupportSearchController;
use App\Http\Controllers\Api\SystemStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('support')->name('support.')->group(function (): void {
    Route::get('/status', SystemStatusController::class)->name('status');
    Route::get('/search', SupportSearchController::class)->name('search');
});
