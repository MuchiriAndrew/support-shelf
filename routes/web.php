<?php

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
