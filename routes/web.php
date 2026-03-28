<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ChatConversationController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/overview', HomeController::class);

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/settings/assistant', fn () => redirect()->route('filament.admin.pages.assistant-settings'))
        ->name('settings.assistant');

    Route::get('/chat', ChatPageController::class)->name('chat');
    Route::get('/chat/conversations/{conversation}', [ChatConversationController::class, 'show'])->name('chat.conversations.show');
    Route::delete('/chat/conversations/{conversation}', [ChatConversationController::class, 'destroy'])->name('chat.conversations.destroy');
    Route::post('/chat/messages', [ChatMessageController::class, 'storeNew'])->name('chat.messages.start');
    Route::post('/chat/conversations/{conversation}/messages', [ChatMessageController::class, 'store'])->name('chat.messages.store');
});
