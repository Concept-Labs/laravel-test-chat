<?php

use Illuminate\Support\Facades\Route;
use Mtr\TestChat\Http\Controllers\ChatController;

$routePrefix = config('test-chat.route_prefix', 'tchat');
$routeNamePrefix = config('test-chat.route_name_prefix', 'test-chat.');
$middleware = (array) config('test-chat.middleware', ['web', 'auth']);

Route::prefix($routePrefix)
    ->name($routeNamePrefix)
    ->middleware($middleware)
    ->group(function () use ($routeNamePrefix): void {
        Route::get('/', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat', fn () => redirect()->route($routeNamePrefix.'chat.index'));
        Route::get('/users', [ChatController::class, 'users'])->name('chat.users');
        Route::get('/conversations/{user}', [ChatController::class, 'conversation'])->name('chat.conversation');
        Route::post('/messages/{user}', [ChatController::class, 'send'])->name('chat.send');
    });