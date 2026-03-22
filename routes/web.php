<?php

use App\Http\Controllers\Admin\IngestionDashboardController;
use App\Http\Controllers\Admin\SourceController;
use App\Http\Controllers\Admin\SupportDocumentController;
use App\Http\Controllers\ChatConversationController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', ChatPageController::class)->name('chat');
Route::get('/chat', ChatPageController::class);
Route::get('/overview', HomeController::class)->name('home');
Route::get('/chat/conversations/{conversation}', [ChatConversationController::class, 'show'])->name('chat.conversations.show');
Route::post('/chat/messages', [ChatMessageController::class, 'storeNew'])->name('chat.messages.start');
Route::post('/chat/conversations/{conversation}/messages', [ChatMessageController::class, 'store'])->name('chat.messages.store');

Route::prefix('/admin/ingestion')->name('admin.ingestion')->group(function (): void {
    Route::get('/', IngestionDashboardController::class);
    Route::post('/sources', [SourceController::class, 'store'])->name('.sources.store');
    Route::post('/sources/{source}/crawl', [SourceController::class, 'crawl'])->name('.sources.crawl');
    Route::post('/documents', [SupportDocumentController::class, 'store'])->name('.documents.store');
});
