<?php

use App\Http\Controllers\Api\KnowledgeSearchController;
use App\Http\Controllers\Api\SystemStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('knowledge')->name('knowledge.')->group(function (): void {
    Route::get('/status', SystemStatusController::class)->name('status');
    Route::get('/search', KnowledgeSearchController::class)->name('search');
});
